<?php

namespace Modules\Socialevents\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Socialevents\Entities\SocialeventOrganizationUser;
use Modules\Socialevents\Support\OrganizationCapabilities;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class OrganizationTeamService
{
    public function __construct(
        private SportsOrganizationService $sports
    ) {}

    public function isOwner($user): bool
    {
        if (! $user) {
            return false;
        }

        if ((string) ($user->socialevent_organization_role ?? '') === 'owner') {
            return true;
        }

        if (! Schema::hasTable('socialevent_organization_users')) {
            return false;
        }

        return DB::table('socialevent_organization_users')
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->exists();
    }

    public function canManage($user): bool
    {
        return $this->sports->isSuperAdmin($user) || $this->isOwner($user);
    }

    public function assertCanManage($user): void
    {
        abort_unless($this->canManage($user), 403, 'Solo el propietario de la organización puede administrar al equipo.');
    }

    /**
     * Espacios de la organización. `limit` null significa sin límite (legado).
     *
     * @return array{used: int, limit: ?int, available: ?int, unlimited: bool}
     */
    public function seatSummary(int $organizationId): array
    {
        $organization = Schema::hasTable('socialevent_organizations')
            ? DB::table('socialevent_organizations')->where('id', $organizationId)->first()
            : null;

        $query = DB::table('socialevent_organization_users')->where('organization_id', $organizationId);
        if (Schema::hasColumn('socialevent_organization_users', 'status')) {
            $query->where('status', 'active');
        }
        $used = (int) $query->count();

        $limit = $organization && $organization->seat_limit !== null && ! $organization->is_legacy
            ? (int) $organization->seat_limit
            : null;

        return [
            'used' => $used,
            'limit' => $limit,
            'available' => $limit === null ? null : max(0, $limit - $used),
            'unlimited' => $limit === null,
        ];
    }

    /**
     * Miembros de la organización, incluidos los inactivos para poder
     * reactivarlos. Los datos salen de la tabla de miembros y no solo de
     * users.socialevent_organization_id, que se limpia al desactivar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function members(int $organizationId): array
    {
        if (! Schema::hasTable('socialevent_organization_users')) {
            return [];
        }

        return DB::table('socialevent_organization_users as members')
            ->join('users', 'users.id', '=', 'members.user_id')
            ->where('members.organization_id', $organizationId)
            ->orderByRaw("CASE WHEN members.role = 'owner' THEN 0 ELSE 1 END")
            ->orderBy('users.name')
            ->get([
                'users.id',
                'users.name',
                'users.apellido_paterno',
                'users.apellido_materno',
                'users.email',
                'users.telefono',
                'users.numero_identidad',
                'users.tipo_identidad',
                'members.role',
                'members.profile_key',
                'members.capabilities',
                'members.status',
                'members.joined_at',
                'members.permissions_updated_at',
            ])
            ->map(function ($member) {
                $capabilities = json_decode((string) ($member->capabilities ?? '[]'), true) ?: [];

                return [
                    'id' => (int) $member->id,
                    'name' => trim(implode(' ', array_filter([
                        $member->name,
                        $member->apellido_paterno,
                        $member->apellido_materno,
                    ]))) ?: (string) $member->name,
                    'email' => (string) $member->email,
                    'phone' => $member->telefono ? (string) $member->telefono : null,
                    'document' => $member->numero_identidad ? (string) $member->numero_identidad : null,
                    'role' => (string) $member->role,
                    'is_owner' => (string) $member->role === 'owner',
                    'profile_key' => $member->profile_key ? (string) $member->profile_key : null,
                    'profile_label' => OrganizationCapabilities::labelFor($capabilities),
                    'capabilities' => $capabilities,
                    'status' => (string) ($member->status ?: 'active'),
                    'joined_at' => $member->joined_at,
                    'permissions_updated_at' => $member->permissions_updated_at,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array{name: string, email: string, password: string, telefono?: ?string, tipo_identidad?: ?string, numero_identidad?: ?string, capabilities: array<int, string>} $data
     */
    public function createMember($actor, int $organizationId, array $data): User
    {
        $this->assertCanManage($actor);
        $keys = array_values(array_unique($data['capabilities'] ?? []));
        OrganizationCapabilities::assertValidKeys($keys);

        return DB::transaction(function () use ($actor, $organizationId, $data, $keys) {
            $organization = $this->lockOrganization($organizationId);
            $this->assertSeatAvailable($organization);

            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower(trim($data['email'])),
                'password' => Hash::make($data['password']),
                'telefono' => $data['telefono'] ?? null,
                'tipo_identidad' => $data['tipo_identidad'] ?? null,
                'numero_identidad' => $data['numero_identidad'] ?? null,
                'status' => 1,
            ]);
            $user->forceFill([
                'socialevent_organization_id' => $organizationId,
                'socialevent_organization_role' => 'member',
            ])->save();

            $this->upsertMembership($user, $organizationId, $keys, $actor, 'active');
            $this->syncUserPermissions($user, $keys);
            $this->audit($organizationId, $actor->id, 'organization_user_created', 'Alta de usuario por el propietario.', [
                'new_user_id' => $user->id,
                'email' => $user->email,
                'capabilities' => $keys,
            ]);

            return $user;
        });
    }

    /**
     * @param array<int, string> $keys
     */
    public function updateCapabilities($actor, int $organizationId, int $userId, array $keys): User
    {
        $this->assertCanManage($actor);
        $keys = array_values(array_unique($keys));
        OrganizationCapabilities::assertValidKeys($keys);

        return DB::transaction(function () use ($actor, $organizationId, $userId, $keys) {
            $membership = SocialeventOrganizationUser::query()
                ->where('organization_id', $organizationId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();
            abort_if($membership->role === 'owner', 422, 'El propietario conserva el acceso completo.');

            $user = User::query()->findOrFail($userId);
            abort_if($this->sports->isSuperAdmin($user), 422, 'No se puede limitar a un administrador del sistema.');

            $before = json_decode((string) ($membership->capabilities ?? '[]'), true) ?: [];
            $membership->fill([
                'profile_key' => $this->profileKey($keys),
                'capabilities' => $keys,
                'permissions_updated_at' => now(),
            ])->save();
            $this->syncUserPermissions($user, $keys);
            $this->audit($organizationId, $actor->id, 'organization_user_permissions_updated', 'Cambio de tareas del usuario.', [
                'user_id' => $userId,
                'before' => $before,
                'after' => $keys,
            ]);

            return $user;
        });
    }

    public function deactivate($actor, int $organizationId, int $userId): void
    {
        $this->assertCanManage($actor);
        abort_if((int) $actor->id === $userId, 422, 'No puedes desactivar tu propia cuenta.');

        DB::transaction(function () use ($actor, $organizationId, $userId) {
            $membership = SocialeventOrganizationUser::query()
                ->where('organization_id', $organizationId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();
            abort_if($membership->role === 'owner', 422, 'No puedes desactivar al propietario de la organización.');

            $user = User::query()->findOrFail($userId);
            abort_if($this->sports->isSuperAdmin($user), 422, 'No se puede desactivar a un administrador del sistema.');

            $membership->fill([
                'status' => 'inactive',
                'capabilities' => [],
                'profile_key' => null,
                'permissions_updated_at' => now(),
            ])->save();
            $this->removeUserAccess($user);
            $user->forceFill([
                'socialevent_organization_id' => null,
                'socialevent_organization_role' => null,
            ])->save();
            $this->audit($organizationId, $actor->id, 'organization_user_deactivated', 'Baja de usuario de la organización.', [
                'user_id' => $userId,
                'email' => $user->email,
            ]);
        });
    }

    /**
     * @param array<int, string> $keys
     */
    public function reactivate($actor, int $organizationId, int $userId, array $keys): User
    {
        $this->assertCanManage($actor);
        $keys = array_values(array_unique($keys));
        OrganizationCapabilities::assertValidKeys($keys);

        return DB::transaction(function () use ($actor, $organizationId, $userId, $keys) {
            $organization = $this->lockOrganization($organizationId);
            $membership = SocialeventOrganizationUser::query()
                ->where('organization_id', $organizationId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();
            abort_if($membership->status === 'active', 422, 'El usuario ya está activo.');
            $this->assertSeatAvailable($organization);

            $user = User::query()->findOrFail($userId);
            $user->forceFill([
                'socialevent_organization_id' => $organizationId,
                'socialevent_organization_role' => 'member',
            ])->save();
            $this->upsertMembership($user, $organizationId, $keys, $actor, 'active');
            $this->syncUserPermissions($user, $keys);
            $this->audit($organizationId, $actor->id, 'organization_user_reactivated', 'Reactivación de usuario de la organización.', [
                'user_id' => $userId,
                'capabilities' => $keys,
            ]);

            return $user;
        });
    }

    /**
     * @param array<int, string> $keys
     */
    private function upsertMembership(User $user, int $organizationId, array $keys, $actor, string $status): void
    {
        SocialeventOrganizationUser::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'organization_id' => $organizationId,
                'role' => 'member',
                'profile_key' => $this->profileKey($keys),
                'capabilities' => $keys,
                'status' => $status,
                'permissions_updated_at' => now(),
                'invited_by' => $actor?->id,
                'joined_at' => now(),
            ]
        );
    }

    /** @param array<int, string> $keys */
    private function syncUserPermissions(User $user, array $keys): void
    {
        $role = Role::firstOrCreate(['name' => 'sports_operator', 'guard_name' => 'web']);
        if ($user->hasRole('sports_organizer')) {
            $user->removeRole('sports_organizer');
        }
        $user->assignRole($role);

        $permissions = OrganizationCapabilities::permissionsFor($keys);
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }
        $user->syncPermissions($permissions);
    }

    private function removeUserAccess(User $user): void
    {
        foreach (['sports_organizer', 'sports_operator'] as $roleName) {
            if ($user->hasRole($roleName)) {
                $user->removeRole($roleName);
            }
        }
        $user->syncPermissions([]);
    }

    /** @param array<int, string> $keys */
    private function profileKey(array $keys): string
    {
        if (in_array('full', $keys, true)) {
            return 'full';
        }

        return count($keys) === 1 ? (string) $keys[0] : 'custom';
    }

    private function lockOrganization(int $organizationId): object
    {
        $organization = DB::table('socialevent_organizations')
            ->where('id', $organizationId)
            ->lockForUpdate()
            ->first();
        abort_unless($organization, 404);

        return $organization;
    }

    private function assertSeatAvailable(object $organization): void
    {
        if ($organization->is_legacy || $organization->seat_limit === null) {
            return;
        }

        $used = DB::table('socialevent_organization_users')
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->count();

        if ($used >= (int) $organization->seat_limit) {
            throw ValidationException::withMessages([
                'seats' => 'No hay espacios disponibles. Compra otro espacio para agregar a esta persona.',
            ]);
        }
    }

    private function audit(int $organizationId, ?int $userId, string $action, string $reason, array $changes): void
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
