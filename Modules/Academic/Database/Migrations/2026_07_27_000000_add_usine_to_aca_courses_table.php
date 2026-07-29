<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aca_courses', function (Blueprint $table) {
            $table->string('usine')->nullable()->after('description')
                ->comment('Código SUNAT UCE del curso según catálogo de SUNAT Peru');
        });
    }

    public function down(): void
    {
        Schema::table('aca_courses', function (Blueprint $table) {
            $table->dropColumn('usine');
        });
    }
};
