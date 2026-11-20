<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Precios editables por proveedor y modelo, en dólares por millón de tokens.
 *
 * El costo que muestra el módulo es una estimación calculada con estas
 * tarifas. Se cargan valores iniciales para los modelos por defecto.
 */
return new class extends Migration
{
    private const DEFAULTS = [
        ['provider' => 'openai', 'model' => 'gpt-4.1-mini', 'input' => 0.40, 'output' => 1.60],
        ['provider' => 'openai', 'model' => 'gpt-4.1', 'input' => 2.00, 'output' => 8.00],
        ['provider' => 'gemini', 'model' => 'gemini-2.5-flash', 'input' => 0.30, 'output' => 2.50],
        ['provider' => 'gemini', 'model' => 'gemini-2.5-pro', 'input' => 1.25, 'output' => 10.00],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('ai_model_prices')) {
            Schema::create('ai_model_prices', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 20);
                $table->string('model', 100);
                $table->decimal('input_price_per_million', 12, 6)->default(0);
                $table->decimal('output_price_per_million', 12, 6)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->unique(['provider', 'model']);
            });
        }

        foreach (self::DEFAULTS as $price) {
            DB::table('ai_model_prices')->updateOrInsert(
                ['provider' => $price['provider'], 'model' => $price['model']],
                [
                    'input_price_per_million' => $price['input'],
                    'output_price_per_million' => $price['output'],
                    'currency' => 'USD',
                    'active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_model_prices');
    }
};
