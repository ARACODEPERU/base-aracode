<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\IdentityDocumentType;
use App\Models\Person;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleDocumentItem;
use App\Models\SaleProduct;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Http\Controllers\AcaSaleDocumentController;
use Modules\Commercial\Emails\CommercialNegotiationDocumentMail;
use Modules\Commercial\Entities\CommercialNegotiation;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Sales\Entities\SalePaymentSchedule;

class CommercialNegotiationProcessController extends Controller
{
    public function index($id)
    {
        $negotiation = CommercialNegotiation::with(['items', 'client', 'creator', 'verifier', 'invoice'])
            ->findOrFail($id);

        if ($negotiation->status !== 'confirmada') {
            return redirect()->route('comm_negotiations_show', $negotiation->id)
                ->with('error', 'Solo se pueden aprobar negociaciones confirmadas.');
        }

        return Inertia::render('Commercial::Negotiations/Process', [
            'negotiation' => $negotiation,
            'statuses' => $this->statuses(),
            'paymentMethods' => $this->paymentMethods(),
            'stepsStatus' => $this->stepStatuses($negotiation),
            'existingAccount' => $this->existingAccountInfo($negotiation),
        ]);
    }

    /**
     * Detecta si el cliente de la negociacion ya tiene persona, cuenta de usuario
     * y/o registro de estudiante: la pantalla de proceso usa esto para mostrar
     * "Actualizar" en lugar de "Crear" y diferenciarlo con otro color.
     */
    private function existingAccountInfo(CommercialNegotiation $negotiation): array
    {
        try {
            $person = $negotiation->client_id ? Person::find($negotiation->client_id) : null;

            if (! $person) {
                return ['has_user' => false, 'has_student' => false];
            }

            $user = User::where('person_id', $person->id)->first();

            if (! $user && $person->email) {
                $user = User::where('email', trim($person->email))->first();
            }

            return [
                'has_user' => (bool) $user,
                'has_student' => AcaStudent::where('person_id', $person->id)->exists(),
            ];
        } catch (\Throwable $e) {
            return ['has_user' => false, 'has_student' => false];
        }
    }

    /**
     * Determina el estado real (done / skipped / pending) de cada paso del proceso
     * para persistir el progreso al recargar. La fuente unica es el campo
     * process_progress: si no existe ningun avance registrado, el proceso inicia
     * en cero (todos los pasos pending o skipped segun corresponda), aun cuando el
     * cliente ya exista o haya confirmado antes. Solo se retoma el progreso cuando
     * el administrador ya ejecuto algun paso y este quedo guardado en process_progress.
     *
     * @return array<string, string>
     */
    public function stepStatuses(CommercialNegotiation $negotiation): array
    {
        $progress = $negotiation->process_progress ?? [];
        $isInstallments = $negotiation->payment_type === 'installments';

        $hasCourses = $negotiation->items->contains(fn ($item) => $item->item_type === 'course');
        $hasSubscriptions = $negotiation->items->contains(fn ($item) => $item->item_type === 'subscription');

        $statusOf = function (string $key, string $fallback = 'pending') use ($progress) {
            if (in_array($key, $progress, true)) {
                return 'done';
            }

            // Paso omitido manualmente desde la pantalla de proceso.
            if (in_array('skipped:'.$key, $progress, true)) {
                return 'omitted';
            }

            return $fallback;
        };

        return [
            'person' => $statusOf('person'),
            'user' => $statusOf('user'),
            'student' => $statusOf('student'),
            'registrations' => $hasCourses ? $statusOf('registrations') : 'skipped',
            'subscriptions' => $hasSubscriptions ? $statusOf('subscriptions') : 'skipped',
            'installments' => $isInstallments ? $statusOf('installments') : 'skipped',
            'document' => $statusOf('document'),
            'email' => $statusOf('email'),
            'webhook' => $statusOf('webhook'),
            'complete' => $statusOf('complete'),
        ];
    }

    /**
     * Claves validas de los pasos del proceso de aprobacion.
     */
    private const PROCESS_KEYS = [
        'person', 'user', 'student', 'registrations', 'subscriptions',
        'installments', 'document', 'email', 'webhook', 'complete',
    ];

    /**
     * Marca un paso como omitido manualmente (error del paso + decision del
     * administrador de continuar con los demas). Se persiste en process_progress
     * con el prefijo "skipped:" para que al recargar la pantalla siga mostrandose
     * como omitido y el proceso no lo vuelva a intentar.
     */
    public function skipStep(Request $request, $id, $key)
    {
        if (! in_array($key, self::PROCESS_KEYS, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Paso invalido.',
            ], 422);
        }

        $negotiation = $this->negotiation($id);
        $progress = $negotiation->process_progress ?? [];

