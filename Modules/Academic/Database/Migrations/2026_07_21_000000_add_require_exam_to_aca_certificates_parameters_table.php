<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->boolean('require_exam_to_download')->nullable()->default(false)
                ->comment('Si es true, solo se descarga al aprobar examen con nota >=11. Si false, se descarga directo');
        });
    }

    public function down(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            $table->dropColumn('require_exam_to_download');
        });
    }
};
