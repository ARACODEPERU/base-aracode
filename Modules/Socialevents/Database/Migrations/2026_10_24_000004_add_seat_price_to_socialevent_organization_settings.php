<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('socialevent_organization_settings', 'seat_price')) {
            Schema::table('socialevent_organization_settings', function (Blueprint $table) {
                $table->decimal('seat_price', 10, 2)->default(1.00)->after('default_match_rate');
            });
        }

        DB::table('socialevent_organization_settings')
            ->whereNull('seat_price')
            ->update(['seat_price' => 1.00]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('socialevent_organization_settings', 'seat_price')) {
            Schema::table('socialevent_organization_settings', function (Blueprint $table) {
                $table->dropColumn('seat_price');
            });
        }
    }
};
