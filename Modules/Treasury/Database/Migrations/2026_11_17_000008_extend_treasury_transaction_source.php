<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El libro admite movimientos nacidos de un extracto bancario ("statement").
 *
 * La columna nació como un enum cerrado (auto, manual, backfill) y MySQL
 * truncaba el valor "statement" al registrar una línea del extracto. Se amplía
 * a texto para que nuevos orígenes no vuelvan a requerir otra migración.
 * Los valores existentes se conservan tal cual.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('treasury_transactions')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `treasury_transactions` MODIFY `source` VARCHAR(20) NOT NULL DEFAULT 'manual'");
        }
    }

    public function down(): void
    {
        // No se revierte: volver al enum truncaría los movimientos del extracto.
    }
};
