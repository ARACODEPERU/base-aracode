<?php

namespace Modules\Bibliodata\Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\Bibliodata\Entities\BibBook;
use Modules\Bibliodata\Entities\BibOrganization;
use Modules\Bibliodata\Entities\BibSubscription;
use Modules\Bibliodata\Entities\BibSubscriptionPlan;
use Modules\Bibliodata\Services\BibSubscriptionService;
use Spatie\Permission\Models\Role;

class BibSubscriptionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $book = BibBook::where('status', 'available')->first();
        if (! $book) {
            $this->command?->warn('BibSubscriptionDemoSeeder: no hay libros disponibles.');

            return;
        }

        $service = app(BibSubscriptionService::class);

        $monthlyPlan = BibSubscriptionPlan::firstOrCreate(
            ['code' => 'demo-mensual'],
            [
                'name' => 'Demo mensual',
                'description' => 'Plan de demostración mensual',
                'scope_type' => 'single_book',
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
        $service->syncPlanBooks($monthlyPlan, [$book->id]);

        $lifetimePlan = BibSubscriptionPlan::firstOrCreate(
            ['code' => 'demo-vitalicia'],
            [
                'name' => 'Demo vitalicia',
                'description' => 'Plan de demostración sin vencimiento',
                'scope_type' => 'single_book',
                'duration_type' => 'lifetime',
                'duration_value' => null,
                'is_active' => true,
                'sort_order' => 2,
            ]
        );
        $service->syncPlanBooks($lifetimePlan, [$book->id]);

        $lectorRole = config('bibliodata.reader.role', 'Lector');
        Role::findOrCreate($lectorRole);

        $lectors = User::role($lectorRole)->limit(2)->get();
        if ($lectors->isEmpty()) {
            $this->command?->warn('BibSubscriptionDemoSeeder: asigne rol Lector a al menos un usuario.');

            return;
        }

        $org = BibOrganization::firstOrCreate(
            ['document_number' => 'DEMO-ORG-001'],
            [
                'name' => 'Empresa demo biblioteca',
                'email' => 'demo-org@bibliodata.local',
                'status' => 'active',
            ]
        );
        $service->syncOrganizationMembers($org, $lectors->pluck('id')->all());

        if (! BibSubscription::where('subscriber_type', 'individual')->where('user_id', $lectors[0]->id)->exists()) {
            BibSubscription::create([
                'plan_id' => $monthlyPlan->id,
                'subscriber_type' => 'individual',
                'user_id' => $lectors[0]->id,
                'book_id' => $book->id,
                'starts_at' => Carbon::today(),
                'ends_at' => $service->computeEndsAt($monthlyPlan, Carbon::today()),
                'status' => 'active',
                'notes' => 'Suscripción demo individual',
            ]);
        }

        if (! BibSubscription::where('organization_id', $org->id)->exists()) {
            BibSubscription::create([
                'plan_id' => $lifetimePlan->id,
                'subscriber_type' => 'organization',
                'organization_id' => $org->id,
                'book_id' => $book->id,
                'starts_at' => Carbon::today(),
                'ends_at' => null,
                'status' => 'active',
                'notes' => 'Suscripción demo organización',
            ]);
        }
    }
}
