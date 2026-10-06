<?php

namespace Modules\Health\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\Dental\Entities\DentAppointment;
use Modules\Health\Entities\HealAppointmentNoticeDelivery;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Entities\HealGoogleCalendarEvent;
use Modules\Health\Entities\HealGoogleCalendarState;
use Modules\Health\Entities\HealPatient;
use Modules\Health\Support\AppointmentEventMapper;
use Modules\Health\Support\GoogleCalendarEventMissingException;
use Modules\Health\Support\GoogleCalendarSyncGuard;
use Modules\Health\Support\GoogleCalendarSyncTokenExpiredException;
use RuntimeException;

/**
 * Sincronizacion bidireccional entre la Agenda de Salud y Google Calendar.
 *
 * Direcciones:
 *   - push(): la cita cambio en el sistema y el evento se crea, se actualiza o
 *     se borra en Google.
 *   - pull(): el feed incremental de Google trae los cambios y los aplica a la
 *     cita; los eventos que no se pueden resolver quedan en la bandeja "Por
 *     revisar".
 *
 * El bucle Laravel -> Google -> Laravel se evita con tres capas que se apoyan
 * en la fila de mapeo: el `etag` (reconoce el evento que acabamos de escribir),
 * el `pushed_hash` (reconoce el mismo contenido aunque Google cambie el etag) y
 * GoogleCalendarSyncGuard (las escrituras que vienen de Google no disparan el
 * observador, asi que nunca se vuelven a empujar).
 */
class GoogleCalendarSyncService
{
    /** Marca de la notificacion push pendiente de leer (rebote de rafagas). */
    private const PUSH_PENDING_CACHE_KEY = 'health.google_calendar.push_pending';

    /** Accion del push: crear o actualizar el evento. */
    public const ACTION_UPSERT = 'upsert';

    /** Accion del push: borrar el evento. */
    public const ACTION_DELETE = 'delete';

    public function __construct(
        private readonly GoogleCalendarService $google,
        private readonly AppointmentEventMapper $mapper,
    ) {
    }

    /**
     * true si las tablas existen y el canal esta activo y con credenciales.
     */
    public function ready(): bool
    {
        return $this->tablesReady() && $this->google->isActive() && $this->google->isConfigured();
    }

    /**
     * true si el canal tiene encendida la direccion Google -> sistema.
     */
    public function inboundEnabled(): bool
    {
        return $this->google->isInboundEnabled();
    }

    /**
     * Marca que hay una notificacion push pendiente de leer.
     *
     * Sirve de rebote (debounce): una rafaga de notificaciones de Google encola
     * una sola lectura. Devuelve true solo cuando la marca no estaba puesta.
     */
    public function markPushPending(): bool
    {
        if (Cache::has(self::PUSH_PENDING_CACHE_KEY)) {
            return false;
        }

        Cache::put(self::PUSH_PENDING_CACHE_KEY, true, now()->addSeconds(300));

        return true;
    }

    /**
     * Limpia la marca de notificacion pendiente (lo hace el job al terminar).
     */
    public function clearPushPending(): void
    {
        Cache::forget(self::PUSH_PENDING_CACHE_KEY);
    }

    /**
     * true si las tablas de la sincronizacion estan migradas.
     */
    public function tablesReady(): bool
    {
        return Schema::hasTable('heal_google_calendar_events')
            && Schema::hasTable('heal_google_calendar_states');
    }

    /**
     * Estado completo del canal para la pantalla.
     *
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $state = $this->state();

        $webhookUrl = $this->webhookUrl();

        return array_merge($this->google->status(), [
            'ready' => $this->ready(),
            'tablesReady' => $this->tablesReady(),
            'webhookUrl' => $webhookUrl,
            'routeUrl' => $this->routeUrl(),
            'publicWebhook' => $webhookUrl !== null,
            'lastSyncAt' => $state->last_sync_at?->toDateTimeString(),
            'lastFullSyncAt' => $state->last_full_sync_at?->toDateTimeString(),
            'lastError' => $state->last_error,
            'hasSyncToken' => trim((string) $state->sync_token) !== '',
            'channelId' => $state->channel_id,
            'channelExpiresAt' => $state->channel_expires_at?->toDateTimeString(),
            'channelFresh' => $state->channelIsFresh((int) config('health.google_calendar.channel_renew_days', 2)),
            'reviewCount' => $this->reviewItems()->count(),
        ]);
    }

    /**
     * Estado del canal (crea la fila del calendario si es la primera vez).
     */
    public function state(): HealGoogleCalendarState
    {
        $calendarId = $this->google->calendarId();

        if (! Schema::hasTable('heal_google_calendar_states')) {
            return new HealGoogleCalendarState(['calendar_id' => $calendarId]);
        }

        return HealGoogleCalendarState::current($calendarId);
    }

