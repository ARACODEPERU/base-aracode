# Permisos de Tesorería — diagnóstico del módulo Treasury

Generado: 05/11/2026 22:32  
Base de datos: `base_app_aracode`

## Resumen

| Dato | Valor |
|---|---|
| Permisos totales en el sistema | 375 |
| Permisos `treasury_*` existentes | 1 |
| Permisos que exige el módulo | 11 |
| Permisos que faltan crear | 10 |
| Módulo `M023` (Tesorería) | NO existe en la tabla `modulos` |
| Roles del sistema | admin, webAdmin, Alumno, Docente, Contabilidad, Lector, Administrador, Ventas, Portero, sports_organizer |

## Permisos que exige el módulo Treasury

| Permiso | Acción | Rutas que lo exigen | Estado | Roles que lo tienen | Módulo |
|---|---|---|---|---|---|
| `treasury_dashboard` | Dashboard de tesorería (ítem y grupo del menú) | GET /treasury/dashboard | ✅ existe | admin | sin módulo |
| `treasury_cuentas` | Ver cuentas de tesorería | GET /treasury/accounts | ❌ FALTA | — | — |
| `treasury_cuentas_nuevo` | Crear cuenta | POST /treasury/accounts/store | ❌ FALTA | — | — |
| `treasury_cuentas_editar` | Editar cuenta y asignar método de pago | POST /treasury/accounts/update/{id} · POST /treasury/accounts/assign-payment-method | ❌ FALTA | — | — |
| `treasury_cuentas_eliminar` | Eliminar cuenta | DELETE /treasury/accounts/destroy/{id} | ❌ FALTA | — | — |
| `treasury_movimientos` | Ver libro de bancos | GET /treasury/transactions | ❌ FALTA | — | — |
| `treasury_movimientos_nuevo` | Registrar movimiento manual | POST /treasury/transactions/store | ❌ FALTA | — | — |
| `treasury_movimientos_editar` | Editar movimiento manual | POST /treasury/transactions/update/{id} | ❌ FALTA | — | — |
| `treasury_movimientos_eliminar` | Anular movimiento | DELETE /treasury/transactions/destroy/{id} | ❌ FALTA | — | — |
| `treasury_conciliacion` | Ver y conciliar | GET /treasury/reconciliation · POST /treasury/reconciliation/reconcile | ❌ FALTA | — | — |
| `treasury_categorias` | CRUD de categorías (un solo permiso para todo) | GET /treasury/categories · POST store/update · DELETE destroy | ❌ FALTA | — | — |

## Permisos a crear

- `treasury_cuentas` — Ver cuentas de tesorería
- `treasury_cuentas_nuevo` — Crear cuenta
- `treasury_cuentas_editar` — Editar cuenta y asignar método de pago
- `treasury_cuentas_eliminar` — Eliminar cuenta
- `treasury_movimientos` — Ver libro de bancos
- `treasury_movimientos_nuevo` — Registrar movimiento manual
- `treasury_movimientos_editar` — Editar movimiento manual
- `treasury_movimientos_eliminar` — Anular movimiento
- `treasury_conciliacion` — Ver y conciliar
- `treasury_categorias` — CRUD de categorías (un solo permiso para todo)

### Cómo crearlos

El seeder del módulo ya declara los 11 permisos, crea el módulo `M023` y se los concede al rol `admin` (id 1):

```bash
php artisan db:seed --class="Modules\Treasury\Database\Seeders\PermissionsModuleSeeder"
```

Es idempotente (`firstOrCreate`), así que se puede reejecutar sobre esta base sin duplicar filas.

### Permisos opcionales (si quieres granularidad en categorías)

Hoy `treasury_categorias` abre el CRUD completo de categorías. Si se separa por acción, habría que crear además:

- `treasury_categorias_nuevo`
- `treasury_categorias_editar`
- `treasury_categorias_eliminar`

y cambiar `Modules/Treasury/Routes/web.php` (hoy las cuatro rutas de categorías usan `treasury_categorias`).

