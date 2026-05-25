<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\ResetPassword;
use App\Models\User;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Health\Entities\HealAttention;
use Modules\Health\Entities\HealAttentionAudit;
use Modules\Health\Entities\HealAllergy;
use Modules\Health\Entities\HealAllergyPatient;
use Modules\Health\Entities\HealCie10;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Entities\HealHistory;
use Modules\Health\Entities\HealPatient;
use Modules\Health\Entities\HealPatientCharge;
use Modules\Health\Entities\HealProcedure;
use Modules\Dental\Entities\DentAppointment;
use Modules\Health\Support\PendingSignatureReminder;

class HealAttentionController extends Controller
{
    use ValidatesRequests;

    private const ATTENTION_SERVICE_TYPES = [
        'general',
        'medicina_general',
        'medicina_interna',
        'pediatria',
        'ginecologia',
        'cardiologia',
        'dermatologia',
        'traumatologia',
        'neurologia',
        'oftalmologia',
        'otorrinolaringologia',
        'gastroenterologia',
        'endocrinologia',
        'urologia',
        'psicologia',
        'nutricion',
        'dental',
        'odontologia_general',
        'ortodoncia',
        'endodoncia',
        'periodoncia',
        'rehabilitacion_oral',
        'cirugia_bucal',
        'odontopediatria',
        'implantologia',
    ];

    private const DENTAL_SERVICE_TYPES = [
        'dental',
        'odontologia_general',
        'ortodoncia',
        'endodoncia',
        'periodoncia',
        'rehabilitacion_oral',
        'cirugia_bucal',
        'odontopediatria',
        'implantologia',
    ];

    public function index(): Response
    {
        $attentions = HealAttention::with(['patient.person', 'doctor.person', 'history', 'cie10'])
            ->when(!$this->canChooseDoctor(), function ($query) {
                $query->where('doctor_id', $this->currentDoctorOrFail()->id);
            })
            ->when(request('search'), function ($query, $search) {
                $query->whereHas('patient.person', function ($person) use ($search) {
                    $person->where('full_name', 'like', '%' . $search . '%')
                        ->orWhere('number', 'like', '%' . $search . '%');
                });
            })
            ->latest('attention_at')
            ->paginate(request('per_page', 10))
            ->onEachSide(2)
            ->withQueryString();

        return Inertia::render('Health::Attentions/List', [
            'attentions' => $attentions,
            'filters' => request()->all('search'),
            'pendingSignatures' => PendingSignatureReminder::items(),
        ]);
    }