    /**
     * Eventos de Google que esperan paciente o doctor.
     */
    public function reviewItems(): Collection
    {
        if (! $this->tablesReady()) {
            return new Collection();
        }

        return HealGoogleCalendarEvent::query()
            ->where('sync_state', HealGoogleCalendarEvent::STATE_PENDING_REVIEW)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();
    }

    /**
     * Ultimos movimientos de la sincronizacion.
     */
    public function recentMovements(int $limit = 15): Collection
    {
        if (! $this->tablesReady()) {
            return new Collection();
        }

        return HealGoogleCalendarEvent::query()
            ->with('appointment.patient')
            ->whereIn('sync_state', [
                HealGoogleCalendarEvent::STATE_LINKED,
                HealGoogleCalendarEvent::STATE_DELETED,
                HealGoogleCalendarEvent::STATE_ERROR,
                HealGoogleCalendarEvent::STATE_IGNORED,
            ])
            ->orderByDesc('updated_at')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * Envia la cita a Google (crear o actualizar el evento).
     *
     * Los estados distintos de '1' (cancelada o no concretada) no generan
     * evento: si la cita era futura se borra el evento y, si ya paso, se deja
     * como historial.
     */
    public function push(DentAppointment $appointment): void
    {
        if (! $this->ready()) {
            return;
        }

        $calendarId = $this->google->calendarId();
        $timezone = $this->google->timezone();
        $mapping = $this->mappingForAppointment($appointment->id);

        if ((string) $appointment->status !== '1') {
            if ($mapping && $mapping->isLinked() && $this->isFutureAppointment($appointment, $timezone)) {
                $this->deleteRemoteEvent($mapping);
            }

            return;
        }

        $appointment->loadMissing(['patient', 'doctor']);

        $payload = $this->mapper->payload($appointment, $timezone);
        $hash = $this->mapper->hash($this->mapper->fieldsFromPayload($payload));

        // Mismo contenido que la ultima vez que escribimos: no se vuelve a
        // tocar el evento (defensa contra el eco propio).
        if ($mapping && $mapping->isLinked() && $mapping->pushed_hash === $hash) {
            $mapping->forceFill(['last_synced_at' => now()])->save();

            return;
        }

        $eventId = $mapping && $mapping->sync_state !== HealGoogleCalendarEvent::STATE_DELETED
            ? trim((string) $mapping->google_event_id)
            : '';

        try {
            if ($eventId !== '') {
                try {
                    $event = $this->google->updateEvent($eventId, $payload);
                } catch (GoogleCalendarEventMissingException) {
                    // El evento desaparecio en Google: se crea de nuevo.
                    $event = $this->google->insertEvent($payload);
                }
            } else {
                $event = $this->google->insertEvent($payload);
            }
        } catch (\Throwable $exception) {
            $this->markError($mapping, $appointment, $calendarId, $exception);

            throw $exception;
        }

        $this->storeMapping($mapping, $appointment, $calendarId, $payload, $event, $hash);
    }

    /**
     * Borra en Google el evento de una cita que ya no existe en el sistema.
     */
    public function pushDelete(int $appointmentId): void
    {
        if (! $this->ready()) {
            return;
        }

        $mapping = $this->mappingForAppointment($appointmentId);

        if (! $mapping) {
            return;
        }

        $this->deleteRemoteEvent($mapping);
    }

    /**
     * Lee los cambios de Google y los aplica.
     *
     * La primera vez (o cuando el syncToken caduco) la lectura es completa y
     * abarca la ventana configurada; despues es incremental y solo trae lo que
     * cambio desde la ultima marca.
     *
     * @return array<string, mixed>
     */
    public function pull(bool $forceFull = false): array
    {
        return $this->runPull($forceFull, false);
    }

    /**
     * @return array<string, mixed>
     */
    private function runPull(bool $forceFull, bool $retried): array
    {
        if (! $this->ready()) {
            return [
                'skipped' => true,
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'review' => 0,
            ];
        }

        $state = $this->state();

        if ($forceFull) {
            $state->forgetSyncToken();
        }

        $calendarId = $this->google->calendarId();
        $timezone = $this->google->timezone();
        $counts = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'review' => 0];

        try {
            $syncToken = trim((string) $state->sync_token);
            $fullSync = $syncToken === '';
            $pageToken = null;
            $nextSyncToken = null;

            do {
                $params = $fullSync
                    ? [
                        'timeMin' => Carbon::now($timezone)->subDays($this->google->windowDays())->startOfDay()->toRfc3339String(),
                        'timeMax' => Carbon::now($timezone)->addDays($this->google->windowDays())->endOfDay()->toRfc3339String(),
                        'singleEvents' => 'true',
                        'showDeleted' => 'true',
                        'maxResults' => (int) config('health.google_calendar.page_size', 250),
                    ]
                    // En modo incremental Google solo acepta el token (y la
                    // pagina): cualquier otro filtro invalida la peticion.
                    : ['syncToken' => $syncToken];

                if ($pageToken !== null) {
                    $params['pageToken'] = $pageToken;
                }

                $page = $this->google->listEvents($params);

                foreach ((array) ($page['items'] ?? []) as $item) {
                    if (is_array($item)) {
                        $this->applyItem($item, $calendarId, $timezone, $counts);
                    }
                }

                $pageToken = $page['nextPageToken'] ?? null;
                $nextSyncToken = $page['nextSyncToken'] ?? $nextSyncToken;
            } while ($pageToken !== null && $pageToken !== '');
        } catch (GoogleCalendarSyncTokenExpiredException) {
            $state->forgetSyncToken();

            if ($retried) {
                throw new RuntimeException('Google Calendar sigue rechazando el token de sincronizacion.');
            }

            return $this->runPull(true, true);
        } catch (\Throwable $exception) {
            $state->registerError($exception);

            throw $exception;
        }

        $state->forceFill([
            'sync_token' => $nextSyncToken ?: $state->sync_token,
            'last_sync_at' => now(),
            'last_full_sync_at' => $fullSync ? now() : $state->last_full_sync_at,
            'last_error' => null,
        ])->save();

        return $counts + ['skipped' => false, 'full' => $fullSync];
    }

