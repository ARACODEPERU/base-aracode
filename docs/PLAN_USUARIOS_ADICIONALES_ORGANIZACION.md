# Plan: usuarios adicionales, permisos por tarea y compra de espacios

## 1. Objetivo

Permitir que una organización deportiva compre espacios de usuario adicionales y cree cuentas para su equipo. Cada cuenta adicional debe:

- Pertenecer siempre a la organización que la creó.
- Ver únicamente los datos de esa organización.
- Recibir solo las tareas que el propietario le asigne mediante botones descriptivos, sin exponer la lista técnica de permisos.
- Poder registrarse, por ejemplo, solo resultados de partidos, sin acceso a configuración, pagos ni administración de la organización.

La compra se cobra con Mercado Pago, con tarjeta o Yape, reutilizando la integración que ya existe en “Mis pagos”.

Este documento es el plan funcional y técnico. No implementa todavía los cambios.

## 2. Lo que ya existe en el proyecto

- `socialevent_organizations` agrupa eventos, equipos, ediciones y locales. `socialevent_organization_users` relaciona usuarios con la organización y `users.socialevent_organization_id` es la fuente que usa el login para resolver la organización de la sesión.
- `SportsOrganizationService` ya resuelve el aislamiento por organización, el estado de acceso, la lectura/escritura y la pertenencia de eventos, equipos, ediciones y partidos.
- `OrganizationTopUpController` y `Organizations/TopUp.vue` ya cobran recargas de saldo con tarjeta y Yape mediante Mercado Pago, con webhook firmado e idempotencia.
- `socialevent_organization_topups` guarda las recargas de saldo. `socialevent_organization_ledger` registra el movimiento del saldo.
- El módulo de ventas ya genera `Sale`, `SaleDocument` y `sale_document_items`, y envía facturas/boletas a SUNAT con `SaleDocumentController::sendSunatDocument`. `storeAdminPayment` usa ese flujo, pero además recarga saldo y extiende el acceso; para emitir el comprobante de una compra ya pagada no se debe reutilizar tal cual.
- Existe `MetaController::sendMessageWhatsapp`, pero hoy tiene el token y el número fijos en el código. Para enviar comprobantes hay que parametrizarlo o usar un enlace seguro de WhatsApp.
- `socialevent_user_packages` existe desde la migración comercial, pero hoy no se usa en ninguna vista ni controlador.
- El panel de superadministrador ya tiene una pestaña “Usuarios” por organización en `Organizations/Accounts.vue` y el alta manual en `SportsOrganizationAdminController::storeOrganizationUser`.
- La API móvil ya devuelve un resumen de usuarios y un bloque `seats`, pero con `limit: null` y `can_invite: false` porque el modelo de asientos todavía no existe.
- Los permisos se administran con Spatie. El frontend recibe `auth.permissions` desde `HandleInertiaRequests`, pero hoy solo con `getPermissionsViaRoles()`.
- Varias rutas de escritura de Socialevents no tienen middleware `permission:` (por ejemplo `events/store`, `events/update`, `teams/store`, `categories/store`, `categories/update` y la creación de precios de tickets). Hoy no se nota porque todos los miembros tienen el rol completo; con cuentas limitadas sería una vía para saltarse la interfaz.

## 3. Alcance

### Incluye

- Espacios de usuario comprables por unidad, con precio configurable. Valor inicial propuesto: **S/ 1.00 por usuario adicional**.
- Pago con tarjeta y Yape por Mercado Pago.
- Alta, edición de tareas, desactivación y reactivación de usuarios por parte del propietario de la organización.
- Permisos limitados por tareas, aplicados en el servidor y reflejados en el menú y los botones.
- Panel de superadministrador para cambiar el precio, otorgar espacios de cortesía y ver el consumo.
- Panel de compras de organizaciones para el superadministrador, con el detalle de cada compra y su comprobante.
- Solicitud, emisión y entrega de boleta, factura o nota de venta para las compras de espacios y las recargas de saldo.
- Historial de compras de espacios separado del historial de recargas de saldo.
- Migración de organizaciones existentes sin quitar accesos ya otorgados.

### No incluye en esta fase

- Cambiar la lógica de torneos, fixture, resultados o actas.
- Crear un sistema nuevo de roles globales para toda la plataforma.
- Renovación automática, cobro recurrente o devoluciones automáticas.
- Emisión automática del comprobante al aprobarse el pago. La emisión será manual, a solicitud del organizador y realizada por el superadministrador.
- Notas de crédito, anulación o reemisión automática de comprobantes.
- Uso de la app móvil por cuentas con permisos limitados. En esta fase la app queda para el propietario; si se necesita, se amplía después con la misma matriz de permisos.
- Paquetes con descuento. La tabla `socialevent_user_packages` queda disponible para una fase posterior, pero la primera versión vende espacios por unidad.

## 4. Reglas funcionales

