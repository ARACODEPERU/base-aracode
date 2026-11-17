<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Categorías base para el bloque de pagos y gastos. Idempotente: si la
 * categoría ya existe no se toca (ni su color ni su estado de sistema).
 */
return new class extends Migration
{
    /** @var array<int, array{name: string, color: string}> */
    private const CATEGORIES = [
        ['name' => 'Pago a proveedor', 'color' => '#f59e0b'],
        ['name' => 'Gasto imprevisto', 'color' => '#ef4444'],
        ['name' => 'Gasto recurrente', 'color' => '#8b5cf6'],
        ['name' => 'Pago de personal', 'color' => '#0ea5e9'],
        ['name' => 'Servicios e insumos', 'color' => '#10b981'],
        ['name' => 'Compra de mercadería', 'color' => '#f97316'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('treasury_categories')) {
            return;
        }

        $now = now();

        foreach (self::CATEGORIES as $category) {
            $exists = DB::table('treasury_categories')->where('name', $category['name'])->exists();

            if ($exists) {
                continue;
            }

            DB::table('treasury_categories')->insert([
                'name' => $category['name'],
                'applies_to' => 'expense',
                'is_system' => true,
                'color' => $category['color'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Las categorías del sistema no se eliminan.
    }
};
