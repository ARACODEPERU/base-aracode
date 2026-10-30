# Plan: Diplomas y certificados para torneos (módulo Torneos)

**Módulo destino:** `Modules/Socialevents` (Torneos / Gestión deportiva)
**Referencia funcional:** `Modules/Academic` → Certificados (editor Konva + vista previa + descarga PNG/PDF)
**Regla principal:** no se reutiliza ningún archivo, tabla ni modelo del módulo académico. Se crean archivos y tablas propias con prefijo `socialevent_`.

---

## 0. Decisiones confirmadas por el usuario

| Tema | Decisión |
|---|---|
| Vigencia de modelos | **Un modelo activo por tipo de premio y por edición**. |
| Caras del diploma | **Sólo anverso**. No hay reverso ni reverso opcional. |
| Diploma de equipo | **Uno solo por equipo**, mostrando la **lista de jugadores** que participaron. Los parámetros de esa lista son configurables (fuente, tamaño, columnas, orden, etc.), igual que los parámetros configurables del académico. |
| Tamaño del lienzo | **Lo define la imagen de fondo subida** (ancho y alto reales). No hay A4 fijo. |
| Página de logros (QR) | **Se implementa después**. Esta entrega es sólo certificados y diplomas. El QR queda como elemento del diseño apuntando por ahora a la landing del torneo. |
| Visibilidad futura de la página de logros | **Siempre visible**, sin depender de `landing_published`. |
| Datos en la página de logros | Sólo **nombre completo**, equipo, camiseta y posición. **Sin DNI ni datos personales**. **Sin montos de dinero**: sólo el detalle disciplinario (tarjeta y partido). |
| Alcance de esta entrega | **Fases 1 a 5** (datos, premios, plantillas, editor Konva, premiados, emisión e impresión, integración). |

---

## 1. Qué existe hoy (auditoría)

### 1.1 En Académico (guía, no se toca)

| Pieza | Archivo | Qué aporta |
|---|---|---|
| Editor | `Modules/Academic/Resources/assets/js/Pages/Certificates/Partials/EditForm.vue` (2516 líneas) | `v-stage` / `v-layer` / `v-transformer` de `vue-konva`, panel por bloques, guardado por `action_type` contra `aca_certificate_update_info`. |
| Vista previa | `Modules/Academic/Resources/assets/js/Pages/Students/Partials/CertificatePreviewModal.vue` | Render de sólo lectura desde un payload del servidor (`sides[]`), descarga PNG (`toDataURL`) y PDF (`jsPDF`). |
| Payload | `AcaCertificateController::certificateSidePayload()` | Estructura `{key, width, height, base_image, texts[], contents[], qr{}}` ya resuelta. |
| Generación server-side | `Modules/Academic/Operations/CertificateImage.php` | Render con Intervention Image. **No se usa**: mantendría dos motores de diseño. |
| Fuentes | `public/fonts/*.ttf` | `Poppins-Light.ttf`, `Pacifico-Regular.ttf`, `Intro-Headr.ttf`, `OLDENGL.TTF`, `PlaywriteIN-Regular.ttf`. |

Dependencias ya presentes en `package.json`: `konva ^10.2.3`, `vue-konva ^3.4.0`, `jspdf ^2.5.1`, `qrcode ^1.5.4`. **No se agrega ninguna dependencia nueva.**

### 1.2 En Torneos (datos que se reutilizan)

| Pieza | Archivo | Uso |
|---|---|---|
| Edición | `Entities/EventEdition.php` | Dueño de diplomas y premiados. `prize_details` (JSON) ya describe premios por puesto. |
| Equipos / escudos | `EventEditionTeam.php`, `EventTeam.php` | Nombre y logo del equipo premiado. |
| Plantilla | `EventEditionTeamPlayer.php` | `edition_id`, `team_id`, `person_id`, `jersey_number`, `position` → lista del plantel. |
| Rankings y detalle | `Services/TournamentRankingsService.php` | `topPlayersPayload()` (MVP), `topScorersPayload()`, `topGoalkeepersPayload()` y **`playerDetail($editionId, $playerId, $category)`** — ya entrega el detalle partido a partido que necesita la página de logros. |
| Posiciones | `Services/PositionTableService.php` | `getStandings($editionId)` → campeón, subcampeón, tercero. |
| Estadísticas por jugador | `Entities/EventEditionMatchPlayerStat.php` | `goals`, `assists`, `saves`, `is_mvp`, `clean_sheet`, `minutes_played`. |
| Sanciones / suspensiones | `EventEditionMatchSanction`, `EventEditionPlayerSuspension`, `EventEditionPlayerExclusion` | Tarjetas, multas, suspensiones para la página de logros. |
| Alcance multi-organización | `Services/SportsOrganizationService.php` | `assertCanRead/Write`, `assertOwnedEdition`. |
| Imágenes | `Support/SavesBase64Image.php`, `TournamentMedia.php` | Subida y URLs de fondos/escudos. |
| Landing pública | `TournamentLandingController`, `Resources/views/torneos/landing.blade.php`, `Resources/assets/sass/torneos-landing.scss` | Estilo y patrón para la nueva página pública de logros (Blade, sin Inertia). |
| Permisos | `Database/Seeders/PermissionsModuleSeeder.php` | Se agregan permisos nuevos (idempotente con `firstOrCreate`). |
| Navegación de la edición | `Pages/Editions/List.vue`, `Pages/Editions/Edit.vue` | Punto de entrada del módulo. |
| Tema visual | `sportsMode`, clases `sports-*` (azul noche `#0b1f2b`, lima `#c8f169`) | El editor y las listas deben verse como Torneos. |

