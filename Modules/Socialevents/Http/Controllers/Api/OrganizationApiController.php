<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Socialevents\Services\OrganizationTeamService;
use Modules\Socialevents\Services\SportsOrganizationService;

/**
 * Estado de cuenta de la organización autenticada (Fase 3 móvil).
 *
 * La organización sale siempre del token; el cliente no la envía. La vista de
 * pagos se mantiene accesible aunque la organización esté vencida o suspendida:
 * es justo el caso en el que el organizador necesita recargar para reactivarla.
 *
 * La recarga no cobra desde la app: devuelve la URL del checkout web, donde vive
 * el formulario de Mercado Pago. La app nunca ve datos de tarjeta.
 */
class OrganizationApiController extends Controller
{
    private const MINIMUM_AMOUNT = 10;

    public function __construct(
        private SportsOrganizationService $sports,
        private OrganizationTeamService $team
    ) {}

    /**
     * Plan, vigencia, saldo y tarifa por partido de la organización.
     */
    public function show(Request $request): JsonResponse
    {
        $organization = $this->currentOrganization($request);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        return response()->json([
            'success' => true,
            'message' => 'Estado de cuenta obtenido correctamente',
            'data' => [
                'organization' => $this->organizationPayload($organization),
                'access' => $this->sports->accessSummary($request->user()),
                'balance' => round((float) $organization->prepaid_balance, 2),
                'match_rate' => round((float) $organization->match_rate, 2),
                'seats' => $this->seatsPayload((int) $organization->id),
                'checkout' => $this->checkoutPayload(),
            ],
        ]);
    }

    /**
     * Recargas y pagos de la organización, del más reciente al más antiguo.
     */
    public function payments(Request $request): JsonResponse
    {
        $organization = $this->currentOrganization($request);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        return response()->json([
            'success' => true,
            'message' => 'Movimientos obtenidos correctamente',
            'data' => $this->sports->paymentHistory($request->user()),
            'summary' => [
                'balance' => round((float) $organization->prepaid_balance, 2),
                'credited_total' => $this->creditedTotal((int) $organization->id),
            ],
        ]);
    }

    /**
     * Personas con acceso a la organización del token (solo lectura).
     *
     * La lista sale de `users.socialevent_organization_id`, que es la fuente que
     * usa el login, complementada con la tabla de miembros cuando existe la fila.
     * Altas y bajas de usuarios se siguen haciendo en la web: la app no invita.
     */
    public function users(Request $request): JsonResponse
    {
        $organization = $this->currentOrganization($request);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        $hasMembers = Schema::hasTable('socialevent_organization_users');

        $query = DB::table('users')
            ->where('users.socialevent_organization_id', $organization->id);

        if ($hasMembers) {
            $query->leftJoin('socialevent_organization_users as members', function ($join) use ($organization) {
                $join->on('members.user_id', '=', 'users.id')
                    ->where('members.organization_id', '=', $organization->id);
            })->select([
                'users.id',
                'users.name',
                'users.email',
                'users.telefono',
                'users.status',
                'users.created_at',
                'users.socialevent_organization_role as user_role',
                'members.role as member_role',
                'members.joined_at as member_joined_at',
            ]);
        } else {
            $query->select([
                'users.id',
                'users.name',
                'users.email',
                'users.telefono',
                'users.status',
                'users.created_at',
                'users.socialevent_organization_role as user_role',
            ]);
        }

        $rows = $query
            ->orderByRaw("CASE WHEN users.socialevent_organization_role = 'owner' THEN 0 ELSE 1 END")
            ->orderBy('users.name')
            ->get();

        $users = $rows->map(function ($row) use ($request) {
            $role = (string) ($row->user_role ?: ($row->member_role ?? 'member'));

            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'email' => (string) $row->email,
                'phone' => $row->telefono ? (string) $row->telefono : null,
                'role' => $role,
                'role_label' => $role === 'owner' ? 'Propietario' : 'Miembro',
                'is_you' => (int) $row->id === (int) $request->user()->id,
                'active' => (bool) ($row->status ?? true),
                'joined_at' => $row->member_joined_at ?? $row->created_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Usuarios de la organización obtenidos correctamente',
            'data' => [
                'organization' => $this->organizationPayload($organization),
                'users' => $users,
                'summary' => [
                    'total' => $users->count(),
                    'owners' => $users->where('role', 'owner')->count(),
                    'members' => $users->where('role', '!=', 'owner')->count(),
                ],
                // La app informa el límite real, pero las altas, bajas y compras
                // siguen en la web para no duplicar la matriz de permisos.
                'seats' => $this->seatsPayload((int) $organization->id),
            ],
        ]);
    }

