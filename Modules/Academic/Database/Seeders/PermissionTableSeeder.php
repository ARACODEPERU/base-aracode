<?php

namespace Modules\Academic\Database\Seeders;

use App\Models\Modulo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::firstOrCreate(['name' => 'admin']);

        $modulo = Modulo::firstOrCreate(
                        ['identifier' => 'M007'],
                        ['description' => 'Académico']
                    );

        $permissions = [];

        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_dashboard']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_suscripciones']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_suscripciones_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_suscripciones_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_suscripciones_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_institucion_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_institucion_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_institucion_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_institucion_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_docente_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_docente_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_docente_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_docente_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_estudiante_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_estudiante_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_estudiante_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_estudiante_importar_excel']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_estudiante_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_estudiante_certificados_crear']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_estudiante_matricular']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_listado_estudiantes']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_modulos']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_modulos_examen']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_examen_configuracion']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_examen_ver']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_examen_resolver']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_miscursos']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_revisar_examenes']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_estudiante_listar_comprobantes']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_estudiante_cobrar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_certificados_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_certificados_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_certificados_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_certificados_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_tutoriales_cortos']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_tutoriales_lista']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_tutoriales_lista_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_tutoriales_lista_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_tutoriales_lista_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_tutoriales_videos']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_tutoriales_videos_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_tutoriales_videos_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_tutoriales_videos_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_tutoriales_lista_agregar_video']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_estudiante_exportar_excel']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_reportes']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_reportes_estado_susc_estudiantes']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_suscripcion_estudiante_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_estudiante_listar_cuotas_espaciales']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_alumno_examenes']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_asistencia_administrador']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_asistencia_crear_link']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_gestion_de_calificaciones']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_gestion_de_participaciones']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_examen_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_examen_final_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_cursos_examen_final_crear']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_category_sector_type_modality']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_send_notifications']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_smsgate_configuracion']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_smsgate_guia']));

        /*
         * Permisos del modulo escolar (colegios): mantenedor de colegios,
         * anios escolares, estructura nivel/grado/seccion, alumnos escolares
         * y matriculas. firstOrCreate = idempotente.
         */
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_year_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_year_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_year_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_estructura']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_alumno_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_alumno_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_alumno_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_alumno_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_alumno_apoderados']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_matricula_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_matricula_nueva']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_matricula_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_docente_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_docente_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_docente_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_docente_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_tarifa_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_tarifa_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_tarifa_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_cobro_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_cobro_registrar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_docente_notas']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_area_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_area_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_area_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'aca_school_area_eliminar']));

        foreach ($permissions as $permission) {

            $role->givePermissionTo($permission->name);

            $exists = DB::table('model_has_permissions')
                ->where('permission_id', $permission->id)
                ->where('model_type', Modulo::class)
                ->where('model_id', $modulo->identifier)
                ->exists();

            if (!$exists) {
                DB::table('model_has_permissions')->insert([
                    'permission_id' => $permission->id,
                    'model_type' => Modulo::class,
                    'model_id' => $modulo->identifier,
                ]);
            }
        }

        $alumno = Role::firstOrCreate(['name' => 'Alumno']);
        $alumno->givePermissionTo('aca_dashboard');
        $alumno->givePermissionTo('aca_miscursos');

        $docente = Role::firstOrCreate(['name' => 'Docente']);
        $docente->givePermissionTo('aca_dashboard');
        $docente->givePermissionTo('aca_cursos_listado');
        // Registro de notas del colegio (solo sus secciones asignadas).
        $docente->givePermissionTo('aca_school_docente_notas');

        // El canal SMSGate lo configuran y usan admin y Administrador; la guia
        // paso a paso queda solo para admin (aca_smsgate_guia ya se concedio
        // arriba, dentro del bloque de $permissions).
        $administrador = Role::firstOrCreate(['name' => 'Administrador']);
        $administrador->givePermissionTo('aca_smsgate_configuracion');
    }
}