---

## 2. Alcance funcional

1. **Diseñador de diplomas (Konva, en el navegador)**: se sube la imagen de fondo, se colocan textos con variables, escudo, QR, marcos y, en el diploma de equipo, la **lista de plantel configurable**. Se guarda el diseño.
2. **Varios modelos por edición**: un modelo por tipo de premio; **uno vigente por `(edición, premio)`**.
3. **Premiados de la edición**: jugadores/equipos premiados, con sugerencia desde rankings y tabla de posiciones, y edición manual.
4. **Diploma de equipo con plantel**: un solo diploma por equipo; el elemento "Plantel" lista a los jugadores participantes con parámetros configurables (fuente, tamaño, color, columnas, orden, mostrar camiseta/posición, separador).
5. **Impresión**: individual y "Imprimir todos" (PDF multipágina) desde la lista de premiados, con vista previa.
6. **Sin funciones fuera de esto**: no se toca la landing existente, ni el registro de resultados, ni Académico.

**Fuera de alcance de esta entrega:** descarga del diploma por parte del jugador, envío por correo/WhatsApp, firma digital, reverso y la **página pública de logros** (queda especificada en §5 y en la fase posterior de §9 para implementarla después). Mientras no exista esa página, el QR del diploma apunta a la landing del torneo.

---

## 3. Base de datos (3 tablas nuevas)

Migraciones en `Modules/Socialevents/Database/Migrations/`, con guardas de idempotencia (`Schema::hasTable` / `hasColumn`) como en `2026_10_24_*`.

### 3.1 `socialevent_edition_awards` — premiados

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `edition_id` | FK `event_editions` cascade | |
| `award_key` | string(60) | `champion`, `runner_up`, `third_place`, `mvp`, `top_scorer`, `best_goalkeeper`, `top_assists`, `best_defender`, `fair_play`, `revelation_team`, `custom_*` |
| `label` | string(120) | "Mejor jugador", "Goleador", etc. |
| `recipient_type` | enum `team`/`player`/`person` | |
| `team_id` | FK `event_teams` nullOnDelete | |
| `person_id` | FK `people` nullOnDelete | |
| `recipient_name` | string(180) | Snapshot al premiar |
| `team_name` | string(150) nullable | Snapshot |
| `source` | enum `auto`/`manual` | Procedencia de la sugerencia |
| `place` | tinyint nullable | 1, 2, 3 para puestos de equipo |
| `meta` | json nullable | `{goles, asistencias, atajadas, mvp, puntos, partidos, camiseta, posicion}` |
| `sort_order` | smallint default 0 | |
| `is_published` | boolean default true | |
| `timestamps` | | |

Índices: `unique(edition_id, award_key)` con nombre corto explícito (límite de 64 caracteres de MySQL), `index(edition_id, sort_order)`.

> `recipient_type = team` cubre el diploma único por equipo con plantel; no se crea un premio por jugador.

### 3.2 `socialevent_edition_diploma_templates` — diseños

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `edition_id` | FK cascade | |
| `award_key` | string(60) | Premio al que pertenece el modelo |
| `name` | string(120) | Nombre interno |
| `background_path` | string | `socialevents/diplomas/{edition}/front_x.png` |
| `background_width` / `background_height` | unsigned int | **Definen el tamaño del lienzo** |
| `layout` | json | Elementos (§3.4) |
| `is_active` | boolean default true | Único vigente por `(edition_id, award_key)`, garantizado en el servicio dentro de una transacción |
| `created_by` | FK `users` nullOnDelete | |
| `timestamps` | | |

Sin `has_reverse`, sin `back_*`: el diploma es de una sola cara.

### 3.3 `socialevent_edition_diplomas` — emisiones

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `public_token` | uuid/char(26) único | Identifica el diploma en la URL pública del QR |
| `edition_id`, `template_id`, `award_id` | FK | `award_id` cascade |
| `recipient_type`, `team_id`, `person_id` | igual que premiados | |
| `recipient_name`, `team_name`, `award_label`, `edition_name` | string | **Snapshot**: el diploma emitido no cambia si luego se edita el premio |
| `squad_snapshot` | json nullable | `[{name, jersey, position}]` congelado al emitir (diploma de equipo) |
| `layout_snapshot` | json nullable | Copia del layout para reimprimir idéntico |
| `issued_at`, `issued_by` | timestamp / FK users | |
| `views` | unsigned int default 0 | Contador de la página pública |
| `timestamps` | | |

