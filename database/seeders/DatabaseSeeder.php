<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
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

class DatabaseSeeder extends Seeder
{
    /**
     * Siembra una instalación completa, de cero.
     *
     * El ORDEN importa y no es cosmético:
     *
     * 1. Catálogos base (empresa, local, serie): varias tablas apuntan a
     *    company_id = 1 y local_id = 1.
     * 2. RolesSeeder: crea 'admin' PRIMERO para que conserve el id 1, porque la
     *    mayoría de los seeders de módulos resuelven el rol administrador con
     *    Role::find(1). Crea también los roles operativos ('Ventas', 'ventas',
     *    'docente', 'Alumno', ...) antes de que ningún módulo conceda permisos.
     * 3. UserRole: usuario administrador y sus permisos base.
     * 4. Seeders de módulos en orden por identificador de `modulos` (M001 → M023):
     *    cada uno crea su fila en `modulos`, sus permisos y los enlaza. Sin este
     *    bloque `modulos` queda vacía y el menú lateral no muestra nada.
     * 5. PermissionsReconcileSeeder: repara lo que las migraciones de módulos
     *    dejaron sin dueño (corren antes de que existan roles y módulos).
     * 6. AutomatizacionesRoleSeeder al final: copia los permisos de
     *    'Administrador', así que necesita todo lo anterior ya sembrado.
     *
     * Todos los seeders de este flujo son idempotentes: `db:seed` se puede
     * reejecutar sobre una base ya instalada sin duplicar filas ni fallar.
     */
    public function run(): void
    {
        // 1) Catálogos base.
        $this->call([
            CompanySeeder::class,
            EstablishmentSeeder::class,
            SeriesSeeder::class,
        ]);

        // Parámetros y catálogos de configuración.
        $this->call([
            ParameterOpenAiSeeder::class,
            BilleterasDigitalesSeeder::class,
        ]);

        // 2) Roles base (admin primero) y 3) usuario administrador.
        $this->call([
            RolesSeeder::class,
            UserRole::class,
        ]);

        // 4) Módulos, en orden por identificador de `modulos` (M001 → M023).
        $this->call([
            PurchasesDatabaseSeeder::class,      // M001 Compras
            SalesDatabaseSeeder::class,          // M002 Ventas (M003 FE, M015 CxC)
            OnlineshopDatabaseSeeder::class,     // M004 Ventas en línea
            CMSDatabaseSeeder::class,            // M005 CMS
            BlogDatabaseSeeder::class,           // M006 Blog
            AcademicDatabaseSeeder::class,       // M007 Académico
            CRMDatabaseSeeder::class,            // M008 Clientes y comunicación
            HealthDatabaseSeeder::class,         // M009 Salud
            DentalDatabaseSeeder::class,         // M010 Odontología
            HelpdeskDatabaseSeeder::class,       // M011 Helpdesk
            RestaurantDatabaseSeeder::class,     // M012 Restaurante
            SocialeventsDatabaseSeeder::class,   // M013 Eventos sociales
            ChurchcommunityDatabaseSeeder::class, // M016 Comunidad de la iglesia
            BibliodataDatabaseSeeder::class,     // M017 Biblio Data
            SecurityDatabaseSeeder::class,       // M019 Configuración y seguridad
            IntegrationhubDatabaseSeeder::class, // M020 Centro de integraciones
            CommercialDatabaseSeeder::class,     // M021 Comercial
            TreasuryDatabaseSeeder::class,       // M023 Tesorería
        ]);

        // 5) Permisos que las migraciones dejaron sin rol y sin módulo.
        $this->call(PermissionsReconcileSeeder::class);

        // 6) Último: depende de los permisos ya concedidos a 'Administrador'.
        $this->call(AutomatizacionesRoleSeeder::class);
    }
}
