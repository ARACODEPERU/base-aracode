<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_subscription_types', function (Blueprint $table) {
            $table->string('usine')->nullable()->after('title')
                ->comment('Código SUNAT UCE según catálogo de SUNAT Peru');
        });
    }

    public function down(): void
    {
        Schema::table('aca_subscription_types', function (Blueprint $table) {
            $table->dropColumn('usine');
        });
    }
};
