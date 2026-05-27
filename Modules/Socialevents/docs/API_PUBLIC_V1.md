# API pública Socialevents v1

Base URL: `{APP_URL}/api/socialevents/v1`

Todas las respuestas exitosas usan el envoltorio:

```json
{
  "success": true,
  "message": "…",
  "data": { }
}
```

Errores:

```json
{
  "success": false,
  "message": "…",
  "data": null
}
```

## Regla `mobile_enabled`

Los endpoints que reciben `editionId` validan que la edición tenga `mobile_enabled = true`. Si no:

- HTTP **403**
- La landing web (`/torneos/{slug}`) **no** usa esta regla; solo exige `landing_published`.

## Endpoint agregador (recomendado para app móvil)

### `GET /edition/{editionId}/public`

Devuelve en una sola respuesta: edición, evento, enlaces, tabla, rankings de equipos, top jugadores/arqueros, próximos partidos y últimos resultados.

**Ejemplo:** `GET /api/socialevents/v1/edition/1/public`

Campos principales en `data`:

| Clave | Descripción |
|--------|-------------|
| `edition` | Metadatos de la edición (contacto, branding, flags) |
| `event` | Evento padre |
| `links.landing_web` | URL absoluta de la landing |
| `links.api_public` | URL de este endpoint |
| `standings` | Tabla (formato `StandingsData` en Flutter) |
| `rankings` | Misma tabla con clave `rank` (formato `RankingData`) |
| `players_ranking` | Top jugadores de campo |
| `goalkeepers_ranking` | Top arqueros |
| `upcoming_matches` | Próximos partidos |
| `recent_results` | Última fecha jugada |
| `phase_labels` | Mapa fase → etiqueta en español |

## Endpoints granulares (compatibles con aracode-movil)

| Método | Ruta | Uso |
|--------|------|-----|
| GET | `/events` | Listado de eventos con edición reciente |
| GET | `/event/current` | Evento + edición + `rankings` (equipos) |
| GET | `/event/{id}` | Evento por id |
| GET | `/edition/current` | Solo edición activa |
| GET | `/edition/{id}/standings` | Tabla de posiciones |
| GET | `/edition/{id}/matches/upcoming` | Próximos partidos |
| GET | `/edition/{id}/matches/results` | Resultados última fecha |
| GET | `/edition/{id}/matches` | Listado paginado (`?page`, filtro en path) |
| GET | `/edition/{id}/stats/players/{filter}` | `goals`, `assists`, `mvp`, `saves`, `cleansheet` |

### Tabla de posiciones (`standings`)

Cada ítem en `data`:

```json
{
  "position": 1,
  "team_id": 10,
  "team_name": "Equipo A",
  "team_short_name": "EQA",
  "team_logo": "https://tenant.test/storage/…",
  "points": 9,
  "matches_played": 3,
  "matches_won": 3,
  "matches_drawn": 0,
  "matches_lost": 0,
  "goals_for": 8,
  "goals_against": 2,
  "goal_difference": 6
}
```

### Rankings en `event/current` (`rankings`)

Igual que la tabla pero con `rank` en lugar de `position` y campo `is_champion`.

## Landing pública (web)

- URL: `/torneos/{public_slug}` (o id numérico con redirección 301 al slug).
- Requiere `landing_published = true`.
- Cache de vista: 120 s por defecto (`SOCIALEVENTS_LANDING_CACHE_TTL`). Se invalida al guardar edición, partidos o recalcular tabla.

## Configuración en admin

En **Ediciones → Editar**, sección **Publicación web y app móvil**:

- `contact_name`, `contact_phone`, `contact_whatsapp`
- `public_slug` → `/torneos/{slug}`
- `landing_published`, `mobile_enabled`
- `branding.accent_color`, imagen hero

## Orden de tabla y rankings de jugadores

- **Equipos:** `rank` en BD; si falta, desempate por puntos → diferencia de goles → goles a favor.
- **Jugadores / arqueros:** pesos en `config/socialevents.php` → `rankings.players` y `rankings.goalkeepers`.

## Rutas protegidas (Sanctum)

Prefijo `/api/socialevents/v1` con `auth:sanctum`: equipo del delegado, jugadores, admin de partidos (`PUT admin/matches/...`). No documentadas aquí en detalle; ver `Routes/api.php`.