## Todos los permisos existentes (375)

Agrupados por prefijo, tal como están hoy en la tabla `permissions`.

### `aca` (35)

- `aca_alumno_examenes` — roles: admin, Alumno
- `aca_asistencia_administrador` — roles: admin
- `aca_asistencia_crear_link` — roles: admin, Docente
- `aca_category_sector_type_modality` — roles: admin
- `aca_cursos_examen_resolver` — roles: admin, Alumno
- `aca_cursos_listado_estudiantes` — sin rol
- `aca_cursos_modulos` — roles: admin
- `aca_cursos_modulos_examen` — roles: admin
- `aca_cursos_revisar_examenes` — sin rol
- `aca_dashboard` — roles: admin
- `aca_estudiante_enviar_correo_acceso` — roles: admin
- `aca_estudiante_exportar_excel` — roles: admin
- `aca_estudiante_importar_excel` — sin rol
- `aca_estudiante_listar_comprobantes` — sin rol
- `aca_estudiante_listar_cuotas_espaciales` — sin rol
- `aca_gestion_de_calificaciones` — roles: admin, Docente
- `aca_gestion_de_participaciones` — roles: admin, Docente
- `aca_miscursos` — roles: admin, Alumno
- `aca_reportes` — roles: admin, Docente
- `aca_reportes_estado_susc_estudiantes` — sin rol
- `aca_school_alumno_apoderados` — roles: admin
- `aca_school_docente_notas` — roles: admin
- `aca_school_estructura` — roles: admin
- `aca_school_horario_docente` — roles: admin, Docente
- `aca_school_matricula_nueva` — roles: admin
- `aca_school_modo_prueba` — roles: admin
- `aca_school_porteria_escaner` — roles: admin, Administrador, Portero
- `aca_school_porteria_reporte` — roles: admin, Administrador
- `aca_send_notifications` — roles: admin
- `aca_smsgate_guia` — roles: admin
- `aca_suscripciones` — roles: admin
- `aca_testimonios` — roles: Alumno
- `aca_tutoriales_cortos` — sin rol
- `aca_tutoriales_lista_agregar_video` — sin rol
- `aca_tutoriales_videos` — sin rol

### `aca_certificados` (4)

- `aca_certificados_editar` — roles: admin
- `aca_certificados_eliminar` — roles: admin
- `aca_certificados_listado` — roles: admin
- `aca_certificados_nuevo` — roles: admin

### `aca_cursos` (4)

- `aca_cursos_editar` — roles: admin
- `aca_cursos_eliminar` — roles: admin
- `aca_cursos_listado` — roles: admin, Docente
- `aca_cursos_nuevo` — roles: admin

### `aca_cursos_examen` (3)

- `aca_cursos_examen_configuracion` — roles: admin
- `aca_cursos_examen_eliminar` — sin rol
- `aca_cursos_examen_ver` — roles: admin, Alumno

### `aca_cursos_examen_final` (2)

- `aca_cursos_examen_final_crear` — sin rol
- `aca_cursos_examen_final_editar` — sin rol

### `aca_docente` (4)

- `aca_docente_editar` — roles: admin
- `aca_docente_eliminar` — roles: admin
- `aca_docente_listado` — roles: admin
- `aca_docente_nuevo` — roles: admin

### `aca_estudiante` (6)

- `aca_estudiante_cobrar` — roles: admin
- `aca_estudiante_editar` — roles: admin
- `aca_estudiante_eliminar` — roles: admin
- `aca_estudiante_listado` — roles: admin
- `aca_estudiante_matricular` — roles: admin
- `aca_estudiante_nuevo` — roles: admin

### `aca_estudiante_certificados` (1)

- `aca_estudiante_certificados_crear` — roles: admin

### `aca_estudiante_matriculas` (1)

- `aca_estudiante_matriculas_crear` — roles: admin

### `aca_institucion` (4)

- `aca_institucion_editar` — roles: admin
- `aca_institucion_eliminar` — roles: admin
- `aca_institucion_listado` — roles: admin
- `aca_institucion_nuevo` — roles: admin

