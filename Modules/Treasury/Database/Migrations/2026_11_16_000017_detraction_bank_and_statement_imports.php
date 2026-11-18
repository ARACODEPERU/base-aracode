<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Conciliación asistida y datos de la cuenta de detracción.
 *
 * - La cuenta de detracción guarda el banco y su número de cuenta: el cliente
 *   (o la empresa, en auto-detracción) deposita allí el 12 %, 10 % o 4 % del
 *   total de la factura. El IGV sigue siendo 18 %: ese depósito solo cubre la
 *   parte tributaria y el resto queda disponible para la empresa.
 * - Las partidas del extracto pueden venir de una importación (CSV, Excel u
 *   OFX) y conservar su referencia bancaria.
 * - Cada importación queda registrada con su archivo, su mapeo y su resumen.
 *
 * Es idempotente: se puede correr sobre una base que ya tenga los cambios.
 */
return new class extends Migration
{
    private const MODULE_IDENTIFIER = 'M023';

    private const MODULE_DESCRIPTION = 'Tesorería';

    /** @var array<int, string> */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    public function up(): void
    {
        $this->addAccountDetails();
        // La tabla de importaciones se crea antes: las partidas la referencian.
        $this->createImportsTable();
        $this->addItemColumns();
        $this->seedPermission();
    }

    public function down(): void
    {
        // Migración aditiva: no se elimina el rastro de las importaciones.
    }

    /**
     * Banco y número de cuenta (se usan en la cuenta de detracción).
     */
    private function addAccountDetails(): void
    {
        if (! Schema::hasTable('treasury_accounts')) {
            return;
        }

        Schema::table('treasury_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('treasury_accounts', 'bank_name')) {
                $table->string('bank_name', 80)->nullable()->after('label')
                    ->comment('Banco donde está la cuenta (detracción: Banco de la Nación)');
            }

            if (! Schema::hasColumn('treasury_accounts', 'account_number')) {
                $table->string('account_number', 40)->nullable()->after('bank_name')
                    ->comment('Número de cuenta o de detracciones');
            }
        });

        // La cuenta de detracción es, por norma, del Banco de la Nación.
        DB::table('treasury_accounts')
            ->where('type', 'detraccion')
            ->whereNull('bank_name')
            ->update(['bank_name' => 'Banco de la Nación', 'updated_at' => now()]);
    }

    /**
     * Origen y referencia de cada partida del extracto.
     */
    private function addItemColumns(): void
    {
        if (! Schema::hasTable('treasury_reconciliation_items')) {
            return;
        }

        Schema::table('treasury_reconciliation_items', function (Blueprint $table) {
            if (! Schema::hasColumn('treasury_reconciliation_items', 'reference')) {
                $table->string('reference', 120)->nullable()->after('description')
                    ->comment('Referencia u operación del banco');
            }

            if (! Schema::hasColumn('treasury_reconciliation_items', 'source')) {
                $table->string('source', 20)->default('manual')->after('type')
                    ->comment('manual | import');
            }

            if (! Schema::hasColumn('treasury_reconciliation_items', 'statement_import_id')) {
                $table->foreignId('statement_import_id')->nullable()->after('source')
                    ->constrained('treasury_statement_imports')->nullOnDelete();
            }
        });
    }

    /**
     * Registro de cada extracto importado (archivo, mapeo y resultado).
     */
    private function createImportsTable(): void
    {
        if (Schema::hasTable('treasury_statement_imports')) {
            return;
        }

        Schema::create('treasury_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_account_id')->constrained('treasury_accounts')->restrictOnDelete();
            $table->string('file_name', 180);
            $table->string('format', 10)->comment('csv | txt | xlsx | ofx');
            $table->string('delimiter', 4)->nullable();
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_matched')->default(0);
            $table->unsignedInteger('rows_created')->default(0);
            $table->json('mapping')->nullable()->comment('Mapeo de columnas confirmado por el usuario');
            $table->json('summary')->nullable()->comment('Detalle de líneas emparejadas y nuevas');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['treasury_account_id', 'created_at'], 'treasury_statement_imports_account_index');
        });
    }

    /**
     * Permiso para importar extractos dentro de la conciliación.
     */
    private function seedPermission(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'treasury_conciliacion_importar',
            'guard_name' => 'web',
        ]);

        // Quien ya concilia puede importar; el administrador siempre.
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->where(function ($query) use ($permission) {
                $query->whereIn('name', self::ADMIN_ROLES)
                    ->orWhereHas('permissions', fn ($q) => $q->where('name', 'treasury_conciliacion'));
            })
            ->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        if (Schema::hasTable('modulos')) {
            $module = Modulo::firstOrCreate(
                ['identifier' => self::MODULE_IDENTIFIER],
                ['description' => self::MODULE_DESCRIPTION]
            );

            DB::table('model_has_permissions')->insertOrIgnore([
                'permission_id' => $permission->id,
                'model_type' => Modulo::class,
                'model_id' => $module->identifier,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
