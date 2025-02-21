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
        Schema::create('related_payment_methods', function (Blueprint $table) {
            // Definir las columnas
            $table->unsignedBigInteger('payment_method_id');
            $table->string('sunat_payment_method_type_id', 255); // Mantenido como string

            // Definir la clave primaria compuesta
            $table->primary(['payment_method_id', 'sunat_payment_method_type_id']);

            // Definir las claves foráneas
            $table->foreign('payment_method_id', 'payment_method_id_fk')
                ->references('id')
                ->on('payment_methods')
                ->onDelete('cascade');

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