### `aca_school` (4)

- `aca_school_editar` — roles: admin
- `aca_school_eliminar` — roles: admin
- `aca_school_listado` — roles: admin
- `aca_school_nuevo` — roles: admin

### `aca_school_alumno` (4)

- `aca_school_alumno_editar` — roles: admin
- `aca_school_alumno_eliminar` — roles: admin
- `aca_school_alumno_listado` — roles: admin
- `aca_school_alumno_nuevo` — roles: admin

### `aca_school_alumno_apoderados` (1)

- `aca_school_alumno_apoderados_editar` — roles: admin, Administrador

### `aca_school_area` (4)

- `aca_school_area_editar` — roles: admin
- `aca_school_area_eliminar` — roles: admin
- `aca_school_area_listado` — roles: admin
- `aca_school_area_nuevo` — roles: admin

### `aca_school_cobro` (2)

- `aca_school_cobro_listado` — roles: admin
- `aca_school_cobro_registrar` — roles: admin

### `aca_school_docente` (4)

- `aca_school_docente_editar` — roles: admin
- `aca_school_docente_eliminar` — roles: admin
- `aca_school_docente_listado` — roles: admin
- `aca_school_docente_nuevo` — roles: admin

### `aca_school_horario` (2)

- `aca_school_horario_editar` — roles: admin, Administrador
- `aca_school_horario_listado` — roles: admin, Administrador

### `aca_school_matricula` (2)

- `aca_school_matricula_editar` — roles: admin
- `aca_school_matricula_listado` — roles: admin

### `aca_school_sms` (1)

- `aca_school_sms_configuracion` — roles: admin, Administrador

### `aca_school_tarifa` (3)

- `aca_school_tarifa_editar` — roles: admin
- `aca_school_tarifa_eliminar` — roles: admin
- `aca_school_tarifa_listado` — roles: admin

### `aca_school_telegram` (1)

- `aca_school_telegram_configuracion` — roles: admin

### `aca_school_year` (3)

- `aca_school_year_editar` — roles: admin
- `aca_school_year_listado` — roles: admin
- `aca_school_year_nuevo` — roles: admin

### `aca_smsgate` (1)

- `aca_smsgate_configuracion` — roles: admin

### `aca_suscripcion_estudiante` (1)

- `aca_suscripcion_estudiante_editar` — sin rol

### `aca_suscripciones` (3)

- `aca_suscripciones_editar` — roles: admin
- `aca_suscripciones_eliminar` — roles: admin
- `aca_suscripciones_nuevo` — roles: admin

### `aca_tutoriales` (1)

- `aca_tutoriales_lista` — sin rol

### `aca_tutoriales_lista` (3)

- `aca_tutoriales_lista_editar` — sin rol
- `aca_tutoriales_lista_eliminar` — sin rol
- `aca_tutoriales_lista_nuevo` — sin rol

### `aca_tutoriales_videos` (3)

- `aca_tutoriales_videos_editar` — sin rol
- `aca_tutoriales_videos_eliminar` — sin rol
- `aca_tutoriales_videos_nuevo` — sin rol

### `acco` (2)

- `acco_dashboard` — roles: admin
- `acco_pagos_cuotas_especiales` — roles: admin

### `acco_documento` (1)

- `acco_documento_listado` — roles: admin

### `acco_pagos_cuotas_especiales` (1)

- `acco_pagos_cuotas_especiales_nuevo` — roles: admin

### `bib` (5)

- `bib_dashboard` — roles: admin
- `bib_leer_libro` — roles: admin, Lector
- `bib_libros_paginas` — roles: admin
- `bib_libros_secciones` — roles: admin
- `bib_suscripciones_cancelar` — roles: admin

### `bib_autores` (4)

- `bib_autores_editar` — roles: admin
- `bib_autores_eliminar` — roles: admin
- `bib_autores_listar` — roles: admin
- `bib_autores_nuevo` — roles: admin

### `bib_categorias` (4)

