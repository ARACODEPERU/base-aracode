<?php

use App\Models\Modulo;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Puesta en marcha de Tesorería.
 *
 * - Tabla de configuración del módulo: activarlo o desactivarlo y fijar la
 *   fecha desde la que cuenta. Lo anterior a esa fecha no se toma en cuenta
 *   para saldos, proyecciones ni reportes.
 * - Permiso de la pantalla de configuración (crítica y auditada).
 * - Aracodito activo por defecto para el rol de administración.
 *
 * Es idempotente: se puede correr sobre una base que ya tenga los cambios.
 */
return new class extends Migration
{
    private const MODULE_IDENTIFIER = 'M023';

    /** @var array<int, string> */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    public function up(): void
    {
        $this->createSettingsTable();
        $this->seedInitialSettings();
        $this->seedPermission();
        $this->enableAracoditoForAdmins();
    }

    public function down(): void
    {
        // Migración aditiva: no se elimina la configuración ni los permisos.
    }

    private function createSettingsTable(): void
    {
        if (Schema::hasTable('treasury_settings')) {
            return;
        }

        Schema::create('treasury_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true)
                ->comment('Tesorería activa para toda la empresa');
            $table->date('starts_on')->nullable()
                ->comment('Fecha desde la que Tesorería cuenta; lo anterior se ignora');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Arranca activa y contando desde el día de la instalación: los
     * movimientos anteriores no entran en saldos ni proyecciones.
     */
    private function seedInitialSettings(): void
    {
        if (! Schema::hasTable('treasury_settings')) {
            return;
        }

        if (DB::table('treasury_settings')->exists()) {
            return;
        }

        DB::table('treasury_settings')->insert([
            'enabled' => true,
            'starts_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPermission(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('modulos')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modulo = Modulo::firstOrCreate(
            ['identifier' => self::MODULE_IDENTIFIER],
            ['description' => 'Tesorería']
        );

        $permission = Permission::firstOrCreate([
            'name' => 'treasury_configuracion',
            'guard_name' => 'web',
        ]);

        Role::query()
            ->whereIn('name', self::ADMIN_ROLES)
            ->where('guard_name', 'web')
            ->get()
            ->each(function (Role $role) use ($permission) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            });

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permission->id,
            'model_type' => Modulo::class,
            'model_id' => $modulo->identifier,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * El administrador trabaja con Aracodito desde el primer día.
     */
    private function enableAracoditoForAdmins(): void
    {
        if (! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'aracodito_enabled')
            || ! Schema::hasTable('roles')
            || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        $adminIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('roles.name', self::ADMIN_ROLES)
            ->where('model_has_roles.model_type', User::class)
            ->pluck('model_has_roles.model_id')
            ->unique()
            ->all();

        if ($adminIds === []) {
            return;
        }

        DB::table('users')
            ->whereIn('id', $adminIds)
            ->update(['aracodito_enabled' => true, 'updated_at' => now()]);
    }
};
