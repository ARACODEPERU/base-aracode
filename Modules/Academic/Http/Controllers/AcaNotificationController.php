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
use Modules\Academic\Services\SmsgateService;
use Modules\Academic\Services\TelegramCourseNotifier;
use Modules\Academic\Services\VonageSmsService;
use Modules\Academic\Services\WhatsappCourseNotifier;
use Modules\Academic\Support\PhoneNumberFormatter;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Integrationhub\Services\TelegramMessageService;
use Modules\Integrationhub\Services\TelegramWebhookRegistrar;
use Modules\Integrationhub\Support\TelegramMessages;

/**
 * Notificaciones masivas de un programa de especializacion.
 *
 * El canal se ofrece solo si esta configurado: SMS via Vonage cuando el
 * parametro del sistema SC-00001 tiene credenciales, SMS via SMSGate cuando el
 * URL, usuario y contrasena (SC-00003, SC-00004 y SC-00005) estan presentes, WhatsApp
 * cuando hay un ID de flujo en Plantillas / Flujos y Telegram cuando el
 * parametro SC-00002 tiene el token del bot (en ese caso el aviso viaja al
 * chat_id que cada alumno registro con el bot). El envio real lo hace
 * SendAcaNotificationCampaign en la
 * cola, espaciando los mensajes cada 280 ms; esta pantalla solo lanza la
 * campana, consulta su avance por sondeo, entrega el enlace unico de registro
 * del bot de Telegram y permite reescribir los textos que ese bot envia (y la
 * plantilla de las campanas de Telegram).
 */
class AcaNotificationController extends Controller
{
    /** Canales que puede usar una campana. */
    private const CHANNELS = ['sms', 'smsgate', 'whatsapp', 'telegram'];

