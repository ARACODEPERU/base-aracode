<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\CommercialNegotiationConfirmedMail;
use App\Models\BankAccount;
use App\Models\IdentityDocumentType;
use App\Models\Parameter;
use App\Models\PaymentMethod;
use App\Models\Person;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\Commercial\Entities\CommercialNegotiation;
use Modules\Commercial\Entities\CommercialNegotiationInvoice;

class CommercialNegotiationPublicController extends Controller
{
    public function show($token)
    {
        $negotiation = CommercialNegotiation::with(['items', 'companyBilleteras.billetera'])
            ->where('token', $token)
            ->first();

        abort_unless($negotiation, 404);

        // Si el enlace vencio y aun estaba pendiente, se marca como "No hubo respuesta".
        if ($negotiation->status === 'pendiente'
            && $negotiation->link_expires_at
            && $negotiation->link_expires_at->isPast()) {
            $negotiation->update(['status' => 'sin_respuesta']);
            $negotiation->refresh();
        }

        return Inertia::render('Commercial::Negotiations/Public/Show', [
            'negotiation' => $this->negotiationPayload($negotiation),
            'identityDocumentTypes' => IdentityDocumentType::orderBy('id')->get(),
            'paymentMethodCatalog' => PaymentMethod::with('bankAccount.bank')->get(),
            'bankAccounts' => BankAccount::with('bank')->where('status', 1)->get(),
        ]);
    }

