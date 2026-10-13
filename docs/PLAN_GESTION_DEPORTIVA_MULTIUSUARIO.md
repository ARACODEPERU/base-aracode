# Plan: Gestión Deportiva multiusuario y monetización de torneos

## Objetivo

Cuando `PW00001 = 2`, presentar Socialevents como **GESTIÓN DEPORTIVA**, enfocar el flujo en torneos y permitir que organizadores con cuenta propia administren exclusivamente sus eventos, ediciones, equipos y partidos. El acceso gratuito, su vencimiento, las excepciones autorizadas por el superadministrador y los pagos deben ser controlables y auditables. Si `PW00001 != 2`, la experiencia actual de Eventos debe conservarse.

Este documento contiene la especificación acordada y el plan de trabajo. Ya se implementó una primera versión de modo deportivo, aislamiento de organizaciones y administración inicial de tarifas; quedan pendientes las partes explícitas en «Decisiones pendientes» y los puntos resumidos en la entrega de esta implementación.

## Hallazgos del código actual

- `app/Support/PaginaPrincipal.php` ya define `PW00001` y reconoce el valor `2` como portada de torneos (`TORNEOS`). Conviene reutilizar una sola lectura/configuración del modo en lugar de duplicar una interpretación distinta.
- El menú de Socialevents está declarado en `Modules/Socialevents/Resources/assets/js/Menu.js`; actualmente se titula “Eventos”, muestra Categorías y usa iconos distintos a una pelota.
- `EvenEventController` ofrece categorías al formulario y valida `category_id`; el dato se recibe del cliente. Por lo tanto, ocultar el selector visual no basta: el servidor debe elegir/validar “Deportivos” cuando el modo deportivo esté activo.
- `even_events` y `event_teams` no tienen dueño. `event_editions` depende de `even_events`, y partidos dependen de ediciones. `event_teams.name` tiene unicidad global, algo que deberá revisarse para que dos organizadores puedan usar el mismo nombre de equipo.
- Las rutas web de administración están bajo `auth` y `verified` y usan permisos Spatie; la versión implementada agrega middleware de estado/propiedad y comprobaciones explícitas en controladores y rutas API administrativas.
- El registro web ya está definido en `routes/auth.php`. La versión implementada crea organización, rol/permisos de organizador y período de prueba al registrarse cuando el modo deportivo está habilitado.

## Reglas funcionales propuestas

1. **Modo legado**: con `PW00001 != 2`, mantener las etiquetas, campos, categorías y flujo actuales de creación/edición.
2. **Modo deportivo**: con `PW00001 = 2`, el nombre visible del módulo será “GESTIÓN DEPORTIVA”, tendrá iconografía de fútbol/pelota, ocultará Categorías para organizadores y tratará los eventos nuevos como deportivos. El servidor impondrá la categoría deportiva aunque se manipule el formulario o la petición.
3. **Organizaciones y aislamiento**: una cuenta organizadora corresponde a una organización. Todos los eventos, ediciones, equipos, partidos y demás registros pertenecen a esa organización. Por defecto tendrá un usuario organizador; los usuarios extra se habilitan mediante paquetes con cantidad y precio único configurables. Los usuarios de la misma organización comparten los registros conforme a sus permisos; otras organizaciones no pueden acceder. Un ID ajeno responderá `404`. El superadministrador conservará acceso global auditado.
4. **Registros existentes**: una migración asignará los eventos y equipos actuales a una organización de legado administrada por el superadministrador. Ningún dato preexistente quedará visible para una organización nueva por falta de dueño.
5. **Prueba y bloqueo**: el registro público de organizaciones se habilitará solo cuando `PW00001 = 2`. La prueba empieza al crear la cuenta y su duración será configurable por el superadministrador. Vencida la prueba, el backend pasa al modo de solo lectura (equipos, fixture/tabla, actas e informes); no permite nuevos registros ni ediciones salvo excepciones. Al vencer una reactivación pagada vuelve a ese modo de lectura. Para registrar partidos se requiere saldo prepago suficiente.
6. **Reactivación y pagos**: se aceptan pagos totales o parciales, pero un abono no reactiva automáticamente la organización. El superadministrador confirma el acuerdo y fija la fecha de reactivación. El efectivo físico se registra y confirma por el administrador. La tarifa base configurable es **S/ 5 por equipo por partido programado/registrado**, independientemente de si se juega, y puede ajustarse por negociación, cortesía, donación o patrocinio.

