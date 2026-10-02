<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\IdentityDocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Commercial\Emails\CommercialQuoteMail;
use Modules\Commercial\Entities\CommercialNegotiation;

class CommercialNegotiationController extends Controller
{
    /**
     * Estados posteriores a la confirmacion del alumno.
     *
     * Desde ahi ya pueden existir registros del proceso (persona, cuenta de usuario,
     * alumno, venta y comprobante), por eso el borrado es sensible: exige la
     * contrasena de quien lo ejecuta y solo un administrador puede forzarlo cuando
     * la negociacion esta 'confirmada'.
     */
    private const PROTECTED_STATUSES = ['confirmada', 'aprobada', 'completada'];

    public function index()
    {
        $this->expireOverdueNegotiations();

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
                'contact_detail' => $this->advisorName(),
                'link_days' => $data['link_days'] ?? $this->defaultLinkDays(),
                'link_expires_at' => $this->expirationDate($data['link_days'] ?? $this->defaultLinkDays()),
                'created_by' => auth()->id(),
            ]));

            $this->syncItems($negotiation, $data['items']);
            $this->syncCompanyBilleteras($negotiation, $data['company_billetera_ids'] ?? []);

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
        $negotiation = CommercialNegotiation::with(['items', 'companyBilleteras', 'creator'])->findOrFail($id);

        abort_unless(
            $this->canManage($negotiation),
            403,
            'Solo puedes editar las negociaciones que tu creaste.'
        );

        return Inertia::render('Commercial::Negotiations/Edit', array_merge($this->formData(), [
            'negotiation' => $negotiation,
        ]));
    }

    public function update(Request $request, $id)
    {
        $negotiation = CommercialNegotiation::findOrFail($id);

        if (! $this->canManage($negotiation)) {
            return $this->forbiddenManageResponse();
        }

        $data = $this->validatedData($request);

        try {
            DB::beginTransaction();

            $negotiation->update(array_merge($this->payload($data), [
                'link_days' => $data['link_days'],
            ]));

            // Recalcula la fecha de expiracion del enlace solo para negociaciones aun no vencidas/respondidas.
            if (in_array($negotiation->status, ['pendiente', 'sin_respuesta'])) {
                $negotiation->update(['link_expires_at' => $this->expirationDate($data['link_days'])]);
            }

            $this->syncItems($negotiation, $data['items']);
            $this->syncCompanyBilleteras($negotiation, $data['company_billetera_ids'] ?? []);

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

    /**
     * Envia el correo con el enlace de la cotizacion al correo del cliente.
     * Opcionalmente recibe un correo en la peticion para registrarlo y enviar.
     */
    public function sendQuote(Request $request, $id)
    {
        $negotiation = CommercialNegotiation::findOrFail($id);

        if (! $this->canManage($negotiation)) {
            return $this->forbiddenManageResponse();
        }

        if (in_array($negotiation->status, ['aprobada', 'cancelada'])) {
            return response()->json([
                'success' => false,
                'message' => 'Esta negociacion ya no puede enviarse como cotizacion.',
            ], 422);
        }

        $incomingEmail = trim((string) ($request->input('email') ?? ''));

        if ($incomingEmail !== '') {
            $negotiation->update(['email' => $incomingEmail]);
        }

        $email = trim((string) $negotiation->fresh()->email);

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'Debe registrar el correo del cliente antes de enviar la cotizacion.',
            ]);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'El correo del cliente no es valido.',
            ]);
        }

        try {
            Mail::to($email)->queue(new CommercialQuoteMail($negotiation->fresh()));

            return response()->json([
                'success' => true,
                'queued' => true,
                'message' => 'La cotizacion fue puesta en cola correctamente para ' . $email,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar el correo: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Elimina la negociacion y solo sus datos propios.
     *
     * Se borran los items, los datos de facturacion capturados en el formulario publico
     * y las billeteras seleccionadas (el pivote no tiene clave foranea, asi que sin el
     * detach quedarian filas huerfanas). NO se toca nada mas: el cliente (persona), su
     * cuenta de usuario, el alumno, las matriculas, las suscripciones, las cuotas, la
     * venta y el comprobante ya emitido se conservan intactos.
     *
     * En los estados protegidos el borrado es sensible: exige la contrasena del usuario
     * que elimina y solo el administrador puede forzar el de una negociacion confirmada.
     */
    public function destroy(Request $request, $id)
    {
        $negotiation = CommercialNegotiation::findOrFail($id);

        if (! $this->canManage($negotiation)) {
            return $this->forbiddenManageResponse();
        }

        $isProtected = in_array($negotiation->status, self::PROTECTED_STATUSES, true);

        if ($negotiation->status === 'confirmada' && ! $this->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo un administrador puede eliminar una negociacion que el alumno ya confirmo.',
            ], 422);
        }

        if ($isProtected && ($passwordError = $this->passwordError($request, $negotiation))) {
            return $passwordError;
        }

        try {
            DB::beginTransaction();

            // El pivote no tiene clave foranea: hay que desvincularlo a mano.
            $negotiation->companyBilleteras()->detach();

            // Hijos directos de la negociacion. Se borran de forma explicita para no
            // depender de las cascadas de MySQL (que sqlite de las pruebas no aplica).
            $negotiation->items()->delete();
            $negotiation->invoice()->delete();

            // En el borrado sensible el voucher se conserva: es la evidencia de pago del
            // cliente y la venta con su comprobante siguen existiendo.
            if (! $isProtected && $negotiation->voucher_path) {
                Storage::disk('public')->delete($negotiation->voucher_path);
            }

            $snapshot = [
                'negotiation_id' => $negotiation->id,
                'title' => $negotiation->title,
                'status' => $negotiation->status,
                'client_id' => $negotiation->client_id,
                'sale_id' => $negotiation->sale_id,
                'sale_document_id' => $negotiation->sale_document_id,
                'deleted_by' => auth()->id(),
            ];

            $negotiation->delete();
            DB::commit();

            Log::info('Negociacion eliminada; sus registros asociados se conservaron.', $snapshot + [
                'source' => 'CommercialNegotiationController::destroy',
            ]);

            return response()->json([
                'success' => true,
                'message' => $isProtected
                    ? 'Negociacion eliminada. Se conservaron el cliente, la venta, el comprobante y los registros del proceso.'
                    : 'Negociacion eliminada correctamente',
                'preserved' => [
                    'client_id' => $snapshot['client_id'],
                    'sale_id' => $snapshot['sale_id'],
                    'sale_document_id' => $snapshot['sale_document_id'],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Valida la contrasena del usuario que ejecuta el borrado sensible.
     * Devuelve la respuesta 422 cuando falta o no coincide, y null cuando es correcta.
     */
    private function passwordError(Request $request, CommercialNegotiation $negotiation)
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Debes ingresar tu contrasena para eliminar esta negociacion.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->getAuthPassword())) {
            // Queda constancia del intento fallido para poder auditar el borrado sensible.
            Log::warning('Intento de eliminar una negociacion con contrasena incorrecta.', [
                'negotiation_id' => $negotiation->id,
                'status' => $negotiation->status,
                'user_id' => $user?->id,
                'source' => 'CommercialNegotiationController::destroy',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'La contrasena no es correcta.',
                'errors' => ['password' => ['La contrasena no es correcta.']],
            ], 422);
        }

        return null;
    }

    public function show($id)
    {
        $negotiation = CommercialNegotiation::with([
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
            ])->findOrFail($id);

        abort_unless(
            $this->canManage($negotiation),
            403,
            'Solo puedes ver el detalle de las negociaciones que tu creaste.'
        );

        return Inertia::render('Commercial::Negotiations/Show', [
            'negotiation' => $negotiation,
            'statuses' => $this->statuses(),
            'paymentMethods' => $this->paymentMethods(),
            'contactChannelLabels' => CommercialNegotiation::contactChannelLabels(),
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

        if (! $this->canManage($negotiation)) {
            return $this->forbiddenManageResponse();
        }

        $negotiation->update([
            'status' => 'cancelada',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Negociacion cancelada correctamente.',
        ]);
    }

    /**
     * Roles administradores: gestionan cualquier negociacion, incluso el borrado
     * forzado de las que ya pasaron por la confirmacion del alumno.
     */
    private function isAdmin(): bool
    {
        $user = auth()->user();

        return $user ? $user->hasAnyRole(['Administrador', 'admin']) : false;
    }

    /**
     * Los roles administradores gestionan cualquier negociacion; el resto (por ejemplo Ventas)
     * solo las que creo. Se usa en show, edit, update, sendQuote, destroy y cancel.
     */
    private function canManage(CommercialNegotiation $negotiation): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return (int) $negotiation->created_by === (int) $user->id;
    }

    /**
     * Respuesta 403 para las rutas AJAX cuando el usuario no puede gestionar la negociacion.
     */
    private function forbiddenManageResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Solo puedes gestionar las negociaciones que tu creaste.',
        ], 403);
    }

    private function formData(): array
    {
        return [
            'courses' => AcaCourse::where('status', 1)
                ->orderBy('description')
                ->get(['id', 'description', 'price']),
            'subscriptions' => AcaSubscriptionType::where('status', 1)
                ->orderBy('title')
                ->get(['id', 'title', 'prices'])
                ->map(function ($sub) {
                    // Exponemos el precio en soles (PEN) de forma explicita para el formulario.
                    $price = null;
                    $prices = is_array($sub->prices) ? $sub->prices : [];

                    foreach ($prices as $row) {
                        if (is_array($row) && strtoupper((string) ($row['currency'] ?? '')) === 'PEN') {
                            $price = $row['amount'] ?? null;
                            break;
                        }
                    }

                    if ($price === null && count($prices) > 0) {
                        $first = reset($prices);
                        $price = is_array($first) ? ($first['amount'] ?? null) : $first;
                    }

                    return [
                        'id' => $sub->id,
                        'title' => $sub->title,
                        'prices' => $sub->prices,
                        'price' => $price !== null ? (float) $price : null,
                    ];
                })
                ->values(),
            'identityDocumentTypes' => IdentityDocumentType::orderBy('id')->get(),
            // unique(): la tabla puede contener filas repetidas para la misma moneda.
            'currencyTypes' => DB::table('sunat_currency_types')
                ->where('active', true)
                ->orderBy('id')
                ->get(['id', 'symbol', 'description'])
                ->unique('id')
                ->values(),
            // Combo de moneda visible solo con el modo multi-moneda (PTM0004) activo;
            // con TC vigente para mostrar la conversion en la interfaz.
            'multiCurrencyEnabled' => app(\Modules\Sales\Services\ExchangeRateService::class)->isMultiCurrencyEnabled(),
            'exchangeRate' => (function () {
                try {
                    $rate = app(\Modules\Sales\Services\ExchangeRateService::class)->getCurrentRate('USD');

                    return $rate ? (float) $rate['rate'] : null;
                } catch (\Throwable $e) {
                    return null;
                }
            })(),
            'paymentMethods' => $this->paymentMethods(),
            'contactChannels' => $this->contactChannels(),
            'companyBilleteras' => \App\Models\CompanyBilletera::with('billetera')
                ->where('status', true)
                ->whereNotNull('qr_image')
                ->get()
                ->map(fn ($cb) => [
                    'id' => $cb->id,
                    'billetera_id' => $cb->billetera_id,
                    'nombre' => $cb->billetera?->full_name,
                    'titular' => $cb->account_name,
                    'numero' => $cb->account_number,
                    'qr_url' => $cb->qr_image ? asset('storage/' . $cb->qr_image) : null,
                ])
                ->values(),
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
            'link_days' => ['nullable', 'integer', 'min:1'],
            'contact_channel' => ['nullable', 'string', 'max:40'],
            // El asesor se fija en el servidor con el usuario logueado (store);
            // lo que llegue en la peticion se ignora.
            'contact_detail' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'payment_method' => ['required', Rule::in(['billetera_digital', 'mercadopago', 'transferencia', 'enlace'])],
            'company_billetera_ids' => ['nullable', 'array'],
            'company_billetera_ids.*' => ['nullable', 'integer', 'exists:company_billeteras,id'],
            'payment_link' => ['nullable', 'url', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', Rule::in(['course', 'subscription'])],
            'items.*.item_id' => ['nullable', 'integer'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ])->after(function ($validator) {
            $data = $validator->getData();

            // Si el medio de pago es billetera digital, debe elegirse al menos una.
            if (($data['payment_method'] ?? null) === 'billetera_digital') {
                $billeteraIds = array_values(array_filter((array) ($data['company_billetera_ids'] ?? [])));

                if (count($billeteraIds) === 0) {
                    $validator->errors()->add('company_billetera_ids', 'Debe seleccionar al menos una billetera digital que tenga QR.');
                }
            }

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

    /**
     * Asesor de la negociacion: siempre el usuario logueado.
     *
     * El campo del formulario es de solo lectura y este valor no se toma de la
     * peticion, asi que no se puede guardar otro nombre manipulando el envio.
     */
    private function advisorName(): string
    {
        return (string) (auth()->user()?->name ?? '');
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
            'link_days' => $data['link_days'] ?? null,
            'contact_channel' => $data['contact_channel'] ?? null,
            // contact_detail no viaja aqui: se fija al crear (advisorName) y al
            // editar se conserva el asesor guardado, sin poder cambiarlo.
            'email' => $data['email'] ?? null,
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
                'entity_name_product' => $item['entity_name_product'] ?? $this->entityNameForItemType($item['item_type']),
                'item_id' => $item['item_id'] ?? null,
                'title' => $item['title'],
                'price' => $item['price'] ?? null,
            ]);
        }
    }

    /**
     * Clase (FQCN) de la que proviene un item segun su tipo.
     */
    private function entityNameForItemType(?string $itemType): ?string
    {
        return match ($itemType) {
            'course' => \Modules\Academic\Entities\AcaCourse::class,
            'subscription' => \Modules\Academic\Entities\AcaSubscriptionType::class,
            default => null,
        };
    }

    /**
     * Sincroniza las billeteras digitales de la empresa seleccionadas en la negociacion.
     */
    private function syncCompanyBilleteras(CommercialNegotiation $negotiation, array $billeteraIds): void
    {
        $ids = array_values(array_filter(array_map('intval', $billeteraIds)));

        $negotiation->companyBilleteras()->sync($ids);
    }

    private function statuses(): array
    {
        return [
            ['value' => 'pendiente', 'label' => 'Pendiente', 'color' => 'secondary'],
            ['value' => 'confirmada', 'label' => 'Confirmada', 'color' => 'primary'],
            ['value' => 'aprobada', 'label' => 'Aprobada', 'color' => 'success'],
            ['value' => 'completada', 'label' => 'Proceso completado', 'color' => 'success'],
            ['value' => 'rechazada', 'label' => 'Rechazada', 'color' => 'danger'],
            ['value' => 'sin_respuesta', 'label' => 'No hubo respuesta', 'color' => 'warning'],
            ['value' => 'cancelada', 'label' => 'Cancelada', 'color' => 'dark'],
        ];
    }

    private function paymentMethods(): array
    {
        return [
            ['value' => 'billetera_digital', 'label' => 'Billetera digital'],
            ['value' => 'mercadopago', 'label' => 'Mercado Pago'],
            ['value' => 'transferencia', 'label' => 'Transferencia bancaria'],
            ['value' => 'enlace', 'label' => 'Enlace de pago'],
        ];
    }

    /**
     * Canales por los que puede llegar el cliente.
     *
     * Se guarda la etiqueta legible como valor (no un slug) porque el listado,
     * el detalle, la pagina publica del cliente y el webhook a n8n muestran
     * contact_channel tal cual, sin mapa de traduccion.
     */
    private function contactChannels(): array
    {

        return [
            ['value' => 'Ads', 'label' => 'Ads'],
            ['value' => 'Invitado CPA', 'label' => 'Invitado CPA'],
            ['value' => 'Masivo Api', 'label' => 'Masivo Api'],
            ['value' => 'Orgánico', 'label' => 'Orgánico'],
            ['value' => 'Personal CPA', 'label' => 'Personal CPA'],
            ['value' => 'Referido', 'label' => 'Referido'],
            ['value' => 'Reserva', 'label' => 'Reserva'],
            ['value' => 'Web CPA', 'label' => 'Web CPA'],
            ['value' => 'Webinar', 'label' => 'Webinar'],
            ['value' => 'Lead gratuito', 'label' => 'Lead gratuito'],
        ];

    }

    /**
     * Marca como "No hubo respuesta" las negociaciones pendientes cuyo enlace vencio.
     * Se ejecuta como script previo antes de listar las negociaciones.
     */
    private function expireOverdueNegotiations(): void
    {
        CommercialNegotiation::where('status', 'pendiente')
            ->whereNotNull('link_expires_at')
            ->where('link_expires_at', '<', now())
            ->update(['status' => 'sin_respuesta']);
    }

    /**
     * Estados desde los que el administrador puede generar un nuevo enlace (reactivar).
     */
    private function reactivableStatuses(): array
    {
        return ['pendiente', 'sin_respuesta', 'rechazada'];
    }

    private function expirationDate(?int $days): ?\Illuminate\Support\Carbon
    {
        return $days ? now()->addDays($days) : null;
    }

    private function defaultLinkDays(): int
    {
        return (int) (config('commercial.negotiation_link_days', 2));
    }

    /**
     * Genera un nuevo enlace (token nuevo) para la negociacion y vuelve a ponerla como pendiente.
     */
    public function reactivate($id)
    {
        $negotiation = CommercialNegotiation::findOrFail($id);

        if (! in_array($negotiation->status, $this->reactivableStatuses())) {
            return back()->with('error', 'No se puede generar un nuevo enlace desde el estado actual de la negociacion.');
        }

        DB::transaction(function () use ($negotiation) {
            $negotiation->update([
                'token' => (string) Str::uuid(),
                'status' => 'pendiente',
                'link_days' => $negotiation->link_days ?: $this->defaultLinkDays(),
                'link_expires_at' => $this->expirationDate($negotiation->link_days ?: $this->defaultLinkDays()),
                'client_id' => null,
                'client_data' => null,
                'voucher_path' => null,
                'rejected_reason' => null,
                'verified_by' => null,
                'verified_at' => null,
            ]);
        });

        return back()->with('success', 'Se genero un nuevo enlace para la negociacion.');
    }
}