### 4.1 Espacios

- Toda organización parte con **1 espacio**, ocupado por el usuario propietario.
- Comprar **6 usuarios nuevos** significa comprar **6 espacios adicionales**: el límite pasa a 7 (1 propietario + 6).
- El espacio se compra **una sola vez y queda para siempre**. No vence, no se renueva y no genera cobros recurrentes.
- Un espacio es un cupo de la organización, no una persona. Si se desactiva un usuario, el cupo vuelve a quedar disponible para otro usuario de la misma organización.
- El propietario siempre ocupa su espacio y no puede desactivarse a sí mismo ni quedarse sin propietario.
- La organización histórica administrada por el superadministrador no tiene límite de espacios.

### 4.2 Compra

- Solo el propietario de la organización puede comprar espacios y administrar a su equipo. El superadministrador también puede crear o asignar usuarios y otorgar espacios desde el panel de cuentas cuando exista un trato directo con el organizador, dejando el motivo en la auditoría.
- La compra se hace desde la vista de plan de la organización, con cantidad, precio unitario, total y medio de pago.
- El servidor calcula el total: `cantidad × precio_unitario`. Nunca confía en el monto enviado por el navegador.
- El precio unitario se guarda en la configuración comercial y puede cambiarlo el superadministrador. Cada compra conserva el precio con el que se pagó.
- Los espacios se acreditan **solo** cuando Mercado Pago confirma el pago como aprobado. Un pago pendiente, rechazado o fallido no aumenta el límite.
- La compra de espacios no recarga el saldo de partidos ni cambia la fecha de vigencia de la organización. Son dos conceptos separados.
- Si la organización está vencida o suspendida, el propietario puede comprar espacios, pero los usuarios nuevos seguirán sujetos al estado de acceso de la organización hasta que se reactive.
- El webhook debe ser idempotente: un mismo pago no puede acreditar espacios dos veces.

### 4.3 Alta de usuarios

- El propietario crea la cuenta desde “Mi plan”, indicando nombre, correo, contraseña inicial y las tareas permitidas.
- El correo debe ser único. La contraseña inicial la define el propietario y se comparte por el medio que prefiera; el usuario puede cambiarla desde su perfil.
- Si no hay espacios disponibles, el alta se bloquea y se ofrece comprar más.
- El servidor impone la organización de la sesión. Aunque se manipule la petición, no se puede crear un usuario en otra organización.
- No se pueden asignar roles de administrador global ni permisos fuera del catálogo de tareas de la organización.

### 4.4 Permisos por tarea

- La interfaz muestra tarjetas con nombres comprensibles: “Registrar resultados”, “Registrar actas y sanciones”, “Programar partidos”, “Gestionar equipos y jugadores”, “Consultar reportes”, “Administrar todo el torneo”.
- Cada tarjeta equivale a un conjunto interno de permisos. El propietario nunca ve nombres técnicos como `even_ediciones_partido_resultado`.
- Se pueden combinar varias tarjetas. “Administrar todo el torneo” selecciona todas las tareas deportivas y no incluye pagos ni gestión de usuarios.
- El servidor valida las tareas contra un catálogo cerrado. Una clave desconocida se rechaza.
- Los permisos se guardan como permisos directos del usuario y se reflejan en el menú, los botones y el middleware de rutas.
- El propietario conserva su rol completo actual. Los usuarios adicionales usan un rol operativo sin permisos fijos y reciben solo los permisos directos asignados.

### 4.5 Edición y baja

- El propietario puede cambiar las tareas de un usuario en cualquier momento. El cambio se aplica en la siguiente petición.
- El propietario puede desactivar a un usuario. Al hacerlo se le quitan sus permisos, se desvincula de la organización y su espacio queda libre.
- La desactivación conserva el historial y la auditoría. No borra los registros que haya creado.
- El usuario desactivado no puede volver a entrar al módulo deportivo. Si se reactiva y hay espacio, recupera el acceso con las tareas que el propietario le asigne.
- Cada alta, cambio de tareas, baja, compra y cortesía queda en `socialevent_organization_audits`.

### 4.6 Acceso a la organización

- Todos los usuarios de la organización comparten eventos, equipos, ediciones y partidos, pero cada uno ve solo las pantallas y acciones que tenga permitidas.
- Las cuentas limitadas no ven el panel de administración de organizaciones, las tarifas, los pagos ni la gestión de usuarios.
- Un ID de otra organización sigue respondiendo `404`.
- El propietario entra a “Mi plan”. Los usuarios limitados no ven esa entrada en el menú.

### 4.7 Compras y comprobantes