Índice `unique(edition_id, award_id, template_id)` con nombre corto; reemitir con otro modelo vigente crea una fila nueva sin duplicar dentro del mismo modelo.

### 3.4 Esquema del `layout` (JSON)

```json
{
  "version": 1,
  "background": { "path": "socialevents/diplomas/12/front_1712.png", "width": 1754, "height": 1240 },
  "elements": [
    { "id": "el_1", "type": "text", "name": "Premio", "x": 877, "y": 300,
      "text": "{{premio}}", "fontFamily": "Poppins Light", "fontSize": 56,
      "color": "#0b1f2b", "align": "center", "width": 1100, "lineHeight": 1.2,
      "letterSpacing": 2, "rotation": 0, "visible": true, "locked": false },

    { "id": "el_2", "type": "text", "name": "Ganador", "x": 877, "y": 470,
      "text": "{{premio_ganador}}", "fontFamily": "Pacifico Regular", "fontSize": 84,
      "color": "#0b1f2b", "align": "center", "width": 1200, "rotation": 0,
      "visible": true, "locked": false },

    { "id": "el_3", "type": "image", "name": "Escudo", "src": "socialevents/.../escudo.png",
      "x": 800, "y": 120, "width": 160, "height": 160, "rotation": 0,
      "visible": true, "locked": false },

    { "id": "el_4", "type": "squad", "name": "Plantel", "x": 300, "y": 700,
      "width": 1150, "columns": 3, "fontFamily": "Poppins Light", "fontSize": 22,
      "color": "#0b1f2b", "align": "left", "lineHeight": 1.45, "gap": 14,
      "showJerseyNumber": true, "showPosition": false, "sortBy": "number",
      "title": "PLANTEL", "titleFontSize": 24, "rotation": 0,
      "visible": true, "locked": false },

    { "id": "el_5", "type": "qr", "name": "QR", "x": 1520, "y": 1040, "size": 140,
      "mode": "landing", "visible": true, "locked": false },

    { "id": "el_6", "type": "rect", "name": "Marco", "x": 70, "y": 70,
      "width": 1614, "height": 1100, "stroke": "#c8f169", "strokeWidth": 6,
      "radius": 24, "fill": null, "rotation": 0, "visible": true, "locked": false }
  ]
}
```

Tipos de elemento: `text`, `image`, `qr`, `rect`, `line`, **`squad`**.

**`squad`** (lista de jugadores, sólo aplica al diploma de equipo): `columns` 1–4, `sortBy` `number|name|position`, `showJerseyNumber`, `showPosition`, `title` opcional, `gap` entre columnas, y los parámetros tipográficos comunes. El renderer compone el bloque de texto desde `squad_snapshot`; si el elemento existe en un diploma de jugador, se omite silenciosamente.

Reglas de validación en el backend (`DiplomaLayoutRequest`):
- máximo 60 elementos; `type` en `text|image|qr|rect|line|squad`;
- `x`,`y`,`width`,`height`,`fontSize`,`size`,`columns` numéricos y acotados (`[-2000, 5000]`, `columns` 1–4);
- `fontFamily` dentro de las 5 fuentes de `public/fonts` + `Arial`, `Georgia`, `Impact`;
- `color`/`stroke` con regex `#rgb|#rrggbb`;
- `src`/`path` sólo rutas internas que empiecen con `socialevents/diplomas/`;
- `text` máximo 500 caracteres; `title` del plantel máximo 60.

---

## 4. Variables (tokens) del diploma

Servicio único: `Modules/Socialevents/Services/DiplomaTokenResolver.php`. **El servidor siempre resuelve los tokens**; el cliente sólo los sustituye con datos de ejemplo en modo editor.

| Token | Valor |
|---|---|
| `{{premio}}` | `award.label` |
| `{{premio_ganador}}` | `recipient_name` |
| `{{premio_equipo}}` | `team_name` |
| `{{torneo}}` | `edition.evento.title` |
| `{{edicion}}` | `edition.name` |
| `{{anio}}` | `edition.year` |
| `{{categoria}}` | categoría del equipo premiado |
| `{{puesto}}` | `place` ("1°", "2°", …) |
| `{{fecha}}` | fecha de emisión `d [de] MMMM [de] YYYY`, locale `es` |
| `{{organizacion}}` | organización dueña de la edición |
| `{{jugador}}`, `{{equipo}}` | alias de ganador / equipo |
| `{{camiseta}}`, `{{posicion}}` | datos del jugador premiado |
| `{{goles}}`, `{{asistencias}}`, `{{atajadas}}`, `{{mvp}}`, `{{puntos}}` | `meta` del premio |
| `{{contacto}}` | `edition.contact_phone` / `contact_whatsapp` |

