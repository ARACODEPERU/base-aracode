<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_edition_matches', function (Blueprint $table) {
            $table->integer('penalty_rounds')->nullable()->default(5)->after('score_a');
            $table->json('penalties')->nullable()->after('penalty_rounds');
        });
    }

    public function down(): void
    {
        Schema::table('event_edition_matches', function (Blueprint $table) {
            $table->dropColumn(['penalty_rounds', 'penalties']);
        });
    }
};
