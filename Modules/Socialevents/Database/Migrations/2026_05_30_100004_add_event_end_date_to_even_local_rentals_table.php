<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('even_local_rentals', function (Blueprint $table) {
            $table->date('event_end_date')->nullable()->after('event_date');
        });

        DB::table('even_local_rentals')
            ->whereNull('event_end_date')
            ->update(['event_end_date' => DB::raw('event_date')]);
    }

    public function down(): void
    {
        Schema::table('even_local_rentals', function (Blueprint $table) {
            $table->dropColumn('event_end_date');
        });
    }
};
