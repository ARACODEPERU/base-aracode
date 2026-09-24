<?php

namespace Tests\Unit\Database;

use Database\Seeders\AutomatizacionesRoleSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PermissionsReconcileSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\UserRole;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Database\Seeders\AcademicDatabaseSeeder;
use Modules\Bibliodata\Database\Seeders\BibliodataDatabaseSeeder;
use Modules\Blog\Database\Seeders\BlogDatabaseSeeder;
use Modules\CMS\Database\Seeders\CMSDatabaseSeeder;
use Modules\Churchcommunity\Database\Seeders\ChurchcommunityDatabaseSeeder;
use Modules\Commercial\Database\Seeders\CommercialDatabaseSeeder;
use Modules\CRM\Database\Seeders\CRMDatabaseSeeder;
use Modules\Dental\Database\Seeders\DentalDatabaseSeeder;
use Modules\Health\Database\Seeders\HealthDatabaseSeeder;
use Modules\Helpdesk\Database\Seeders\HelpdeskDatabaseSeeder;
use Modules\Integrationhub\Database\Seeders\IntegrationhubDatabaseSeeder;
use Modules\Onlineshop\Database\Seeders\OnlineshopDatabaseSeeder;
use Modules\Purchases\Database\Seeders\PurchasesDatabaseSeeder;
use Modules\Restaurant\Database\Seeders\RestaurantDatabaseSeeder;
use Modules\Sales\Database\Seeders\SalesDatabaseSeeder;
use Modules\Security\Database\Seeders\SecurityDatabaseSeeder;
use Modules\Socialevents\Database\Seeders\SocialeventsDatabaseSeeder;
use Modules\Treasury\Database\Seeders\TreasuryDatabaseSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Arranque desde cero: `php artisan db:seed` tiene que dejar el sistema usable.
 *
 * Qué se fija aquí:
 *   - DatabaseSeeder ejecuta los 18 seeders de módulos (antes no ejecutaba
 *     ninguno: la tabla `modulos` quedaba vacía y el menú no mostraba nada), en
 *     orden por identificador y con los roles base creados antes;
 *   - una instalación nueva queda con módulos, roles base y los permisos de cada
 *     módulo concedidos al rol admin, sin ningún permiso huérfano;
 *   - los permisos que solo creaban las migraciones quedan creados, enlazados a
 *     su módulo y concedidos;
 *   - sembrar dos veces no falla ni duplica filas.
 *
 * No usa RefreshDatabase: este suite no puede correr TODAS las migraciones (hay
 * migraciones con SQL exclusivo de MySQL, por ejemplo un CREATE EVENT
 * programado), así que arma el esquema mínimo que tocan los seeders, igual que
 * hacen las pruebas del módulo Security.
 */
class FreshInstallSeederTest extends TestCase
{
    /**
     * Seeders de módulos, en el orden por identificador de `modulos` (M001 → M023).
     *
     * @var array<int, class-string>
     */
    private const MODULE_SEEDERS = [
        PurchasesDatabaseSeeder::class,       // M001 Compras
        SalesDatabaseSeeder::class,           // M002 Ventas
        OnlineshopDatabaseSeeder::class,      // M004 Ventas en línea
        CMSDatabaseSeeder::class,             // M005 CMS
        BlogDatabaseSeeder::class,            // M006 Blog
        AcademicDatabaseSeeder::class,        // M007 Académico
        CRMDatabaseSeeder::class,             // M008 Clientes y comunicación
        HealthDatabaseSeeder::class,          // M009 Salud
        DentalDatabaseSeeder::class,          // M010 Odontología
        HelpdeskDatabaseSeeder::class,        // M011 Helpdesk
        RestaurantDatabaseSeeder::class,      // M012 Restaurante
        SocialeventsDatabaseSeeder::class,    // M013 Eventos sociales
        ChurchcommunityDatabaseSeeder::class, // M016 Comunidad de la iglesia
        BibliodataDatabaseSeeder::class,      // M017 Biblio Data
        SecurityDatabaseSeeder::class,        // M019 Configuración y seguridad
        IntegrationhubDatabaseSeeder::class,  // M020 Centro de integraciones
        CommercialDatabaseSeeder::class,      // M021 Comercial
        TreasuryDatabaseSeeder::class,        // M023 Tesorería
    ];

    /**
     * Identificadores de `modulos` que deja una instalación nueva.
     *
     * @var array<int, string>
     */
    private const MODULES = [
        'M001', 'M002', 'M003', 'M004', 'M005', 'M006', 'M007', 'M008', 'M009',
        'M010', 'M011', 'M012', 'M013', 'M015', 'M016', 'M017', 'M019', 'M020',
        'M021', 'M023',
    ];

