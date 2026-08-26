<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_courses', function (Blueprint $table) {
            $table->boolean('round_grades')->default(false)->after('auto_certificate')->comment('Redondear notas de módulo a entero antes de calcular promedio final en certificados');
        });
    }

    public function down(): void
    {
        Schema::table('aca_courses', function (Blueprint $table) {
            $table->dropColumn('round_grades');
        });
    }
};
