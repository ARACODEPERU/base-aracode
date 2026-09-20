<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\CommercialNegotiationConfirmedMail;
use App\Models\BankAccount;
use App\Models\Country;
use App\Models\District;
use App\Models\IdentityDocumentType;
use App\Models\Industry;
use App\Models\Parameter;
use App\Models\PaymentMethod;
use App\Models\Person;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\Commercial\Entities\CommercialNegotiation;
use Modules\Commercial\Entities\CommercialNegotiationInvoice;
use Modules\Commercial\Support\NegotiationConfirmedRecipients;

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
            'identityDocumentTypes' => IdentityDocumentType::orderBy('id')->get()
                ->map(fn ($type) => [
                    'id' => $type->id,
                    'description' => $type->description,
                    'sunat_code' => $type->sunat_code,
                    'requires_foreign_location' => $type->requiresForeignLocation(),
                ])
                ->values(),
            'industries' => Industry::select('id', 'description')->orderBy('description')->get(),
            // Cargos en orden alfabetico: el cliente los busca escribiendo.
            'occupations' => DB::table('occupations')->select('id', 'description')->orderBy('description')->get(),
            'countries' => Country::where('status', true)->orderBy('description')->get(['id', 'description']),
            'ubigeo' => $this->ubigeo(),
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
            // Mercado Pago: 'card' (pago con tarjeta) o 'evidence' (el cliente ya pago
            // por fuera y adjunta la captura). En los otros medios no se envia.
            'payment_option' => ['nullable', Rule::in(['card', 'evidence'])],
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
            // El multiselect envia el objeto {id, description}: se valida el id por
            // separado. Un texto suelto (pagina en cache) sigue siendo valido.
            'ocupacion' => ['nullable'],
            'ocupacion.id' => ['nullable', 'integer', 'exists:occupations,id'],
            'ocupacion.description' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:200'],
            'industry_id' => ['nullable'],
            // El multiselect envia el objeto {id, description}: se valida el id por separado.
            'industry_id.id' => ['nullable', 'integer', 'exists:industries,id'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:255'],
            // Ubicacion: si el documento es extranjero se piden Pais / Depto.-Estado / Ciudad,
            // en caso contrario se usa el ubigeo peruano como en el resto de formularios.
            'ubigeo' => [Rule::requiredIf(fn () => ! IdentityDocumentType::isForeignLocation($request->input('document_type_id'))), 'nullable', 'string', 'max:10'],
            'ubigeo_description' => ['nullable', 'string', 'max:255'],
            'foreign_country_id' => [Rule::requiredIf(fn () => IdentityDocumentType::isForeignLocation($request->input('document_type_id'))), 'nullable', 'integer', 'exists:countries,id'],
            'foreign_state' => [Rule::requiredIf(fn () => IdentityDocumentType::isForeignLocation($request->input('document_type_id'))), 'nullable', 'string', 'max:255'],
            'foreign_city' => [Rule::requiredIf(fn () => IdentityDocumentType::isForeignLocation($request->input('document_type_id'))), 'nullable', 'string', 'max:255'],
            // Boleta a nombre de una tercera persona: el cliente pide que la boleta
            // salga a nombre de otra persona (DNI validado por RENIEC/migo).
            'boleta_documento_tipo' => ['nullable', 'string', 'max:10'],
            'boleta_numero' => ['nullable', 'string', 'max:20'],
            'boleta_nombre' => ['nullable', 'string', 'max:255'],
            'ruc' => ['required_if:invoice_type,factura', 'nullable', 'string', 'size:11'],
            'invoice_razon_social' => ['required_if:invoice_type,factura', 'nullable', 'string', 'max:255'],
            'invoice_direccion' => ['nullable', 'string', 'max:255'],
            'invoice_estado' => ['required_if:invoice_type,factura', 'nullable', 'string', Rule::in(['ACTIVO'])],
            'invoice_condicion' => ['required_if:invoice_type,factura', 'nullable', 'string', Rule::in(['HABIDO'])],
            'invoice_ubigeo' => ['nullable', 'string', 'max:10'],
            'invoice_distrito' => ['nullable', 'string', 'max:255'],
            'invoice_provincia' => ['nullable', 'string', 'max:255'],
            'invoice_departamento' => ['nullable', 'string', 'max:255'],
            'voucher' => [Rule::requiredIf(fn () => $this->voucherRequired($negotiation, $request->input('payment_option'))), 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $isRuc = (string) $data['document_type_id'] === '6';

        $fullName = $isRuc
            ? trim((string) ($data['full_name'] ?? ''))
            : trim(($data['father_lastname'] ?? '') . ' ' . ($data['mother_lastname'] ?? '') . ' ' . ($data['names'] ?? ''));

        $person = Person::where('number', trim($data['number']))
            ->where('document_type_id', $data['document_type_id'])
            ->first();

        $isForeignLocation = IdentityDocumentType::isForeignLocation($data['document_type_id']);

        $industryInput = $data['industry_id'] ?? null;
        $industryId = is_array($industryInput) ? ($industryInput['id'] ?? null) : $industryInput;
        $industry = ! empty($industryId) ? Industry::find($industryId) : null;

        // Cargo u ocupacion: se guarda el texto del catalogo junto a su id.
        [$occupationId, $occupation] = $this->resolveOccupation($data['ocupacion'] ?? null);

        // El cliente extranjero guarda su ubicacion como "Pais - Estado - Ciudad".
        $foreignCountry = ($isForeignLocation && ! empty($data['foreign_country_id']))
            ? Country::where('id', $data['foreign_country_id'])->value('description')
            : null;

        // La ubicacion se guarda en columnas propias: peruana (ubigeo) o extranjera (pais/estado/ciudad).
        $locationPayload = $isForeignLocation
            ? [
                'ubigeo' => null,
                'ubigeo_description' => trim(($foreignCountry ?? '') . ' - ' . ($data['foreign_state'] ?? '') . ' - ' . ($data['foreign_city'] ?? '')),
                'foreign_country_id' => $data['foreign_country_id'] ?? null,
                'foreign_state' => $data['foreign_state'] ?? null,
                'foreign_city' => $data['foreign_city'] ?? null,
            ]
            : [
                'ubigeo' => $data['ubigeo'] ?? null,
                'ubigeo_description' => $data['ubigeo_description'] ?? null,
                'foreign_country_id' => null,
                'foreign_state' => null,
                'foreign_city' => null,
            ];

        // Solo se pisa la ubicacion guardada cuando el cliente envio datos de ubicacion.
        $hasLocationInput = $isForeignLocation
            ? (! empty($data['foreign_country_id']) || ! empty($data['foreign_state']) || ! empty($data['foreign_city']))
            : (! empty($data['ubigeo']) || ! empty($data['ubigeo_description']));

        $personPayload = array_merge([
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
            'ocupacion' => $occupation,
            'occupation_id' => $occupationId,
            'company' => $data['company'] ?? null,
            'industry_id' => $industry?->id,
            'industry' => $industry?->description,
            'birthdate' => $data['birthdate'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => true,
        ], $locationPayload);

        try {
            DB::beginTransaction();

            if ($person) {
                $person->update(array_filter($personPayload, fn ($value) => $value !== null && $value !== ''));

                if ($hasLocationInput) {
                    // Se aplica completo (incluye nulls) para poder pasar de extranjero a Peru y viceversa.
                    $person->update($locationPayload);
                }

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
                    // Mercado Pago liquidado por fuera: el cliente declara el pago y
                    // adjunta su evidencia, no hay transaccion procesada en el sistema.
                    'payment_declared' => $this->paymentDeclared($negotiation, $data['payment_option'] ?? null),
                ]),
                'voucher_path' => $voucherPath ?: $negotiation->voucher_path,
                'rejected_reason' => null,
                'verified_by' => null,
                'verified_at' => null,
            ]);

            // Boleta a nombre de terceros: solo se guarda si el cliente la activo
            // y el DNI fue validado (numero + nombre completados por la consulta).
            $boletaTercero = $data['invoice_type'] === 'boleta'
                && ! empty($data['boleta_documento_tipo'])
                && ! empty($data['boleta_numero'])
                && ! empty($data['boleta_nombre']);

            CommercialNegotiationInvoice::updateOrCreate(
                ['negotiation_id' => $negotiation->id],
                [
                    'invoice_type' => $data['invoice_type'],
                    'boleta_documento_tipo' => $boletaTercero ? $data['boleta_documento_tipo'] : null,
                    'boleta_numero' => $boletaTercero ? trim($data['boleta_numero']) : null,
                    'boleta_nombre' => $boletaTercero ? trim($data['boleta_nombre']) : null,
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

        $this->notifyNegotiationRecipients($negotiation, $person);

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

    /**
     * Indica si el correo ingresado ya tiene una cuenta de usuario registrada.
     * Si existe, devuelve los datos de la persona vinculada para precargarlos
     * en el formulario y que el cliente continue con esa misma cuenta.
     */
    public function checkEmail(Request $request, $token)
    {
        CommercialNegotiation::where('token', $token)->firstOrFail();

        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = trim(strtolower((string) $request->input('email')));

        $user = \App\Models\User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'exists' => false,
                'message' => 'El correo no tiene una cuenta registrada.',
            ]);
        }

        $person = $user->person_id ? Person::find($user->person_id) : null;

        return response()->json([
            'exists' => true,
            'has_person' => (bool) $person,
            'person' => $person, // Precarga el formulario con los datos de la cuenta existente.
            'message' => 'Este correo ya tiene una cuenta registrada'
                .($person?->full_name ? ' a nombre de '.$person->full_name : '')
                .'. Puedes continuar con esa cuenta y tus datos se actualizaran en ella.',
        ]);
    }

    /**
     * Consulta un DNI en RENIEC (apis.net.pe, token P000012) y como respaldo en migo.pe
     * (token P000023). Devuelve los nombres separados cuando la fuente los proporciona.
     */
    public function validateDni(Request $request, $token)
    {
        CommercialNegotiation::where('token', $token)->firstOrFail();

        $request->validate([
            'dni' => ['required', 'string', 'digits:8'],
        ]);

        $dni = trim((string) $request->input('dni'));

        // Fuente principal: RENIEC por apis.net.pe (nombres y apellidos separados).
        try {
            $tokenReniec = Parameter::where('parameter_code', 'P000012')->value('value_default');
            $client = new Client(['verify' => false, 'connect_timeout' => 5]);
            $response = $client->request('GET', 'https://api.apis.net.pe/v2/reniec/dni', [
                'headers' => [
                    'Authorization' => 'Bearer '.$tokenReniec,
                    'Referer' => 'https://apis.net.pe/api-consulta-dni',
                    'User-Agent' => 'laravel/guzzle',
                    'Accept' => 'application/json',
                ],
                'query' => ['numero' => $dni],
                'timeout' => 12,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (! empty($data['nombreCompleto']) || ! empty($data['nombres'])) {
                return response()->json([
                    'success' => true,
                    'source' => 'reniec',
                    'person' => [
                        'names' => $data['nombres'] ?? null,
                        'father_lastname' => $data['apellidoPaterno'] ?? null,
                        'mother_lastname' => $data['apellidoMaterno'] ?? null,
                        'full_name' => $data['nombreCompleto'] ?? null,
                        'document_number' => $data['numeroDocumento'] ?? $dni,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            // Sin token, token vencido o servicio caido: se intenta el respaldo.
        }

        // Respaldo: migo.pe (devuelve el nombre completo en un solo campo).
        try {
            $tokenMigo = Parameter::where('parameter_code', 'P000023')->value('value_default');
            $client = new Client();
            $response = $client->post('https://api.migo.pe/api/v1/dni', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'token' => $tokenMigo,
                    'dni' => $dni,
                ],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody(), true);

            if (! empty($data['nombre'])) {
                return response()->json([
                    'success' => true,
                    'source' => 'migo',
                    'person' => [
                        'names' => null,
                        'father_lastname' => null,
                        'mother_lastname' => null,
                        'full_name' => $data['nombre'],
                        'document_number' => $data['dni'] ?? $dni,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            // Se reporta como no disponible.
        }

        return response()->json([
            'success' => false,
            'error' => 'No se pudo validar el DNI con RENIEC en este momento. Intenta nuevamente.',
        ]);
    }

    /**
     * Ciudades peruanas (ubigeo) para el formulario publico.
     */
    private function ubigeo()
    {
        return District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();
    }

    /**
     * El voucher (imagen) es obligatorio en todos los medios de pago, salvo en
     * Mercado Pago pagando con tarjeta. En Mercado Pago el cliente puede declarar
     * que ya pago por fuera ('evidence'): ahi la imagen vuelve a ser obligatoria,
     * igual que en una transferencia o billetera.
     */
    private function voucherRequired(CommercialNegotiation $negotiation, ?string $paymentOption): bool
    {
        if ($negotiation->payment_method !== 'mercadopago') {
            return true;
        }

        return $paymentOption === 'evidence';
    }

    /**
     * Marca el pago declarado por el cliente: Mercado Pago liquidado por fuera y
     * enviado como evidencia. El metodo de pago del registro sigue siendo 'mercadopago'.
     */
    private function paymentDeclared(CommercialNegotiation $negotiation, ?string $paymentOption): bool
    {
        return $negotiation->payment_method === 'mercadopago' && $paymentOption === 'evidence';
    }

    /**
     * Cargo u ocupacion elegido en el formulario publico.
     *
     * El multiselect envia {id, description}; el texto guardado sale del
     * catalogo (no de lo que venga en la peticion) para que la tabla people
     * quede siempre con el nombre oficial. Un texto suelto (pagina en cache)
     * se respeta tal cual, sin id.
     *
     * @return array{0: ?int, 1: ?string}
     */
    private function resolveOccupation($input): array
    {
        $id = is_array($input) ? ($input['id'] ?? null) : null;
        $texto = is_array($input) ? ($input['description'] ?? null) : $input;
        $description = is_string($texto) ? trim($texto) : null;

        if (! empty($id)) {
            $oficial = DB::table('occupations')->where('id', $id)->value('description');

            if ($oficial !== null) {
                return [(int) $id, $oficial];
            }
        }

        return [null, $description ?: null];
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
            // El canal de contacto y el asesor son datos internos: no viajan al
            // navegador del cliente para que no puedan verse en el payload.
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

    /**
     * Notifica que el cliente respondio la negociacion al equipo administrador (los
     * roles del modulo mas el buzon MAIL_ADMIN) y al asesor que la creo. Cada
     * destinatario se encola por separado para que un correo invalido o un fallo
     * puntual no impida notificar a los demas.
     */
    private function notifyNegotiationRecipients(CommercialNegotiation $negotiation, Person $client): void
    {
        // Los destinatarios (roles administradores, buzon MAIL_ADMIN y asesor) se
        // resuelven en NegotiationConfirmedRecipients, que valida y deduplica.
        $recipients = NegotiationConfirmedRecipients::forNegotiation($negotiation);

        if ($recipients === []) {
            // Sin destinatarios el aviso no sale: queda registrado para no perderlo
            // en silencio.
            Log::warning('Negociacion confirmada sin destinatarios para el aviso por correo.', [
                'negotiation_id' => $negotiation->id,
            ]);

            return;
        }

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->queue(new CommercialNegotiationConfirmedMail($negotiation, $client));
            } catch (\Throwable $e) {
                // Un destinatario fallido no debe impedir los demas envios encolados.
                report($e);
            }
        }
    }
}
