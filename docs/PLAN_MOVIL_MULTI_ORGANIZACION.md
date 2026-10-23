# Plan: app móvil multi-organización (alta web, acceso móvil y aislamiento)

## 1. Reglas del producto

1. **El alta es solo por la web.** El organizador crea su cuenta, su organización y su usuario en la web. La app no implementa registro.
2. **La app se descarga y se usa después.** Instalada la app, el organizador inicia sesión con esas credenciales y accede a las mismas funcionalidades que tiene en la web, dentro de su organización.
3. **Sin sesión, solo lectura pública.** Quien no inició sesión ve la información de torneos y ediciones igual que hoy. Las funcionalidades de administración quedan detrás del login.
4. **“Crear cuenta” en la app es un puente a la web.** La opción abre la página de registro (`/register`) en el navegador; no hay formulario nativo.
5. **Aislamiento total.** Cada organización ve y modifica solo sus datos. La organización se deriva del token; nunca del cliente.
6. **Vigencia la fija el superadministrador.** Al vencer, la app conserva la lectura y bloquea la escritura.

Estas reglas sustituyen la idea anterior de registrar organizaciones desde la app: el backend de alta sigue siendo el único punto de creación y la app solo consume la sesión resultante.

## 2. Situación actual

### App Flutter (`aracode-movil`)

- `lib/core/services/api_service.dart` centraliza HTTP, token en `flutter_secure_storage` y usuario en `GetStorage`. Ya cubre endpoints públicos, delegado de equipo y administración (`/socialevents/v1/admin/*`).
- `lib/presentation/controllers/auth_controller.dart` solo distingue `isAdmin` por nombre de rol; no conoce organización, plan ni permiso de escritura.
- `lib/core/widgets/admin_route_guard.dart` bloquea la administración con `isAdmin`, por lo que **un organizador con cuenta propia queda fuera** aunque el backend ya lo acepte.
- `lib/presentation/controllers/event_selection_controller.dart` elige entre **todos** los eventos publicados y guarda `selected_event_id`, `selected_edition_id` y `event_data` en claves globales.
- `lib/core/services/offline/pending_queue_service.dart` encola escrituras bajo `pending_operations`, sin identificar usuario ni organización.
- `lib/presentation/pages/login_page.dart` ya tiene el botón “¿Olvidaste tu contraseña?”, pero solo muestra “Funcionalidad en desarrollo”. No hay enlace a registro.
- No hay pantalla de plan, pagos, usuarios de la organización ni gestión de eventos/ediciones.

### Backend ya disponible

- `EnsureSocialeventsAdmin` acepta `sports_organizer` con `socialevent_organization_id` y bloquea escrituras cuando el acceso venció (403).
- Los controladores de la API administrativa ya validan pertenencia con `assertOwnedApiEdition/Team/Match`.
- El registro web (`GET/POST /register`, `RegisteredUserController`) crea usuario + organización + prueba; solo está habilitado con `PW00001 = 2`.
- `AuthController::login` devuelve `user` y `roles`, sin organización ni vigencia.
- `EventApiController::getActiveEvents` devuelve eventos publicados de todas las organizaciones; es la fuente actual del selector móvil.

## 3. Brechas

| # | Brecha | Impacto |
|---|--------|---------|
| G1 | La sesión móvil no lleva organización ni plan | No se puede filtrar ni avisar del vencimiento |
| G2 | El selector usa eventos públicos globales | El organizador puede elegir torneos ajenos |
| G3 | Claves de `GetStorage` globales | Al cambiar de cuenta se ven datos del anterior |
| G4 | No hay endpoint “mis torneos” | La app no puede listar solo lo propio |
| G5 | El modo solo lectura solo se descubre al fallar una escritura | Mala experiencia y cola offline condenada a fallar |
| G6 | `AdminRouteGuard` solo admite `isAdmin` | El organizador no entra a la administración |
| G7 | No hay enlace a registro web | El usuario nuevo no sabe dónde crear su cuenta |
| G8 | Pagos y estado de cuenta solo en la web | El organizador no puede recargar desde el móvil |
| G9 | La cola offline no distingue organización | Riesgo de enviar operaciones con otro token/tenant |
| G10 | Faltan funcionalidades de la web (eventos, ediciones, fixture, sanciones, premios, usuarios) | No se cumple “las mismas funcionalidades” |
| G11 | Endpoints públicos por `editionId` no verifican publicación ni propiedad | Fuga de datos de ediciones no publicadas |
| G12 | Recuperación de contraseña sin implementar | Abandono si el usuario la olvida |

## 4. Arquitectura propuesta

### 4.1 Contexto de sesión

Un `SessionContext` hidratado desde el backend y guardado junto al token:

```
user: { id, name, email, avatar, roles[] }
organization: { id, name, status, trial_ends_at, access_until, days_left, is_legacy }
access: { can_read, can_write, read_only, reason }
features: { mobile_payments, extra_users }
```