    public function create(Request $request): Response
    {
        if (!$this->canChooseDoctor()) {
            $this->currentDoctorOrFail();
        }

        $patientId = $request->integer('patient_id');
        $appointmentDefaults = null;

        if ($request->integer('appointment_id')) {
            $appointment = DentAppointment::findOrFail($request->integer('appointment_id'));
            $this->authorizeAppointmentDoctor($appointment);

            $patientId = (int) $appointment->patient_id;
            $appointmentDefaults = $this->appointmentDefaults($appointment);
        }

        return Inertia::render('Health::Attentions/Create', [
            'doctors' => $this->doctorOptions(),
            'allergyTypes' => $this->allergyOptions(),
            'currentDoctor' => $this->currentDoctorOption(),
            'canChooseDoctor' => $this->canChooseDoctor(),
            'patientSummary' => $patientId ? $this->patientSummary($patientId) : null,
            'attentionDefaults' => $appointmentDefaults,
            'pendingSignatures' => PendingSignatureReminder::items(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request, true);

        $attentionId = DB::transaction(function () use ($request, $data) {
            $patient = HealPatient::with('person')->findOrFail($data['patient_id']);
            $doctorId = $this->resolveDoctorId($request);

            $history = HealHistory::firstOrCreate(
                ['patient_id' => $patient->id],
                ['history_code' => $patient->person->number]
            );

            $attention = HealAttention::create([
                'attention_at' => $data['attention_at'],
                'service_type' => $this->normalizeServiceType($data['service_type']),
                'patient_id' => $patient->id,
                'doctor_id' => $doctorId,
                'history_id' => $history->id,
                'user_id' => Auth::id(),
                'appointment_id' => $this->resolveAppointmentId($request, $patient->id, $doctorId),
                'patient_story' => $request->get('patient_story'),
                'doctor_observation' => $request->get('doctor_observation'),
                'clinical_findings' => $request->get('clinical_findings'),
                'cie10_id' => $request->get('cie10_id'),
                'diagnosis' => $request->get('diagnosis'),
                'treatment_plan' => $request->get('treatment_plan'),
                'observations' => $request->get('observations'),
                'blood_pressure_systolic' => $request->get('blood_pressure_systolic'),
                'blood_pressure_diastolic' => $request->get('blood_pressure_diastolic'),
                'heart_rate' => $request->get('heart_rate'),
                'respiratory_rate' => $request->get('respiratory_rate'),
                'height' => $request->get('height'),
                'weight' => $request->get('weight'),
                'body_mass_index' => $request->get('body_mass_index'),
            ]);

            $this->syncExams($attention, $request->get('exams', []));
            $this->syncTreatments($attention, $request->get('treatments', []));
            if (count($request->get('procedure_charges', []))) {
                $this->syncProcedureCharges($attention, $request->get('procedure_charges', []));
            }

            if ($this->isDentalServiceType($data['service_type'])) {
                $attention->odontogram()->create([
                    'teeth' => $request->input('odontogram.teeth', []),
                    'notes' => $request->input('odontogram.notes'),
                ]);
            }

            $this->audit('created', $attention, null, $this->attentionSnapshot($attention), $request);

            return $attention->id;
        });

        return to_route('heal_attentions_edit', $attentionId);
    }

    public function edit(int $id): Response
    {
        $attention = HealAttention::with([
            'patient.person',
            'doctor.person',
            'exams',
            'treatments',
            'odontogram',
            'cie10',
            'charges' => fn ($query) => $query->where('status', 'pending')->with('procedure'),
        ])
            ->findOrFail($id);

        $this->authorizeAttentionDoctor($attention);

        return Inertia::render('Health::Attentions/Edit', [
            'attention' => $attention,
            'doctors' => $this->doctorOptions(),
            'allergyTypes' => $this->allergyOptions(),
            'currentDoctor' => $this->currentDoctorOption(),
            'canChooseDoctor' => $this->canChooseDoctor(),
            'patientSummary' => $this->patientSummary($attention->patient_id),
            'pendingSignatures' => PendingSignatureReminder::items(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $existingAttention = HealAttention::findOrFail($id);
        $this->authorizeAttentionDoctor($existingAttention);

        $data = $this->validatedData($request, true, $existingAttention);

        DB::transaction(function () use ($request, $data, $id) {
            $attention = HealAttention::findOrFail($id);

            if ($attention->signed_at) {
                throw ValidationException::withMessages([
                    'attention' => 'La atención ya fue firmada y no puede modificarse.',
                ]);
            }

            $before = $this->attentionSnapshot($attention->load(['exams', 'treatments', 'odontogram']));
            $patient = HealPatient::with('person')->findOrFail($data['patient_id']);
            $doctorId = $this->resolveDoctorId($request);

            $history = HealHistory::firstOrCreate(
                ['patient_id' => $patient->id],
                ['history_code' => $patient->person->number]
            );

            $attention->update([
                'attention_at' => $data['attention_at'],
                'service_type' => $this->normalizeServiceType($data['service_type']),
                'patient_id' => $patient->id,
                'doctor_id' => $doctorId,
                'history_id' => $history->id,
                'patient_story' => $request->get('patient_story'),
                'doctor_observation' => $request->get('doctor_observation'),
                'clinical_findings' => $request->get('clinical_findings'),
                'cie10_id' => $request->get('cie10_id'),
                'diagnosis' => $request->get('diagnosis'),
                'treatment_plan' => $request->get('treatment_plan'),
                'observations' => $request->get('observations'),
                'blood_pressure_systolic' => $request->get('blood_pressure_systolic'),
                'blood_pressure_diastolic' => $request->get('blood_pressure_diastolic'),
                'heart_rate' => $request->get('heart_rate'),
                'respiratory_rate' => $request->get('respiratory_rate'),
                'height' => $request->get('height'),
                'weight' => $request->get('weight'),
                'body_mass_index' => $request->get('body_mass_index'),
            ]);

            $this->syncExams($attention, $request->get('exams', []));
            $this->syncTreatments($attention, $request->get('treatments', []));
            if (count($request->get('procedure_charges', []))) {
                $this->syncProcedureCharges($attention, $request->get('procedure_charges', []));
            }

            if ($this->isDentalServiceType($data['service_type'])) {
                $attention->odontogram()->updateOrCreate(
                    ['attention_id' => $attention->id],
                    [
                        'teeth' => $request->input('odontogram.teeth', []),
                        'notes' => $request->input('odontogram.notes'),
                    ]
                );
            } else {
                $attention->odontogram()->delete();
            }

            $this->audit('updated', $attention, $before, $this->attentionSnapshot($attention->fresh(['exams', 'treatments', 'odontogram'])), $request);
        });

        if ($request->boolean('stay_on_attention')) {
            return to_route('heal_attentions_edit', $id);
        }

        return to_route('heal_attentions_list');
    }

    public function destroy(int $id)
    {
        $attention = HealAttention::findOrFail($id);

        $this->authorizeAttentionDoctor($attention);

        if ($attention->signed_at) {
            return response()->json([
                'success' => false,
                'message' => 'La atención ya fue firmada y no puede eliminarse.',
            ], 422);
        }

        $before = $this->attentionSnapshot($attention->load(['exams', 'treatments', 'odontogram']));
        $this->audit('deleted', $attention, $before, null, request());
        $attention->delete();

        return response()->json([
            'success' => true,
            'message' => 'Atención eliminada correctamente',
        ]);
    }

    public function sign(Request $request, int $id)
    {
        $request->validate([
            'pin' => ['required', 'digits:4'],
            'signed_at' => ['required', 'date'],
        ]);

        $attention = HealAttention::with('doctor')->findOrFail($id);

        $this->authorizeAttentionDoctor($attention);

        if ($attention->signed_at) {
            throw ValidationException::withMessages([
                'pin' => 'La atención ya fue firmada previamente.',
            ]);
        }

        if (!$this->pinMatches($attention->doctor, $request->get('pin'))) {
            throw ValidationException::withMessages([
                'pin' => 'El PIN del doctor no es correcto.',
            ]);
        }

        $signedAt = Carbon::parse($request->get('signed_at'))->seconds(0);
        $attentionStart = Carbon::parse($attention->attention_at)->seconds(0);

        if ($signedAt->lte($attentionStart)) {
            throw ValidationException::withMessages([
                'signed_at' => 'La hora de firma debe ser posterior al inicio de la atención.',
            ]);
        }

        if ($signedAt->gt(now()->addMinute())) {
            throw ValidationException::withMessages([
                'signed_at' => 'La hora de firma no puede estar en el futuro.',
            ]);
        }

        $attention->update([
            'signed_at' => $signedAt,
            'signed_by_doctor_id' => $attention->doctor_id,
            'signed_by_user_id' => Auth::id(),
            'signature_ip' => $request->ip(),
        ]);

        $this->completeAppointmentFromSignature($attention->fresh());

        $this->audit('signed', $attention, null, $this->attentionSnapshot($attention->fresh()), $request);

        return response()->json([
            'success' => true,
            'message' => 'Atención firmada correctamente.',
        ]);
    }

    public function sendDoctorAccessReset(Request $request, int $doctorId)
    {
        abort_unless($this->canChooseDoctor(), 403);

        $doctor = HealDoctor::with(['person', 'user'])->findOrFail($doctorId);
        $user = $doctor->user ?: User::where('person_id', $doctor->person_id)->first();

        if (!$user?->email) {
            throw ValidationException::withMessages([
                'doctor' => 'El doctor no tiene un usuario con correo para enviar restablecimiento.',
            ]);
        }

        $doctor->update(['signature_pin_hash' => null]);
        Mail::to($user->email)->send(new ResetPassword($user));

        HealAttentionAudit::create([
            'actor_user_id' => Auth::id(),
            'actor_doctor_id' => $this->currentDoctor()?->id,
            'affected_doctor_id' => $doctor->id,
            'event' => 'doctor_access_reset_sent',
            'after_data' => [
                'doctor_id' => $doctor->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'pin_reset_to_default' => true,
            ],
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Se envió el correo de restablecimiento y el PIN volvió temporalmente a 1234.',
        ]);
    }

    public function searchCie10(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $mode = $request->get('mode', 'all');

        $items = HealCie10::query()
            ->when($search, function ($query) use ($search, $mode) {
                $query->where(function ($subQuery) use ($search, $mode) {
                    if ($mode === 'code') {
                        $subQuery->where('cie10x', 'like', '%' . $search . '%');
                    } elseif ($mode === 'description') {
                        $subQuery->where('cie10', 'like', '%' . $search . '%')
                            ->orWhere('description', 'like', '%' . $search . '%');
                    } else {
                        $subQuery->where('cie10x', 'like', '%' . $search . '%')
                            ->orWhere('cie10', 'like', '%' . $search . '%')
                            ->orWhere('description', 'like', '%' . $search . '%');
                    }
                });
            })
            ->orderBy('cie10x')
            ->limit(20)
            ->get(['id', 'cie10x', 'cie10', 'description']);

        return response()->json($items);
    }

    public function updateDoctorPin(Request $request)
    {
        $request->validate([
            'current_pin' => ['required', 'digits:4'],
            'new_pin' => ['required', 'digits:4', 'confirmed'],
        ]);

        $doctor = $this->currentDoctor();

        if (!$doctor) {
            throw ValidationException::withMessages([
                'current_pin' => 'Tu usuario no está vinculado a un doctor.',
            ]);
        }

        if (!$this->pinMatches($doctor, $request->get('current_pin'))) {
            throw ValidationException::withMessages([
                'current_pin' => 'El PIN actual no es correcto.',
            ]);
        }

        $doctor->update([
            'signature_pin_hash' => Hash::make($request->get('new_pin')),
        ]);

        HealAttentionAudit::create([
            'actor_user_id' => Auth::id(),
            'actor_doctor_id' => $doctor->id,
            'affected_doctor_id' => $doctor->id,
            'event' => 'doctor_pin_changed',
            'after_data' => ['doctor_id' => $doctor->id],
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PIN actualizado correctamente.',
        ]);
    }

    public function patientSummary(int $patientId)
    {
        $patient = HealPatient::with('person')->findOrFail($patientId);

        $lastAttention = HealAttention::where('patient_id', $patientId)
            ->latest('attention_at')
            ->first();

        $diagnoses = HealAttention::where('patient_id', $patientId)
            ->whereNotNull('diagnosis')
            ->where('diagnosis', '<>', '')
            ->latest('attention_at')
            ->limit(3)
            ->get(['id', 'attention_at', 'diagnosis']);

        $treatments = HealAttention::where('patient_id', $patientId)
            ->whereNotNull('treatment_plan')
            ->where('treatment_plan', '<>', '')
            ->latest('attention_at')
            ->limit(3)
            ->get(['id', 'attention_at', 'treatment_plan']);

        $allergies = HealAllergyPatient::query()
            ->join('heal_allergies', 'heal_allergy_patients.allergy_id', '=', 'heal_allergies.id')
            ->where('heal_allergy_patients.patient_id', $patientId)
            ->latest('heal_allergy_patients.created_at')
            ->limit(5)
            ->get([
                'heal_allergy_patients.id',
                'heal_allergy_patients.description',
                'heal_allergy_patients.created_at',
                'heal_allergies.title',
            ]);

        return [
            'patient' => [
                'id' => $patient->id,
                'person' => $patient->person,
                'birthdate' => $patient->person?->birthdate,
                'age' => $this->calculateAge($patient->person?->birthdate),
            ],
            'last_attention' => $lastAttention ? [
                'id' => $lastAttention->id,
                'attention_at' => $lastAttention->attention_at,
                'service_type' => $lastAttention->service_type,
            ] : null,
            'diagnoses' => $diagnoses,
            'allergies' => $allergies,
            'treatments' => $treatments,
            'latest_odontogram' => $this->latestOdontogram($patientId),
            'active_endodontic_treatments' => $this->activeEndodonticTreatments($patientId),
            'billing' => $this->billingSummary($patientId),
        ];
    }

    private function validatedData(Request $request, bool $validateAttentionWindow = false, ?HealAttention $existingAttention = null): array
    {
        if ($request->filled('attention_date') && $request->filled('attention_time')) {
            $request->merge([
                'attention_at' => "{$request->get('attention_date')} {$request->get('attention_time')}",
            ]);
        }

        $request->merge([
            'doctor_id_value' => $this->extractOptionId($request->get('doctor_id')),
        ]);

        $data = $request->validate([
            'attention_at' => ['required', 'date'],
            'attention_date' => ['required', 'date_format:Y-m-d'],
            'attention_time' => ['required', 'date_format:H:i'],
            'service_type' => ['required', 'in:' . implode(',', self::ATTENTION_SERVICE_TYPES)],
            'patient_id' => ['required', 'exists:heal_patients,id'],
            'doctor_id_value' => [$this->canChooseDoctor() ? 'required' : 'nullable', 'exists:heal_doctors,id'],
            'patient_story' => ['nullable', 'string'],
            'doctor_observation' => ['nullable', 'string'],
            'clinical_findings' => ['nullable', 'string'],
            'cie10_id' => ['nullable', 'exists:heal_cie10s,id'],
            'diagnosis' => ['nullable', 'string'],
            'treatment_plan' => ['nullable', 'string'],
            'observations' => ['nullable', 'string'],
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:1', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:1', 'max:200'],
            'heart_rate' => ['nullable', 'integer', 'min:1', 'max:300'],
            'respiratory_rate' => ['nullable', 'integer', 'min:1', 'max:120'],
            'height' => ['nullable', 'numeric', 'min:1', 'max:300'],
            'weight' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'body_mass_index' => ['nullable', 'numeric', 'min:1', 'max:200'],
            'appointment_id' => ['nullable', 'exists:dent_appointments,id'],
            'exams' => ['nullable', 'array'],
            'treatments' => ['nullable', 'array'],
            'treatments.*.endodontic_data.session_number' => ['nullable', 'integer', 'min:1'],
            'treatments.*.endodontic_data.status' => ['nullable', 'in:proceso,finalizado'],
            'treatments.*.endodontic_data.is_final_session' => ['nullable', 'boolean'],
            'odontogram' => ['nullable', 'array'],
            'procedure_charges' => ['nullable', 'array'],
            'procedure_charges.*.procedure_id' => ['required_with:procedure_charges', 'exists:heal_procedures,id'],
            'procedure_charges.*.price' => ['required_with:procedure_charges', 'numeric', 'min:0'],
            'procedure_charges.*.quantity' => ['required_with:procedure_charges', 'numeric', 'min:0.01'],
            'procedure_charges.*.notes' => ['nullable', 'string'],
        ]);

        if ($validateAttentionWindow) {
            $this->validateAttentionWindow($data['attention_at'], $existingAttention);
        }

        return $data;
    }

    private function validateAttentionWindow(string $attentionAt, ?HealAttention $existingAttention = null): void
    {
        $timezone = config('app.timezone');
        $selected = Carbon::parse($attentionAt, $timezone)->seconds(0);

        if ($existingAttention) {
            $current = Carbon::parse($existingAttention->attention_at, $timezone)->seconds(0);

            if ($selected->equalTo($current)) {
                return;
            }
        }

        $now = Carbon::now($timezone);
        $minimum = $now->copy()->subHours(24)->seconds(0);

        if ($selected->lt($minimum) || $selected->gt($now)) {
            throw ValidationException::withMessages([
                'attention_at' => 'El momento de atención debe ser la hora actual o hasta 24 horas hacia atrás.',
            ]);
        }
    }

    private function extractOptionId($value): ?int
    {
        if (is_array($value)) {
            return $value['code'] ?? $value['id'] ?? null;
        }

        return $value ? (int) $value : null;
    }

    private function doctorOptions()
    {
        $query = HealDoctor::with('person');

        if (!$this->canChooseDoctor()) {
            $doctor = $this->currentDoctorOrFail();
            $query->where('id', $doctor?->id);
        }

        return $query->get()->map(function (HealDoctor $doctor) {
            return [
                'code' => $doctor->id,
                'name' => $doctor->person?->full_name,
                'email' => $doctor->person?->email,
                'telephone' => $doctor->person?->telephone,
                'colegiatura' => $doctor->colegiatura,
                'profession' => $doctor->profession,
                'specialty' => $doctor->specialty,
                'service_type' => $doctor->attention_service_type ?: 'general',
            ];
        });
    }

    private function allergyOptions()
    {
        return HealAllergy::orderBy('title')->get(['id', 'title']);
    }

    private function canChooseDoctor(): bool
    {
        $user = Auth::user();

        return !$user || $user->hasAnyRole(['admin', 'Admin', 'Administrador', 'webAdmin']);
    }

    private function resolveDoctorId(Request $request): ?int
    {
        if ($this->canChooseDoctor()) {
            return $this->extractOptionId($request->get('doctor_id'));
        }

        $doctor = $this->currentDoctor();

        if (!$doctor) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Tu usuario no está vinculado a un doctor.',
            ]);
        }

        return $doctor->id;
    }

    private function authorizeAttentionDoctor(HealAttention $attention): void
    {
        if ($this->canChooseDoctor()) {
            return;
        }

        if ($attention->doctor_id !== $this->currentDoctorOrFail()->id) {
            abort(403, 'Solo puedes trabajar con atenciones creadas para tu propio doctor.');
        }
    }

    private function authorizeAppointmentDoctor(DentAppointment $appointment): void
    {
        if ($this->canChooseDoctor()) {
            return;
        }

        if ((int) $appointment->doctor_id !== $this->currentDoctorOrFail()->id) {
            abort(403, 'Solo puedes iniciar atenciones desde citas de tu propia agenda.');
        }
    }

    private function resolveAppointmentId(Request $request, int $patientId, int $doctorId): ?int
    {
        if (!$request->filled('appointment_id')) {
            return null;
        }

        $appointment = DentAppointment::findOrFail((int) $request->get('appointment_id'));
        $this->authorizeAppointmentDoctor($appointment);

        $appointmentEnd = $appointment->time_end_appointmen
            ? Carbon::parse(($appointment->date_end_appointmen ?: $appointment->date_appointmen) . ' ' . $appointment->time_end_appointmen)->seconds(0)
            : Carbon::parse($appointment->date_appointmen . ' ' . $appointment->time_appointmen)->seconds(0)->addMinutes(15);

        if ($appointment->status === '3' || ($appointment->status === '1' && $appointmentEnd->lte(now()))) {
            if ($appointment->status === '1') {
                $appointment->update([
                    'status' => '3',
                    'no_show_at' => now(),
                    'details' => mb_substr(trim((string) $appointment->details . "\nCita no concretada automaticamente el " . now()->format('Y-m-d H:i:s')), 0, 255),
                    'updated_user_id' => Auth::id(),
                ]);
            }

            throw ValidationException::withMessages([
                'appointment_id' => 'La cita ya paso su horario y quedo marcada como no concretada.',
            ]);
        }

        if ((int) $appointment->patient_id !== $patientId || (int) $appointment->doctor_id !== $doctorId) {
            throw ValidationException::withMessages([
                'appointment_id' => 'La cita no corresponde al paciente y doctor de la atención. Vuelve a abrir la atención desde la agenda.',
            ]);
        }

        return $appointment->id;
    }

    private function completeAppointmentFromSignature(HealAttention $attention): void
    {
        if (!$attention->appointment_id || !$attention->signed_at) {
            return;
        }

        $appointment = DentAppointment::find($attention->appointment_id);

        if (!$appointment) {
            return;
        }

        $start = Carbon::parse($appointment->date_appointmen . ' ' . $appointment->time_appointmen)->seconds(0);
        $currentEnd = $appointment->time_end_appointmen
            ? Carbon::parse(($appointment->date_end_appointmen ?: $appointment->date_appointmen) . ' ' . $appointment->time_end_appointmen)->seconds(0)
            : $start->copy()->addMinutes(15);
        $signedAt = Carbon::parse($attention->signed_at)->seconds(0);

        $newEnd = $signedAt->between($start, $currentEnd, true) ? $signedAt : $currentEnd;

        $appointment->update([
            'status' => '2',
            'date_end_appointmen' => $newEnd->toDateString(),
            'time_end_appointmen' => $newEnd->format('H:i:s'),
            'no_show_at' => null,
            'updated_user_id' => Auth::id(),
        ]);
    }

    private function appointmentDefaults(DentAppointment $appointment): array
    {
        $doctor = HealDoctor::with('person')->find($appointment->doctor_id);

        return [
            'appointment_id' => $appointment->id,
            'patient_id' => (int) $appointment->patient_id,
            'doctor_id' => $doctor ? [
                'code' => $doctor->id,
                'name' => $doctor->person?->full_name,
                'email' => $doctor->person?->email,
                'telephone' => $doctor->person?->telephone,
                'colegiatura' => $doctor->colegiatura,
                'profession' => $doctor->profession,
                'specialty' => $doctor->specialty,
                'service_type' => $doctor->attention_service_type ?: 'general',
            ] : null,
            'service_type' => $doctor?->attention_service_type ?: 'general',
            'patient_story' => $appointment->description,
            'doctor_observation' => $appointment->details,
        ];
    }

    private function currentDoctorOrFail(): HealDoctor
    {
        $doctor = $this->currentDoctor();

        if (!$doctor) {
            abort(403, 'Tu usuario no está vinculado a un doctor.');
        }

        return $doctor;
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

    private function currentDoctorOption(): ?array
    {
        $doctor = $this->currentDoctor();

        if (!$doctor) {
            return null;
        }

        return [
            'code' => $doctor->id,
            'name' => $doctor->person?->full_name,
            'colegiatura' => $doctor->colegiatura,
            'profession' => $doctor->profession,
            'specialty' => $doctor->specialty,
            'service_type' => $doctor->attention_service_type ?: 'general',
            'has_custom_pin' => (bool) $doctor->signature_pin_hash,
        ];
    }

    private function isDentalServiceType(?string $serviceType): bool
    {
        return in_array($serviceType, self::DENTAL_SERVICE_TYPES, true);
    }

    private function normalizeServiceType(?string $serviceType): string
    {
        return $serviceType === 'dental' ? 'odontologia_general' : ($serviceType ?: 'general');
    }

    private function pinMatches(?HealDoctor $doctor, string $pin): bool
    {
        if (!$doctor) {
            return false;
        }

        if (!$doctor->signature_pin_hash) {
            return $pin === '1234';
        }

        return Hash::check($pin, $doctor->signature_pin_hash);
    }

    private function latestOdontogram(int $patientId): ?array
    {
        $attention = HealAttention::with('odontogram')
            ->where('patient_id', $patientId)
            ->whereIn('service_type', self::DENTAL_SERVICE_TYPES)
            ->whereHas('odontogram')
            ->latest('attention_at')
            ->first();

        if (!$attention?->odontogram) {
            return null;
        }

        return [
            'attention_id' => $attention->id,
            'attention_at' => $attention->attention_at,
            'teeth' => $attention->odontogram->teeth ?: [],
            'notes' => $attention->odontogram->notes,
        ];
    }

    private function syncExams(HealAttention $attention, array $exams): void
    {
        $attention->exams()->delete();

        foreach ($exams as $exam) {
            if (empty($exam['exam_type']) && empty($exam['description']) && empty($exam['result'])) {
                continue;
            }

            $attention->exams()->create([
                'exam_type' => $exam['exam_type'] ?? 'otros',
                'name' => $exam['name'] ?? null,
                'description' => $exam['description'] ?? null,
                'result' => $exam['result'] ?? null,
            ]);
        }
    }

    private function syncTreatments(HealAttention $attention, array $treatments): void
    {
        $attention->treatments()->delete();

        foreach ($treatments as $treatment) {
            if (
                empty($treatment['treatment_type'])
                && empty($treatment['name'])
                && empty($treatment['description'])
                && empty($treatment['indications'])
                && empty($treatment['endodontic_data'])
            ) {
                continue;
            }

            $treatmentType = $treatment['treatment_type'] ?? 'otros';

            $attention->treatments()->create([
                'treatment_type' => $treatmentType,
                'name' => $treatment['name'] ?? null,
                'description' => $treatment['description'] ?? null,
                'indications' => $treatment['indications'] ?? null,
                'endodontic_data' => $treatmentType === 'endodontico'
                    ? $this->normalizeEndodonticData($treatment['endodontic_data'] ?? [], $attention)
                    : null,
            ]);
        }
    }

    private function syncProcedureCharges(HealAttention $attention, array $charges): void
    {
        if ($attention->exists) {
            HealPatientCharge::where('attention_id', $attention->id)
                ->where('status', 'pending')
                ->delete();
        }

        foreach ($charges as $charge) {
            $procedure = HealProcedure::where('is_active', true)->find($charge['procedure_id'] ?? null);

            if (!$procedure) {
                continue;
            }

            $price = (float) ($charge['price'] ?? $procedure->default_price);
            $quantity = (float) ($charge['quantity'] ?? 1);

            HealPatientCharge::create([
                'patient_id' => $attention->patient_id,
                'attention_id' => $attention->id,
                'doctor_id' => $attention->doctor_id,
                'procedure_id' => $procedure->id,
                'name_snapshot' => $procedure->name,
                'description_snapshot' => $procedure->description,
                'default_price' => $procedure->default_price,
                'price' => $price,
                'quantity' => $quantity,
                'total' => round($price * $quantity, 2),
                'currency_type_id' => $procedure->currency_type_id,
                'status' => 'pending',
                'charged_at' => $attention->attention_at,
                'notes' => $charge['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);
        }
    }

    private function billingSummary(int $patientId): array
    {
        return [
            'procedures' => HealProcedure::where('is_active', true)
                ->orderByDesc('is_consultation')
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
            'consultation_procedure' => HealProcedure::where('is_active', true)
                ->where('is_consultation', true)
                ->first(),
            'should_charge_consultation' => $this->shouldChargeConsultation($patientId),
        ];
    }

    private function shouldChargeConsultation(int $patientId): bool
    {
        $lastAttention = HealAttention::where('patient_id', $patientId)
            ->latest('attention_at')
            ->first();

        if (!$lastAttention) {
            return true;
        }

        return Carbon::parse($lastAttention->attention_at)->lt(now()->subMonths(2));
    }

    private function normalizeEndodonticData(array $data, HealAttention $attention): array
    {
        $tooth = $data['tooth'] ?? null;
        $sessionNumber = $this->positiveIntegerOrNull($data['session_number'] ?? null)
            ?: $this->nextEndodonticSessionNumber($attention, $tooth);
        $isFinalSession = filter_var($data['is_final_session'] ?? false, FILTER_VALIDATE_BOOLEAN)
            || ($data['status'] ?? null) === 'finalizado';
        $status = $isFinalSession ? 'finalizado' : 'proceso';

        $canals = collect($data['canals'] ?? [])
            ->map(function ($canal) {
                return [
                    'name' => $canal['name'] ?? null,
                    'length' => $canal['length'] ?? null,
                    'supported_on' => $canal['supported_on'] ?? null,
                    'initial_file' => $canal['initial_file'] ?? null,
                    'working_file' => $canal['working_file'] ?? null,
                    'master_file' => $canal['master_file'] ?? null,
                ];
            })
            ->values()
            ->all();

        if (count($canals) === 0) {
            $canals[] = [
                'name' => null,
                'length' => null,
                'supported_on' => null,
                'initial_file' => null,
                'working_file' => null,
                'master_file' => null,
            ];
        }

        return [
            'tooth' => $tooth,
            'diagnosis' => $data['diagnosis'] ?? null,
            'session_number' => $sessionNumber,
            'status' => $status,
            'is_final_session' => $isFinalSession,
            'ldr' => $data['ldr'] ?? null,
            'lt' => $data['lt'] ?? null,
            'canals' => $canals,
        ];
    }

    private function activeEndodonticTreatments(int $patientId): array
    {
        $active = [];

        $attentions = HealAttention::with('treatments')
            ->where('patient_id', $patientId)
            ->orderBy('attention_at')
            ->orderBy('id')
            ->get(['id', 'patient_id', 'attention_at']);

        foreach ($attentions as $attention) {
            foreach ($attention->treatments->where('treatment_type', 'endodontico') as $treatment) {
                $data = $treatment->endodontic_data ?: [];
                $key = $this->endodonticTreatmentKey($data);
                $status = $data['status'] ?? 'proceso';

                if ($status === 'finalizado' || !empty($data['is_final_session'])) {
                    unset($active[$key]);
                    continue;
                }

                $sessionNumber = $this->positiveIntegerOrNull($data['session_number'] ?? null) ?: 1;
                $active[$key] = [
                    'id' => $treatment->id,
                    'attention_id' => $attention->id,
                    'attention_at' => $attention->attention_at,
                    'name' => $treatment->name,
                    'description' => $treatment->description,
                    'indications' => $treatment->indications,
                    'endodontic_data' => [
                        ...$data,
                        'session_number' => $sessionNumber,
                        'status' => 'proceso',
                        'is_final_session' => false,
                    ],
                    'next_session_number' => $sessionNumber + 1,
                ];
            }
        }

        return array_values($active);
    }

    private function nextEndodonticSessionNumber(HealAttention $attention, ?string $tooth): int
    {
        $activeTreatments = $this->activeEndodonticTreatments((int) $attention->patient_id);
        $toothKey = $this->normalizeEndodonticTooth($tooth);

        $matchingTreatment = collect($activeTreatments)
            ->filter(function ($treatment) use ($toothKey) {
                if ($toothKey === '') {
                    return true;
                }

                return $this->normalizeEndodonticTooth($treatment['endodontic_data']['tooth'] ?? null) === $toothKey;
            })
            ->sortByDesc('next_session_number')
            ->first();

        return $matchingTreatment['next_session_number'] ?? 1;
    }

    private function endodonticTreatmentKey(array $data): string
    {
        $tooth = $this->normalizeEndodonticTooth($data['tooth'] ?? null);

        return $tooth !== '' ? 'tooth:' . $tooth : 'tooth:sin-pieza';
    }

    private function normalizeEndodonticTooth(?string $tooth): string
    {
        return mb_strtolower(trim((string) $tooth));
    }

    private function positiveIntegerOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $number === false ? null : $number;
    }

    private function attentionSnapshot(HealAttention $attention): array
    {
        return $attention->loadMissing(['exams', 'treatments', 'odontogram', 'cie10'])->toArray();
    }

    private function audit(string $event, HealAttention $attention, ?array $before, ?array $after, Request $request): void
    {
        HealAttentionAudit::create([
            'attention_id' => $attention->id,
            'actor_user_id' => Auth::id(),
            'actor_doctor_id' => $this->currentDoctor()?->id,
            'affected_doctor_id' => $attention->doctor_id,
            'event' => $event,
            'before_data' => $before,
            'after_data' => $after,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);
    }

    private function calculateAge(?string $birthdate): ?string
    {
        if (!$birthdate) {
            return null;
        }

        $birthDate = Carbon::parse($birthdate)->startOfDay();
        $today = now()->startOfDay();

        if ($birthDate->greaterThan($today)) {
            return null;
        }

        $years = (int) $birthDate->diffInYears($today);
        $months = (int) $birthDate->copy()->addYears($years)->diffInMonths($today);

        return "{$years}a {$months}m";
    }
}
