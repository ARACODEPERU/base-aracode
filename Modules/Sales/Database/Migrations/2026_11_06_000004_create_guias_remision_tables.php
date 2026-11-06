<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas nuevas y aisladas: no alteran ventas, inventario ni comprobantes.
     * Todos los campos propios del traslado son nullable para que se puedan
     * acoplar después otros casos (traslado entre establecimientos, importación,
     * exportación) sin migrar de nuevo.
     */
    public function up(): void
    {
        if (! Schema::hasTable('guias_remision')) {
            Schema::create('guias_remision', function (Blueprint $table) {
                $table->id();

                // Identificación del documento (tipo 09 = guía remitente).
                $table->string('tipo_doc', 2)->default('09');
                $table->string('serie', 10);
                $table->string('correlativo', 20);
                $table->dateTime('fecha_emision');

                // Motivo y modalidad del traslado (catálogos SUNAT).
                $table->string('cod_traslado', 2)->default('01');
                $table->string('des_traslado', 255)->nullable();
                $table->string('mod_traslado', 2)->default('02');
                $table->decimal('peso_total', 12, 3)->default(0);
                $table->string('und_peso_total', 5)->default('KGM');
                $table->unsignedInteger('num_bultos')->nullable();
                $table->json('indicadores')->nullable();
                $table->string('sustento_peso', 255)->nullable();

                // Punto de partida.
                $table->string('partida_ubigeo', 6)->nullable();
                $table->string('partida_direccion', 255)->nullable();
                $table->string('partida_cod_local', 10)->nullable();
                $table->string('partida_ruc', 11)->nullable();

                // Punto de llegada.
                $table->string('llegada_ubigeo', 6)->nullable();
                $table->string('llegada_direccion', 255)->nullable();
                $table->string('llegada_cod_local', 10)->nullable();
                $table->string('llegada_ruc', 11)->nullable();

                // Destinatario (a quien se entrega la mercadería).
                $table->string('destinatario_tipo_doc', 2)->default('6');
                $table->string('destinatario_num_doc', 15);
                $table->string('destinatario_razon_social', 255);
                $table->string('destinatario_email', 150)->nullable();

                // Remitente distinto al emisor (traslados de terceros) y comprador.
                $table->string('tercero_tipo_doc', 2)->nullable();
                $table->string('tercero_num_doc', 15)->nullable();
                $table->string('tercero_razon_social', 255)->nullable();
                $table->string('comprador_tipo_doc', 2)->nullable();
                $table->string('comprador_num_doc', 15)->nullable();
                $table->string('comprador_razon_social', 255)->nullable();

                // Transportista (público o privado).
                $table->string('transportista_tipo_doc', 2)->default('6');
                $table->string('transportista_num_doc', 15);
                $table->string('transportista_razon_social', 255);
                $table->string('transportista_nro_mtc', 20)->nullable();

                // Vehículo principal.
                $table->string('vehiculo_placa', 15)->nullable();
                $table->string('vehiculo_nro_circulacion', 20)->nullable();
                $table->string('vehiculo_cod_emisor', 10)->nullable();
                $table->string('vehiculo_nro_autorizacion', 20)->nullable();

                // Conductor principal.
                $table->string('conductor_tipo_doc', 2)->nullable();
                $table->string('conductor_num_doc', 15)->nullable();
                $table->string('conductor_nombres', 150)->nullable();
                $table->string('conductor_apellidos', 150)->nullable();
                $table->string('conductor_licencia', 20)->nullable();

                // Vínculo comercial (opcional): venta y comprobante que sustenta el traslado.
                $table->unsignedBigInteger('sale_id')->nullable();
                $table->unsignedBigInteger('sale_document_id')->nullable();
                $table->string('doc_relacionado_tipo', 2)->nullable();
                $table->string('doc_relacionado_numero', 20)->nullable();

                $table->string('observacion', 500)->nullable();

                // Estado local + trazabilidad SUNAT.
                $table->string('status', 30)->default('registrado');
                $table->string('sunat_send_status', 20)->nullable();
                $table->string('ticket', 100)->nullable();
                $table->string('cdr', 500)->nullable();
                $table->string('xml', 500)->nullable();
                $table->string('hash', 100)->nullable();
                $table->string('response_code', 10)->nullable();
                $table->text('response_description')->nullable();
                $table->json('notes')->nullable();

                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('anio', 4)->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['serie', 'correlativo']);
                $table->index('status');
                $table->index('sale_id');
                $table->index('sale_document_id');
            });
        }

        if (! Schema::hasTable('guia_remision_detalles')) {
            Schema::create('guia_remision_detalles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('guia_remision_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->unsignedBigInteger('sale_document_item_id')->nullable();

                $table->string('codigo', 50)->nullable();
                $table->string('descripcion', 255);
                $table->string('unidad', 5)->default('NIU');
                $table->decimal('cantidad', 12, 4)->default(1);
                $table->string('cod_prod_sunat', 20)->nullable();
                $table->decimal('peso', 12, 3)->nullable();
                $table->json('atributos')->nullable();
                $table->unsignedInteger('orden')->nullable();

                $table->timestamps();

                $table->foreign('guia_remision_id')
                    ->references('id')
                    ->on('guias_remision')
                    ->cascadeOnDelete();

                $table->index('guia_remision_id');
                $table->index('product_id');
            });
        }
    }

    public function down(): void
    {
        // Nunca se eliminan datos en producción: la migración es reversible
        // solo de forma manual y deliberada.
    }
};
