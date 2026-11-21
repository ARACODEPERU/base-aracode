# Conectar agentes de IA al servidor MCP del Académico

El módulo Académico expone un servidor MCP para que un agente cree cursos en borrador con el mismo modelo de datos y las mismas validaciones que la web. Este documento reúne lo necesario para conectar **Claude Code**, **Claude Desktop**, **claude.ai** y **Freebuff**.

- Endpoint: `http://base_aracode.test/mcp/academic` (en producción, el dominio real del ERP). En Freebuff Desktop usar `http://localhost/base_aracode/public/mcp/academic`: `*.test` solo existe en el archivo `hosts` de Windows y su cliente MCP no lo encuentra.
- Ruta declarada en: `routes/ai.php` (`Mcp::web('/mcp/academic', AcademicServer::class)`).
- Servidor: `Modules/Academic/Mcp/Servers/AcademicServer.php` — 47 herramientas, 2 recursos y 1 prompt.
- Autenticación: token Bearer de Sanctum (`auth:sanctum`) con límite de peticiones `throttle:mcp`.
- Todo curso creado por un agente nace en **borrador** (`status = false`). Activarlo sigue siendo una acción humana en el ERP.

## 1. Requisitos

| Requisito | Detalle |
| --- | --- |
| Laragon encendido | Apache + MySQL arriba; `base_aracode.test` está en el `hosts` apuntando a `127.0.0.1` |
| PHP | 8.5 (`C:\laragon\bin\php\php-8.5.12-1-Win32-vs17-x64\php.exe`); desde Git Bash no está en el `PATH` |
| Usuario | Con permisos del Académico: `aca_cursos_listado`, `aca_cursos_nuevo`, `aca_cursos_editar` |
| Node (solo Claude Desktop) | Necesario para el puente `npx -y mcp-remote` |

## 2. Crear el token de cada cliente

Un token por cliente permite revocar uno sin cortar los demás y dejar rastro en la auditoría. El valor se muestra **una sola vez**.

```bash
php artisan mcp:token admin@gmail.com --abilities=academic:read,academic:write --name=Claude-Code
php artisan mcp:token admin@gmail.com --abilities=academic:read,academic:write --name=Claude-Desktop
php artisan mcp:token admin@gmail.com --abilities=academic:read,academic:write --name=Freebuff-Desktop
```

El comando recibe el **correo**, no el id del usuario. Salida:

```
Token creado para admin@gmail.com con abilities: academic:read, academic:write

1|Xk3...token...9fQ

Guárdalo ahora: no se vuelve a mostrar. Úsalo como "Authorization: Bearer <token>".
```

### Abilities válidas

| Ability | Qué permite | Combinación obligatoria |
| --- | --- | --- |
| `academic:read` | Consultar cursos, alumnos, docentes, categorías, modalidades | — |
| `academic:write` | Crear y editar cursos **en borrador**, módulos, clases, exámenes, landing, brochure | — |
| `academic:write_active` | Editar contenido de cursos ya activos con confirmación | Requiere `academic:read` |
| `academic:autonomous` | Saltarse la confirmación por lote (permiso absoluto) | Requiere `academic:write` |

Ejemplos de combinaciones:

```bash
# Solo lectura (revisar antes de dar escritura)
php artisan mcp:token admin@gmail.com --abilities=academic:read --name=Solo-Lectura

# Recomendado: lectura + borradores
php artisan mcp:token admin@gmail.com --abilities=academic:read,academic:write --name=Claude-Code

# Con edición de cursos activos
php artisan mcp:token admin@gmail.com --abilities=academic:read,academic:write,academic:write_active --name=Editor-Activos

# Permiso absoluto para un agente de confianza
php artisan mcp:token admin@gmail.com --abilities=academic:read,academic:write,academic:autonomous --name=Agente-Autonomo
```

