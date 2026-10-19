# Alertas de error por Telegram con contexto completo

## Qué hace

Cuando la aplicación registra un error que alcanza el nivel configurado, se avisa
por la integración `Telegram_bot` a los `chat_id` activos de **Seguridad →
Alertas**. El aviso lleva ahora:

- nivel, mensaje y excepción con archivo y línea;
- resumen de la traza (configurable: `security.alerts.trace_lines`, 10 por defecto);
- entorno, método y ruta, usuario, IP, navegador y repeticiones;
- un enlace **“Ver el contexto completo del error”** a la pantalla
  **Seguridad → Errores** (módulo Security).

## Interruptores de avisos

En **Seguridad → Alertas** hay un interruptor general y uno por tipo de aviso:

- **Avisos activos (general)** (`enabled`) — si se apaga, no sale ningún aviso por
  Telegram, sin importar los tipos activados.
- **Alertas de errores** (`errors_enabled`) — los fallos registrados en los logs.
- **Mensajes de contacto** (`contacts_enabled`) — el formulario del sitio.

Los interruptores por tipo permiten silenciar unos avisos sin perder los otros y
sin borrar destinatarios: al reactivarlos, los mismos `chat_id` vuelven a
recibirlos. Agregar un tipo nuevo es agregar su columna `{clave}_enabled` en una
migración y una entrada en `SecurityAlertSetting::topics()`; el interruptor en
pantalla, el guardado y el filtrado salen de ese registro. El botón de prueba
sigue enviando aunque los interruptores estén apagados, porque es una acción
explícita.

## Dónde vive el contexto

Cada error avisado se guarda completo en la tabla `security_error_logs`
(`Modules/Security/Entities/SecurityErrorLog`): traza entera, contexto del log,
datos de la petición (query, input y cabeceras básicas) y los datos de
ubicación (ruta, método, usuario, IP, navegador).

Reglas que se respetan al guardar:

- Los campos sensibles (`password`, `token`, `secret`, `authorization`,
  `cookie`, …) se guardan como `*** (oculto)`.
- Un error repetido **no crea una fila nueva durante 24 h**
  (`security.alerts.merge_minutes`): se le suma `occurrences` y se actualiza
  `last_seen_at`. El aviso sí se repite, pero como mucho una vez cada
  `cooldown_minutes` (pantalla Alertas) y con el total acumulado.
- Nunca se lanza una excepción al guardar: si la base falla (puede ser el propio
  error), la alerta sale igual, sin enlace.

## Mensajes del formulario de contacto

El mismo bot y los mismos destinatarios activos reciben también un aviso cuando
alguien envía el formulario de contacto del sitio (`/contacto` y el modal
«Solicitar Asesoría»). El mensaje lleva nombre, email, teléfono, empresa,
servicio, fecha y el texto de la consulta.

- El texto de la persona se escapa: una etiqueta escrita en el mensaje se ve
  literal en Telegram y no rompe el HTML del aviso.
- Solo se envía cuando el envío pasó el captcha, el campo trampa y el límite de
  envíos por IP; un intento descartado no llega al chat.
- Se rige por los interruptores de **Seguridad → Alertas**: el general y el de
  **Mensajes de contacto** (`contacts_enabled`). No depende del nivel mínimo ni
  del interruptor de errores, así que apagar las alertas de error no silencia los
  contactos (ni al revés).
- Se encola (`SendContactMessageTelegram`). Si Telegram falla, el mensaje ya
  quedó guardado en `contact_messages` y el correo al admin ya se encoló.
- Sin destinatarios activos no se envía nada y el formulario responde igual.

## Fallos al usar una integración (Integrationhub)

Cuando una llamada a un endpoint de **Integrationhub** falla, el error se avisa
por este mismo canal (nivel `ERROR`, fuente `integrationhub_endpoint`) y queda en
el Historial del módulo. El aviso incluye el nombre del endpoint y de la
integración, el estado HTTP cuando lo hubo y la respuesta del servicio externo;
los datos que cambian en cada intento (id de lote, mensaje exacto del tercero)
viajan en el contexto del error, así el mismo fallo repetido conserva su huella y
no llena el chat.

Lo importante para el sistema: **Integrationhub nunca detiene a quien lo llama**.
Los jobs, webhooks y pantallas reciben una respuesta de error en vez de una
excepción, así que el flujo sigue (incluido un endpoint mal configurado o un
mapeo roto). Y si el aviso en sí falla —cola caída, base de datos sin responder—
tampoco se propaga: se registra a nivel `warning` con la marca interna
`security_alert`, sin generar una alerta por el fallo de la alerta.

## Fallos de comandos programados

El planificador, por su cuenta, registra “Scheduled command [...] failed with exit
code [1]”, que no dice nada del motivo. Los comandos de este repositorio avisan
del fallo real marcando el contexto con `ErrorAlertService::SCHEDULED_COMMAND_MARKER`
(por ejemplo `health:sync-google-calendar`, que además agrega la sugerencia para
corregirlo); cuando eso ocurre, esa línea genérica no se avisa y el chat recibe un
solo mensaje: el que sirve.

## Limpieza (para no llenar el disco)

Pantalla **Seguridad → Errores** (`security_errors`):

- `conf_errores` — ver el listado y el detalle.
- `conf_errores_eliminar` — borrar (uno, todos o los vencidos). Se concede solo
  a los roles de administración (`admin`, `Administrador`).

Borrado automático: el comando `security:prune-error-logs` corre todos los días a
las 02:00 (America/Lima) y elimina lo que supere los días de retención
configurados en **Seguridad → Alertas** (`retention_days`, 30 por defecto; `0` =
no borrar nunca; además hay un interruptor `purge_enabled`).

Manualmente:

```bash
php artisan security:prune-error-logs              # usa la retención guardada
php artisan security:prune-error-logs --days=7     # fuerza 7 días
php artisan security:prune-error-logs --all        # borra todo el contexto
php artisan security:prune-error-logs --logs       # también los .log rotados viejos
```
