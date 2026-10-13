# Permisos del módulo Académico declarados en el código

Informe generado el 13 de octubre de 2026 recorriendo el código de `Modules/Academic` (seeders,
migraciones, rutas, controladores y JS del menú) y comparándolo con la tabla `permissions` de la
base de datos. Incluye los permisos existan o no en la base de datos.

Fuentes revisadas:

- `Modules/Academic/Database/Seeders/PermissionTableSeeder.php`: lista principal de permisos y su
  asignación a los roles admin, Alumno, Docente, Administrador y Portero.
- `Modules/Academic/Database/Migrations/*`: permisos creados por migración.
- `Modules/Academic/Routes/web.php` y `api.php`: middleware `permission:` y `can:`.
- `Modules/Academic/Http/Controllers/**`: `hasPermissionTo`, `->can()` y `Gate::`.
- `Modules/Academic/Resources/assets/js/Menu.js` y vistas `.vue`: `hasPermission(...)` y listas.

## Resumen

| Métrica | Total |
| --- | --- |
| Permisos definidos por el código del módulo (seeder + migraciones) | 103 |
| Permisos `aca_*` que existen hoy en la tabla `permissions` | 52 |
| Definidos en el código y **todavía sin registro** en la base de datos | 52 |
| Usados en el código pero que el módulo no define | 0 |
| En la base de datos pero no definidos en el módulo | 1 |

## Permisos definidos en el código del módulo