    /**
     * Un permiso representativo de cada módulo: si falta, su módulo no se ve.
     *
     * @var array<int, string>
     */
    private const PERMISSION_PER_MODULE = [
        'dashboard',                // base
        'conf_dashboard',           // M019 Security
        'purc_dashboard',           // M001 Purchases
        'sale_dashboard',           // M002 Sales
        'invo_dashboard',           // M003 Billing
        'acco_dashboard',           // M015 Cuentas por cobrar
        'onli_dashboard',           // M004 Onlineshop
        'cms_dashboard',            // M005 CMS
        'blog_dashboard',           // M006 Blog
        'aca_dashboard',            // M007 Academic
        'crm_dashboard',            // M008 CRM
        'heal_dashboard',           // M009 Health
        'dental_dashboard',         // M010 Dental
        'help_dashboard',           // M011 Helpdesk
        'res_dashboard',            // M012 Restaurant
        'even_dashboard',           // M013 Socialevents
        'cigle_dashboard',          // M016 Churchcommunity
        'bib_dashboard',            // M017 Bibliodata
        'integrationhub_dashboard', // M020 Integrationhub
        'comm_dashboard',           // M021 Commercial
        'treasury_dashboard',       // M023 Treasury
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildSeederSchema();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * El defecto que motivó este archivo: DatabaseSeeder no llamaba a ningún
     * seeder de módulos, así que una base nueva quedaba sin `modulos`.
     */
    public function test_database_seeder_ejecuta_los_seeders_de_modulos_en_orden(): void
    {
        $spy = $this->spyDatabaseSeeder();
        $spy->run();

        $order = $spy->seeders;

        foreach (self::MODULE_SEEDERS as $seeder) {
            $this->assertContains($seeder, $order, "DatabaseSeeder no ejecuta {$seeder}");
        }

        // El orden entre módulos es el de `modulos` (M001 → M023), no el alfabético.
        $moduleOrder = array_values(array_intersect($order, self::MODULE_SEEDERS));
        $this->assertSame(self::MODULE_SEEDERS, $moduleOrder, 'Los seeders de módulos deben correr en orden por identificador.');

        $lastModuleSeeder = self::MODULE_SEEDERS[count(self::MODULE_SEEDERS) - 1];

        $rolesIndex = array_search(RolesSeeder::class, $order, true);
        $userIndex = array_search(UserRole::class, $order, true);
        $firstModuleIndex = array_search(self::MODULE_SEEDERS[0], $order, true);
        $lastModuleIndex = array_search($lastModuleSeeder, $order, true);
        $reconcileIndex = array_search(PermissionsReconcileSeeder::class, $order, true);

        $this->assertLessThan($firstModuleIndex, $rolesIndex, 'RolesSeeder debe correr antes de los módulos (varios usan Role::find(1)).');
        $this->assertLessThan($firstModuleIndex, $userIndex, 'UserRole debe correr antes de los módulos.');
        $this->assertGreaterThan($rolesIndex, $userIndex, 'UserRole necesita el rol admin ya creado.');
        $this->assertGreaterThan($lastModuleIndex, $reconcileIndex, 'La reconciliación de permisos va después de todos los módulos.');
        $this->assertSame(
            array_key_last($order),
            array_search(AutomatizacionesRoleSeeder::class, $order, true),
            'AutomatizacionesRoleSeeder copia los permisos de Administrador: debe ir al final.'
        );
    }

    public function test_una_instalacion_nueva_deja_modulos_roles_y_permisos_asignados(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(
            self::MODULES,
            DB::table('modulos')->orderBy('identifier')->pluck('identifier')->all(),
            'La tabla `modulos` no quedó completa.'
        );

        foreach (RolesSeeder::ROLES as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName, 'guard_name' => 'web']);
        }

        $admin = Role::findByName('admin');

        foreach (self::PERMISSION_PER_MODULE as $permission) {
            $this->assertTrue(
                $admin->hasPermissionTo($permission),
                "El rol admin no tiene el permiso {$permission}: ese módulo no se le muestra."
            );
        }

        $this->assertSame(
            0,
            $this->permissionsWithoutAnyRole(),
            'Quedaron permisos sin ningún rol: revisa que los seeders de módulos los concedan.'
        );

