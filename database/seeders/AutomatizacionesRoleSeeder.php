<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AutomatizacionesRoleSeeder extends Seeder
{
    /**
     * Permisos que el rol "Automatizaciones" NO debe tener:
     * la creación/anulación de comprobantes de pago, la gestión de resúmenes
     * y la creación/eliminación de personas (usuarios, estudiantes, docentes) y matrículas.
     */
    protected array $excludedPermissions = [
        // Comprobantes de pago: creación y anulación
        'invo_documento',                   // FE: acceso a la pantalla "Crear Documento"
        'invo_documento_nuevo',             // FE: crear boleta/factura electrónica
        'invo_documento_anular',            // FE: anular documento electrónico
        'invo_comunicacion_baja',           // FE: comunicación de baja (anular facturas vía SUNAT)
        'invo_resumenes_lista',             // FE: crear/consultar resumen diario de boletas
        'sale_documento_fisico_nuevo',      // Ventas: registrar boleta/factura física (de otras plataformas)
        'sale_documento_fisico_eliminar',   // Ventas: anular boleta/factura física

        // Matrículas de alumnos
        'aca_estudiante_matricular',
        'aca_estudiantes_matricular',
        'aca_estudiante_matriculas_crear',
        'Aca_student_matricular',

        // Registrar/eliminar estudiantes y docentes
        'aca_estudiante_nuevo',
        'aca_estudiante_importar_excel',
        'aca_estudiante_eliminar',
        'aca_docente_nuevo',
        'aca_docente_eliminar',

        // Gestión de usuarios del sistema
        'usuarios',
        'usuarios_nuevo',
        'usuarios_editar',
        'usuarios_eliminar',
        'usuarios_ver',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::query()
            ->where('name', 'Administrador')
            ->where('guard_name', 'web')
            ->first();

        if (! $adminRole) {
            throw new \RuntimeException(
                'No se encontró el rol "Administrador". Asegúrate de que exista antes de ejecutar este seeder.'
            );
        }

        $role = Role::firstOrCreate(
            ['name' => 'Automatizaciones', 'guard_name' => 'web']
        );

        $permissions = $adminRole->permissions()
            ->whereNotIn('name', $this->excludedPermissions)
            ->pluck('name')
            ->toArray();

        $role->syncPermissions($permissions);

        $this->command?->info(
            "Rol 'Automatizaciones' creado/actualizado con " . count($permissions)
            . ' permisos (igual que Administrador, sin crear/anular comprobantes, sin resumen de boletas, sin matricular/registrar/eliminar personas).'
        );
    }
}