- El servidor deriva la organización del token; la app nunca envía `organization_id`.
- `access.can_write` es la única fuente de verdad para habilitar escrituras.
- El superadministrador conserva acceso global; si cambia de organización, se hace con un encabezado explícito aceptado solo para su rol y auditado.

### 4.2 Público vs. autenticado

| Contexto | Datos | Acceso |
|----------|-------|--------|
| Sin sesión | Eventos/ediciones publicados, partidos, posiciones, estadísticas | Igual que hoy |
| Organizador | Sus eventos y ediciones (incluidos borradores) | Login + pertenencia |
| Delegado | Su equipo, jugadores, partidos, sanciones | Login + rol |

Endpoints nuevos:

| Uso | Endpoint |
|-----|----------|
| Sesión completa | `GET /api/me` → `user`, `roles`, `organization`, `access`, `features` |
| Mis torneos | `GET /api/socialevents/v1/my/events` (solo su organización, incluye no publicados) |
| Mi edición | `GET /api/socialevents/v1/my/editions/{id}` (autoriza por pertenencia) |
| Plan y pagos | `GET /api/organizations/current`, `GET /api/organizations/current/payments` |
| Recarga | `POST /api/organizations/current/topup-intent` → `checkout_url` (checkout web) |
| Usuarios | `GET /api/organizations/current/users` (solo lectura en el MVP) |

Código de error homogéneo para escritura bloqueada:

```
403 {
  "success": false,
  "code": "ORG_READ_ONLY",
  "message": "El acceso de la organización está vencido...",
  "access": { "can_write": false, "reason": "trial_expired" }
}
```

Además, los endpoints públicos de edición deben responder 404 cuando la edición no está publicada y quien consulta no es dueño ni superadministrador (cierra G11).

### 4.3 Enlace a la web

- “Crear cuenta” abre `AppConfig.webBaseUrl + '/register'` con `url_launcher` (ya es dependencia).
- “Recuperar contraseña” abre `/forgot-password`.
- Al volver del navegador, la app reintenta el login; no hay traspaso automático de sesión en el MVP.
- Se puede añadir `?origen=app` para medir conversiones. Un handoff con token firmado queda como mejora posterior.

### 4.4 Almacenamiento por contexto

```
session.user_id
session.organization_id
org.<orgId>.selected_event_id
org.<orgId>.event_data
org.<orgId>.pending_operations
```

Migración de una sola vez desde las claves globales actuales; limpieza total en logout y al cambiar de organización. La cola rechaza operaciones nuevas en solo lectura y descarta las pendientes al recibir `ORG_READ_ONLY`.

### 4.5 Modo solo lectura y navegación

- `AccessController` observa `can_write`; banner persistente con fecha límite y botón “Ver plan”.
- `ReadOnlyGuard` envuelve rutas de escritura.
- Navegación por modo: espectador (pestañas actuales), organizador (Mis torneos + administración), delegado (mi equipo).
- `AdminRouteGuard` pasa a admitir `isOrganizer || isAdmin` y a respetar `can_write`.

## 5. Paridad con la web

“Las mismas funcionalidades” se alcanza por etapas; esta matriz fija el orden.

| Funcionalidad web | Estado en la app | Fase |
|-------------------|------------------|------|
| Ver torneos y ediciones publicados | Sí | — |
| Iniciar sesión con cuenta web | Sí (sin contexto de organización) | 1 |
| Ver mis torneos y elegir edición propia | Sí | 1 y 4 |
| Registrar actas, resultados e informes | Sí (parcial) | 4 |
| Equipos y jugadores de la edición | Sí | 4 |
| Galería del torneo | Sí | 4 |
| Crear/editar eventos y ediciones | Sí | 5A |
| Generar fixture y programar partidos | Sí (fixture; partido individual en “Editar Partido”) | 5A |
| Sanciones, suspensiones, exclusiones, premios | Sí (el cobro de sanciones queda en la web) | 5B |
| Plan, saldo, pagos e historial | Sí | 3 |
| Usuarios de la organización | Sí (lectura; altas en la web) | 5C |
| Reportes/PDF y publicación de landing | Sí (actas, planillas, sanciones y landing) | 5C |

## 6. Fases

Estimación orientativa con un desarrollador; backend y móvil pueden solaparse.

### Fase 0 — Prerrequisitos backend (1–1.5 semanas)

1. `GET /api/me` con organización, acceso y features (sin tocar `/user/roles`, que se conserva).
2. `GET /api/my/events` y `GET /api/my/editions/{id}` con pertenencia.
3. `code: ORG_READ_ONLY` en el 403 y `access` en la respuesta.
4. Restringir endpoints públicos de edición a publicadas o dueño/superadmin.
5. Tests de aislamiento entre dos organizaciones y de solo lectura.

**No se crea endpoint de registro:** el alta sigue en `/register`.

### Fase 1 — Fundaciones móviles (1–1.5 semanas)

