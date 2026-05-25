<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Dental\Entities\DentAppointment;
use Modules\Health\Entities\HealAttention;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Entities\HealPatient;
use Modules\Health\Support\PendingSignatureReminder;

class HealAgendaController extends Controller
{
    private const NORMAL_START = '08:00';
    private const NORMAL_END = '20:00';
    private const SLOT_MINUTES = 15;
    private const ATTENTION_BLOCK_MINUTES = 60;
    private const ON_TIME_GRACE_MINUTES = 5;

    public function index(Request $request): Response
    {
        $selectedDate = $this->normalizeDate($request->get('date')) ?? now()->toDateString();
        $currentDoctor = $this->currentDoctor();
        $selectedDoctorId = (int) ($request->get('doctor_id') ?: ($currentDoctor?->id ?: HealDoctor::query()->value('id')));

        return Inertia::render('Health::Agendas/Index', [
            'doctors' => $this->doctorOptions(),
            'patients' => $this->patientOptions(),
            'currentDoctor' => $currentDoctor ? $this->doctorOption($currentDoctor) : null,
            'canChooseAppointmentDoctor' => $this->canChooseDoctor(),
            'selectedDoctorId' => $selectedDoctorId,
            'selectedDate' => $selectedDate,
            'preselectedPatientId' => $request->integer('patient_id') ?: null,
            'normalStart' => self::NORMAL_START,
            'normalEnd' => self::NORMAL_END,
            'slotMinutes' => self::SLOT_MINUTES,
            'attentionBlockMinutes' => self::ATTENTION_BLOCK_MINUTES,
            'pendingSignatures' => PendingSignatureReminder::items(),
        ]);
    }

    public function day(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => ['required', 'exists:heal_doctors,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'days' => ['nullable', 'integer', 'min:1', 'max:7'],
        ]);

        $doctorId = (int) $data['doctor_id'];
        $startDate = $data['date'];
        $days = (int) ($data['days'] ?? 1);