El usuario del token **también** necesita los permisos Spatie del ERP. El token no los sustituye: la herramienta comprueba ability y permiso.

## 3. Verificar que el servidor responde

```bash
TOKEN="1|tu-token"

# ¿Vive el servidor? (401 es correcto sin token; 200 con token)
curl -s -X POST http://base_aracode.test/mcp/academic \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"prueba","version":"1"}}}'

# Probarlo con una herramienta real
curl -s -X POST http://base_aracode.test/mcp/academic \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"academic_get_permissions","arguments":{}}}'
```

`academic_get_permissions` devuelve el modo de trabajo del agente: `confirmar` cuando no hay permiso absoluto, `autonomo` cuando el token tiene `academic:autonomous`.

## 4. Claude Code

Claude Code habla HTTP directamente, sin puente. El flag `--header` va **después de la URL**; si va antes del nombre, el comando falla con `missing required argument 'name'`.

```bash
claude mcp add --transport http --scope user academic-aracode \
  http://base_aracode.test/mcp/academic \
  --header "Authorization: Bearer 1|tu-token"
```

- `--scope user` → disponible en todos los proyectos (`~/.claude.json`).
- `--scope project` → escribe `.mcp.json` en la raíz del proyecto, compartible con el equipo.
- Sin `--scope`, el valor por defecto es `local` (solo el proyecto actual).

Comprobar la conexión:

```bash
claude mcp list
# academic-aracode: http://base_aracode.test/mcp/academic (HTTP) - ✔ Connected
```

Equivalente en JSON para `.mcp.json` (el `type` es obligatorio: sin él, Claude Code lo interpreta como servidor stdio y lo descarta):

```json
{
  "mcpServers": {
    "academic-aracode": {
      "type": "http",
      "url": "http://base_aracode.test/mcp/academic",
      "headers": {
        "Authorization": "Bearer 1|tu-token"
      }
    }
  }
}
```

Con `--scope project`, ese `.mcp.json` no debe llevar el token en un repositorio compartido: usa una variable de entorno y deja el valor fuera de Git.

## 5. Claude Desktop

`claude_desktop_config.json` solo arranca **programas locales**; no acepta `url`. Los servidores remotos entran por conector (UI) y, para un servidor en tu equipo, por el puente `mcp-remote`.

### 5.1 Servidor local: puente `mcp-remote`

Archivo (Windows): `%APPDATA%\Claude\claude_desktop_config.json`. Se abre desde Claude con `Ctrl+,` → Developer → Edit Config.

```json
{
  "mcpServers": {
    "academic-aracode": {
      "command": "npx",
      "args": [
        "-y",
        "mcp-remote",
        "http://base_aracode.test/mcp/academic",
        "--allow-http",
        "--header",
        "Authorization:${ACADEMIC_MCP_AUTH}"
      ],
      "env": {
        "ACADEMIC_MCP_AUTH": "Bearer 1|tu-token"
      }
    }
  }
}
```

**Dos avisos:**

- `--allow-http` es obligatorio: `mcp-remote` solo acepta `http://` para `localhost`/`127.0.0.1` y responde `Non-HTTPS URLs are only allowed for localhost or when --allow-http flag is provided` con cualquier otro dominio, incluido `base_aracode.test`. En producción con HTTPS no hace falta.
- **Importante en Windows:** Claude Desktop no escapa los espacios dentro de `args` al invocar `npx` y rompe la cabecera. Por eso la cabecera va sin espacios (`Authorization:${ACADEMIC_MCP_AUTH}`) y el espacio real vive en `env`, que sí lo admite. Si copias el ejemplo de la documentación de `mcp-remote` con `"Authorization: Bearer ..."` directo en `args`, el servidor no recibe la cabecera y responde `401`.

Para no dejar el token en los argumentos del proceso (visible en el listado de procesos), usa un archivo de cabeceras:

```json
{
  "mcpServers": {
    "academic-aracode": {
      "command": "npx",
      "args": [
        "-y",
        "mcp-remote",
        "http://base_aracode.test/mcp/academic",
        "--allow-http",
        "--header-file",
        "C:\\laragon\\www\\base_aracode\\mcp\\headers.local.txt"
      ]
    }
  }
}
```

`mcp/headers.local.txt`, una cabecera por línea:

```
Authorization: Bearer 1|tu-token
```

Después de guardar: cerrar Claude por completo (icono de la bandeja del sistema, no solo la ventana) y volver a abrirlo. El servidor aparece en el selector de conectores del cuadro de mensaje.

### 5.2 Servidor público: conector con OAuth

Si el ERP está en un dominio con HTTPS, no hace falta token a mano ni `mcp-remote`: se agrega como **conector personalizado** en claude.ai o Claude Desktop (Settings → Connectors → Add custom connector) con la URL pública:

```
https://tu-dominio.com/mcp/academic
```

El proyecto ya implementa OAuth 2.1 con PKCE, que es lo que el conector espera encontrar:

| Ruta | Para qué |
| --- | --- |
| `GET /.well-known/oauth-protected-resource/mcp/academic` | Metadatos del recurso (RFC 9728) |
| `GET /.well-known/oauth-authorization-server` | Metadatos del servidor de autorización (RFC 8414) |
| `POST /oauth/register` | Registro dinámico de clientes públicos (RFC 7591) |
| `GET /oauth/authorize` | Pantalla de consentimiento del ERP (exige sesión y permisos de cursos) |
| `POST /oauth/token` | Canje del código por token Sanctum de 90 días |

En la pantalla de consentimiento la persona elige los scopes (`academic:read`, `academic:write`, `academic:write_active`) y si otorga permiso absoluto. Los conectores **no** alcanzan `localhost` ni `*.test`: esa vía exige un dominio público.

## 6. Freebuff

Freebuff acepta servidores remotos HTTP con cabeceras, en el formato estándar `mcpServers`.

### 6.1 Solo este proyecto

Archivo `.agents/mcp.json` en la raíz del repositorio:

```json
{
  "mcpServers": {
    "academic-aracode": {
      "type": "http",
      "url": "http://localhost/base_aracode/public/mcp/academic",
      "headers": {
        "Authorization": "Bearer 1|tu-token"
      }
    }
  }
}
```

**La URL va con `localhost`, no con `base_aracode.test`.** El dominio de Laragon solo se resuelve por el archivo `hosts` de Windows (`nslookup base_aracode.test` responde "Non-existent domain" aunque `ping` sí acierte, porque `ping` lee `hosts` y el resolutor del cliente MCP no). Freebuff contesta `Couldn't connect to that MCP server: its address couldn't be found. Check the URL.` y `localhost` se resuelve en cualquier equipo sin depender de `hosts`. El segmento `/base_aracode/public` es necesario porque el host por defecto de Apache apunta a `C:/laragon/www`, no al `public/` del proyecto; el virtual host de `base_aracode.test` no se aplica a `localhost`.

La primera vez que Freebuff encuentra `.agents/`, pregunta si debe cargar y ejecutar esos servidores. Hasta que se aprueba, las herramientas no aparecen en el chat.

### 6.2 En todos los proyectos

Mismo bloque en `~/.agents/mcp.json`. Ojo: el archivo global **pisa** al del proyecto cuando define un servidor con el mismo nombre, y es la causa habitual de que un servidor funcione en un repositorio y no en otro.

### 6.3 Desde la interfaz

Settings → **MCP** → **Add server** y pegar el JSON completo. El servidor queda como *Needs approval*: se revisa el comando, se ejecuta y se marcan las herramientas permitidas. Al aprobarlo aparece como *Connected*.

