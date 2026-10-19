<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socialevent_organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->boolean('is_legacy')->default(false);
            $table->string('status', 30)->default('trial');
            $table->timestamp('trial_started_at')->nullable();
            $table->unsignedSmallInteger('trial_days')->default(30);
            $table->text('read_permissions')->nullable();
            $table->decimal('match_rate', 10, 2)->default(5.00);
            $table->decimal('prepaid_balance', 12, 2)->default(0);
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('socialevent_organization_id')
                ->nullable()
                ->after('id')
                ->constrained('socialevent_organizations')
                ->nullOnDelete();
            $table->string('socialevent_organization_role', 30)->nullable()->after('socialevent_organization_id');
        });

        Schema::table('even_events', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('socialevent_organizations')
                ->nullOnDelete();
            $table->index('organization_id');
        });

        Schema::table('event_teams', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('socialevent_organizations')
                ->nullOnDelete();
            $table->unique(['organization_id', 'name'], 'event_teams_org_name_unique');
        });

        Schema::create('socialevent_organization_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('socialevent_organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30)->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
            $table->unique('user_id');
        });

        Schema::create('socialevent_organization_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('socialevent_organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60);
            $table->text('reason')->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'created_at']);
        });

        $adminId = null;
        if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
            $adminId = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', App\Models\User::class)
                ->whereIn('roles.name', ['admin', 'Administrador'])
                ->orderBy('model_has_roles.model_id')
                ->value('model_has_roles.model_id');
        }

        $legacyId = DB::table('socialevent_organizations')->insertGetId([
            'name' => 'Organización histórica',
            'is_legacy' => true,
            'status' => 'active',
            'trial_days' => 0,
            'match_rate' => 5,
            'prepaid_balance' => 0,
            'created_by' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($adminId) {
            DB::table('users')->where('id', $adminId)->update([
                'socialevent_organization_id' => $legacyId,
                'socialevent_organization_role' => 'owner',
            ]);
        }

        if ($adminId) {
            DB::table('socialevent_organization_users')->insert([
                'organization_id' => $legacyId,
                'user_id' => $adminId,
                'role' => 'owner',
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('even_events')->whereNull('organization_id')->update(['organization_id' => $legacyId]);
        DB::table('event_teams')->whereNull('organization_id')->update(['organization_id' => $legacyId]);
        DB::table('socialevent_organization_audits')->insert([
            'organization_id' => $legacyId,
            'user_id' => $adminId,
            'action' => 'legacy_organization_created',
            'reason' => 'Migración de datos históricos de eventos y equipos.',
            'changes' => json_encode(['events_assigned' => DB::table('even_events')->where('organization_id', $legacyId)->count(), 'teams_assigned' => DB::table('event_teams')->where('organization_id', $legacyId)->count()]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('socialevent_organization_audits');
        Schema::dropIfExists('socialevent_organization_users');

        Schema::table('event_teams', function (Blueprint $table) {
            $table->dropUnique('event_teams_org_name_unique');
            $table->dropConstrainedForeignId('organization_id');
            $table->unique('name');
        });

        Schema::table('even_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('socialevent_organization_role');
            $table->dropConstrainedForeignId('socialevent_organization_id');
        });

        Schema::dropIfExists('socialevent_organizations');
    }
};
