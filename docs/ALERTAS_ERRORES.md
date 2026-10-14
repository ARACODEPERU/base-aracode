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

## Dónde vive el contexto

Cada error avisado se guarda completo en la tabla `security_error_logs`
(`Modules/Security/Entities/SecurityErrorLog`): traza entera, contexto del log,
datos de la petición (query, input y cabeceras básicas) y los datos de
ubicación (ruta, método, usuario, IP, navegador).

Reglas que se respetan al guardar:

- Los campos sensibles (`password`, `token`, `secret`, `authorization`,
  `cookie`, …) se guardan como `*** (oculto)`.
- Un error repetido dentro de la ventana de anti-duplicados **no crea una fila
  nueva**: se le suma `occurrences` y se actualiza `last_seen_at`.
- Nunca se lanza una excepción al guardar: si la base falla (puede ser el propio
  error), la alerta sale igual, sin enlace.

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
