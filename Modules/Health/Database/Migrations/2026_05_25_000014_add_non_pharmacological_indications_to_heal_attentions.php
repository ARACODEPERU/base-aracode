<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heal_attentions', function (Blueprint $table) {
            $table->text('non_pharmacological_indications')->nullable()->after('observations');
        });
    }

    public function down(): void
    {
        Schema::table('heal_attentions', function (Blueprint $table) {
            $table->dropColumn('non_pharmacological_indications');
        });
    }
};