        $this->assertSame(
            0,
            $this->permissionsWithoutModule(),
            'Quedaron permisos sin módulo: el editor de roles los mostraría en "Otros permisos".'
        );
    }

    /**
     * Los permisos que solo crean las migraciones (que corren antes que los
     * seeders, cuando ni los roles ni `modulos` existen todavía).
     */
    public function test_los_permisos_que_solo_creaban_migraciones_quedan_asignados(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (PermissionsReconcileSeeder::ORPHANS as $orphan) {
            $this->assertDatabaseHas('permissions', ['name' => $orphan['name'], 'guard_name' => 'web']);

            // Enlazado a su módulo para que el editor de roles lo agrupe.
            $this->assertSame(
                1,
                DB::table('model_has_permissions')
                    ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
                    ->where('permissions.name', $orphan['name'])
                    ->where('model_has_permissions.model_type', \App\Models\Modulo::class)
                    ->where('model_has_permissions.model_id', $orphan['module'])
                    ->count(),
                "El permiso {$orphan['name']} no quedó enlazado al módulo {$orphan['module']}."
            );

            foreach ($orphan['roles'] as $roleName) {
                $this->assertTrue(
                    Role::findByName($roleName)->hasPermissionTo($orphan['name']),
                    "El rol {$roleName} no tiene {$orphan['name']}."
                );
            }
        }
    }

    public function test_sembrar_dos_veces_no_duplica_ni_falla(): void
    {
        $this->seed(DatabaseSeeder::class);
        $first = $this->rowCounts();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($first, $this->rowCounts(), 'Reejecutar db:seed duplicó filas.');
    }

    /**
     * Cuenta de filas de las tablas que llenan los seeders.
     *
     * @return array<string, int>
     */
    private function rowCounts(): array
    {
        $counts = [];

        foreach ([
            'modulos', 'roles', 'permissions', 'model_has_permissions',
            'role_has_permissions', 'users', 'companies', 'local_sales',
            'series', 'billeteras_digitales', 'parameters',
        ] as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }

    /**
     * Permisos que no están enlazados a ningún módulo.
     */
    private function permissionsWithoutModule(): int
    {
        return DB::table('permissions')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('model_has_permissions')
                    ->whereColumn('model_has_permissions.permission_id', 'permissions.id')
                    ->where('model_has_permissions.model_type', \App\Models\Modulo::class);
            })
            ->count();
    }

    /**
     * Permisos que no tiene ningún rol.
     */
    private function permissionsWithoutAnyRole(): int
    {
        return DB::table('permissions')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('role_has_permissions')
                    ->whereColumn('role_has_permissions.permission_id', 'permissions.id');
            })
            ->count();
    }

    /**
     * DatabaseSeeder con `call()` interceptado: registra qué seeders se ejecutan
     * y en qué orden, sin tocar la base de datos.
     */
    private function spyDatabaseSeeder(): DatabaseSeeder
    {
        return new class extends DatabaseSeeder
        {
            /**
             * @var array<int, string>
             */
            public array $seeders = [];

            /**
             * @param  mixed  $class
             * @param  bool  $silent
             * @param  array<int, mixed>  $parameters
             * @return $this
             */
            public function call($class, $silent = false, array $parameters = [])
            {
                foreach ((array) $class as $seeder) {
                    $this->seeders[] = is_string($seeder) ? $seeder : get_class($seeder);
                }

                return $this;
            }
        };
    }

    /**
     * Esquema mínimo que escriben los seeders (spatie, módulos y catálogos base).
     */
    private function buildSeederSchema(): void
    {
        $tables = config('permission.table_names');

        Schema::create($tables['permissions'], function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tables['roles'], function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        // model_id es texto: la aplicación guarda identificadores de módulo ('M002').
        Schema::create($tables['model_has_permissions'], function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->string('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create($tables['model_has_roles'], function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->string('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create($tables['role_has_permissions'], function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('modulos', function (Blueprint $table) {
            $table->string('identifier', 4)->primary();
            $table->string('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('ruc')->nullable();
            $table->string('name')->nullable();
            $table->string('business_name')->nullable();
            $table->string('tradename')->nullable();
            $table->string('fiscal_address')->nullable();
            $table->string('phone')->nullable();
            $table->string('representative')->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_document')->nullable();
            $table->text('key_sunat')->nullable();
            $table->string('user_sunat')->nullable();
            $table->text('certificate_sunat')->nullable();
            $table->string('mode')->nullable();
            $table->string('ubigeo')->nullable();
            $table->string('logo_negative')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('isotipo')->nullable();
            $table->string('isotipo_negative')->nullable();
            $table->string('isotipo_dark')->nullable();
            $table->timestamps();
        });

        Schema::create('local_sales', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('ubigeo')->nullable();
            $table->string('sunat_code')->nullable();
            $table->timestamps();
        });

        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_type_id')->nullable();
            $table->string('description')->nullable();
            $table->integer('number')->default(1);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('local_id')->nullable();
            $table->timestamps();
        });

        Schema::create('billeteras_digitales', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('short_name');
            $table->string('full_name')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('parameter_code')->unique();
            $table->string('description')->nullable();
            $table->string('control_type', 3)->nullable();
            $table->text('json_query_data')->nullable();
            $table->text('value_default')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('local_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->boolean('status')->nullable()->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('cigle_member_types', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('short')->nullable();
            $table->timestamps();
        });
    }
}
