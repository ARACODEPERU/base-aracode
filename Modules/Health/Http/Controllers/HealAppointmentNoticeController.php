<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Health\Entities\HealAppointmentNotice;
use Modules\Health\Entities\HealAppointmentNoticeDelivery;
use Modules\Health\Entities\HealSetting;
use Modules\Health\Jobs\SendAppointmentNotice;
use Modules\Health\Jobs\SendAppointmentNoticeTest;
use Modules\Health\Rules\PeruMobile;
use Modules\Health\Services\AppointmentNoticeService;
use Modules\Health\Support\AppointmentNoticeMessage;
use Modules\Health\Support\HealthPhoneNumber;

/**
 * Pantalla Salud > Avisos (notificaciones a pacientes).
 *
 * Configura los tres bloques de recordatorio de citas (dos "minutos antes" y
 * uno "un dia antes"), muestra el estado del canal SMS Gateway con su
 * interruptor Activo (parametro SC-00009) y permite previsualizar y enviar un
 * SMS de prueba. Los avisos reales los envia el planificador por la cola.
 *
 * La pantalla muestra tambien las ultimas entregas con su estado y motivo: los
 * avisos que no salieron (telefono mal guardado, por ejemplo) se ven aqui y se
 * pueden reintentar sin tocar la base. El telefono de prueba se valida con la
 * regla de Salud (celular del Peru) para no encolar un numero que SMSGate va a
 * rechazar.
 */
class HealAppointmentNoticeController extends Controller
{
    public function __construct(
        private readonly AppointmentNoticeService $notices,
    ) {
    }

    public function index(): Response
    {
        $blocks = HealAppointmentNotice::query()
            ->orderBy('id')
            ->get()
            ->map(fn (HealAppointmentNotice $notice) => [
                'id' => $notice->id,
                'key' => $notice->key,
                'active' => (bool) $notice->active,
                'minutes_before' => $notice->minutes_before,
                'send_time' => $notice->send_time ? mb_substr((string) $notice->send_time, 0, 5) : null,
                'message' => (string) $notice->message,
            ])
            ->values();

        return Inertia::render('Health::Notices/Index', [
            'notices' => $blocks,
            'deliveries' => $this->recentDeliveries(),
            'channel' => array_merge($this->notices->channelStatus(), [
                'parameterCode' => $this->notices->channelParameterCode(),
                'parameterId' => $this->notices->channelParameterId(),
                'parameterCodes' => $this->notices->parameterCodes(),
            ]),
            'variables' => AppointmentNoticeMessage::variables(),
            'timeOptions' => [
                ['value' => 15, 'label' => '15 minutos antes'],
                ['value' => 30, 'label' => '30 minutos antes'],
                ['value' => 60, 'label' => '1 hora antes'],
                ['value' => 120, 'label' => '2 horas antes'],
                ['value' => 180, 'label' => '3 horas antes'],
                ['value' => 360, 'label' => '6 horas antes'],
            ],
            'parametersUrl' => route('parameters'),
            'clinicName' => HealSetting::first()?->establishment_name,
        ]);
    }