1. `SessionContext`, `SessionService` y migración de claves globales.
2. `ApiService` con errores tipados (`ORG_READ_ONLY`, 401, 404) y `/api/me`.
3. `SessionController` que reemplaza el uso directo de `AuthController`; guards por modo.
4. `AdminRouteGuard` habilitado para organizador y bloqueado en solo lectura.
5. Inicialización de `main.dart`: sesión antes de eventos.

**Aceptación**: con token guardado la app conoce organización y `can_write`; sin token solo muestra el directorio público; logout no deja datos ni cola del usuario anterior.

### Fase 2 — Puente web y experiencia de acceso (1 semana)

1. `WelcomePage` o bloque de bienvenida con “Iniciar sesión” y “Crear cuenta” (abre `/register`).
2. Login con destino por contexto: organizador → Mis torneos; espectador → inicio público.
3. Reemplazar el aviso “Funcionalidad en desarrollo” por `/forgot-password` web.
4. Mensajes claros para cuenta sin organización, cuenta desactivada y credenciales inválidas.
5. `MyTournamentsPage` en estado vacío con guía para crear el primer torneo en la web.

**Aceptación**: un usuario creado en la web inicia sesión en la app y cae en su espacio; un visitante sigue viendo torneos sin login; “Crear cuenta” abre la web correcta.

### Fase 3 — Acceso, solo lectura y pagos (1 semana)

1. Banner de plan, `ReadOnlyGuard` y bloqueo de la cola offline.
2. `AccessStatusPage` con estado, saldo y movimientos.
3. Botón de recarga con checkout web.
4. Aviso al recibir `ORG_READ_ONLY` a mitad de una acción.

**Aceptación**: con la prueba vencida la app queda en solo lectura, permite leer y pagar, y habilita escrituras al reactivarse sin reinstalar.

### Fase 4 — Mis torneos y administración propia (1.5 semanas)

1. `MyTournamentsPage` con eventos/ediciones propios y estado de publicación.
2. Sustituir el selector global en el flujo de administración.
3. Ajustar partidos, actas, equipos, jugadores y galería a la edición propia.
4. Encabezado con la edición activa y cambio solo entre las propias.

**Aceptación**: el organizador alterna sus ediciones; no puede seleccionar ni alcanzar una ajena.

### Fase 5A — Eventos, ediciones y fixture (2 semanas)

1. Crear/editar evento y edición desde la app con las reglas de servidor.
2. Generación de fixture y programación de partidos.
3. Validaciones y permisos equivalentes a la web.

### Fase 5B — Sanciones, suspensiones, exclusiones y premios (1.5 semanas)

1. Pantallas y endpoints ya existentes en la web.
2. Reglas de solo lectura y fechas.

### Fase 5C — Usuarios y reportes (completada)

1. Asientos incluidos/usados y usuarios de la organización (lectura; invitación según plan).
2. Reportes/PDF y publicación de landing.

### Fase 6 — Endurecimiento y publicación (implementada; pendiente staging y firma de tienda)

1. Pruebas de widget e integración de los flujos críticos.
2. Prueba manual con dos organizaciones en staging.
3. Telemetría de 401/403/404 y versión.
4. Build y despliegue gradual.

Totales orientativos: **≈4 semanas** para las fases 0–2 (flujo “alta web → login → mis torneos”), **≈7.5–8 semanas** para las fases 0–4 y 6 (organización operativa con sus torneos y pagos) y **≈12–12.5 semanas** para la paridad completa de la matriz (incluye 5A, 5B y 5C). Las fases 5A–5C pueden reordenarse según el valor para el organizador.

## 7. Flujos de referencia

### Alta y primer ingreso

1. Web: `/register` → cuenta + organización + prueba.
2. App: instalar → “Iniciar sesión”.
3. `GET /api/me` → modo organizador.
4. `MyTournamentsPage` vacía → “Crear torneo” desde la app y luego su primera edición.

### Visitante

1. Abre la app sin sesión → directorio de torneos publicados.
2. Puede ver ediciones, partidos, posiciones y estadísticas.
3. Al tocar una acción de administración: “Inicia sesión para administrar”.

### Vencimiento durante el uso

1. La escritura devuelve 403 `ORG_READ_ONLY`.
2. `ApiService` actualiza `access`; la UI pasa a solo lectura.
3. La cola offline se detiene y se avisa; el pago se ofrece desde el banner.

## 8. Pruebas

**Unitarias**: mapeo de `SessionContext`, `can_write` en guards, migración/limpieza de claves, cola bloqueada en solo lectura.

**Widget**: login con destino por rol; “Crear cuenta” abre la URL; banner y `ReadOnlyGuard`; Mis torneos vacía/con datos; pestañas por modo.

**Integración/contrato**: cuenta web → login app → `/api/me`; dos organizaciones aisladas (404/403); lectura 200 y escritura 403 al vencer; reactivación sin relogin; edición no publicada 404 para terceros.

## 9. Checklist de seguridad

