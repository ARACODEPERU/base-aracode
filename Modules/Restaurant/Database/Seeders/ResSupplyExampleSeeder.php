<?php

namespace Modules\Restaurant\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Restaurant\Entities\ResSupply;
use Modules\Restaurant\Services\ResSupplyStockService;

class ResSupplyExampleSeeder extends Seeder
{
    /**
     * Insumos de ejemplo para desarrollo/demo.
     */
    public function run(): void
    {
        if (ResSupply::count() > 0) {
            return;
        }

        $stockService = app(ResSupplyStockService::class);

        $examples = [
            ['name' => 'Arroz', 'unit' => 'kg', 'stock' => 10, 'stock_min' => 2],
            ['name' => 'Pollo', 'unit' => 'kg', 'stock' => 5, 'stock_min' => 1],
            ['name' => 'Aceite', 'unit' => 'litro', 'stock' => 3, 'stock_min' => 1],
            ['name' => 'Cebolla', 'unit' => 'kg', 'stock' => 2, 'stock_min' => 0.5],
            ['name' => 'Tomate', 'unit' => 'kg', 'stock' => 1.5, 'stock_min' => 0.5],
            ['name' => 'Sal', 'unit' => 'kg', 'stock' => 1, 'stock_min' => 0.2],
            ['name' => 'Huevos', 'unit' => 'docena', 'stock' => 2, 'stock_min' => 1],
            ['name' => 'Leche', 'unit' => 'litro', 'stock' => 4, 'stock_min' => 2],
        ];

        foreach ($examples as $row) {
            $supply = ResSupply::create([
                'name' => $row['name'],
                'unit' => $row['unit'],
                'stock' => 0,
                'stock_min' => $row['stock_min'],
                'status' => true,
            ]);
            $stockService->addPurchase($supply->id, $row['stock'], 'Stock inicial (seeder)');
        }
    }
}