        $dateCursor = Carbon::parse($startDate);
        $result = [];

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $dateCursor->copy()->addDays($i)->toDateString();
            $this->expireMissedAppointments($doctorId, $currentDate);
            $result[$currentDate] = [
                'events' => $this->dayEvents($doctorId, $currentDate),
                'free_slots' => $this->freeSlots($doctorId, $currentDate, self::SLOT_MINUTES),
                'punctuality' => $this->punctualitySummary($doctorId, $currentDate),
            ];
        }

        return response()->json($result);
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => ['required', 'exists:heal_doctors,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:240', 'multiple_of:15'],
        ]);

        $duration = (int) ($data['duration_minutes'] ?? self::SLOT_MINUTES);

        $doctorId = (int) $data['doctor_id'];
        $this->expireMissedAppointments($doctorId, $data['date']);

        return response()->json([
            'free_slots' => $this->freeSlots($doctorId, $data['date'], $duration),
        ]);
    }

    public function storeAppointment(Request $request)
    {
        $request->merge([
            'patient_id_value' => $this->extractOptionId($request->get('patient_id')),
            'doctor_id_value' => $this->extractOptionId($request->get('doctor_id')),
        ]);

        $data = $request->validate([
            'patient_id_value' => ['required', 'exists:heal_patients,id'],
            'doctor_id_value' => [$this->canChooseDoctor() ? 'required' : 'nullable', 'exists:heal_doctors,id'],
            'date_appointmen' => ['required', 'date_format:Y-m-d'],
            'time_appointmen' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240', 'multiple_of:15'],
            'description' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $doctorId = $this->resolveAppointmentDoctorId($request);
        $patient = HealPatient::with('person')->findOrFail((int) $data['patient_id_value']);
        $doctor = HealDoctor::with('person')->findOrFail($doctorId);
        $start = Carbon::parse($data['date_appointmen'] . ' ' . $data['time_appointmen'])->seconds(0);
        $end = $start->copy()->addMinutes((int) $data['duration_minutes']);

        if ($this->hasConflict($doctorId, $start, $end)) {
            throw ValidationException::withMessages([
                'time_appointmen' => 'Ese horario no está disponible. Horarios libres: ' . $this->formatFreeSlots($doctorId, $start->toDateString(), (int) $data['duration_minutes']),
            ]);
        }

        $appointment = DentAppointment::create([
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
            'description' => $data['description'],
            'details' => $data['details'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => '1',
            'created_user_id' => Auth::id(),
            'updated_user_id' => Auth::id(),
        ]);

        return response()->json([
            'appointment' => $appointment->load(['patient', 'doctor']),
            'events' => $this->dayEvents($doctorId, $start->toDateString()),
            'free_slots' => $this->freeSlots($doctorId, $start->toDateString(), self::SLOT_MINUTES),
            'punctuality' => $this->punctualitySummary($doctorId, $start->toDateString()),
        ]);
    }

    public function moveAppointment(Request $request)
    {
        $data = $request->validate([
            'appointment_id' => ['required', 'exists:dent_appointments,id'],
            'doctor_id' => ['required', 'exists:heal_doctors,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240', 'multiple_of:15'],
        ]);

        $appointmentId = (int) $data['appointment_id'];
        $doctorId = (int) $data['doctor_id'];
        $newStart = Carbon::parse($data['date'] . ' ' . $data['time'])->seconds(0);
        $newEnd = $newStart->copy()->addMinutes((int) $data['duration_minutes']);

        $appointment = DentAppointment::findOrFail($appointmentId);

        if ($appointment->status !== '1') {
            throw ValidationException::withMessages([
                'appointment_id' => 'Solo se pueden reagendar citas pendientes.',
            ]);
        }

        if ($this->hasConflict($doctorId, $newStart, $newEnd, $appointmentId)) {
            throw ValidationException::withMessages([
                'time' => 'Ese horario no está disponible. Horarios libres: ' . $this->formatFreeSlots($doctorId, $data['date'], (int) $data['duration_minutes']),
            ]);
        }

        $doctor = HealDoctor::with('person')->findOrFail($doctorId);

        $appointment->update([
            'doctor_id' => $doctorId,
            'doctor_person_id' => $doctor->person_id,
            'date_appointmen' => $data['date'],
            'time_appointmen' => $data['time'],
            'date_end_appointmen' => $data['date'],
            'time_end_appointmen' => $newEnd->format('H:i:s'),
            'status' => '1',
            'updated_user_id' => Auth::id(),
        ]);

        return response()->json([
            'appointment' => $appointment->fresh()->load(['patient', 'doctor']),
        ]);
    }

    private function dayEvents(int $doctorId, string $date): array
    {
        $appointments = DentAppointment::with(['patient', 'doctor', 'healthAttention'])
            ->where('doctor_id', $doctorId)
            ->where('status', '<>', '0')
            ->whereDate('date_appointmen', $date)
            ->orderBy('time_appointmen')
            ->get()
            ->map(function (DentAppointment $appointment) {
                $arrival = $this->appointmentArrival($appointment);

                return [
                    'id' => 'appointment-' . $appointment->id,
                    'source_id' => $appointment->id,
                    'type' => 'appointment',
                    'patient_id' => (int) $appointment->patient_id,
                    'doctor_id' => (int) $appointment->doctor_id,
                    'status' => $appointment->status,
                    'title' => $appointment->description ?: 'Cita',
                    'patient' => $appointment->patient?->full_name,
                    'doctor' => $appointment->doctor?->full_name,
                    'start' => Carbon::parse($appointment->date_appointmen . ' ' . $appointment->time_appointmen)->format('Y-m-d H:i:s'),
                    'end' => $this->appointmentEnd($appointment)->format('Y-m-d H:i:s'),
                    'details' => $appointment->details,
                    'no_show_at' => $appointment->no_show_at?->format('Y-m-d H:i:s'),
                    'attention_at' => $appointment->healthAttention?->attention_at?->format('Y-m-d H:i:s'),
                    'arrival_status' => $arrival['status'],
                    'arrival_minutes' => $arrival['minutes'],
                    'can_start_attention' => $appointment->status === '1' && $this->canStartAttentionForAppointment($appointment),
                ];
            });

        $attentions = HealAttention::with(['patient.person', 'doctor.person', 'appointment'])
            ->where('doctor_id', $doctorId)
            ->whereDate('attention_at', $date)
            ->orderBy('attention_at')
            ->get()
            ->map(function (HealAttention $attention) {
                $start = Carbon::parse($attention->attention_at)->seconds(0);
                $end = $attention->signed_at
                    ? Carbon::parse($attention->signed_at)->seconds(0)
                    : $start->copy()->addMinutes(self::ATTENTION_BLOCK_MINUTES);

                return [
                    'id' => 'attention-' . $attention->id,
                    'source_id' => $attention->id,
                    'type' => 'attention',
                    'patient_id' => (int) $attention->patient_id,
                    'doctor_id' => (int) $attention->doctor_id,
                    'status' => $attention->signed_at ? 'signed' : 'registered',
                    'title' => 'Atención',
                    'patient' => $attention->patient?->person?->full_name,
                    'doctor' => $attention->doctor?->person?->full_name,
                    'start' => $start->format('Y-m-d H:i:s'),
                    'end' => $end->greaterThan($start) ? $end->format('Y-m-d H:i:s') : $start->copy()->addMinutes(self::SLOT_MINUTES)->format('Y-m-d H:i:s'),
                    'details' => $attention->diagnosis ?: $attention->patient_story,
                    'appointment_at' => $attention->appointment
                        ? Carbon::parse($attention->appointment->date_appointmen . ' ' . $attention->appointment->time_appointmen)->format('Y-m-d H:i:s')
                        : null,
                ];
            });

        return $appointments
            ->concat($attentions)
            ->sortBy('start')
            ->values()
            ->all();
    }

    private function busyIntervals(int $doctorId, string $date, ?int $excludeAppointmentId = null): array
    {
        $appointments = DentAppointment::query()
            ->where('doctor_id', $doctorId)
            ->where('status', '1')
            ->whereDate('date_appointmen', $date)
            ->when($excludeAppointmentId, function ($query) use ($excludeAppointmentId) {
                $query->where('id', '<>', $excludeAppointmentId);
            })
            ->get()
            ->map(function (DentAppointment $appointment) {
                return [
                    Carbon::parse($appointment->date_appointmen . ' ' . $appointment->time_appointmen)->seconds(0),
                    $this->appointmentEnd($appointment),
                ];
            });

        $attentions = HealAttention::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('attention_at', $date)
            ->get()
            ->map(function (HealAttention $attention) {
                $start = Carbon::parse($attention->attention_at)->seconds(0);
                $end = $attention->signed_at
                    ? Carbon::parse($attention->signed_at)->seconds(0)
                    : $start->copy()->addMinutes(self::ATTENTION_BLOCK_MINUTES);

                return [$start, $end->greaterThan($start) ? $end : $start->copy()->addMinutes(self::SLOT_MINUTES)];
            });

        return $appointments->concat($attentions)->all();
    }

    private function appointmentEnd(DentAppointment $appointment): Carbon
    {
        $start = Carbon::parse($appointment->date_appointmen . ' ' . $appointment->time_appointmen)->seconds(0);

        if (!$appointment->time_end_appointmen) {
            return $start->copy()->addMinutes(self::SLOT_MINUTES);
        }

        return Carbon::parse(($appointment->date_end_appointmen ?: $appointment->date_appointmen) . ' ' . $appointment->time_end_appointmen)->seconds(0);
    }

    private function expireMissedAppointments(int $doctorId, string $date): void
    {
        $now = Carbon::now(config('app.timezone'))->seconds(0);

        DentAppointment::with('healthAttention')
            ->where('doctor_id', $doctorId)
            ->where('status', '1')
            ->whereDate('date_appointmen', '<=', $date)
            ->get()
            ->each(function (DentAppointment $appointment) use ($now) {
                if ($appointment->healthAttention || $this->appointmentEnd($appointment)->gt($now)) {
                    return;
                }

                $appointment->update([
                    'status' => '3',
                    'no_show_at' => $now,
                    'details' => mb_substr(trim((string) $appointment->details . "\nCita no concretada automaticamente el " . $now->format('Y-m-d H:i:s')), 0, 255),
                    'updated_user_id' => Auth::id() ?: $appointment->updated_user_id,
                ]);
            });
    }

    private function punctualitySummary(int $doctorId, string $date): array
    {
        $items = HealAttention::with(['patient.person', 'appointment'])
            ->where('doctor_id', $doctorId)
            ->whereDate('attention_at', $date)
            ->whereNotNull('appointment_id')
            ->whereHas('appointment')
            ->orderBy('attention_at')
            ->get()
            ->map(function (HealAttention $attention) {
                $appointmentAt = Carbon::parse($attention->appointment->date_appointmen . ' ' . $attention->appointment->time_appointmen)->seconds(0);
                $attentionAt = Carbon::parse($attention->attention_at)->seconds(0);
                $minutes = (int) $appointmentAt->diffInMinutes($attentionAt, false);
                $status = $this->arrivalStatus($minutes);

                return [
                    'patient_id' => (int) $attention->patient_id,
                    'patient' => $attention->patient?->person?->full_name,
                    'appointment_at' => $appointmentAt->format('Y-m-d H:i:s'),
                    'attention_at' => $attentionAt->format('Y-m-d H:i:s'),
                    'minutes' => $minutes,
                    'status' => $status,
                ];
            })
            ->values();

        return [
            'early' => $items->where('status', 'early')->values(),
            'on_time' => $items->where('status', 'on_time')->values(),
            'late' => $items->where('status', 'late')->values(),
            'grace_minutes' => self::ON_TIME_GRACE_MINUTES,
        ];
    }

    private function appointmentArrival(DentAppointment $appointment): array
    {
        if (!$appointment->healthAttention) {
            return ['status' => null, 'minutes' => null];
        }

        $appointmentAt = Carbon::parse($appointment->date_appointmen . ' ' . $appointment->time_appointmen)->seconds(0);
        $attentionAt = Carbon::parse($appointment->healthAttention->attention_at)->seconds(0);
        $minutes = (int) $appointmentAt->diffInMinutes($attentionAt, false);

        return [
            'status' => $this->arrivalStatus($minutes),
            'minutes' => $minutes,
        ];
    }

    private function arrivalStatus(int $minutes): string
    {
        if ($minutes < 0) {
            return 'early';
        }

        if ($minutes <= self::ON_TIME_GRACE_MINUTES) {
            return 'on_time';
        }

        return 'late';
    }

    private function freeSlots(int $doctorId, string $date, int $durationMinutes): array
    {
        $day = Carbon::parse($date);
        $cursor = Carbon::parse($day->toDateString() . ' ' . self::NORMAL_START)->seconds(0);
        $endOfNormalDay = Carbon::parse($day->toDateString() . ' ' . self::NORMAL_END)->seconds(0);
        $busyIntervals = $this->busyIntervals($doctorId, $date);
        $slots = [];

        while ($cursor->copy()->addMinutes($durationMinutes)->lte($endOfNormalDay)) {
            $slotEnd = $cursor->copy()->addMinutes($durationMinutes);
            if (!$this->intervalOverlaps($cursor, $slotEnd, $busyIntervals)) {
                $slots[] = [
                    'start' => $cursor->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                ];
            }

            $cursor->addMinutes(self::SLOT_MINUTES);
        }

        return $slots;
    }

    private function hasConflict(int $doctorId, Carbon $start, Carbon $end, ?int $excludeAppointmentId = null): bool
    {
        return $this->intervalOverlaps($start, $end, $this->busyIntervals($doctorId, $start->toDateString(), $excludeAppointmentId));
    }

    private function intervalOverlaps(Carbon $start, Carbon $end, array $intervals): bool
    {
        foreach ($intervals as [$busyStart, $busyEnd]) {
            if ($start->lt($busyEnd) && $end->gt($busyStart)) {
                return true;
            }
        }

        return false;
    }

    private function formatFreeSlots(int $doctorId, string $date, int $durationMinutes): string
    {
        $slots = $this->freeSlots($doctorId, $date, $durationMinutes);

        if (count($slots) === 0) {
            return 'no hay espacios libres dentro del horario normal.';
        }

        return collect($slots)
            ->take(8)
            ->map(fn ($slot) => $slot['start'] . '-' . $slot['end'])
            ->implode(', ');
    }

    private function normalizeDate($date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function doctorOptions()
    {
        return HealDoctor::with('person')
            ->orderBy('id')
            ->get()
            ->map(fn (HealDoctor $doctor) => $this->doctorOption($doctor))
            ->values();
    }

    private function doctorOption(HealDoctor $doctor): array
    {
        return [
            'code' => $doctor->id,
            'name' => $doctor->person?->full_name,
            'email' => $doctor->person?->email,
            'telephone' => $doctor->person?->telephone,
            'specialty' => $doctor->specialty,
        ];
    }

    private function patientOptions()
    {
        return HealPatient::with('person')
            ->orderBy('id')
            ->get()
            ->map(fn (HealPatient $patient) => [
                'code' => $patient->id,
                'name' => $patient->person?->full_name,
                'email' => $patient->person?->email,
                'telephone' => $patient->person?->telephone,
            ])
            ->values();
    }

    private function extractOptionId($value): ?int
    {
        if (is_array($value)) {
            return $value['code'] ?? $value['id'] ?? null;
        }

        return $value ? (int) $value : null;
    }

    private function resolveAppointmentDoctorId(Request $request): int
    {
        if ($this->canChooseDoctor()) {
            return (int) $this->extractOptionId($request->get('doctor_id'));
        }

        $doctor = $this->currentDoctor();

        if (!$doctor) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Tu usuario no está vinculado a un doctor.',
            ]);
        }

        return $doctor->id;
    }

    private function canChooseDoctor(): bool
    {
        $user = Auth::user();

        return !$user || $user->hasAnyRole(['admin', 'Admin', 'Administrador', 'webAdmin']);
    }

    private function canStartAttentionForAppointment(DentAppointment $appointment): bool
    {
        if ($this->canChooseDoctor()) {
            return true;
        }

        $doctor = $this->currentDoctor();

        return $doctor && (int) $appointment->doctor_id === (int) $doctor->id;
    }

    private function currentDoctor(): ?HealDoctor
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return HealDoctor::with('person')
            ->where('user_id', $user->id)
            ->when($user->person_id, function ($query) use ($user) {
                $query->orWhere('person_id', $user->person_id);
            })
            ->first();
    }
}