    /**
     * Espacios reales de la organización para la app. `can_invite` sigue en
     * false: invitar y comprar se hacen en la web.
     *
     * @return array<string, mixed>
     */
    private function seatsPayload(int $organizationId): array
    {
        $summary = $this->team->seatSummary($organizationId);
        // Cuentas anteriores a la tabla de miembros no tienen fila de cupo: la
        // app sigue mostrando el total de usuarios vinculados a la organización.
        $hasMemberships = Schema::hasTable('socialevent_organization_users')
            && DB::table('socialevent_organization_users')->where('organization_id', $organizationId)->exists();
        if (! $hasMemberships) {
            $summary['used'] = DB::table('users')
                ->where('socialevent_organization_id', $organizationId)
                ->where('status', 1)
                ->count();
            $summary['available'] = $summary['limit'] === null
                ? null
                : max(0, $summary['limit'] - $summary['used']);
        }
        $seatPrice = Schema::hasTable('socialevent_organization_settings')
            ? (float) (DB::table('socialevent_organization_settings')->value('seat_price') ?? 1)
            : 1.0;

        return [
            'used' => $summary['used'],
            'limit' => $summary['limit'],
            'available' => $summary['available'],
            'unlimited' => $summary['unlimited'],
            'seat_price' => $seatPrice,
            'can_invite' => false,
            'note' => $summary['limit'] === null
                ? 'El equipo se administra desde la web.'
                : 'Compra espacios y administra al equipo desde la web, en Mi plan.',
        ];
    }

    /**
     * Devuelve la URL del checkout web. No crea un cobro ni guarda datos de pago:
     * el monto solo precarga el formulario de la web.
     */
    public function topUpIntent(Request $request): JsonResponse
    {
        $organization = $this->currentOrganization($request);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:'.self::MINIMUM_AMOUNT.'|max:999999.99',
        ]);

        $query = ['origen' => 'app'];
        if (isset($validated['amount'])) {
            $query['amount'] = round((float) $validated['amount'], 2);
        }

        return response()->json([
            'success' => true,
            'message' => 'Checkout de recarga preparado',
            'data' => $this->checkoutPayload($query),
        ]);
    }

    /**
     * Organización de la sesión o el error que la app ya sabe interpretar.
     */
    private function currentOrganization(Request $request)
    {
        $organizationId = $this->sports->organizationId($request->user());

        if (! $organizationId || ! Schema::hasTable('socialevent_organizations')) {
            return response()->json([
                'success' => false,
                'code' => 'ORG_REQUIRED',
                'message' => 'Esta cuenta no tiene una organización asociada. Crea tu cuenta desde la web.',
            ], 403);
        }

        $organization = DB::table('socialevent_organizations')
            ->where('id', $organizationId)
            ->whereNull('deleted_at')
            ->first();

        if (! $organization) {
            return response()->json([
                'success' => false,
                'code' => 'ORG_REQUIRED',
                'message' => 'Esta cuenta no tiene una organización asociada. Crea tu cuenta desde la web.',
            ], 403);
        }

        // El mismo control que usa la web: una cuenta suspendida conserva el
        // acceso a sus pagos para poder regularizarse.
        $this->sports->assertCanAccessPayments($request->user());

        return $organization;
    }

    /** @param object $organization */
    private function organizationPayload($organization): array
    {
        return [
            'id' => (int) $organization->id,
            'name' => (string) $organization->name,
            'status' => (string) $organization->status,
            'status_label' => $this->statusLabel((string) $organization->status),
            'is_legacy' => (bool) $organization->is_legacy,
            'trial_ends_at' => $organization->trial_ends_at
                ? \Illuminate\Support\Carbon::parse($organization->trial_ends_at)->toDateString()
                : null,
            'access_until' => $organization->access_until
                ? \Illuminate\Support\Carbon::parse($organization->access_until)->toDateString()
                : null,
        ];
    }

    private function statusLabel(string $status): string
    {
        return [
            'trial' => 'Prueba gratuita',
            'active' => 'Activo',
            'sponsored' => 'Cortesía / patrocinio',
            'suspended' => 'Bloqueado',
        ][$status] ?? $status;
    }

    /**
     * Enlace al checkout web y datos que la app necesita para no ofrecer un
     * pago que la web no puede procesar.
     *
     * @param array<string, mixed> $query
     * @return array{available: bool, url: string, minimum_amount: int}
     */
    private function checkoutPayload(array $query = ['origen' => 'app']): array
    {
        return [
            'available' => (bool) (config('services.mercadopago.token') && config('services.mercadopago.key')),
            'url' => route('even_organizations.topup.index', $query),
            'minimum_amount' => self::MINIMUM_AMOUNT,
        ];
    }

    private function creditedTotal(int $organizationId): float
    {
        if (! Schema::hasTable('socialevent_organization_topups')) {
            return 0.0;
        }

        return round((float) DB::table('socialevent_organization_topups')
            ->where('organization_id', $organizationId)
            ->whereIn('status', ['credited', 'approved'])
            ->sum('amount'), 2);
    }
}
