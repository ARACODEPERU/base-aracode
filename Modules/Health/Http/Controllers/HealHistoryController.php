<?php

namespace Modules\Health\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Health\Entities\HealAllergy;
use Modules\Health\Entities\HealAttention;
use Modules\Health\Entities\HealPatient;
use Modules\Health\Support\HealthAudit;

class HealHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('health::index');
    }

    public function clinicalRecords(Request $request)
    {
        $search = trim((string) $request->get('search'));

        $patients = HealPatient::query()
            ->join('people', 'heal_patients.person_id', '=', 'people.id')
            ->leftJoin('heal_attentions', 'heal_attentions.patient_id', '=', 'heal_patients.id')
            ->select([
                'heal_patients.id',
                'heal_patients.person_id',
                'heal_patients.patient_code',
                'people.full_name',
                'people.names',
                'people.father_lastname',
                'people.mother_lastname',
                'people.number',
                'people.birthdate',
                'people.gender',
                DB::raw('MAX(heal_attentions.attention_at) as last_attention_at'),
                DB::raw('COUNT(heal_attentions.id) as attentions_count'),
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('people.full_name', 'like', "%{$search}%")
                        ->orWhere('people.names', 'like', "%{$search}%")
                        ->orWhere('people.father_lastname', 'like', "%{$search}%")
                        ->orWhere('people.mother_lastname', 'like', "%{$search}%")
                        ->orWhere('people.number', 'like', "%{$search}%");
                });
            })
            ->groupBy([
                'heal_patients.id',
                'heal_patients.person_id',
                'heal_patients.patient_code',
                'people.full_name',
                'people.names',
                'people.father_lastname',
                'people.mother_lastname',
                'people.number',
                'people.birthdate',
                'people.gender',
            ])
            ->orderByDesc('last_attention_at')
            ->orderBy('people.full_name')
            ->paginate($this->perPage($request, 15))
            ->withQueryString();

        return Inertia::render('Health::History/List', [
            'patients' => $patients,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function clinicalRecordShow(Request $request, HealPatient $patient)
    {
        $patient->load('person');

        // Abrir una historia clínica es un acceso a dato sensible: se audita.
        $this->registrarConsulta($patient, 'historia_clinica');

        $date = $request->filled('date')
            ? Carbon::parse($request->get('date'))->format('Y-m-d')
            : null;

        $attentions = HealAttention::with([
            'doctor.person',
            'signedByDoctor.person',
            'cie10',
            'treatments',
            'exams',
        ])
            ->where('patient_id', $patient->id)
            ->when($date, fn ($query) => $query->whereDate('attention_at', $date))
            ->orderByRaw('attention_at IS NULL ASC')
            ->orderByDesc('attention_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request, 5))
            ->withQueryString();

        $attentionDates = HealAttention::where('patient_id', $patient->id)
            ->whereNotNull('attention_at')
            ->selectRaw('DATE(attention_at) as date')
            ->distinct()
            ->orderByDesc('date')
            ->pluck('date')
            ->values();

        return Inertia::render('Health::History/Show', [
            'patient' => $patient,
            'attentions' => $attentions,
            'attentionDates' => $attentionDates,
            'filters' => [
                'date' => $date,
            ],
        ]);
    }

    public function patientStory($id)
    {
        $patient = HealPatient::with('person')
            ->where('id', $id)
            ->firstOrFail();

        $this->registrarConsulta($patient, 'relato_paciente');

        $allergies = $allergies = HealAllergy::with(['allergyPatient' => function ($query) use ($id) {
            $query->where('patient_id', $id);
        }])->get();

        return Inertia::render('Health::History/PatientStory', [
            'patient' => $patient,
            'allergies' => $allergies
        ]);
    }

    public function lastVitalSigns($id)
    {
        // Los signos vitales de Salud viven en heal_attentions (antes se leían
        // de la tabla odontológica, que no tiene esos campos).
        $attention = HealAttention::where('patient_id', $id)
            ->where(function ($query) {
                $query->whereNotNull('blood_pressure_systolic')
                    ->orWhereNotNull('blood_pressure_diastolic')
                    ->orWhereNotNull('heart_rate')
                    ->orWhereNotNull('respiratory_rate')
                    ->orWhereNotNull('height')
                    ->orWhereNotNull('weight')
                    ->orWhereNotNull('body_mass_index');
            })
            ->orderByDesc('attention_at')
            ->orderByDesc('id')
            ->first();

        if (!$attention) {
            return response()->json(['vitals' => []]);
        }

        // Se conservan las claves que ya consume LastVitalSigns.vue.
        return response()->json([
            'vitals' => [
                'date_time_attention' => $attention->attention_at,
                'pex_talla' => $attention->height,
                'pex_peso' => $attention->weight,
                'pex_imc' => $attention->body_mass_index,
                'pex_tem' => null,
                'pex_fc' => $attention->heart_rate,
                'pex_fr' => $attention->respiratory_rate,
                'pex_pa' => $attention->blood_pressure_systolic || $attention->blood_pressure_diastolic
                    ? ($attention->blood_pressure_systolic ?? '—') . '/' . ($attention->blood_pressure_diastolic ?? '—')
                    : null,
            ],
        ]);
    }

    /**
     * Limita el tamaño de página pedido por el cliente.
     */
    private function perPage(Request $request, int $default): int
    {
        return min(100, max(5, (int) $request->get('per_page', $default)));
    }

    /**
     * Deja constancia de quién consultó qué historia clínica.
     */
    private function registrarConsulta(HealPatient $patient, string $documento): void
    {
        HealthAudit::record('viewed', [
            'subject' => $patient,
            'patient_id' => (int) $patient->id,
            'patient_display' => $patient->person?->full_name,
            'category' => HealthAudit::CATEGORY_ACCESS,
            'metadata' => [
                'documento' => $documento,
                'subject_name' => $patient->person?->full_name ?? ('Paciente #' . $patient->id),
            ],
        ]);
    }
}