Los tokens desconocidos se renderizan vacíos. En el editor la vista previa usa datos reales de la edición (nombre del torneo, un equipo real), nunca datos inventados.

---

## 5. Página pública de logros (fase posterior, NO incluida en esta entrega)

Se deja aquí especificada para no rehacer decisiones. **No se implementa ahora**: esta entrega termina en los diplomas impresos. Mientras tanto, el elemento QR del editor funciona en `mode: "landing"` (landing del torneo).

### 5.1 Ruta

```php
Route::get('torneos/{slug}/logro/{token}', [TournamentDiplomaController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*|\d+')
    ->where('token', '[A-Za-z0-9\-]{8,64}')
    ->middleware('throttle:60,1')
    ->name('socialevents_torneos_diploma');
```

- Pública, sin autenticación, mismo criterio que `socialevents_torneos_player_detail`.
- **Siempre visible**: no depende de `landing_published`. Sólo exige que el diploma exista; si no, `404` con mensaje neutro ("Diploma no disponible"). Nunca se listan diplomas.
- `increment('views')` sin bloquear la respuesta (patrón de `TournamentLandingVisitService`).

### 5.2 Contenido de la página

Vista Blade nueva `Modules/Socialevents/Resources/views/torneos/diploma.blade.php` (+ `partials/diploma-*.blade.php`), con el mismo layout público y SCSS de la landing (`torneos-landing.scss`), sin Inertia.

**Cabecera:** torneo, edición y año, badge del premio, fecha de emisión, logo del equipo y foto del jugador.

**Reglas de privacidad (obligatorias):** sólo **nombre completo**, equipo, camiseta y posición. **No** se muestran DNI, teléfono, correo, fecha de nacimiento ni ningún otro dato personal. **No** se muestran montos de dinero: del apartado disciplinario sólo se indica qué tarjeta y en qué partido se registró.

**Jugador premiado** (`recipient_type = player|person`):

- Identidad: nombre completo, equipo, camiseta, posición.
- Totales del torneo: partidos jugados, goles, asistencias, MVP, atajadas, vallas invictas, minutos.
- Disciplina: amarillas y rojas **con el partido en que se registraron**, suspensiones y exclusiones. Sin importes.
- Detalle partido a partido (fecha, rival, resultado, goles, asistencias, MVP, tarjetas) reutilizando `TournamentRankingsService::playerDetail()`.
- Posición del jugador en los rankings del torneo (goleadores, MVP, arqueros) cuando aplique.

**Equipo premiado** (`recipient_type = team`):

- Identidad: nombre, escudo, categoría.
- Totales: posición final, puntos, partidos, goles a favor/contra, diferencia.
- Plantel completo (`squad_snapshot` del diploma, con respaldo en `event_edition_team_players` si el snapshot no existe).
- Disciplina del equipo y sus jugadores más destacados.

**Pie:** enlace a la landing del torneo y aviso de que el diploma fue emitido en esa edición.

### 5.3 Servicio de datos

`DiplomaAchievementService` construye el payload de la página:
- jugador: `TournamentRankingsService::playerDetail()` + agregados de `EventEditionMatchPlayerStat` + sanciones/suspensiones/exclusiones (sin `amount_fine`);
- equipo: `PositionTableService::getStandings()` + `EventEditionMatch` + plantel;
- cacheado por `(diploma_id, versión)` con `TournamentLandingCache` para no golpear la base en cada visita.

### 5.4 QR en el diploma

- **Ahora (esta entrega):** `mode: "landing"`, la URL apunta a la landing del torneo. Es el único modo seleccionable en el editor.
- **Cuando exista la página de logros:** se habilita `mode: "diploma"`, con la URL generada a partir del `public_token` del diploma emitido, y pasa a ser el modo por defecto.
- La columna `public_token` se crea desde ahora (sin uso todavía) para no requerir una migración extra después.
- En vista previa sin emitir nunca se publica un enlace roto: si el modo es `landing` se usa la landing real; si algún día es `diploma` sin emisión, se marca como "vista previa".

---

## 6. Backend

### 6.1 Archivos nuevos

