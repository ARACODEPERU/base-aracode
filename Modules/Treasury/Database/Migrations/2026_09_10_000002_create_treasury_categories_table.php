<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categorías de movimientos de tesorería (ingresos y egresos).
     * Las categorías del sistema (is_system) alimentan el backfill y los
     * registros automáticos; el cliente puede crear las suyas.
     */
    public function up(): void
    {
        Schema::create('treasury_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('applies_to', ['income', 'expense', 'both'])->default('both');
            $table->boolean('is_system')->default(false)->comment('Categoría base del sistema, no se puede eliminar');
            $table->string('color', 9)->nullable()->comment('Color hex para la UI');
            $table->timestamps();
        });

        DB::table('treasury_categories')->insert([
            ['name' => 'Cobro de venta', 'applies_to' => 'income', 'is_system' => true, 'color' => '#22c55e'],
            ['name' => 'Abono de cuota', 'applies_to' => 'income', 'is_system' => true, 'color' => '#0ea5e9'],
            ['name' => 'Pago de contrato/servicio', 'applies_to' => 'expense', 'is_system' => true, 'color' => '#f97316'],
            ['name' => 'Recibo por honorarios', 'applies_to' => 'expense', 'is_system' => true, 'color' => '#a855f7'],
            ['name' => 'Pago de planillas', 'applies_to' => 'expense', 'is_system' => true, 'color' => '#ef4444'],
            ['name' => 'Comisión/mantenimiento bancario', 'applies_to' => 'expense', 'is_system' => true, 'color' => '#64748b'],
            ['name' => 'Otros ingresos', 'applies_to' => 'income', 'is_system' => true, 'color' => '#14b8a6'],
            ['name' => 'Otros egresos', 'applies_to' => 'expense', 'is_system' => true, 'color' => '#94a3b8'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_categories');
    }
};
