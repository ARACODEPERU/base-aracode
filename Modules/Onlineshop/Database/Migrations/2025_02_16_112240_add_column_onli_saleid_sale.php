<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sunat_payment_method_types', function (Blueprint $table) {
            // Cambia la columna 'id' a string de 255 con el collation adecuado.
            // Utilizamos el método change() para alterar la columna.
            $table->string('id', 255)->collation('utf8mb4_unicode_ci')->change();
        });

        Schema::create('related_payment_methods', function (Blueprint $table) {
            // Definir la columna payment_method_id (mantiene unsignedBigInteger)
            $table->unsignedBigInteger('payment_method_id');

            // Definir la columna sunat_payment_method_type_id como string de 255 y con collation igual al de la otra tabla
            $table->string('sunat_payment_method_type_id', 255)->collation('utf8mb4_unicode_ci');

            // Definir la clave primaria compuesta
            $table->primary(['payment_method_id', 'sunat_payment_method_type_id']);

            // Definir la clave foránea para payment_method_id
            $table->foreign('payment_method_id', 'payment_method_id_fk')
                ->references('id')
                ->on('payment_methods')
                ->onDelete('cascade');

            // Definir la clave foránea para sunat_payment_method_type_id
            $table->foreign('sunat_payment_method_type_id', 'sunat_payment_method_type_id_fk')
                ->references('id')
                ->on('sunat_payment_method_types')
                ->onDelete('cascade');
        });

        Schema::table('onli_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('nota_sale_id')->nullable()->comment('para relasionar con el modulo de ventas y hacer la boleta o factura electronica');
        });

        DB::table('payment_methods')->insert([
            ['id' => 6, 'description' => 'Mercadopago'],
            ['id' => 7, 'description' => 'Giro']
        ]);

        DB::table('related_payment_methods')->insert([
            ['payment_method_id' => 1, 'sunat_payment_method_type_id' => '009'],
            ['payment_method_id' => 2, 'sunat_payment_method_type_id' => '001'],
            ['payment_method_id' => 3, 'sunat_payment_method_type_id' => '001'],
            ['payment_method_id' => 4, 'sunat_payment_method_type_id' => '001'],
            ['payment_method_id' => 5, 'sunat_payment_method_type_id' => '001'],
            ['payment_method_id' => 6, 'sunat_payment_method_type_id' => '001'],
            ['payment_method_id' => 7, 'sunat_payment_method_type_id' => '002'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('related_payment_methods');

        Schema::table('onli_sales', function (Blueprint $table) {
            $table->dropColumn('nota_sale_id');
        });
    }
};