```
Modules/Socialevents/
├── Database/Migrations/
│   ├── 2026_10_26_000001_create_socialevent_edition_awards.php
│   ├── 2026_10_26_000002_create_socialevent_edition_diploma_templates.php
│   └── 2026_10_26_000003_create_socialevent_edition_diplomas.php
├── Entities/
│   ├── EventEditionAward.php
│   ├── EventEditionDiplomaTemplate.php
│   └── EventEditionDiploma.php
├── Services/
│   ├── EditionAwardService.php               (catálogo, sugerencias, upsert, orden)
│   ├── DiplomaTemplateService.php            (crear/duplicar/activar, layout, fondo)
│   ├── DiplomaTokenResolver.php              (tokens → texto final)
│   ├── DiplomaPayloadService.php             (payload de render, incluye plantel)
│   └── DiplomaAchievementService.php         (datos de la página pública)
├── Http/Requests/
│   ├── EditionAwardRequest.php
│   ├── DiplomaTemplateRequest.php
│   └── DiplomaLayoutRequest.php
├── Http/Controllers/
│   ├── EventEditionDiplomaController.php       (panel, editor, CRUD de modelos, fondo)
│   ├── EventEditionAwardController.php         (CRUD, sugerencias, orden)
│   └── EventEditionDiplomaIssueController.php  (payload individual/lote, emitir)
│   # Página de logros (fase posterior):
│   # TournamentDiplomaController.php + Resources/views/torneos/diploma.blade.php
│   # + partials/diploma/{header,player,team,discipline,matches,footer}.blade.php
└── Resources/assets/js/Pages/Editions/Diplomas/
    ├── Index.vue                 (pestañas "Diseños" y "Premiados")
    ├── Editor.vue                (editor Konva)
    └── Partials/
        ├── DiplomaStage.vue      (v-stage + Transformer, compartido con preview)
        ├── DiplomaLayersPanel.vue
        ├── DiplomaInspector.vue
        ├── DiplomaSquadInspector.vue
        ├── DiplomaTokensPanel.vue
        ├── DiplomaTemplateCard.vue
        ├── AwardRow.vue
        ├── AwardFormModal.vue
        └── DiplomaPreviewModal.vue
└── Resources/assets/js/utils/
    ├── diplomaLayout.js          (defaults, normalización, tipos, plantel → líneas)
    └── diplomaExport.js          (PNG, PDF individual, PDF multipágina, print)
```

### 6.2 Rutas del panel (dentro del grupo `auth` existente)

| Método | URI | Nombre | Permiso |
|---|---|---|---|
| GET | `editions/{id}/diplomas` | `even_ediciones_diplomas` | `even_ediciones_diplomas` |
| GET | `editions/{id}/diplomas/create` | `even_ediciones_diplomas_crear` | `even_ediciones_diplomas_disenar` |
| POST | `editions/{id}/diplomas/templates` | `even_ediciones_diplomas_store` | `even_ediciones_diplomas_disenar` |
| GET | `editions/{id}/diplomas/templates/{tplId}` | `even_ediciones_diplomas_editar` | `even_ediciones_diplomas_disenar` |
| PUT | `editions/{id}/diplomas/templates/{tplId}` | `even_ediciones_diplomas_update` | `even_ediciones_diplomas_disenar` |
| POST | `editions/{id}/diplomas/templates/{tplId}/activate` | `even_ediciones_diplomas_activar` | `even_ediciones_diplomas_disenar` |
| POST | `editions/{id}/diplomas/templates/{tplId}/background` | `even_ediciones_diplomas_fondo` | `even_ediciones_diplomas_disenar` |
| DELETE | `editions/{id}/diplomas/templates/{tplId}` | `even_ediciones_diplomas_destroy` | `even_ediciones_diplomas_disenar` |
| POST | `editions/{id}/diplomas/awards` | `even_ediciones_premiados_store` | `even_ediciones_premiados` |
| PUT | `editions/{id}/diplomas/awards/{awardId}` | `even_ediciones_premiados_update` | `even_ediciones_premiados` |
| DELETE | `editions/{id}/diplomas/awards/{awardId}` | `even_ediciones_premiados_destroy` | `even_ediciones_premiados` |
| PUT | `editions/{id}/diplomas/awards/order` | `even_ediciones_premiados_orden` | `even_ediciones_premiados` |
| GET | `editions/{id}/diplomas/awards/suggestions` | `even_ediciones_premiados_sugerencias` | `even_ediciones_premiados` |
| GET | `editions/{id}/diplomas/awards/{awardId}/payload` | `even_ediciones_diploma_payload` | `even_ediciones_diplomas` |
| POST | `editions/{id}/diplomas/awards/{awardId}/issue` | `even_ediciones_diploma_emitir` | `even_ediciones_diplomas` |

Todos empiezan con `SportsOrganizationService::assertCanRead/Write` + `assertOwnedEdition($user, $editionId)`.

Permisos nuevos en `PermissionsModuleSeeder` (idempotente): `even_ediciones_diplomas`, `even_ediciones_diplomas_disenar`, `even_ediciones_premiados`.

La ruta pública `torneos/{slug}/logro/{token}` de §5.1 **no** se registra en esta entrega; se agrega cuando se implemente la página de logros.

### 6.3 Payload de render

`GET .../awards/{awardId}/payload` devuelve:

```json
{
  "template": { "id": 4, "name": "Campeón 2026", "award_key": "champion" },
  "award": { "id": 9, "label": "Campeón", "recipient_type": "team",
             "recipient_name": "Deportivo Chosica", "team_name": "Deportivo Chosica",
             "meta": { "puntos": 21, "partidos": 9 } },
  "side": {
    "key": "front",
    "width": 1754,
    "height": 1240,
    "background_url": "https://.../storage/socialevents/diplomas/12/front.png",
    "elements": [
      { "id": "el_2", "type": "text", "x": 877, "y": 470, "width": 1200,
        "text": "Deportivo Chosica", "fontFamily": "Pacifico Regular", "fontSize": 84,
        "color": "#0b1f2b", "align": "center", "lineHeight": 1.2, "rotation": 0, "visible": true },
      { "id": "el_4", "type": "squad", "x": 300, "y": 700, "width": 1150, "columns": 3,
        "fontFamily": "Poppins Light", "fontSize": 22, "color": "#0b1f2b", "align": "left",
        "sortBy": "number", "showJerseyNumber": true, "showPosition": false,
        "title": "PLANTEL", "titleFontSize": 24,
        "items": [ { "name": "Luis Ramos", "jersey": 10, "position": "Delantero" } ] },
      { "id": "el_5", "type": "qr", "x": 1520, "y": 1040, "size": 140,
        "qr_text": "https://.../torneos/deportivo-chosica-2026/logro/a1b2c3d4" }
    ]
  },
  "file_name": "diploma_campeon_deportivo-chosica"
}
```

El servidor ya compone el plantel (`items`) según `sortBy` y el snapshot; el renderer sólo dibuja. Si no hay plantilla vigente o el premio no tiene ganador → `422` con `message` claro. El lote (`GET .../payload?all=1`) devuelve un array con el mismo contrato, en `sort_order`.

### 6.4 Emisión

`POST .../issue` (idempotente por `(edition, award, template)`):
1. Valida premio con ganador y plantilla vigente.
2. Congela `recipient_name`, `team_name`, `award_label`, `edition_name`, `layout`, `squad_snapshot`.
3. Genera `public_token` (uuid v4 sin guiones, 32 chars) si no existe.
4. Devuelve el payload con `qr_text` real y `public_url` de la página de logros.

---

## 7. Frontend

### 7.1 `Index.vue` (pestañas)

- **Premiados**: lista con premio, ganador, equipo, origen (`auto`/`manual`), estado del diploma ("Sin diseño vigente", "Listo", "Emitido el …"), acciones `Diploma`, `Ver logros` (si está emitido), `Editar`, `Quitar`. Cabecera con `Imprimir todos` (deshabilitado con tooltip explicativo si falta algún diseño) y `Sugerir premiados`.
- **Diseños**: tarjetas con miniatura del fondo, premio asociado, badge "Vigente", acciones `Abrir editor`, `Duplicar`, `Activar`, `Eliminar` (confirmación SweetAlert).
- Cabecera con estilo Torneos y breadcrumb `Torneos › {edición} › Diplomas`.

### 7.2 Editor `Editor.vue`

- Tres zonas: capas + botones (`Agregar texto / imagen / plantel / QR / marco / línea`), lienzo con zoom (`fit`, 50 %–200 %), inspector del elemento.
- El inspector cambia según el tipo; `DiplomaSquadInspector.vue` expone columnas, orden, mostrar camiseta/posición, título y tipografía del plantel, con vista previa inmediata usando el plantel real del equipo premiado (o el primero de la edición si aún no hay premio).
- Lienzo dimensionado **exactamente** al fondo (`background_width × background_height`); al subir un fondo nuevo se pregunta si se reescala el layout proporcionalmente ("Escalar elementos" / "Mantener posiciones").
- Guardado: `Guardar` manual + autoguardado con debounce 1.5 s; se guarda en `dragend`/`transformend`, no durante el arrastre.
- Atajos: `Ctrl/Cmd+Z`, `Ctrl/Cmd+Shift+Z`, `Supr`, flechas (Shift = 10 px).
- Fuentes: `@font-face` de `public/fonts/*.ttf` en alcance de la página y `await document.fonts.ready` **antes** de exportar.
- Fondo: drag & drop o selector, PNG/JPG hasta 8 MB; se reescala a máx. 2500 px por lado al guardar y se devuelven las dimensiones reales.

### 7.3 Impresión y descarga (`diplomaExport.js`)

- `renderSideToCanvas(payloadSide)` a `pixelRatio` para ~300 DPI sobre el tamaño físico declarado.
- `downloadPng`, `downloadPdf` (mismo enfoque que `CertificatePreviewModal.vue`).
- `downloadBatchPdf(items)`: un `jsPDF` con una página por diploma, progreso `x de N`, nombre `diplomas_{edicion}_{fecha}.pdf`, generación secuencial con `await`.
- `printBatch(items)`: ventana con una imagen por página y `@page { size: Wpx Hpx; margin: 0 }`.

