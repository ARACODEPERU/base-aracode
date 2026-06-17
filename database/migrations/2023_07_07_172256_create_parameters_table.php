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
            $table->char('control_type', 3)->nullable()->comment('in=text,sq=select(query),sa=select(json),chq=checkbox(query),chj=checkbox(json),tx=textarea,rgq=range(query),rgj=range(json),fl=file,chx=Checkbox');
            $table->text('json_query_data')->nullable();
            $table->text('value_default')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $parameters = [
            [
                'parameter_code' => 'P000001',
                'description' => 'Valor de IGV',
                'control_type' => 'in',
                'json_query_data' => null,
                'value_default' => '18',
            ],
            [
                'parameter_code' => 'P000002',
                'description' => 'Tipo de operacion Venta - Catalog. 51 Sunat',
                'control_type' => 'sq',
                'json_query_data' => 'SELECT id, description FROM sunat_operation_types;',
                'value_default' => '0101',
            ],
            [
                'parameter_code' => 'P000003',
                'description' => 'versión ubl',
                'control_type' => 'in',
                'json_query_data' => null,
                'value_default' => '2.1',
            ],
            [
                'parameter_code' => 'P000004',
                'description' => 'impuesto a la bolsa plástica',
                'control_type' => 'in',
                'json_query_data' => null,
                'value_default' => '0.20',
            ],
            [
                'parameter_code' => 'P000006',
                'description' => 'pruebas sunat',
                'control_type' => 'in',
                'json_query_data' => null,
                'value_default' => null,
            ],
            [
                'parameter_code' => 'P000007',
                'description' => 'produccion sunat',
                'control_type' => 'in',
                'json_query_data' => null,
                'value_default' => null,
            ],
            [
                'parameter_code' => 'P000008',
                'description' => 'Tipo de afectación del IGV por defecto compras y ventas',
                'control_type' => 'sq',
                'json_query_data' => 'SELECT id, description FROM sunat_affectation_igv_types;',
                'value_default' => '10',
            ],
            [
                'parameter_code' => 'P000009',
                'description' => 'Tipo de negocio o empresa, para ventas en linea',
                'control_type' => 'sa',
                'json_query_data' => '[{"value": "1","label":"Cursos y capacitaciones"},{"value": "2","label":"Productos"},{"value": "3","label":"Productos con Especificaciónes"},{"value": "4","label":"Servicios"},{"value": "99","label":"Todos"}]',
                'value_default' => '4',
            ],
            [
                'parameter_code' => 'P000010',
                'description' => 'Token de Editor en linea Tiny',
                'control_type' => 'tx',
                'json_query_data' => null,
                'value_default' => 'xmpsrss1dh49by6nnf83jicfv477cz0o31h0xu3ejsnnhsnz',
            ],
            [
                'parameter_code' => 'P000011',
                'description' => 'Correo electronico a donde llegaran los mensajes del modulo CRM  Buzón de correo',
                'control_type' => 'tx',
                'json_query_data' => null,
                'value_default' => 'grandejosh.josh@gmail.com',
            ],
            [
                'parameter_code' => 'P000012',
                'description' => 'token para consultas reniec y sunat',
                'control_type' => 'tx',
                'json_query_data' => null,
                'value_default' => 'apis-token-9042.kSfEdAqdNoOW8-fGfu-cKQoWOH7Tzg2Z',
            ],
            [
                'parameter_code' => 'P000013',
                'description' => 'Correo electronico remitente de mensajes del modulo CRM, correos enviados a estudiantes',
                'control_type' => 'in',
                'json_query_data' => null,
                'value_default' => 'grandejosh.josh@gmail.com',
            ],
            [
                'parameter_code' => 'P000014',
                'description' => 'Como se mostrará el cuadro de selección en el formulario de nuevo y editar categoría',
                'control_type' => 'rdj',
                'json_query_data' => '[{"value": "1","label":"Lista de un nivel (básico)"},{"value": "2","label":"Lista en Cascada (Avanzada)"}]',
                'value_default' => '2',
            ],
            [
                'parameter_code' => 'P000015',
                'description' => 'Proveedor de inteligencia artificial, las configuraciones se realizan en el archivo .env de base-aracodeo server-socket nodejs',
                'control_type' => 'sa',
                'json_query_data' => '[{"value": "1","label":"OpenAI"},{"value": "2","label":"Gemini AI"}]',
                'value_default' => '2',
            ],
            [
                'parameter_code' => 'P000016',
                'description' => 'Guardar certificado del estudiante',
                'control_type' => 'rdj',
                'json_query_data' => '[{"value": "1","label":"Enlace del archivo en repositorios externos"},{"value": "2","label":"Guardar imagen en local o generar certificado automático"}]',
                'value_default' => '2',
            ],
            [
                'parameter_code' => 'P000017',
                'description' => 'Correo Electronico administrador jose ronal',
                'control_type' => 'in',
                'json_query_data' => null,
                'value_default' => 'jsuclupe@globalcpaperu.com',
            ],
            [
                'parameter_code' => 'P000018',
                'description' => 'Activar campos para datos adicionales del curso (Descripción del Certificado, Aplicación de Descuento)',
                'control_type' => 'chx',
                'json_query_data' => null,
                'value_default' => 'true',
            ],
            [
                'parameter_code' => 'P000020',
                'description' => 'Orede del nombre completo de los usuarios',
                'control_type' => 'rdj',
                'json_query_data' => '[{"value": "1","label":"Pallido paterno Apellido materno Nombres"},{"value": "2","label":"Nombres Pallido paterno Apellido materno"},{"value": "3","label":"Nombres Pallido paterno"}]',
                'value_default' => '1',
            ],
            [
                'parameter_code' => 'P000021',
                'description' => 'Activar las suscripciones para el módulo académico, si esta en falso todos los formularios vinculados a suscripciones se ocultarán',
                'control_type' => 'chx',
                'json_query_data' => null,
                'value_default' => 'true',
            ],
            [
                'parameter_code' => 'P000022',
                'description' => 'Scripts para colocar en el header de las páginas',
                'control_type' => 'tx',
                'json_query_data' => null,
                'value_default' => '
    &lt;script async src=&quot;https://www.googletagmanager.com/gtag/js?id=G-EKDSRLYXFM&quot;&gt;&lt;/script&gt;
    &lt;script&gt;
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag(&#039;js&#039;, new Date());
    gtag(&#039;config&#039;, &#039;G-EKDSRLYXFM&#039;);
    &lt;/script&gt;',
            ],
            [
                'parameter_code' => 'P000023',
                'description' => 'TPV activar o desactivar',
                'control_type' => 'chx',
                'json_query_data' => null,
                'value_default' => 'false',
            ],
            [
                'parameter_code' => 'P000025',
                'description' => 'Modo de descuento en comprobantes electrónicos (boleta y factura)',
                'control_type' => 'rdj',
                'json_query_data' => '[{"value": "1", "label": "Trabajar con precio final (no enviar descuento separado a SUNAT)"},{"value": "2", "label": "Enviar descuento por ítem a SUNAT"}]',
                'value_default' => '2',
            ],
            [
                'parameter_code' => 'P000026',
                'description' => 'Plantilla A4 para impresión de documentos de ventas',
                'control_type' => 'rdj',
                'json_query_data' => '[{"value":"1","label":"Formato plantilla 1"},{"value":"2","label":"Formato plantilla 2"},{"value":"3","label":"Formato plantilla 3"},{"value":"4","label":"Formato plantilla 4"}]',
                'value_default' => '1',
            ],
            [
                'parameter_code' => 'P000027',
                'description' => 'Modulos activos',
                'control_type' => 'chq',
                'json_query_data' => 'SELECT * FROM modulos',
                'value_default' => '1',
            ],
        ];

        foreach ($parameters as $parameter) {
            Parameter::create($parameter);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parameters');
    }
};
