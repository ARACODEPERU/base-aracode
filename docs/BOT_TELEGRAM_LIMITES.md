# Límites y anti-abuso de los bots de Telegram

## ¿Se puede saturar el servidor escribiendo mucho a un bot?

Sí. El webhook es público por necesidad (Telegram no envía sesión ni token de
Sanctum), así que cualquiera que encuentre el bot puede escribirle. Telegram
reenvía **cada** mensaje a nuestro servidor como un POST y espera respuesta; por
eso:

- un solo chat en bucle abre muchos procesos PHP a la vez;
- cada mensaje procesado hace consultas a la base de datos y, casi siempre, una
  o dos llamadas salientes a `api.telegram.org`;
- si Telegram tarda en responder, cada proceso queda ocupado hasta el timeout.
  Con el timeout anterior de 30 s, 20 mensajes simultáneos podían bloquear 20
  procesos durante medio minuto.

El endpoint en sí está protegido con `X-Telegram-Bot-Api-Secret-Token` (derivado
del token del bot), de modo que un tercero **no** puede fabricar updates: solo
Telegram puede entregarlos. El riesgo real es abusar del bot legítimo, no
falsificar el webhook.

## Defensas implementadas

Compartidas por el bot escolar (`api/academic/school/telegram/webhook`) y el bot
de Integrationhub (`api/integrationhub/telegram/webhook`), en
`App\Support\TelegramWebhookGuard`:

1. **Deduplicación por `update_id`** (10 minutos): si Telegram reintenta el mismo
   update, no se procesa dos veces.
2. **Límite por chat**: 20 updates por minuto (configurable). El exceso se
   descarta.
3. **Límite global por bot**: 600 updates por minuto (configurable). Protege al
   servidor aunque el abuso venga de muchos chats a la vez.
4. **Respuesta 200 a lo descartado**: devolver error haría que Telegram reintente
   y el problema empeoraría. Tampoco se avisa al chat descartado, porque ese
   aviso consumiría otra llamada saliente y amplificaría el ataque.
5. **Un solo log por chat y minuto**: una inundación no llena el disco con una
   entrada por mensaje.
6. **Timeouts de salida cortos**: 10 s de espera y 5 s para conectar con Telegram
   (`ACA_SCHOOL_TELEGRAM_TIMEOUT`, `ACA_SCHOOL_TELEGRAM_CONNECT_TIMEOUT`).
7. **`max_connections` en `setWebhook`**: Telegram abre como máximo 10 conexiones
   simultáneas contra el webhook (antes 40 por defecto).
8. **Rate limit global de la API**: `RouteServiceProvider` ya limita el grupo
   `api`; el webhook queda fuera porque Telegram no puede autenticarse con
   sesión, por eso el freno vive dentro del propio webhook.

Variables de entorno (ambas tienen valores por defecto, no hace falta definirlas):

| Variable | Bot escolar | Bot Integrationhub | Defecto |
| --- | --- | --- | --- |
| Updates por chat y minuto | `ACA_SCHOOL_TELEGRAM_WEBHOOK_PER_CHAT_PER_MINUTE` | `TELEGRAM_WEBHOOK_PER_CHAT_PER_MINUTE` | 20 |
| Updates por bot y minuto | `ACA_SCHOOL_TELEGRAM_WEBHOOK_GLOBAL_PER_MINUTE` | `TELEGRAM_WEBHOOK_GLOBAL_PER_MINUTE` | 600 |
| Conexiones simultáneas | `ACA_SCHOOL_TELEGRAM_WEBHOOK_MAX_CONNECTIONS` | `TELEGRAM_WEBHOOK_MAX_CONNECTIONS` | 10 |

Los límites viven en la caché (`CACHE_DRIVER`). Con Redis o una caché
compartida la cuenta es exacta entre servidores; con `file` y varios servidores
cada uno lleva su propia cuenta.

> `max_connections` solo se aplica **al volver a registrar el webhook**: hay que
> pulsar "Registrar webhook" en la pantalla de Telegram del colegio o ejecutar
> `php artisan integrationhub:telegram-set-webhook` después de desplegar.

## Capas que deben vivir fuera de PHP

El código frena el abuso razonable, pero una inundación volumétrica debe
detenerse antes de arrancar Laravel. El sitio de producción está detrás de
**Cloudflare**:

- **Rate limiting por IP** sobre las dos rutas de webhook. Ojo: todo el tráfico
  legítimo llega desde los rangos de Telegram, así que el umbral debe ser alto
  (por ejemplo 3 000 peticiones por minuto por IP) para cortar una inundación sin
  bloquear a los padres. Un umbral bajo por IP acabaría frenando al bot completo.
- **Regla WAF**: bloquear las peticiones a esas rutas que no traigan la cabecera
  `X-Telegram-Bot-Api-Secret-Token`. No hace falta comparar el valor en
  Cloudflare (bastaría con exponer el secreto allí); con exigir su presencia los
  escáneres nunca llegan a PHP.
- Si el ataque es dirigido y sostenido, "Under Attack Mode" o un desafío
  gestionado sobre la ruta (nunca sobre todo el sitio) mientras dura.

Si algún día el sitio sale de Cloudflare y queda en nginx:

```nginx
# Define el límite en el bloque http
limit_req_zone $binary_remote_addr zone=telegram_webhook:10m rate=120r/m;

location ~ ^/api/(academic/school|integrationhub)/telegram/webhook$ {
    limit_req zone=telegram_webhook burst=120 nodelay;
    limit_req_status 429;
    try_files $uri /index.php?$query_string;
}
```

## Límites de esta solución

- No evita un ataque volumétrico (DDoS): eso es trabajo del CDN o del firewall.
- No evita que alguien cree muchos chats distintos; el límite global lo frena,
  pero un atacante con muchos números puede seguir consumiendo cuota hasta el
  techo. Si eso ocurre, hay que bloquear chats o desactivar el bot temporalmente
  desde la configuración del colegio.
- Los mensajes descartados no se recuperan: es preferible perder un mensaje bajo
  ataque a tumbar el sistema para todos.