| Permiso | Área | Definido en | Usado en | En la BD |
| --- | --- | --- | --- | --- |
| `aca_alumno_examenes` | General | seeder | rutas | sí |
| `aca_category_sector_type_modality` | General | migración, seeder | JS (menú), controlador, rutas | sí |
| `aca_dashboard` | General | seeder | JS (menú), rutas, seeder (asigna) | sí |
| `aca_miscursos` | General | seeder | JS (menú), rutas, seeder (asigna) | sí |
| `aca_send_notifications` | General | migración, seeder | JS (menú), controlador, rutas | sí |
| `aca_testimonios` | General | migración | controlador, rutas | sí |
| `aca_suscripcion_estudiante_editar` | Suscripciones | seeder | rutas, vistas (v-can) | **no** |
| `aca_suscripciones` | Suscripciones | seeder | JS (menú), rutas | sí |
| `aca_suscripciones_editar` | Suscripciones | seeder | rutas | sí |
| `aca_suscripciones_eliminar` | Suscripciones | seeder | rutas | sí |
| `aca_suscripciones_nuevo` | Suscripciones | seeder | rutas | sí |
| `aca_institucion_editar` | Instituciones | seeder | rutas | sí |
| `aca_institucion_eliminar` | Instituciones | seeder | rutas | sí |
| `aca_institucion_listado` | Instituciones | seeder | JS (menú), rutas | sí |
| `aca_institucion_nuevo` | Instituciones | seeder | rutas | sí |
| `aca_docente_editar` | Docentes | seeder | rutas | sí |
| `aca_docente_eliminar` | Docentes | seeder | rutas | sí |
| `aca_docente_listado` | Docentes | seeder | JS (menú), rutas | sí |
| `aca_docente_nuevo` | Docentes | seeder | rutas | sí |
| `aca_estudiante_certificados_crear` | Estudiantes | seeder | rutas, vistas (v-can) | sí |
| `aca_estudiante_cobrar` | Estudiantes | seeder | rutas, vistas (v-can) | sí |
| `aca_estudiante_editar` | Estudiantes | seeder | rutas, vistas (v-can) | sí |
| `aca_estudiante_eliminar` | Estudiantes | seeder | rutas, vistas (v-can) | sí |
| `aca_estudiante_enviar_correo_acceso` | Estudiantes | migración | controlador, rutas, vistas (v-can) | sí |
| `aca_estudiante_exportar_excel` | Estudiantes | seeder | rutas, vistas (v-can) | sí |
| `aca_estudiante_importar_excel` | Estudiantes | seeder | rutas, vistas (v-can) | **no** |
| `aca_estudiante_listado` | Estudiantes | seeder | JS (menú), rutas, vistas (v-can) | sí |
| `aca_estudiante_listar_comprobantes` | Estudiantes | seeder | rutas, vistas (v-can) | **no** |
| `aca_estudiante_listar_cuotas_espaciales` | Estudiantes | seeder | rutas, vistas (v-can) | **no** |
| `aca_estudiante_matricular` | Estudiantes | seeder | rutas, vistas (v-can) | **no** |
| `aca_estudiante_nuevo` | Estudiantes | seeder | rutas | sí |
| `aca_cursos_editar` | Cursos y exámenes | seeder | rutas | sí |
| `aca_cursos_eliminar` | Cursos y exámenes | seeder | rutas | sí |
| `aca_cursos_examen_configuracion` | Cursos y exámenes | seeder | rutas, vistas (v-can) | sí |
| `aca_cursos_examen_eliminar` | Cursos y exámenes | seeder | rutas | **no** |
| `aca_cursos_examen_final_crear` | Cursos y exámenes | seeder | vistas (v-can) | **no** |
| `aca_cursos_examen_final_editar` | Cursos y exámenes | seeder | vistas (v-can) | **no** |
| `aca_cursos_examen_resolver` | Cursos y exámenes | seeder | rutas | sí |
| `aca_cursos_examen_ver` | Cursos y exámenes | seeder | rutas | sí |
| `aca_cursos_listado` | Cursos y exámenes | seeder | JS (menú), rutas, seeder (asigna) | sí |
| `aca_cursos_listado_estudiantes` | Cursos y exámenes | seeder | JS (menú), rutas, vistas (v-can) | **no** |
| `aca_cursos_modulos` | Cursos y exámenes | seeder | — | sí |
| `aca_cursos_modulos_examen` | Cursos y exámenes | seeder | rutas | sí |
| `aca_cursos_nuevo` | Cursos y exámenes | seeder | rutas | sí |
| `aca_cursos_revisar_examenes` | Cursos y exámenes | seeder | JS (menú), rutas | **no** |
| `aca_certificados_editar` | Certificados | seeder | — | sí |
| `aca_certificados_eliminar` | Certificados | seeder | — | sí |
| `aca_certificados_listado` | Certificados | seeder | JS (menú) | sí |
| `aca_certificados_nuevo` | Certificados | seeder | — | sí |
| `aca_tutoriales_cortos` | Tutoriales | seeder | JS (menú) | **no** |
| `aca_tutoriales_lista` | Tutoriales | seeder | JS (menú), rutas | **no** |
| `aca_tutoriales_lista_agregar_video` | Tutoriales | seeder | — | **no** |
| `aca_tutoriales_lista_editar` | Tutoriales | seeder | vistas (v-can) | **no** |
| `aca_tutoriales_lista_eliminar` | Tutoriales | seeder | rutas, vistas (v-can) | **no** |
| `aca_tutoriales_lista_nuevo` | Tutoriales | seeder | rutas, vistas (v-can) | **no** |
| `aca_tutoriales_videos` | Tutoriales | seeder | JS (menú), rutas | **no** |
| `aca_tutoriales_videos_editar` | Tutoriales | seeder | — | **no** |
| `aca_tutoriales_videos_eliminar` | Tutoriales | seeder | rutas | **no** |
| `aca_tutoriales_videos_nuevo` | Tutoriales | seeder | rutas | **no** |
| `aca_reportes` | Reportes | seeder | JS (menú), rutas | sí |
| `aca_reportes_estado_susc_estudiantes` | Reportes | seeder | rutas, vistas (v-can) | **no** |
| `aca_asistencia_administrador` | Asistencia | seeder | JS (menú), rutas | **no** |
| `aca_asistencia_crear_link` | Asistencia | seeder | rutas | sí |
| `aca_gestion_de_calificaciones` | Calificaciones y participaciones | seeder | JS (menú), rutas | sí |
| `aca_gestion_de_participaciones` | Calificaciones y participaciones | seeder | JS (menú), rutas | sí |
| `aca_school_alumno_apoderados` | Colegio (school) | seeder | JS (menú), rutas | **no** |
| `aca_school_alumno_apoderados_editar` | Colegio (school) | migración | rutas, vistas (JS) | **no** |
| `aca_school_alumno_editar` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_alumno_eliminar` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_alumno_listado` | Colegio (school) | seeder | JS (menú), rutas | **no** |
| `aca_school_alumno_nuevo` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_area_editar` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_area_eliminar` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_area_listado` | Colegio (school) | seeder | JS (menú), rutas | **no** |
| `aca_school_area_nuevo` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_cobro_listado` | Colegio (school) | seeder | JS (menú), rutas | **no** |
| `aca_school_cobro_registrar` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_docente_editar` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_docente_eliminar` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_docente_listado` | Colegio (school) | seeder | JS (menú), rutas | **no** |
| `aca_school_docente_notas` | Colegio (school) | seeder | JS (menú), rutas, seeder (asigna) | **no** |
| `aca_school_docente_nuevo` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_editar` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_eliminar` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_estructura` | Colegio (school) | seeder | JS (menú), rutas | **no** |
| `aca_school_horario_docente` | Colegio (school) | seeder | JS (menú), rutas, seeder (asigna) | sí |
| `aca_school_horario_editar` | Colegio (school) | seeder | rutas | sí |
| `aca_school_horario_listado` | Colegio (school) | seeder | JS (menú), rutas | sí |
| `aca_school_listado` | Colegio (school) | seeder | JS (menú), rutas | **no** |
| `aca_school_matricula_editar` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_matricula_listado` | Colegio (school) | seeder | JS (menú), rutas | **no** |
| `aca_school_matricula_nueva` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_nuevo` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_porteria_escaner` | Colegio (school) | seeder | JS (menú), rutas, seeder (asigna) | sí |
| `aca_school_porteria_reporte` | Colegio (school) | seeder | JS (menú), controlador, rutas, seeder (asigna) | sí |
| `aca_school_tarifa_editar` | Colegio (school) | seeder | JS (menú), rutas | **no** |
| `aca_school_tarifa_eliminar` | Colegio (school) | seeder | JS (menú), rutas | **no** |
| `aca_school_tarifa_listado` | Colegio (school) | seeder | JS (menú), rutas | **no** |
| `aca_school_year_editar` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_year_listado` | Colegio (school) | seeder | JS (menú), rutas | **no** |
| `aca_school_year_nuevo` | Colegio (school) | seeder | rutas | **no** |
| `aca_school_sms_configuracion` | SMS / SMSGate | seeder | JS (menú), rutas, seeder (asigna) | sí |
| `aca_smsgate_configuracion` | SMS / SMSGate | migración, seeder | seeder (asigna) | sí |
| `aca_smsgate_guia` | SMS / SMSGate | migración, seeder | — | sí |