### 7.4 Integración

- `Pages/Editions/List.vue`: botón `🎓 Diplomas` en la grilla de acciones (`edition-action--sports`).
- `Pages/Editions/Edit.vue`: entrada `Diplomas` en el breadcrumb de la edición.
- Sin ítem nuevo en el menú lateral (es por edición).

---

## 8. Catálogo de premios

| `award_key` | Etiqueta | Tipo | Origen del ganador |
|---|---|---|---|
| `champion` | Campeón | team | `PositionTableService::getStandings()` puesto 1 / final de eliminatorias |
| `runner_up` | Subcampeón | team | puesto 2 |
| `third_place` | Tercer lugar | team | puesto 3 |
| `mvp` | Mejor jugador | player | `topPlayersPayload()` #1 |
| `top_scorer` | Goleador | player | `topScorersPayload()` #1 |
| `best_goalkeeper` | Mejor arquero | player | `topGoalkeepersPayload()` #1 |
| `top_assists` | Máximo asistidor | player | ranking de asistencias |
| `best_defender` | Mejor defensa | player | manual |
| `fair_play` | Juego limpio | team | manual / menor cantidad de tarjetas |
| `revelation_team` | Equipo revelación | team | manual |
| `custom_*` | Premio libre | team/player/person | manual |

La sugerencia **nunca** se aplica sola: se muestra en un modal con candidatos y el organizador confirma.

---

## 9. Fases de implementación

### Fase 1 — Datos y permisos
Migraciones idempotentes, entidades con `$fillable`/relaciones (`edition()`, `team()`, `person()`, `template()`, `award()`), permisos en el seeder, factories para tests.
**Aceptación:** `php artisan migrate` limpio sobre base existente y nueva; rollback sin errores.

### Fase 2 — Premios y plantillas
`EditionAwardService`, `DiplomaTemplateService`, requests, controladores y rutas. Sugerencias automáticas. Activación exclusiva por `(edición, premio)` en transacción.
**Aceptación:** CRUD de premios; `award_key` duplicado → 422; activar un modelo desactiva el anterior del mismo premio; edición ajena → 403.

### Fase 3 — Editor Konva
`Editor.vue`, `DiplomaStage`, capas, inspector, inspector de plantel, tokens, zoom, historial, autoguardado, fondo y fuentes.
**Aceptación:** diseñar un diploma completo, recargar y recuperar el mismo layout; el fondo define el tamaño del lienzo; el plantel se previsualiza con jugadores reales y respeta columnas/orden.

### Fase 4 — Premiados, emisión e impresión
Pestaña Premiados, modal de premio, sugerencias, payload, emisión con `public_token` y `squad_snapshot`, previsualización, PNG/PDF y lote.
**Aceptación:** emitir registra la fila con snapshot; reemitir con el mismo modelo no duplica; el PDF del lote trae una página por premiado; PNG/PDF coinciden con la vista previa.

### Fase 5 — Integración y pulido
Botón en la lista de ediciones, breadcrumb, estados vacíos, modo oscuro, responsive (editor en tablet; en móvil se avisa que el diseño se hace en escritorio).
**Aceptación:** recorrido completo Torneos → diseño → premiado → PDF sin callejones sin salida; sin cambios visuales en Académico ni en otras vistas de Torneos.

### Fase posterior (fuera de esta entrega) — Página pública de logros
`TournamentDiplomaController`, `DiplomaAchievementService`, vistas Blade y estilos del tema Torneos, QR en `mode: "diploma"`.
**Aceptación prevista:** el QR abre la página con goles, asistencias, MVP, partidos y tarjetas del jugador (sin DNI ni montos); el diploma de equipo muestra posición final y plantel del snapshot; token inexistente → 404; sin filtración de datos de otros diplomas; visible aunque la landing esté oculta.

---

## 10. Pruebas previstas

`tests/Feature/Socialevents/EditionDiplomasTest.php`
1. Crear premio de equipo y de jugador.
2. `award_key` duplicado en la misma edición → 422.
3. Sugerencias: goleador y mejor arquero desde rankings; campeón desde la tabla.
4. Crear plantilla, guardar layout válido; rechazar `type` desconocido, coordenadas fuera de rango y columnas del plantel fuera de 1–4.
5. Activar plantilla: sólo una vigente por `(edición, premio)`.
6. Payload resuelve tokens y **compone el plantel** según `sortBy`, columnas y `showJerseyNumber`.
7. Payload sin plantilla vigente → 422.
8. Emisión: registra fila con `public_token` y `squad_snapshot`; repetir no duplica; el snapshot conserva nombres aunque cambie la plantilla después.
9. Organización ajena → 403; sin sesión → 401/302.
10. Borrar edición elimina premios, plantillas y emisiones (cascade).

