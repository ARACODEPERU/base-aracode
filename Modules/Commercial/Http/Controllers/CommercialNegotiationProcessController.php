<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\Controller;
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
        ]);
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
            'profession' => $data['profession'] ?? $person->profession,
            'status' => true,
            'is_client' => true,
        ];

        $person->update(array_filter($personPayload, fn ($value) => $value !== null && $value !== ''));

        return response()->json([
            'success' => true,
            'message' => 'Datos del cliente guardados en la tabla people.',
            'person_id' => $person->id,
        ]);
    }

    public function processUser(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);
        $person = $this->person($negotiation);

        $user = User::where('person_id', $person->id)->first();

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

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente.',
            'user_id' => $user->id,
        ]);
    }

    public function processStudent(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);
        $person = $this->person($negotiation);

        $student = AcaStudent::where('person_id', $person->id)->first();

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

        return response()->json([
            'success' => true,
            'message' => 'Estudiante registrado en aca_students.',
            'student_id' => $student->id,
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

        return response()->json([
            'success' => true,
            'message' => $isInstallments
                ? 'Suscripciones registradas con fecha de fin hasta la siguiente cuota.'
                : 'Suscripciones registradas segun el plan contratado.',
        ]);
    }

    public function processDocument(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);

        if ($negotiation->payment_type !== 'single') {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'message' => 'Pago en cuotas: el comprobante se generara en el proceso de cuotas.',
            ]);
        }

        if ($negotiation->sale_document_id) {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'message' => 'El comprobante ya fue generado.',
            ]);
        }

        try {
            DB::transaction(function () use ($negotiation) {
                $person = $this->person($negotiation);
                $invoice = $negotiation->invoice;
                $isFactura = $invoice && $invoice->invoice_type === 'factura';
                $localId = Auth::user()->local_id ?? 1;
                $total = (float) $negotiation->total_price;

                $payments = [['type' => 1, 'reference' => null, 'amount' => $total]];

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
                ]);

                $items = $negotiation->items;
                $presentationMode = \App\Helpers\Invoice\DocumentPresentation::modeForCount($items->count());
                $formattedDescription = \App\Helpers\Invoice\DocumentPresentation::descriptionForItems($items);

                if ($presentationMode === 'list' || $presentationMode === 'summary') {
                    $firstItem = $items->first();
                    $entity = $firstItem->item_type === 'subscription'
                        ? AcaSubscriptionType::class
                        : AcaCourse::class;
                    $product = $entity::find($firstItem->item_id);

                    if (! $product) {
                        throw new \Exception('No se encontro el producto de la negociacion.');
                    }

                    $product->formatted_description = $formattedDescription;

                    SaleProduct::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'product' => json_encode($product),
                        'saleProduct' => json_encode($product),
                        'price' => $total,
                        'discount' => 0,
                        'quantity' => 1,
                        'total' => $total,
                        'entity_name_product' => $entity,
                    ]);
                } else {
                    foreach ($items as $item) {
                        $entity = $item->item_type === 'subscription'
                            ? AcaSubscriptionType::class
                            : AcaCourse::class;
                        $product = $entity::find($item->item_id);

                        if (! $product) {
                            continue;
                        }

                        $product->formatted_description = $formattedDescription;

                        $price = $items->count() === 1
                            ? $total
                            : (float) ($item->price ?? $product->price ?? 0);

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

                // La descripcion del detalle del comprobante queda ya formateada.
                foreach (SaleDocumentItem::where('document_id', $data['document']['id'])->get() as $documentItem) {
                    $documentItem->update([
                        'decription_product' => $formattedDescription,
                    ]);
                }

                // En paquetes el detalle del comprobante es una sola linea;
                // vinculamos el comprobante a todas las matriculas de cursos.
                if (in_array($presentationMode, ['list', 'summary'], true)) {
                    $student = AcaStudent::where('person_id', $person->id)->first();

                    foreach ($negotiation->items as $item) {
                        if ($item->item_type === 'course') {
                            AcaCapRegistration::where('student_id', $student->id)
                                ->where('course_id', $item->item_id)
                                ->update(['document_id' => $data['document']['id']]);
                        }
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Comprobante de venta generado correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function processEmail(Request $request, $id)
    {
        $negotiation = $this->negotiation($id);

        if ($negotiation->payment_type !== 'single') {
            return response()->json([
                'success' => true,
                'skipped' => true,
                'message' => 'Pago en cuotas: el correo se enviara en el proceso de cuotas.',
            ]);
        }

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

        try {
            $dataFile = app(AcaSaleDocumentController::class)->generateBoletaPDF($document->id);

            Mail::to(trim($person->email))->send(
                new CommercialNegotiationDocumentMail($negotiation, $document, $dataFile)
            );

            return response()->json([
                'success' => true,
                'message' => 'Correo con los detalles del acuerdo y su comprobante enviado al cliente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar el correo: ' . $e->getMessage(),
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

    private function nextPaymentDate(CommercialNegotiation $negotiation): ?string
    {
        $schedule = $negotiation->schedule ?? [];

        if (! is_array($schedule) || count($schedule) === 0) {
            return null;
        }

        return $schedule[0]['due_date'] ?? null;
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