- El superadministrador puede ver **todas las compras** de las organizaciones: compras de espacios y recargas de saldo. Cada fila muestra organización, comprador, fecha, concepto, monto, medio de pago, estado y comprobante.
- El propietario puede solicitar un comprobante desde el historial de “Mi plan” para cualquier compra acreditada. Elige boleta, factura o nota de venta.
- Antes de aceptar la solicitud, el sistema valida los datos fiscales:
  - Factura: RUC, razón social, dirección y ubigeo.
  - Boleta: documento de identidad del cliente; si usa RUC, se comporta como factura.
  - Nota de venta: datos mínimos del cliente.
- La solicitud queda pendiente para el superadministrador, que puede emitirla o rechazarla con un motivo visible para el organizador.
- La emisión usa el flujo existente de `Sale`/`SaleDocument` y la serie del local del administrador. Si es factura o boleta electrónica, se envía a SUNAT; si es nota de venta, queda como documento interno.
- **La emisión de un comprobante no vuelve a recargar saldo, no extiende el acceso y no registra dos veces el ingreso.** El pago ya existe en Mercado Pago; el comprobante solo lo documenta.
- El superadministrador puede enviar el comprobante por correo electrónico al correo registrado o a otro correo indicado, y por WhatsApp al número registrado o a otro número indicado. Puede elegir uno o ambos canales.
- El envío queda registrado con fecha, canal, destinatario, estado y error si lo hubiera.
- Una vez emitido, el comprobante queda visible en “Mi plan” para el propietario, con tipo, serie, número, fecha, monto y botones para descargar PDF/XML.
- Si es factura o boleta electrónica y SUNAT lo deja pendiente o lo rechaza, el organizador ve ese estado y el motivo; la descarga como documento válido se habilita cuando SUNAT lo acepta. No se emite un documento nuevo: se reintenta el envío o se corrige la solicitud.
- No se puede emitir dos comprobantes válidos para la misma compra. Si una solicitud fue rechazada, el organizador puede corregir los datos y volver a solicitarla.

## 5. Modelo de datos

### 5.1 `socialevent_organizations`

Agregar:

| Columna | Tipo | Uso |
|---|---|---|
| `seat_limit` | `unsignedSmallInteger`, nullable, default 1 | Espacios totales. `null` significa sin límite para la organización histórica. |

### 5.2 `socialevent_organization_seat_purchases`

Nueva tabla para no mezclar la compra de usuarios con el saldo de partidos:

| Columna | Tipo | Uso |
|---|---|---|
| `id` | bigint | Identificador. |
| `organization_id` | FK | Organización compradora. |
| `purchased_by` | FK users, nullable | Propietario que pagó. |
| `external_reference` | uuid único | Referencia enviada a Mercado Pago. |
| `idempotency_key` | uuid único | Clave de idempotencia del cobro. |
| `provider_payment_id` | string único, nullable | ID del pago en Mercado Pago. |
| `payment_method` | string | `card` o `yape`. |
| `status` | string | `processing`, `approved`, `credited`, `rejected`, `failed`. |
| `quantity` | unsignedSmallInteger | Espacios comprados. |
| `unit_price` | decimal(10,2) | Precio unitario al momento de la compra. |
| `total` | decimal(12,2) | Total cobrado. |
| `credited_at` | timestamp nullable | Momento en que se sumaron los espacios. |
| `timestamps` | | |

Índice por `organization_id` y `created_at`.

### 5.3 `socialevent_organization_document_requests`

Nueva tabla para solicitar y emitir comprobantes sin duplicar la lógica de pagos:

| Columna | Tipo | Uso |
|---|---|---|
| `id` | bigint | Identificador. |
| `organization_id` | FK | Organización que solicita. |
| `requested_by` | FK users, nullable | Propietario que solicita; `null` si la genera el administrador. |
| `purchase_type` | string(30) | `seat_purchase` o `topup`. |
| `purchase_id` | unsignedBigInteger | ID de la compra de espacios o de la recarga. |
| `document_type_id` | FK sale_document_types | Boleta, factura o nota de venta solicitada. |
| `status` | string(20), default `requested` | `requested`, `issued` o `rejected`. |
| `billing_data` | json | Razón social, RUC, dirección, ubigeo, documento y contactos usados al solicitar. |
| `sale_document_id` | FK sale_documents, nullable | Comprobante emitido. |
| `issued_by` | FK users, nullable | Superadministrador que emitió. |
| `issued_at` | timestamp nullable | Fecha de emisión. |
| `delivery_channels` | json, nullable | Canales solicitados o usados: `email`, `whatsapp`. |
| `delivery_log` | json, nullable | Destinatario, estado y error de cada envío. |
| `sent_at` | timestamp nullable | Último envío registrado. |
| `rejection_reason` | text nullable | Motivo visible para el organizador. |
| `timestamps` | | |

Índices por `organization_id`/`status` y por `purchase_type`/`purchase_id`. La emisión se controla para que una compra no tenga dos comprobantes válidos.

### 5.4 `socialevent_organization_users`

Agregar:

