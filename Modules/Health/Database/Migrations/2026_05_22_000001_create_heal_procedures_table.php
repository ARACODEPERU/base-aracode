<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heal_procedures', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('category', 80)->nullable();
            $table->text('description')->nullable();
            $table->decimal('default_price', 12, 2)->default(0);
            $table->string('currency_type_id', 3)->default('PEN');
            $table->boolean('is_consultation')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('heal_procedures')->insert([
            [
                'name' => 'Consulta',
                'category' => 'Consulta',
                'description' => 'Consulta medica u odontologica inicial o de control segun regla de recobro.',
                'default_price' => 50,
                'currency_type_id' => 'PEN',
                'is_consultation' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Endodoncia',
                'category' => 'Odontologia',
                'description' => 'Tratamiento de conductos.',
                'default_price' => 250,
                'currency_type_id' => 'PEN',
                'is_consultation' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Extraccion dental',
                'category' => 'Odontologia',
                'description' => 'Extraccion simple de pieza dental.',
                'default_price' => 120,
                'currency_type_id' => 'PEN',
                'is_consultation' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Restauracion dental',
                'category' => 'Odontologia',
                'description' => 'Restauracion con resina u otro material.',
                'default_price' => 100,
                'currency_type_id' => 'PEN',
                'is_consultation' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Profilaxis',
                'category' => 'Odontologia',
                'description' => 'Limpieza dental profesional.',
                'default_price' => 80,
                'currency_type_id' => 'PEN',
                'is_consultation' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_procedures');
    }
};
