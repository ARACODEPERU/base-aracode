<?php

namespace Modules\Academic\Http\Controllers;

use App\Services\JobOffersAccess;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaNotificationCampaign;
use Modules\Academic\Entities\AcaNotificationCampaignRecipient;
use Modules\Academic\Jobs\SendAcaNotificationCampaign;
use Modules\Academic\Services\NotificationAudienceResolver;
use Modules\Academic\Services\VonageSmsService;
use Modules\Academic\Services\WhatsappCourseNotifier;
use Modules\Academic\Support\PhoneNumberFormatter;

/**
 * Notificaciones masivas de un programa de especializacion.
 *
 * El canal se ofrece solo si esta configurado: SMS via Vonage cuando el
 * parametro del sistema SC-00001 tiene credenciales y WhatsApp cuando hay un ID
 * de flujo en Plantillas / Flujos. El envio real lo hace
 * SendAcaNotificationCampaign en la cola, espaciando los mensajes cada 280 ms;
 * esta pantalla solo lanza la campana y consulta su avance por sondeo.
 */
class AcaNotificationController extends Controller
{
    public function __construct(
        private readonly NotificationAudienceResolver $audienceResolver,
        private readonly VonageSmsService $vonage,
        private readonly WhatsappCourseNotifier $whatsapp,
    ) {
    }

    public function index(Request $request)
    {
        $courses = AcaCourse::query()
            ->where('type_description', JobOffersAccess::SPECIALIZATION_TYPE)
            ->orderBy('description')
            ->get(['id', 'description'])
            ->map(fn (AcaCourse $course) => [
                'id' => $course->id,
                'description' => $course->description,
            ])
            ->values();

        $activeCampaign = $this->activeCampaign($request);

        return Inertia::render('Academic::Notifications/Index', [
            'courses' => $courses,
            'channels' => [
                'vonage' => $this->vonage->isConfigured(),
                'whatsapp' => WhatsappCourseNotifier::isConfigured(),
            ],
            'timeSuggestions' => ['5 minutos', '10 minutos', '15 minutos', '30 minutos'],
            'countryCode' => (string) config('academic.notifications.country_code', '51'),
            'intervalMs' => (int) config('academic.notifications.interval_ms', 280),
            // Tarifa de Vonage para Peru (USD) que se muestra en el pie de pagina.
            'smsPricePeru' => (float) config('academic.notifications.vonage.sms_price_peru_usd', 0.23369),
            'activeCampaign' => $activeCampaign ? $this->campaignPayload($activeCampaign) : null,
        ]);
    }