| Columna | Tipo | Uso |
|---|---|---|
| `profile_key` | string(60), nullable | Perfil principal mostrado, por ejemplo `results` o `full`. |
| `capabilities` | json, nullable | Claves de tareas asignadas, para mostrar y auditar. |
| `status` | string(20), default `active` | `active` o `inactive`. |
| `permissions_updated_at` | timestamp nullable | Última actualización de permisos. |
| `invited_by` | FK users, nullable | Propietario que creó la cuenta. |

La tabla ya tiene `role` (`owner`/`member`) y `joined_at`.

### 5.5 `socialevent_organization_settings`

Agregar:

| Columna | Tipo | Uso |
|---|---|---|
| `seat_price` | decimal(10,2), default 1.00 | Precio unitario de cada espacio adicional. |

### 5.6 Roles y permisos

- Se crea el rol `sports_operator` sin permisos fijos, solo para identificar cuentas operativas de una organización.
- El propietario conserva `sports_organizer`.
- Los permisos de cada operador se asignan con `syncPermissions()` sobre el usuario.
- `HandleInertiaRequests` debe compartir `getAllPermissions()` en lugar de solo `getPermissionsViaRoles()`, para que el frontend reciba también los permisos directos.
- `organizerHome()` debe dejar de exigir `sports_organizer` y reconocer a cualquier usuario con organización, salvo el superadministrador.

## 6. Catálogo de tareas y permisos internos

El catálogo vivirá en una clase de soporte, por ejemplo `Modules/Socialevents/Support/OrganizationCapabilities.php`, con:

- `all()`: definición de cada tarjeta.
- `permissionsFor(array $keys)`: unión de permisos.
- `assertValidKeys(array $keys)`: validación contra el catálogo.

Base común para todas las cuentas limitadas: `even_dashboard` y `even_ediciones_listado`, para que puedan entrar al módulo y llegar a las ediciones.

| Tarjeta visible | Permisos internos principales |
|---|---|
| Registrar resultados | `even_ediciones_fixtures`, `even_ediciones_partido_resultado` |
| Registrar actas y sanciones | `even_ediciones_fixtures`, `even_ediciones_actas`, `even_ediciones_actas_nuevo`, `even_ediciones_partido_acta`, `even_ediciones_partido_acta_editar`, `even_ediciones_acta_editar`, `even_ediciones_actas_solucion_update`, `even_ediciones_actas_file_update`, `even_ediciones_actas_protest_file_destroy`, `even_ediciones_match_accordance_update`, `even_ediciones_sanciones`, `even_ediciones_sanciones_registrar` |
| Programar partidos | `even_ediciones_fixtures`, `even_ediciones_fixtures_nuevo` |
| Gestionar equipos y jugadores | `even_equipos_listado`, `even_equipos_nuevo`, `even_equipos_editar`, `even_ediciones_equipos`, `even_ediciones_equipos_eliminar`, `even_ediciones_equipo_jugadores`, `even_team_player_store`, `even_team_player_update`, `even_team_player_image_update`, `even_ediciones_equipos_player_destroy`, `even_ediciones_equipos_agregar`, `even_ediciones_equipos_bonus_store`, `even_ediciones_equipos_bonus_destroy`, `even_ediciones_equipos_recalculate` |
| Consultar reportes | `even_ediciones_fixtures`, `even_ediciones_actas`, `even_ediciones_galeria` |
| Administrar todo el torneo | Todos los permisos deportivos de la organización: eventos, equipos, ediciones, fixture, resultados, actas, sanciones, galería, exclusiones, suspensiones y locales. No incluye `even_organizaciones_admin` ni la gestión de usuarios. |

La lista exacta se revisará contra las rutas al implementar. La interfaz solo muestra las tarjetas; el mapeo queda en código y cubierto por pruebas.

## 7. Flujo de compra y pago

1. El propietario abre “Mi plan”. El servidor valida que sea propietario o superadministrador y devuelve: espacios usados, límite, disponibles, precio unitario, usuarios y compras.
2. Elige cuántos espacios comprar. La interfaz muestra el total, pero el servidor lo recalcula.
3. `POST /socialevents/organizations/topup/seats` recibe cantidad, token de Mercado Pago, método (`card`/`yape`) y datos del pagador. No recibe precio ni total.
4. El servidor valida cantidad (por ejemplo, 1 a 50), precio mayor que cero, propiedad de la organización y método permitido; crea la fila en `socialevent_organization_seat_purchases` con estado `processing` y llama a Mercado Pago con una referencia externa única.
5. Si el pago queda aprobado, en una transacción con bloqueo:
   - Se vuelve a leer la compra y se comprueba que no esté acreditada.
   - Se incrementa `socialevent_organizations.seat_limit` en la cantidad comprada.
   - Se marca la compra como `credited` y se guarda `credited_at`.
   - Se registra la auditoría.
