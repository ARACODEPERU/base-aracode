<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            if (!Schema::hasColumn('aca_certificates_parameters', 'back_size_qr')) {
                $table->integer('back_size_qr')->nullable()->after('back_visible_module');
            }

            if (!Schema::hasColumn('aca_certificates_parameters', 'back_font_align_qr')) {
                $table->string('back_font_align_qr')->nullable()->after('back_size_qr');
            }

            if (!Schema::hasColumn('aca_certificates_parameters', 'back_position_qr_x')) {
                $table->integer('back_position_qr_x')->nullable()->after('back_font_align_qr');
            }

            if (!Schema::hasColumn('aca_certificates_parameters', 'back_position_qr_y')) {
                $table->integer('back_position_qr_y')->nullable()->after('back_position_qr_x');
            }

            if (!Schema::hasColumn('aca_certificates_parameters', 'back_visible_qr')) {
                $table->boolean('back_visible_qr')->default(false)->after('back_position_qr_y');
            }
        });
    }

    public function down(): void
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            // En el down es recomendable hacer lo mismo para evitar errores al revertir
            $columns = [
                'back_size_qr',
                'back_font_align_qr',
                'back_position_qr_x',
                'back_position_qr_y',
                'back_visible_qr',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('aca_certificates_parameters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