- [ ] La organización se deriva del token; nunca del cuerpo ni de la URL.
- [ ] Sin sesión no hay rutas de escritura alcanzables, ni por deep link.
- [ ] Token en `flutter_secure_storage`; datos y cola namespaced.
- [ ] Logout y cambio de organización limpian token, contexto, caché y cola.
- [ ] 401 fuerza reautenticación; 403 `ORG_READ_ONLY` no se reintenta.
- [ ] El enlace a registro/recuperación abre el dominio oficial.
- [ ] Logs sin tokens ni datos personales.
- [ ] Checkout en navegador; no se almacenan datos de tarjeta.

## 10. Riesgos

| Riesgo | Mitigación |
|--------|------------|
| Fuga por endpoints públicos por ID | Restricción por publicación/propiedad + tests |
| Pérdida de selección al migrar claves | Migración versionada y prueba de upgrade |
| Cola offline con otra cuenta | Namespaced + purga al cambiar de sesión |
| Expectativa de paridad total inmediata | Matriz de paridad y entregas por fase |
| Usuario registrado en la web pero sin organización | `/api/me` responde `organization: null` y la app muestra guía |
| Bloqueo a mitad de formulario | Guard previo + captura del 403 |

## 11. Decisiones ya cerradas

- Alta, organización y usuario: **solo web**.
- La app abre el registro web desde “Crear cuenta”; no hay formulario nativo.
- Los usuarios externos (no administradores) son **organizadores por defecto**, tengan o no el rol explícito en la respuesta del servidor.
- Sin login: solo consulta pública de torneos y ediciones.
- Con login: funcionalidades de gestión, con paridad progresiva según la matriz.

## 12. Decisiones pendientes

1. **Pagos**: checkout web (recomendado para el MVP) o SDK nativo de Culqi/Mercado Pago.
2. **Multipertenencia**: se mantiene una organización por usuario (`users.socialevent_organization_id`) en el MVP.
3. **Verificación de correo**: la maneja la web; confirmar si bloquea el primer login en la app.
4. **Usuarios adicionales**: invitación desde la app en Fase 5C o solo administración web.
5. **Superadministrador en móvil**: conserva acceso global completo o solo lectura/auditoría.

## 13. Criterios de aceptación finales

- Un usuario creado en la web inicia sesión en la app y accede a la administración de su organización.
- Sin sesión, la app solo muestra información pública de torneos y ediciones.
- “Crear cuenta” y “Recuperar contraseña” abren las páginas web correctas.
- El organizador no ve ni alcanza datos de otra organización.
- La app refleja el vencimiento, bloquea escrituras y permite pagar; al reactivarse, vuelve a escribir sin reinstalar.
- Las funcionalidades de la matriz de paridad quedan cubiertas o explícitamente fuera de alcance.

## 14. Estado de implementación

### Fase 0 — Backend (completada)

- `GET /api/me` con organización, `access` (`can_read`, `can_write`, `reason`) y features.
- `GET /api/socialevents/v1/my/events` y `/my/editions/{id}` con pertenencia derivada del token.
- `403 ORG_READ_ONLY` con el bloque `access` en las escrituras administrativas.
- Los endpoints públicos de edición responden 404 para borradores ajenos; el dueño y el superadministrador sí los ven.
- Pruebas: `MobileOrganizationApiTest` (aislamiento entre dos organizaciones y solo lectura).

### Fase 1 — Fundaciones móviles (completada)

- `SessionContext`, `SessionService` y `SessionController`; `AuthController` retirado y sus usos migrados.
- `ApiService` con errores tipados (`ORG_READ_ONLY`, 401, 404) y adopción inmediata del `access` que devuelve un 403.
- Claves namespaced (`org.<id>` / `user.<id>` / `guest`) para selección de torneo, caché del dashboard y cola offline; migración única desde las claves globales.
- `AdminRouteGuard` admite organizador (`sports_organizer`) y bloquea la administración en solo lectura; el delegado con organización no hereda el menú de administración.
- `main.dart` hidrata la sesión antes de cargar eventos; el logout purga token, sesión, selección, caché y cola del contexto anterior.
- Si el servidor aún no expone `/api/me` (404/405), la app arma una sesión mínima con `/user/roles` y conserva el comportamiento anterior; un 401 sí invalida el token.

Verificación: `flutter analyze` sin errores y `flutter test` con 53 pruebas en verde (modelo de sesión, claves namespaced y guard). Queda pendiente la prueba en dispositivo/emulador contra staging para validar login real, arranque sin conexión y purga al cerrar sesión.

### Fase 2 — Puente web y experiencia de acceso (completada)

