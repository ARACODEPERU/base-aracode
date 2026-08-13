<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\IdentityDocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Commercial\Entities\CommercialNegotiation;

class CommercialNegotiationController extends Controller
{
    public function index()
    {
        $negotiations = CommercialNegotiation::with(['items', 'client'])
            ->when(request()->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('contact_detail', 'like', "%{$search}%")
                        ->orWhereJsonContains('client_data->number', $search)
                        ->orWhereJsonContains('client_data->full_name', $search)
                        ->orWhereHas('client', function ($client) use ($search) {
                            $client->where('full_name', 'like', "%{$search}%")
                                ->orWhere('number', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(request()->input('per_page', 20))
            ->withQueryString();

        return Inertia::render('Commercial::Negotiations/List', [
            'negotiations' => $negotiations,
            'filters' => request()->only('search', 'per_page'),
            'statuses' => $this->statuses(),
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Commercial::Negotiations/Create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        try {
            DB::beginTransaction();

            $negotiation = CommercialNegotiation::create(array_merge($this->payload($data), [
                'token' => (string) Str::uuid(),
                'status' => 'pendiente',
                'created_by' => auth()->id(),
            ]));

            $this->syncItems($negotiation, $data['items']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return redirect()->back()->with('success', 'Negociacion registrada correctamente');
    }

    public function edit($id)
    {
        return Inertia::render('Commercial::Negotiations/Edit', array_merge($this->formData(), [
            'negotiation' => CommercialNegotiation::with('items')->findOrFail($id),
        ]));
    }

    public function update(Request $request, $id)
    {
        $negotiation = CommercialNegotiation::findOrFail($id);
        $data = $this->validatedData($request);

        try {
            DB::beginTransaction();

            $negotiation->update($this->payload($data));
            $this->syncItems($negotiation, $data['items']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return redirect()->back()->with('success', 'Negociacion actualizada correctamente');
    }

    public function destroy($id)
    {
        $negotiation = CommercialNegotiation::findOrFail($id);

        if ($negotiation->status === 'confirmada') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una negociacion que el alumno ya confirmo.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            if ($negotiation->voucher_path) {
                Storage::disk('public')->delete($negotiation->voucher_path);
            }

            $negotiation->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Negociacion eliminada correctamente',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show($id)
    {
        return Inertia::render('Commercial::Negotiations/Show', [
            'negotiation' => CommercialNegotiation::with([
                'items',
                'client',
                'creator',
                'verifier',
                'invoice',
                'saleDocument' => fn ($query) => $query->select([
                    'id',
                    'sale_id',
                    'invoice_type_doc',
                    'invoice_serie',
                    'invoice_correlative',
                    'invoice_status',
                    'invoice_response_code',
                    'invoice_response_description',
                    'invoice_notes',
                    'invoice_pdf',
                    'invoice_xml',
                    'invoice_cdr',
                    'invoice_document_name',
                    'invoice_type_currency',
                    'invoice_broadcast_date',
                    'invoice_send_date',
                    'invoice_mto_imp_sale',
                    'overall_total',
                    'created_at',
                ]),
            ])->findOrFail($id),
            'statuses' => $this->statuses(),
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function approve($id)
    {
        $negotiation = CommercialNegotiation::findOrFail($id);

        if ($negotiation->status !== 'confirmada') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden aprobar negociaciones confirmadas.',
            ], 422);
        }

        $negotiation->update([
            'status' => 'aprobada',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'rejected_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Negociacion aprobada correctamente.',
        ]);
    }

    public function reject(Request $request, $id)
    {
        $negotiation = CommercialNegotiation::findOrFail($id);

        if ($negotiation->status !== 'confirmada') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden rechazar negociaciones confirmadas.',
            ], 422);
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $negotiation->update([
            'status' => 'rechazada',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'rejected_reason' => $request->input('reason'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Negociacion rechazada. El cliente podra reintentar con un nuevo voucher.',
        ]);
    }

    public function cancel($id)
    {
        $negotiation = CommercialNegotiation::findOrFail($id);

        $negotiation->update([
            'status' => 'cancelada',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Negociacion cancelada correctamente.',
        ]);
    }

    private function formData(): array
    {
        return [
            'courses' => AcaCourse::where('status', 1)
                ->orderBy('description')
                ->get(['id', 'description', 'price']),
            'subscriptions' => AcaSubscriptionType::where('status', 1)
                ->orderBy('title')
                ->get(['id', 'title', 'prices']),
            'identityDocumentTypes' => IdentityDocumentType::orderBy('id')->get(),
            'currencyTypes' => DB::table('sunat_currency_types')
                ->where('active', true)
                ->orderBy('id')
                ->get(['id', 'symbol', 'description']),
            'paymentMethods' => $this->paymentMethods(),
            'contactChannels' => $this->contactChannels(),
        ];
    }

    private function validatedData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:5'],
            'payment_type' => ['required', Rule::in(['single', 'installments'])],
            'initial_amount' => ['nullable', 'numeric', 'min:0'],
            'schedule' => ['nullable', 'array'],
            'schedule.*.due_date' => ['required', 'date'],
            'schedule.*.amount' => ['required', 'numeric', 'min:0'],
            'single_payment_days' => ['nullable', 'integer', 'min:1'],
            'contact_channel' => ['nullable', 'string', 'max:40'],
            'contact_detail' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', Rule::in(['yape', 'mercadopago', 'transferencia', 'enlace'])],
            'payment_link' => ['nullable', 'url', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', Rule::in(['course', 'subscription'])],
            'items.*.item_id' => ['nullable', 'integer'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ])->after(function ($validator) {
            $data = $validator->getData();

            if (($data['payment_type'] ?? null) !== 'installments') {
                return;
            }

            $schedule = $data['schedule'] ?? [];

            if (!is_array($schedule) || count($schedule) === 0) {
                $validator->errors()->add('schedule', 'Debe registrar al menos una cuota.');

                return;
            }

            $sum = array_sum(array_map(fn ($row) => (float) ($row['amount'] ?? 0), $schedule));
            $total = (float) ($data['total_price'] ?? 0);

            if (abs($sum - $total) > 0.01) {
                $validator->errors()->add('schedule', 'La suma de las cuotas debe ser igual al monto total acordado.');
            }
        });

        return $validator->validate();
    }

    private function payload(array $data): array
    {
        return [
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'total_price' => $data['total_price'],
            'currency' => $data['currency'] ?? 'PEN',
            'payment_type' => $data['payment_type'],
            'initial_amount' => $data['initial_amount'] ?? null,
            'schedule' => $data['schedule'] ?? null,
            'single_payment_days' => $data['single_payment_days'] ?? null,
            'contact_channel' => $data['contact_channel'] ?? null,
            'contact_detail' => $data['contact_detail'] ?? null,
            'payment_method' => $data['payment_method'],
            'payment_link' => $data['payment_link'] ?? null,
        ];
    }

    private function syncItems(CommercialNegotiation $negotiation, array $items): void
    {
        $negotiation->items()->delete();

        foreach ($items as $item) {
            $negotiation->items()->create([
                'item_type' => $item['item_type'],
                'item_id' => $item['item_id'] ?? null,
                'title' => $item['title'],
                'price' => $item['price'] ?? null,
            ]);
        }
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

    private function contactChannels(): array
    {
        return [
            ['value' => 'telefono', 'label' => 'Telefono'],
            ['value' => 'whatsapp', 'label' => 'WhatsApp'],
            ['value' => 'instagram', 'label' => 'Instagram'],
            ['value' => 'facebook_messenger', 'label' => 'Facebook Messenger'],
            ['value' => 'facebook', 'label' => 'Facebook'],
            ['value' => 'otro', 'label' => 'Otro'],
        ];
    }
}