6. Si Mercado Pago responde pendiente o rechazado, se actualiza el estado y no se acredita nada.
7. El webhook existente verifica la firma, busca la referencia en compras de espacios y, si no está allí, en recargas de saldo. Debe acreditar una sola vez.
8. El historial de la vista muestra dos tipos de movimiento: “Recarga de saldo” y “Compra de N usuarios”, con estado y referencia.
9. Desde ese historial, el propietario puede solicitar boleta, factura o nota de venta para una compra acreditada. La solicitud se guarda con los datos fiscales y queda pendiente.
10. El superadministrador revisa la solicitud, emite el documento con el flujo de ventas, lo envía por correo o WhatsApp si corresponde y lo deja visible para el propietario.

### Validaciones de seguridad del cobro

- El monto se calcula en el servidor con el precio vigente.
- La cantidad y el precio se guardan como instantánea en la compra.
- Se usa `X-Idempotency-Key` y un `external_reference` único.
- Se rechazan pagos de otra moneda, monto distinto o método distinto al registrado, igual que hace hoy `OrganizationTopUpService`.
- Un webhook repetido no suma espacios otra vez.

## 8. Flujo de alta y gestión de usuarios

### Alta

1. El propietario pulsa “Agregar usuario”.
2. Completa nombre, correo, contraseña inicial y selecciona una o varias tarjetas de tareas.
3. El servidor bloquea la organización, cuenta los miembros activos y comprueba `miembros_activos < seat_limit`. Si no hay cupo, responde `422` con un mensaje que invita a comprar espacios.
4. Crea el `User`, lo vincula a la organización, crea la fila en `socialevent_organization_users` con `role = member` y `status = active`, le asigna el rol `sports_operator` y sincroniza los permisos directos.
5. Guarda `profile_key`, `capabilities` y la auditoría.
6. La interfaz muestra la cuenta creada y su contraseña inicial una sola vez, para que el propietario la comparta.

### Edición de tareas

- `PUT /socialevents/organizations/team/{userId}` con las nuevas tarjetas.
- El servidor comprueba que el usuario pertenezca a la organización del propietario, que no sea el propietario y que las claves sean válidas.
- Reemplaza los permisos directos, actualiza `capabilities` y audita el antes/después.
- Si el usuario venía con el rol completo anterior, en ese momento se le retira `sports_organizer` y se le asigna `sports_operator` con los permisos elegidos.

### Baja y reactivación

- `DELETE /socialevents/organizations/team/{userId}` desactiva: marca `status = inactive`, quita permisos y roles, limpia `users.socialevent_organization_id` y `users.socialevent_organization_role`, y audita.
- El espacio queda libre y puede usarse para otra cuenta.
- `POST /socialevents/organizations/team/{userId}/reactivate` comprueba cupo y restaura la vinculación con las tareas elegidas.
- No se permite desactivar al propietario ni dejar la organización sin propietario.

## 9. Autorización y seguridad

### Servidor

- Nuevo servicio `OrganizationTeamService` (o métodos en `SportsOrganizationService`) con:
  - `isOwner($user)`, `assertOwner($user)`.
  - `seatSummary($organizationId)`.
  - `assertSeatAvailable($organizationId)` con bloqueo de fila.
  - `createMember(...)`, `updateMemberCapabilities(...)`, `deactivateMember(...)`, `reactivateMember(...)`.
  - `creditSeatPurchase(...)`.
- Todas las operaciones derivan la organización de la sesión, nunca de un `organization_id` enviado por el cliente.
- Los endpoints de compra y equipo exigen propietario o superadministrador. La vista “Mi plan” deja de estar disponible para cualquier miembro.
- Se auditan altas, cambios de permisos, bajas, compras, cortesías, solicitudes de comprobante, emisiones y envíos.

### Rutas

- Agregar `permission:` a todas las rutas de escritura que hoy no lo tienen, como mínimo:
  - `events/store` y `events/update` → `even_evento_nuevo` / `even_evento_editar`.
  - `teams/store` → `even_equipos_nuevo`.
  - `categories/store` y `categories/update` → permisos de categoría.
  - `events/prices/tickets/store` → permiso de ventas o evento.
- Revisar el resto de rutas de Socialevents y su API para que ninguna escritura quede solo protegida por la interfaz.
- Los usuarios limitados no pueden acceder a rutas de administración de organizaciones aunque escriban la URL.

### Menú y frontend

- `HandleInertiaRequests` comparte todos los permisos, incluidos los directos, y agrega `sportsOrganization.role` e `is_owner`.
- La entrada “Mi plan” se muestra solo al propietario. Los usuarios limitados ven únicamente las entradas que sus permisos permiten.
- Los botones de compra, alta y edición de usuarios se ocultan a los usuarios limitados; el servidor también los rechaza.
- La directiva `v-can` sigue funcionando con los permisos directos.

### App móvil