- El directorio público muestra un bloque de bienvenida: sin sesión ofrece “Iniciar sesión” y “Crear cuenta” (abre `/register?origen=app`); con sesión muestra la cuenta, su organización y el acceso a “Mi espacio”.
- Login con destino por contexto: administrador → panel público; usuario externo → `/my-tournaments`. Un organizador sin organización recibe un diálogo con el enlace para crearla en la web.
- “¿Olvidaste tu contraseña?” abre `/forgot-password?origen=app`; ya no queda el aviso de “funcionalidad en desarrollo”.
- Mensajes de error diferenciados: credenciales, demasiados intentos (429), tiempo de espera y falta de conexión.
- `MyTournamentsPage` lista los torneos de la organización (incluidos borradores) con edición, estado de publicación, equipos y partidos; contempla carga, error, lista vacía y cuenta sin organización.
- Acciones de la lista: abrir el torneo en la app, guía para crear el primer torneo o la edición en la web y refresco de la vigencia.

Verificación: `flutter analyze` sin errores y `flutter test` con 67 pruebas en verde (política de rol, destino por contexto, resultado de “mis torneos”, URLs web, tarjeta de sesión y guard). Falta la prueba en dispositivo contra un backend con la Fase 0 desplegada: sin `/api/me` y `/my/events` la app cae correctamente en la guía, pero no puede listar los torneos.

Pendiente de contrato: el backend no bloquea el login de un usuario con `users.status = false`; hoy ese estado no tiene enforcement en la web ni en la API. La app muestra cuentas sin organización, cuentas con acceso vencido y credenciales inválidas, pero “cuenta desactivada” requiere decidir primero dónde se valida.

### Fase 3 — Acceso, solo lectura y pagos (completada)

- Endpoints nuevos en la API móvil, siempre con la organización derivada del token:
  - `GET /api/socialevents/v1/organizations/current` → organización, `access`, saldo, tarifa por partido y datos del checkout.
  - `GET /api/socialevents/v1/organizations/current/payments` → recargas de la organización y total acreditado.
  - `POST /api/socialevents/v1/organizations/current/topup-intent` → `checkout_url` web con el monto precargado; no crea cobros ni recibe datos de tarjeta.
- Las cuentas vencidas y suspendidas conservan el acceso a su estado de cuenta y al pago, que es la vía para reactivarse. Una cuenta sin organización recibe `403 ORG_REQUIRED`.
- `AccessStatusPage` ("Mi plan") muestra vigencia, estado de solo lectura, saldo, tarifa por partido, movimientos y el botón de recarga. Se actualiza al volver del navegador y en cada entrada.
- `PlanBanner` avisa en Inicio y en Mis torneos cuando el acceso está en solo lectura o vence en siete días o menos; lleva a "Mi plan".
- `ReadOnlyGuard` envuelve las pantallas de escritura (resultado, acta, edición de partido, equipos, jugadores y galería) para que un vencimiento a mitad de la sesión no deje formularios abiertos enviando un 403.
- Un `403 ORG_READ_ONLY` actualiza el `access` de la sesión y muestra un único aviso con acceso directo al plan; si hay cambios en la cola offline, se informa que se enviarán al reactivar el acceso (no se descartan).
- La app refresca la sesión al volver de segundo plano, así una reactivación web habilita la escritura sin reinstalar ni cerrar sesión.
- El checkout web precarga `?amount=` que envía la app; la cola offline permanece bloqueada mientras `can_write` sea falso.

Verificación: `MobileOrganizationPaymentsApiTest` (7 pruebas PHP: plan, vencimiento, suspensión, cuenta sin organización, aislamiento de movimientos y checkout) y `flutter test` con 85 pruebas en verde (modelos de estado de cuenta y movimientos, visibilidad del banner, `ReadOnlyGuard`, pantalla "Mi plan" sin sesión y transición a solo lectura). `flutter analyze` sin errores.

Pendiente de integración: el POST de checkout exige que el correo esté verificado en la web (`verified`), igual que la página de recarga; si un organizador tiene el correo sin verificar, el navegador lo llevará al aviso de verificación en lugar del formulario. Falta probar en dispositivo el regreso desde Mercado Pago y la reactivación real del acceso.

### Fase 4 — Mis torneos y administración propia (completada)

- El selector de torneos dejó de usar el directorio público cuando hay sesión con organización: carga `GET /my/events`, incluye borradores y nunca otro tenant. Sin sesión, o contra un backend anterior a la Fase 0 (404/405), se conserva el directorio público.
- La pantalla de selección muestra las ediciones de cada torneo propio con su estado (`Público` / `Borrador`); al tocar una se entra con esa edición y no con la última publicada por defecto.
- `MyTournamentsPage` pide la edición antes de abrir un torneo con varias ediciones.
- Encabezado con la edición activa y cambio entre ediciones propias: icono en la cabecera del torneo, acción en “Administrar Partidos” y entrada “Cambiar Edición” en Más. El selector solo lista las ediciones del evento propio; `selectEdition` rechaza un id que no esté en esa lista.
- La selección guardada se restaura validando que el torneo siga en el directorio propio; al iniciar sesión, una selección pública de la sesión anterior se descarta si no pertenece a la organización.
- Los datos de la edición se cargan con el endpoint público, que ya autoriza al dueño de una edición sin publicar; no hizo falta un endpoint nuevo ni cambios de backend.

