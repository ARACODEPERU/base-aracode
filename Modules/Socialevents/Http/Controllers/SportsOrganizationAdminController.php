<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Helpers\NumberLetter;
use App\Http\Controllers\Controller;
use App\Models\LocalSale;
use App\Models\Parameter;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleDocumentType;
use App\Models\Serie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\Socialevents\Services\OrganizationTeamService;
use Modules\Socialevents\Services\SportsOrganizationService;
use Modules\Socialevents\Support\OrganizationCapabilities;

class SportsOrganizationAdminController extends Controller
{
    /**
     * Lista de tarifas por equipo y partido, con la opción de crear una nueva.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $settings = DB::table('socialevent_organization_settings')->first();

        return Inertia::render('Socialevents::Organizations/Admin', [
            'defaults' => [
                'trial_days' => (int) ($settings->trial_days ?? 30),
                'default_match_rate' => (float) ($settings->default_match_rate ?? 5),
                'seat_price' => (float) ($settings->seat_price ?? 1),
            ],
            'rates' => DB::table('socialevent_match_rates')->orderBy('name')->get(),
        ]);
    }

    /**
     * Cuentas de organizaciones con el contacto de sus usuarios y las acciones de administración.
     */
    public function accounts(Request $request)
    {
        $this->authorizeAdmin($request);

        $members = DB::table('socialevent_organization_users')
            ->join('users', 'users.id', '=', 'socialevent_organization_users.user_id')
            ->select(
                'socialevent_organization_users.organization_id',
                'socialevent_organization_users.role',
                'socialevent_organization_users.profile_key',
                'socialevent_organization_users.capabilities',
                'socialevent_organization_users.status',
                'socialevent_organization_users.permissions_updated_at',
                'users.name',
                'users.apellido_paterno',
                'users.apellido_materno',
                'users.email',
                'users.telefono',
                'users.tipo_identidad',
                'users.numero_identidad',
            )
            ->orderBy('users.name')
            ->get()
            ->groupBy('organization_id');

        // Historial de cobros por organización: se muestra junto al formulario
        // de cobro para no tener que salir de la lista.
        $ledger = DB::table('socialevent_organization_ledger')
            ->where('entry_type', 'balance_topup')
            ->orderByDesc('id')
            ->get(['id', 'organization_id', 'amount', 'payment_method', 'reference', 'description', 'created_at'])
            ->groupBy('organization_id');

        // Compras de espacios y solicitudes de comprobante de todas las
        // organizaciones: se agrupan para no consultar dentro del map.
        $seatPurchases = DB::table('socialevent_organization_seat_purchases')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('organization_id');
        $documentRequests = DB::table('socialevent_organization_document_requests as requests')
            ->leftJoin('sale_documents as documents', 'documents.id', '=', 'requests.sale_document_id')
            ->leftJoin('sale_document_types as types', 'types.id', '=', 'requests.document_type_id')
            ->orderByDesc('requests.created_at')
            ->orderByDesc('requests.id')
            ->get([
                'requests.id',
                'requests.organization_id',
                'requests.purchase_type',
                'requests.purchase_id',
                'requests.status',
                'requests.billing_data',
                'requests.delivery_channels',
                'requests.rejection_reason',
                'requests.sale_document_id',
                'requests.created_at',
                'types.description as document_type',
                'types.sunat_id',
                'documents.invoice_serie',
                'documents.invoice_correlative',
            ])
            ->groupBy('organization_id');

        $organizations = DB::table('socialevent_organizations')
            ->where('is_legacy', false)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get()
            ->map(function ($organization) use ($members, $ledger, $seatPurchases, $documentRequests) {
                $organizationMembers = $members->get($organization->id, collect());
                $owner = $organizationMembers->firstWhere('role', 'owner');
                $organization->owner_name = $owner ? trim(implode(' ', array_filter([$owner->name, $owner->apellido_paterno, $owner->apellido_materno]))) : null;
                $organization->owner_email = $owner->email ?? null;
                $organization->owner_phone = $owner->telefono ?? null;
                $organization->owner_document = $owner->numero_identidad ?? null;
                $organization->members = $organizationMembers->values();
                $organization->payments = $ledger->get($organization->id, collect())->take(8)->values();

                $activeMembers = $organizationMembers->where('status', 'active')->count();
                $organization->seats_used = $activeMembers;
                $organization->seats_available = $organization->seat_limit === null
                    ? null
                    : max(0, (int) $organization->seat_limit - $activeMembers);

                $organization->seat_purchases = $seatPurchases->get($organization->id, collect())
                    ->take(10)
                    ->map(fn ($purchase) => [
                        'id' => (int) $purchase->id,
                        'quantity' => (int) $purchase->quantity,
                        'unit_price' => (float) $purchase->unit_price,
                        'total' => (float) $purchase->total,
                        'status' => (string) $purchase->status,
                        'payment_method' => (string) $purchase->payment_method,
                        'reference' => $purchase->provider_payment_id ?: $purchase->external_reference,
                        'created_at' => $purchase->created_at,
                    ])
                    ->values();

                $organization->document_requests = $documentRequests->get($organization->id, collect())
                    ->take(10)
                    ->map(fn ($document) => [
                        'id' => (int) $document->id,
                        'purchase_type' => (string) $document->purchase_type,
                        'purchase_id' => (int) $document->purchase_id,
                        'status' => (string) $document->status,
                        'document_type' => $document->document_type,
                        'sunat_id' => $document->sunat_id,
                        'billing_data' => json_decode((string) $document->billing_data, true) ?: [],
                        'delivery_channels' => json_decode((string) $document->delivery_channels, true) ?: [],
                        'rejection_reason' => $document->rejection_reason,
                        'created_at' => $document->created_at,
                        'number' => $document->invoice_serie
                            ? $document->invoice_serie . '-' . str_pad((string) $document->invoice_correlative, 9, '0', STR_PAD_LEFT)
                            : null,
                        'can_download' => (bool) $document->sale_document_id,
                    ])
                    ->values();

                return $organization;
            });

        $recentAlerts = DB::table('socialevent_organization_alerts')
            ->join('socialevent_organizations', 'socialevent_organizations.id', '=', 'socialevent_organization_alerts.organization_id')
            ->join('users', 'users.id', '=', 'socialevent_organization_alerts.user_id')
            ->orderByDesc('socialevent_organization_alerts.created_at')
            ->limit(30)
            ->get([
                'socialevent_organization_alerts.id',
                'socialevent_organization_alerts.organization_id',
                'socialevent_organizations.name as organization_name',
                'users.name as user_name',
                'socialevent_organization_alerts.type',
                'socialevent_organization_alerts.message',
                'socialevent_organization_alerts.read_at',
                'socialevent_organization_alerts.created_at',
            ]);

        return Inertia::render('Socialevents::Organizations/Accounts', [
            'organizations' => $organizations,
            'recentAlerts' => $recentAlerts,
            'documentTypes' => SaleDocumentType::query()->whereIn('sunat_id', ['80', '01', '03'])->orderBy('id')->get(['id', 'description', 'sunat_id']),
            'paymentMethods' => PaymentMethod::query()->orderBy('id')->get(['id', 'description']),
            'identityTypes' => DB::table('identity_document_type')->get(['id', 'description']),
            'districts' => DB::table('districts')->orderBy('name')->get(['id', 'name']),
            'alerts' => $this->alertSettings(),
            'capabilities' => OrganizationCapabilities::describe(),
            'seatPrice' => (float) (DB::table('socialevent_organization_settings')->value('seat_price') ?? 1),
        ]);
    }