## Usados en el código pero no definidos por el módulo

Ninguno: todos los permisos que usa el código del módulo están definidos en su seeder o en sus
migraciones.

## En la base de datos pero no definidos en el código del módulo

- `aca_estudiante_matriculas_crear`

Referencias externas al módulo que conviene revisar:

- `database/seeders/AutomatizacionesRoleSeeder.php` menciona `aca_estudiante_matricular`,
  `aca_estudiantes_matricular`, `aca_estudiante_matriculas_crear` y `Aca_student_matricular`. El primero
  sí está en el seeder del módulo; `aca_estudiantes_matricular` y `Aca_student_matricular` no existen en
  ninguna parte del código ni en la base de datos (posibles nombres escritos de más).
- `database/seeders/PermissionsReconcileSeeder.php` crea `aca_testimonios`,
  `aca_estudiante_enviar_correo_acceso` y `aca_school_alumno_apoderados_editar`, fuera del módulo
  Académico: los dos primeros son de otros módulos y el último existe solo por migración
  (`2026_10_13_000002_add_school_student_guardian_edit_permission.php`), que concede a
  `admin` y `Administrador` el permiso para editar los datos de un apoderado.
- `aca_course_notification` **no es un permiso**: es la clave del flujo de WhatsApp definida en
  `Modules/Academic/Config/config.php`.

## Permisos definidos que hoy no se usan en el código

Se crean en el seeder o en una migración, pero ninguna ruta, controlador ni el menú los
consulta: son candidatos a limpiar o a conectar.

`aca_certificados_editar`, `aca_certificados_eliminar`, `aca_certificados_nuevo`, `aca_cursos_modulos`, `aca_smsgate_guia`, `aca_tutoriales_lista_agregar_video`, `aca_tutoriales_videos_editar`

## Lista de permisos sin registro en la base de datos

Son los que hay que revisar si se esperaba que existieran en producción:

`aca_asistencia_administrador`, `aca_cursos_examen_eliminar`, `aca_cursos_examen_final_crear`, `aca_cursos_examen_final_editar`, `aca_cursos_listado_estudiantes`, `aca_cursos_revisar_examenes`, `aca_estudiante_importar_excel`, `aca_estudiante_listar_comprobantes`, `aca_estudiante_listar_cuotas_espaciales`, `aca_estudiante_matricular`, `aca_reportes_estado_susc_estudiantes`, `aca_school_alumno_apoderados`, `aca_school_alumno_editar`, `aca_school_alumno_eliminar`, `aca_school_alumno_listado`, `aca_school_alumno_nuevo`, `aca_school_area_editar`, `aca_school_area_eliminar`, `aca_school_area_listado`, `aca_school_area_nuevo`, `aca_school_cobro_listado`, `aca_school_cobro_registrar`, `aca_school_docente_editar`, `aca_school_docente_eliminar`, `aca_school_docente_listado`, `aca_school_docente_notas`, `aca_school_docente_nuevo`, `aca_school_editar`, `aca_school_eliminar`, `aca_school_estructura`, `aca_school_listado`, `aca_school_matricula_editar`, `aca_school_matricula_listado`, `aca_school_matricula_nueva`, `aca_school_nuevo`, `aca_school_tarifa_editar`, `aca_school_tarifa_eliminar`, `aca_school_tarifa_listado`, `aca_school_year_editar`, `aca_school_year_listado`, `aca_school_year_nuevo`, `aca_suscripcion_estudiante_editar`, `aca_tutoriales_cortos`, `aca_tutoriales_lista`, `aca_tutoriales_lista_agregar_video`, `aca_tutoriales_lista_editar`, `aca_tutoriales_lista_eliminar`, `aca_tutoriales_lista_nuevo`, `aca_tutoriales_videos`, `aca_tutoriales_videos_editar`, `aca_tutoriales_videos_eliminar`, `aca_tutoriales_videos_nuevo`