Verificación: `flutter test` con 93 pruebas en verde (8 nuevas: edición guardada, edición ajena inexistente, edición preferida, estado de publicación y selector de edición) y `flutter analyze` sin errores. El aislamiento del servidor sigue cubierto por `MobileOrganizationApiTest` (mis torneos, edición ajena 404 y borradores).

Pendiente de dispositivo: validar el cambio de edición con dos ediciones reales y confirmar que las pantallas de equipos, jugadores y galería recargan al volver del selector; hoy toman la edición activa al abrirse y solo “Administrar Partidos” refresca su lista tras el cambio.

### Fase 5A — Eventos, ediciones y fixture (completada)

- Endpoints nuevos en la API móvil, dentro del grupo `admin` (por tanto con `auth:sanctum`, rol de organización y `403 ORG_READ_ONLY` cuando el acceso está vencido):
  - `POST /api/socialevents/v1/admin/events` y `PUT .../admin/events/{id}` → alta y edición del torneo con las validaciones de la web (título, descripción, fechas coherentes, entradas y transmisión).
  - `POST /api/socialevents/v1/admin/editions` y `PUT .../admin/editions/{id}` → alta y edición de la edición con formato, puntaje, inscripción, plantel, tiempos, sanciones, contacto y publicación. Una edición no se puede mover de torneo (`422`).
  - `POST .../admin/editions/{id}/fixtures/generate` → genera los cruces todos contra todos que faltan con los equipos inscritos indicados; conserva los partidos existentes y registra el lote y el cobro por partido igual que la web.
  - `GET /api/socialevents/v1/my/editions/{id}` ahora incluye la configuración editable completa; antes solo traía lo necesario para el listado. Sigue siendo solo para el dueño o el superadministrador.
- La pertenencia se comprueba en cada escritura: un torneo o una edición de otra organización responde `404`, y un equipo ajeno al torneo no se puede usar para el fixture.
- Pantallas nuevas en la app:
  - `TournamentFormPage` (crear y editar torneo) con selector de fechas, entradas opcionales y transmisión.
  - `EditionFormPage` (crear y editar edición) agrupado en identificación, fechas, formato y puntaje, plantel, tiempo de juego, sanciones, contacto y publicación; el año exige cuatro dígitos y el enlace público se valida antes de enviar.
  - `FixtureGeneratorPage` con los equipos inscritos preseleccionados, conteo de cruces (`n·(n−1)/2`), aviso de partidos ya programados y bloqueo por debajo de dos equipos.
- Accesos: botón “+” y menú por torneo en “Mis torneos” (editar torneo, nueva edición, editar edición), botón de alta en el directorio propio y acción “Generar fixture” en “Administrar Partidos” (también desde su estado vacío). El formulario de edición pide la edición cuando el torneo tiene varias.
- Adaptación a teléfono: los formularios usan un ancho máximo de lectura, bloques con título, parejas de campos que se apilan cuando no caben (verificado a 320 dp) y un botón principal a todo el ancho; las etiquetas largas y el texto de ayuda pueden ocupar varias líneas.

Verificación: `MobileTournamentAdminApiTest` (9 pruebas PHP: alta, fechas inválidas, torneo ajeno, alta de edición, edición ajena, edición sin cambiar de torneo, fixture con inscritos, fixture con equipo ajeno y solo lectura) y `MobileOrganizationApiTest` (configuración editable de la edición). `flutter test` con 101 pruebas en verde (8 nuevas: modelo de configuración con valores por defecto, catálogo de formatos, cálculo de cruces, apilado de campos a 320 dp, fila en pantalla ancha, formulario largo sin desbordes y formato de fechas). `flutter analyze` sin errores.

Límites de la fase: el generador solo crea el fixture todos contra todos (los formatos de grupos y eliminación se siguen armando en la web); la programación de un partido concreto ya existe en “Editar Partido”, pero no hay edición masiva de horarios. El formulario móvil no administra bases PDF ni imágenes de la landing. Falta probar en dispositivo el alta completa con datos reales y el cobro por partido que dispara el fixture.

### Fase 5B — Sanciones, suspensiones, exclusiones y premios (completada)

- Endpoints nuevos en el grupo `admin` de la API móvil:
  - `GET /admin/editions/{id}/sanctions` → tarjetas pendientes por jugador con deuda efectiva (aplica la absorción de amarillas cuando hay doble amarilla), partidos jugados para el registro manual y todos los jugadores inscritos.
  - `POST /admin/editions/{id}/sanctions` → tarjeta manual en un partido con resultado; la doble amarilla registra la expulsión más sus dos amarillas y el importe sale de la configuración de la edición si no se envía.
  - `GET/POST /admin/editions/{id}/suspensions`, `DELETE .../suspensions/{suspensionId}` → listado, alta y levantamiento. Usa `PlayerSuspensionService`, así que los tipos definitiva, por partidos y por fechas se comportan igual que en la web.
  - `GET/POST /admin/editions/{id}/exclusions`, `DELETE .../exclusions/{exclusionId}` → exclusión definitiva de rankings y su restauración.
  - `PUT /admin/editions/{id}/prizes` → premios por puesto con las claves que ya lee la landing (`first`…`fourth`). `GET /my/editions/{id}` ahora incluye `prize_details`.