Si al añadirlo responde `Couldn't connect to that MCP server: its address couldn't be found. Check the URL.`, la URL usa `*.test` o un nombre que solo vive en `hosts`: cambiarla por `http://localhost/base_aracode/public/mcp/academic`. Freebuff guarda las aprobaciones en `~/.freebuff/mcp.json` y solo relee la configuración al arrancar, así que hay que reiniciarlo desde el icono de la bandeja del sistema, no solo cerrar la ventana.

## 7. Dónde va cada cosa

| Cliente | Archivo o comando | Transporte |
| --- | --- | --- |
| Claude Code | `claude mcp add --transport http ...` o `.mcp.json` | HTTP |
| Claude Desktop (local) | `%APPDATA%\Claude\claude_desktop_config.json` con `mcp-remote` | stdio → HTTP |
| Claude Desktop / claude.ai (público) | Conector personalizado con la URL https | HTTP + OAuth |
| Freebuff (proyecto) | `.agents/mcp.json` | HTTP |
| Freebuff (global) | `~/.agents/mcp.json` o Settings → MCP | HTTP |
| App móvil / integraciones | Token Sanctum en `Authorization: Bearer` | HTTP |

## 8. Errores frecuentes

| Síntoma | Causa | Solución |
| --- | --- | --- |
| `401 {"message":"Unauthenticated."}` | Falta la cabecera o el token es de otro entorno | Revisar `Authorization: Bearer <token>` y que el token no sea de producción |
| `400 Bad Request` con HTML de Apache | Cabecera mal formada por espacios en `args` (Claude Desktop en Windows) | Usar `Authorization:${VAR}` + `env`, o `--header-file` |
| `Non-HTTPS URLs are only allowed for localhost or when --allow-http flag is provided` | El puente `mcp-remote` rechaza `http://` fuera de localhost | Añadir `--allow-http` a los `args` |
| `El token no tiene la ability [academic:write]` | Se generó solo con lectura | Volver a ejecutar `mcp:token` con la ability correcta |
| `El usuario no tiene el permiso [aca_cursos_listado]` | El token es válido pero al usuario le falta el permiso Spatie | Asignar el permiso en el ERP |
| `El curso ya está activo: desde MCP solo se modifican borradores` | Se intentó editar un curso activo sin `academic:write_active` | Generar token con esa ability o editar desde el ERP |
| `429 Too Many Requests` | Límite `throttle:mcp` | Bajar el ritmo; el agente encadena muchas llamadas por curso |
| `Couldn't connect to that MCP server: its address couldn't be found. Check the URL.` | La URL usa `base_aracode.test`, que solo existe en el `hosts` de Windows y el cliente MCP no lo resuelve | Cambiar la URL a `http://localhost/base_aracode/public/mcp/academic` |
| El cliente no conecta y `curl` sí | El cliente corre en otro equipo o en la nube y `base_aracode.test` no resuelve allí | Usar la URL de `localhost` o un dominio público |
| No aparecen las herramientas en Freebuff | El servidor no fue aprobado todavía | Settings → MCP, revisar y aprobar |

## 9. Seguridad, auditoría y rotación

- Los tokens se guardan en `mcp/*.local.*` y `.agents/mcp.json`; ambas rutas están en `.gitignore`. Nunca los subas al repositorio ni los pegues en un chat.
- Un token por cliente y con nombre explícito (`Claude-Code`, `Claude-Desktop`, `Freebuff-Desktop`): así se revoca uno solo.
- Revocar: eliminar la fila correspondiente en `personal_access_tokens`. Regenerar: volver a ejecutar `mcp:token` con otro `--name`.
- Los tokens de OAuth duran 90 días y los códigos de autorización 10 minutos; `academic:prune-mcp-data` limpia los caducados por cron.
- Cada llamada queda en `mcp_audit_logs` (usuario, herramienta, resultado, duración). Se consulta en la pantalla `academic/mcp-audit` del ERP.
- El agente no puede publicar landings ni borrar sin confirmación explícita, y todo curso nuevo queda en borrador.