- `bib_categorias_editar` — roles: admin
- `bib_categorias_eliminar` — roles: admin
- `bib_categorias_listar` — roles: admin
- `bib_categorias_nuevo` — roles: admin

### `bib_libros` (4)

- `bib_libros_editar` — roles: admin
- `bib_libros_eliminar` — roles: admin
- `bib_libros_listado` — roles: admin
- `bib_libros_nuevo` — roles: admin

### `bib_organizaciones` (4)

- `bib_organizaciones_editar` — sin rol
- `bib_organizaciones_eliminar` — sin rol
- `bib_organizaciones_listar` — sin rol
- `bib_organizaciones_nuevo` — sin rol

### `bib_planes` (4)

- `bib_planes_editar` — roles: admin
- `bib_planes_eliminar` — roles: admin
- `bib_planes_listar` — roles: admin
- `bib_planes_nuevo` — roles: admin

### `bib_suscripciones` (3)

- `bib_suscripciones_editar` — roles: admin
- `bib_suscripciones_listar` — roles: admin
- `bib_suscripciones_nuevo` — roles: admin

### `bib_tags` (3)

- `bib_tags_eliminar` — roles: admin
- `bib_tags_listar` — roles: admin
- `bib_tags_nuevo` — roles: admin

### `blog` (3)

- `blog_articulos` — roles: admin
- `blog_categorias` — roles: admin
- `blog_dashboard` — roles: admin

### `blog_articulos` (3)

- `blog_articulos_editar` — roles: admin
- `blog_articulos_eliminar` — roles: admin
- `blog_articulos_nuevo` — roles: admin

### `blog_categorias` (3)

- `blog_categorias_editar` — roles: admin
- `blog_categorias_eliminar` — roles: admin
- `blog_categorias_nuevo` — roles: admin

### `caja` (1)

- `caja_chica` — roles: admin

### `cigle` (3)

- `cigle_dashboard` — roles: admin
- `cigle_miembros` — roles: admin
- `cigle_tipo_miembro` — roles: admin

### `cigle_creyente` (4)

- `cigle_creyente_editar` — roles: admin
- `cigle_creyente_eliminar` — roles: admin
- `cigle_creyente_listado` — roles: admin
- `cigle_creyente_nuevo` — roles: admin

### `cigle_tipo_miembro` (2)

- `cigle_tipo_miembro_eliminar` — roles: admin
- `cigle_tipo_miembro_nuevo` — roles: admin

### `clientes` (4)

- `clientes` — roles: admin
- `clientes_editar` — roles: admin
- `clientes_eliminar` — roles: admin
- `clientes_nuevo` — roles: admin

### `cms` (14)

- `cms_blog_suscriptores` — roles: admin
- `cms_dashboard` — roles: admin, webAdmin
- `cms_editor` — roles: admin, webAdmin
- `cms_items` — roles: admin, webAdmin
- `cms_mensajes_contacto` — roles: admin
- `cms_pagina` — roles: admin, webAdmin
- `cms_pagina_seccion` — roles: admin, webAdmin
- `cms_pagina_seccion_items` — roles: admin, webAdmin
- `cms_pagina_seccion_items_delete` — roles: admin, webAdmin
- `cms_seccion` — roles: admin, webAdmin
- `cms_seccion_grupos` — roles: admin, webAdmin
- `cms_seccion_items` — roles: admin, webAdmin
- `cms_subscribers_list` — roles: admin
- `cms_testimonios` — roles: admin, webAdmin

### `cms_blog_suscriptores` (1)

- `cms_blog_suscriptores_exportar` — roles: admin

### `cms_mensajes_contacto` (3)

- `cms_mensajes_contacto_editar` — roles: admin
- `cms_mensajes_contacto_eliminar` — roles: admin, webAdmin, Administrador
- `cms_mensajes_contacto_ver` — roles: admin

### `cms_pagina` (3)

- `cms_pagina_editar` — roles: admin, webAdmin
- `cms_pagina_eliminar` — roles: admin, webAdmin
- `cms_pagina_nuevo` — roles: admin, webAdmin

