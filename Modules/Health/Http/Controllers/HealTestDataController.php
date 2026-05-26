<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Dental\Entities\DentAppointment;
use Modules\Health\Entities\HealAttention;
use Modules\Health\Entities\HealAttentionExam;
use Modules\Health\Entities\HealAttentionTreatment;
use Modules\Health\Entities\HealCie10;
use Modules\Health\Entities\HealDoctor;
use Modules\Health\Entities\HealHistory;
use Modules\Health\Entities\HealOdontogram;
use Modules\Health\Entities\HealPatient;
use Modules\Health\Entities\HealTestRecord;

class HealTestDataController extends Controller
{
    public function status()
    {
        return response()->json($this->summary());
    }

    public function store(Request $request)
    {
        $this->validatePassword($request);

        $summary = DB::transaction(function () {
            $batchCode = 'HEALTH-TEST-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
            $userId = Auth::id();
            $now = Carbon::now(config('app.timezone'))->seconds(0);
            $cie10Ids = HealCie10::query()->limit(8)->pluck('id')->values();

            $doctors = collect($this->doctorSeeds())->map(function (array $seed, int $index) use ($batchCode, $userId) {
                $person = Person::create([
                    'document_type_id' => '1',
                    'short_name' => $seed['names'],
                    'full_name' => "{$seed['father_lastname']} {$seed['mother_lastname']} {$seed['names']}",
                    'description' => 'Doctor de prueba Health',
                    'number' => $this->uniqueNumber('79', $index),
                    'telephone' => '99910' . str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'email' => "health.test.doctor.{$batchCode}.{$index}@example.test",
                    'address' => 'Av. Test Salud ' . ($index + 1),
                    'is_provider' => false,
                    'is_client' => false,
                    'ubigeo' => '021801',
                    'birthdate' => now()->subYears(35 + $index)->toDateString(),
                    'names' => $seed['names'],
                    'father_lastname' => $seed['father_lastname'],
                    'mother_lastname' => $seed['mother_lastname'],
                    'gender' => $seed['gender'],
                ]);
                $this->track($batchCode, 'people', $person->id, $userId);

                $doctor = HealDoctor::create([
                    'person_id' => $person->id,
                    'doctor_code' => $person->number,
                    'colegiatura' => 'CMP-TEST-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'profession' => $seed['profession'],
                    'specialty' => $seed['specialty'],
                    'attention_service_type' => $seed['service_type'],
                ]);
                $this->track($batchCode, 'heal_doctors', $doctor->id, $userId);

                return $doctor;
            });

            $patients = collect($this->patientSeeds())->map(function (array $seed, int $index) use ($batchCode, $userId) {
                $person = Person::create([
                    'document_type_id' => '1',
                    'short_name' => $seed['names'],
                    'full_name' => "{$seed['father_lastname']} {$seed['mother_lastname']} {$seed['names']}",
                    'description' => 'Paciente de prueba Health',
                    'number' => $this->uniqueNumber('78', $index),
                    'telephone' => '98820' . str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'email' => "health.test.patient.{$batchCode}.{$index}@example.test",
                    'address' => 'Jr. Paciente Test ' . ($index + 1),
                    'is_provider' => false,
                    'is_client' => true,
                    'ubigeo' => '021801',
                    'birthdate' => now()->subYears($seed['age'])->subMonths($index)->toDateString(),
                    'names' => $seed['names'],
                    'father_lastname' => $seed['father_lastname'],
                    'mother_lastname' => $seed['mother_lastname'],
                    'gender' => $seed['gender'],
                ]);
                $this->track($batchCode, 'people', $person->id, $userId);

                $patient = HealPatient::create([
                    'person_id' => $person->id,
                    'patient_code' => $person->number,
                ]);
                $this->track($batchCode, 'heal_patients', $patient->id, $userId);

                $history = HealHistory::create([
                    'patient_id' => $patient->id,
                    'history_code' => $person->number,
                ]);
                $this->track($batchCode, 'heal_histories', $history->id, $userId);

                $patient->setRelation('person', $person);
                $patient->setRelation('history', $history);
                $patient->test_dental_only = (bool) ($seed['dental_only'] ?? false);

                return $patient;
            });

            $dentalDoctors = $doctors->filter(fn (HealDoctor $doctor) => $this->isDental($doctor->attention_service_type))->values();
            $generalDoctors = $doctors->reject(fn (HealDoctor $doctor) => $this->isDental($doctor->attention_service_type))->values();
            $today = $now->copy()->startOfDay();
            $attentionSlotsByDoctor = [];
            $appointmentSlotsByDoctor = [];

            $patients->each(function (HealPatient $patient, int $index) use ($doctors, $dentalDoctors, $generalDoctors, $today, $cie10Ids, $batchCode, $userId, &$attentionSlotsByDoctor, &$appointmentSlotsByDoctor) {
                $patient->loadMissing('person');
                $history = HealHistory::where('patient_id', $patient->id)->first();

                for ($attentionIndex = 0; $attentionIndex < 8; $attentionIndex++) {
                    $sequence = ($index * 8) + $attentionIndex;
                    $doctor = $this->doctorForPatient($patient, $sequence, $doctors, $dentalDoctors, $generalDoctors);
                    $doctor->loadMissing('person');
                    $doctorSlot = $attentionSlotsByDoctor[$doctor->id] ?? 0;
                    $attentionSlotsByDoctor[$doctor->id] = $doctorSlot + 1;
                    $attentionAt = $this->testSlotAt($today, $doctorSlot, 'past');

                    $attention = HealAttention::create([
                        'attention_at' => $attentionAt,
                        'service_type' => $doctor->attention_service_type ?: 'general',
                        'patient_id' => $patient->id,
                        'doctor_id' => $doctor->id,
                        'history_id' => $history->id,
                        'user_id' => $userId,
                        'appointment_id' => null,
                        'patient_story' => $this->patientStory($sequence),
                        'doctor_observation' => 'Evaluacion clinica de prueba con datos variados.',
                        'clinical_findings' => $this->clinicalFindings($doctor->attention_service_type),
                        'cie10_id' => $cie10Ids->get($sequence % max($cie10Ids->count(), 1)),
                        'diagnosis' => $this->diagnosis($doctor->attention_service_type),
                        'treatment_plan' => $this->treatmentPlan($doctor->attention_service_type),
                        'observations' => 'Dato de prueba generado automaticamente.',
                        'non_pharmacological_indications' => 'Hidratacion, descanso relativo y control segun evolucion.',
                        'blood_pressure_systolic' => 110 + ($sequence % 12),
                        'blood_pressure_diastolic' => 70 + ($sequence % 5),
                        'heart_rate' => 68 + ($sequence % 14),
                        'respiratory_rate' => 16 + ($sequence % 3),
                        'height' => 1.55 + ($sequence % 5) * 0.05,
                        'weight' => 58 + ($sequence % 11) * 3,
                        'body_mass_index' => 22 + ($sequence % 6),
                    ]);
                    $this->track($batchCode, 'heal_attentions', $attention->id, $userId);

                    foreach ($this->examSeeds($doctor->attention_service_type) as $exam) {
                        $createdExam = HealAttentionExam::create(['attention_id' => $attention->id] + $exam);
                        $this->track($batchCode, 'heal_attention_exams', $createdExam->id, $userId);
                    }

                    foreach ($this->treatmentSeeds($doctor->attention_service_type, $sequence) as $treatment) {
                        $createdTreatment = HealAttentionTreatment::create(['attention_id' => $attention->id] + $treatment);
                        $this->track($batchCode, 'heal_attention_treatments', $createdTreatment->id, $userId);
                    }

                    if ($this->isDental($doctor->attention_service_type)) {
                        $odontogram = HealOdontogram::create([
                            'attention_id' => $attention->id,
                            'teeth' => $this->odontogramTeeth($sequence),
                            'notes' => 'Odontograma de prueba con caries, obturaciones y tratamiento endodontico sugerido.',
                        ]);
                        $this->track($batchCode, 'heal_odontograms', $odontogram->id, $userId);
                    }
                }

                for ($appointmentIndex = 0; $appointmentIndex < 8; $appointmentIndex++) {
                    $sequence = ($index * 8) + $appointmentIndex;
                    $doctor = $this->doctorForPatient($patient, $sequence, $doctors, $dentalDoctors, $generalDoctors);
                    $doctor->loadMissing('person');
                    $doctorSlot = $appointmentSlotsByDoctor[$doctor->id] ?? 0;
                    $appointmentSlotsByDoctor[$doctor->id] = $doctorSlot + 1;
                    $start = $this->testSlotAt($today, $doctorSlot, 'future');
                    $end = $start->copy()->addMinutes(45);

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
                        'description' => $this->appointmentTitle($doctor->attention_service_type),
                        'details' => 'Cita futura generada como dato de prueba del modulo Health.',
                        'message' => 'Registro de prueba. Puede eliminarse desde Configuracion de Salud.',
                        'status' => '1',
                        'important' => $sequence % 3 === 0,
                        'created_user_id' => $userId,
                        'updated_user_id' => $userId,
                    ]);
                    $this->track($batchCode, 'dent_appointments', $appointment->id, $userId);
                }
            });

            return $this->summary();
        });

        return response()->json([
            'success' => true,
            'message' => 'Datos de prueba generados correctamente.',
            'summary' => $summary,
        ]);
    }

    public function destroy(Request $request)
    {
        $this->validatePassword($request);

        $deleted = DB::transaction(function () {
            $records = HealTestRecord::query()->get()->groupBy('table_name');
            $deleted = 0;

            foreach ($this->deleteOrder() as $table) {
                $ids = $records->get($table, collect())->pluck('record_id')->unique()->values();

                if ($ids->isEmpty()) {
                    continue;
                }

                foreach ($ids->chunk(500) as $chunk) {
                    $deleted += DB::table($table)->whereIn('id', $chunk->all())->delete();
                }
            }

            HealTestRecord::query()->delete();

            return $deleted;
        });

        return response()->json([
            'success' => true,
            'message' => "Datos de prueba eliminados correctamente. Registros eliminados: {$deleted}.",
            'summary' => $this->summary(),
        ]);
    }

    private function validatePassword(Request $request): void
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = Auth::user();

        if (!$user || !Hash::check($request->get('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'La contrasena no es correcta.',
            ]);
        }
    }

    private function track(string $batchCode, string $tableName, int $recordId, ?int $userId): void
    {
        HealTestRecord::create([
            'batch_code' => $batchCode,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'created_by' => $userId,
        ]);
    }

    private function summary(): array
    {
        if (!Schema::hasTable('heal_test_records')) {
            return $this->emptySummary();
        }

        return [
            'records' => HealTestRecord::count(),
            'batches' => HealTestRecord::distinct('batch_code')->count('batch_code'),
            'doctors' => HealTestRecord::where('table_name', 'heal_doctors')->count(),
            'patients' => HealTestRecord::where('table_name', 'heal_patients')->count(),
            'appointments' => HealTestRecord::where('table_name', 'dent_appointments')->count(),
            'attentions' => HealTestRecord::where('table_name', 'heal_attentions')->count(),
            'odontograms' => HealTestRecord::where('table_name', 'heal_odontograms')->count(),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'records' => 0,
            'batches' => 0,
            'doctors' => 0,
            'patients' => 0,
            'appointments' => 0,
            'attentions' => 0,
            'odontograms' => 0,
        ];
    }

    private function deleteOrder(): array
    {
        return [
            'heal_odontograms',
            'heal_attention_exams',
            'heal_attention_treatments',
            'heal_attentions',
            'dent_appointments',
            'heal_histories',
            'heal_doctors',
            'heal_patients',
            'people',
        ];
    }

    private function doctorSeeds(): array
    {
        return [
            ['names' => 'Ana Lucia', 'father_lastname' => 'Salazar', 'mother_lastname' => 'Vega', 'gender' => 'F', 'profession' => 'Medicina Humana', 'specialty' => 'Medicina General', 'service_type' => 'medicina_general'],
            ['names' => 'Marco Antonio', 'father_lastname' => 'Rivera', 'mother_lastname' => 'Leon', 'gender' => 'M', 'profession' => 'Odontologia', 'specialty' => 'Odontologia General', 'service_type' => 'odontologia_general'],
            ['names' => 'Carla Beatriz', 'father_lastname' => 'Mendoza', 'mother_lastname' => 'Rios', 'gender' => 'F', 'profession' => 'Medicina Humana', 'specialty' => 'Pediatria', 'service_type' => 'pediatria'],
            ['names' => 'Jorge Luis', 'father_lastname' => 'Caceres', 'mother_lastname' => 'Paredes', 'gender' => 'M', 'profession' => 'Medicina Humana', 'specialty' => 'Cardiologia', 'service_type' => 'cardiologia'],
            ['names' => 'Rosa Maria', 'father_lastname' => 'Quispe', 'mother_lastname' => 'Torres', 'gender' => 'F', 'profession' => 'Odontologia', 'specialty' => 'Endodoncia', 'service_type' => 'endodoncia'],
            ['names' => 'Luis Alberto', 'father_lastname' => 'Herrera', 'mother_lastname' => 'Molina', 'gender' => 'M', 'profession' => 'Medicina Humana', 'specialty' => 'Dermatologia', 'service_type' => 'dermatologia'],
            ['names' => 'Patricia Elena', 'father_lastname' => 'Sanchez', 'mother_lastname' => 'Ortega', 'gender' => 'F', 'profession' => 'Medicina Humana', 'specialty' => 'Ginecologia', 'service_type' => 'ginecologia'],
            ['names' => 'Nicolas', 'father_lastname' => 'Aguilar', 'mother_lastname' => 'Reyes', 'gender' => 'M', 'profession' => 'Medicina Humana', 'specialty' => 'Traumatologia', 'service_type' => 'traumatologia'],
            ['names' => 'Gabriela', 'father_lastname' => 'Vera', 'mother_lastname' => 'Salinas', 'gender' => 'F', 'profession' => 'Odontologia', 'specialty' => 'Ortodoncia', 'service_type' => 'ortodoncia'],
            ['names' => 'Fernando', 'father_lastname' => 'Palacios', 'mother_lastname' => 'Cruz', 'gender' => 'M', 'profession' => 'Odontologia', 'specialty' => 'Periodoncia', 'service_type' => 'periodoncia'],
        ];
    }

    private function patientSeeds(): array
    {
        return [
            ['names' => 'Luciana', 'father_lastname' => 'Ramos', 'mother_lastname' => 'Flores', 'gender' => 'F', 'age' => 8],
            ['names' => 'Pedro Miguel', 'father_lastname' => 'Alvarez', 'mother_lastname' => 'Diaz', 'gender' => 'M', 'age' => 34],
            ['names' => 'Valeria', 'father_lastname' => 'Castillo', 'mother_lastname' => 'Nunez', 'gender' => 'F', 'age' => 27],
            ['names' => 'Hector', 'father_lastname' => 'Lopez', 'mother_lastname' => 'Soto', 'gender' => 'M', 'age' => 51],
            ['names' => 'Mariana', 'father_lastname' => 'Fernandez', 'mother_lastname' => 'Campos', 'gender' => 'F', 'age' => 42],
            ['names' => 'Diego', 'father_lastname' => 'Vargas', 'mother_lastname' => 'Mejia', 'gender' => 'M', 'age' => 15],
            ['names' => 'Elena', 'father_lastname' => 'Morales', 'mother_lastname' => 'Ibarra', 'gender' => 'F', 'age' => 63],
            ['names' => 'Santiago', 'father_lastname' => 'Ponce', 'mother_lastname' => 'Arias', 'gender' => 'M', 'age' => 29],
            ['names' => 'Camila', 'father_lastname' => 'Espinoza', 'mother_lastname' => 'Luna', 'gender' => 'F', 'age' => 22],
            ['names' => 'Rafael', 'father_lastname' => 'Gomez', 'mother_lastname' => 'Prado', 'gender' => 'M', 'age' => 46],
            ['names' => 'Andrea', 'father_lastname' => 'Silva', 'mother_lastname' => 'Barrios', 'gender' => 'F', 'age' => 31],
            ['names' => 'Mateo', 'father_lastname' => 'Contreras', 'mother_lastname' => 'Lagos', 'gender' => 'M', 'age' => 11],
            ['names' => 'Isabel', 'father_lastname' => 'Carrasco', 'mother_lastname' => 'Fuentes', 'gender' => 'F', 'age' => 57],
            ['names' => 'Rodrigo', 'father_lastname' => 'Mamani', 'mother_lastname' => 'Cueva', 'gender' => 'M', 'age' => 38],
            ['names' => 'Natalia', 'father_lastname' => 'Benites', 'mother_lastname' => 'Paz', 'gender' => 'F', 'age' => 19],
            ['names' => 'Alonso', 'father_lastname' => 'Rojas', 'mother_lastname' => 'Moya', 'gender' => 'M', 'age' => 44],
            ['names' => 'Renata', 'father_lastname' => 'Huaman', 'mother_lastname' => 'Vidal', 'gender' => 'F', 'age' => 36],
            ['names' => 'Oscar', 'father_lastname' => 'Navarro', 'mother_lastname' => 'Pena', 'gender' => 'M', 'age' => 69],
            ['names' => 'Bruno', 'father_lastname' => 'Delgado', 'mother_lastname' => 'Sierra', 'gender' => 'M', 'age' => 24, 'dental_only' => true],
            ['names' => 'Sofia', 'father_lastname' => 'Miranda', 'mother_lastname' => 'Acosta', 'gender' => 'F', 'age' => 17, 'dental_only' => true],
            ['names' => 'Teresa', 'father_lastname' => 'Valdivia', 'mother_lastname' => 'Pinto', 'gender' => 'F', 'age' => 55, 'dental_only' => true],
        ];
    }

    private function uniqueNumber(string $prefix, int $index): string
    {
        return $prefix . now()->format('His') . str_pad((string) $index, 2, '0', STR_PAD_LEFT) . random_int(1000, 9999);
    }

    private function isDental(?string $serviceType): bool
    {
        return in_array($serviceType, config('health.dental_service_types'), true);
    }

    private function testSlotAt(Carbon $today, int $slotIndex, string $direction): Carbon
    {
        $slotsPerDay = 15;
        $slotMinutes = 45;
        $startMinute = 8 * 60;
        $dayOffset = intdiv($slotIndex, $slotsPerDay) + 1;
        $minuteOfDay = $startMinute + (($slotIndex % $slotsPerDay) * $slotMinutes);
        $date = $direction === 'past'
            ? $today->copy()->subDays($dayOffset)
            : $today->copy()->addDays($dayOffset);

        return $date->setTime(intdiv($minuteOfDay, 60), $minuteOfDay % 60);
    }

    private function doctorForPatient(HealPatient $patient, int $sequence, $doctors, $dentalDoctors, $generalDoctors): HealDoctor
    {
        if (($patient->test_dental_only ?? false) && $dentalDoctors->isNotEmpty()) {
            return $dentalDoctors[$sequence % $dentalDoctors->count()];
        }

        if ($sequence % 4 === 0 && $dentalDoctors->isNotEmpty()) {
            return $dentalDoctors[$sequence % $dentalDoctors->count()];
        }

        if ($generalDoctors->isNotEmpty()) {
            return $generalDoctors[$sequence % $generalDoctors->count()];
        }

        return $doctors[$sequence % $doctors->count()];
    }

    private function appointmentTitle(?string $serviceType): string
    {
        return $this->isDental($serviceType) ? 'Evaluacion odontologica de prueba' : 'Consulta medica de prueba';
    }

    private function patientStory(int $index): string
    {
        $stories = [
            'Dolor intermitente y malestar general desde hace dos dias.',
            'Control preventivo con antecedente familiar relevante.',
            'Seguimiento por tratamiento previo con buena adherencia.',
            'Molestia localizada que aumenta con actividad diaria.',
        ];

        return $stories[$index % count($stories)];
    }

    private function clinicalFindings(?string $serviceType): string
    {
        return $this->isDental($serviceType)
            ? 'Placa bacteriana moderada, lesion cariosa localizada y sensibilidad al frio.'
            : 'Paciente hemodinamicamente estable, sin signos de alarma al examen inicial.';
    }

    private function diagnosis(?string $serviceType): string
    {
        return match ($serviceType) {
            'cardiologia' => 'Hipertension arterial en evaluacion.',
            'pediatria' => 'Rinofaringitis aguda no complicada.',
            'endodoncia' => 'Pulpitis irreversible sintomatica.',
            'odontologia_general' => 'Caries dental y gingivitis leve.',
            default => 'Cuadro clinico general en evaluacion.',
        };
    }

    private function treatmentPlan(?string $serviceType): string
    {
        return $this->isDental($serviceType)
            ? 'Profilaxis, restauracion y control odontologico programado.'
            : 'Manejo sintomatico, examenes complementarios y control evolutivo.';
    }

    private function examSeeds(?string $serviceType): array
    {
        if ($this->isDental($serviceType)) {
            return [
                ['exam_type' => 'radiografia', 'name' => 'Radiografia periapical', 'description' => 'Imagen de pieza comprometida.', 'result' => 'Zona radiolucida compatible con lesion cariosa.'],
                ['exam_type' => 'fotografia', 'name' => 'Fotografia intraoral', 'description' => 'Registro fotografico inicial.', 'result' => 'Se evidencia placa y lesion localizada.'],
            ];
        }

        return [
            ['exam_type' => 'laboratorio', 'name' => 'Hemograma completo', 'description' => 'Control hematologico de prueba.', 'result' => 'Valores dentro de rango esperado para prueba.'],
            ['exam_type' => 'imagen', 'name' => 'Ecografia/Radiografia segun criterio', 'description' => 'Examen complementario simulado.', 'result' => 'Sin hallazgos criticos en registro de prueba.'],
        ];
    }

    private function treatmentSeeds(?string $serviceType, int $index): array
    {
        if ($serviceType === 'endodoncia') {
            return [
                [
                    'treatment_type' => 'endodontico',
                    'name' => 'Endodoncia pieza 16',
                    'description' => 'Apertura cameral y conductometria de prueba.',
                    'indications' => 'Evitar masticacion intensa por 24 horas.',
                    'endodontic_data' => [
                        'tooth' => '16',
                        'diagnosis' => 'Pulpitis irreversible',
                        'session_number' => 1,
                        'status' => 'proceso',
                        'is_final_session' => false,
                        'ldr' => '20 mm',
                        'lt' => '19 mm',
                        'canals' => [
                            ['name' => 'MV', 'length' => '19 mm', 'supported_on' => 'RX', 'initial_file' => 'K10', 'working_file' => 'K25', 'master_file' => 'K30'],
                        ],
                    ],
                ],
            ];
        }

        if ($this->isDental($serviceType)) {
            return [
                ['treatment_type' => 'operatoria', 'name' => 'Restauracion con resina', 'description' => 'Restauracion clase I de prueba.', 'indications' => 'Control en 7 dias.'],
                ['treatment_type' => 'profilaxis', 'name' => 'Profilaxis dental', 'description' => 'Limpieza y educacion de higiene oral.', 'indications' => 'Cepillado tres veces al dia.'],
            ];
        }

        return [
            [
                'treatment_type' => 'farmacologica',
                'name' => 'Tratamiento farmacologico de prueba',
                'description' => 'Medicacion simulada segun diagnostico.',
                'indications' => 'Tomar segun indicacion medica y reportar signos de alarma.',
                'pharmacological_data' => [
                    'medication' => $index % 2 === 0 ? 'Paracetamol' : 'Ibuprofeno',
                    'dose' => $index % 2 === 0 ? '500 mg' : '400 mg',
                    'frequency' => 'Cada 8 horas',
                    'duration' => '3 dias',
                ],
            ],
        ];
    }

    private function odontogramTeeth(int $index): array
    {
        return [
            '16' => ['status' => 'caries', 'surfaces' => ['occlusal' => true], 'notes' => 'Caries oclusal de prueba.'],
            '11' => ['status' => 'obturado', 'surfaces' => [], 'notes' => 'Restauracion previa.'],
            '26' => ['status' => $index % 2 === 0 ? 'endodontic_required' : 'corona', 'surfaces' => [], 'notes' => 'Evaluar tratamiento.'],
            '36' => ['status' => 'fractura', 'surfaces' => ['distal' => true], 'notes' => 'Fractura coronaria simulada.'],
            'bridges' => [['from' => 14, 'to' => 16]],
        ];
    }
}