    /**
     * Aplica un item del feed de Google.
     *
     * @param array<string, mixed> $item
     * @param array<string, int>   $counts
     */
    private function applyItem(array $item, string $calendarId, string $timezone, array &$counts): void
    {
        $eventId = trim((string) ($item['id'] ?? ''));

        if ($eventId === '') {
            return;
        }

        $mapping = $this->mappingForEvent($calendarId, $eventId);

        if ((string) ($item['status'] ?? '') === 'cancelled') {
            if ($this->applyCancellation($mapping)) {
                $counts['deleted']++;
            }

            return;
        }

        $etag = $this->nullableString($item['etag'] ?? null);
        $updatedAt = $this->moment($item['updated'] ?? null);

        // El evento trae nuestra marca: se recupera el enlace si la fila se
        // perdio (por ejemplo, se restauro la base de datos).
        if (! $mapping) {
            $mapping = $this->adoptByMarker($item, $calendarId, $eventId);
        }

        if ($mapping && $mapping->appointment_id) {
            $this->applyKnownEvent($mapping, $item, $etag, $updatedAt, $timezone, $counts);

            return;
        }

        if ($this->handleUnknownEvent($mapping, $item, $calendarId, $timezone) === 'created') {
            $counts['created']++;
        } else {
            $counts['review']++;
        }
    }

    /**
     * Aplica el evento a una cita conocida.
     *
     * @param array<string, mixed> $item
     * @param array<string, int>   $counts
     */
    private function applyKnownEvent(
        HealGoogleCalendarEvent $mapping,
        array $item,
        ?string $etag,
        ?Carbon $updatedAt,
        string $timezone,
        array &$counts
    ): void {
        // Mismo etag que la ultima escritura: es el eco de lo que acabamos de
        // enviar. No se toca la cita.
        if ($etag !== null && $etag === $mapping->etag) {
            $mapping->forceFill(['google_updated_at' => $updatedAt, 'last_synced_at' => now()])->save();

            return;
        }

        $appointment = DentAppointment::with(['patient', 'doctor'])->find($mapping->appointment_id);

        if (! $appointment) {
            // La cita ya no existe: la fila queda como lapida, no se resucita.
            $mapping->forceFill([
                'appointment_id' => null,
                'sync_state' => HealGoogleCalendarEvent::STATE_DELETED,
                'last_synced_at' => now(),
            ])->save();

            return;
        }

        $eventHash = $this->mapper->hash($this->mapper->fieldsFromEvent($item, $timezone));

        // El contenido es el mismo que ya teniamos aunque el etag cambio: solo
        // se refresca la firma.
        if ($mapping->pushed_hash === $eventHash) {
            $mapping->forceFill([
                'etag' => $etag,
                'google_updated_at' => $updatedAt,
                'last_synced_at' => now(),
            ])->save();

            return;
        }

        $changes = $this->mapper->appointmentChanges($item, $appointment, $timezone);

        if ($changes === []) {
            // Cambio que no se mapea (color, invitados, recordatorios): se
            // adopta el contenido como propio para no revisarlo en cada vuelta.
            $mapping->forceFill([
                'etag' => $etag,
                'google_updated_at' => $updatedAt,
                'pushed_hash' => $eventHash,
                'last_synced_at' => now(),
            ])->save();

            return;
        }

        $this->touchAppointment($appointment, $changes);

        $mapping->forceFill([
            'etag' => $etag,
            'google_updated_at' => $updatedAt,
            'pushed_hash' => $eventHash,
            'origin' => HealGoogleCalendarEvent::ORIGIN_GOOGLE,
            'sync_state' => HealGoogleCalendarEvent::STATE_LINKED,
            'error_message' => null,
            'last_synced_at' => now(),
        ])->save();

        $counts['updated']++;
    }

