<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('socialevent_organizations', 'seat_limit')) {
            Schema::table('socialevent_organizations', function (Blueprint $table) {
                $table->unsignedSmallInteger('seat_limit')->nullable()->default(1)->after('prepaid_balance');
            });
        }

        // La organización histórica no compra espacios: el superadministrador
        // la administra sin límite.
        DB::table('socialevent_organizations')
            ->where('is_legacy', true)
            ->update(['seat_limit' => null]);

        // Las organizaciones que ya tienen varios usuarios conservan su equipo.
        // El límite se ajusta al uso actual y solo se bloquean las altas nuevas.
        DB::table('socialevent_organizations')
            ->where('is_legacy', false)
            ->orderBy('id')
            ->each(function ($organization) {
                $members = DB::table('socialevent_organization_users')
                    ->where('organization_id', $organization->id)
                    ->count();

                DB::table('socialevent_organizations')
                    ->where('id', $organization->id)
                    ->update(['seat_limit' => max(1, $members)]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('socialevent_organizations', 'seat_limit')) {
            Schema::table('socialevent_organizations', function (Blueprint $table) {
                $table->dropColumn('seat_limit');
            });
        }
    }
};
