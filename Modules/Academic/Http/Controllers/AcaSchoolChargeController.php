<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\ApisnetPeController;
use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Models\PaymentMethod;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolCharge;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolFee;
use Modules\Academic\Entities\AcaSchoolFeeType;
use Modules\Academic\Entities\AcaSchoolPaymentSchedule;
use Modules\Academic\Entities\AcaSchoolStudentGuardian;
use Modules\Academic\Services\SchoolChargeDocumentService;
use Modules\Academic\Services\SchoolCommitmentsService;
use Modules\Academic\Services\SchoolContextService;

class AcaSchoolChargeController extends Controller
{
    public function __construct(private SchoolContextService $context)
    {
    }

    /**
     * Vista de cobrar: conceptos con la tarifa resuelta por jerarquia,
     * compromisos (matricula + cronograma), historial de cobros y datos
     * para el modal de crear cobro / comprobante.
     */
    public function show(Request $request, int $enrollmentId)
    {
        $enrollment = AcaSchoolEnrollment::with([
            'student.person',
            'year:id,year,status',
            'section:id,name,shift,grade_id',
            'section.grade:id,name,level_id',
            'section.grade.level:id,name',
        ])->findOrFail($enrollmentId);

        $school = $this->context->currentSchool();

        if ($school && $enrollment->school_id !== $school->id) {
            return redirect()->route('aca_school_enrollments_list')
                ->with('error', 'La matrícula no pertenece al colegio activo.');
        }

        AcaSchoolFeeType::ensureDefaults();

        $section = $enrollment->section;
        $grade = $section?->grade;

        $concepts = AcaSchoolFeeType::where('status', true)->orderBy('id')->get()
            ->map(function (AcaSchoolFeeType $feeType) use ($enrollment, $grade, $section) {
                $fee = AcaSchoolFee::resolve(
                    $enrollment->year_id,
                    $feeType->id,
                    $grade?->level_id,
                    $grade?->id,
                    $section?->id
                );

                $paid = AcaSchoolCharge::where('enrollment_id', $enrollment->id)
                    ->where('fee_type_id', $feeType->id)
                    ->where('status', AcaSchoolCharge::STATUS_PAGADO)
                    ->sum('amount');

                return [
                    'id' => $feeType->id,
                    'code' => $feeType->code,
                    'name' => $feeType->name,
                    'is_recurring' => $feeType->is_recurring,
                    'fee_id' => $fee?->id,
                    'amount' => $fee ? (float) $fee->amount : null,
                    'paid_amount' => (float) $paid,
                ];
            });

        $schedules = AcaSchoolPaymentSchedule::query()
            ->where('enrollment_id', $enrollment->id)
            ->with([
                'charge:id,enrollment_id,fee_type_id,description,amount,status,payment_method,reference,paid_at,sale_id,sale_document_id',
                'charge.saleDocument:id,sale_id,invoice_serie,number,invoice_type_doc',
            ])
            ->orderBy('installment')
            ->get()
            ->map(function (AcaSchoolPaymentSchedule $schedule) {
                $arr = $schedule->toArray();

                if ($schedule->charge) {
                    $arr['charge']['pdf_url'] = $this->chargePdfUrl($schedule->charge);
                }

                return $arr;
            });

        $charges = AcaSchoolCharge::query()
            ->where('enrollment_id', $enrollment->id)
            ->with([
                'feeType:id,code,name',
                'saleDocument:id,sale_id,invoice_serie,number,invoice_type_doc',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (AcaSchoolCharge $charge) => array_merge($charge->toArray(), ['pdf_url' => $this->chargePdfUrl($charge)]))
            ->values();

        $matriculaCharge = null;
        $matriculaFeeType = AcaSchoolFeeType::where('code', 'matricula')->first();

        if ($matriculaFeeType) {
            $matricula = AcaSchoolCharge::where('enrollment_id', $enrollment->id)
                ->where('fee_type_id', $matriculaFeeType->id)
                ->whereIn('status', [AcaSchoolCharge::STATUS_PENDIENTE, AcaSchoolCharge::STATUS_PAGADO])
                ->with('saleDocument:id,sale_id,invoice_serie,number,invoice_type_doc')
                ->orderByDesc('id')
                ->first();

            if ($matricula) {
                $matriculaCharge = array_merge($matricula->toArray(), ['pdf_url' => $this->chargePdfUrl($matricula)]);
            }
        }

        return Inertia::render('Academic::School/Charges/Show', [
            'enrollment' => $enrollment,
            'schoolType' => $enrollment->school->type ?? 'privado',
            'concepts' => $concepts,
            'schedules' => $schedules,
            'charges' => $charges,
            'paymentMethods' => AcaSchoolCharge::paymentMethods(),
            'matriculaCharge' => $matriculaCharge,
            'personCandidates' => $this->personCandidates($enrollment),
            'identityDocuments' => DB::table('identity_document_type')->orderBy('id')->get(['id', 'description']),
            'documentTypes' => [
                ['value' => '80', 'label' => 'Nota de venta'],
                ['value' => '03', 'label' => 'Boleta'],
                ['value' => '01', 'label' => 'Factura'],
            ],
            'salePaymentMethods' => PaymentMethod::orderBy('id')->get(['id', 'description']),
            'taxes' => [
                'igv' => (float) Parameter::where('parameter_code', 'P000001')->value('value_default'),
                'icbper' => (float) Parameter::where('parameter_code', 'P000004')->value('value_default'),
            ],
        ]);
    }

    /**
     * Atajo desde la lista de alumnos: resuelve la matrícula activa del
     * alumno (la mas reciente) y redirige a la vista de cobrar.
     */
    public function byStudent(Request $request, int $studentId)
    {
        $enrollment = AcaSchoolEnrollment::where('student_id', $studentId)
            ->orderByDesc('id')
            ->first();

        if (! $enrollment) {
            return redirect()->route('aca_school_students_list')
                ->with('error', 'El alumno no tiene matrículas registradas.');
        }

        return redirect()->route('aca_school_charges_show', $enrollment->id);
    }

    /**
     * Registra un cobro (concepto suelto) o el pago de una cuota del
     * cronograma, sin comprobante. Los formularios van por axios: JSON siempre.
     */
    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'enrollment_id' => 'required|integer|exists:aca_school_enrollments,id',
            'fee_type_id' => 'required|integer|exists:aca_school_fee_types,id',
            'schedule_id' => 'nullable|integer|exists:aca_school_payment_schedules,id',
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'payment_method' => 'required|in:efectivo,transferencia,yape,plin,otro',
            'reference' => 'nullable|max:50',
            'notes' => 'nullable|max:300',
        ]);

        $enrollment = AcaSchoolEnrollment::with('year')->findOrFail($validated['enrollment_id']);
        $feeType = AcaSchoolFeeType::findOrFail($validated['fee_type_id']);
        $schedule = null;

        try {
            $charge = DB::transaction(function () use ($validated, $enrollment, $feeType, &$schedule) {
                if (! empty($validated['schedule_id'])) {
                    $schedule = AcaSchoolPaymentSchedule::where('id', $validated['schedule_id'])
                        ->where('enrollment_id', $enrollment->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($schedule->isPaid()) {
                        throw new \Exception('La cuota ya fue pagada.');
                    }
                }

                $description = $schedule
                    ? $feeType->name.' - Cuota '.$schedule->installment.' ('.$schedule->due_date->format('d/m/Y').')'
                    : $feeType->name.' '.$enrollment->year->year;

                $charge = AcaSchoolCharge::create([
                    'school_id' => $enrollment->school_id,
                    'enrollment_id' => $enrollment->id,
                    'fee_type_id' => $feeType->id,
                    'description' => $description,
                    'amount' => $validated['amount'],
                    'status' => AcaSchoolCharge::STATUS_PAGADO,
                    'paid_at' => $validated['paid_at'],
                    'payment_method' => $validated['payment_method'],
                    'reference' => $validated['reference'] ?? null,
                    'payment_schedule_id' => $schedule?->id,
                ]);

                if ($schedule) {
                    $schedule->update([
                        'charge_id' => $charge->id,
                        'paid_at' => $validated['paid_at'],
                    ]);
                }

                return $charge;
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $schedule
                ? 'Cuota pagada correctamente'
                : 'Cobro registrado correctamente',
            'charge_id' => $charge->id,
        ]);
    }

    /**
     * Crea el cobro / comprobante desde el modal multi-concepto:
     * paga los conceptos elegidos (compromisos, cuotas o extras) y emite
     * nota de venta (80), boleta (03), factura (01) o nada.
     */
    public function storeComprobante(Request $request)
    {
        $validated = $this->validate($request, [
            'enrollment_id' => 'required|integer|exists:aca_school_enrollments,id',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:charge,schedule,concept',
            'items.*.id' => 'nullable|integer',
            'items.*.fee_type_id' => 'nullable|integer|exists:aca_school_fee_types,id',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.detail' => 'nullable|max:100',
            'document_type' => 'nullable|in:80,01,03',
            'person_id' => 'nullable|integer|exists:people,id',
            'client_document_type_id' => 'nullable|integer',
            'client_number' => 'nullable|max:20',
            'client_full_name' => 'nullable|max:300',
            'client_address' => 'nullable|max:300',
            'client_email' => 'nullable|max:150',
            'client_telephone' => 'nullable|max:30',
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'reference' => 'nullable|max:50',
            'paid_at' => 'required|date',
        ]);

        $enrollment = AcaSchoolEnrollment::with('year')->findOrFail($validated['enrollment_id']);

        try {
            $result = app(SchoolChargeDocumentService::class)->register(
                $enrollment,
                $validated['items'],
                $validated['document_type'] ?? null,
                $validated['person_id'] ?? null,
                [
                    'document_type_id' => $validated['client_document_type_id'] ?? null,
                    'number' => $validated['client_number'] ?? null,
                    'full_name' => $validated['client_full_name'] ?? null,
                    'address' => $validated['client_address'] ?? null,
                    'email' => $validated['client_email'] ?? null,
                    'telephone' => $validated['client_telephone'] ?? null,
                ],
                (int) $validated['payment_method_id'],
                $validated['reference'] ?? null,
                $validated['paid_at'],
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Uno de los conceptos seleccionados ya no está disponible (refresca la página).',
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(array_merge(['success' => true], $result));
    }

    /**
     * Consulta RUC/DNI para autocompletar el cliente del comprobante.
     * Busca primero en la base de datos y, si no existe, consulta la
     * API de SUNAT (migo.pe) reutilizando ApisnetPeController.
     */
    public function consultClientDocument(Request $request)
    {
        $validated = $this->validate($request, [
            'document_type_id' => 'required|integer',
            'number' => 'required|string|max:15',
        ]);

        $number = trim($validated['number']);
        $typeId = (int) $validated['document_type_id'];

        // 1. Si la persona ya esta registrada usamos sus datos guardados.
        $person = Person::where('number', $number)
            ->where('document_type_id', $typeId)
            ->first();

        if ($person) {
            return response()->json([
                'success' => true,
                'source' => 'database',
                'person' => [
                    'razon_social' => $person->full_name,
                    'direccion' => $person->address,
                    'numero_documento' => $person->number,
                ],
            ]);
        }

        // 2. Consultamos la API externa (RUC en SUNAT, DNI en RENIEC, via migo.pe).
        $service = app(ApisnetPeController::class);
        $data = $typeId === 6
            ? $service->consultaRUCmigo($number)
            : $service->consultaDNImigo($number);

        return response()->json(array_merge($data ?? ['success' => false], [
            'source' => $typeId === 6 ? 'sunat' : 'reniec',
        ]));
    }

    /**
     * Anula un cobro: si provenia de una cuota, la cuota vuelve a pendiente.
     * El comprobante emitido no se anula automaticamente.
     */
    public function annul(Request $request, int $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $charge = AcaSchoolCharge::lockForUpdate()->findOrFail($id);

                if ($charge->status === AcaSchoolCharge::STATUS_ANULADO) {
                    throw new \Exception('El cobro ya está anulado.');
                }

                $charge->update(['status' => AcaSchoolCharge::STATUS_ANULADO]);

                if ($charge->payment_schedule_id) {
                    AcaSchoolPaymentSchedule::where('id', $charge->payment_schedule_id)
                        ->where('charge_id', $charge->id)
                        ->update(['charge_id' => null, 'paid_at' => null]);
                }
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Cobro anulado correctamente']);
    }

    /**
     * Genera los compromisos de pago de una matricula ya existente:
     * cargo pendiente de matricula + cronograma de mensualidades.
     */
    public function generateSchedule(Request $request)
    {
        $validated = $this->validate($request, [
            'enrollment_id' => 'required|integer|exists:aca_school_enrollments,id',
        ]);

        $enrollment = AcaSchoolEnrollment::with('year', 'section.grade', 'school')
            ->findOrFail($validated['enrollment_id']);

        $result = app(SchoolCommitmentsService::class)->createForEnrollment($enrollment);

        $created = ($result['matricula_charge_id'] ? 1 : 0) + $result['installments_created'];

        if ($created === 0) {
            $schoolType = $enrollment->school->type ?? 'privado';
            $message = $schoolType !== 'privado'
                ? 'El cronograma de mensualidades solo aplica a colegios privados y no hay tarifa de matrícula configurada.'
                : 'Los compromisos ya están generados o no hay tarifas configuradas para el alcance del alumno (configure la tarifa primero).';

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $parts = [];
        if ($result['matricula_charge_id']) {
            $parts[] = 'compromiso de matrícula';
        }
        if ($result['installments_created'] > 0) {
            $parts[] = $result['installments_created'].' cuotas mensuales';
        }

        return response()->json([
            'success' => true,
            'message' => 'Compromisos generados: '.implode(' + ', $parts).'.',
        ]);
    }

    /**
     * URL del PDF del comprobante vinculado al cobro (si existe).
     */
    private function chargePdfUrl(AcaSchoolCharge $charge): ?string
    {
        if (! $charge->sale_document_id || ! $charge->saleDocument) {
            return null;
        }

        $type = $charge->saleDocument->invoice_type_doc;

        if (in_array($type, ['01', '03'], true)) {
            return route('saledocuments_download', [
                'id' => $charge->sale_document_id,
                'type' => $type,
                'file' => 'PDF',
                'format' => 'A4',
            ]);
        }

        if ($type === '80' && $charge->sale_id) {
            return route('ticketpdf_sales', ['id' => $charge->sale_id]);
        }

        return null;
    }

    /**
     * Personas a nombre de quien se puede emitir el comprobante:
     * apoderados del alumno + el propio alumno.
     */
    private function personCandidates(AcaSchoolEnrollment $enrollment): array
    {
        $candidates = [];
        $student = $enrollment->student;

        $guardians = AcaSchoolStudentGuardian::query()
            ->with('person')
            ->where('student_id', $student?->id)
            ->where('status', true)
            ->orderByDesc('is_primary')
            ->get();

        foreach ($guardians as $guardian) {
            if ($guardian->person) {
                $candidates[] = $this->candidate($guardian->person, 'Apoderado'.($guardian->relationship ? ' ('.$guardian->relationshipLabel().')' : ''));
            }
        }

        if ($student?->person) {
            $candidates[] = $this->candidate($student->person, 'Alumno');
        }

        return $candidates;
    }

    private function candidate(Person $person, string $label): array
    {
        return [
            'id' => $person->id,
            'label' => $label,
            'full_name' => $person->full_name,
            'number' => $person->number,
            'document_type_id' => (int) ($person->document_type_id ?? 1),
            'address' => $person->address,
            'email' => $person->email,
            'telephone' => $person->telephone,
            'ubigeo' => $person->ubigeo,
            'ubigeo_description' => $person->ubigeo_description,
        ];
    }
}