    /**
     * Atiende un evento que no tiene cita: lo crea si puede resolver paciente y
     * doctor, y si no lo deja en la bandeja "Por revisar".
     *
     * @param array<string, mixed> $item
     * @return string 'created' o 'review'
     */
    private function handleUnknownEvent(?HealGoogleCalendarEvent $mapping, array $item, string $calendarId, string $timezone): string
    {
        // Si ya estaba en la bandeja no se vuelve a intentar solo: el usuario es
        // quien decide (evita que un evento ambiguo se cree en cada lectura).
        if ($mapping === null) {
            $participants = $this->resolveParticipants($item);

            if ($participants !== null) {
                $appointment = $this->createAppointmentFromEvent($item, $participants['patient'], $participants['doctor'], $timezone);

                if ($appointment !== null) {
                    $this->attachEventToAppointment($appointment, $item, $calendarId, $timezone);

                    return 'created';
                }
            }
        }

        $this->storeReviewItem($mapping, $item, $calendarId, $timezone);

        return 'review';
    }

    /**
     * Borra la cita de un evento cancelado en Google.
     *
     * Si la cita ya tiene una atencion registrada no se borra (se perderia el
     * vinculo clinico): se marca como no concretada y se deja el motivo.
     */
    private function applyCancellation(?HealGoogleCalendarEvent $mapping): bool
    {
        if (! $mapping) {
            return false;
        }

        if ($mapping->appointment_id) {
            $appointment = DentAppointment::with('healthAttention')->find($mapping->appointment_id);

            if ($appointment && $appointment->healthAttention()->exists()) {
                $this->touchAppointment($appointment, ['status' => '3']);

                $mapping->forceFill([
                    'sync_state' => HealGoogleCalendarEvent::STATE_LINKED,
                    'error_message' => 'El evento se borro en Google, pero la cita tiene una atencion registrada: se marco como no concretada.',
                    'last_synced_at' => now(),
                ])->save();

                return false;
            }

            if ($appointment) {
                GoogleCalendarSyncGuard::withoutSync(function () use ($appointment) {
                    // Sin la cita, sus avisos por SMS quedarian huerfanos.
                    HealAppointmentNoticeDelivery::where('appointment_id', $appointment->id)->delete();

                    $appointment->delete();
                });
            }
        }

        $mapping->forceFill([
            'appointment_id' => null,
            'sync_state' => HealGoogleCalendarEvent::STATE_DELETED,
            'etag' => null,
            'pushed_hash' => null,
            'last_synced_at' => now(),
        ])->save();

        return true;
    }

    /**
     * Resuelve paciente y doctor de un evento creado directamente en Google.
     *
     * El paciente se identifica por el nombre exacto que abre el titulo del
     * evento; el doctor solo cuando hay uno solo en el consultorio o cuando el
     * evento trae nuestra marca. Si algo no cuadra, el evento va a la bandeja.
     *
     * @param array<string, mixed> $item
     * @return array{patient: HealPatient, doctor: HealDoctor}|null
     */
    private function resolveParticipants(array $item): ?array
    {
        $private = $item['extendedProperties']['private'] ?? [];

        $patient = null;
        $patientId = (int) ($private['healPatientId'] ?? 0);

        if ($patientId > 0) {
            $patient = HealPatient::with('person')->find($patientId);
        }

        if (! $patient) {
            $patient = $this->matchPatientBySummary((string) ($item['summary'] ?? ''));
        }

        $doctor = null;
        $doctorId = (int) ($private['healDoctorId'] ?? 0);

        if ($doctorId > 0) {
            $doctor = HealDoctor::with('person')->find($doctorId);
        }

        if (! $doctor) {
            $doctor = $this->defaultDoctor();
        }

        if ($patient && $doctor) {
            return ['patient' => $patient, 'doctor' => $doctor];
        }

        return null;
    }

