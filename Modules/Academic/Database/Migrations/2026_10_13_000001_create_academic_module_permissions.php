<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /** Identificador del modulo Academico en la tabla `modulos`. */
    private const MODULE_IDENTIFIER = 'M007';

    /**
     * Permisos que declara el codigo del modulo Academico (seeder del modulo,
     * migraciones anteriores, rutas, controladores y menu), incluidos los que
     * todavia no tenian registro en la tabla `permissions`.
     *
     * Esta migracion solo se asegura de que existan: no concede nada a ningun
     * rol, ni siquiera a admin, para que cada equipo habilite los que necesite
     * desde Roles y Permisos.
     *
     * Es idempotente: usa firstOrCreate, asi que volver a ejecutarla no duplica
     * permisos ni enlaces.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        // General / dashboard
        'aca_dashboard',
        'aca_alumno_examenes',
        'aca_miscursos',
        'aca_category_sector_type_modality',
        'aca_send_notifications',
        'aca_testimonios',

        // Suscripciones
        'aca_suscripciones',
        'aca_suscripciones_nuevo',
        'aca_suscripciones_editar',
        'aca_suscripciones_eliminar',
        'aca_suscripcion_estudiante_editar',

        // Instituciones
        'aca_institucion_listado',
        'aca_institucion_nuevo',
        'aca_institucion_editar',
        'aca_institucion_eliminar',

        // Docentes
        'aca_docente_listado',
        'aca_docente_nuevo',
        'aca_docente_editar',
        'aca_docente_eliminar',

        // Estudiantes
        'aca_estudiante_listado',
        'aca_estudiante_nuevo',
        'aca_estudiante_editar',
        'aca_estudiante_eliminar',
        'aca_estudiante_importar_excel',
        'aca_estudiante_exportar_excel',
        'aca_estudiante_matricular',
        'aca_estudiante_certificados_crear',
        'aca_estudiante_cobrar',
        'aca_estudiante_listar_comprobantes',
        'aca_estudiante_listar_cuotas_espaciales',
        'aca_estudiante_enviar_correo_acceso',

        // Cursos y examenes
        'aca_cursos_listado',
        'aca_cursos_nuevo',
        'aca_cursos_editar',
        'aca_cursos_eliminar',
        'aca_cursos_listado_estudiantes',
        'aca_cursos_modulos',
        'aca_cursos_modulos_examen',
        'aca_cursos_examen_configuracion',
        'aca_cursos_examen_ver',
        'aca_cursos_examen_resolver',
        'aca_cursos_examen_eliminar',
        'aca_cursos_examen_final_crear',
        'aca_cursos_examen_final_editar',
        'aca_cursos_revisar_examenes',

        // Certificados
        'aca_certificados_listado',
        'aca_certificados_nuevo',
        'aca_certificados_editar',
        'aca_certificados_eliminar',

        // Tutoriales
        'aca_tutoriales_cortos',
        'aca_tutoriales_lista',
        'aca_tutoriales_lista_nuevo',
        'aca_tutoriales_lista_editar',
        'aca_tutoriales_lista_eliminar',
        'aca_tutoriales_lista_agregar_video',
        'aca_tutoriales_videos',
        'aca_tutoriales_videos_nuevo',
        'aca_tutoriales_videos_editar',
        'aca_tutoriales_videos_eliminar',

        // Reportes
        'aca_reportes',
        'aca_reportes_estado_susc_estudiantes',

        // Asistencia
        'aca_asistencia_administrador',
        'aca_asistencia_crear_link',

        // Calificaciones y participaciones
        'aca_gestion_de_calificaciones',
        'aca_gestion_de_participaciones',

        // Colegio (mantenedores, alumnos, matriculas, tarifas, horarios)
        'aca_school_listado',
        'aca_school_nuevo',
        'aca_school_editar',
        'aca_school_eliminar',
        'aca_school_estructura',
        'aca_school_year_listado',
        'aca_school_year_nuevo',
        'aca_school_year_editar',
        'aca_school_area_listado',
        'aca_school_area_nuevo',
        'aca_school_area_editar',
        'aca_school_area_eliminar',
        'aca_school_alumno_listado',
        'aca_school_alumno_nuevo',
        'aca_school_alumno_editar',
        'aca_school_alumno_eliminar',
        'aca_school_alumno_apoderados',
        'aca_school_matricula_listado',
        'aca_school_matricula_nueva',
        'aca_school_matricula_editar',
        'aca_school_docente_listado',
        'aca_school_docente_nuevo',
        'aca_school_docente_editar',
        'aca_school_docente_eliminar',
        'aca_school_docente_notas',
        'aca_school_tarifa_listado',
        'aca_school_tarifa_editar',
        'aca_school_tarifa_eliminar',
        'aca_school_cobro_listado',
        'aca_school_cobro_registrar',
        'aca_school_horario_listado',
        'aca_school_horario_editar',
        'aca_school_horario_docente',
        'aca_school_porteria_escaner',
        'aca_school_porteria_reporte',

        // Avisos por SMS del colegio y canal SMSGate
        'aca_school_sms_configuracion',
        'aca_smsgate_configuracion',
        'aca_smsgate_guia',
    ];

    /**
     * Crea (si faltan) todos los permisos del modulo y los enlaza al modulo
     * Academico para que el editor de roles los agrupe en su carpeta.
     */
    public function up(): void
    {
        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $this->linkToModule($permission);
        }

        // Los permisos se cachean en spatie; sin esto los nuevos no se ven.
        Artisan::call('permission:cache-reset');
    }

    /**
     * Deshace la migracion borrando los permisos de la lista que todavia no se
     * usan (sin roles ni usuarios asignados). Los que ya se hayan habilitado
     * desde Roles y Permisos se respetan para no romper accesos.
     */
    public function down(): void
    {
        $ids = Permission::whereIn('name', self::PERMISSIONS)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $enUso = DB::table('role_has_permissions')
            ->whereIn('permission_id', $ids)
            ->pluck('permission_id')
            ->merge(
                DB::table('model_has_permissions')
                    ->whereIn('permission_id', $ids)
                    ->where('model_type', '!=', Modulo::class)
                    ->pluck('permission_id')
            )
            ->unique();

        // Las filas de role_has_permissions y model_has_permissions caen por
        // cascade al borrar el permiso.
        $borrables = $ids->diff($enUso)->values()->all();

        if ($borrables !== []) {
            Permission::whereIn('id', $borrables)->delete();
        }

        Artisan::call('permission:cache-reset');
    }

    /**
     * Enlaza el permiso al modulo Academico (M007). Es solo la relacion que usa
     * la pantalla Modulos y el agrupado del editor de roles: no concede el
     * permiso a ningun rol.
     */
    private function linkToModule(Permission $permission): void
    {
        $moduleExists = DB::table('modulos')
            ->where('identifier', self::MODULE_IDENTIFIER)
            ->exists();

        if (! $moduleExists) {
            return;
        }

        $alreadyLinked = DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->where('model_type', Modulo::class)
            ->where('model_id', self::MODULE_IDENTIFIER)
            ->exists();

        if ($alreadyLinked) {
            return;
        }

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permission->id,
            'model_type' => Modulo::class,
            'model_id' => self::MODULE_IDENTIFIER,
        ]);
    }
};
