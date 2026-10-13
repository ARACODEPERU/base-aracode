<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Socialevents\Services\SportsOrganizationService;

class SportsOrganizationAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(app(SportsOrganizationService::class)->isSuperAdmin($request->user()), 403);

        $settings = DB::table('socialevent_organization_settings')->first();
        $organizations = DB::table('socialevent_organizations')
            ->leftJoin('users', 'users.id', '=', 'socialevent_organizations.created_by')
            ->select('socialevent_organizations.*', 'users.name as owner_name', 'users.email as owner_email')
            ->orderByDesc('socialevent_organizations.id')
            ->get();

        return Inertia::render('Socialevents::Organizations/Admin', [
            'defaults' => [
                'trial_days' => (int) ($settings->trial_days ?? 30),
                'default_match_rate' => (float) ($settings->default_match_rate ?? 5),
            ],
            'organizations' => $organizations,
        ]);
    }

    public function updateDefaults(Request $request)
    {
        abort_unless(app(SportsOrganizationService::class)->isSuperAdmin($request->user()), 403);
        $validated = $request->validate([
            'trial_days' => 'required|integer|min:1|max:3660',
            'default_match_rate' => 'required|numeric|min:0|max:999999.99',
        ]);

        DB::table('socialevent_organization_settings')->updateOrInsert(
            ['id' => 1],
            [...$validated, 'updated_at' => now(), 'created_at' => now()]
        );
        $legacyOrganizationId = app(SportsOrganizationService::class)->legacyOrganizationId();
        if ($legacyOrganizationId) {
            DB::table('socialevent_organization_audits')->insert([
                'organization_id' => $legacyOrganizationId,
                'user_id' => $request->user()->id,
                'action' => 'commercial_defaults_updated',
                'reason' => 'Actualización de los valores comerciales predeterminados.',
                'changes' => json_encode($validated),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Configuración comercial guardada.');
    }

    public function updateOrganization(Request $request, int $organizationId)
    {
        abort_unless(app(SportsOrganizationService::class)->isSuperAdmin($request->user()), 403);
        $validated = $request->validate([
            'match_rate' => 'required|numeric|min:0|max:999999.99',
            'status' => 'required|in:trial,active,sponsored,suspended',
            'trial_days' => 'required|integer|min:1|max:3660',
            'access_until' => 'nullable|date',
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
                $changes['trial_started_at'] = $now;
                $changes['trial_ends_at'] = $now->copy()->addDays((int) $validated['trial_days']);
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

            DB::table('socialevent_organization_audits')->insert([
                'organization_id' => $organizationId,
                'user_id' => $request->user()->id,
                'action' => $credit > 0 ? 'organization_commercial_settings_and_credit' : 'organization_commercial_settings',
                'reason' => $validated['reason'],
                'changes' => json_encode([
                    'before' => ['status' => $organization->status, 'match_rate' => $organization->match_rate, 'prepaid_balance' => $organization->prepaid_balance],
                    'after' => ['status' => $validated['status'], 'match_rate' => $validated['match_rate'], 'prepaid_balance' => $balance],
                    'trial_days' => $validated['trial_days'], 'access_until' => $validated['access_until'] ?? null,
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        return back()->with('success', 'Organización actualizada; el cambio quedó auditado.');
    }
}
