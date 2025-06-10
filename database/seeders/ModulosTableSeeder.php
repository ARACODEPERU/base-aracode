<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModulosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('modulos')->insert([
            ['identifier' => 'M001', 'description' => 'Compras'],
            ['identifier' => 'M002', 'description' => 'Ventas'],
            ['identifier' => 'M003', 'description' => 'Facturación Electrónica'],
            ['identifier' => 'M004', 'description' => 'Ventas en línea'],
            ['identifier' => 'M005', 'description' => 'CMS'],
            ['identifier' => 'M006', 'description' => 'Blog'],
            ['identifier' => 'M007', 'description' => 'Académico'],
            ['identifier' => 'M008', 'description' => 'Gestión de Clientes y Comunicación'],
            ['identifier' => 'M009', 'description' => 'Salud'],
            ['identifier' => 'M010', 'description' => 'Odontología'],
            ['identifier' => 'M011', 'description' => 'Helpdesk'],
            ['identifier' => 'M012', 'description' => 'Restaurante'],
        ]);
    }
}