- Todas las rutas validan pertenencia (`404` para otra edición) y jugador inscrito (`422`); las tarjetas manuales exigen un partido con marcador. La escritura queda bloqueada con `403 ORG_READ_ONLY` cuando el acceso está vencido.
- App:
  - `AdminDisciplinePage` con pestañas Sanciones, Suspensiones y Exclusiones, resumen de deuda pendiente, tarjetas con tipo/monto/partido, estados de suspensión y acciones para levantar o quitar con confirmación.
  - Formularios `SanctionFormPage`, `SuspensionFormPage` y `ExclusionFormPage` con selector de jugador/partido mediante hoja de búsqueda (`showSearchPicker`), útil con planteles largos en el teléfono.
  - `EditionPrizesPage` para editar dinero y regalo de los cuatro puestos; dejar ambos vacíos quita el premio.
  - Entradas nuevas en “Más”: Disciplina y Premios de la edición.
- Adaptación a teléfono: las listas usan tarjetas con `Wrap` para tipo, monto y estado; los textos largos (motivo, partido, nombres) ocupan varias líneas; el selector de búsqueda se probó a 320 dp y los formularios mantienen el ancho de lectura y el apilado de campos de la Fase 5A.

Verificación: `MobileEditionDisciplineApiTest` (8 pruebas PHP: deuda y absorción, doble amarilla, partido/jugador inválido, alta y levantamiento de suspensión, exclusión y restauración, premios con claves de landing, edición ajena y solo lectura) y las 28 pruebas de los tres suites móviles en verde (147 aserciones). `flutter test` con 110 pruebas en verde (9 nuevas: modelos de sanciones, suspensiones, exclusiones y premios, y selector con búsqueda) y `flutter analyze` sin errores.

Límites de la fase: el cobro de sanciones sigue en la web porque emite el comprobante de venta; la app muestra la deuda y registra tarjetas, pero no cobra. Tampoco genera el PDF de sanciones. Los premios se limitan a los cuatro puestos que publica la landing. Falta probar en dispositivo el flujo completo con un torneo real y confirmar que las listas se refrescan al volver de cada formulario.

### Fase 5C — Usuarios y reportes (completada)

- Endpoints nuevos:
  - `GET /organizations/current/users` → personas con acceso a la organización del token, con rol (`owner`/`member`), `is_you`, estado activo y fecha de alta. La lista sale de `users.socialevent_organization_id`, así también aparecen las cuentas históricas sin fila en la tabla de miembros. Se puede consultar con el acceso vencido.
  - `GET /admin/editions/{id}/reports` → actas cerradas con partido, marcador, fecha y estado del reclamo, además de los partidos disponibles para imprimir planilla y un resumen (partidos, actas y partidos con resultado sin acta).
  - `POST /admin/editions/{id}/reports/pdf` → prepara una URL firmada de 30 minutos para un acta (`report_id`), una planilla de partido (`match_id`) o la lista de sanciones. La ruta pública `GET /reports/pdf/{editionId}/{type}` valida la firma y entrega el PDF sin dejar archivos en `storage/public`. Preparar el documento es una lectura y sigue permitido con el acceso vencido.
  - `GET/PUT /admin/editions/{id}/publication` → interruptores de landing y app, enlace público, URL definitiva, conteos (equipos, jugadores, partidos, resultados y actas pendientes) y comprobaciones previas a difundir. El `PUT` exige acceso de escritura (`403 ORG_READ_ONLY` si venció) y genera el slug cuando falta, sin repetir el año si el nombre ya lo incluye.
- Los PDF usan las mismas vistas Blade de la web (`minutes_match`, `match_sheet` y la lista de sanciones), con la misma absorción de la doble amarilla al sumar deuda. La firma de la URL se emite solo después de validar pertenencia a la edición.
- App:
  - `OrganizationUsersPage`: resumen de cuentas con acceso, tarjetas con iniciales, rol, correo, teléfono, fecha de alta y marca “Tú”. Es solo lectura; no crea usuarios ni invitaciones.
  - `EditionReportsPage`: resumen de partidos/actas/sin acta, acciones para la lista de sanciones y la planilla de un partido (selector con búsqueda), y tarjetas de actas con estado del reclamo y botón “Abrir PDF”. Los documentos se abren en el navegador.
  - `EditionPublicationPage`: interruptores de landing y app, enlace público editable con validación, URL para abrir o copiar, conteos y lista de comprobaciones. Reutiliza `ReadOnlyGuard` y el ancho de lectura de los formularios.
  - Entradas nuevas en “Más”: Publicación de la edición, Reportes y PDF y Usuarios de la organización.
