<?php

namespace Tests\Unit\Modules\Commercial\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Esquema mínimo del módulo Commercial para las pruebas.
 *
 * El suite no puede correr todas las migraciones de la aplicación sobre sqlite (hay
 * migraciones con esquema MySQL), así que la prueba del borrado de negociaciones arma
 * solo lo que necesita: las tablas de spatie, las tablas de las que cuelga la
 * negociación (cliente, usuario y billeteras), los registros que crea el proceso de
 * aprobación (alumno, venta y comprobante) y las tablas de la negociación con sus
 * hijos —estas últimas ejecutando sus migraciones reales del módulo—.
 *
 * No se usa RefreshDatabase justamente por ese motivo; el esquema se crea y se
 * destruye en cada prueba.
 */
trait BuildsCommercialNegotiationSchema
{
    /** Migraciones reales del módulo que definen la negociación y sus hijos. */
    private const MODULE_MIGRATIONS = [
        'Modules/Commercial/Database/Migrations/2026_08_10_100000_create_commercial_negotiations_table.php',
        'Modules/Commercial/Database/Migrations/2026_08_10_100001_create_commercial_negotiation_items_table.php',
        'Modules/Commercial/Database/Migrations/2026_08_11_100000_create_commercial_negotiation_invoices_table.php',
        'Modules/Commercial/Database/Migrations/2026_08_11_120000_add_sale_columns_to_commercial_negotiations_table.php',
        'Modules/Commercial/Database/Migrations/2026_08_19_000000_create_commercial_negotiation_company_billetera_table.php',
        'Modules/Commercial/Database/Migrations/2026_10_02_000000_add_document_emission_date_to_commercial_negotiations_table.php',
    ];

    private function createCommercialSchema(): void
    {
        // Cliente y usuarios (el asesor y la cuenta del cliente).
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('number')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('company_billeteras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('billetera_id')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('qr_image')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        // Registros que el proceso de aprobación crea y que el borrado NO debe tocar.
        Schema::create('aca_students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code')->nullable();
            $table->unsignedBigInteger('person_id');
            $table->boolean('new_student')->nullable();
            $table->unsignedBigInteger('user_id_registers')->nullable();
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('local_id');
            $table->decimal('total', 12, 2)->nullable();
            $table->decimal('advancement', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('sale_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('serie_id');
            $table->string('number');
            $table->string('invoice_type_doc', 2)->nullable();
            $table->string('invoice_serie', 10)->nullable();
            $table->string('invoice_correlative', 20)->nullable();
            $table->string('invoice_status')->nullable();
            $table->timestamps();
        });

        // Tablas de spatie (roles y permisos por nombre, como en el resto del proyecto).
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_type', 'model_id']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_type', 'model_id']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        foreach (self::MODULE_MIGRATIONS as $migration) {
            (require base_path($migration))->up();
        }
    }

    private function dropCommercialSchema(): void
    {
        // Se destruye tabla por tabla (y no con el down() de cada migración) para que las
        // migraciones que solo agregan columnas no dependan del soporte de dropColumn de sqlite.
        foreach ([
            'commercial_negotiation_company_billetera',
            'commercial_negotiation_invoices',
            'commercial_negotiation_items',
            'commercial_negotiations',
            'sale_documents',
            'sales',
            'aca_students',
            'company_billeteras',
            'role_has_permissions',
            'model_has_permissions',
            'model_has_roles',
            'permissions',
            'roles',
            'users',
            'people',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
}