    public function store(Request $request, $token)
    {
        $negotiation = CommercialNegotiation::where('token', $token)->firstOrFail();

        // Enlace vencido: se marca como "No hubo respuesta" y se bloquea el envio.
        if ($negotiation->status === 'pendiente'
            && $negotiation->link_expires_at
            && $negotiation->link_expires_at->isPast()) {
            $negotiation->update(['status' => 'sin_respuesta']);
        }

        if (in_array($negotiation->status, ['aprobada', 'cancelada', 'sin_respuesta'])) {
            return response()->json([
                'success' => false,
                'message' => 'Esta negociacion ya no acepta envios.',
            ], 422);
        }

        $data = $request->validate([
            'accepted' => ['required', 'accepted'],
            'invoice_type' => ['required', Rule::in(['boleta', 'factura'])],
            'document_type_id' => ['required', 'string', 'exists:identity_document_type,id'],
            'number' => ['required', 'string', 'max:20'],
            'full_name' => ['required_if:document_type_id,6', 'nullable', 'string', 'max:255'],
            'names' => ['required_unless:document_type_id,6', 'nullable', 'string', 'max:255'],
            'father_lastname' => ['required_unless:document_type_id,6', 'nullable', 'string', 'max:255'],
            'mother_lastname' => ['required_unless:document_type_id,6', 'nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:M,F'],
            'email' => ['nullable', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'ocupacion' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'ruc' => ['required_if:invoice_type,factura', 'nullable', 'string', 'size:11'],
            'invoice_razon_social' => ['required_if:invoice_type,factura', 'nullable', 'string', 'max:255'],
            'invoice_direccion' => ['nullable', 'string', 'max:255'],
            'invoice_estado' => ['required_if:invoice_type,factura', 'nullable', 'string', Rule::in(['ACTIVO'])],
            'invoice_condicion' => ['required_if:invoice_type,factura', 'nullable', 'string', Rule::in(['HABIDO'])],
            'invoice_ubigeo' => ['nullable', 'string', 'max:10'],
            'invoice_distrito' => ['nullable', 'string', 'max:255'],
            'invoice_provincia' => ['nullable', 'string', 'max:255'],
            'invoice_departamento' => ['nullable', 'string', 'max:255'],
            'voucher' => [Rule::requiredIf($negotiation->payment_method !== 'mercadopago'), 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $isRuc = (string) $data['document_type_id'] === '6';

        $fullName = $isRuc
            ? trim((string) ($data['full_name'] ?? ''))
            : trim(($data['father_lastname'] ?? '') . ' ' . ($data['mother_lastname'] ?? '') . ' ' . ($data['names'] ?? ''));

        $person = Person::where('number', trim($data['number']))
            ->where('document_type_id', $data['document_type_id'])
            ->first();

        $personPayload = [
            'short_name' => $data['names'] ?? ($fullName ?: $data['full_name']),
            'full_name' => $fullName ?: ($data['full_name'] ?? null),
            'document_type_id' => $data['document_type_id'],
            'number' => trim($data['number']),
            'names' => $data['names'] ?? null,
            'father_lastname' => $data['father_lastname'] ?? null,
            'mother_lastname' => $data['mother_lastname'] ?? null,
            'gender' => $data['gender'] ?? null,
            'email' => $data['email'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'ocupacion' => $data['ocupacion'] ?? null,
            'profession' => $data['profession'] ?? null,
            'status' => true,
        ];

        try {
            DB::beginTransaction();

            if ($person) {
                $person->update(array_filter($personPayload, fn ($value) => $value !== null && $value !== ''));
                $clientId = $person->id;
            } else {
                $person = Person::create(array_merge($personPayload, [
                    'is_client' => true,
                    'is_provider' => false,
                ]));
                $clientId = $person->id;
            }

            $voucherPath = null;
            if ($request->hasFile('voucher')) {
                $voucherPath = $request->file('voucher')->store('negotiations/vouchers', 'public');

                if ($negotiation->voucher_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($negotiation->voucher_path);
                }
            }

            $negotiation->update([
                'status' => 'confirmada',
                'client_id' => $clientId,
                'client_data' => array_merge($personPayload, [
                    'full_name' => $fullName ?: ($data['full_name'] ?? null),
                ]),
                'voucher_path' => $voucherPath ?: $negotiation->voucher_path,
                'rejected_reason' => null,
                'verified_by' => null,
                'verified_at' => null,
            ]);

            CommercialNegotiationInvoice::updateOrCreate(
                ['negotiation_id' => $negotiation->id],
                [
                    'invoice_type' => $data['invoice_type'],
                    'ruc' => $data['invoice_type'] === 'factura' ? trim($data['ruc']) : null,
                    'razon_social' => $data['invoice_razon_social'] ?? null,
                    'direccion' => $data['invoice_direccion'] ?? null,
                    'estado' => $data['invoice_estado'] ?? null,
                    'condicion' => $data['invoice_condicion'] ?? null,
                    'ubigeo' => $data['invoice_ubigeo'] ?? null,
                    'distrito' => $data['invoice_distrito'] ?? null,
                    'provincia' => $data['invoice_provincia'] ?? null,
                    'departamento' => $data['invoice_departamento'] ?? null,
                ]
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }

        $this->notifyAsesor($negotiation, $person);

        return redirect()->back()->with('success', 'Tu acuerdo fue enviado correctamente. El asesor revisara la confirmacion.');
    }

    public function searchPerson(Request $request, $token)
    {
        CommercialNegotiation::where('token', $token)->firstOrFail();

        $request->validate([
            'document_type_id' => 'nullable|string',
            'number' => 'nullable|string',
        ]);

        $person = Person::query()
            ->when($request->input('number'), function ($query, $number) use ($request) {
                $query->where('number', $number)
                    ->when($request->input('document_type_id'), function ($q, $documentTypeId) {
                        $q->where('document_type_id', $documentTypeId);
                    });
            })
            ->first();

        return response()->json([
            'status' => (bool) $person,
            'person' => $person,
            'message' => $person ? 'Cliente encontrado en la base de datos.' : 'No se encontro el cliente en la base de datos.',
        ]);
    }

    public function validateRuc(Request $request, $token)
    {
        CommercialNegotiation::where('token', $token)->firstOrFail();

        $request->validate([
            'ruc' => ['required', 'string', 'size:11'],
        ]);

        // Replica del metodo consultaRUCmigo del modulo de ventas (ApisnetPeController).
        $baseMigo = 'https://api.migo.pe/api';
        $tokenMigo = Parameter::where('parameter_code', 'P000023')->value('value_default');

        $client = new Client();

        try {
            $response = $client->post($baseMigo . '/v1/ruc', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'token' => $tokenMigo,
                    'ruc' => $request->input('ruc'),
                ],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody(), true);

            return response()->json([
                'success' => true,
                'person' => [
                    'razon_social' => $data['nombre_o_razon_social'],
                    'numero_documento' => $data['ruc'],
                    'direccion' => $data['direccion_simple'],
                    'estado' => $data['estado_del_contribuyente'],
                    'condicion' => $data['condicion_de_domicilio'],
                    'ubigeo' => $data['ubigeo'],
                    'distrito' => $data['distrito'],
                    'provincia' => $data['provincia'],
                    'departamento' => $data['departamento'],
                ],
            ]);
        } catch (ClientException $e) {
            $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
            $message = $errorResponse['message'] ?? 'Error desconocido';

            return response()->json([
                'success' => false,
                'error' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ocurrió un error inesperado: ' . $e->getMessage(),
            ]);
        }
    }

    private function negotiationPayload(CommercialNegotiation $negotiation): array
    {
        return [
            'id' => $negotiation->id,
            'token' => $negotiation->token,
            'title' => $negotiation->title,
            'body' => $negotiation->body,
            'total_price' => (float) $negotiation->total_price,
            'currency' => $negotiation->currency,
            'payment_type' => $negotiation->payment_type,
            'initial_amount' => $negotiation->initial_amount !== null ? (float) $negotiation->initial_amount : null,
            'schedule' => $negotiation->schedule,
            'single_payment_days' => $negotiation->single_payment_days,
            'contact_channel' => $negotiation->contact_channel,
            'contact_detail' => $negotiation->contact_detail,
            'payment_method' => $negotiation->payment_method,
            'payment_link' => $negotiation->payment_link,
            'status' => $negotiation->status,
            'client_data' => $negotiation->client_data,
            'link_days' => $negotiation->link_days,
            'link_expires_at' => $negotiation->link_expires_at?->toISOString(),
            'voucher_path' => $negotiation->voucher_path,
            'rejected_reason' => $negotiation->rejected_reason,
            'items' => $negotiation->items->map(fn ($item) => [
                'item_type' => $item->item_type,
                'title' => $item->title,
                'price' => (float) $item->price,
            ])->values(),
            'company_billeteras' => $negotiation->companyBilleteras
                ->map(fn ($cb) => [
                    'id' => $cb->id,
                    'nombre' => $cb->billetera?->full_name,
                    'short_name' => $cb->billetera?->short_name,
                    'titular' => $cb->account_name,
                    'numero' => $cb->account_number,
                    'qr_url' => $cb->qr_image ? asset('storage/' . $cb->qr_image) : null,
                ])
                ->values(),
        ];
    }

    private function notifyAsesor(CommercialNegotiation $negotiation, Person $client): void
    {
        $asesor = $negotiation->creator;

        if (! $asesor || ! $asesor->email) {
            return;
        }

        try {
            Mail::to($asesor->email)->send(new CommercialNegotiationConfirmedMail($negotiation, $client));
        } catch (\Exception $e) {
            // El aviso por correo no debe interrumpir el registro de la negociacion.
        }
    }
}
