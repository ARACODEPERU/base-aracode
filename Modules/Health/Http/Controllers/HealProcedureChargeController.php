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
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Health\Entities\HealAttention;
use Modules\Health\Entities\HealPatient;
use Modules\Health\Entities\HealPatientCharge;
use Modules\Health\Entities\HealProcedure;
use Modules\Health\Support\BillingOptions;
use Modules\Health\Support\PendingSignatureReminder;
use Modules\Health\Support\ResolvesDoctorContext;

class HealProcedureChargeController extends Controller
{
    use ResolvesDoctorContext;

    /** Estado normal de un cobro por documentar. */
    private const STATUS_PENDING = 'pending';

    /**
     * Reserva: el cobro viajo a Sales y espera el documento de venta. Antes
     * nadie asignaba este estado, asi que el filtro "Facturados" salia vacio y
     * el mismo cobro podia facturarse dos veces.
     */
    private const STATUS_BILLED = 'billed';

    /** Estados que acepta el filtro de la pantalla. */
    private const CHARGE_STATUSES = ['pending', 'billed', 'paid', 'cancelled'];

    /** Filas por pagina cuando el parametro falta o no es un numero util. */
    private const DEFAULT_PER_PAGE = 10;

    /** Tope de filas por pagina: `per_page` llega desde el navegador. */
    private const MAX_PER_PAGE = 100;

    /** Minutos que un cobro queda reservado esperando el documento de venta. */
    private const RESERVATION_MINUTES = 30;

    /** `sale_documents.status` 3 = documento anulado. */
    private const CANCELLED_DOCUMENT_STATUS = 3;

    public function index(Request $request): Response
    {
        $search = trim((string) $request->get('search'));
        $status = (string) $request->get('status', '');
        $onlyPending = $request->boolean('only_pending', true);

        if (!in_array($status, self::CHARGE_STATUSES, true)) {
            $status = '';
        }

        // `per_page` viaja en la URL: casteado y acotado, porque un valor como
        // "abc" revienta el paginador y uno enorme carga la tabla completa.
        $requestedPerPage = (int) $request->get('per_page', self::DEFAULT_PER_PAGE);
        $perPage = $requestedPerPage > 0
            ? min(self::MAX_PER_PAGE, $requestedPerPage)
            : self::DEFAULT_PER_PAGE;

        // Una reserva que nunca llego a documento no debe dejar el cobro
        // escondido para siempre.
        $this->releaseStaleReservations();

        $charges = HealPatientCharge::with(['patient.person', 'doctor.person', 'procedure', 'attention', 'saleDocument'])
            ->when($this->ownDoctorId(), fn ($query, $doctorId) => $query->where('doctor_id', $doctorId))
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
            ->paginate($perPage)
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
            // El catalogo cambia precios: la pantalla oculta el formulario a
            // quien no puede guardarlo (heal_configuracion).
            'canManageProcedures' => (bool) Auth::user()?->can('heal_configuracion'),
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

        // Un campo ausente conserva lo que ya tenia el procedimiento: si no,
        // editar el precio desde otro cliente desactivaba el procedimiento.
        $procedure->update([
            ...$data,
            'is_consultation' => (bool) ($data['is_consultation'] ?? $procedure->is_consultation),
            'is_active' => (bool) ($data['is_active'] ?? $procedure->is_active),
        ]);

        return back();
    }