    /**
     * Guarda los tres bloques de aviso.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'notices' => ['required', 'array'],
            'notices.*.key' => ['required', Rule::in(AppointmentNoticeMessage::keys())],
            'notices.*.active' => ['boolean'],
            'notices.*.minutes_before' => ['nullable', 'integer', 'min:5', 'max:10080'],
            'notices.*.send_time' => ['nullable', 'date_format:H:i'],
            'notices.*.message' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($validated['notices'] as $index => $payload) {
            $isDayBefore = $payload['key'] === AppointmentNoticeMessage::KEY_DAY_BEFORE;

            if ($isDayBefore && blank($payload['send_time'] ?? null)) {
                throw ValidationException::withMessages([
                    "notices.{$index}.send_time" => 'Indica la hora a la que se envia el aviso del día anterior.',
                ]);
            }

            if (! $isDayBefore && blank($payload['minutes_before'] ?? null)) {
                throw ValidationException::withMessages([
                    "notices.{$index}.minutes_before" => 'Indica cuántos minutos antes de la cita se envía este aviso.',
                ]);
            }

            $notice = HealAppointmentNotice::where('key', $payload['key'])->first();

            if (! $notice) {
                continue;
            }

            $notice->update([
                'active' => (bool) ($payload['active'] ?? false),
                'minutes_before' => $isDayBefore ? null : (int) $payload['minutes_before'],
                'send_time' => $isDayBefore ? $payload['send_time'] . ':00' : null,
                'message' => $payload['message'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Avisos guardados. Se aplican a los próximos recordatorios que detecte el planificador.',
        ]);
    }

    /**
     * Texto final (plano) de un mensaje con datos de ejemplo.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'text' => $this->notices->renderSampleText($validated['message'] ?? ''),
        ]);
    }

    /**
     * Encola un SMS de prueba con el mensaje del bloque indicado.
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30', new PeruMobile()],
            'key' => ['required', Rule::in(AppointmentNoticeMessage::keys())],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $this->notices->isConfigured()) {
            throw ValidationException::withMessages([
                'phone' => 'Falta configurar SMSGate (Salud): revisa los parámetros SC-00006, SC-00007 y SC-00008.',
            ]);
        }

        if (! $this->notices->isChannelActive()) {
            throw ValidationException::withMessages([
                'phone' => 'El canal SMS Gateway está desactivado: enciende el interruptor Activo (' . $this->notices->channelParameterCode() . ') para enviar avisos.',
            ]);
        }

        $message = $validated['message'] ?? null;

        if (blank($message)) {
            $message = HealAppointmentNotice::where('key', $validated['key'])->value('message');
        }

        $phone = HealthPhoneNumber::normalize($validated['phone']);

        SendAppointmentNoticeTest::dispatch($phone, $this->notices->renderSampleText($message));

        return response()->json([
            'message' => 'SMS de prueba encolado. Llega en unos segundos si el worker de la cola está corriendo.',
        ], 202);
    }

    /**
     * Vuelve a encolar un aviso que no salio (telefono corregido, canal que
     * estuvo apagado, intento fallido...).
     *
     * La fila de entrega es la misma: la clave unica (cita, bloque y canal) no
     * admite duplicados, asi que solo se reinicia su estado y se despacha de
     * nuevo el job. Un aviso ya enviado no se repite.
     */
    public function retry(HealAppointmentNoticeDelivery $delivery)
    {
        if (! $this->notices->isConfigured() || ! $this->notices->isChannelActive()) {
            throw ValidationException::withMessages([
                'delivery' => 'El canal SMS Gateway no esta listo: revisa el interruptor Activo (' . $this->notices->channelParameterCode() . ') y las credenciales de SMSGate (Salud).',
            ]);
        }

        if ($delivery->status === 'sent') {
            throw ValidationException::withMessages([
                'delivery' => 'Ese aviso ya se envio; no se repite.',
            ]);
        }

        $delivery->update([
            'status' => 'pending',
            'error_message' => null,
            'sent_at' => null,
        ]);

        SendAppointmentNotice::dispatch($delivery->id);

        return response()->json([
            'message' => 'Aviso encolado otra vez. El resultado se vera en la tabla de entregas.',
        ], 202);
    }

    /**
     * Ultimas entregas de avisos, con el estado y el motivo del fallo.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentDeliveries(): array
    {
        // Instalacion aun sin migrar: la tabla de entregas todavia no existe.
        if (! Schema::hasTable('heal_appointment_notice_deliveries')) {
            return [];
        }

        return HealAppointmentNoticeDelivery::query()
            ->with(['appointment.patient', 'notice'])
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (HealAppointmentNoticeDelivery $delivery) => [
                'id' => $delivery->id,
                'appointment_id' => $delivery->appointment_id,
                'correlative' => $delivery->appointment?->correlative,
                'patient' => $delivery->appointment?->patient?->full_name,
                'telephone' => $delivery->appointment?->telephone,
                'notice_key' => $delivery->notice?->key,
                'channel' => $delivery->channel,
                'status' => $delivery->status,
                'error_message' => $delivery->error_message,
                'sent_at' => $delivery->sent_at?->format('d/m/Y H:i'),
                'created_at' => $delivery->created_at?->format('d/m/Y H:i'),
            ])
            ->values()
            ->all();
    }
}
