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

    public function isSuperAdmin($user): bool
    {
        return $user && ($user->hasRole('admin') || $user->hasRole('Administrador'));
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

    public function canRead($user): bool
    {
        if ($this->isSuperAdmin($user) || ! $this->sportsMode()) return true;
        $org = $this->organization($user);
        if (! $org) return false;
        if ($org->is_legacy) return true;
        if ($org->status === 'suspended') return false;
        $now = now();
        return ($org->status === 'trial' && $org->trial_ends_at && $now->lte($org->trial_ends_at))
            || ($org->status === 'active' && $org->access_until && $now->lte($org->access_until))
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
            || ($org->status === 'active' && $org->access_until && $now->lte($org->access_until));
    }

    public function assertCanRead($user): void
    {
        if (! $this->canRead($user)) abort(403, 'El acceso de lectura del torneo está vencido.');
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
        $id = DB::table('socialevent_organizations')->where('is_legacy', true)->value('id');
        return $id ? (int) $id : null;
    }

    public function createOrganizationForUser($user): int
    {
        $settings = Schema::hasTable('socialevent_organization_settings')
            ? DB::table('socialevent_organization_settings')->first()
            : null;
        $trialDays = max(1, (int) ($settings->trial_days ?? config('socialevents.organizations.trial_days', 30)));
        $startedAt = now();
        $organizationId = DB::table('socialevent_organizations')->insertGetId([
            'name' => $user->name,
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
        if (in_array($path, ['socialevents/locales', 'socialevents/alquiler-local', 'socialevents/tickets'], true) || str_starts_with($path, 'socialevents/events/prices/')) abort(404);

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
            $this->assertOwnedEditionTeam($user, (int) $editionId, (int) $teamId);
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
        if (preg_match('#^(socialevents/(categories|locales|alquiler-local|tickets)(/|$)|api/socialevents/v1/admin/locations$)#', $path)
            || str_starts_with($path, 'socialevents/events/prices/')) abort(404);
        if ($this->canWrite($user)) return;

        $method = strtoupper($method);
        $read = ($method === 'GET' && in_array($path, ['socialevents/events', 'socialevents/teams', 'socialevents/editions'], true))
            || ($method === 'GET' && preg_match('#^socialevents/editions/[0-9]+/(teams|fixtures|reports|gallery|exclusions|suspensions|collection/penalties|sanctions/pdf)$#', $path))
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
        $rate = $org->status === 'sponsored' ? 0.0 : (float) ($edition->match_rate ?? $org->match_rate);
        if ($rate > 0 && (float) $org->prepaid_balance < round($teamCount * $rate, 2)) {
            throw ValidationException::withMessages(['prepaid_balance' => 'Saldo insuficiente. Recarga el saldo antes de registrar el partido.']);
        }
    }

    public function chargeMatches(iterable $matches, $user): void
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
            $charge = $this->calculateMatchCharge($teamCount, $rate);
            $amount += $charge;
            $items[] = ['match_id' => $match->id, 'edition_id' => $match->edition_id, 'teams' => $teamCount, 'rate_per_team' => $rate, 'amount' => $charge];
        }

        if ($organization->status !== 'sponsored' && (float) $organization->prepaid_balance < $amount) {
            throw ValidationException::withMessages(['prepaid_balance' => 'Saldo insuficiente para generar todos los partidos del fixture.']);
        }
        if ($organization->status === 'sponsored') return;

        $balance = round((float) $organization->prepaid_balance - $amount, 2);
        DB::table('socialevent_organizations')->where('id', $organizationId)->update(['prepaid_balance' => $balance, 'updated_at' => now()]);
        foreach ($items as $item) {
            $balance = round($balance, 2);
            DB::table('socialevent_organization_ledger')->insert([
                'organization_id' => $organizationId, 'user_id' => $user->id, 'edition_id' => $item['edition_id'], 'match_id' => $item['match_id'],
                'entry_type' => 'match_charge', 'amount' => -$item['amount'], 'balance_after' => $balance,
                'description' => 'Cargo por registro de partido; se aplica aunque no se juegue.',
                'metadata' => json_encode($item), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
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
        if ($balance < 0) throw ValidationException::withMessages(['prepaid_balance' => 'Se requiere saldo suficiente para registrar este partido.']);
        DB::table('socialevent_organizations')->where('id', $orgId)->update(['prepaid_balance' => $balance, 'updated_at' => now()]);
        DB::table('socialevent_organization_ledger')->insert([
            'organization_id' => $orgId, 'user_id' => $user->id, 'edition_id' => $match->edition_id, 'match_id' => $match->id,
            'entry_type' => 'match_charge', 'amount' => -$amount, 'balance_after' => $balance,
            'description' => 'Cargo por registro de partido; se aplica aunque no se juegue.',
            'metadata' => json_encode(['rate_per_team' => $rate, 'teams' => $teams, 'amount' => $amount]), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