### `cms_seccion` (3)

- `cms_seccion_editar` — roles: admin, webAdmin
- `cms_seccion_eliminar` — roles: admin, webAdmin
- `cms_seccion_nuevo` — roles: admin, webAdmin

### `cms_testimonios` (4)

- `cms_testimonios_aprobar` — roles: admin
- `cms_testimonios_editar` — roles: admin, webAdmin
- `cms_testimonios_eliminar` — roles: admin, webAdmin
- `cms_testimonios_nuevo` — roles: admin, webAdmin

### `comm` (3)

- `comm_contratos_cronograma` — roles: admin, Administrador
- `comm_dashboard` — roles: admin, Administrador
- `comm_negociaciones_verificar` — roles: admin, Administrador

### `comm_clientes` (4)

- `comm_clientes_editar` — roles: admin, Administrador
- `comm_clientes_eliminar` — roles: admin, Administrador
- `comm_clientes_listado` — roles: admin, Administrador
- `comm_clientes_nuevo` — roles: admin, Administrador

### `comm_configuracion` (2)

- `comm_configuracion_editar` — roles: admin, Administrador
- `comm_configuracion_ver` — roles: admin, Administrador

### `comm_contratos` (4)

- `comm_contratos_editar` — roles: admin, Administrador
- `comm_contratos_eliminar` — roles: admin, Administrador
- `comm_contratos_listado` — roles: admin, Administrador
- `comm_contratos_nuevo` — roles: admin, Administrador

### `comm_negociaciones` (4)

- `comm_negociaciones_editar` — roles: admin, Administrador, Ventas
- `comm_negociaciones_eliminar` — roles: admin, Administrador, Ventas
- `comm_negociaciones_listado` — roles: admin, Administrador, Ventas
- `comm_negociaciones_nuevo` — roles: admin, Administrador, Ventas

### `conf` (4)

- `conf_alertas` — roles: admin
- `conf_dashboard` — roles: admin
- `conf_errores` — roles: admin, Administrador
- `conf_historial_actividades` — roles: admin

### `conf_errores` (1)

- `conf_errores_eliminar` — roles: admin, Administrador

### `configuracion` (1)

- `configuracion` — roles: admin

### `crm` (11)

- `crm_chat_asistente` — roles: admin
- `crm_chat_messages` — roles: admin, Contabilidad
- `crm_chat_notifications` — roles: admin, Contabilidad
- `crm_clientes_preguntas_ia` — roles: admin
- `crm_dashboard` — roles: admin
- `crm_dudas_comunes` — roles: admin
- `crm_empresas_agregar_empleados` — roles: admin
- `crm_empresas_eliminar_empleados` — roles: admin
- `crm_empresas_empleados` — roles: admin
- `crm_empresas_enviar_correo_empleados` — roles: admin
- `crm_envio_correo_masivo` — roles: admin

### `crm_chat` (1)

- `crm_chat_dashboard` — roles: admin

### `crm_contactos` (4)

- `crm_contactos_editar` — roles: admin
- `crm_contactos_eliminar` — roles: admin
- `crm_contactos_listado` — roles: admin
- `crm_contactos_nuevo` — roles: admin

### `crm_dudas_comunes` (1)

- `crm_dudas_comunes_edicion` — roles: admin

### `crm_empresas` (4)

- `crm_empresas_editar` — roles: admin
- `crm_empresas_eliminar` — roles: admin
- `crm_empresas_listado` — roles: admin
- `crm_empresas_nuevo` — roles: admin

### `crm_mailbox` (1)

- `crm_mailbox_dashboard` — roles: admin

### `dashboard` (1)

- `dashboard` — roles: admin

### `dental` (2)

- `dental_citas_acceso_atencion` — roles: admin
- `dental_dashboard` — roles: admin

### `dental_atencion` (4)

- `dental_atencion_editar` — roles: admin
- `dental_atencion_eliminar` — roles: admin
- `dental_atencion_listado` — roles: admin
- `dental_atencion_nuevo` — roles: admin