## Plan de implementación por fases

### Fase 0 — Cerrar reglas de negocio y modelo de acceso

- Definir explícitamente el rol de superadministrador que puede ver todas las organizaciones, configurar tarifas y pruebas, conceder prestaciones, registrar pagos y activar/restringir organizaciones.
- Crear una matriz acción × estado (superadministrador, prueba vigente, prueba vencida, pago confirmado, cuenta con cortesía/patrocinio) para lectura, creación, edición y eliminación.
- Registro público disponible solo en modo `PW00001 = 2`; la prueba comienza al crear la cuenta y su duración se configura por organización.
- Los IDs de registros ajenos devolverán `404`, evitando revelar su existencia.
- Precio de referencia: S/ 5 por equipo en cada partido creado (S/ 10 por partido con dos equipos), juegue o no. El registro consume saldo prepago de la organización/edición, cargado antes de permitir guardar.
- Cada organización comienza con un usuario organizador. Los usuarios adicionales se habilitan mediante paquetes con cantidad y precio único configurable; el superadministrador puede otorgar paquetes gratuitos por promoción/patrocinio. La tarifa por equipo-partido no cambia por cantidad de usuarios.

### Fase 1 — Modo Gestión Deportiva sin afectar el modo actual

- Exponer una resolución central del modo basada en `PW00001` para controladores y frontend.
- Condicionar título del módulo, encabezados y navegación; usar icono de pelota solo en el modo deportivo.
- Ocultar Categorías en el menú y las vistas deportivas. Restringir también las rutas de mantenimiento de categorías a superadministradores/administradores autorizados; no basta con esconder el enlace.
- En formularios deportivos nuevos y de edición, retirar el selector de categoría y los campos/eventos sociales que no correspondan al torneo. Mantener sin cambios las vistas cuando el parámetro sea distinto de `2`.
- Resolver la categoría “Deportivos” desde el catálogo por identidad estable (no asumir que siempre tiene ID `1`); imponerla en servidor al crear y no permitir que una petición editada la cambie en modo deportivo.
- Revisar títulos, ayudas, validaciones y mensajes de los formularios de evento para reflejar el flujo deportivo. Mantener separados los datos del evento padre y los datos específicos de cada edición/torneo.

### Fase 2 — Registro y espacio inicial del organizador

- Habilitar el registro público existente solo con `PW00001 = 2`, con validación, limitación antiabuso y protección contra asignación de roles privilegiados.
- Al registrarse, crear la organización y su primer usuario organizador con permisos mínimos; iniciar el período de prueba configurable desde la creación de la cuenta. Nunca aceptar rol, permisos, estado de pago, estado de organización o tenant enviados por el cliente.
- Llevar al organizador a un panel vacío y guiado si aún no tiene eventos; permitir el primer evento y después edición → equipos → fixture/partidos.
- Si el modo deportivo está desactivado, preservar el flujo actual del módulo y mantener cerrado el registro público nuevo de organizadores.

### Fase 3 — Propiedad y aislamiento de datos

