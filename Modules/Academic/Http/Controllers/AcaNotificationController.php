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
use Modules\Academic\Services\TelegramCourseNotifier;
use Modules\Academic\Services\VonageSmsService;
use Modules\Academic\Services\WhatsappCourseNotifier;
use Modules\Academic\Support\PhoneNumberFormatter;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Integrationhub\Services\TelegramRegistrationService;

/**
 * Notificaciones masivas de un programa de especializacion.
 *
 * El canal se ofrece solo si esta configurado: SMS via Vonage cuando el
 * parametro del sistema SC-00001 tiene credenciales, WhatsApp cuando hay un ID
 * de flujo en Plantillas / Flujos y Telegram cuando el parametro SC-00002 tiene
 * el token del bot (en ese caso el aviso viaja al chat_id que cada alumno
 * registro con el bot). El envio real lo hace SendAcaNotificationCampaign en la
 * cola, espaciando los mensajes cada 280 ms; esta pantalla solo lanza la
 * campana, consulta su avance por sondeo y gestiona los enlaces de registro de
 * Telegram.
 */
class AcaNotificationController extends Controller
{
    /** Canales que puede usar una campana. */
    private const CHANNELS = ['sms', 'whatsapp', 'telegram'];

    public function __construct(
        private readonly NotificationAudienceResolver $audienceResolver,
        private readonly VonageSmsService $vonage,
        private readonly WhatsappCourseNotifier $whatsapp,
        private readonly TelegramBotService $telegramBot,
        private readonly TelegramRegistrationService $telegramRegistration,
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
        $telegramConfigured = TelegramCourseNotifier::isConfigured();

        return Inertia::render('Academic::Notifications/Index', [
            'courses' => $courses,
            'channels' => [
                'vonage' => $this->vonage->isConfigured(),
                'whatsapp' => WhatsappCourseNotifier::isConfigured(),
                'telegram' => $telegramConfigured,
            ],
            // Usuario publico del bot (@) para mostrar de que bot se trata. Se
            // lee solo de cache: consultar Telegram al pintar la pantalla la
            // expondria a un timeout de red. Lo llenan las acciones explicitas
            // (generar enlaces / registrar webhook).
            'telegramBotUsername' => $telegramConfigured ? $this->telegramBot->cachedUsername() : null,
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
     *
     * El canal importa: el SMS y el WhatsApp cuentan por telefono, mientras que
     * Telegram cuenta por chat_id y descuenta a quien todavia no se registro.
     */
    public function audience(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:aca_courses,id'],
            'channel' => ['nullable', Rule::in(self::CHANNELS)],
        ]);

        $course = $this->specializationCourseOrFail((int) $validated['course_id']);

        if (($validated['channel'] ?? 'sms') === 'telegram') {
            return response()->json($this->audienceResolver->countsTelegram($course));
        }

        return response()->json($this->audienceResolver->counts($course));
    }

    /**
     * Crea la campana (con el padron congelado) y la envia a la cola.
     *
     * En modo prueba el padron son los numeros que escribe el administrador
     * (ya completos con su codigo de pais) en lugar de los alumnos del
     * programa, y el programa pasa a ser opcional: solo aporta el nombre del
     * curso que viaja en el mensaje. El modo prueba no aplica a Telegram, que
     * necesita un chat_id.
     */
    public function store(Request $request)
    {
        $isTest = $request->boolean('is_test');

        $validated = $request->validate([
            'course_id' => [$isTest ? 'nullable' : 'required', 'integer', 'exists:aca_courses,id'],
            'channel' => ['required', Rule::in(self::CHANNELS)],
            'message' => ['required', 'string', 'max:480'],
            'time_label' => ['nullable', 'string', 'max:60'],
            'is_test' => ['nullable', 'boolean'],
            'test_numbers' => [$isTest ? 'required' : 'nullable', 'string', 'max:1000'],
        ]);

        $channel = $validated['channel'];

        if ($channel === 'telegram' && $isTest) {
            throw ValidationException::withMessages([
                'is_test' => 'El modo prueba solo aplica a SMS y WhatsApp: en Telegram los avisos van a los alumnos que registraron su chat_id.',
            ]);
        }

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

        if ($channel === 'sms' && ! $this->vonage->isConfigured()) {
            throw ValidationException::withMessages([
                'channel' => 'El envio por SMS no esta disponible: falta configurar las credenciales de Vonage en el parametro SC-00001.',
            ]);
        }

        if ($channel === 'whatsapp') {
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

        if ($channel === 'telegram' && ! TelegramCourseNotifier::isConfigured()) {
            throw ValidationException::withMessages([
                'channel' => 'El envío por Telegram no está disponible: falta el token del bot en el parámetro SC-00002.',
            ]);
        }

        $recipients = $isTest
            ? $this->testRecipients((string) $validated['test_numbers'])
            : ($channel === 'telegram'
                ? $this->audienceResolver->resolveTelegram($course)
                : $this->audienceResolver->resolve($course));

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                $isTest ? 'test_numbers' : 'course_id' => $this->emptyAudienceMessage($isTest, $channel),
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
                    'phone' => $recipient['phone'] ?? null,
                    'chat_id' => $recipient['chat_id'] ?? null,
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
     * Genera los enlaces de registro de Telegram para los alumnos del programa
     * que todavia no registraron su chat_id.
     *
     * El enlace es un deep link (t.me/<bot>?start=<codigo>) de un solo uso: al
     * abrirlo y pulsar Iniciar, el webhook del bot guarda el chat_id de la
     * persona y desde ese momento entra en las campanas de Telegram.
     */
    public function telegramLinks(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:aca_courses,id'],
        ]);

        if (! $this->telegramBot->isConfigured()) {
            throw ValidationException::withMessages([
                'course_id' => 'Falta el token del bot de Telegram en el parámetro SC-00002.',
            ]);
        }

        $course = $this->specializationCourseOrFail((int) $validated['course_id']);

        // Se refresca el usuario del bot: sin el no se puede armar el deep link.
        if ($this->telegramBot->username(true) === null) {
            throw ValidationException::withMessages([
                'course_id' => 'No se pudo consultar el usuario del bot en Telegram. Revisa el token del parámetro SC-00002.',
            ]);
        }

        $links = [];
        $skipped = 0;

        foreach ($this->audienceResolver->pendingTelegram($course) as $person) {
            $contact = $this->telegramRegistration->issueCode((int) $person['person_id'], $person['name']);
            $link = $contact ? $this->telegramBot->registrationLink((string) $contact->registration_code) : null;

            if ($link === null) {
                $skipped++;
                continue;
            }

            $links[] = [
                'person_id' => $person['person_id'],
                'name' => $person['name'],
                'link' => $link,
                'expires_at' => $contact->code_expires_at?->toIso8601String(),
            ];
        }

        return response()->json([
            'message' => count($links) === 0
                ? 'Todos los alumnos de este programa ya tienen su chat de Telegram registrado.'
                : 'Enlaces generados. Compártelos con cada alumno (uno por uno): el enlace es personal y de un solo uso.',
            'links' => $links,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Registra en Telegram la URL del webhook de este sistema y publica el menu
     * de comandos del bot.
     */
    public function telegramWebhook(Request $request)
    {
        if (! $this->telegramBot->isConfigured()) {
            throw ValidationException::withMessages([
                'webhook' => 'Falta el token del bot de Telegram en el parámetro SC-00002.',
            ]);
        }

        try {
            $url = $this->telegramBot->webhookUrl();
            $this->telegramBot->setWebhook($url, true);
            $this->telegramBot->setMyCommands($this->telegramBot->defaultCommands());
            $username = $this->telegramBot->username(true);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'webhook' => 'No se pudo registrar el webhook: ' . $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Webhook registrado en Telegram. Desde ahora los mensajes al bot llegan a este sistema.',
            'url' => $url,
            'username' => $username,
        ]);
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
     * Mensaje cuando el padron quedo vacio, segun el modo y el canal.
     */
    private function emptyAudienceMessage(bool $isTest, string $channel): string
    {
        if ($isTest) {
            return 'Escribe al menos un numero de prueba valido.';
        }

        if ($channel === 'telegram') {
            return 'Ningún alumno de este programa tiene su chat_id de Telegram registrado todavía. Genera los enlaces de registro y compártelos.';
        }

        return 'El programa no tiene alumnos con un telefono valido para notificar.';
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
            ->get(['name', 'phone', 'chat_id', 'error_message']);

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
                'phone' => $recipient->phone ? PhoneNumberFormatter::toDisplay($recipient->phone) : null,
                'chat_id' => $recipient->chat_id,
                'error' => $recipient->error_message,
            ])->all(),
        ];
    }
}
