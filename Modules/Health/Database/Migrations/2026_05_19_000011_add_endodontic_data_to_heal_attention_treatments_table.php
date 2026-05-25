<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heal_attention_treatments', function (Blueprint $table) {
            $table->json('endodontic_data')->nullable()->after('indications');
        });
    }

    public function down(): void
    {
        Schema::table('heal_attention_treatments', function (Blueprint $table) {
            $table->dropColumn('endodontic_data');
        });
    }
};
