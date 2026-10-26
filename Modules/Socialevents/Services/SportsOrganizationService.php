<?php

namespace Modules\Socialevents\Services;

use App\Models\Parameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Socialevents\Entities\EvenCategory;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SportsOrganizationService
{
    public function sportsMode(): bool
    {
        return Schema::hasTable('parameters')
            && trim((string) Parameter::query()->where('parameter_code', 'PW00001')->value('value_default')) === '2';
    }

    public function availableToRegister(): bool
    {
        return $this->sportsMode();
    }

    /**
     * Pantalla de inicio del organizador deportivo.
     *
     * En modo torneos lleva al panel de Gestión Deportiva; si la organización
     * está suspendida, a "Mis pagos" para que pueda reactivarse. El resto de
     * cuentas conserva el dashboard general del sistema.
     */
    public function organizerHome($user): ?string
    {
        if (! $this->sportsMode() || ! $user || $this->isSuperAdmin($user)) {
            return null;
        }

        // Los operadores limitados también entran al módulo. La organización
        // sale de la sesión; sus permisos directos deciden qué pantallas ven.
        $organizationId = $this->organizationId($user);
        if (! $organizationId) {
            return null;
        }

        // Una organización suspendida no puede abrir el panel deportivo. Solo
        // el propietario conserva "Mi plan" para regularizar el pago.
        if ($this->organizationStatus($user) === 'suspended') {
            return (string) ($user->socialevent_organization_role ?? '') === 'owner'
                ? route('even_organizations.topup.index')
                : null;
        }

        if (! $this->canRead($user)) {
            return null;
        }

        return route('even_dashboard');
    }

    public function isSuperAdmin($user): bool
    {
        return $user && ($user->hasRole('admin') || $user->hasRole('Administrador'));
    }

    // Administrar tarifas y cuentas de organizaciones exige rol de administración Y el permiso propio.
    public function canAdministerOrganizations($user): bool
    {
        return $this->isSuperAdmin($user) && $user->can('even_organizaciones_admin');
    }

    public function organizationId($user): ?int
    {
        return $user && Schema::hasColumn('users', 'socialevent_organization_id') && $user->socialevent_organization_id
            ? (int) $user->socialevent_organization_id
            : null;
    }

    private function organization($user): ?object
    {
        $id = $this->organizationId($user);
        return $id && Schema::hasTable('socialevent_organizations') ? DB::table('socialevent_organizations')->find($id) : null;
    }

    public function organizationStatus($user): ?string
    {
        $organization = $this->organization($user);

        return $organization ? (string) $organization->status : null;
    }

    public function canRead($user): bool
    {
        if ($this->isSuperAdmin($user) || ! $this->sportsMode()) return true;
        $org = $this->organization($user);
        if (! $org) return false;
        if ($org->is_legacy) return true;
        if ($org->status === 'suspended') return false;
        $now = now();
        return ($org->status === 'trial' && $org->trial_ends_at && $now->lte($org->trial_ends_at))
            || ($org->status === 'active' && $this->accessDateIsCurrent($org->access_until))
            || filled($org->read_permissions);
    }

    public function canWrite($user): bool
    {
        if ($this->isSuperAdmin($user) || ! $this->sportsMode()) return true;
        $org = $this->organization($user);
        if (! $org || $org->is_legacy) return (bool) $org;
        if ($org->status === 'sponsored') return true;
        $now = now();
        return ($org->status === 'trial' && $org->trial_ends_at && $now->lte($org->trial_ends_at))
            || ($org->status === 'active' && $this->accessDateIsCurrent($org->access_until));
    }

    private function accessDateIsCurrent($accessUntil): bool
    {
        if (! $accessUntil) return false;

        $deadline = \Illuminate\Support\Carbon::parse($accessUntil);
        if ($deadline->isStartOfDay()) $deadline->endOfDay();

        return now()->lte($deadline);
    }

    /**
     * Vigencia de la organización para la vista "Mis pagos": la misma regla de
     * canRead/canWrite, expresada como fecha límite y días restantes, para que el
     * organizador vea hasta cuándo tiene acceso y pueda adelantar el pago antes
     * de que venza.
     *
     * `can_read` y `can_write` son la fuente de verdad para la app móvil: no se
     * deducen del rol ni de la fecha en el cliente, y `reason` explica el corte.
     *
     * @return array{status: string, label: string, until: ?string, days_left: ?int, expired: bool, sponsored: bool, can_read: bool, can_write: bool, reason: ?string}
     */
    public function accessSummary($user): array
    {
        $labels = [
            'trial' => 'Prueba gratuita',
            'active' => 'Activo',
            'sponsored' => 'Cortesía / patrocinio',
            'suspended' => 'Bloqueado',
        ];

        $canRead = $this->canRead($user);
        $canWrite = $this->canWrite($user);

        $organization = $this->organization($user);
        if (! $organization) {
            return [
                'status' => 'none',
                'label' => 'Sin organización',
                'until' => null,
                'days_left' => null,
                'expired' => true,
                'sponsored' => false,
                'can_read' => $canRead,
                'can_write' => $canWrite,
                'reason' => 'no_organization',
            ];
        }

        $status = (string) $organization->status;
        $summary = [
            'status' => $status,
            'label' => $labels[$status] ?? $status,
            'until' => null,
            'days_left' => null,
            'expired' => $status === 'suspended',
            'sponsored' => $status === 'sponsored',
            'can_read' => $canRead,
            'can_write' => $canWrite,
            'reason' => $this->accessReason($user, $organization),
        ];

        // La cortesía/patrocinio y la organización histórica no caducan.
        if ($summary['sponsored'] || $organization->is_legacy) {
            return $summary;
        }

        // "Activo" se mide por la fecha que fijó el administrador; la prueba, por
        // el fin del período gratuito. Una prueba vencida conserva la lectura, que
        // es justo el caso en el que el organizador quiere adelantar el pago.
        $until = $status === 'active' ? $organization->access_until : $organization->trial_ends_at;
        $summary['until'] = $until ? \Illuminate\Support\Carbon::parse($until)->toDateString() : null;
        $summary['days_left'] = $until ? $this->daysUntil($until) : null;
        $summary['expired'] = $status === 'suspended' || ! $this->accessDateIsCurrent($until);

        return $summary;
    }

    /**
     * Datos públicos de la organización para la sesión móvil. No incluye saldo
     * ni tarifas: eso vive en el endpoint de pagos.
     *
     * @return array{id: int, name: string, status: string, is_legacy: bool, trial_ends_at: ?string, access_until: ?string}|null
     */
    public function organizationSummary($user): ?array
    {
        $organization = $this->organization($user);
        if (! $organization) {
            return null;
        }

        return [
            'id' => (int) $organization->id,
            'name' => (string) $organization->name,
            'status' => (string) $organization->status,
            'is_legacy' => (bool) $organization->is_legacy,
            'trial_ends_at' => $organization->trial_ends_at
                ? \Illuminate\Support\Carbon::parse($organization->trial_ends_at)->toDateString()
                : null,
            'access_until' => $organization->access_until
                ? \Illuminate\Support\Carbon::parse($organization->access_until)->toDateString()
                : null,
        ];
    }

    /**
     * Motivo del corte para que la app muestre un mensaje útil y no solo un 403.
     */
    private function accessReason($user, $organization): ?string
    {
        if ($this->canWrite($user)) {
            return null;
        }

        return match ((string) $organization->status) {
            'suspended' => 'suspended',
            'trial' => 'trial_expired',
            'active' => 'access_expired',
            default => 'read_only',
        };
    }

    /**
     * ¿La edición puede verse en la API móvil? Es pública si está marcada para
     * la landing o para la app; el dueño y el superadministrador pueden ver
     * además las que aún no se publicaron.
     */
    public function canViewEdition($user, EventEdition $edition): bool
    {
        if ($user && $this->isSuperAdmin($user)) return true;
        if ($edition->mobile_enabled || $edition->landing_published) return true;
        if (! $user || ! $this->canRead($user)) return false;

        $orgId = $this->organizationId($user);
        if (! $orgId) return false;

        return DB::table('event_editions')
            ->join('even_events', 'even_events.id', '=', 'event_editions.event_id')
            ->where('event_editions.id', $edition->id)
            ->where('even_events.organization_id', $orgId)
            ->exists();
    }

    /**
     * Días completos que faltan para la fecha límite (0 = vence hoy, negativo =
     * ya vencido). Se calcula con marcas de tiempo enteras para no depender del
     * signo de diffInDays, que cambió entre versiones de Carbon.
     */
    private function daysUntil($date): int
    {
        $deadline = \Illuminate\Support\Carbon::parse($date)->startOfDay();
        $today = now()->startOfDay();

        return (int) round(($deadline->getTimestamp() - $today->getTimestamp()) / 86400);
    }

    /**
     * Historial de recargas/pagos de la organización para "Mis pagos".
     * Los estados son los de la pasarela más los propios de la tabla
     * (processing al iniciar, failed si el cobro no pudo crearse, credited al
     * acreditar el saldo).
     *
     * @return array<int, array<string, mixed>>
     */
    public function paymentHistory($user, int $limit = 20): array
    {
        $organizationId = $this->organizationId($user);
        if (! $organizationId || ! Schema::hasTable('socialevent_organization_topups')) return [];

        $methodLabels = ['card' => 'Tarjeta', 'yape' => 'Yape'];
        $statusLabels = [
            'processing' => 'En proceso',
            'pending' => 'Pendiente',
            'in_process' => 'En proceso',
            'approved' => 'Aprobado',
            'credited' => 'Acreditado',
            'rejected' => 'Rechazado',
            'failed' => 'Rechazado',
            'cancelled' => 'Anulado',
            'refunded' => 'Reembolsado',
        ];

        return DB::table('socialevent_organization_topups')
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($topUp) => [
                'id' => (int) $topUp->id,
                'amount' => (float) $topUp->amount,
                'method' => $methodLabels[$topUp->payment_method] ?? (string) $topUp->payment_method,
                'status' => (string) $topUp->status,
                'status_label' => $statusLabels[$topUp->status] ?? (string) $topUp->status,
                'reference' => $topUp->provider_payment_id ? (string) $topUp->provider_payment_id : (string) $topUp->external_reference,
                'created_at' => \Illuminate\Support\Carbon::parse($topUp->created_at)->format('d/m/Y H:i'),
            ])
            ->all();
    }

    /** @return array{can_create: bool, can_write: bool, required_amount: float, balance: float, reason: ?string} */
    public function matchCreationAvailability($user, EventEdition $edition): array
    {
        $canWrite = $this->canWrite($user);
        $organizationId = $this->organizationId($user);
        $organization = $organizationId && Schema::hasTable('socialevent_organizations')
            ? DB::table('socialevent_organizations')->find($organizationId)
            : null;
        $balance = (float) ($organization->prepaid_balance ?? 0);
        $rate = (float) ($edition->match_rate ?? $organization->match_rate ?? 0);
        $orgStatus = $organization->status ?? null;
        $requiredAmount = $this->sportsMode() && ! $this->isSuperAdmin($user)
            && $orgStatus !== 'sponsored'
            ? $this->calculateMatchCharge(2, $rate)
            : 0.0;
        // Si tiene acceso válido (canWrite), permitir crear aunque el saldo sea insuficiente
        // durante el periodo de permiso concedido.
        $canCreate = $canWrite;

        return [
            'can_create' => $canCreate,
            'can_write' => $canWrite,
            'required_amount' => $requiredAmount,
            'balance' => $balance,
            'reason' => ! $canWrite
                ? 'El acceso de la organización está vencido o suspendido.'
                : ($canCreate ? null : 'Saldo insuficiente. Recarga el saldo antes de registrar el partido.'),
        ];
    }

    public function assertCanRead($user): void
    {
        if (! $this->canRead($user)) abort(403, 'El acceso de lectura del torneo está vencido.');
    }

    /**
     * La cuenta suspendida no puede usar el módulo, pero debe poder acceder a
     * su propia pantalla de pagos para regularizarla.
     */
    public function assertCanAccessPayments($user): int
    {
        $organizationId = $this->organizationId($user);

        abort_unless($organizationId, 403, 'No hay una organización asociada a esta cuenta.');
        abort_unless(Schema::hasTable('socialevent_organizations') && DB::table('socialevent_organizations')->where('id', $organizationId)->exists(), 403);

        return $organizationId;
    }

    public function assertCanWrite($user): void
    {
        if (! $this->canWrite($user)) abort(403, 'El acceso está en modo de solo lectura o vencido.');
    }

    public function assertOwnedEvent($user, int $eventId): void
    {
        if (! $this->sportsMode() || $this->isSuperAdmin($user)) return;
        $orgId = $this->organizationId($user);
        abort_unless($orgId && DB::table('even_events')->where('id', $eventId)->where('organization_id', $orgId)->exists(), 404);
    }

    public function assertOwnedEdition($user, int $editionId): void
    {
        if (! $this->sportsMode() || $this->isSuperAdmin($user)) return;
        $orgId = $this->organizationId($user);
        abort_unless($orgId && DB::table('event_editions')->join('even_events', 'even_events.id', '=', 'event_editions.event_id')
            ->where('event_editions.id', $editionId)->where('even_events.organization_id', $orgId)->exists(), 404);
    }

    public function assertOwnedMatch($user, int $matchId): EventEditionMatch
    {
        if (! $this->sportsMode() || $this->isSuperAdmin($user)) return EventEditionMatch::findOrFail($matchId);
        $orgId = $this->organizationId($user);
        return EventEditionMatch::query()->whereKey($matchId)
            ->whereHas('edicion.evento', fn ($q) => $q->where('organization_id', $orgId))->firstOrFail();
    }

    public function assertOwnedTeam($user, int $teamId): void
    {
        if (! $this->sportsMode() || $this->isSuperAdmin($user)) return;
        $orgId = $this->organizationId($user);
        abort_unless($orgId && DB::table('event_teams')->where('id', $teamId)->where('organization_id', $orgId)->exists(), 404);
    }

    public function assertOwnedEditionTeam($user, int $editionId, int $teamId): void
    {
        $this->assertOwnedEdition($user, $editionId);
        $this->assertOwnedTeam($user, $teamId);
        abort_unless(DB::table('event_edition_teams')->where('edition_id', $editionId)->where('team_id', $teamId)->exists(), 404);
    }

    public function sportsCategoryId(): int
    {
        $id = EvenCategory::query()->whereNull('main_category_id')->whereRaw('LOWER(TRIM(description)) = ?', ['deportivos'])->value('id');
        if ($id) return (int) $id;

        return (int) EvenCategory::query()->create([
            'description' => 'Deportivos',
            'status' => true,
            'main_category_id' => null,
            'informations' => null,
        ])->id;
    }

    public function legacyOrganizationId(): ?int
    {
        if (! Schema::hasTable('socialevent_organizations')) return null;
        $id = DB::table('socialevent_organizations')->where('is_legacy', true)->whereNull('deleted_at')->value('id');
        return $id ? (int) $id : null;
    }

    /**
     * Crea la organización del organizador y le asigna el rol con sus permisos.
     * $datos (opcional) trae nombre, tipo (empresa|persona_natural) y RUC.
     */
    public function createOrganizationForUser($user, array $datos = []): int
    {
        $settings = Schema::hasTable('socialevent_organization_settings')
            ? DB::table('socialevent_organization_settings')->first()
            : null;
        $trialDays = max(1, (int) ($settings->trial_days ?? config('socialevents.organizations.trial_days', 30)));
        $startedAt = now();
        $organizationId = DB::table('socialevent_organizations')->insertGetId([
            'name' => $datos['nombre'] ?? $user->name,
            'tipo_organizacion' => $datos['tipo'] ?? null,
            'ruc' => $datos['ruc'] ?? null,
            'address' => $datos['address'] ?? null,
            'ubigeo' => $datos['ubigeo'] ?? null,
            'status' => 'trial',
            'trial_started_at' => $startedAt,
            'trial_days' => $trialDays,
            'trial_ends_at' => $startedAt->copy()->addDays($trialDays),
            'access_until' => null,
            'read_permissions' => json_encode(['teams', 'fixtures', 'reports', 'minutes']),
            'match_rate' => (float) ($settings->default_match_rate ?? config('socialevents.organizations.default_match_rate', 5)),
            'prepaid_balance' => 0,
            'created_by' => $user->id,
            'created_at' => $startedAt,
            'updated_at' => $startedAt,
        ]);
        $user->forceFill(['socialevent_organization_id' => $organizationId, 'socialevent_organization_role' => 'owner'])->save();
        DB::table('socialevent_organization_users')->insert([
            'organization_id' => $organizationId, 'user_id' => $user->id, 'role' => 'owner', 'joined_at' => $startedAt,
            'created_at' => $startedAt, 'updated_at' => $startedAt,
        ]);

        $permissions = [
            'even_dashboard', 'even_evento_listado', 'even_evento_nuevo', 'even_evento_editar', 'even_evento_eliminar',
            'even_equipos_listado', 'even_equipos_nuevo', 'even_equipos_editar', 'even_equipos_eliminar',
            'even_ediciones_actas_nuevo', 'even_ediciones_actas_solucion_update', 'even_ediciones_actas_file_update',
            'even_ediciones_actas_protest_file_destroy', 'even_ediciones_match_accordance_update', 'even_ediciones_exclusiones_store',
            'even_ediciones_suspensiones_store', 'even_ediciones_equipos_agregar', 'even_ediciones_equipos_bonus_store',
            'even_ediciones_equipos_bonus_destroy', 'even_ediciones_equipos_recalculate', 'even_ediciones_pago_sanciones_store',
            'even_ediciones_sanciones_card_store', 'even_ediciones_actas_file_update', 'even_team_player_store',
            'even_team_player_update', 'even_team_player_image_update', 'even_ediciones_equipos_player_destroy',
            'even_ediciones_listado', 'even_ediciones_nuevo', 'even_ediciones_editar', 'even_ediciones_eliminar',
            'even_ediciones_equipos', 'even_ediciones_equipos_eliminar', 'even_ediciones_equipo_jugadores',
            'even_ediciones_fixtures', 'even_ediciones_fixtures_nuevo', 'even_ediciones_partido_resultado',
            'even_ediciones_partido_eliminar', 'even_ediciones_sanciones', 'even_ediciones_sanciones_registrar',
            'even_ediciones_partido_acta', 'even_ediciones_actas', 'even_ediciones_acta_editar',
            'even_ediciones_partido_acta_editar', 'even_ediciones_galeria', 'even_ediciones_galeria_nuevo',
            'even_ediciones_galeria_eliminar', 'even_ediciones_exclusiones', 'even_ediciones_suspensiones',
            'even_ediciones_diplomas', 'even_ediciones_diplomas_disenar', 'even_ediciones_premiados',
            // Locales (complejos deportivos): cada organizador crea los suyos y solo edita o elimina los que creó.
            'even_local_listado', 'even_local_nuevo', 'even_local_editar', 'even_local_eliminar',
        ];
        $role = Role::firstOrCreate(['name' => 'sports_organizer', 'guard_name' => 'web']);
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            $role->givePermissionTo($permission);
        }
        $user->assignRole($role);
        DB::table('socialevent_organization_audits')->insert([
            'organization_id' => $organizationId, 'user_id' => $user->id,
            'action' => 'organization_registered', 'reason' => 'Creación de organización y alta de prueba gratuita.',
            'changes' => json_encode(['status' => 'trial', 'trial_days' => $trialDays, 'trial_ends_at' => $startedAt->copy()->addDays($trialDays)->toDateTimeString()]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $organizationId;
    }

    public function assertRequestOwnership($request): void
    {
        $user = $request->user();
        if (! $this->sportsMode() || $this->isSuperAdmin($user)) return;

        $path = trim($request->path(), '/');
        $parameters = $request->route()?->parameters() ?? [];
        $editionPathId = preg_match('#^socialevents/editions/(\d+)(?:/|$)#', $path, $matches) ? (int) $matches[1] : null;

        if (preg_match('#^socialevents/categories(?:/|$)#', $path)) abort(404);
        if (in_array($path, ['socialevents/alquiler-local', 'socialevents/tickets'], true) || str_starts_with($path, 'socialevents/events/prices/')) abort(404);

        if (preg_match('#^socialevents/events/(\d+)(?:/edit)?$#', $path, $matches)) {
            $this->assertOwnedEvent($user, (int) $matches[1]);
        }
        if (preg_match('#^socialevents/events/(?:update)$#', $path) && $request->input('id')) {
            $this->assertOwnedEvent($user, (int) $request->input('id'));
        }
        if (preg_match('#^socialevents/teams/(\d+)(?:/edit|/destroy)?$#', $path, $matches)) {
            $this->assertOwnedTeam($user, (int) $matches[1]);
        }
        if (preg_match('#^socialevents/teams/update$#', $path) && $request->input('id')) {
            $this->assertOwnedTeam($user, (int) $request->input('id'));
        }
        if ($editionPathId) {
            $this->assertOwnedEdition($user, $editionPathId);
            $teamPathId = $parameters['tId'] ?? null;
            if ($teamPathId && preg_match('#/teams/\d+#', $path)) {
                $this->assertOwnedEditionTeam($user, $editionPathId, (int) $teamPathId);
            }
            $matchPathId = $parameters['matchId'] ?? $parameters['mId'] ?? null;
            if ($matchPathId) {
                $match = $this->assertOwnedMatch($user, (int) $matchPathId);
                abort_unless((int) $match->edition_id === $editionPathId, 404);
            }
        }

        $routeName = (string) $request->route()?->getName();
        $matchParam = $parameters['matchId'] ?? $parameters['mId'] ?? null;
        if (! $matchParam && str_starts_with($path, 'socialevents/editions/fixtures/')) {
            $matchParam = $parameters['id'] ?? $parameters['fId'] ?? null;
        }
        if (! $matchParam && $routeName && preg_match('/match|fixture_update|match_score_destroy/', $routeName)) {
            $matchParam = $parameters['id'] ?? $parameters['fId'] ?? null;
        }
        if ($matchParam) $this->assertOwnedMatch($user, (int) $matchParam);

        $reportId = $request->input('acta_id') ?? $request->input('report_id');
        if (! $reportId && str_contains($routeName, 'acta')) $reportId = $parameters['id'] ?? $request->input('id');
        if ($reportId && str_contains($routeName, 'acta')) {
            $matchId = DB::table('event_edition_match_reports')->where('id', $reportId)->value('match_id');
            if ($matchId) $this->assertOwnedMatch($user, (int) $matchId);
        }
        if ($request->filled('acta.id')) {
            $matchId = DB::table('event_edition_match_reports')->where('id', $request->input('acta.id'))->value('match_id');
            if ($matchId) $this->assertOwnedMatch($user, (int) $matchId);
        }
        $accordanceId = $parameters['id'] ?? $request->input('id');
        if ($accordanceId && str_contains($routeName, 'accordance')) {
            $agreementEditionId = DB::table('event_edition_accordances')->where('id', $accordanceId)->value('edition_id');
            if ($agreementEditionId) $this->assertOwnedEdition($user, (int) $agreementEditionId);
        }
        if (str_contains($routeName, 'accordance_update')) {
            $accordanceId = (int) ($request->input('id') ?? 0);
            $agreementEditionId = DB::table('event_edition_accordances')->where('id', $accordanceId)->value('edition_id');
            if ($agreementEditionId) $this->assertOwnedEdition($user, (int) $agreementEditionId);
        }
        if ($request->filled('id') && $routeName && str_contains($routeName, 'events_update')) {
            $this->assertOwnedEvent($user, (int) $request->input('id'));
        }
        if ($request->filled('id') && $routeName && str_contains($routeName, 'teams_update')) {
            $this->assertOwnedTeam($user, (int) $request->input('id'));
        }
        if ($request->filled('id') && str_contains($routeName, 'ediciones_update')) {
            $this->assertOwnedEdition($user, (int) $request->input('id'));
        }
        if ($request->filled('id') && str_contains($routeName, 'equipo_update')) {
            $this->assertOwnedTeam($user, (int) $request->input('id'));
        }
        if (str_contains($routeName, 'actas_solucion_update') || str_contains($routeName, 'actas_file_update') || str_contains($routeName, 'match_accordance_update') || str_contains($routeName, 'actas_protest_file_destroy')) {
            $reportId = (int) ($request->input('id') ?? 0);
            if ($reportId) {
                $matchId = DB::table('event_edition_match_reports')->where('id', $reportId)->value('match_id');
                if ($matchId) $this->assertOwnedMatch($user, (int) $matchId);
            }
            $matchId = (int) ($request->input('match_id') ?? 0);
            if ($matchId) $this->assertOwnedMatch($user, $matchId);
        }
        if (str_contains($routeName, 'protest_file_destroy')) {
            $matchId = DB::table('event_edition_match_reports')->where('id', $request->input('report_id'))->value('match_id');
            if ($matchId) $this->assertOwnedMatch($user, (int) $matchId);
        }
        if (str_contains($routeName, 'actas_crear') || str_contains($routeName, 'accordance_store') || str_contains($routeName, 'actas_pdf') || str_contains($routeName, 'accordance_pdf')) {
            $editionId = $request->input('edition_id') ?? $request->input('acta.edition_id');
            if ($editionId) $this->assertOwnedEdition($user, (int) $editionId);
        }

        $eventId = $request->input('event_id');
        if ($eventId) $this->assertOwnedEvent($user, (int) $eventId);
        if (preg_match('#^socialevents/editions/(\d+)/fixtures/(generate|create)$#', $path, $matches)) {
            $this->assertOwnedEdition($user, (int) $matches[1]);
        }
        if (preg_match('#^socialevents/editions/(\d+)/(gallery|gallery/store|gallery/\d+/destroy|teams/bonus|teams/bonus/store|teams/bonus/\d+/destroy|teams/recalculate|teams/add|exclusions/store|exclusions/\d+/destroy|suspensions/store|suspensions/\d+/destroy|sanctions/register-card)$#', $path, $matches)) {
            $this->assertOwnedEdition($user, (int) $matches[1]);
        }
        if ($request->filled('teams') && is_array($request->input('teams')) && str_contains($path, '/teams/bonus')) {
            $editionId = (int) ($parameters['id'] ?? 0);
            foreach ($request->input('teams') as $teamId) $this->assertOwnedEditionTeam($user, $editionId, (int) $teamId);
        }
        if (preg_match('#^socialevents/editions/(\d+)/teams/(\d+)/(payers|pdf-roster|player/\d+/destroy)$#', $path, $matches)) {
            $this->assertOwnedEditionTeam($user, (int) $matches[1], (int) $matches[2]);
        }
        if ($request->isMethod('delete') && str_contains($path, '/gallery/')) {
            $mediaId = $parameters['mediaId'] ?? null;
            if ($editionPathId && $mediaId) {
                abort_unless(DB::table('event_edition_media')->where('id', $mediaId)->where('edition_id', $editionPathId)->exists(), 404);
            }
        }
        if ($request->isMethod('delete') && preg_match('#^socialevents/editions/(\d+)/(exclusions|suspensions)/(\d+)/destroy$#', $path, $matches)) {
            $table = $matches[2] === 'exclusions' ? 'event_edition_player_exclusions' : 'event_edition_player_suspensions';
            abort_unless(DB::table($table)->where('id', $matches[3])->where('edition_id', $matches[1])->exists(), 404);
        }
        if ($request->isMethod('delete') && preg_match('#^socialevents/editions/(\d+)/teams/bonus/(\d+)/destroy$#', $path, $matches)) {
            abort_unless(DB::table('event_edition_team_bonus_points')->where('id', $matches[2])->where('edition_id', $matches[1])->exists(), 404);
        }
        if (preg_match('#^socialevents/editions/(reports/(update|upload/file|protest-file/destroy)|match/accordance/update)$#', $path)) {
            $reportId = $request->input('id') ?? $request->input('acta_id') ?? $request->input('report_id');
            $matchId = DB::table('event_edition_match_reports')->where('id', $reportId)->value('match_id');
            if ($matchId) $this->assertOwnedMatch($user, (int) $matchId);
        }
        if (preg_match('#^socialevents/editions/accordance/update$#', $path)) {
            $editionId = DB::table('event_edition_accordances')->where('id', $request->input('id'))->value('edition_id');
            if ($editionId) $this->assertOwnedEdition($user, (int) $editionId);
        }
        if (preg_match('#^socialevents/editions/fixtures/(\d+)/update$#', $path, $matches)) {
            $this->assertOwnedMatch($user, (int) $matches[1]);
        }
        if (preg_match('#^socialevents/editions/fixtures/match/(\d+)/destroy$#', $path, $matches)) {
            $this->assertOwnedMatch($user, (int) $matches[1]);
        }
        if (preg_match('#^socialevents/events/destroy/(\d+)$#', $path, $matches)) {
            $this->assertOwnedEvent($user, (int) $matches[1]);
        }
        if (preg_match('#^socialevents/editions/(\d+)/destroy$#', $path, $matches)) {
            $this->assertOwnedEdition($user, (int) $matches[1]);
        }
        if ($request->filled('edition_id')) {
            $editionId = (int) $request->input('edition_id');
            $this->assertOwnedEdition($user, $editionId);
        } else {
            $editionId = null;
        }
        if ($request->filled('id') && in_array($routeName, ['even_ediciones_fixtures_update', 'even_edition_match_score_update'], true)) {
            $this->assertOwnedMatch($user, (int) $request->input('id'));
        }
        if ($request->filled('id') && str_contains($routeName, 'accordance_update')) {
            $accordanceEditionId = DB::table('event_edition_accordances')->where('id', $request->input('id'))->value('edition_id');
            abort_unless($accordanceEditionId, 404);
            $this->assertOwnedEdition($user, (int) $accordanceEditionId);
        }
        if ($request->filled('match_id') && str_contains($routeName, 'sanctions')) {
            $sanctionMatch = EventEditionMatch::query()->whereKey($request->input('match_id'))->firstOrFail();
            $this->assertOwnedEdition($user, (int) $sanctionMatch->edition_id);
        }
        if ($editionId) $this->assertOwnedEdition($user, (int) $editionId);
        if ($request->filled('edition_id') && $request->filled('id') && $routeName === 'even_ediciones_fixtures_update') {
            $this->assertOwnedMatch($user, (int) $request->input('id'));
        }
        $teamId = $request->input('team_id');
        if ($teamId && ! $editionId && str_contains($routeName, 'admin.teams.store')) {
            $this->assertOwnedTeam($user, (int) $teamId);
        }
        if ($teamId && $editionId && $routeName === 'even_ediciones_equipos_agregar') {
            // Al agregar, el equipo todavía no está en la edición: solo se valida que
            // ambos pertenezcan a la organización (el duplicado lo controla el 409 del controlador).
            $this->assertOwnedEdition($user, (int) $editionId);
            $this->assertOwnedTeam($user, (int) $teamId);
        } elseif ($teamId && $editionId) {
            $this->assertOwnedEditionTeam($user, (int) $editionId, (int) $teamId);
        } elseif ($teamId) {
            $this->assertOwnedTeam($user, (int) $teamId);
        }
        if ($request->filled('equipo_h_id') && $editionId) $this->assertOwnedEditionTeam($user, $editionId, (int) $request->input('equipo_h_id'));
        if ($request->filled('equipo_a_id') && $editionId) $this->assertOwnedEditionTeam($user, $editionId, (int) $request->input('equipo_a_id'));
        if ($request->filled('team_h') && $editionId) $this->assertOwnedEditionTeam($user, $editionId, (int) $request->input('team_h'));
        if ($request->filled('team_a') && $editionId) $this->assertOwnedEditionTeam($user, $editionId, (int) $request->input('team_a'));
    }

    public function assertSportsRouteAllowed($user, string $path, string $method = 'GET'): void
    {
        if (! $this->sportsMode() || $this->isSuperAdmin($user)) return;
        if (preg_match('#^(socialevents/(categories|alquiler-local|tickets)(/|$)|api/socialevents/v1/admin/locations$)#', $path)
            || str_starts_with($path, 'socialevents/events/prices/')) abort(404);
        if ($this->canWrite($user)) return;

        $method = strtoupper($method);
        // El propio panel del módulo también es lectura: si se bloquea, el
        // organizador vencido llega a un 403 desde el menú que sigue viendo.
        $read = ($method === 'GET' && in_array($path, ['socialevents/dashboard', 'socialevents/events', 'socialevents/teams', 'socialevents/editions'], true))
            || ($method === 'GET' && preg_match('#^socialevents/editions/[0-9]+/(teams|fixtures|reports|gallery|exclusions|suspensions|collection/penalties|sanctions/pdf|landing-visits)$#', $path))
            || ($method === 'GET' && preg_match('#^socialevents/editions/[0-9]+/fixtures/create/availability$#', $path))
            || ($method === 'GET' && preg_match('#^socialevents/editions/[0-9]+/fixtures/match/[0-9]+/(resultado|pdf-sheet)$#', $path))
            || ($method === 'GET' && preg_match('#^socialevents/editions/match/reports/[0-9]+/edit$#', $path))
            || ($method === 'POST' && in_array($path, ['socialevents/editions/reports/pdf', 'socialevents/editions/accordance/pdf'], true));
        abort_unless($read, 403, 'Esta sección no está habilitada durante el modo de solo lectura.');
    }

    public function assertOwnedApiEdition($user, int $editionId): void
    {
        $this->assertCanRead($user);
        $this->assertOwnedEdition($user, $editionId);
    }

    public function assertOwnedApiTeam($user, int $editionId, int $teamId): void
    {
        $this->assertCanRead($user);
        $this->assertOwnedEditionTeam($user, $editionId, $teamId);
    }

    public function assertOwnedApiMatch($user, int $matchId): EventEditionMatch
    {
        $this->assertCanRead($user);
        return $this->assertOwnedMatch($user, $matchId);
    }

    public function assertCanCreateMatch($user, int $editionId, int $teamCount = 2): void
    {
        $this->assertOwnedEdition($user, $editionId);
        $this->assertCanWrite($user);
        if (! $this->sportsMode() || $this->isSuperAdmin($user)) return;
        $orgId = $this->organizationId($user);
        $org = DB::table('socialevent_organizations')->where('id', $orgId)->lockForUpdate()->first();
        $edition = EventEdition::findOrFail($editionId);
        if (! $org) throw ValidationException::withMessages(['prepaid_balance' => 'No hay una organización habilitada para esta cuenta.']);
        // Permitir crear si tiene acceso válido (assertCanWrite ya validó). No bloquear por saldo.
        $rate = $org->status === 'sponsored' ? 0.0 : (float) ($edition->match_rate ?? $org->match_rate);
        if ($rate > 0 && (float) $org->prepaid_balance < round($teamCount * $rate, 2)) {
            // No lanzar error; permitir crear durante periodo de permiso. El cobro puede registrarse igual.
            // throw ValidationException::withMessages(['prepaid_balance' => 'Saldo insuficiente. Recarga el saldo antes de registrar el partido.']);
        }
    }

    public function chargeMatches(iterable $matches, $user, bool $freeBatch = false): void
    {
        if (! $this->sportsMode() || $this->isSuperAdmin($user)) return;
        $this->assertCanWrite($user);
        $matches = is_array($matches) ? $matches : iterator_to_array($matches);
        if ($matches === []) return;
        foreach ($matches as $match) $this->assertOwnedEdition($user, (int) $match->edition_id);
        $organizationId = $this->organizationId($user);
        $organization = DB::table('socialevent_organizations')->where('id', $organizationId)->lockForUpdate()->first();
        if (! $organization) throw ValidationException::withMessages(['prepaid_balance' => 'No hay una organización habilitada para esta cuenta.']);
        if ($organization->status === 'sponsored') return;

        $matches = collect($matches)->filter(fn ($match) => ! DB::table('socialevent_organization_ledger')->where('match_id', $match->id)->where('entry_type', 'match_charge')->exists())->values()->all();
        if ($matches === []) return;

        $amount = 0.0;
        $items = [];
        foreach ($matches as $match) {
            $teamCount = min(2, (int) ($match->team_h_id !== null) + (int) ($match->team_a_id !== null));
            if ($teamCount !== 2) continue;
            $edition = EventEdition::findOrFail($match->edition_id);
            $rate = (float) ($edition->match_rate ?? $organization->match_rate);
            // La primera fecha de la edición no descuenta saldo; se registra en 0 para no cobrarla luego.
            $charge = $freeBatch ? 0.0 : $this->calculateMatchCharge($teamCount, $rate);
            $amount += $charge;
            $items[] = ['match_id' => $match->id, 'edition_id' => $match->edition_id, 'teams' => $teamCount, 'rate_per_team' => $rate, 'amount' => $charge];
        }

        if ($organization->status !== 'sponsored' && (float) $organization->prepaid_balance < $amount) {
            // Permitir generar durante periodo de acceso válido
            // throw ValidationException::withMessages(['prepaid_balance' => 'Saldo insuficiente para generar todos los partidos del fixture.']);
        }
        if ($organization->status === 'sponsored') return;

        $balance = round((float) $organization->prepaid_balance - $amount, 2);
        DB::table('socialevent_organizations')->where('id', $organizationId)->update(['prepaid_balance' => $balance, 'updated_at' => now()]);
        foreach ($items as $item) {
            $balance = round($balance, 2);
            DB::table('socialevent_organization_ledger')->insert([
                'organization_id' => $organizationId, 'user_id' => $user->id, 'edition_id' => $item['edition_id'], 'match_id' => $item['match_id'],
                'entry_type' => 'match_charge', 'amount' => -$item['amount'], 'balance_after' => $balance,
                'description' => $freeBatch ? 'Primera fecha de la edición: sin cobro.' : 'Cargo por registro de partido; se aplica aunque no se juegue.',
                'metadata' => json_encode($item), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /**
     * Registra un lote de partidos (una fecha) generado por la organización.
     * La primera fecha de cada edición es gratis; las siguientes quedan marcadas para cobro.
     */
    public function registerMatchBatch($matches, $user, int $editionId, string $format): bool
    {
        if (! $this->sportsMode() || $this->isSuperAdmin($user)) return false;
        $organizationId = $this->organizationId($user);
        if (! $organizationId) return false;

        $matches = is_array($matches) ? $matches : iterator_to_array($matches);
        $teamCount = collect($matches)->flatMap(fn ($match) => [$match->team_h_id, $match->team_a_id])->filter()->unique()->count();

        $organizationRate = (float) DB::table('socialevent_organizations')->where('id', $organizationId)->value('match_rate');
        $rate = (float) (EventEdition::find($editionId)?->match_rate ?? $organizationRate);

        $amount = 0.0;
        foreach ($matches as $match) {
            $teams = (int) ($match->team_h_id !== null) + (int) ($match->team_a_id !== null);
            if ($teams === 2) $amount += $this->calculateMatchCharge(2, $rate);
        }

        $isFree = ! DB::table('socialevent_match_batches')
            ->where('organization_id', $organizationId)
            ->where('edition_id', $editionId)
            ->exists();

        DB::table('socialevent_match_batches')->insert([
            'organization_id' => $organizationId,
            'edition_id' => $editionId,
            'created_by' => $user->id,
            'format' => $format,
            'teams_count' => $teamCount,
            'matches_count' => count($matches),
            'amount' => round($amount, 2),
            'is_free' => $isFree,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $isFree;
    }

    public function calculateMatchCharge(int $teamCount, float $ratePerTeam): float
    {
        return round($teamCount * $ratePerTeam, 2);
    }

    public function chargeMatch(EventEditionMatch $match, $user): void
    {
        if (! $this->sportsMode() || $this->isSuperAdmin($user)) return;
        $this->assertCanWrite($user);
        $this->assertOwnedEdition($user, (int) $match->edition_id);

        $teams = (int) ($match->team_h_id !== null) + (int) ($match->team_a_id !== null);
        if ($teams !== 2) return;

        $orgId = $this->organizationId($user);
        $org = DB::table('socialevent_organizations')->where('id', $orgId)->lockForUpdate()->first();
        $edition = EventEdition::findOrFail($match->edition_id);
        if (! $org || (int) $org->id !== (int) $this->organizationId($user)) throw ValidationException::withMessages(['prepaid_balance' => 'No hay una organización habilitada para esta cuenta.']);
        if (DB::table('socialevent_organization_ledger')->where('match_id', $match->id)->where('entry_type', 'match_charge')->exists()) return;
        if ($org->status === 'sponsored') return;

        $rate = (float) ($edition->match_rate ?? $org->match_rate);
        $amount = $this->calculateMatchCharge($teams, $rate);
        $balance = round((float) $org->prepaid_balance - $amount, 2);
        // Permitir crear durante periodo de acceso válido incluso sin saldo suficiente
        if ($balance < 0) {
            // No bloquear; registrar con saldo negativo o simplemente permitir
            // Para no romper flujo, permitimos crear
        }
        DB::table('socialevent_organizations')->where('id', $orgId)->update(['prepaid_balance' => $balance, 'updated_at' => now()]);
        DB::table('socialevent_organization_ledger')->insert([
            'organization_id' => $orgId, 'user_id' => $user->id, 'edition_id' => $match->edition_id, 'match_id' => $match->id,
            'entry_type' => 'match_charge', 'amount' => -$amount, 'balance_after' => $balance,
            'description' => 'Cargo por registro de partido; se aplica aunque no se juegue.',
            'metadata' => json_encode(['rate_per_team' => $rate, 'teams' => $teams, 'amount' => $amount]), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
