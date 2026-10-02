<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_school_sections', function (Blueprint $table) {
            $table->unsignedBigInteger('auxiliary_person_id')->nullable()->after('tutor_person_id')->comment('Docente auxiliar (typ. Inicial)');
            $table->foreign('auxiliary_person_id')->references('id')->on('people')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aca_school_sections', function (Blueprint $table) {
            $table->dropForeign(['auxiliary_person_id']);
            $table->dropColumn('auxiliary_person_id');
        });
    }
};
