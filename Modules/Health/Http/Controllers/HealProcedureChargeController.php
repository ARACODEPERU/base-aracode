<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Health\Entities\HealAttention;
use Modules\Health\Entities\HealPatient;
use Modules\Health\Entities\HealPatientCharge;
use Modules\Health\Entities\HealProcedure;
use Modules\Health\Support\PendingSignatureReminder;

class HealProcedureChargeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->get('search'));
        $status = $request->get('status');
        $onlyPending = $request->boolean('only_pending', true);

        $charges = HealPatientCharge::with(['patient.person', 'doctor.person', 'procedure', 'attention', 'saleDocument'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name_snapshot', 'like', "%{$search}%")
                        ->orWhereHas('patient.person', function ($person) use ($search) {
                            $person->where('full_name', 'like', "%{$search}%")
                                ->orWhere('number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($onlyPending, function ($query) {
                $query->where(function ($q) {
                    $q->where('status', 'pending')
                        ->orWhere(function ($paidWithoutDocument) {
                            $paidWithoutDocument->where('status', 'paid')
                                ->whereNull('sale_document_id');
                        });
                });
            })
            ->when(!$onlyPending && $status, fn ($query) => $query->where('status', $status))
            ->latest('charged_at')
            ->latest('id')
            ->paginate($request->get('per_page', 10))
            ->withQueryString();

        return Inertia::render('Health::Procedures/Index', [
            'charges' => $charges,
            'procedures' => $this->procedureOptions(),
            'currencies' => $this->currencyOptions(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'only_pending' => $onlyPending,
            ],
            'pendingSignatures' => PendingSignatureReminder::items(),
        ]);
    }

    public function storeProcedure(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'default_price' => ['required', 'numeric', 'min:0'],
            'currency_type_id' => ['required', 'string', 'max:3'],
            'is_consultation' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        HealProcedure::create([
            ...$data,
            'is_consultation' => (bool) ($data['is_consultation'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back();
    }

    public function updateProcedure(Request $request, HealProcedure $procedure): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'default_price' => ['required', 'numeric', 'min:0'],
            'currency_type_id' => ['required', 'string', 'max:3'],
            'is_consultation' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $procedure->update([
            ...$data,
            'is_consultation' => (bool) ($data['is_consultation'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back();
    }

    public function storeCharge(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:heal_patients,id'],
            'attention_id' => ['nullable', 'exists:heal_attentions,id'],
            'procedure_id' => ['required', 'exists:heal_procedures,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'charged_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $procedure = HealProcedure::findOrFail($data['procedure_id']);
        $attention = !empty($data['attention_id']) ? HealAttention::find($data['attention_id']) : null;

        $this->createChargeFromProcedure($procedure, [
            ...$data,
            'doctor_id' => $attention?->doctor_id,
            'charged_at' => $data['charged_at'] ?? now(),
        ]);

        return back();
    }

    public function storeManyCharges(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:heal_patients,id'],
            'attention_id' => ['nullable', 'exists:heal_attentions,id'],
            'charged_at' => ['nullable', 'date'],
            'charges' => ['required', 'array', 'min:1'],
            'charges.*.procedure_id' => ['required', 'exists:heal_procedures,id'],
            'charges.*.price' => ['required', 'numeric', 'min:0'],
            'charges.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'charges.*.notes' => ['nullable', 'string'],
        ]);

        $attention = !empty($data['attention_id']) ? HealAttention::findOrFail($data['attention_id']) : null;

        if ($attention && (int) $attention->patient_id !== (int) $data['patient_id']) {
            return back()->withErrors(['attention_id' => 'La atención seleccionada no pertenece al paciente.']);
        }

        DB::transaction(function () use ($data, $attention) {
            foreach ($data['charges'] as $charge) {
                $procedure = HealProcedure::where('is_active', true)->find($charge['procedure_id']);

                if (!$procedure) {
                    continue;
                }

                $this->createChargeFromProcedure($procedure, [
                    ...$charge,
                    'patient_id' => $data['patient_id'],
                    'attention_id' => $attention?->id,
                    'doctor_id' => $attention?->doctor_id,
                    'charged_at' => $data['charged_at'] ?? $attention?->attention_at ?? now(),
                ]);
            }
        });

        return back();
    }

    public function patientAttentions(HealPatient $patient)
    {
        $procedures = HealProcedure::where('is_active', true)->get();
        $procedureLookup = $procedures->mapWithKeys(fn (HealProcedure $procedure) => [
            $this->normalizeLookupText($procedure->name) => $procedure,
        ]);

        $attentions = HealAttention::with(['doctor.person', 'treatments', 'charges'])
            ->where('patient_id', $patient->id)
            ->latest('attention_at')
            ->limit(30)
            ->get()
            ->map(function (HealAttention $attention) use ($procedures, $procedureLookup) {
                return [
                    'id' => $attention->id,
                    'attention_at' => $attention->attention_at,
                    'service_type' => $attention->service_type,
                    'doctor_name' => $attention->doctor?->person?->full_name,
                    'charges_count' => $attention->charges->count(),
                    'treatments' => $attention->treatments
                        ->filter(fn ($treatment) => $this->hasBillableTreatmentData($treatment))
                        ->map(function ($treatment) use ($procedures, $procedureLookup) {
                            $suggestedProcedure = $this->suggestProcedureForTreatment($treatment, $procedures, $procedureLookup);

                            return [
                                'id' => $treatment->id,
                                'treatment_type' => $treatment->treatment_type,
                                'name' => $treatment->name,
                                'description' => $treatment->description,
                                'indications' => $treatment->indications,
                                'endodontic_data' => $treatment->endodontic_data,
                                'suggested_procedure_id' => $suggestedProcedure?->id,
                                'suggested_notes' => $this->treatmentNotes($treatment),
                            ];
                        })
                        ->values(),
                ];
            });

        return response()->json([
            'success' => true,
            'attentions' => $attentions,
        ]);
    }

    public function updateChargeStatus(Request $request, HealPatientCharge $charge): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['cancelled'])],
        ]);

        $charge->update($data);

        return back();
    }

    public function prepareSalesReview(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'charge_ids' => ['required', 'array', 'min:1'],
            'charge_ids.*' => ['integer', 'exists:heal_patient_charges,id'],
        ]);

        $charges = HealPatientCharge::with('patient.person')
            ->whereIn('id', $data['charge_ids'])
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere(function ($paidWithoutDocument) {
                        $paidWithoutDocument->where('status', 'paid')
                            ->whereNull('sale_document_id');
                    });
            })
            ->get();

        if ($charges->isEmpty()) {
            return back()->withErrors(['charges' => 'Selecciona cobros pendientes o pagados sin documento para revisar en Sales.']);
        }

        $patientIds = $charges->pluck('patient_id')->unique();

        if ($patientIds->count() > 1) {
            return back()->withErrors(['charges' => 'Solo se pueden preparar cobros de un paciente a la vez.']);
        }

        $patient = $charges->first()->patient;
        $person = $patient?->person;

        session()->put('health_billing_draft', [
            'charge_ids' => $charges->pluck('id')->values()->all(),
            'client' => $person ? $this->salesClientPayload($person) : null,
            'items' => $charges->map(fn (HealPatientCharge $charge) => $this->salesItemPayload($charge))->values()->all(),
            'additional_description' => 'Cobros generados desde Salud. Revisar antes de emitir.',
        ]);

        return redirect()->route('saledocuments_create');
    }

    public function createChargeFromProcedure(HealProcedure $procedure, array $data): HealPatientCharge
    {
        $price = (float) ($data['price'] ?? $procedure->default_price);
        $quantity = (float) ($data['quantity'] ?? 1);

        return HealPatientCharge::create([
            'patient_id' => $data['patient_id'],
            'attention_id' => $data['attention_id'] ?? null,
            'doctor_id' => $data['doctor_id'] ?? null,
            'procedure_id' => $procedure->id,
            'name_snapshot' => $data['name_snapshot'] ?? $procedure->name,
            'description_snapshot' => $data['description_snapshot'] ?? $procedure->description,
            'default_price' => $procedure->default_price,
            'price' => $price,
            'quantity' => $quantity,
            'total' => round($price * $quantity, 2),
            'currency_type_id' => $data['currency_type_id'] ?? $procedure->currency_type_id,
            'status' => $data['status'] ?? 'pending',
            'charged_at' => $data['charged_at'] ?? Carbon::now(),
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);
    }

    public function procedureOptions()
    {
        return HealProcedure::orderByDesc('is_consultation')
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    public function shouldSuggestConsultation(int $patientId): bool
    {
        $lastAttention = HealAttention::where('patient_id', $patientId)
            ->latest('attention_at')
            ->first();

        if (!$lastAttention) {
            return true;
        }

        return Carbon::parse($lastAttention->attention_at)->lt(now()->subMonths(2));
    }

    public function consultationProcedure(): ?HealProcedure
    {
        return HealProcedure::where('is_consultation', true)
            ->where('is_active', true)
            ->first();
    }

    private function hasBillableTreatmentData($treatment): bool
    {
        return filled($treatment->name)
            || filled($treatment->description)
            || filled($treatment->indications)
            || filled($treatment->endodontic_data);
    }

    private function suggestProcedureForTreatment($treatment, $procedures, $procedureLookup): ?HealProcedure
    {
        $nameKey = $this->normalizeLookupText($treatment->name);

        if ($nameKey && $procedureLookup->has($nameKey)) {
            return $procedureLookup->get($nameKey);
        }

        $typeKey = $this->normalizeLookupText($treatment->treatment_type);

        if (str_contains($typeKey, 'endodont')) {
            return $procedures->first(fn (HealProcedure $procedure) => str_contains($this->normalizeLookupText($procedure->name), 'endodoncia'));
        }

        return $procedures->first(function (HealProcedure $procedure) use ($typeKey) {
            $haystack = $this->normalizeLookupText($procedure->name . ' ' . $procedure->category);

            return $typeKey && str_contains($haystack, $typeKey);
        });
    }

    private function treatmentNotes($treatment): string
    {
        $parts = array_filter([
            $treatment->name,
            $treatment->description,
            $treatment->indications,
        ]);

        $data = $treatment->endodontic_data ?: [];

        if (!empty($data['tooth'])) {
            $parts[] = 'Pieza: ' . $data['tooth'];
        }

        if (!empty($data['session_number'])) {
            $parts[] = 'Sesion: ' . $data['session_number'];
        }

        return implode(' | ', $parts);
    }

    private function normalizeLookupText(?string $text): string
    {
        return str($text ?: '')
            ->lower()
            ->ascii()
            ->trim()
            ->toString();
    }

    private function currencyOptions()
    {
        return DB::table('sunat_currency_types')
            ->where('active', true)
            ->orderBy('id')
            ->get(['id', 'symbol', 'description']);
    }

    private function salesClientPayload(Person $person): array
    {
        return [
            'id' => $person->id,
            'full_name' => $person->full_name,
            'number' => $person->number,
            'document_type_id' => $person->document_type_id,
            'ubigeo' => $person->ubigeo,
            'telephone' => $person->telephone,
            'email' => $person->email,
            'address' => $person->address,
        ];
    }

    private function salesItemPayload(HealPatientCharge $charge): array
    {
        return [
            'id' => null,
            'description' => $charge->name_snapshot,
            'is_product' => false,
            'unit_type' => 'ZZ',
            'quantity' => (float) $charge->quantity,
            'unit_price' => (float) $charge->price,
            'discount' => 0,
            'total' => (float) $charge->total,
            'afe_igv' => 10,
            'size' => null,
            'm_igv' => 0,
            'presentations' => null,
            'icbper' => false,
            'health_charge_id' => $charge->id,
        ];
    }
}