- Adaptación a teléfono: las tarjetas usan `Wrap` para rol, estado y etiquetas; los nombres, correos y detalles de las comprobaciones ocupan varias líneas; los botones de documento van a todo el ancho y el formulario mantiene el apilado de la Fase 5A.

Verificación: `MobileEditionReportsApiTest` (12 pruebas PHP: usuarios y aislamiento, cuenta sin organización, actas propias y ajenas, URL firmada y entrega del PDF, URL sin firma rechazada, planilla y sanciones, publicación, slug y solo lectura; 63 aserciones). Las suites móviles relacionadas (`MobileEditionReportsApiTest`, `MobileEditionDisciplineApiTest`, `MobileTournamentAdminApiTest` y `MobileOrganizationApiTest`) quedaron en 40 pruebas y 210 aserciones en verde. `flutter test` con 116 pruebas en verde (6 nuevas de modelos) y `flutter analyze` sin errores; los avisos restantes son preexistentes.

Límites de la fase: el modelo actual no vende asientos, así que la API informa `seats.used` con `limit: null` y `can_invite: false`; las altas de usuarios siguen en la web con el equipo ARACODE. Los reportes son actas ya cerradas: registrar o editar un acta sigue en el flujo del partido, y los PDF se abren en el navegador con una URL temporal, no se descargan dentro de la app. La pantalla de publicación no administra la imagen hero ni el color de marca (siguen en el formulario de edición/web). Falta probar en dispositivo la apertura de los tres PDF y el regreso desde el navegador.

### Fase 6 — Endurecimiento y publicación (implementada; falta prueba en celular, staging y firma)

- Telemetría local de errores de API:
  - `ApiTelemetryService` registra `401`, `403`, `404`, `5xx` y fallos de red con método, ruta normalizada (`/editions/12/...` → `/editions/:id/...`), estado, código de negocio y versión. Descarta los `2xx` y los `422`, no guarda tokens ni datos personales y conserva los últimos 40 eventos en `GetStorage`.
  - Se engancha en `_handleResponse` y en los fallos de conexión de `get/post/put/delete`, sin tocar el flujo de sesión ni la cola offline.
  - `DiagnosticsPage` (Más → Diagnóstico) muestra versión, servidor, contadores, últimos errores, copia del resumen para soporte y borrado. La versión sale de `AppConfig.appVersion`, sincronizada con `pubspec.yaml` mediante una prueba.
- Pruebas nuevas: `api_telemetry_test` (normalización, conteos, descarte de 2xx/422, límite de 40, persistencia y borrado) y `diagnostics_page_test` (renderizado a 320 dp, estado vacío y lectura de rutas), además de la prueba de sincronización de versión en `app_config_links_test`.
- Compilación verificada: `flutter build apk --release` generó `build/app/outputs/flutter-apk/app-release.apk` (90.7 MB). El build sigue firmado con la clave de depuración: la firma de tienda es un bloqueante documentado.
- APK de pruebas en celular contra el servidor local, sin publicar: `flutter build apk --release --dart-define=API_BASE_URL=http://192.168.100.6:8080` deja `build/pruebas-lan/pichanguero-pruebas-lan-192.168.100.6.apk`. Se sirve con `php artisan serve --host=0.0.0.0 --port=8080` y una regla de firewall limitada a la subred local; los pasos y el checklist están en la guía.
- Servidor de pruebas vigente: `app_config.dart` apunta por defecto a `https://pichanguero.com` (la API respondió `200` en `/api/socialevents/v1/events`). `https://avc.pichanguero.com` quedó comentado en el mismo archivo. El APK compilado sin `--dart-define` está en `build/pruebas-lan/pichanguero-pruebas-pichanguero.com.apk` y se copió a `Descargas` del teléfono para instalarlo desde ahí.
- Guía operativa en [PUBLICACION_MOVIL_FASE_6.md](PUBLICACION_MOVIL_FASE_6.md): compilación, prueba en el celular contra el servidor local, checklist manual con dos organizaciones en staging, criterios para detener el despliegue y plan de rollout gradual (interna → 10 % → 50 % → 100 %).

Verificación: `flutter test` con 124 pruebas en verde (8 nuevas) y `flutter analyze` sin errores. Las suites móviles PHP siguen en 40 pruebas y 210 aserciones; el resto de `tests/Feature/Socialevents` mantiene 4 fallos de fixture/saldo ajenos a estos cambios.

Límites de la fase: no se ejecutó la prueba funcional en el celular, la prueba manual con dos organizaciones en staging ni el despliegue gradual desde este entorno; quedan como checklist. La telemetría es local, sin envío remoto. El APK de release usa la clave de depuración y no debe subirse a la tienda hasta configurar el keystore. La instalación por USB quedó bloqueada por el teléfono (`INSTALL_FAILED_USER_RESTRICTED`): hay que abrir el APK desde Descargas o habilitar “Instalar vía USB” en Opciones de desarrollador.