`tests/js/diploma-layout.test.mjs`
- Normalización del layout, defaults, tamaño del lienzo según fondo, nombre de archivo y armado de líneas del plantel.

**Posteriores (cuando se implemente la página de logros):** `tests/Feature/Socialevents/TournamentDiplomaPageTest.php` — token válido con goles/asistencias/MVP y tarjetas por partido, token inexistente 404, visible sin `landing_published`, diploma de equipo con posición final y plantel, sin DNI ni montos, incremento de `views`.

---

## 11. Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Dos motores de render desincronizados | **Un solo motor**: Konva en el navegador; el backend sólo entrega datos y guarda el layout. |
| Fuentes no cargadas al exportar | `document.fonts.ready` + espera explícita antes de `toDataURL`. |
| Fondos enormes que congelan el navegador | Reescalado a máx. 2500 px por lado al subir; `pixelRatio` de exportación acotado. |
| Plantel desactualizado tras inscribir jugadores | `squad_snapshot` al emitir + aviso "el plantel cambió desde la emisión" con acción "Reemitir". |
| Privacidad de la página de logros | Token aleatorio no enumerable, `throttle:60,1`, sin listados, `noindex` en la vista, sólo datos del diploma. |
| Nombres de índice largos en MySQL | Nombres explícitos en todos los índices/constraints. |
| Autoguardado fallido | Guardado manual siempre disponible, indicador de estado y `beforeunload` si hay cambios sin guardar. |
| Organizador accede a ediciones ajenas | `assertOwnedEdition` en todos los endpoints, incluido payload y emisión. |
| Migración sobre producción ya migrada | Guardas `Schema::hasTable`/`hasColumn`; sólo `create`. |

---

## 12. Decisiones cerradas y pendiente único

Cerradas:

1. ~~Un modelo activo por tipo de premio **y** edición.~~ Reemplazado: los modelos son reutilizables por torneo y puede haber varios vigentes del mismo tipo; cada premio elige uno.
2. Sólo anverso.
3. Tamaño del lienzo = tamaño del fondo subido.
4. Diploma de equipo único, con plantel configurable.
5. La página de logros se hará después; siempre visible, sin exigir `landing_published`.
6. En la página de logros: sólo nombre completo, sin DNI ni datos personales; sin montos de dinero, sólo tarjetas con su partido.

Pendiente para cuando se retome la página de logros: si el QR del diploma debe migrar automáticamente de `landing` a `diploma` en los diplomas ya emitidos (reemisión) o sólo aplicarse a los nuevos.

---

## 13. Reestructuración: modelos reutilizables por torneo

Implementado sobre el flujo anterior sin borrar tablas ni diplomas históricos.

### Cambios de estructura

- `socialevent_edition_diploma_templates` gana `event_id`, `diploma_type` (`team|player`) y `year`.
  La tabla conserva sus filas: `edition_id` queda como edición de origen del modelo.
- `socialevent_edition_awards` gana `template_id`: la selección explícita del modelo para ese premio.
  Si queda vacío, se usa el modelo vigente más reciente del mismo tipo en el torneo.
- Migración `2026_10_27_000001_make_diploma_templates_reusable` idempotente, con relleno de
  `event_id` y `diploma_type` para los modelos ya guardados.

### Flujo

1. **Torneos → Diplomas** (`GET /socialevents/events/{id}/diplomas`): lista de modelos con año,
   tipo, vigencia y acciones. Es la biblioteca del torneo.
2. Crear o editar el modelo en el editor Konva de siempre. El fondo define el lienzo.
3. **Edición → Diplomas**: dos secciones, equipos y jugadores. Cada premiado muestra el modelo
   seleccionado y permite fijarlo explícitamente.
4. La vista previa usa `GET .../awards/{id}/payload` y no registra emisión. `Emitir` registra
   `socialevent_edition_diplomas` con instantánea de layout y plantel.
5. Una reimpresión usa el modelo con el que se emitió, aunque luego se cree otro modelo o se
   cambie la selección.

### Variables

- Equipo: `nombre_equipo`, `edicion`, `lista_jugadores`, `puesto`.
- Jugador: `nombre_equipo`, `edicion`, `nombre_jugador`, `tipo_premio`.
- Compatibilidad: variables anteriores (`premio`, `premio_ganador`, `torneo`, `fecha`, QR, etc.)
  siguen resolviendo para no romper diseños guardados.
- El editor sólo ofrece las variables del tipo del modelo y la vista previa avisa qué variables
  quedaron vacías antes de descargar.

### Límite conocido

El modelo se reutiliza entre ediciones del **mismo torneo**. Compartirlo entre torneos distintos
requeriría una relación adicional y autorización explícita. Si se elimina la edición de origen,
la llave foránea heredada elimina también el modelo; se recomienda desactivar modelos en uso en
lugar de borrar ediciones con diplomas emitidos.