    public function storeCharge(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:heal_patients,id'],
            'attention_id' => ['nullable', 'exists:heal_attentions,id'],
            'procedure_id' => ['required', Rule::exists('heal_procedures', 'id')->where('is_active', true)],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'charged_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $procedure = HealProcedure::findOrFail($data['procedure_id']);
        $attention = $this->resolveAttention($data['attention_id'] ?? null, (int) $data['patient_id']);

        $this->createChargeFromProcedure($procedure, [
            ...$data,
            'doctor_id' => $attention?->doctor_id ?? $this->currentDoctor()?->id,
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

        $attention = $this->resolveAttention($data['attention_id'] ?? null, (int) $data['patient_id']);

        // Un procedimiento retirado del catalogo no debe pasar en silencio: el
        // usuario veria "Cobro registrado" con la lista vacia. Se cargan todos
        // de una vez para no repetir la consulta por cada fila.
        $requestedIds = collect($data['charges'])
            ->pluck('procedure_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $procedures = HealProcedure::whereIn('id', $requestedIds)->get()->keyBy('id');

        $rejected = $requestedIds
            ->reject(fn (int $id) => $procedures->has($id) && $procedures->get($id)?->is_active)
            ->map(fn (int $id) => $procedures->get($id)?->name ?? "Procedimiento #{$id}");

        if ($rejected->isNotEmpty()) {
            throw ValidationException::withMessages([
                'charges' => 'Estos procedimientos ya no estan disponibles en el catalogo: '
                    . $rejected->implode(', ') . '. Actualiza la lista y vuelve a intentarlo.',
            ]);
        }

        $doctorId = $attention?->doctor_id ?? $this->currentDoctor()?->id;

        DB::transaction(function () use ($data, $attention, $procedures, $doctorId) {
            foreach ($data['charges'] as $charge) {
                $procedure = $procedures->get((int) $charge['procedure_id']);

                if (!$procedure) {
                    continue;
                }

                $this->createChargeFromProcedure($procedure, [
                    ...$charge,
                    'patient_id' => $data['patient_id'],
                    'attention_id' => $attention?->id,
                    'doctor_id' => $doctorId,
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

        $attentions = HealAttention::with(['doctor.person', 'treatments'])
            ->withCount('charges')
            ->when($this->ownDoctorId(), fn ($query, $doctorId) => $query->where('doctor_id', $doctorId))
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
                    'charges_count' => (int) $attention->charges_count,
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

        $this->guardChargeScope($charge);

        $this->guardBillableDocument($charge);

        $charge->update($data);

        return back();
    }

    /**
     * Devuelve un cobro reservado a la lista de pendientes.
     *
     * Se usa cuando el documento de venta no llego a emitirse y el usuario no
     * quiere esperar a que venza la reserva.
     */
    public function releaseCharge(HealPatientCharge $charge): RedirectResponse
    {
        $this->guardChargeScope($charge);

        if ($charge->status !== self::STATUS_BILLED) {
            return back()->withErrors([
                'status' => 'Solo se pueden liberar los cobros en preparacion para Sales.',
            ]);
        }

        $charge->update([
            'status' => self::STATUS_PENDING,
            'billed_at' => null,
        ]);

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
            ->when($this->ownDoctorId(), fn ($query, $doctorId) => $query->where('doctor_id', $doctorId))
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

        // El borrador anterior vuelve a la lista: si no, un borrador abandonado
        // dejaba sus cobros fuera de "pendientes por documentar" hasta que
        // venciera la reserva.
        $this->releasePreviousDraft();

        // Reserva: mientras el documento esta en preparacion nadie mas puede
        // enviar estos cobros a Sales, que era la via de la doble facturacion.
        HealPatientCharge::whereIn('id', $charges->pluck('id')->all())->update([
            'status' => self::STATUS_BILLED,
            'billed_at' => now(),
        ]);

        $options = BillingOptions::read();

        session()->put('health_billing_draft', [
            'charge_ids' => $charges->pluck('id')->values()->all(),
            'client' => $person ? $this->salesClientPayload($person) : null,
            'items' => $charges->map(fn (HealPatientCharge $charge) => $this->salesItemPayload($charge))->values()->all(),
            'additional_description' => $options['billing_description'],
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

        $months = BillingOptions::read()['consultation_recobro_months'];

        return Carbon::parse($lastAttention->attention_at)->lt(now()->subMonths($months));
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
        $options = BillingOptions::read();

        return [
            'id' => null,
            'description' => $charge->name_snapshot,
            'is_product' => false,
            // Unidad y afectacion del IGV salen de Salud > Configuracion: los
            // servicios medicos suelen ir exonerados y antes todo salia gravado.
            'unit_type' => $options['unit_type'],
            'quantity' => (float) $charge->quantity,
            'unit_price' => (float) $charge->price,
            'discount' => 0,
            'total' => (float) $charge->total,
            'afe_igv' => $options['igv_affectation'],
            'size' => null,
            'm_igv' => 0,
            'presentations' => null,
            'icbper' => false,
            'health_charge_id' => $charge->id,
        ];
    }

    /**
     * Doctor al que se limita la pantalla, o null cuando administra a todos.
     *
     * Misma semantica que la lista de atenciones: el administrador ve y cobra a
     * cualquier doctor, y el resto solo lo suyo. Sin doctor vinculado responde
     * 403 en vez de mostrar datos de otros.
     */
    private function ownDoctorId(): ?int
    {
        if ($this->canChooseDoctor()) {
            return null;
        }

        return $this->currentDoctorIdOrAbort();
    }

    private function currentDoctorIdOrAbort(): int
    {
        $doctor = $this->currentDoctor();

        abort_unless($doctor, 403, 'Tu usuario no está vinculado a un doctor.');

        return (int) $doctor->id;
    }

    /**
     * Resuelve la atencion seleccionada y exige que sea del paciente.
     *
     * Antes solo lo validaba el alta multiple: el alta individual tomaba el
     * doctor de una atencion que podia ser de otro paciente.
     */
    private function resolveAttention(mixed $attentionId, int $patientId): ?HealAttention
    {
        if (!$attentionId) {
            return null;
        }

        $attention = HealAttention::find($attentionId);

        if ($attention && (int) $attention->patient_id !== $patientId) {
            throw ValidationException::withMessages([
                'attention_id' => 'La atención seleccionada no pertenece al paciente.',
            ]);
        }

        return $attention;
    }

    /** El doctor solo opera sobre sus propios cobros. */
    private function guardChargeScope(HealPatientCharge $charge): void
    {
        if ($this->canChooseDoctor()) {
            return;
        }

        abort_unless(
            (int) $charge->doctor_id === $this->currentDoctorIdOrAbort(),
            403,
            'Este cobro pertenece a otro doctor.'
        );
    }

    /**
     * Un cobro con comprobante vigente no se anula desde Salud: el documento y
     * el ingreso en tesoreria seguirian vivos y las cuentas no cuadrarian.
     */
    private function guardBillableDocument(HealPatientCharge $charge): void
    {
        $document = $charge->saleDocument;

        if (!$document || (int) $document->status === self::CANCELLED_DOCUMENT_STATUS) {
            return;
        }

        if (BillingOptions::read()['allow_cancel_with_document']) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'Este cobro ya generó un comprobante vigente. Anula o acredita primero el documento en Sales, '
                . 'o habilita la anulación de cobros facturados en Salud > Configuración.',
        ]);
    }

    /**
     * Libera las reservas vencidas: un borrador de venta abandonado no debe
     * esconder el cobro para siempre.
     */
    private function releaseStaleReservations(): void
    {
        HealPatientCharge::where('status', self::STATUS_BILLED)
            ->whereNull('sale_document_id')
            ->where('billed_at', '<', now()->subMinutes(self::RESERVATION_MINUTES))
            ->update([
                'status' => self::STATUS_PENDING,
                'billed_at' => null,
            ]);
    }

    /** Devuelve a pendientes los cobros del borrador de venta anterior. */
    private function releasePreviousDraft(): void
    {
        $previous = session()->pull('health_billing_draft', []);
        $ids = collect(is_array($previous) ? ($previous['charge_ids'] ?? []) : [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        HealPatientCharge::whereIn('id', $ids->all())
            ->where('status', self::STATUS_BILLED)
            ->whereNull('sale_document_id')
            ->update([
                'status' => self::STATUS_PENDING,
                'billed_at' => null,
            ]);
    }
}