- Introducir una entidad Organización (tenant) y asociar cada usuario a una organización con rol/estado; permitir un usuario organizador inicial y usuarios adicionales mediante el cargo único acordado. Todos los registros quedan bajo organización.
- Añadir `organization_id` a `even_events` y `event_teams` (y a las tablas que no puedan resolver pertenencia inequívoca por relación) mediante migraciones reversibles con índices/FKs. Ediciones y partidos deben comprobar la pertenencia heredada desde evento/edición. Revisar unicidad de `event_teams.name` para que el mismo nombre pueda existir en organizaciones diferentes sin romper la historia.
- Asignar los eventos/equipos existentes a una organización de legado administrada por el superadministrador, no a las nuevas cuentas.
- Aplicar scopes/policies o un servicio de autorización común para consultas y escrituras. Las listas siempre filtran por propietario; las operaciones individuales verifican cadena completa de pertenencia antes de cargar/alterar datos.
- Auditar todas las rutas web y controladores: eventos, ediciones, equipos, plantillas/jugadores, fixture, resultados/actas, sanciones, galerías, reportes, archivos, puntos y endpoints auxiliares. Repetir la auditoría en API móvil y endpoints JSON/AJAX.
- Revisar recursos compartidos actuales (personas/delegados, locales, expositores, categorías) para decidir si son catálogos globales de solo lectura, propiedad del organizador o funciones deshabilitadas en el modo deportivo.
- Conservar las landing públicas expresamente publicadas; no exponer ediciones privadas/no publicadas ni habilitar escrituras sin sesión por esas rutas.
- Añadir índices de propietario y claves foráneas; planear actualización de consultas y cache keys para evitar datos cruzados y mantener rendimiento.

### Fase 4 — Prueba gratuita, permisos y vencimiento automático

- Persistir el estado comercial separado de permisos genéricos: organización, inicio y fin de prueba, estado (prueba, vencido, activo por pago, patrocinado/sin costo, suspendido), prestaciones y quién lo cambió. La prueba inicia en el registro de la organización/cuenta inicial y su duración la configura el superadministrador.
- Permitir al superadministrador cambiar duración/fecha, extender prueba, conceder uso gratis, seleccionar prestaciones y registrar motivo/auditoría. La activación manual tras pago o acuerdo tendrá fecha de vencimiento definida por el administrador.
- Implementar vencimiento automático comprobado al autorizar peticiones (no depender solo de que corra un cron). Un comando programado puede actualizar estados/notificar, pero el bloqueo debe funcionar aun si el programador falla.
- Definir modo vencido de solo lectura: permitir ver equipos, fixture/tabla, actas e informes; bloquear con autorización de servidor creación, edición y eliminación. Al vencer el período de reactivación pagada, regresar al mismo modo de solo lectura. El administrador podrá conceder prestaciones extra. El cobro/revalidación se aplica a la organización, no a cada usuario integrante.
- Mostrar avisos de vencimiento/reactivación sin filtrar datos de otros organizadores; permitir al superadministrador el acceso global sin que la expiración de una cuenta propia afecte su administración.

### Fase 5 — Tarifas, liquidación y pagos