    public function storeOrganization(Request $request)
    {
        $this->authorizeAdmin($request);
        $ownerInput = (array) $request->input('owner', []);
        $ownerInput['email'] = strtolower(trim((string) ($ownerInput['email'] ?? '')));
        $request->merge(['owner' => $ownerInput]);
        $validated = $request->validate([
            'name' => 'required|string|max:180',
            'tipo_organizacion' => 'nullable|in:empresa,persona_natural',
            'ruc' => ['nullable', 'string', 'regex:/^\d{11}$/'],
            'match_rate' => 'required|numeric|min:0|max:999999.99',
            'owner.person_id' => 'nullable|integer|exists:people,id',
            'owner.name' => 'required|string|max:255',
            'owner.password' => 'nullable|string|min:8|max:128',
            'owner.telefono' => 'nullable|string|max:20',
            'owner.tipo_identidad' => 'nullable|in:0,1,6',
            // La identidad o el correo pueden pertenecer a una persona/cuenta que
            // se reutilizará; las colisiones entre registros distintos se validan
            // dentro de la transacción antes de actualizar.
            'owner.numero_identidad' => 'required|string|max:20',
            'owner.email' => 'required|email|max:255',
            // Los datos de facturación son opcionales al crear la cuenta: se
            // completan luego desde "Cobros" cuando se emite factura o boleta.
            'address' => 'nullable|string|max:255',
            'ubigeo' => ['nullable', 'string', 'size:6', Rule::exists('districts', 'id')],
        ], [], [
            'name' => 'organización',
            'ruc' => 'RUC',
            'address' => 'dirección fiscal',
            'ubigeo' => 'ubigeo SUNAT',
            'match_rate' => 'tarifa por equipo y partido',
            'owner.name' => 'nombre del titular',
            'owner.email' => 'correo de acceso',
            'owner.password' => 'contraseña inicial',
            'owner.numero_identidad' => 'documento del titular',
        ]);

        $organizationId = DB::transaction(function () use ($validated, $request) {
            $ownerData = $validated['owner'];
            $email = strtolower(trim($ownerData['email']));
            $identityNumber = trim($ownerData['numero_identidad']);
            $person = ! empty($ownerData['person_id'])
                ? Person::query()->whereKey($ownerData['person_id'])->lockForUpdate()->first()
                : null;
            $existingUser = null;

            $personUser = $person
                ? User::query()->where('person_id', $person->id)->lockForUpdate()->first()
                : null;
            $identityUser = User::query()->where('numero_identidad', $identityNumber)->lockForUpdate()->first();
            $emailUser = User::query()->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
            $candidateUserIds = collect([$personUser, $identityUser, $emailUser])
                ->filter()
                ->pluck('id')
                ->unique();
            if ($candidateUserIds->count() > 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'owner.email' => 'El correo, el documento y la persona seleccionada deben pertenecer a la misma cuenta.',
                ]);
            }
            $existingUser = $personUser ?? $identityUser ?? $emailUser;

            if ($existingUser && ! $person && $existingUser->person_id) {
                $person = Person::query()->whereKey($existingUser->person_id)->lockForUpdate()->first();
            } elseif ($existingUser && $person && $existingUser->person_id && (int) $existingUser->person_id !== (int) $person->id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'owner.email' => 'La cuenta encontrada ya está vinculada a otra persona.',
                ]);
            }

            $emailPerson = Person::query()->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
            $identityPerson = Person::query()->where('number', $identityNumber)->lockForUpdate()->first();
            $matchingPeople = collect([$person, $emailPerson, $identityPerson])->filter()->pluck('id')->unique();
            if ($matchingPeople->count() > 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'owner.email' => 'El correo y el documento deben pertenecer a la misma persona.',
                ]);
            }
            $person ??= $emailPerson ?? $identityPerson;
            if ($person && $person->number !== $identityNumber && $identityPerson) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'owner.numero_identidad' => 'El documento ya está asociado a otra persona.',
                ]);
            }

            if ($existingUser) {
                if ($existingUser->hasRole('admin') || $existingUser->hasRole('Administrador')) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'owner.email' => 'No se puede reutilizar una cuenta de administración como titular.',
                    ]);
                }
                if (! $person) {
                    $person = Person::create([
                        'document_type_id' => $ownerData['tipo_identidad'] ?? '1',
                        'number' => $identityNumber,
                        'full_name' => $ownerData['name'],
                        'short_name' => $ownerData['name'],
                        'email' => $email,
                        'telephone' => $ownerData['telefono'] ?? null,
                        'is_client' => false,
                    ]);
                }
                if ($existingUser->socialevent_organization_id || $existingUser->socialeventOrganizationUsers()->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'owner.email' => 'Esta cuenta ya está vinculada a otra organización.',
                    ]);
                }

                $person->fill([
                    'document_type_id' => $ownerData['tipo_identidad'] ?? $person->document_type_id,
                    'number' => $identityNumber,
                    'full_name' => $ownerData['name'],
                    'short_name' => $ownerData['name'],
                    'email' => $email,
                    'telephone' => $ownerData['telefono'] ?? null,
                ]);
                $person->save();

                $userData = [
                    'person_id' => $person->id,
                    'name' => $ownerData['name'],
                    'email' => $email,
                    'telefono' => $ownerData['telefono'] ?? null,
                    'tipo_identidad' => $ownerData['tipo_identidad'] ?? null,
                    'numero_identidad' => $identityNumber,
                    'status' => 1,
                ];
                if (filled($ownerData['password'] ?? null)) {
                    $userData['password'] = Hash::make($ownerData['password']);
                }
                $user = $existingUser->forceFill($userData);
                $user->save();
            } else {
                if (blank($ownerData['password'] ?? null)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'owner.password' => 'Ingresa una contraseña inicial para la nueva cuenta.',
                    ]);
                }
                if (! $person) {
                    $person = Person::create([
                        'document_type_id' => $ownerData['tipo_identidad'] ?? '1',
                        'number' => $identityNumber,
                        'full_name' => $ownerData['name'],
                        'short_name' => $ownerData['name'],
                        'email' => $email,
                        'telephone' => $ownerData['telefono'] ?? null,
                        'is_client' => false,
                    ]);
                } else {
                    $person->fill([
                        'document_type_id' => $ownerData['tipo_identidad'] ?? $person->document_type_id,
                        'full_name' => $ownerData['name'],
                        'short_name' => $ownerData['name'],
                        'email' => $email,
                        'telephone' => $ownerData['telefono'] ?? null,
                    ]);
                    $person->save();
                }

                $user = User::create([
                    'person_id' => $person->id,
                    'name' => $ownerData['name'],
                    'email' => $email,
                    'telefono' => $ownerData['telefono'] ?? null,
                    'tipo_identidad' => $ownerData['tipo_identidad'] ?? null,
                    'numero_identidad' => $identityNumber,
                    'password' => Hash::make($ownerData['password']),
                    'status' => 1,
                ]);
            }
            $organizationId = app(SportsOrganizationService::class)->createOrganizationForUser($user, [
                'nombre' => $validated['name'],
                'tipo' => $validated['tipo_organizacion'] ?? null,
                'ruc' => $validated['ruc'] ?? null,
                'address' => $validated['address'] ?? null,
                'ubigeo' => $validated['ubigeo'] ?? null,
            ]);
            DB::table('socialevent_organizations')->where('id', $organizationId)->update([
                'match_rate' => $validated['match_rate'],
                'updated_at' => now(),
            ]);
            $this->audit($organizationId, $request->user()->id, 'organization_created_by_admin', 'Alta manual por el superadministrador.', [
                'owner_user_id' => $user->id,
                'match_rate' => $validated['match_rate'],
            ]);

            return $organizationId;
        });

        return redirect()->route('even_organizations.accounts')->with('success', 'Organización y usuario organizador creados.');
    }

    public function searchOrganizationOwner(Request $request)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate(['search' => 'required|string|min:2|max:255']);
        $term = trim($validated['search']);
        $people = Person::query()
            ->leftJoin('users as linked_users', 'linked_users.person_id', '=', 'people.id')
            ->where(function ($query) use ($term) {
                $query->where('people.full_name', 'like', "%{$term}%")
                    ->orWhere('people.short_name', 'like', "%{$term}%")
                    ->orWhere('people.number', 'like', "%{$term}%")
                    ->orWhere('people.email', 'like', "%{$term}%")
                    ->orWhere('linked_users.name', 'like', "%{$term}%")
                    ->orWhere('linked_users.email', 'like', "%{$term}%")
                    ->orWhere('linked_users.numero_identidad', 'like', "%{$term}%");
            })
            ->select('people.id', 'people.document_type_id', 'people.number', 'people.full_name', 'people.short_name', 'people.email', 'people.telephone')
            ->distinct()
            ->orderBy('people.full_name')
            ->limit(10)
            ->get()
            ->map(function (Person $person) {
                $user = User::query()
                    ->where(function ($query) use ($person) {
                        $query->where('person_id', $person->id);
                        if ($person->number) $query->orWhere('numero_identidad', $person->number);
                        if ($person->email) $query->orWhereRaw('LOWER(email) = ?', [strtolower($person->email)]);
                    })
                    ->first();

                return [
                    'id' => $person->id,
                    'name' => $person->full_name ?: $person->short_name,
                    'email' => $person->email ?: $user?->email,
                    'telefono' => $person->telephone ?: $user?->telefono,
                    'tipo_identidad' => $person->document_type_id ?: $user?->tipo_identidad,
                    'numero_identidad' => $person->number ?: $user?->numero_identidad,
                    'user_id' => $user?->id,
                    'available' => ! $user || (! $user->socialevent_organization_id && ! $user->socialeventOrganizationUsers()->exists() && ! $user->hasRole('admin') && ! $user->hasRole('Administrador')),
                ];
            });
        $unlinkedUsers = User::query()
            ->whereNull('person_id')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('numero_identidad', 'like', "%{$term}%");
            })
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['admin', 'Administrador']))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email', 'telefono', 'tipo_identidad', 'numero_identidad', 'socialevent_organization_id'])
            ->map(fn (User $user) => [
                'id' => null,
                'name' => $user->name,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'tipo_identidad' => $user->tipo_identidad,
                'numero_identidad' => $user->numero_identidad,
                'user_id' => $user->id,
                'available' => ! $user->socialevent_organization_id && ! $user->socialeventOrganizationUsers()->exists() && ! $user->hasRole('admin') && ! $user->hasRole('Administrador'),
            ]);

        return response()->json(['people' => $people->concat($unlinkedUsers)->values()]);
    }

    public function storeOrganizationUser(Request $request, int $organizationId)
    {
        $this->authorizeAdmin($request);
        $organization = DB::table('socialevent_organizations')->where('id', $organizationId)->where('is_legacy', false)->whereNull('deleted_at')->first();
        abort_unless($organization, 404);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:128',
            'telefono' => 'nullable|string|max:20',
            'tipo_identidad' => 'nullable|in:0,1,6',
            'numero_identidad' => 'required|string|max:20|unique:users,numero_identidad',
            'capabilities' => 'nullable|array',
            'capabilities.*' => 'string',
        ], [], [
            'name' => 'nombre',
            'email' => 'correo de acceso',
            'password' => 'contraseña inicial',
            'numero_identidad' => 'documento de identidad',
            'capabilities' => 'tareas',
        ]);

        // El alta manual reutiliza el mismo servicio que el propietario: así
        // respeta los espacios disponibles y aplica solo los permisos de las
        // tareas elegidas, sin entregar todo el módulo por defecto.
        $validated['capabilities'] = $validated['capabilities'] ?? ['full'];
        app(OrganizationTeamService::class)->createMember($request->user(), (int) $organization->id, $validated);

        return back()->with('success', 'Usuario agregado a la organización.');
    }

    public function markOrganizationAlertRead(Request $request, int $alertId)
    {
        $user = $request->user();
        abort_unless($user, 403);
        $query = DB::table('socialevent_organization_alerts')->where('id', $alertId)->whereNull('read_at');
        if (app(SportsOrganizationService::class)->isSuperAdmin($user)) {
            $this->authorizeAdmin($request);
        } else {
            $organizationId = app(SportsOrganizationService::class)->assertCanAccessPayments($user);
            $query->where('organization_id', $organizationId)->where('user_id', $user->id);
        }
        $query->update(['read_at' => now(), 'updated_at' => now()]);

        return back();
    }

    public function updateOrganizationProfile(Request $request, int $organizationId)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'name' => 'required|string|max:180',
            'tipo_organizacion' => 'nullable|in:empresa,persona_natural',
            // RUC, dirección y ubigeo se pueden completar al momento del cobro.
            'ruc' => ['nullable', 'string', 'regex:/^\d{11}$/'],
            'address' => 'nullable|string|max:255',
            'ubigeo' => ['nullable', 'string', 'size:6', Rule::exists('districts', 'id')],
            'reason' => 'required|string|max:1000',
        ], [], [
            'name' => 'organización',
            'ruc' => 'RUC',
            'address' => 'dirección fiscal',
            'ubigeo' => 'ubigeo SUNAT',
            'reason' => 'motivo de auditoría',
        ]);

        DB::transaction(function () use ($organizationId, $validated, $request) {
            $organization = $this->lockOrganization($organizationId);
            DB::table('socialevent_organizations')->where('id', $organizationId)->update([
                'name' => $validated['name'],
                'tipo_organizacion' => $validated['tipo_organizacion'] ?? null,
                'ruc' => $validated['ruc'] ?? null,
                'address' => $validated['address'] ?? null,
                'ubigeo' => $validated['ubigeo'] ?? null,
                'updated_at' => now(),
            ]);
            $this->audit($organizationId, $request->user()->id, 'organization_profile_updated', $validated['reason'], [
                'before' => ['name' => $organization->name, 'ruc' => $organization->ruc ?? null, 'address' => $organization->address ?? null, 'ubigeo' => $organization->ubigeo ?? null],
                'after' => ['name' => $validated['name'], 'ruc' => $validated['ruc'] ?? null, 'address' => $validated['address'] ?? null, 'ubigeo' => $validated['ubigeo'] ?? null],
            ]);
        });

        return back()->with('success', 'Organización actualizada y auditada.');
    }

    public function destroyOrganization(Request $request, int $organizationId)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate(['reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($organizationId, $validated, $request) {
            $organization = $this->lockOrganization($organizationId);
            $this->audit($organizationId, $request->user()->id, 'organization_deleted', $validated['reason'], [
                'name' => $organization->name,
            ]);
            // La organización conserva sus registros e historial para auditoría; la eliminación lógica la suspende.
            DB::table('socialevent_organizations')->where('id', $organizationId)->update([
                'status' => 'suspended',
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('even_organizations.accounts')->with('success', 'Organización eliminada; se conservó su historial.');
    }

    public function storeAdminPayment(Request $request, int $organizationId)
    {
        $this->authorizeAdmin($request);
        $organization = DB::table('socialevent_organizations')->where('id', $organizationId)->where('is_legacy', false)->whereNull('deleted_at')->first();
        abort_unless($organization, 404);
        $validated = $request->validate([
            'amount' => 'required|numeric|gt:0|max:999999.99',
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'document_type_id' => ['required', 'integer', Rule::exists('sale_document_types', 'id')->whereIn('sunat_id', ['80', '01', '03'])],
            'paid_at' => 'required|date',
            'reference' => 'nullable|string|max:180',
            // Datos de facturación que el administrador puede completar en el
            // mismo cobro (por ejemplo, cuando la factura se pide recién aquí).
            'ruc' => ['nullable', 'string', 'regex:/^\d{11}$/'],
            'address' => 'nullable|string|max:255',
            'ubigeo' => ['nullable', 'string', 'size:6', Rule::exists('districts', 'id')],
        ], [], [
            'amount' => 'monto',
            'document_type_id' => 'comprobante',
            'payment_method_id' => 'medio de pago',
            'paid_at' => 'fecha de pago',
            'ruc' => 'RUC',
            'address' => 'dirección fiscal',
            'ubigeo' => 'ubigeo SUNAT',
        ]);

        $documentType = SaleDocumentType::findOrFail($validated['document_type_id']);
        abort_unless($request->user()->local_id, 422, 'El administrador necesita un local de ventas asignado para emitir documentos.');
        // Los datos enviados en el cobro tienen prioridad; si no vienen, se usan
        // los guardados previamente en la organización.
        $billing = [
            'ruc' => filled($validated['ruc'] ?? null) ? $validated['ruc'] : ($organization->ruc ?? null),
            'address' => filled($validated['address'] ?? null) ? $validated['address'] : ($organization->address ?? null),
            'ubigeo' => filled($validated['ubigeo'] ?? null) ? $validated['ubigeo'] : ($organization->ubigeo ?? null),
        ];
        $billingUbigeoValid = $billing['ubigeo'] && DB::table('districts')->where('id', $billing['ubigeo'])->exists();
        if ($documentType->sunat_id === '01') {
            if (empty($billing['ruc'])) {
                return back()->withErrors(['ruc' => 'Ingresa el RUC para emitir la factura electrónica.'])->withInput();
            }
            if (empty($billing['address']) || ! $billingUbigeoValid) {
                return back()->withErrors(['address' => 'Para la factura electrónica necesitas la dirección fiscal y el ubigeo.'])->withInput();
            }
        }
        if ($documentType->sunat_id === '03' && ! in_array((string) ($organization->tipo_organizacion ?? ''), ['persona_natural', 'empresa'], true)) {
            return back()->withErrors(['document_type_id' => 'Completa los datos de la organización antes de emitir una boleta electrónica.'])->withInput();
        }
        $saleDocument = DB::transaction(function () use ($request, $organization, $validated, $documentType, $billing) {
            $organization = DB::table('socialevent_organizations')->where('id', $organization->id)->lockForUpdate()->first();
            // Guarda los datos de facturación ingresados en el cobro para no
            // volver a pedirlos en el siguiente comprobante.
            DB::table('socialevent_organizations')->where('id', $organization->id)->update([
                'ruc' => $billing['ruc'],
                'address' => $billing['address'],
                'ubigeo' => $billing['ubigeo'],
                'updated_at' => now(),
            ]);
            $organization->ruc = $billing['ruc'];
            $organization->address = $billing['address'];
            $organization->ubigeo = $billing['ubigeo'];
            $local = LocalSale::query()->where('id', $request->user()->local_id)->first();
            abort_unless($local, 422, 'Configura primero un local de ventas para emitir la nota de venta.');
            $series = Serie::query()->where('local_id', $local->id)->where('document_type_id', $documentType->id)->lockForUpdate()->first();
            $typeLabel = match ($documentType->sunat_id) {
                '01' => 'factura electrónica',
                '03' => 'boleta electrónica',
                default => 'nota de venta',
            };
            abort_unless($series, 422, 'No hay una serie de '.$typeLabel.' configurada en tu local.');
            $client = $this->resolveOrganizationClient($organization, $documentType->sunat_id);
            $cash = PettyCash::query()->firstOrCreate([
                'user_id' => $request->user()->id,
                'state' => 1,
                'local_sale_id' => $local->id,
            ], [
                'date_opening' => now()->toDateString(),
                'time_opening' => now()->format('H:i:s'),
                'income' => 0,
            ]);
            $amount = round((float) $validated['amount'], 2);
            $igvRate = $documentType->sunat_id === '80' ? 0 : (float) Parameter::query()->where('parameter_code', 'P000001')->value('value_default');
            $taxableBase = $documentType->sunat_id === '80' ? $amount : round($amount / (1 + ($igvRate / 100)), 2);
            $taxAmount = round($amount - $taxableBase, 2);
            $sale = Sale::create([
                'sale_date' => $validated['paid_at'],
                'user_id' => $request->user()->id,
                'client_id' => $client->id,
                'local_id' => $local->id,
                'total' => $amount,
                'advancement' => $amount,
                'total_discount' => 0,
                'payments' => json_encode([[
                    'type' => $validated['payment_method_id'],
                    'amount' => $amount,
                    'reference' => $validated['reference'] ?? null,
                ]]),
                'petty_cash_id' => $cash->id,
                'physical' => $documentType->sunat_id === '80' ? 1 : 2,
            ]);
            $seriesNumber = (int) $series->number;
            $numberLetters = new NumberLetter;
            $document = SaleDocument::create([
                'sale_id' => $sale->id,
                'serie_id' => $series->id,
                'number' => str_pad((string) $seriesNumber, 9, '0', STR_PAD_LEFT),
                'overall_total' => $amount,
                'user_id' => $request->user()->id,
                'invoice_type_doc' => $documentType->sunat_id,
                'invoice_serie' => $series->description,
                'invoice_correlative' => $seriesNumber,
                'invoice_broadcast_date' => $validated['paid_at'],
                'invoice_due_date' => $validated['paid_at'],
                'invoice_send_date' => now(),
                'invoice_type_currency' => 'PEN',
                'invoice_type_operation' => (string) (Parameter::query()->where('parameter_code', 'P000002')->value('value_default') ?: '0101'),
                'invoice_ubl_version' => (string) (Parameter::query()->where('parameter_code', 'P000003')->value('value_default') ?: '2.1'),
                'invoice_legend_code' => '1000',
                'invoice_legend_description' => $numberLetters->convertToLetter($amount, 'SOLES'),
                'invoice_mto_oper_unaffected' => 0,
                'invoice_mto_oper_exonerated' => 0,
                'invoice_mto_oper_taxed' => $documentType->sunat_id === '80' ? 0 : $taxableBase,
                'invoice_mto_igv' => $documentType->sunat_id === '80' ? 0 : $taxAmount,
                'invoice_icbper' => 0,
                'invoice_total_taxes' => $documentType->sunat_id === '80' ? 0 : $taxAmount,
                'invoice_value_sale' => $documentType->sunat_id === '80' ? $amount : $taxableBase,
                'invoice_subtotal' => $documentType->sunat_id === '80' ? $amount : $taxableBase + $taxAmount,
                'invoice_mto_imp_sale' => $amount,
                'invoice_rounding' => '0.00',
                'forma_pago' => 'Contado',
                'status_pay' => true,
                'client_type_doc' => $client->document_type_id,
                'client_number' => $client->number,
                'client_rzn_social' => $client->full_name,
                'client_address' => $client->address,
                'client_ubigeo_code' => $client->ubigeo,
                'client_ubigeo_description' => $client->ubigeo_description,
                'client_phone' => $client->telephone,
                'client_email' => $client->email,

                'invoice_status' => $documentType->sunat_id === '80' ? 'No aplica' : 'Pendiente',
                'overall_total' => $amount,
                'status' => true,

            ]);
            $description = 'Servicio de acceso y administración de organización: '.$organization->name;
            $product = Product::query()->where('interne', 'ORG-SUBSCRIPTION')->first();
            if (! $product) {
                $product = Product::create([
                    'usine' => 'ORG-SUBSCRIPTION',
                    'interne' => 'ORG-SUBSCRIPTION',
                    'description' => $description,
                    'purchase_prices' => $amount,
                    'sale_prices' => json_encode(['high' => $amount, 'low' => $amount, 'medium' => $amount]),
                    'sizes' => null,
                    'stock_min' => 0,
                    'stock' => 0,
                    'presentations' => false,
                    'is_product' => false,
                    'type_sale_affectation_id' => '10',
                    'type_purchase_affectation_id' => '10',
                    'type_unit_measure_id' => 'ZZ',
                    'status' => true,
                    'icbper' => false,
                ]);
            }
            $product->forceFill(['description' => $description, 'sale_prices' => json_encode(['high' => $amount, 'low' => $amount, 'medium' => $amount])])->save();
            $saleProduct = [
                'id' => $product->id,
                'usine' => $product->usine,
                'interne' => $product->interne,
                'description' => $description,
                'price' => $amount,
                'discount' => 0,
                'quantity' => 1,
                'total' => $amount,
                'unit_type' => 'ZZ',
                'afe_igv' => $documentType->sunat_id === '80' ? '30' : '10',
                'is_product' => false,
            ];
            $item = DB::table('sale_products')->insertGetId([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'product' => json_encode($product->fresh()->toArray()),
                'saleProduct' => json_encode($saleProduct),
                'price' => $amount,
                'discount' => 0,
                'quantity' => 1,
                'total' => $amount,
                'entity_name_product' => Product::class,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('sale_document_items')->insert([
                'document_id' => $document->id,
                'product_id' => $product->id,
                'cod_product' => $product->interne,
                'decription_product' => $description,
                'unit_type' => 'ZZ',
                'quantity' => 1,
                'mto_base_igv' => $taxableBase,
                'percentage_igv' => $igvRate,
                'igv' => $taxAmount,
                'total_tax' => $taxAmount,
                'type_afe_igv' => $documentType->sunat_id === '80' ? '30' : '10',
                'icbper' => 0,
                'factor_icbper' => 0,
                'mto_value_sale' => $taxableBase,
                'mto_value_unit' => $taxableBase,
                'mto_price_unit' => $amount,
                'price_sale' => $amount,
                'mto_total' => $amount,
                'mto_discount' => 0,
                'json_discounts' => json_encode([]),
                'entity_name_product' => Product::class,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $document->invoice_mto_oper_unaffected = $documentType->sunat_id === '80' ? $amount : 0;
            $document->invoice_mto_oper_taxed = $documentType->sunat_id === '80' ? 0 : $taxableBase;
            $document->invoice_mto_igv = $documentType->sunat_id === '80' ? 0 : $taxAmount;
            $document->invoice_total_taxes = $documentType->sunat_id === '80' ? 0 : $taxAmount;
            $document->invoice_value_sale = $amount;
            $document->save();
            $series->increment('number');
            $balance = round((float) $organization->prepaid_balance + $amount, 2);
            $renewalBase = $organization->status === 'active' && $organization->access_until && now()->toDateString() <= $organization->access_until
                ? \Illuminate\Support\Carbon::parse($organization->access_until)
                : now();
            DB::table('socialevent_organizations')->where('id', $organization->id)->update([
                'prepaid_balance' => $balance,
                'status' => 'active',
                'access_until' => $renewalBase->addDays((int) ($organization->trial_days ?: 30))->toDateString(),
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
            DB::table('socialevent_organization_ledger')->insert([
                'organization_id' => $organization->id,
                'user_id' => $request->user()->id,
                'entry_type' => 'balance_topup',
                'amount' => $amount,
                'balance_after' => $balance,
                'payment_method' => (string) $validated['payment_method_id'],
                'reference' => $validated['reference'] ?? (string) $document->id,
                'description' => 'Pago de acceso de organización; comprobante '.$document->invoice_serie.'-'.$document->invoice_correlative,
                'metadata' => json_encode(['sale_id' => $sale->id, 'document_id' => $document->id, 'sale_product_id' => $item, 'document_type_id' => $documentType->id]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->audit((int) $organization->id, $request->user()->id, 'organization_payment', 'Pago registrado por administrador.', [
                'amount' => $amount,
                'document_number' => $document->invoice_serie.'-'.$document->invoice_correlative,
                'sale_id' => $sale->id,
                'document_id' => $document->id,
                'document_type' => $documentType->description,
            ]);

            return ['document' => $document, 'sale' => $sale];
        });

        $document = $saleDocument['document'];
        if (in_array($documentType->sunat_id, ['01', '03'], true)) {
            $result = app(\Modules\Sales\Http\Controllers\SaleDocumentController::class)
                ->sendSunatDocument((int) $document->id, $documentType->sunat_id)
                ->getData(true);
            if (! ($result['success'] ?? false)) {
                return back()->with('error', 'El pago quedó registrado con el comprobante '.$document->invoice_serie.'-'.$document->invoice_correlative.', pero el envío electrónico falló: '.($result['message'] ?? 'Error al enviar a SUNAT.'));
            }
        }

        app(\Modules\Treasury\Services\TreasuryHooks::class)::recordSaleIncomes($saleDocument['sale']);

        return back()->with('success', 'Pago y comprobante registrados: '.$document->invoice_serie.'-'.$document->invoice_correlative);
    }

    public function updateAlertSettings(Request $request)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'alerts_enabled' => 'required|boolean',
            'email_enabled' => 'required|boolean',
            'system_enabled' => 'required|boolean',
            'days_before' => 'required|integer|min:1|max:60',
            'overdue_after' => 'required|integer|min:0|max:90',
            'repeat_every' => 'required|integer|min:0|max:30',
            'time' => 'required|date_format:H:i',
        ]);
        $settings = DB::table('socialevent_organization_settings')->first();
        if (! $settings) {
            abort(500, 'No se encontró la configuración comercial de organizaciones.');
        }
        $current = json_decode($settings->alert_settings ?? '{}', true) ?: [];
        $current['billing'] = $validated;
        DB::table('socialevent_organization_settings')->where('id', $settings->id)->update([
            'alert_settings' => json_encode($current),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Programación de avisos guardada.');
    }

    public function sendOrganizationAlert(Request $request, int $organizationId)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'type' => 'required|in:upcoming,due,overdue,custom',
            'message' => 'required|string|max:1000',
            'send_email' => 'required|boolean',
            'send_system' => 'required|boolean',
        ]);
        $organization = DB::table('socialevent_organizations')->where('id', $organizationId)->where('is_legacy', false)->whereNull('deleted_at')->first();
        abort_unless($organization, 404);
        abort_unless($validated['send_email'] || $validated['send_system'], 422, 'Selecciona al menos un canal de aviso.');
        $emails = DB::table('socialevent_organization_users')
            ->join('users', 'users.id', '=', 'socialevent_organization_users.user_id')
            ->where('socialevent_organization_users.organization_id', $organizationId)
            ->whereNotNull('users.email')->pluck('users.email')->unique()->values();
        if ($validated['send_system'] && ! DB::table('socialevent_organization_users')->where('organization_id', $organizationId)->exists()) {
            return back()->withErrors(['send_system' => 'No hay usuarios asociados para recibir avisos en el sistema.']);
        }
        if ($validated['send_email'] && $emails->isEmpty()) {
            return back()->withErrors(['send_email' => 'No hay correos asociados para recibir el aviso.']);
        }
        $created = 0;
        $delivered = 0;
        if ($validated['send_system']) {
            foreach (DB::table('socialevent_organization_users')->where('organization_id', $organizationId)->pluck('user_id') as $userId) {
                DB::table('socialevent_organization_alerts')->insert([
                    'organization_id' => $organizationId,
                    'user_id' => $userId,
                    'type' => $validated['type'],
                    'message' => $validated['message'],
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;
            }
        }
        if ($validated['send_email']) {
            foreach ($emails as $email) {
                Mail::raw($validated['message'], function ($mail) use ($email, $organization) {
                    $mail->to($email)->subject('Aviso de pago: '.$organization->name);
                });
                $delivered++;
            }
        }
        return back()->with('success', "Avisos en sistema: {$created}; correos enviados: {$delivered}.");
    }

    public function updateDefaults(Request $request)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'trial_days' => 'required|integer|min:1|max:3660',
            'default_match_rate' => 'required|numeric|min:0|max:999999.99',
            'seat_price' => 'required|numeric|min:0|max:999999.99',
        ]);

        DB::table('socialevent_organization_settings')->updateOrInsert(
            ['id' => 1],
            [...$validated, 'updated_at' => now(), 'created_at' => now()]
        );
        $legacyOrganizationId = app(SportsOrganizationService::class)->legacyOrganizationId();
        if ($legacyOrganizationId) {
            $this->audit($legacyOrganizationId, $request->user()->id, 'commercial_defaults_updated', 'Actualización de los valores comerciales predeterminados.', $validated);
        }

        return back()->with('success', 'Configuración comercial guardada.');
    }

    /**
     * El superadministrador regala espacios sin cobro. Queda registrado en el
     * historial con método "admin" para distinguirlo de una compra pagada.
     */
    public function grantSeats(Request $request, int $organizationId)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
            'reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($organizationId, $validated, $request) {
            $organization = $this->lockOrganization($organizationId);
            abort_if($organization->seat_limit === null, 422, 'Esta organización no administra espacios.');

            $quantity = (int) $validated['quantity'];
            DB::table('socialevent_organizations')->where('id', $organization->id)->update([
                'seat_limit' => (int) $organization->seat_limit + $quantity,
                'updated_at' => now(),
            ]);
            DB::table('socialevent_organization_seat_purchases')->insert([
                'organization_id' => $organization->id,
                'purchased_by' => $request->user()->id,
                'external_reference' => (string) \Illuminate\Support\Str::uuid(),
                'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
                'payment_method' => 'admin',
                'status' => 'granted',
                'quantity' => $quantity,
                'unit_price' => 0,
                'total' => 0,
                'credited_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->audit((int) $organization->id, $request->user()->id, 'seats_granted', 'Espacios otorgados por el superadministrador.', [
                'quantity' => $quantity,
                'reason' => $validated['reason'] ?? null,
            ]);
        });

        return back()->with('success', 'Espacios agregados a la organización.');
    }

    public function storeRate(Request $request)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0|max:999999.99',
            'description' => 'nullable|string|max:255',
        ]);

        DB::table('socialevent_match_rates')->insert([
            ...$validated,
            'created_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Tarifa creada.');
    }

    public function updateOrganization(Request $request, int $organizationId)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'match_rate' => 'required|numeric|min:0|max:999999.99',
            'status' => 'required|in:trial,active,sponsored,suspended',
            'trial_days' => 'required|integer|min:1|max:3660',
            'access_until' => 'nullable|date',
            'trial_ends_at' => 'nullable|date',
            'credit_amount' => 'nullable|numeric|min:0|max:99999999.99',
            'reason' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($request, $organizationId, $validated) {
            $organization = DB::table('socialevent_organizations')->where('id', $organizationId)->lockForUpdate()->first();
            abort_unless($organization, 404);
            abort_if($organization->is_legacy, 422, 'La organización histórica no se administra desde este panel.');

            $now = now();
            $changes = [
                'match_rate' => $validated['match_rate'],
                'status' => $validated['status'],
                'trial_days' => $validated['trial_days'],
                'access_until' => $validated['access_until'] ?? null,
                'updated_at' => $now,
            ];
            if ($validated['status'] === 'trial') {
                // "Fin de prueba" manda cuando viene con fecha: es la única forma de
                // vencer la prueba hoy mismo (por ejemplo, para comprobar el modo
                // solo lectura). Sin fecha se conserva el cálculo por "Días de prueba",
                // que reinicia la prueba desde ahora.
                $changes['trial_ends_at'] = filled($validated['trial_ends_at'] ?? null)
                    ? \Illuminate\Support\Carbon::parse($validated['trial_ends_at'])->endOfDay()
                    : $now->copy()->addDays((int) $validated['trial_days']);
                $changes['trial_started_at'] = $changes['trial_ends_at']->copy()->subDays((int) $validated['trial_days']);
            }

            $credit = round((float) ($validated['credit_amount'] ?? 0), 2);
            $balance = round((float) $organization->prepaid_balance + $credit, 2);
            if ($credit > 0) {
                $changes['prepaid_balance'] = $balance;
            }
            DB::table('socialevent_organizations')->where('id', $organizationId)->update($changes);

            if ($credit > 0) {
                DB::table('socialevent_organization_ledger')->insert([
                    'organization_id' => $organizationId,
                    'user_id' => $request->user()->id,
                    'entry_type' => 'balance_topup',
                    'amount' => $credit,
                    'balance_after' => $balance,
                    'payment_method' => 'manual',
                    'description' => $validated['reason'],
                    'metadata' => json_encode(['status' => $validated['status'], 'match_rate' => $validated['match_rate']]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->audit($organizationId, $request->user()->id, $credit > 0 ? 'organization_commercial_settings_and_credit' : 'organization_commercial_settings', $validated['reason'], [
                'before' => ['status' => $organization->status, 'match_rate' => $organization->match_rate, 'prepaid_balance' => $organization->prepaid_balance],
                'after' => ['status' => $validated['status'], 'match_rate' => $validated['match_rate'], 'prepaid_balance' => $balance],
                'trial_days' => $validated['trial_days'], 'access_until' => $validated['access_until'] ?? null,
                'trial_ends_at' => $changes['trial_ends_at'] ?? $organization->trial_ends_at,
            ]);
        });

        return back()->with('success', 'Organización actualizada; el cambio quedó auditado.');
    }

    /**
     * Bloquea la cuenta (por ejemplo, por falta de pago). Queda en solo lectura para la organización.
     */
    public function block(Request $request, int $organizationId)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate(['reason' => 'required|string|max:1000']);

        DB::transaction(function () use ($request, $organizationId, $validated) {
            $organization = $this->lockOrganization($organizationId);

            DB::table('socialevent_organizations')->where('id', $organizationId)->update([
                'status' => 'suspended',
                'updated_at' => now(),
            ]);

            $this->audit($organizationId, $request->user()->id, 'organization_blocked', $validated['reason'], [
                'before' => ['status' => $organization->status],
                'after' => ['status' => 'suspended'],
            ]);
        });

        return back()->with('success', 'Cuenta bloqueada; el cambio quedó auditado.');
    }

    /**
     * Desbloquea la cuenta. Hay que indicar hasta cuándo tiene acceso, salvo que aún esté en prueba vigente.
     */
    public function unblock(Request $request, int $organizationId)
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'access_until' => 'nullable|date|after:today',
        ]);

        DB::transaction(function () use ($request, $organizationId, $validated) {
            $organization = $this->lockOrganization($organizationId);

            $trialVigente = $organization->trial_ends_at && now()->lte($organization->trial_ends_at);
            if (empty($validated['access_until']) && ! $trialVigente) {
                abort(422, 'Indica hasta cuándo tendrá acceso la organización.');
            }

            $after = empty($validated['access_until'])
                ? ['status' => 'trial', 'access_until' => null]
                : ['status' => 'active', 'access_until' => $validated['access_until']];

            DB::table('socialevent_organizations')->where('id', $organizationId)->update([
                ...$after,
                'updated_at' => now(),
            ]);

            $this->audit($organizationId, $request->user()->id, 'organization_unblocked', $validated['reason'], [
                'before' => ['status' => $organization->status, 'access_until' => $organization->access_until],
                'after' => $after,
            ]);
        });

        return back()->with('success', 'Cuenta desbloqueada; el cambio quedó auditado.');
    }

    private function alertSettings(): array
    {
        $settings = DB::table('socialevent_organization_settings')->first();
        $stored = json_decode($settings->alert_settings ?? '{}', true) ?: [];
        $billing = $stored['billing'] ?? [];

        return array_merge([
            'alerts_enabled' => false,
            'email_enabled' => false,
            'system_enabled' => true,
            'days_before' => 7,
            'overdue_after' => 1,
            'repeat_every' => 7,
            'time' => '09:00',
        ], $billing);
    }

    private function resolveOrganizationClient(object $organization, string $sunatDocumentType): Person
    {
        if ($sunatDocumentType === '01' && ! empty($organization->ruc)) {
            $number = $organization->ruc;
            $documentTypeId = '6';
            $name = $organization->name;
            $owner = DB::table('socialevent_organization_users')
                ->join('users', 'users.id', '=', 'socialevent_organization_users.user_id')
                ->where('socialevent_organization_users.organization_id', $organization->id)
                ->where('socialevent_organization_users.role', 'owner')
                ->select('users.email', 'users.telefono')
                ->first();
            $email = $owner->email ?? null;
            $telephone = $owner->telefono ?? null;
            $address = $organization->address ?? null;
            $ubigeo = $organization->ubigeo ?? null;
        } else {
            $owner = DB::table('socialevent_organization_users')
                ->join('users', 'users.id', '=', 'socialevent_organization_users.user_id')
                ->where('socialevent_organization_users.organization_id', $organization->id)
            ->where('socialevent_organization_users.role', 'owner')
            ->select('users.*')
                ->first();
            if (! $owner || empty($owner->numero_identidad)) {
                abort(422, 'El titular debe tener documento de identidad registrado para emitir el comprobante.');
            }
            $number = $owner->numero_identidad;
            $documentTypeId = $owner->tipo_identidad ?: '1';
            $name = trim(implode(' ', array_filter([$owner->name, $owner->apellido_paterno, $owner->apellido_materno])));
            $email = $owner->email;
            $telephone = $owner->telefono;
            $address = $organization->address ?? null;
            $ubigeo = $organization->ubigeo ?? null;
        }

        $person = Person::query()->where('number', $number)->first();
        if ($person) {
            $person->fill([
                'document_type_id' => $documentTypeId,
                'full_name' => $name,
                'short_name' => $name,
                'email' => $email,
                'telephone' => $telephone,
                'address' => $address,
                'ubigeo' => $ubigeo,
                'is_client' => true,
            ]);
            $person->save();

            return $person;
        }

        return Person::create([
            'document_type_id' => $documentTypeId,
            'full_name' => $name,
            'short_name' => $name,
            'number' => $number,
            'email' => $email,
            'telephone' => $telephone,
            'address' => $address,
            'ubigeo' => $ubigeo,
            'is_client' => true,
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(app(SportsOrganizationService::class)->canAdministerOrganizations($request->user()), 403);
    }

    private function lockOrganization(int $organizationId): object
    {
        $organization = DB::table('socialevent_organizations')->where('id', $organizationId)->lockForUpdate()->first();
        abort_unless($organization, 404);
        abort_if($organization->is_legacy, 422, 'La organización histórica no se administra desde este panel.');

        return $organization;
    }

    private function audit(int $organizationId, int $userId, string $action, string $reason, array $changes): void
    {
        DB::table('socialevent_organization_audits')->insert([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'action' => $action,
            'reason' => $reason,
            'changes' => json_encode($changes),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
