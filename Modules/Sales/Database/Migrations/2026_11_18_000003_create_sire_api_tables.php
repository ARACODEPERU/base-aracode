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
 * Fase A del SIRE por API: credenciales cifradas de la empresa, modo de trabajo
 * (TXT/ZIP manual o API automática), envíos con su ticket y bitácora de cada
 * llamada a SUNAT.
 *
 * Aditiva e idempotente: producción solo ejecuta `php artisan migrate`.
 */
return new class extends Migration
{
    private const MODULE_IDENTIFIER = 'M003';

    private const PERMISSION = 'sire_api';

    /**
     * @var array<int, string>
     */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    public function up(): void
    {
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (! Schema::hasColumn('companies', 'sire_mode')) {
                    $table->string('sire_mode', 10)->default('txt');
                }

                // Los cuatro valores se guardan cifrados con el cast
                // `encrypted` del modelo Company, por eso son texto largo.
                if (! Schema::hasColumn('companies', 'sire_client_id')) {
                    $table->text('sire_client_id')->nullable();
                }

                if (! Schema::hasColumn('companies', 'sire_client_secret')) {
                    $table->text('sire_client_secret')->nullable();
                }

                if (! Schema::hasColumn('companies', 'sire_sol_user')) {
                    $table->text('sire_sol_user')->nullable();
                }

                if (! Schema::hasColumn('companies', 'sire_sol_key')) {
                    $table->text('sire_sol_key')->nullable();
                }
            });
        }

        if (! Schema::hasTable('sire_submissions')) {
            Schema::create('sire_submissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('tipo', 10); // ventas | compras
                $table->string('period', 6); // AAAAMM
                $table->string('action', 20)->default('exportar');
                $table->string('ticket', 80)->nullable();
                $table->string('status', 20)->default('solicitado');
                $table->string('file_name')->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_hash', 64)->nullable();
                $table->unsignedInteger('lines')->default(0);
                $table->unsignedInteger('attempts')->default(0);
                $table->json('response')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sire_submission_logs')) {
            Schema::create('sire_submission_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sire_submission_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('action', 30);
                $table->string('method', 10)->nullable();
                $table->string('endpoint', 500)->nullable();
                $table->unsignedSmallInteger('http_status')->nullable();
                $table->boolean('success')->default(false);
                $table->text('message')->nullable();
                $table->unsignedInteger('duration_ms')->default(0);
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => self::PERMISSION,
            'guard_name' => 'web',
        ]);

        $roles = Role::query()
            ->whereIn('name', self::ADMIN_ROLES)
            ->where('guard_name', 'web')
            ->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        if (Schema::hasTable('modulos')) {
            $modulo = Modulo::query()->where('identifier', self::MODULE_IDENTIFIER)->first();

            if ($modulo) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $permission->id,
                    'model_type' => Modulo::class,
                    'model_id' => $modulo->identifier,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Aditiva a propósito: las columnas y tablas conservan el historial de
        // envíos y las credenciales; quitarlas dejaría sin bitácora al SIRE.
    }
};
