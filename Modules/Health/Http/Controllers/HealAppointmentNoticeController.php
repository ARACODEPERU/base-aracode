<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Health\Entities\HealAppointmentNotice;
use Modules\Health\Entities\HealSetting;
use Modules\Health\Jobs\SendAppointmentNoticeTest;
use Modules\Health\Services\AppointmentNoticeService;
use Modules\Health\Support\AppointmentNoticeMessage;

/**
 * Pantalla Salud > Avisos (notificaciones a pacientes).
 *
 * Configura los tres bloques de recordatorio de citas (dos "minutos antes" y
 * uno "un dia antes"), muestra el estado del canal SMS Gateway con su
 * interruptor Activo (parametro SC-00009) y permite previsualizar y enviar un
 * SMS de prueba. Los avisos reales los envia el planificador por la cola.
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
            'phone' => ['required', 'string', 'max:30'],
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

        SendAppointmentNoticeTest::dispatch($validated['phone'], $this->notices->renderSampleText($message));

        return response()->json([
            'message' => 'SMS de prueba encolado. Llega en unos segundos si el worker de la cola está corriendo.',
        ], 202);
    }
}