- Administrar una tarifa predeterminada de referencia de **S/ 5 por equipo en cada partido creado**, con tarifa negociada por organización/edición, moneda, vigencia y aprobador. Cada partido con dos equipos genera S/ 10; 6 partidos de una fecha con 12 equipos generan 12 × S/ 5 = S/ 60. Se cobra aunque el partido no llegue a disputarse; cancelarlo o retirarlo después no revierte el cargo salvo ajuste autorizado. El superadministrador puede modificar tarifa, aplicar cortesía o bonificación por donación/patrocinio y guardar motivo/auditoría.
- Antes de guardar cada partido, comprobar que la organización ya pagó/prepagó el importe requerido o que el superadministrador autorizó la excepción. El estimado puede calcularse con equipos inscritos y formato; registrar un partido consume el crédito correspondiente. Guardar una instantánea de conteo, tarifa y cálculo; cancelar o no jugar el partido no revierte el cargo salvo ajuste autorizado.
- Modelar saldo prepago/crédito por organización/edición, cargos por partidos y pagos separados: importe, parcialidades, saldo, vencimientos, método, referencia, estado y evidencia. Cada partido cuesta S/ 10 a tarifa base (dos equipos); solo se permite guardarlo si el saldo prepago cubre el importe. El saldo no usado permanece en la organización y puede utilizarse en otra edición; no se reembolsa automáticamente. La pasarela en línea procesa el pago total de la compra de saldo; pagos parciales/en fechas se registran y confirman por el administrador, que define hasta cuándo reactiva la organización. La tarifa y facturación corresponden a la organización, no a cada usuario integrante.
- Permitir cortesía, beca, donación o patrocinio como descuento/bonificación explícita con motivo y aprobador, en lugar de alterar el precio sin trazabilidad.
- Para pagos peruanos el organizador considera **Culqi y Mercado Pago**. Las páginas oficiales consultadas publican tarifas aproximadas distintas por producto/método y disponibilidad de fondos; Culqi publica, entre otras, 3,44% + US$0,20 para tarjeta nacional/Yape y 3,99% + US$0,20 para tarjeta internacional; Mercado Pago muestra cifras desde 3,29% + S/1 o 3,49% + S/1 según disponibilidad, pero su propia página de Link de pago tiene discrepancias entre secciones e indica posible IGV. Son referencias no contractuales: antes de elegir se solicitará cotización escrita que aclare método, IGV, fijo, cambio de moneda y liquidación. Fuentes: [tarifas CulqiOnline](https://ayuda.culqi.com/portal/es/kb/articles/cual-es-la-comision-de-culqionline), [Checkout Mercado Pago Perú](https://www.mercadopago.com.pe/herramientas-para-vender/check-out), [Link de pago Mercado Pago](https://www.mercadopago.com.pe/herramientas-para-vender/link-de-pago).
- **Recomendación preliminar para Perú:** evaluar primero Culqi y Mercado Pago con el ticket promedio y métodos (incluido Yape); no seleccionar por porcentaje anunciado solamente. Para tarifas negociadas y pagos locales/internacionales, solicitar cotización a dLocal o PayU Latam; no publican tarifas universales en las páginas consultadas. PayPal Perú facilita alcance internacional, pero su tarifa publicada para recepción comercial es 5,40% + cargo fijo y puede añadir margen de conversión, por lo que no parece la opción de menor comisión. Stripe no permite abrir una cuenta directamente con entidad peruana; sus precios estadounidenses no se deben tratar como precios disponibles para este negocio. Fuentes: [dLocal Perú](https://docs.dlocal.com/docs/peru), [PayU Perú](https://developers.payulatam.com/latam/en/docs/integrations/api-integration/payments-api-peru.html), [tarifas PayPal Perú](https://www.paypal.com/pe/business/paypal-business-fees), [países Stripe](https://stripe.com/global).
- Para cobros internacionales, aún falta elegir proveedor: pedir propuestas a dLocal y PayU y comparar contra PayPal; Adyen puede evaluarse si la empresa y su volumen califican. Confirmar elegibilidad contractual, tarjetas internacionales, moneda cobrada/liquidada, conversión, plazo y contracargos con cada proveedor.
- Toda integración deberá verificar firma/webhook, idempotencia, conciliación de pago y reembolsos. No guardar datos de tarjeta; conservar credenciales en configuración segura. La pasarela se habilitará para pagos totales; pagos parciales/en fechas se registrarán según el acuerdo y confirmación administrativa. La reactivación será manual por el administrador hasta una fecha definida. La pantalla comercial mostrará tarifa, descuentos, pagos y saldo.

### Fase 6 — Pruebas, despliegue y operación

- Tests de regresión para `PW00001 != 2` y tests de interfaz/servidor para `PW00001 = 2`: menú, formulario crear/editar, categoría deportiva forzada incluso con petición manipulada y rutas de categorías no accesibles a organizadores.
- Tests multiusuario con dos organizaciones y varios usuarios dentro de una organización: los usuarios de la misma organización ven recursos compartidos conforme a sus permisos; otra organización queda aislada; acceso por URL a evento/edición/equipo/partido ajeno devuelve `404`; las escrituras de IDs cruzados son rechazadas; el tenant deriva de la sesión y no del cliente; incluye API.
- Tests para cuenta nueva vacía, primer evento y flujo de creación; rol de registro no privilegiado; accesos permitidos al superadministrador.
- Tests de prueba vigente desde el alta, vencimiento, solo lectura, prestaciones concedidas, donación/gratuidad, cargos prepagados, pagos manuales totales/parciales, partido bloqueado sin saldo y reactivación con fecha de fin; probar límites y operaciones de pago repetidas.
- Migración de producción ensayada con copia restaurable: conteos antes/después, owner asignado, FKs/índices, unicidad de equipos y landing pública previa.
- Desplegar primero en entorno de prueba; revisar registro, cambio de modo, aislamiento, rutas, API y flujo completo de cobro antes de habilitar cuentas públicas.

## Criterios de aceptación

- Con `PW00001 = 2`, el módulo se presenta como Gestión Deportiva, muestra icono de pelota, no ofrece Categorías al organizador y los eventos creados quedan con categoría deportiva impuesta también en backend.
- Con otro valor, etiquetas, formularios, categorías y comportamiento de Eventos son iguales a los actuales.
- Una organización nueva no ve registros heredados ni datos de otras organizaciones; comienza sin registros y crea su primer evento. Los usuarios de la misma organización comparten registros según sus permisos. Manipular IDs o llamar rutas web/API no permite leer ni modificar recursos de otra organización.
- Antes de aceptar cada partido nuevo, se confirma cobertura del cargo de S/ 5 por equipo; un partido con dos equipos requiere S/ 10 a tarifa base aunque luego no se juegue.
- Superadministrador puede inspeccionar y administrar globalmente, cambiar la prueba, permisos/prestaciones, precios y pagos con auditoría.
- La prueba comienza al crear la cuenta. Al vencer, se rechazan en backend las operaciones no permitidas y se conservan las lecturas habilitadas. Crear partidos exige saldo prepago suficiente; un abono no reactiva por sí solo el acceso completo y el superadministrador fija la fecha de habilitación.
- El cobro es reproducible: tarifa aplicada, edición, partidos registrados, monto de cada partido, descuentos/bonificaciones, pagos y saldo quedan registrados sin alterar historiales pasados.
- Las migraciones preservan y asignan explícitamente los datos existentes; las pruebas anteriores del módulo y una suite de seguridad nueva pasan antes de publicar.

## Decisiones ya confirmadas

- Registro de organizadores: **solo cuando `PW00001 = 2`**.
- Duración de prueba: **configurable por el superadministrador**.
- Vencida la prueba o la reactivación pagada: **solo lectura** de equipos, fixture/tabla, actas e informes; se bloquean registros y ediciones, salvo prestaciones adicionales otorgadas por el superadministrador.
- La pasarela online es para pago total/compra de saldo; pagos en partes requieren acuerdo y confirmación administrativa. El pago total confirmado por pasarela activa automáticamente según la vigencia comprada. Un abono manual no reactiva por sí solo: el superadministrador confirma el acuerdo y establece la fecha de reactivación.
- Tarifa base: **S/ 5 por equipo en cada partido creado** (S/ 10 por partido con dos equipos), juegue o no; el pago debe estar cubierto antes de aceptar el registro.
- Un usuario organizador inicial por organización; los usuarios adicionales se habilitan por paquetes con cantidad y cargo único configurable, con posibilidad de cortesía del administrador. La tarifa por partido-equipo no cambia al sumar usuarios.
- Prueba: comienza al crear la cuenta, con duración configurable por el superadministrador.
- Acceso a recursos de otra organización: responde **404**.
- Datos existentes: asignados a una organización histórica administrada por el superadministrador.
- Pasarelas por evaluar: Culqi y Mercado Pago para Perú; la pasarela internacional se decidirá después de recibir cotizaciones y comparar costos totales.

## Decisiones pendientes para completar la especificación

1. ¿Qué plazo tendrá cada compra de saldo prepago antes de vencer? ¿Se puede reembolsar al cerrar definitivamente una organización?
2. La ampliación de usuarios será por paquetes de cantidad y precio únicos configurables, y puede bonificarse. Queda pendiente definir qué opciones/cantidades iniciales deben ofrecerse.
3. La pasarela internacional queda pendiente hasta recibir cotizaciones de dLocal/PayU y compararlas con PayPal. La entidad receptora es peruana y el volumen anual sigue por definir.

Las tarifas de pasarelas referidas arriba son precios públicos consultados para orientar la comparación, no una cotización garantizada. La aceptación final debe basarse en oferta escrita del proveedor y en el país/entidad legal que recibirá los fondos.