### `dental_citas` (4)

- `dental_citas_editar` — roles: admin
- `dental_citas_eliminar` — roles: admin
- `dental_citas_listado` — roles: admin
- `dental_citas_nuevo` — roles: admin

### `empresa` (1)

- `empresa` — roles: admin

### `even` (3)

- `even_ediciones_diplomas` — roles: admin, sports_organizer
- `even_ediciones_galeria` — roles: admin
- `even_ediciones_premiados` — roles: admin, sports_organizer

### `even_alquiler_local` (5)

- `even_alquiler_local_editar` — roles: admin
- `even_alquiler_local_eliminar` — roles: admin
- `even_alquiler_local_listado` — roles: admin
- `even_alquiler_local_nuevo` — roles: admin
- `even_alquiler_local_pagos` — roles: admin

### `even_ediciones_diplomas` (1)

- `even_ediciones_diplomas_disenar` — roles: admin, sports_organizer

### `even_ediciones_galeria` (2)

- `even_ediciones_galeria_eliminar` — roles: admin
- `even_ediciones_galeria_nuevo` — roles: admin

### `even_local` (4)

- `even_local_editar` — roles: sports_organizer
- `even_local_eliminar` — roles: sports_organizer
- `even_local_listado` — roles: sports_organizer
- `even_local_nuevo` — roles: sports_organizer

### `even_organizaciones` (1)

- `even_organizaciones_admin` — roles: admin, Administrador

### `heal` (4)

- `heal_avisos` — roles: admin, webAdmin, Administrador
- `heal_dashboard` — roles: admin
- `heal_google_calendar` — roles: admin, webAdmin, Administrador
- `heal_odontology` — roles: admin

### `heal_citas` (4)

- `heal_citas_editar` — roles: admin
- `heal_citas_eliminar` — roles: admin
- `heal_citas_listado` — roles: admin
- `heal_citas_nuevo` — roles: admin

### `heal_codigo_cie10` (4)

- `heal_codigo_cie10_editar` — roles: admin
- `heal_codigo_cie10_eliminar` — roles: admin
- `heal_codigo_cie10_listado` — roles: admin
- `heal_codigo_cie10_nuevo` — roles: admin

### `heal_doctores` (4)

- `heal_doctores_editar` — roles: admin
- `heal_doctores_eliminar` — roles: admin
- `heal_doctores_listado` — roles: admin
- `heal_doctores_nuevo` — roles: admin

### `heal_pacientes` (4)

- `heal_pacientes_editar` — roles: admin
- `heal_pacientes_eliminar` — roles: admin
- `heal_pacientes_listado` — roles: admin
- `heal_pacientes_nuevo` — roles: admin

### `integrationhub` (6)

- `integrationhub_crear` — roles: admin
- `integrationhub_dashboard` — roles: admin
- `integrationhub_editar` — roles: admin
- `integrationhub_ejecutar` — roles: admin
- `integrationhub_eliminar` — roles: admin
- `integrationhub_listado` — roles: admin

### `invo` (7)

- `invo_comunicacion_baja` — roles: admin, Contabilidad
- `invo_dashboard` — roles: admin, Contabilidad
- `invo_documento` — roles: admin
- `invo_documento_anular` — roles: admin
- `invo_documento_envio_sunat` — roles: admin
- `invo_nota_credito` — roles: admin, Contabilidad
- `invo_tipo_cambio` — roles: admin

### `invo_documento` (2)

- `invo_documento_lista` — roles: admin, Contabilidad
- `invo_documento_nuevo` — roles: admin

### `invo_resumenes` (1)

- `invo_resumenes_lista` — roles: admin, Contabilidad

### `modulos` (1)

- `modulos` — roles: admin

### `onli` (5)

- `onli_dashboard` — roles: admin
- `onli_items` — roles: admin
- `onli_pedidos` — roles: admin
- `onli_pedidos_enviar_boletas` — roles: admin
- `onli_pedidos_responder` — roles: admin

### `onli_items` (3)