    /**
     * Paciente cuyo nombre completo coincide con lo que abre el titulo.
     */
    private function matchPatientBySummary(string $summary): ?HealPatient
    {
        $summary = trim($summary);

        if ($summary === '') {
            return null;
        }

        $parts = preg_split('/\s+[—–-]\s+/u', $summary, 2);
        $candidate = trim((string) ($parts[0] ?? ''));

        if ($candidate === '' || mb_strlen($candidate) < 3) {
            return null;
        }

        return HealPatient::with('person')
            ->whereHas('person', function ($query) use ($candidate) {
                $query->whereRaw('LOWER(full_name) = ?', [mb_strtolower($candidate)]);
            })
            ->first();
    }

    /**
     * Doctor por defecto: el unico del consultorio (con varios, hay que elegir).
     */
    private function defaultDoctor(): ?HealDoctor
    {
        $doctors = HealDoctor::with('person')->limit(2)->get();

        return $doctors->count() === 1 ? $doctors->first() : null;
    }

    /**
     * Crea la cita a partir del evento de Google.
     *
     * Se hace dentro del guard: la cita nace de Google y no debe empujarse de
     * vuelta; el enlace se encarga despues de dejar el evento con nuestra marca.
     *
     * @param array<string, mixed> $item
     */
    private function createAppointmentFromEvent(array $item, HealPatient $patient, HealDoctor $doctor, string $timezone): ?DentAppointment
    {
        $start = $this->mapper->momentFromEvent($item['start'] ?? null, $timezone);

        if (! $start) {
            return null;
        }

        $end = $this->mapper->momentFromEvent($item['end'] ?? null, $timezone) ?? $start->copy()->addMinutes(30);

        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->copy()->addMinutes(30);
        }

        $patient->loadMissing('person');
        $doctor->loadMissing('person');

        // La descripcion del evento puede venir como "Paciente — motivo": se
        // guarda solo el motivo.
        $probe = new DentAppointment();
        $probe->setRelation('patient', $patient->person);

        $description = $this->mapper->descriptionFromSummary((string) ($item['summary'] ?? ''), $probe) ?? 'Cita';
        $details = mb_substr(trim((string) ($item['description'] ?? '')), 0, 255);
        $location = mb_substr(trim((string) ($item['location'] ?? '')), 0, 500);
        $userId = $this->systemUserId();