- `EnsureSocialeventsAdmin` seguirá exigiendo `sports_organizer` en esta fase. Las cuentas `sports_operator` no podrán usar la API móvil, evitando que un permiso limitado se convierta en acceso total por la app.
- El bloque `seats` de la API móvil se actualizará con límite real, usados y disponibles, pero las invitaciones siguen siendo web.
- Si más adelante se quiere que los operadores usen la app, se deberá aplicar la misma matriz de permisos en cada endpoint móvil.

## 10. Interfaz “Mi plan”

La vista actual “Mis pagos” pasa a llamarse **“Mi plan”**, con el subtítulo “Usuarios, permisos y pagos”. La ruta existente `even_organizations.topup.index` se conserva para no romper enlaces ni el checkout de la app.

Secciones:

1. **Resumen de la organización**
   - Nombre, estado, vigencia, saldo y tarifa por partido.
   - Espacios: “1 incluido + 6 comprados”, “3 en uso”, “4 disponibles”.

2. **Usuarios**
   - Tabla con nombre, correo, perfil, estado y última actualización.
   - Botones “Editar tareas” y “Desactivar”.
   - Botón “Agregar usuario”, deshabilitado si no hay espacios, con enlace a compra.

3. **Comprar espacios**
   - Selector de cantidad, precio unitario, total.
   - Medio de pago: tarjeta o Yape, reutilizando el formulario actual.
   - Aviso: “Comprar usuarios no cambia la vigencia de tu organización”.
   - Resumen: espacios actuales, nuevos y total después del pago.

4. **Historial y comprobantes**
   - Recargas de saldo y compras de usuarios, con fecha, concepto, monto, estado y referencia.
   - Estado del comprobante: “Sin solicitar”, “Solicitado”, “Emitido” o “Rechazado”.
   - Botón “Solicitar comprobante” en compras acreditadas; modal para elegir boleta, factura o nota de venta y confirmar los datos fiscales.
   - Si ya fue emitido: tipo, serie-número, fecha, monto y botones para descargar PDF/XML.
   - Si fue rechazado: motivo y opción para corregir y volver a solicitar.

5. **Modal de alta/edición**
   - Datos de la cuenta.
   - Tarjetas descriptivas de tareas con un resumen de lo que podrá hacer.
   - Validación en vivo: si no hay cupo, se muestra el aviso antes de enviar.

La vista debe funcionar en escritorio, tablet y móvil, manteniendo el tema navy/lima de Gestión Deportiva. En modo clásico de Eventos no se muestra porque no hay organización.

## 11. Panel de superadministrador

### Tarifas (`Organizations/Admin.vue`)

- Nuevo campo “Precio por usuario adicional”, con valor inicial S/ 1.00.
- `updateDefaults` valida y guarda `seat_price`.
- Se muestra el impacto: “6 usuarios = S/ 6.00”.

### Cuentas (`Organizations/Accounts.vue`)

- Mostrar por organización: límite de espacios, usados, disponibles y compras.
- Acción “Otorgar espacios” con cantidad y motivo, para cortesías, acuerdos directos o soporte. Incrementa el límite y audita.
- La pestaña “Usuarios” respeta el límite y permite elegir las mismas tarjetas de tareas. El superadministrador puede crear o asignar una cuenta cuando lo acordó directamente con el organizador; el motivo queda en la auditoría.
- El historial de la organización distingue recargas de saldo y compras de usuarios.

### Compras de organizaciones

- Nueva pestaña “Compras” en `Organizations/Accounts.vue`, con listado global de compras de espacios y recargas de saldo.
- Filtros por organización, comprador, tipo, estado, fecha y estado del comprobante.
- Cada fila muestra organización, comprador, fecha, concepto, monto, medio de pago, referencia, estado y comprobante.
- Las solicitudes pendientes se destacan y se pueden abrir para emitir el documento.
- Acción “Emitir comprobante”: confirma el tipo, los datos fiscales y la serie del local; genera el `SaleDocument`, lo envía a SUNAT si es electrónico y registra el resultado.
- La emisión desde una compra ya pagada **no recarga saldo, no extiende el acceso y no vuelve a registrar el ingreso**. Se separa la emisión del comprobante del registro del pago.
- Si el administrador no tiene local o serie configurada para ese tipo, se muestra el error y no se emite.
- Envío por correo y/o WhatsApp, con registro de destinatario, fecha, estado y error.
- El administrador también puede emitir sin solicitud previa; en ese caso la solicitud se crea a su nombre y queda auditada.

### API móvil

- `OrganizationApiController@users` devuelve `seats.limit`, `used`, `available`, `can_invite` y `seat_price`.
- `show` incluye el resumen de espacios.
- Los comprobantes y solicitudes se administran desde la web; la app móvil no emite ni envía documentos en esta fase.

## 12. Migración y compatibilidad