- `onli_items_editar` — roles: admin
- `onli_items_eliminar` — roles: admin
- `onli_items_nuevo` — roles: admin

### `onli_pedidos` (1)

- `onli_pedidos_aprobar` — roles: admin

### `parametros` (4)

- `parametros` — roles: admin
- `parametros_editar` — roles: admin
- `parametros_eliminar` — roles: admin
- `parametros_nuevo` — roles: admin

### `permisos` (1)

- `permisos` — roles: admin

### `productos` (7)

- `productos` — roles: admin
- `productos_codigoqr` — roles: admin
- `productos_editar` — roles: admin
- `productos_eliminar` — roles: admin
- `productos_entrada` — roles: admin
- `productos_nuevo` — roles: admin
- `productos_salida` — roles: admin

### `proveedores` (4)

- `proveedores` — roles: admin
- `proveedores_editar` — roles: admin
- `proveedores_eliminar` — roles: admin
- `proveedores_nuevo` — roles: admin

### `punto` (1)

- `punto_ventas` — roles: admin, Alumno

### `res` (4)

- `res_insumos` — roles: admin
- `res_insumos_compra` — roles: admin
- `res_lista_compras` — roles: admin
- `res_recetas` — roles: admin

### `res_insumos` (2)

- `res_insumos_editar` — roles: admin
- `res_insumos_nuevo` — roles: admin

### `res_recetas` (1)

- `res_recetas_editar` — roles: admin

### `roles` (1)

- `roles` — roles: admin

### `sale` (16)

- `sale_aplicar_descuento` — roles: admin
- `sale_categorias` — roles: admin
- `sale_categorias_agregar` — roles: admin
- `sale_categorias_visualizar` — roles: admin
- `sale_dashboard` — roles: admin
- `sale_documento_fisico` — roles: admin
- `sale_importar_facturador3` — roles: admin
- `sale_marcas` — roles: admin
- `sale_marcas_agregar` — roles: admin
- `sale_marcas_visualizar` — roles: admin
- `sale_registar_producto_alvender` — roles: admin
- `sale_reportes` — roles: admin
- `sale_servicios` — roles: admin
- `sale_tienda` — roles: admin
- `sale_tienda_agregar_vendedor` — roles: admin
- `sale_tienda_series` — roles: admin

### `sale_categorias` (3)

- `sale_categorias_editar` — roles: admin
- `sale_categorias_eliminar` — roles: admin
- `sale_categorias_nuevo` — roles: admin

### `sale_documento_fisico` (3)

- `sale_documento_fisico_editar` — roles: admin
- `sale_documento_fisico_eliminar` — roles: admin
- `sale_documento_fisico_nuevo` — roles: admin

### `sale_marcas` (3)

- `sale_marcas_editar` — roles: admin
- `sale_marcas_eliminar` — roles: admin
- `sale_marcas_nuevo` — roles: admin

### `sale_productos` (1)

- `sale_productos_importar` — roles: admin

### `sale_servicios` (3)

- `sale_servicios_editar` — roles: admin
- `sale_servicios_eliminar` — roles: admin
- `sale_servicios_nuevo` — roles: admin

### `sale_tienda` (3)

- `sale_tienda_editar` — roles: admin
- `sale_tienda_eliminar` — roles: admin
- `sale_tienda_nuevo` — roles: admin

### `treasury` (1)

- `treasury_dashboard` — roles: admin

### `usuarios` (5)

- `usuarios` — roles: admin
- `usuarios_editar` — roles: admin
- `usuarios_eliminar` — roles: admin
- `usuarios_nuevo` — roles: admin
- `usuarios_ver` — roles: admin

## Módulos en la tabla `modulos`

| Identificador | Descripción | Permisos enlazados |
|---|---|---|
| M015 | Cuentas por cobrar | 4 |
| M016 | Comunidad de la iglesia | 9 |
| M017 | Biblio Data | 31 |
| M019 | Configuración y seguridad | 11 |
| M020 | Centro de integraciónes | 6 |
| M021 | Comercial | 17 |
