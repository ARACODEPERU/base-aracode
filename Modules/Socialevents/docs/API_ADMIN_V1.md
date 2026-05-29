# API Admin Socialevents v1 (móvil)

Base: `{apiBaseUrl}/socialevents/v1/admin`  
Auth: `Authorization: Bearer {token}` + rol `admin` o `administrador`.

## Equipos (catálogo global)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/teams/catalog` | Lista todos los equipos |
| POST | `/teams` | Crear equipo (`name`, `short_name`, `ubigeo`, `ubigeo_description`, `manager_id`, `logo_path` base64) |

## Equipos por edición

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/editions/{editionId}/teams` | Equipos inscritos + standings |
| POST | `/editions/{editionId}/teams` | Inscribir `{ team_id }` |
| DELETE | `/editions/{editionId}/teams/{teamId}` | Quitar de la edición |
| GET | `/teams/{editionId}` | Lista simple (legacy, partidos) |

## Jugadores (admin, cualquier equipo de la edición)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/editions/{editionId}/teams/{teamId}/players` | Listar plantilla |
| POST | `/editions/{editionId}/teams/{teamId}/players/link` | Vincular persona `{ person_id }` |
| POST | `/editions/{editionId}/teams/{teamId}/players` | Crear persona + inscribir |
| PUT | `/editions/{editionId}/teams/{teamId}/players/{personId}` | Actualizar |
| POST | `.../players/{personId}/photo` | Foto base64 `{ image }` |
| DELETE | `.../players/{personId}` | Quitar de la plantilla |

## Personas

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/persons/search` | `{ document_type_id, number }` → `{ found, person }` |
| POST | `/persons` | Crear/actualizar persona antes de vincular |

## Utilidades

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/ubigeo` | Distritos para formulario de equipo |

Respuesta estándar: `{ success, message, data }`.
