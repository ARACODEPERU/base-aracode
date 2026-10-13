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
     * Permisos que declara el menu lateral del modulo Academico
     * (Modules/Academic/Resources/assets/js/Menu.js): el permiso de entrada de
     * cada grupo y el de cada item. Es el filtro que decide que ve cada rol en
     * el rail Academico y en sus submenus.
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
        // Grupo Capacitacion (capacitacionGroup) y menu clasico
        'aca_suscripciones',
        'aca_institucion_listado',
        'aca_docente_listado',
        'aca_estudiante_listado',
        'aca_cursos_listado',
        'aca_category_sector_type_modality',
        'aca_send_notifications',
        'aca_certificados_listado',

        // Grupo Aula y Seguimiento (aulaGroup)
        'aca_miscursos',
        'aca_cursos_revisar_examenes',
        'aca_asistencia_administrador',
        'aca_gestion_de_participaciones',
        'aca_gestion_de_calificaciones',

        // Grupo Colegio (colegioGroup): entrada del grupo y sus items
        'aca_school_listado',
        'aca_school_year_listado',
        'aca_school_tarifa_listado',
        'aca_school_estructura',
        'aca_school_area_listado',
        'aca_school_horario_listado',
        'aca_school_horario_docente',
        'aca_school_docente_listado',
        'aca_school_alumno_listado',
        'aca_school_matricula_listado',
        'aca_school_docente_notas',
        'aca_school_porteria_escaner',
        'aca_school_porteria_reporte',
        'aca_school_sms_configuracion',
        'aca_school_cobro_listado',

        // Tutoriales y reportes
        'aca_tutoriales_cortos',
        'aca_tutoriales_lista',
        'aca_tutoriales_videos',
        'aca_reportes',

        // Entrada del rail del modulo
        'aca_dashboard',
    ];

    /**
     * Crea (si faltan) los permisos del menu y los enlaza al modulo Academico
     * para que el editor de roles los agrupe en su carpeta.
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