        if (in_array($key, $progress, true)) {
            return response()->json([
                'success' => true,
                'message' => 'El paso ya estaba completado.',
            ]);
        }

        $skippedKey = 'skipped:'.$key;

        if (! in_array($skippedKey, $progress, true)) {
            $progress[] = $skippedKey;
            $negotiation->update(['process_progress' => $progress]);
        }

        return response()->json([
            'success' => true,
            'skipped' => true,
            'message' => 'Paso omitido: se continuara con los pasos restantes.',
        ]);
    }

    /**
     * Marca un paso del proceso como ejecutado en process_progress.
     */
    private function markStepDone(CommercialNegotiation $negotiation, string $key): void
    {
        $progress = $negotiation->process_progress ?? [];

        if (! in_array($key, $progress, true)) {
            $progress[] = $key;
            $negotiation->update(['process_progress' => $progress]);
        }
    }

    public function processPerson(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);
        $data = $negotiation->client_data ?? [];

        if (! $negotiation->client_id) {
            throw new \Exception('La negociacion no tiene un cliente registrado.');
        }

        $person = Person::find($negotiation->client_id);

        if (! $person) {
            throw new \Exception('El cliente registrado no existe en la tabla people.');
        }

        $personPayload = [
            'short_name' => $data['short_name'] ?? $data['names'] ?? $person->short_name,
            'full_name' => $data['full_name'] ?? $person->full_name,
            'document_type_id' => $data['document_type_id'] ?? $person->document_type_id,
            'number' => $data['number'] ?? $person->number,
            'names' => $data['names'] ?? $person->names,
            'father_lastname' => $data['father_lastname'] ?? $person->father_lastname,
            'mother_lastname' => $data['mother_lastname'] ?? $person->mother_lastname,
            'gender' => $data['gender'] ?? $person->gender,
            'email' => $data['email'] ?? $person->email,
            'telephone' => $data['telephone'] ?? $person->telephone,
            'ocupacion' => $data['ocupacion'] ?? $person->ocupacion,
            'company' => $data['company'] ?? $person->company,
            'industry_id' => $data['industry_id'] ?? $person->industry_id,
            'industry' => $data['industry'] ?? $person->industry,
            'birthdate' => $data['birthdate'] ?? $person->birthdate,
            'address' => $data['address'] ?? $person->address,
            'status' => true,
            'is_client' => true,
        ];

        // La ubicacion depende del tipo de documento: peruana (ubigeo) o extranjera (pais/estado/ciudad).
        $isForeignLocation = IdentityDocumentType::isForeignLocation($personPayload['document_type_id']);

        $locationPayload = $isForeignLocation
            ? [
                'ubigeo' => null,
                'ubigeo_description' => $data['ubigeo_description'] ?? $person->ubigeo_description,
                'foreign_country_id' => $data['foreign_country_id'] ?? $person->foreign_country_id,
                'foreign_state' => $data['foreign_state'] ?? $person->foreign_state,
                'foreign_city' => $data['foreign_city'] ?? $person->foreign_city,
            ]
            : [
                'ubigeo' => $data['ubigeo'] ?? $person->ubigeo,
                'ubigeo_description' => $data['ubigeo_description'] ?? $person->ubigeo_description,
                'foreign_country_id' => null,
                'foreign_state' => null,
                'foreign_city' => null,
            ];

        $person->update(array_filter($personPayload, fn ($value) => $value !== null && $value !== ''));

        // Se aplica completa (incluye nulls) para poder pasar de extranjero a Peru y viceversa.
        $person->update($locationPayload);

        $this->markStepDone($negotiation, 'person');

        return response()->json([
            'success' => true,
            'message' => 'Actualizar los datos del cliente en la tabla people: cliente ya existente.',
            'person_id' => $person->id,
        ]);
    }    public function processUser(Request $request, $id)
    {        $negotiation = $this->negotiation($id);
        $person = $this->person($negotiation);

        $user = User::where('person_id', $person->id)->first();

        // Si el correo ya tiene una cuenta registrada, se reutiliza esa cuenta en lugar
        // de crear un duplicado: se vincula a esta persona y se asigna el rol Alumno.
        if (! $user && $person->email) {
            $user = User::where('email', trim($person->email))->first();

            if ($user) {
                $user->update(['person_id' => $person->id, 'status' => true]);
            }
        }

        $existed = (bool) $user;

        if (! $user) {
            $email = $person->email ?: 'alumno' . $person->id . '@sistema.local';

            $user = User::create([
                'name' => $person->full_name ?: $person->short_name,
                'email' => $email,
                'password' => Hash::make($person->number),
                'local_id' => Auth::user()->local_id ?? 1,
                'person_id' => $person->id,
                'status' => true,
            ]);
        }

        $user->assignRole('Alumno');

        $this->markStepDone($negotiation, 'user');

        return response()->json([
            'success' => true,
            'message' => $existed
                ? 'Usuario actualizado correctamente: el alumno ya tenia una cuenta, no se creo una nueva.'
                : 'Usuario creado correctamente.',
            'user_id' => $user->id,
            'updated' => (bool) $existed,
        ]);
    }

    public function processStudent(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);
        $person = $this->person($negotiation);

        $student = AcaStudent::where('person_id', $person->id)->first();
        $wasExisting = (bool) $student;

        if (! $student) {
            $student = AcaStudent::create([
                'student_code' => $person->number,
                'person_id' => $person->id,
            ]);
        } else {
            $student->update([
                'student_code' => $person->number ?? $student->student_code,
            ]);
        }

        $this->markStepDone($negotiation, 'student');

        return response()->json([
            'success' => true,
            'message' => $wasExisting
                ? 'Estudiante actualizado en aca_students: el alumno ya estaba registrado.'
                : 'Estudiante registrado en aca_students.',
            'student_id' => $student->id,
            'updated' => (bool) $wasExisting,
        ]);
    }

    public function processRegistrations(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);
        $person = $this->person($negotiation);
        $student = AcaStudent::where('person_id', $person->id)->first();

        if (! $student) {
            throw new \Exception('Primero debe registrarse el estudiante.');
        }

        $isInstallments = $negotiation->payment_type === 'installments';
        $nextPaymentDate = $this->nextPaymentDate($negotiation);
        $courseItems = $negotiation->items->where('item_type', 'course');

        if ($courseItems->isEmpty()) {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'message' => 'La negociacion no incluye cursos.',
            ]);
        }

        foreach ($courseItems as $item) {
            $amountPaid = (float) ($item->price ?? 0);
            $advancement = $isInstallments ? (float) ($negotiation->initial_amount ?? 0) : $amountPaid;

            AcaCapRegistration::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'course_id' => $item->item_id,
                ],
                [
                    'status' => true,
                    'date_start' => $isInstallments ? Carbon::now()->format('Y-m-d') : null,
                    'date_end' => $isInstallments ? $nextPaymentDate : null,
                    'unlimited' => ! $isInstallments,
                    'payment_installments' => $isInstallments,
                    'amount_paid' => $amountPaid,
                    'advancement' => $advancement,
                ]
            );
        }

        $this->markStepDone($negotiation, 'registrations');

        return response()->json([
            'success' => true,
            'message' => $isInstallments
                ? 'Matriculas registradas en cuotas con acceso hasta la siguiente cuota.'
                : 'Matriculas registradas con acceso ilimitado.',
        ]);
    }

    public function processSubscriptions(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);
        $person = $this->person($negotiation);
        $student = AcaStudent::where('person_id', $person->id)->first();

        if (! $student) {
            throw new \Exception('Primero debe registrarse el estudiante.');
        }

        $isInstallments = $negotiation->payment_type === 'installments';
        $nextPaymentDate = $this->nextPaymentDate($negotiation);
        $subscriptionItems = $negotiation->items->where('item_type', 'subscription');

        if ($subscriptionItems->isEmpty()) {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'message' => 'La negociacion no incluye suscripciones.',
            ]);
        }

        foreach ($subscriptionItems as $item) {
            $subscription = AcaSubscriptionType::find($item->item_id);

            if (! $subscription) {
                continue;
            }

            $dateStart = Carbon::today();

            if ($isInstallments) {
                $dateEnd = $nextPaymentDate ? Carbon::parse($nextPaymentDate) : null;
            } else {
                $dateEnd = $this->calculateDateEnd($subscription->period, $dateStart);
            }

            $amount = (float) ($item->price ?? 0);

            if (! $amount && $subscription->prices) {
                foreach (json_decode($subscription->prices, true) ?: [] as $price) {
                    if (($price['currency'] ?? null) === 'PEN') {
                        $amount = (float) ($price['amount'] ?? 0);
                    }
                }
            }

            AcaStudentSubscription::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subscription_id' => $item->item_id,
                ],
                [
                    'date_start' => $dateStart->format('Y-m-d'),
                    'date_end' => $dateEnd ? $dateEnd->format('Y-m-d') : null,
                    'status' => true,
                    'notes' => null,
                    'renewals' => false,
                    'registration_user_id' => Auth::id(),
                    'amount_paid' => $amount,
                ]
            );
        }

        $this->markStepDone($negotiation, 'subscriptions');

        return response()->json([
            'success' => true,
            'message' => $isInstallments
                ? 'Suscripciones registradas con fecha de fin hasta la siguiente cuota.'
                : 'Suscripciones registradas segun el plan contratado.',
        ]);
    }

    /**
     * Registra la venta en cuotas en el modulo de cuentas por cobrar, creando el cronograma
     * acordado y marcando la primera cuota (pago inicial) como pagada.
     */
    public function processInstallments(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);

        if ($negotiation->payment_type !== 'installments') {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'message' => 'No aplica para pago unico.',
            ]);
        }

        if ($negotiation->sale_id) {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'message' => 'La venta en cuotas ya fue registrada en cuentas por cobrar.',
            ]);
        }

        try {
            DB::transaction(function () use ($negotiation) {
                $person = $this->person($negotiation);
                $invoice = $negotiation->invoice;
                $isFactura = $invoice && $invoice->invoice_type === 'factura';
                $localId = Auth::user()->local_id ?? 1;
                $total = (float) $negotiation->total_price;

                // La primera cuota del cronograma acordado es el pago inicial que el cliente ya realizo.
                $schedule = $negotiation->schedule ?? [];
                $initialAmount = 0;

                if (is_array($schedule) && isset($schedule[0]['amount'])) {
                    $initialAmount = (float) $schedule[0]['amount'];
                }

                $sale = Sale::create([
                    'sale_date' => Carbon::now()->format('Y-m-d'),
                    'user_id' => Auth::id(),
                    'client_id' => $person->id,
                    'local_id' => $localId,
                    'total' => $total,
                    'advancement' => $initialAmount,
                    'total_discount' => 0,
                    'payments' => json_encode([]),
                    'petty_cash_id' => null,
                    'physical' => 1,
                    'invoice_type' => $isFactura ? 1 : 0,
                    'invoice_razon_social' => $invoice->razon_social ?? null,
                    'invoice_ruc' => $invoice->ruc ?? null,
                    'invoice_direccion' => $invoice->direccion ?? null,
                    'invoice_ubigeo' => $invoice->ubigeo ?? null,
                    'invoice_ubigeo_description' => trim(collect([$invoice->departamento, $invoice->provincia, $invoice->distrito])
                        ->filter()
                        ->implode(' - ')) ?: null,
                    'payment_installments' => true,
                ]);

                $this->createInstallmentSchedule($negotiation, $sale);

                $negotiation->update(['sale_id' => $sale->id]);
            });

            $this->markStepDone($negotiation, 'installments');

            return response()->json([
                'success' => true,
                'message' => 'Venta en cuotas registrada en cuentas por cobrar con su cronograma.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function processDocument(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);

        $isInstallments = $negotiation->payment_type === 'installments';

        if ($negotiation->sale_document_id) {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'message' => 'El comprobante ya fue generado.',
            ]);
        }

        try {
            DB::transaction(function () use ($negotiation, $isInstallments) {
                $isInstallments = $negotiation->payment_type === 'installments';
                $person = $this->person($negotiation);
                $invoice = $negotiation->invoice;
                $isFactura = $invoice && $invoice->invoice_type === 'factura';

                // Boleta a nombre de una tercera persona (el cliente la pidio al confirmar).
                $boletaTercero = $invoice && $invoice->invoice_type === 'boleta'
                    && ! empty($invoice->boleta_numero)
                    && ! empty($invoice->boleta_nombre);

                $clientOverride = $boletaTercero ? [
                    'client_type_doc' => $invoice->boleta_documento_tipo ?: '1',
                    'client_number' => $invoice->boleta_numero,
                    'client_rzn_social' => $invoice->boleta_nombre,
                ] : null;
                $localId = Auth::user()->local_id ?? 1;
                $total = (float) $negotiation->total_price;

                // El comprobante en cuotas se emite por el valor de la primera cuota (pago inicial),
                // no por la totalidad de la deuda.
                $firstInstallmentAmount = $isInstallments
                    ? $this->firstInstallmentAmount($negotiation)
                    : null;

                // Monto base del documento (primera cuota en cuotas, total en pago unico).
                $documentTotal = $firstInstallmentAmount ?? $total;

                $payments = [['type' => 1, 'reference' => null, 'amount' => $documentTotal]];

                // Para pago unico creamos la venta aqui; para cuotas la venta ya fue creada
                // en el paso "crear cuotas en cuentas por cobrar" (sale_id guardado en la negociacion).
                $sale = $isInstallments
                    ? Sale::find($negotiation->sale_id)
                    : null;

                // En cuotas la venta guarda la deuda total; mientras generamos el comprobante
                // la ajustamos temporalmente al monto de la primera cuota y restauramos al final.
                $saleOriginalTotal = null;

                if ($isInstallments && $sale) {
                    $saleOriginalTotal = $sale->total;
                    $sale->total = $documentTotal;
                    $sale->save();
                }

                if (! $isInstallments) {
                    $sale = Sale::create([
                        'sale_date' => Carbon::now()->format('Y-m-d'),
                        'user_id' => Auth::id(),
                        'client_id' => $person->id,
                        'local_id' => $localId,
                        'total' => $total,
                        'advancement' => $negotiation->initial_amount ?? $total,
                        'total_discount' => 0,
                        'payments' => json_encode($payments),
                        'petty_cash_id' => null,
                        'physical' => 1,
                        'invoice_type' => $isFactura ? 1 : 0,
                        'invoice_razon_social' => $invoice->razon_social ?? null,
                        'invoice_ruc' => $invoice->ruc ?? null,
                        'invoice_direccion' => $invoice->direccion ?? null,
                        'invoice_ubigeo' => $invoice->ubigeo ?? null,
                        'invoice_ubigeo_description' => trim(collect([$invoice->departamento, $invoice->provincia, $invoice->distrito])
                            ->filter()
                            ->implode(' - ')) ?: null,
                        'payment_installments' => false,
                    ]);
                }

                $items = $negotiation->items;
                $presentationMode = \App\Helpers\Invoice\DocumentPresentation::modeForCount($items->count());
                $formattedDescription = \App\Helpers\Invoice\DocumentPresentation::descriptionForItems($items);

                // En cuotas el comprobante de la 1ra cuota lleva el prefijo "1ra cuota - ".
                // Solo cuando el cronograma tiene mas de una cuota (con una sola seria un pago unico).
                if ($isInstallments && count(is_array($negotiation->schedule) ? $negotiation->schedule : []) > 1) {
                    $formattedDescription = \App\Helpers\Invoice\DocumentPresentation::installmentLabel(1).' - '.$formattedDescription;
                }

                if ($presentationMode === 'list' || $presentationMode === 'summary') {
                    $firstItem = $items->first();
                    $entity = $firstItem->entityClass();
                    $product = $entity ? $entity::find($firstItem->item_id) : null;

                    if (! $entity || ! $product) {
                        throw new \Exception('No se encontro el producto ('.($entity ?: 'clase no resuelta').') de la clase '.($firstItem->entity_name_product ?? 'desconocida').' en la negociacion.');
                    }

                    $product->formatted_description = $formattedDescription;

                    SaleProduct::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'product' => json_encode($product),
                        'saleProduct' => json_encode($product),
                        'price' => $documentTotal,
                        'discount' => 0,
                        'quantity' => 1,
                        'total' => $documentTotal,
                        'entity_name_product' => $entity,
                    ]);
                } else {
                    // En cuotas el monto del documento se reparte entre los items para que sume la primera cuota.
                    $totalItems = $items->count();
                    $basePrice = $documentTotal / $totalItems;
                    $addedProducts = 0;

                    foreach ($items as $item) {
                        $entity = $item->entityClass();
                        $product = $entity ? $entity::find($item->item_id) : null;

                        if (! $entity || ! $product) {
                            continue;
                        }

                        $product->formatted_description = $formattedDescription;

                        // En cuotas el monto se reparte; el ultimo item absorbe el residuo para sumar exacto.
                        $price = round($basePrice, 2);

                        if ($item->is($items->last())) {
                            $assigned = $basePrice * ($totalItems - 1);
                            $price = round($documentTotal - $assigned, 2);
                        }

                        SaleProduct::create([
                            'sale_id' => $sale->id,
                            'product_id' => $product->id,
                            'product' => json_encode($product),
                            'saleProduct' => json_encode($product),
                            'price' => $price,
                            'discount' => 0,
                            'quantity' => 1,
                            'total' => $price,
                            'entity_name_product' => $entity,
                        ]);

                        $addedProducts++;
                    }

                    if ($addedProducts === 0) {
                        throw new \Exception('Ninguno de los items de la negociacion pudo resolverse a un producto valido para generar el comprobante.');
                    }
                }

                $pedido = [
                    'venta' => [
                        'id' => $sale->id,
                        'nota_sale_id' => $sale->id,
                    ],
                    'local' => $localId,
                    'serie' => null,
                    'documenttypeId' => $isFactura ? 1 : 2,
                    'userId' => Auth::id(),
                    'enline' => true,
                ];

                // La boleta se emite con los datos del tercero indicado por el cliente.
                if ($clientOverride) {
                    $pedido['client_override'] = $clientOverride;
                }

                $internalRequest = Request::create(
                    '/commercial/negotiations/document/internal',
                    'POST',
                    ['pedido' => $pedido]
                );

                $response = app(AcaSaleDocumentController::class)->generateBoleta($internalRequest);
                $data = json_decode($response->getContent(), true);

                if (! ($data['success'] ?? false)) {
                    throw new \Exception($data['message'] ?? 'Error al generar el comprobante.');
                }

                $negotiation->update([
                    'sale_id' => $sale->id,
                    'sale_document_id' => $data['document']['id'],
                ]);

                // En cuotas vinculamos el comprobante a la primera cuota (ya pagada).
                if ($isInstallments) {
                    $firstSchedule = SalePaymentSchedule::where('sale_id', $sale->id)
                        ->orderBy('installment_number')
                        ->first();

                    if ($firstSchedule) {
                        $firstSchedule->update([
                            'document_id' => $data['document']['id'],
                        ]);

                        // Vincular tambien el documento a la cuota (schedule_id) para
                        // que aparezca en "Ver Documentos de venta" del modulo de cuentas por cobrar.
                        SaleDocument::where('id', $data['document']['id'])
                            ->update(['schedule_id' => $firstSchedule->id]);
                    }

                    // Restauramos el total real de la venta en cuotas luego de emitir el comprobante.
                    $sale->total = $saleOriginalTotal;
                    $sale->save();
                }

                // La descripcion del detalle del comprobante queda ya formateada.
                foreach (SaleDocumentItem::where('document_id', $data['document']['id'])->get() as $documentItem) {
                    $documentItem->update([
                        'decription_product' => $formattedDescription,
                    ]);
                }

                // En paquetes el detalle del comprobante es una sola linea;
                // vinculamos el comprobante a todas las matriculas de cursos.
                if (in_array($presentationMode, ['list', 'summary'], true)) {
                    $student = $person ? AcaStudent::where('person_id', $person->id)->first() : null;

                    if ($student) {
                        foreach ($negotiation->items as $item) {
                            if ($item->entityClass() === AcaCourse::class) {
                                AcaCapRegistration::where('student_id', $student->id)
                                    ->where('course_id', $item->item_id)
                                    ->update(['document_id' => $data['document']['id']]);
                            }
                        }
                    }
                }
            });

            $this->markStepDone($negotiation, 'document');

            return response()->json([
                'success' => true,
                'message' => $isInstallments
                    ? 'Venta en cuotas y comprobante de venta generados correctamente.'
                    : 'Comprobante de venta generado correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('CommercialNegotiation::processDocument error para negociacion '.$id, [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function processEmail(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);

        if (! $negotiation->sale_document_id) {
            return response()->json([
                'success' => false,
                'message' => 'Primero debe generarse el comprobante de venta.',
            ], 422);
        }

        $person = $this->person($negotiation);

        if (! $person->email) {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'message' => 'El cliente no tiene correo electronico registrado.',
            ]);
        }

        $document = SaleDocument::find($negotiation->sale_document_id);

        $user = User::where('person_id', $person->id)->first();

        $credentials = $user ? [
            'username' => $user->email,
            'password' => $person->number,
        ] : null;

        try {
            $dataFile = app(AcaSaleDocumentController::class)->generateBoletaPDF($document->id);

            Mail::to(trim($person->email))->send(
                new CommercialNegotiationDocumentMail($negotiation, $document, $dataFile, $credentials)
            );

            $negotiation->update(['email_sent_at' => now()]);

            $this->markStepDone($negotiation, 'email');

            return response()->json([
                'success' => true,
                'message' => 'Correo con los detalles del acuerdo, su comprobante y credenciales de acceso enviado al cliente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar el correo: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Ultimo paso de trabajo del proceso: envia todos los datos de la negociacion a n8n
     * a traves de la integracion N8N_Global (endpoint n8n_post_negociacion).
     * Si falla, la negociacion no se marca como completada y el paso puede reintentarse.
     */
    public function processWebhook(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);

        try {
            $person = $this->person($negotiation);

            $payload = $this->webhookPayload($negotiation, $person);

            // Misma ejecucion por nombre de endpoint que usa el modulo Onlineshop.
            $response = app(IntegrationhubController::class)
                ->runEndpoint('n8n_post_negociacion', [], ['body' => $payload], true);

            $result = $response->getData(true);
            $statusCode = (int) ($result['status_code'] ?? 0);

            if ($response->getStatusCode() !== 200 || $statusCode < 200 || $statusCode >= 300) {
                $externalResponse = $result['response'] ?? null;
                $externalMessage = is_array($externalResponse)
                    ? ($externalResponse['message'] ?? $externalResponse['error'] ?? null)
                    : (is_string($externalResponse) ? $externalResponse : null);

                throw new \Exception(
                    $externalMessage
                        ? "n8n respondio con estado {$statusCode}: ".(is_string($externalMessage) ? $externalMessage : json_encode($externalMessage))
                        : ($result['message'] ?? "La integracion respondio con estado {$statusCode}.")
                );
            }

            $this->markStepDone($negotiation, 'webhook');

            return response()->json([
                'success' => true,
                'message' => 'Datos de la negociacion enviados a n8n correctamente.',
            ]);
        } catch (\Throwable $e) {
            IntegrationError::create([
                'message' => 'CommercialNegotiation::processWebhook (negociacion '.$id.'): '.$e->getMessage(),
                'source' => 'CommercialNegotiation::processWebhook',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudieron enviar los datos a n8n: '.$e->getMessage(),
            ], 422);
        }
    }

    public function complete(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);

        $negotiation->update([
            'status' => 'completada',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'rejected_reason' => null,
        ]);

        $this->markStepDone($negotiation, 'complete');

        return response()->json([
            'success' => true,
            'message' => 'Proceso completado: la negociacion y su comprobante quedaron registrados.',
        ]);
    }

    private function negotiation($id): CommercialNegotiation
    {
        $negotiation = CommercialNegotiation::with(['items', 'client', 'invoice'])
            ->findOrFail($id);

        if (! in_array($negotiation->status, ['confirmada', 'aprobada'])) {
            abort(422, 'Solo se pueden procesar negociaciones confirmadas.');
        }

        return $negotiation;
    }

    private function person(CommercialNegotiation $negotiation): Person
    {
        if (! $negotiation->client_id) {
            throw new \Exception('La negociacion no tiene un cliente registrado.');
        }

        $person = Person::find($negotiation->client_id);

        if (! $person) {
            throw new \Exception('El cliente registrado no existe en la tabla people.');
        }

        return $person;
    }

    /**
     * Arma el JSON que se envia a n8n con la negociacion, el cliente, su usuario,
     * el comprobante y los items acordados.
     *
     * @return array<string, mixed>
     */
    private function webhookPayload(CommercialNegotiation $negotiation, Person $person): array
    {
        $negotiation->loadMissing(['items', 'creator', 'verifier', 'invoice']);

        $user = User::where('person_id', $person->id)->first();
        $student = AcaStudent::where('person_id', $person->id)->first();
        $invoice = $negotiation->invoice;
        $document = $negotiation->sale_document_id ? SaleDocument::find($negotiation->sale_document_id) : null;

        return [
            'evento' => 'negociacion_aprobada',
            'fecha' => Carbon::now()->toIso8601String(),
            'origen' => 'commercial_negociations',
            'negociacion' => [
                'id' => $negotiation->id,
                'titulo' => $negotiation->title,
                'descripcion' => $negotiation->body,
                'moneda' => $negotiation->currency,
                'total' => (float) $negotiation->total_price,
                'tipo_pago' => $negotiation->payment_type,
                'monto_inicial' => $negotiation->initial_amount !== null ? (float) $negotiation->initial_amount : null,
                'cuotas' => $negotiation->schedule ?? [],
                'estado' => $negotiation->status,
                'canal_contacto' => $negotiation->contact_channel_label ?: $negotiation->contact_channel,
                'detalle_contacto' => $negotiation->contact_detail,
                'metodo_pago' => $negotiation->payment_method,
                'sale_id' => $negotiation->sale_id,
                'sale_document_id' => $negotiation->sale_document_id,
                'creado_por' => $negotiation->creator?->name,
                'aprobado_por' => $negotiation->verifier?->name,
                'aprobado_en' => $negotiation->verified_at?->toIso8601String(),
            ],
            'persona' => [
                'id' => $person->id,
                'nombre_completo' => $person->full_name ?: $person->short_name,
                'nombres' => $person->names,
                'apellido_paterno' => $person->father_lastname,
                'apellido_materno' => $person->mother_lastname,
                'tipo_documento' => $person->document_type_id,
                'numero_documento' => $person->number,
                'email' => $person->email,
                'telefono' => $person->telephone,
                'genero' => $person->gender,
                'ocupacion' => $person->ocupacion,
                'empresa' => $person->company,
                'industria' => $person->industry,
                'industria_id' => $person->industry_id,
                'fecha_nacimiento' => $person->birthdate,
                'direccion' => $person->address,
                'ubigeo' => $person->ubigeo,
                'ciudad' => $person->ubigeo_description,
                'pais_extranjero_id' => $person->foreign_country_id,
                'departamento_estado' => $person->foreign_state,
                'ciudad_extranjera' => $person->foreign_city,
            ],
            'usuario' => [
                'id' => $user?->id,
                'email' => $user?->email,
            ],
            'estudiante' => [
                'id' => $student?->id,
                'codigo' => $student?->student_code,
            ],
            'comprobante' => [
                'tipo' => $invoice?->invoice_type,
                'ruc' => $invoice?->ruc,
                'razon_social' => $invoice?->razon_social,
                'direccion' => $invoice?->direccion,
                'distrito' => $invoice?->distrito,
                'provincia' => $invoice?->provincia,
                'departamento' => $invoice?->departamento,
                'serie' => $document?->invoice_serie,
                'correlativo' => $document?->invoice_correlative,
                'numero' => $document?->invoice_document_name,
                'estado' => $document?->invoice_status,
                'pdf' => $this->publicInvoiceUrl($document?->invoice_pdf),
                'total' => $document?->overall_total !== null ? (float) $document->overall_total : null,
            ],
            'items' => $negotiation->items->map(fn ($item) => [
                'tipo' => $item->item_type,
                'titulo' => $item->title,
                'producto_id' => $item->item_id,
                'precio' => (float) $item->price,
                'entidad' => $item->entity_name_product,
            ])->values()->toArray(),
        ];
    }

    /**
     * Convierte la ruta absoluta con la que se guarda el PDF del comprobante
     * (public/storage/invoice/20613668323-03-B001-247.pdf) en su URL publica
     * (.../storage/invoice/20613668323-03-B001-247.pdf), que es la que un
     * sistema externo como n8n puede descargar.
     */
    private function publicInvoiceUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        // Si ya viene una URL (documentos antiguos) se devuelve tal cual.
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $file = basename(str_replace('\\', '/', $path));

        return rtrim(config('app.url') ?: url('/'), '/').'/storage/invoice/'.$file;
    }

    private function nextPaymentDate(CommercialNegotiation $negotiation): ?string
    {
        $schedule = $negotiation->schedule ?? [];

        if (! is_array($schedule) || count($schedule) === 0) {
            return null;
        }

        return $schedule[0]['due_date'] ?? null;
    }

    /**
     * Retorna el monto de la primera cuota del cronograma acordado en la negociacion.
     */
    private function firstInstallmentAmount(CommercialNegotiation $negotiation): ?float
    {
        $schedule = $negotiation->schedule ?? [];

        if (is_array($schedule) && isset($schedule[0]['amount'])) {
            return (float) $schedule[0]['amount'];
        }

        return null;
    }

    /**
     * Crea el cronograma de cuotas en cuentas por cobrar a partir del cronograma acordado
     * en la negociacion. La primera cuota (pago inicial) queda registrada como pagada.
     */
    private function createInstallmentSchedule(CommercialNegotiation $negotiation, Sale $sale): void
    {
        $schedule = $negotiation->schedule ?? [];

        if (! is_array($schedule) || count($schedule) === 0) {
            throw new \Exception('La negociacion no tiene un cronograma de cuotas registrado.');
        }

        foreach ($schedule as $index => $row) {
            $amount = (int) round((float) ($row['amount'] ?? 0));
            $isFirst = $index === 0;

            SalePaymentSchedule::create([
                'sale_id' => $sale->id,
                'installment_number' => $index + 1,
                'payment_date' => $row['due_date'] ?? Carbon::now()->format('Y-m-d'),
                'amount_to_pay' => $amount,
                // La primera cuota (pago inicial) ya fue pagada por el cliente.
                'amount_paid' => $isFirst ? $amount : 0,
                'remaining_amount' => $isFirst ? 0 : $amount,
                'document_id' => null,
                'is_paid' => $isFirst,
            ]);
        }
    }

    private function calculateDateEnd(?string $period, Carbon $dateStart): ?Carbon
    {
        return match ($period) {
            'Mensual' => $dateStart->copy()->addMonth(),
            'Trimestral' => $dateStart->copy()->addMonths(3),
            'Semestral' => $dateStart->copy()->addMonths(6),
            'Anual' => $dateStart->copy()->addYear(),
            'Semanal' => $dateStart->copy()->addWeek(),
            'Diario' => $dateStart->copy()->addDay(),
            'Prueba gratuita', 'Única Vez' => null,
            default => null,
        };
    }

    private function statuses(): array
    {
        return [
            ['value' => 'pendiente', 'label' => 'Pendiente', 'color' => 'secondary'],
            ['value' => 'confirmada', 'label' => 'Confirmada', 'color' => 'primary'],
            ['value' => 'aprobada', 'label' => 'Aprobada', 'color' => 'success'],
            ['value' => 'completada', 'label' => 'Proceso completado', 'color' => 'success'],
            ['value' => 'rechazada', 'label' => 'Rechazada', 'color' => 'danger'],
            ['value' => 'cancelada', 'label' => 'Cancelada', 'color' => 'dark'],
        ];
    }

    private function paymentMethods(): array
    {
        return [
            ['value' => 'yape', 'label' => 'Yape'],
            ['value' => 'mercadopago', 'label' => 'Mercado Pago'],
            ['value' => 'transferencia', 'label' => 'Transferencia bancaria'],
            ['value' => 'enlace', 'label' => 'Enlace de pago'],
        ];
    }
}
