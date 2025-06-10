<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('parameter_code')->unique();
            $table->string('description');
            $table->char('control_type', 3)->nullable()->comment('in=text,sq=select(query),sa=select(json),chq=checkbox(query),chj=checkbox(json),tx=textarea,rgq=range(query),rgj=range(json),fl=file');
            $table->text('json_query_data')->nullable();
            $table->text('value_default')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Parameter::create([
            'parameter_code'    => 'P000001',
            'description'       => 'Valor de IGV',
            'control_type'      => 'in',
            'json_query_data'   => null,
            'value_default'     => '18'
        ]);
        Parameter::create([
            'parameter_code'    => 'P000002',
            'description'       => 'Tipo de operacion Venta - Catalog. 51 Sunat',
            'control_type'      => 'sq',
            'json_query_data'   => 'SELECT id,description FROM sunat_operation_types',
            'value_default'     => '0101'
        ]);


        Parameter::create([
            'parameter_code'    => 'P000003',
            'description'       => 'versión ubl',
            'control_type'      => 'in',
            'json_query_data'   => null,
            'value_default'     => '2.1'
        ]);

        Parameter::create([
            'parameter_code'    => 'P000004',
            'description'       => 'impuesto a la bolsa plástica',
            'control_type'      => 'in',
            'json_query_data'   => null,
            'value_default'     => '0.20'
        ]);

        Parameter::create([
            'parameter_code'    => 'P000006',
            'description'       => 'pruebas sunat',
            'control_type'      => 'in',
            'json_query_data'   => null,
            'value_default'     => null
        ]);

        Parameter::create([
            'parameter_code'    => 'P000007',
            'description'       => 'produccion sunat',
            'control_type'      => 'in',
            'json_query_data'   => null,
            'value_default'     => null
        ]);
        Parameter::create([
            'parameter_code'    => 'P000008',
            'description'       => 'Tipo de afectación del IGV por defecto compras y ventas',
            'control_type'      => 'sq',
            'json_query_data'   => "SELECT id,description FROM sunat_affectation_igv_types",
            'value_default'     => '10'
        ]);

        Parameter::create([
            'parameter_code'    => 'P000009',
            'description'       => 'Tipo de negocio o empresa, para ventas en linea',
            'control_type'      => 'sa',
            'json_query_data'   => '[{"value": "1","label":"Cursos y capacitaciones"},{"value": "2","label":"Productos"},{"value": "3","label":"Productos con Especificaciónes"},{"value": "99","label":"Todos"}]',
            'value_default'     => 1
        ]);

        Parameter::create([
            'parameter_code'    => 'P000010',
            'description'       => 'Token de Editor en linea Tiny',
            'control_type'      => 'tx',
            'json_query_data'   => null,
            'value_default'     => 'xmpsrss1dh49by6nnf83jicfv477cz0o31h0xu3ejsnnhsnz'
        ]);

        Parameter::create([
            'parameter_code'    => 'P000011',
            'description'       => 'Correo electronico a donde llegaran los mensajes del modulo CRM  Buzón de correo',
            'control_type'      => 'in',
            'json_query_data'   => null,
            'value_default'     => 'aracode_atencion@gmail.com'
        ]);

        Parameter::create([
            'parameter_code'    => 'P000012',
            'description'       => 'token para consultas reniec y sunat',
            'control_type'      => 'tx',
            'json_query_data'   => null,
            'value_default'     => 'apis-token-9042.kSfEdAqdNoOW8-fGfu-cKQoWOH7Tzg2Z'
        ]);

        Parameter::create([
            'parameter_code'    => 'P000013',
            'description'       => 'Correo electronico asistente para entencion de mensajes enviado por estudiantes',
            'control_type'      => 'in',
            'json_query_data'   => null,
            'value_default'     => 'aracode_atencion@gmail.com'
        ]);

        Parameter::create([
            'parameter_code'    => 'P000014',
            'description'       => 'Como se mostrará el cuadro de selección en el formulario de nuevo y editar categoría',
            'control_type'      => 'rdj',
            'json_query_data'   => '[{"value": "1","label":"Lista de niveles (básico)"},{"value": "2","label":"Lista en Cascada (Avanzada)"}]',
            'value_default'     => 1
        ]);

        Parameter::create([
            'parameter_code'    => 'P000015',
            'description'       => 'Proveedor de inteligencia artificial, las configuraciones se realizan en el archivo .env de base-aracodeo server-socket nodejs',
            'control_type'      => 'sa',
            'json_query_data'   => '[{"value": "1","label":"OpenAI"},{"value": "2","label":"Gemini AI"}]',
            'value_default'     => 2
        ]);

        Parameter::create([
            'parameter_code'    => 'P000016',
            'description'       => 'Destino para guardar certificado del estudiante',
            'control_type'      => 'rdj',
            'json_query_data'   => '[{"value": "1","label":"Enlace del archivo en repositorios externos"},{"value": "2","label":"Guardar imagen en local o generar certificado automático"}]',
            'value_default'     => 1
        ]);

        Parameter::create([
            'parameter_code'    => 'P000017',
            'description'       => 'Correo Electronico administrador 1',
            'control_type'      => 'in',
            'json_query_data'   => null,
            'value_default'     => 'aracode_atencion@gmail.com'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parameters');
    }
};
