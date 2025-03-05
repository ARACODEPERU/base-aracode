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
        Schema::table('aca_modules', function (Blueprint $table) {
            $table->unsignedBigInteger('teacher_id')->nullable()->comment('se registre en caso el modulo lo de un docente');
            $table->foreign('teacher_id', 'module_teacher_id_fk')
                ->references('id')
                ->on('aca_teachers')
                ->onDelete('set null');;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_modules', function (Blueprint $table) {
            $table->dropForeign('module_teacher_id_fk');
            $table->dropColumn('teacher_id');
        });
    }
};