    /**
     * Previsualizacion del padron antes de enviar.
     */
    public function audience(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:aca_courses,id'],
        ]);

        $course = $this->specializationCourseOrFail((int) $validated['course_id']);

        return response()->json($this->audienceResolver->counts($course));
    }

    /**
     * Crea la campana (con el padron congelado) y la envia a la cola.
     *
     * En modo prueba el padron son los numeros que escribe el administrador
     * (ya completos con su codigo de pais) en lugar de los alumnos del
     * programa, y el programa pasa a ser opcional: solo aporta el nombre del
     * curso que viaja en el mensaje.
     */
    public function store(Request $request)
    {
        $isTest = $request->boolean('is_test');

        $validated = $request->validate([
            'course_id' => [$isTest ? 'nullable' : 'required', 'integer', 'exists:aca_courses,id'],
            'channel' => ['required', Rule::in(['sms', 'whatsapp'])],
            'message' => ['required', 'string', 'max:480'],
            'time_label' => ['nullable', 'string', 'max:60'],
            'is_test' => ['nullable', 'boolean'],
            'test_numbers' => [$isTest ? 'required' : 'nullable', 'string', 'max:1000'],
        ]);

        // El curso, cuando se elige, debe ser un programa de especializacion.
        $course = null;

        if (! empty($validated['course_id'])) {
            $course = $this->specializationCourseOrFail((int) $validated['course_id']);
        }

        if ($activeCampaign = $this->activeCampaign($request)) {
            throw ValidationException::withMessages([
                'course_id' => 'Ya tienes una campana en curso (#'.$activeCampaign->id.'). Espera a que termine antes de lanzar otra.',
            ]);
        }

        if ($validated['channel'] === 'sms' && ! $this->vonage->isConfigured()) {
            throw ValidationException::withMessages([
                'channel' => 'El envio por SMS no esta disponible: falta configurar las credenciales de Vonage en el parametro SC-00001.',
            ]);
        }

        if ($validated['channel'] === 'whatsapp') {
            if (! WhatsappCourseNotifier::isConfigured()) {
                throw ValidationException::withMessages([
                    'channel' => 'El envio por WhatsApp no esta disponible: falta el ID del flujo en Plantillas / Flujos.',
                ]);
            }

            if (blank($validated['time_label'] ?? null)) {
                throw ValidationException::withMessages([
                    'time_label' => 'Indica el tiempo que se enviara al alumno por WhatsApp.',
                ]);
            }
        }

        $recipients = $isTest
            ? $this->testRecipients((string) $validated['test_numbers'])
            : $this->audienceResolver->resolve($course);

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                $isTest ? 'test_numbers' : 'course_id' => $isTest
                    ? 'Escribe al menos un numero de prueba valido.'
                    : 'El programa no tiene alumnos con un telefono valido para notificar.',
            ]);
        }

        $campaign = DB::transaction(function () use ($request, $course, $validated, $recipients, $isTest) {
            $campaign = AcaNotificationCampaign::create([
                'user_id' => $request->user()->id,
                'course_id' => $course?->id,
                'channel' => $validated['channel'],
                'is_test' => $isTest,
                'message' => $validated['message'],
                'time_label' => $validated['time_label'] ?? null,
                'total_recipients' => $recipients->count(),
                'status' => 'pending',
            ]);

            $now = now();

            AcaNotificationCampaignRecipient::insert(
                $recipients->map(fn (array $recipient) => [
                    'campaign_id' => $campaign->id,
                    'student_id' => $recipient['student_id'],
                    'person_id' => $recipient['person_id'],
                    'name' => $recipient['name'],
                    'phone' => $recipient['phone'],
                    'source' => $recipient['source'],
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );

            return $campaign;
        });

        SendAcaNotificationCampaign::dispatch($campaign->id);

        return response()->json([
            'message' => 'El envio de notificaciones ha sido encolado. Este proceso continuara aunque salgas de la pantalla.',
            'campaign' => $this->campaignPayload($campaign),
        ], 202);
    }

    /**
     * Avance de una campana para la barra de progreso.
     */
    public function progress(Request $request, int $id)
    {
        $campaign = AcaNotificationCampaign::findOrFail($id);

        if ((int) $campaign->user_id !== (int) $request->user()->id) {
            abort(403, 'No puedes consultar una campana de otro usuario.');
        }

        return response()->json($this->campaignPayload($campaign));
    }

    /**
     * Numeros del modo prueba: deben venir con su codigo de pais.
     *
     * @throws ValidationException cuando algun numero esta incompleto.
     */
    private function testRecipients(string $raw): Collection
    {
        $parsed = PhoneNumberFormatter::parseInternationalList($raw);

        if ($parsed['invalid'] !== []) {
            throw ValidationException::withMessages([
                'test_numbers' => 'Estos numeros de prueba no son validos (deben incluir el codigo de pais, de 8 a 15 digitos): '
                    . implode(', ', $parsed['invalid']),
            ]);
        }

        return $this->audienceResolver->resolveTestNumbers($parsed['numbers']);
    }

    /**
     * Campana del usuario que todavia no termina (se reabre al volver a la
     * pantalla para retomar el aviso en segundo plano).
     */
    private function activeCampaign(Request $request): ?AcaNotificationCampaign
    {
        return AcaNotificationCampaign::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'processing'])
            // Ventana de seguridad: una campana que quedo colgada (worker caido)
            // deja de bloquear el lanzamiento de una nueva despues de 3 horas.
            ->where('created_at', '>=', now()->subHours(3))
            ->latest('id')
            ->first();
    }

    /**
     * Curso validado: debe existir, ser un programa de especializacion.
     */
    private function specializationCourseOrFail(int $courseId): AcaCourse
    {
        $course = AcaCourse::findOrFail($courseId);

        if (strcasecmp((string) $course->type_description, JobOffersAccess::SPECIALIZATION_TYPE) !== 0) {
            abort(422, 'Las notificaciones masivas solo aplican a programas de especializacion.');
        }

        return $course;
    }

    /**
     * Datos de la campana que consume la interfaz.
     */
    private function campaignPayload(AcaNotificationCampaign $campaign): array
    {
        $campaign->loadMissing('course');

        $errors = $campaign->recipients()
            ->where('status', 'failed')
            ->orderBy('id')
            ->limit(10)
            ->get(['name', 'phone', 'error_message']);

        return [
            'id' => $campaign->id,
            'channel' => $campaign->channel,
            'is_test' => (bool) $campaign->is_test,
            'status' => $campaign->status,
            'course' => $campaign->course?->description,
            'time_label' => $campaign->time_label,
            'message' => $campaign->message,
            'total' => (int) $campaign->total_recipients,
            'sent' => (int) $campaign->sent_count,
            'failed' => (int) $campaign->failed_count,
            'percent' => $campaign->percent(),
            'current_phone' => $campaign->current_phone
                ? PhoneNumberFormatter::toDisplay($campaign->current_phone)
                : null,
            'finished_at' => $campaign->finished_at?->toIso8601String(),
            'error_message' => $campaign->error_message,
            'errors' => $errors->map(fn ($recipient) => [
                'name' => $recipient->name,
                'phone' => PhoneNumberFormatter::toDisplay($recipient->phone),
                'error' => $recipient->error_message,
            ])->all(),
        ];
    }
}