1. Agregar `seat_limit` a `socialevent_organizations`.
2. Crear `socialevent_organization_seat_purchases`.
3. Agregar columnas de perfil, capacidades, estado y auditoría a `socialevent_organization_users`.
4. Agregar `seat_price` a `socialevent_organization_settings`.
5. Crear `socialevent_organization_document_requests`.
6. Crear el rol `sports_operator` sin permisos.
7. Backfill:
   - `seat_limit = max(1, cantidad de miembros activos)` para cada organización.
   - Organización histórica: `seat_limit = null` (sin límite).
   - Miembros existentes: `status = active`, `profile_key = full`, `capabilities = ["full"]`.
8. **Compatibilidad:** los miembros existentes conservan su rol `sports_organizer` y su acceso actual. Solo cuando el propietario edite sus tareas se convertirán a `sports_operator` con permisos directos. Así no se rompe el acceso móvil ni el trabajo en curso.
9. Los usuarios nuevos siempre se crean como `sports_operator` con permisos directos.
10. Si una organización ya tiene más miembros que espacios, no se expulsan: el límite se ajusta al número actual y solo se bloquean las altas nuevas.

## 13. Fases de implementación

### Fase 0 — Reglas confirmadas

Las decisiones de la sección 15 ya están cerradas. No queda una fase de definición pendiente.

### Fase 1 — Base de datos y catálogo

- Migraciones, backfill y rol `sports_operator`.
- `OrganizationCapabilities`.
- Métodos de resumen de espacios y validación de cupo.

### Fase 2 — Autorización y accesos

- Permisos directos en `HandleInertiaRequests`.
- Cambio de `organizerHome`.
- Middleware `permission:` en rutas de escritura faltantes.
- Menú y vistas solo para propietario.
- Restricción móvil para operadores.

### Fase 3 — Compra de espacios

- Endpoints de compra tarjeta/Yape.
- Tabla de compras, webhook e idempotencia.
- Historial y auditoría.
- Precio configurable y cortesías del administrador.

### Fase 4 — Alta y gestión de usuarios

- Endpoints de alta, edición de tareas, baja y reactivación.
- Validación de cupo con bloqueo.
- Pantalla “Mi plan” y modal de tareas.

### Fase 5 — Panel de superadministrador

- Precio por espacio.
- Otorgamiento de espacios.
- Límite y consumo en cuentas.
- Listado global de compras de espacios y recargas.
- Historial separado por tipo de movimiento.

### Fase 6 — Comprobantes

- Solicitud del propietario con validación de datos fiscales.
- Bandeja de solicitudes pendientes para el superadministrador.
- Emisión con el flujo de ventas, sin duplicar saldo ni ingresos.
- Envío por correo y WhatsApp con registro de entrega.
- Visualización y descarga del comprobante en “Mi plan”.

### Fase 7 — Pruebas y despliegue

- Pruebas de integración y de navegador.
- Ensayo de migración con datos existentes.
- Verificación de que las organizaciones actuales no pierden acceso.

## 14. Pruebas y criterios de aceptación

### Pruebas de servidor

- Propietario compra 6 espacios con pago aprobado: el límite pasa de 1 a 7 y se registra una sola compra.
- Webhook repetido: no suma espacios dos veces.
- Pago rechazado o pendiente: no suma espacios.
- El monto se calcula en el servidor; cambiar el total en la petición no altera el cobro.
- Alta con cupo disponible: crea el usuario, lo vincula a la organización y le asigna solo los permisos de las tareas elegidas.
- Alta sin cupo: `422` y no crea el usuario.
- Un operador no puede comprar espacios, crear usuarios ni editar permisos: `403`.
- Un operador con “Registrar resultados” puede abrir el partido y guardar el marcador; no puede crear eventos, equipos, ediciones ni entrar a administración.
- Un operador con “Programar partidos” puede crear fixture; no puede registrar resultados si no tiene esa tarea.
- Acceso a un recurso de otra organización: `404`.
- Desactivar un usuario libera el espacio; reactivarlo lo vuelve a ocupar.
- Un miembro existente conserva su acceso hasta que el propietario edite sus tareas.
- La organización histórica no tiene límite y el superadministrador conserva su acceso global.
- La API móvil rechaza a un operador con `403` y el propietario sigue funcionando.
- El superadministrador ve las compras de espacios y las recargas de todas las organizaciones, con filtros y detalle.
- Solo se puede solicitar comprobante de una compra acreditada; una compra rechazada o pendiente no lo permite.
- La factura exige RUC, razón social, dirección y ubigeo; la boleta exige documento de identidad; la nota de venta acepta datos mínimos.
- Emitir un comprobante no modifica el saldo, la vigencia ni el límite de espacios.
- No se puede emitir dos comprobantes válidos para la misma compra.
- El envío por correo y WhatsApp queda registrado con destinatario y estado; si falla, el documento sigue emitido y visible.
- Otro organizador no puede ver ni descargar el comprobante de una organización ajena: `404`.

### Pruebas de interfaz