    public function __construct(
        private readonly NotificationAudienceResolver $audienceResolver,
        private readonly VonageSmsService $vonage,
        private readonly SmsgateService $smsgate,
        private readonly WhatsappCourseNotifier $whatsapp,
        private readonly TelegramBotService $telegramBot,
        private readonly TelegramMessageService $telegramMessages,
        private readonly TelegramCourseNotifier $telegramCourseNotifier,
        private readonly TelegramWebhookRegistrar $telegramWebhookRegistrar,
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
        $smsgateConfigured = $this->smsgate->isConfigured();

        // La guia paso a paso de SMSGate es solo para quien puede verla (por
        // defecto, el rol admin): ni la URL se le abre al resto. El bearer es un
        // secreto, asi que solo viaja a quien puede ver la guia.
        $canViewSmsgateGuide = $this->userCan($request, 'aca_smsgate_guia');

        return Inertia::render('Academic::Notifications/Index', [
            'courses' => $courses,
            'channels' => [
                'vonage' => $this->vonage->isConfigured(),
                'smsgate' => $smsgateConfigured,
                'whatsapp' => WhatsappCourseNotifier::isConfigured(),
                'telegram' => $telegramConfigured,
            ],
            // Datos del canal SMSGate. La URL del servidor (no es secreto) solo
            // se entrega a quien puede ver la guia.
            'smsgate' => [
                'configured' => $smsgateConfigured,
                'url' => $canViewSmsgateGuide ? $this->smsgate->url() : null,
            ],
            'canViewSmsgateGuide' => $canViewSmsgateGuide,
            'canConfigureSmsgate' => $this->userCan($request, 'aca_smsgate_configuracion'),
            // Usuario publico del bot (@) para mostrar de que bot se trata. Se
            // lee solo de cache: consultar Telegram al pintar la pantalla la
            // expondria a un timeout de red. Lo llenan las acciones explicitas
            // (obtener el enlace / registrar el webhook).
            'telegramBotUsername' => $telegramConfigured ? $this->telegramBot->cachedUsername() : null,
            // Enlace unico de registro del bot, ya armado si el usuario del bot
            // esta cacheado (la pantalla puede refrescarlo con su boton).
            'telegramLink' => $telegramConfigured ? $this->telegramBot->cachedRegistrationLink() : null,
            // Textos que envia el bot (y plantilla de las campanas), para el
            // modal de configuracion.
            'telegramMessages' => $telegramConfigured ? $this->telegramMessages->catalog() : [],
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

        if ($channel === 'smsgate' && ! $this->smsgate->isConfigured()) {
            throw ValidationException::withMessages([
                'channel' => 'El envío por SMSGate no está disponible: falta el usuario y la contraseña en los parámetros SC-00004 y SC-00005.',
            ]);
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
     * Enlace unico de registro del bot de Telegram.
     *
     * Es el mismo enlace para todas las personas (t.me/<bot>): al abrirlo y
     * pulsar Iniciar, el bot pide el numero de documento y, si la persona esta
     * en el padron (matricula en un programa de especializacion o suscripcion
     * vigente), guarda su chat_id. Aqui solo se entrega el enlace y, si se
     * indica el programa, cuantos alumnos de ese programa ya estan registrados.
     */
    public function telegramRegistrationLink(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:aca_courses,id'],
        ]);

        if (! $this->telegramBot->isConfigured()) {
            throw ValidationException::withMessages([
                'course_id' => 'Falta el token del bot de Telegram en el parámetro SC-00002.',
            ]);
        }

        // Se refresca el usuario del bot: sin el no se puede armar el enlace, y
        // de paso queda cacheado para el resto de la pantalla.
        if ($this->telegramBot->username(true) === null) {
            throw ValidationException::withMessages([
                'course_id' => 'No se pudo consultar el usuario del bot en Telegram. Revisa el token del parámetro SC-00002.',
            ]);
        }

        $registered = null;
        $pending = null;

        if (! empty($validated['course_id'])) {
            $course = $this->specializationCourseOrFail((int) $validated['course_id']);
            $counts = $this->audienceResolver->countsTelegram($course);

            $registered = $counts['total'];
            $pending = $counts['skipped'];
        }

        return response()->json([
            'message' => 'Enlace de registro listo. Es el mismo para todos: compártelo y pide a cada alumno que lo abra, pulse Iniciar y escriba su número de documento.',
            'link' => $this->telegramBot->registrationLink(),
            'username' => $this->telegramBot->cachedUsername(),
            'registered' => $registered,
            'pending' => $pending,
        ]);
    }

    /**
     * Textos que envia el bot de Telegram.
     *
     * Se devuelven con su valor vigente, su nombre, sus variables disponibles y
     * el texto de fabrica (para poder restaurarlo o compararlo).
     */
    public function telegramMessages(Request $request)
    {
        return response()->json([
            'messages' => $this->telegramMessages->catalog(),
            'configured' => $this->telegramBot->isConfigured(),
        ]);
    }

    /**
     * Guarda los textos editados.
     *
     * Un texto vacio vuelve al de fabrica, igual que el boton Restaurar: no se
     * guarda una version en blanco que dejaria al bot sin respuesta.
     */
    public function telegramMessagesSave(Request $request)
    {
        $validated = $request->validate([
            'messages' => ['required', 'array'],
            'messages.*.code' => ['required', 'string', 'max:60'],
            'messages.*.body' => ['nullable', 'string', 'max:4096'],
            'messages.*.format' => ['nullable', Rule::in([TelegramMessages::FORMAT_HTML, TelegramMessages::FORMAT_TEXT])],
        ]);

        try {
            foreach ($validated['messages'] as $message) {
                $this->telegramMessages->save(
                    (string) $message['code'],
                    $message['body'] ?? null,
                    $message['format'] ?? null,
                    (int) $request->user()->id,
                );
            }
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'messages' => 'No se pudieron guardar los textos: ' . $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Textos de Telegram guardados. Se aplican a los próximos mensajes que envíe el bot.',
            'messages' => $this->telegramMessages->catalog(),
        ]);
    }

    /**
     * Restaura un texto (o todos) al valor de fabrica.
     */
    public function telegramMessagesReset(Request $request)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:60'],
        ]);

        $code = trim((string) ($validated['code'] ?? ''));

        try {
            if ($code !== '') {
                $this->telegramMessages->reset($code);
            } else {
                $this->telegramMessages->resetAll();
            }
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'code' => 'No se pudo restaurar el texto: ' . $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => $code !== ''
                ? 'Texto restaurado al valor de fábrica.'
                : 'Todos los textos volvieron al valor de fábrica.',
            'messages' => $this->telegramMessages->catalog(),
        ]);
    }

    /**
     * Vista previa del aviso de Telegram.
     *
     * Se arma en el servidor con la misma plantilla que usa la campana, para
     * que lo que se ve en pantalla sea exactamente lo que recibira el alumno
     * (el nombre es un dato de ejemplo: en el envio real es el de cada alumno).
     */
    public function telegramPreview(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:aca_courses,id'],
            'message' => ['nullable', 'string', 'max:480'],
            'time_label' => ['nullable', 'string', 'max:60'],
        ]);

        $course = empty($validated['course_id'])
            ? null
            : AcaCourse::find((int) $validated['course_id']);

        return response()->json([
            'text' => $this->telegramCourseNotifier->renderCampaignText(
                (string) ($validated['message'] ?? ''),
                (string) ($course?->description ?? ''),
                $validated['time_label'] ?? null,
                'Alumno de ejemplo'
            ),
            'html' => $this->telegramCourseNotifier->campaignIsHtml(),
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
            // El registro vive en TelegramWebhookRegistrar: el mismo camino que
            // usan el comando del modulo y la migracion de despliegue.
            $url = $this->telegramWebhookRegistrar->register(true);
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
            return 'Ningún alumno de este programa tiene su chat_id de Telegram registrado todavía. Comparte el enlace de registro del bot y pide a cada alumno que escriba su número de documento.';
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
     * true si el usuario autenticado tiene el permiso indicado.
     */
    private function userCan(Request $request, string $permission): bool
    {
        $user = $request->user();

        return $user !== null && method_exists($user, 'can') && $user->can($permission);
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