        return GoogleCalendarSyncGuard::withoutSync(function () use ($patient, $doctor, $start, $end, $description, $details, $location, $userId) {
            return DentAppointment::create([
                'patient_id' => $patient->id,
                'patient_person_id' => $patient->person_id,
                'doctor_id' => $doctor->id,
                'doctor_person_id' => $doctor->person_id,
                'date_appointmen' => $start->toDateString(),
                'time_appointmen' => $start->format('H:i:s'),
                'date_end_appointmen' => $end->toDateString(),
                'time_end_appointmen' => $end->format('H:i:s'),
                'email' => $patient->person?->email,
                'telephone' => $patient->person?->telephone,
                'description' => $description,
                'details' => $details !== '' ? $details : 'Cita creada desde Google Calendar.',
                'message' => $location !== '' ? $location : null,
                'status' => '1',
                'created_user_id' => $userId,
                'updated_user_id' => $userId,
            ]);
        });
    }

    /**
     * Crea la cita de un evento que estaba en la bandeja "Por revisar".
     *
     * @throws RuntimeException cuando el evento ya no esta pendiente.
     */
    public function review(int $mappingId, int $patientId, ?int $doctorId = null): DentAppointment
    {
        if (! Schema::hasTable('heal_google_calendar_events')) {
            throw new RuntimeException('Faltan las migraciones de Google Calendar.');
        }

        $mapping = HealGoogleCalendarEvent::findOrFail($mappingId);

        if (! $mapping->isPendingReview()) {
            throw new RuntimeException('Ese evento ya no esta pendiente de revision.');
        }

        $item = is_array($mapping->payload) ? $mapping->payload : [];
        $timezone = $this->google->timezone();
        $calendarId = $mapping->calendar_id !== '' ? $mapping->calendar_id : $this->google->calendarId();

        $patient = HealPatient::with('person')->find($patientId);

        if (! $patient) {
            throw new RuntimeException('Elige un paciente valido para el evento.');
        }

        $doctor = $doctorId !== null
            ? HealDoctor::with('person')->find($doctorId)
            : $this->defaultDoctor();

        if (! $doctor) {
            throw new RuntimeException('Elige el doctor de la cita.');
        }

        $appointment = $this->createAppointmentFromEvent($item, $patient, $doctor, $timezone);

        if (! $appointment) {
            throw new RuntimeException('El evento de Google no tiene una hora valida.');
        }

        $this->attachEventToAppointment($appointment, $item, $calendarId, $timezone, $mapping);

        return $appointment;
    }

    /**
     * Descarta un evento de la bandeja (no se trae al sistema).
     */
    public function discard(int $mappingId): void
    {
        if (! Schema::hasTable('heal_google_calendar_events')) {
            return;
        }

        $mapping = HealGoogleCalendarEvent::find($mappingId);

        if (! $mapping) {
            return;
        }

        $mapping->forceFill([
            'sync_state' => HealGoogleCalendarEvent::STATE_IGNORED,
            'payload' => null,
            'error_message' => null,
            'last_synced_at' => now(),
        ])->save();
    }

    /**
     * Registra o renueva el canal de notificaciones push.
     *
     * @return array{ok: bool, message: string, expiresAt: ?string}
     */
    public function refreshChannel(): array
    {
        if (! $this->ready()) {
            return ['ok' => false, 'message' => 'El canal no esta listo: revisa el interruptor Activo y las credenciales de Google.', 'expiresAt' => null];
        }

        $webhookUrl = $this->webhookUrl();

        if ($webhookUrl === null) {
            return [
                'ok' => false,
                'message' => 'Google solo entrega notificaciones a una URL HTTPS publica. Define HEALTH_GOOGLE_WEBHOOK_URL (por ejemplo con un tunel) y vuelve a intentarlo; mientras tanto la reconciliacion programada revisa los cambios.',
                'expiresAt' => null,
            ];
        }

        $state = $this->state();

        // El canal anterior se cierra para no acumular canales vivos.
        if (trim((string) $state->channel_id) !== '') {
            try {
                $this->google->stopChannel((string) $state->channel_id, (string) $state->resource_id);
            } catch (\Throwable) {
                // Si ya expiro, cerrarlo no importa.
            }
        }

        try {
            $channel = $this->google->watch($webhookUrl);
        } catch (\Throwable $exception) {
            $state->registerError($exception);

            return ['ok' => false, 'message' => $exception->getMessage(), 'expiresAt' => null];
        }

        $expiresAt = $this->expirationMoment($channel['expiration'] ?? null);

        $state->forceFill([
            'channel_id' => $this->nullableString($channel['id'] ?? null),
            'resource_id' => $this->nullableString($channel['resourceId'] ?? null),
            'resource_uri' => $this->nullableString($channel['resourceUri'] ?? null),
            'channel_expires_at' => $expiresAt,
            'last_error' => null,
        ])->save();

        return [
            'ok' => true,
            'message' => 'Canal de notificaciones registrado.',
            'expiresAt' => $expiresAt?->toDateTimeString(),
        ];
    }

    /**
     * URL publica a la que Google debe notificar (null si no sirve todavia).
     */
    public function webhookUrl(): ?string
    {
        $configured = trim((string) config('health.google_calendar.webhook_url', ''));

        if ($configured !== '') {
            return $configured;
        }

        $route = $this->routeUrl();

        // Google exige HTTPS con dominio publico: una URL local no sirve.
        if ($route === null || ! str_starts_with($route, 'https://')) {
            return null;
        }

        return $route;
    }

    /**
     * URL del webhook en este servidor (aunque no sea publica).
     */
    public function routeUrl(): ?string
    {
        try {
            $url = route('health_google_calendar_webhook');
        } catch (\Throwable) {
            return null;
        }

        return $url !== '' ? $url : null;
    }

    /**
     * Aplica cambios a la cita sin disparar el push hacia Google.
     *
     * @param array<string, mixed> $changes
     */
    private function touchAppointment(DentAppointment $appointment, array $changes): void
    {
        GoogleCalendarSyncGuard::withoutSync(function () use ($appointment, $changes) {
            $appointment->update($changes);
        });
    }

    /**
     * Deja el evento con la marca del sistema (healAppointmentId) y guarda el
     * enlace con la cita.
     *
     * @param array<string, mixed> $item
     */
    private function attachEventToAppointment(
        DentAppointment $appointment,
        array $item,
        string $calendarId,
        string $timezone,
        ?HealGoogleCalendarEvent $mapping = null
    ): void {
        $appointment->loadMissing(['patient', 'doctor']);

        $mapping = $mapping ?? $this->mappingForAppointment($appointment->id);

        $payload = $this->mapper->payload($appointment, $timezone);
        $hash = $this->mapper->hash($this->mapper->fieldsFromPayload($payload));
        $eventId = trim((string) ($item['id'] ?? ($mapping->google_event_id ?? '')));

        try {
            $event = $eventId !== ''
                ? $this->google->updateEvent($eventId, $payload)
                : $this->google->insertEvent($payload);

            $this->storeMapping($mapping, $appointment, $calendarId, $payload, $event, $hash);
        } catch (\Throwable $exception) {
            // La cita ya es del sistema: el enlace se guarda igual y el proximo
            // push vuelve a intentar dejar la marca en el evento.
            $attributes = [
                'appointment_id' => $appointment->id,
                'calendar_id' => $calendarId,
                'google_event_id' => $eventId !== '' ? $eventId : null,
                'etag' => $this->nullableString($item['etag'] ?? null),
                'google_updated_at' => $this->moment($item['updated'] ?? null),
                'pushed_hash' => $this->mapper->hash($this->mapper->fieldsFromEvent($item, $timezone)),
                'origin' => HealGoogleCalendarEvent::ORIGIN_GOOGLE,
                'sync_state' => HealGoogleCalendarEvent::STATE_LINKED,
                'error_message' => mb_substr($exception->getMessage(), 0, 500),
                'last_synced_at' => now(),
                'payload' => null,
            ] + $this->mapper->mappingAttributes($payload, $timezone);

            if ($mapping) {
                $mapping->forceFill($attributes)->save();
            } else {
                HealGoogleCalendarEvent::updateOrCreate(['appointment_id' => $appointment->id], $attributes);
            }
        }
    }

    /**
     * Guarda el evento de Google en la bandeja "Por revisar".
     *
     * @param array<string, mixed> $item
     */
    private function storeReviewItem(?HealGoogleCalendarEvent $mapping, array $item, string $calendarId, string $timezone): void
    {
        $attributes = [
            'appointment_id' => null,
            'calendar_id' => $calendarId,
            'google_event_id' => $this->nullableString($item['id'] ?? null),
            'etag' => $this->nullableString($item['etag'] ?? null),
            'google_updated_at' => $this->moment($item['updated'] ?? null),
            'pushed_hash' => $this->mapper->hash($this->mapper->fieldsFromEvent($item, $timezone)),
            'origin' => HealGoogleCalendarEvent::ORIGIN_GOOGLE,
            'sync_state' => HealGoogleCalendarEvent::STATE_PENDING_REVIEW,
            'summary' => mb_substr(trim((string) ($item['summary'] ?? '')), 0, 255),
            'starts_at' => $this->mapper->momentFromEvent($item['start'] ?? null, $timezone),
            'ends_at' => $this->mapper->momentFromEvent($item['end'] ?? null, $timezone),
            'location' => mb_substr(trim((string) ($item['location'] ?? '')), 0, 255),
            'payload' => $item,
            'error_message' => null,
            'last_synced_at' => now(),
        ];

        if ($mapping) {
            $mapping->forceFill($attributes)->save();

            return;
        }

        HealGoogleCalendarEvent::create($attributes);
    }

    /**
     * Fila de mapeo de una cita.
     */
    private function mappingForAppointment(int $appointmentId): ?HealGoogleCalendarEvent
    {
        if (! Schema::hasTable('heal_google_calendar_events')) {
            return null;
        }

        return HealGoogleCalendarEvent::where('appointment_id', $appointmentId)->first();
    }

    /**
     * Fila de mapeo de un evento del calendario.
     */
    private function mappingForEvent(string $calendarId, string $eventId): ?HealGoogleCalendarEvent
    {
        if (! Schema::hasTable('heal_google_calendar_events')) {
            return null;
        }

        return HealGoogleCalendarEvent::query()
            ->where('calendar_id', $calendarId)
            ->where('google_event_id', $eventId)
            ->first();
    }

    /**
     * Recupera el enlace usando la marca que lleva el propio evento.
     *
     * @param array<string, mixed> $item
     */
    private function adoptByMarker(array $item, string $calendarId, string $eventId): ?HealGoogleCalendarEvent
    {
        $appointmentId = (int) ($item['extendedProperties']['private']['healAppointmentId'] ?? 0);

        if ($appointmentId <= 0) {
            return null;
        }

        $mapping = $this->mappingForAppointment($appointmentId);

        if (! $mapping) {
            return null;
        }

        $mapping->forceFill([
            'calendar_id' => $calendarId,
            'google_event_id' => $eventId,
        ])->save();

        return $mapping;
    }

    /**
     * Guarda el resultado de un push exitoso.
     *
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $event
     */
    private function storeMapping(
        ?HealGoogleCalendarEvent $mapping,
        DentAppointment $appointment,
        string $calendarId,
        array $payload,
        array $event,
        string $hash
    ): void {
        $attributes = [
            'appointment_id' => $appointment->id,
            'calendar_id' => $calendarId,
            'google_event_id' => $this->nullableString($event['id'] ?? null) ?? $mapping?->google_event_id,
            'etag' => $this->nullableString($event['etag'] ?? null),
            'google_updated_at' => $this->moment($event['updated'] ?? null),
            'pushed_hash' => $hash,
            'origin' => HealGoogleCalendarEvent::ORIGIN_HEALTH,
            'sync_state' => HealGoogleCalendarEvent::STATE_LINKED,
            'error_message' => null,
            'last_synced_at' => now(),
            'payload' => null,
        ] + $this->mapper->mappingAttributes($payload, $this->google->timezone());

        if ($mapping) {
            $mapping->forceFill($attributes)->save();

            return;
        }

        HealGoogleCalendarEvent::updateOrCreate(['appointment_id' => $appointment->id], $attributes);
    }

    /**
     * Registra el fallo del push en la fila de mapeo (y la crea si no existia).
     */
    private function markError(?HealGoogleCalendarEvent $mapping, DentAppointment $appointment, string $calendarId, \Throwable $exception): void
    {
        $attributes = [
            'appointment_id' => $appointment->id,
            'calendar_id' => $calendarId,
            'origin' => HealGoogleCalendarEvent::ORIGIN_HEALTH,
            'sync_state' => HealGoogleCalendarEvent::STATE_ERROR,
            'error_message' => mb_substr($exception->getMessage(), 0, 500),
            'last_synced_at' => now(),
        ];

        try {
            if ($mapping) {
                $mapping->forceFill($attributes)->save();

                return;
            }

            HealGoogleCalendarEvent::updateOrCreate(['appointment_id' => $appointment->id], $attributes);
        } catch (\Throwable) {
            // Si ni siquiera se puede registrar el error, el job ya reintentara.
        }
    }

    /**
     * Borra el evento en Google y deja la fila como lapida.
     */
    private function deleteRemoteEvent(HealGoogleCalendarEvent $mapping): void
    {
        $eventId = trim((string) $mapping->google_event_id);

        if ($eventId !== '' && $mapping->sync_state !== HealGoogleCalendarEvent::STATE_DELETED) {
            try {
                $this->google->deleteEvent($eventId);
            } catch (\Throwable $exception) {
                $mapping->forceFill([
                    'sync_state' => HealGoogleCalendarEvent::STATE_ERROR,
                    'error_message' => mb_substr($exception->getMessage(), 0, 500),
                ])->save();

                throw $exception;
            }
        }

        // Se conserva la fila (sin cita) para reconocer el eco tardio del feed
        // de Google: un evento cancelado que ya no nos interesa.
        $mapping->forceFill([
            'appointment_id' => null,
            'sync_state' => HealGoogleCalendarEvent::STATE_DELETED,
            'etag' => null,
            'pushed_hash' => null,
            'error_message' => null,
            'last_synced_at' => now(),
        ])->save();
    }

    /**
     * true si la cita todavia no ocurre.
     */
    private function isFutureAppointment(DentAppointment $appointment, string $timezone): bool
    {
        return $this->mapper->startAt($appointment, $timezone)->greaterThan(Carbon::now($timezone));
    }

    /**
     * Usuario responsable de una cita creada desde Google.
     *
     * Las columnas `created_user_id` y `updated_user_id` no aceptan nulos: si no
     * hay sesion (lo normal, lo crea una cola) se registra el primer usuario.
     */
    private function systemUserId(): int
    {
        $userId = Auth::id();

        if ($userId) {
            return (int) $userId;
        }

        return (int) (User::query()->min('id') ?? 0);
    }

    /**
     * Momento de un campo `updated` de Google.
     */
    private function moment(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Vencimiento del canal: Google lo manda en milisegundos Unix.
     */
    private function expirationMoment(mixed $value): ?Carbon
    {
        if (is_string($value) || is_int($value)) {
            $milliseconds = (int) $value;

            if ($milliseconds > 0) {
                return Carbon::createFromTimestampMs($milliseconds);
            }
        }

        $moment = $this->moment($value);

        return $moment;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