- “Mi plan” muestra espacios usados, límite y disponibles.
- El total de la compra cambia al variar la cantidad.
- Sin cupo, “Agregar usuario” dirige a comprar espacios.
- Las tarjetas de tareas describen la acción sin mostrar nombres técnicos.
- El menú de un operador solo muestra las entradas permitidas.
- La vista responde en escritorio, tablet y móvil sin desbordamientos.
- El superadministrador ve la pestaña “Compras”, filtra por organización y estado, y distingue compras de espacios de recargas.
- El propietario ve el botón “Solicitar comprobante”, el formulario fiscal y el estado de la solicitud.
- Una vez emitido, el comprobante muestra tipo, serie-número, fecha, monto y descarga PDF/XML.
- Si el comprobante fue rechazado, el propietario ve el motivo y puede corregir los datos.

### Criterios de aceptación

- Una organización con un solo usuario puede comprar 6 espacios y crear 6 cuentas.
- Esas cuentas ven solo datos de su organización y solo las tareas asignadas.
- El propietario puede cambiar tareas y desactivar cuentas sin perder el historial.
- El pago con tarjeta y Yape acredita espacios únicamente al aprobarse y una sola vez.
- Los usuarios existentes no pierden acceso durante la migración.
- El superadministrador puede cambiar el precio, crear o asignar usuarios y otorgar espacios con auditoría.
- Los espacios comprados no vencen ni se vuelven a cobrar.
- El pago queda registrado con su referencia; la boleta, factura o nota de venta puede solicitarse después.
- El superadministrador ve todas las compras de los organizadores y puede emitir el comprobante solicitado.
- El comprobante se envía por correo y/o WhatsApp y queda visible para el propietario en “Mi plan”.

## 15. Decisiones confirmadas

| Tema | Decisión |
|---|---|
| Vigencia del espacio | Pago único. El espacio queda para siempre, sin renovación ni cobro recurrente. |
| Espacios al desactivar | Se libera y puede reutilizarse para otra persona de la misma organización. |
| Permisos | Tarjetas predefinidas y combinables. El propietario no ve permisos técnicos. |
| Nombre de la vista | **Mi plan**. |
| Quién administra | El propietario. El superadministrador también puede crear o asignar usuarios y otorgar espacios cuando exista un trato directo con el organizador, con motivo auditado. |
| Comprobante | El propietario lo solicita desde “Mi plan”. El superadministrador emite boleta, factura o nota de venta y la envía por correo y/o WhatsApp. El documento queda visible y descargable para el organizador. La emisión no vuelve a cobrar ni recargar saldo. |
| Compras visibles al administrador | El superadministrador ve todas las compras de espacios y recargas de las organizaciones, con comprador, monto, estado y comprobante. |

## 16. Riesgos y puntos de atención

- **Permisos directos:** hoy el frontend solo recibe permisos por rol. Si no se cambia a `getAllPermissions()`, el menú ocultará opciones que el servidor sí permite.
- **Rutas sin `permission:`:** es el riesgo principal para que una cuenta limitada escape de su tarea mediante una petición directa. Debe cerrarse antes de habilitar operadores.
- **Carrera de cupos:** dos pestañas podrían crear usuarios a la vez. Se resuelve con bloqueo de la organización y conteo dentro de la misma transacción.
- **Webhook duplicado:** Mercado Pago puede reintentar. La acreditación debe ser idempotente por `provider_payment_id` y estado.
- **Monto manipulado:** el navegador no debe enviar precio ni total; solo cantidad y token.
- **App móvil:** si se permite a un operador, la API actual no aplica la matriz de permisos y le daría acceso completo. En esta fase queda fuera.
- **Compatibilidad:** convertir a todos los miembros existentes a permisos limitados de golpe rompería su acceso. Se migran solo cuando el propietario los edite.
- **Renombre:** “Mis pagos” aparece en pruebas y en el menú. Hay que actualizar textos y pruebas sin cambiar la ruta para no romper enlaces.
- **Doble contabilidad:** `storeAdminPayment` recarga saldo y extiende acceso además de emitir el documento. Para comprobantes de compras ya pagadas hay que separar la emisión del registro del ingreso; si se reutiliza tal cual, se duplicarían saldo y tesorería.
- **WhatsApp:** `MetaController` tiene token y número fijos en el código. Antes de usarlo para comprobantes hay que moverlos a configuración segura y validar el número de destino; nunca exponer el token.
- **SUNAT:** una factura o boleta puede quedar pendiente o ser rechazada. El comprobante debe conservar ese estado y permitir reintentar el envío sin emitir un documento nuevo.
- **Privacidad:** los PDF/XML de comprobantes no deben quedar en una URL pública. Se sirven por una ruta autenticada que valide la organización o el rol de superadministrador.
- **Datos fiscales:** una factura con RUC o dirección inválidos debe rechazarse antes de consumir una serie; la corrección se hace sobre la solicitud, no emitiendo otro documento.
