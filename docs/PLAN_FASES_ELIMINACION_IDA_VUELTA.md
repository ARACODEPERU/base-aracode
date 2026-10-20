# Plan — Fases de eliminación y formato de cruce (ida y vuelta)

Ruta afectada: `socialevents/editions/{id}/fixtures/create` y el editar de un partido
en el fixture (`.../fixtures`), más la landing pública y el fixture agrupado.

---

## 1. Objetivo

1. Ampliar el combo **Fase** con: Octavos de final, Cuartos de final, Semifinales y
   Tercer y cuarto puesto, **reutilizando** los identificadores y descripciones que ya
   existen en el sistema (sin crear claves nuevas duplicadas).
2. Añadir al crear/editar un partido una opción **opcional** de formato del cruce:
   - Partido único (normal: prórroga y penales)
   - Partido único (muerte súbita)
   - Ida y vuelta
3. Que el fixture (admin, landing y API) entienda esos formatos: agrupar las dos
   piernas de un cruce, mostrar el marcador global y ordenar bien las fases.

---

## 2. Estado actual (hallazgos)

| Pieza | Hoy | Problema |
|---|---|---|
| Combo Fase al crear | `Modules/Socialevents/Resources/assets/js/Pages/Fixtures/Partials/CreateForm.vue:105-110` → solo `league`, `quarterfinals`, `final` | Faltan fases que el backend ya soporta |
| Combo Fase al editar | **No existe**. El modal de `Editions/Partials/RoundRobinPlayoff.vue:803-951` no tiene selector de fase, y `editionFixturesUpdate` (`EventEditionMatchController.php:366`) solo valida jornada, fecha, grupos y equipos | La fase se guarda en el form (`formMatch.phase`) pero nunca se puede cambiar |
| Claves existentes | `Modules/Socialevents/Support/TournamentPhaseLabels.php` | Tiene alias repetidos: `round_16`/`round_8`, `quarter`/`quarterfinals`, `semi`/`semifinals` → si se usan como opciones del select, se duplican |
| Generación de playoffs | `Services/TournamentService.php:414-435` crea `quarterfinals`, `semifinals`, `third_place`, `final` y, para eliminación simple, `phase = 'knockout'` | `knockout` no tiene etiqueta en `TournamentPhaseLabels` |
| Orden de fases | `EventEditionMatchController.php:72-78` → `third_place = 5`, `final = 4` | El tercer puesto se muestra **después** de la final; faltan `round_16` y `groups` |
| API móvil | `Http/Controllers/Api/MatchAdminController.php:82,148` valida `league,groups,round_16,quarter,semi,final,third_place` | Usa `quarter`/`semi`, que **no** coinciden con lo que genera la web (`quarterfinals`/`semifinals`) |
| Etiquetas duplicadas en Vue | `RoundRobinPlayoff.vue:336-348`, `MatchScore.vue:68-74` | Cada vista mantiene su propio diccionario (y dicen distinto: "Tercer Puesto" vs "Tercer lugar") |
| Ida y vuelta | **No existe**. No hay `leg`, `tie_id` ni agregado en `event_edition_matches` | Hay que crearlo |
| Cobro | `SportsOrganizationService::chargeMatch` cobra **por partido** | Un cruce de ida y vuelta genera **2 cargos** y hay que avisarlo |
| Tabla de posiciones | `PositionTableService` suma **todos** los partidos con marcador (incluye playoffs) | Con ida y vuelta, la tabla de liga se inflaría |

---

## 3. Nomenclatura canónica (una sola fuente de verdad)

Se reutilizan las claves que ya usa la base y la generación automática. Las
descripciones se toman de `TournamentPhaseLabels`, unificando el texto.

| Clave (BD) | Etiqueta | Se usa en |
|---|---|---|
| `league` | Fase de grupos / Liga | fixture, acta, landing |
| `groups` | Fase de grupos | alias de `league` para grupos |
| `round_16` | Octavos de final | nueva opción del combo |
| `quarterfinals` | Cuartos de final | ya en `TournamentService` |
| `semifinals` | Semifinales | ya en `TournamentService` |
| `third_place` | **Tercer y cuarto puesto** | ya en `TournamentService` (un partido define 3.° y 4.°) |
| `final` | Gran final | ya en `CreateForm` |

Reglas:

- **No** se crea `fourth_place`: el partido de `third_place` ya define tercero y cuarto.
- `round_8`, `quarter`, `semi`, `playoff`, `relampago` quedan **solo como alias de
  lectura** (`TournamentPhaseLabels::canonical()`), nunca como opciones del select.
- Se agrega etiqueta para `knockout` → "Eliminatorias" (dato heredado de la eliminación
  simple) para que no salga como "Knockout" crudo.
- Se agrega `TournamentPhaseLabels::options()` que devuelve **solo** las claves
  canónicas en orden de competencia; es lo único que consumen los selects.

Orden de fases: `league → groups → round_16 → quarterfinals → semifinals →
third_place → final` (el tercer puesto se juega antes de la final).

---

## 4. Modelo de datos

Nueva migración:
`Modules/Socialevents/Database/Migrations/2026_10_20_000001_add_tie_fields_to_event_edition_matches.php`

| Columna | Tipo | Uso |
|---|---|---|
| `tie_format` | `string(20)`, default `single`, index | `single` \| `home_and_away` |
| `decider` | `string(20)`, default `normal`, index | `normal` (prórroga + penales) \| `sudden_death` (gol de oro) |
| `tie_id` | `unsignedBigInteger` nullable, index | id del partido de **ida**; `null` = partido suelto |
| `leg` | `unsignedTinyInteger` nullable | `1` ida, `2` vuelta |

Notas:

- `tie_format` y `decider` con default no nulo: los partidos existentes quedan como
  partido único normal, sin migración de datos.
- `tie_id` + `leg` permiten agrupar sin tabla nueva. La vuelta apunta a la ida.
- `home_and_away` y `sudden_death` solo se permiten en fases de eliminación
  (`round_16`, `quarterfinals`, `semifinals`, `third_place`, `final`, `knockout`).
  En `league`/`groups` se rechaza con error de validación.

`EventEditionMatch`: agregar a `$fillable`, casts de `leg`/`tie_id` a entero y la
relación `legs()` (`hasMany(..., 'tie_id')`).

---

## 5. Backend

### 5.1 Catálogo de fases
- `TournamentPhaseLabels`: `canonical()`, `options()`, `label()` usando el mapa único;
  etiqueta `third_place` = "Tercer y cuarto puesto" y `knockout` = "Eliminatorias".
- Quitar los diccionarios duplicados de `RoundRobinPlayoff.vue`, `SingleElimination.vue`
  y `MatchScore.vue`: las etiquetas llegan por props desde el servidor.

### 5.2 Crear partido (`editionFixturesCreate` / `editionFixturesStore`)
- `editionFixturesCreate`: enviar `phaseOptions` (de `TournamentPhaseLabels::options()`)
  y `tieFormats` (catálogo con la descripción de cada formato).
- `editionFixturesStore`:
  - Validar `phase` contra las claves canónicas (más alias aceptados → se normalizan).
  - Validar `tie_format` y `decider` con `in:`; exigir fase de eliminación cuando
    `tie_format = home_and_away` o `decider = sudden_death`.
  - **Duplicados**: la regla actual "estos equipos ya tienen partido en esta fase"
    debe excluir los partidos del mismo `tie_id`; si no, la vuelta se bloquearía.
  - Si `home_and_away`: en una sola transacción crear
    - ida con `leg = 1`, `tie_format = home_and_away`, `decider`
    - vuelta con `leg = 2`, equipos invertidos, `tie_id` = id de la ida,
      `match_date` de `match_date_return` (nullable), `location` de `location_return`
      (o el mismo lugar)
  - Cobro: `chargeMatch()` por **cada** partido creado (ida y vuelta = 2 cargos).
    Devolver en el flash el número de partidos y cargos generados.

### 5.3 Editar partido (`editionFixturesUpdate`)
- Añadir `phase` y `match_date_return`/`tie_format`/`decider` a la validación.
- Si el partido tiene `tie_id`: la fase y el formato se actualizan en **ambas** piernas
  (un cruce no puede quedar en dos fases distintas).
- Rechazar `home_and_away` sobre fases de liga y no permitir desvincular una vuelta
  que ya tiene acta cerrada.
- Mantener la regla actual: un partido jugado/en vivo no cambia de equipos.

### 5.4 Nuevo servicio de cruces
`Modules/Socialevents/Services/TournamentTieService.php`:

- `summary(EventEditionMatch $match): array` →
  `leg`, `tie_id`, `aggregate_h`, `aggregate_a`, `decided_by`
  (`aggregate` | `penalties` | `pending`), `winner_team_id`.
- `aggregate()` suma `score_h`/`score_a` de ambas piernas; si el global empata, usa
  `penalties`/`penalty_rounds` de la vuelta.
- Regla mínima de `sudden_death`: el acta no admite empate; al guardar el resultado
  (`editionMatchScoreStore`) se exige un ganador y se ignoran los penales.
- `home_and_away` en fase de eliminación: si el global empata y no hay penales en la
  vuelta, el acta devuelve advertencia y el cruce queda "pendiente de definición".

### 5.5 Orden de visualización
- `EventEditionMatchController@editionFixtures`: `$phaseOrder` con las 7 claves
  canónicas y `third_place` **antes** de `final`.
- `TournamentFixtureService::groupedFixture`: dentro de cada fase/jornada ordenar por
  `leg` (ida antes que vuelta) y por fecha.

### 5.6 API móvil
`Api/MatchAdminController` (store/update y `formatMatch`):

- Aceptar las claves canónicas (`quarterfinals`, `semifinals`, `round_16`) **y**
  conservar los alias (`quarter`, `semi`) por compatibilidad.
- Exponer en la respuesta `tie_format`, `decider`, `tie_id`, `leg` y, cuando exista,
  el resumen del global (`aggregate_h`, `aggregate_a`, `decided_by`).
- `TournamentFixtureService::serializeMatch` (próximos partidos, resultados recientes,
  paginado público) agrega los mismos campos.

### 5.7 Tabla de posiciones (decisión a confirmar)
`PositionTableService` hoy suma los partidos de eliminación a la tabla general; con
ida y vuelta se duplicarían los partidos de playoff. Recomendación: contabilizar solo
fases de liga/grupos. Es un cambio de comportamiento sobre datos existentes, así que
se deja como paso separado y confirmado por el usuario (ver §9).

---

## 6. Frontend

### 6.1 Crear partido (`Fixtures/Partials/CreateForm.vue`)
- Select **Fase** alimentado por `phaseOptions` (sin listas hardcodeadas).
- Nuevo select **Formato del cruce (Opcional)** con tres opciones:
  - Partido único (normal)
  - Partido único (muerte súbita)
  - Ida y vuelta
- Si elige ida y vuelta, mostrar "Fecha y hora de la vuelta" y "Cancha de la vuelta"
  (ambos opcionales) y un aviso: *"Se crearán 2 partidos y 2 cargos por registro."*
- Deshabilitar los formatos de eliminación cuando la fase elegida sea liga/grupos.

### 6.2 Editar partido
- `RoundRobinPlayoff.vue`: agregar el select **Fase** al modal (hoy no tiene) usando
  `phaseOptions`, más "Formato del cruce" y, si es ida y vuelta, el marcador global de
  solo lectura.
- `SingleElimination.vue`: misma pieza compartida (extraer un partial
  `Partials/MatchFormFields.vue` con fase, formato y fecha de vuelta para no duplicar).

### 6.3 Fixture
- `RoundRobinPlayoff.vue` / `SingleElimination.vue`: en cada tarjeta, chip
  **Ida** / **Vuelta** y, en la vuelta, línea **Global x - y** con el texto de cómo se
  definió (global / penales / pendiente).
- `MatchScore.vue`: usar `phaseLabels` del backend; mostrar el global en la vuelta y
  bloquear el empate cuando el formato sea muerte súbita.

### 6.4 Landing pública
- `torneos-landing.js` + `landing.blade.php` + `torneos-landing.scss`:
  - Etiquetas desde `phase_labels` (ya viene en `buildForLanding`).
  - Chip Ida/Vuelta y marcador global en la tarjeta de la vuelta.
  - Nuevas fases aparecen sin cambios extra porque el agrupado es por `phase`.

### 6.5 Premios
`editionPrizes.js` ya mapea `third_place => 3`; solo se alinea el texto visible a
"Tercer y cuarto puesto".

---

## 7. Pruebas

| Archivo | Casos |
|---|---|
| `tests/Unit/Socialevents/TournamentPhaseLabelsTest.php` | `options()` sin duplicados, orden correcto, `canonical()` resuelve alias, `third_place` = "Tercer y cuarto puesto" |
| `tests/Feature/Socialevents/FixturePhaseOptionsTest.php` | crear partido con cada fase nueva; editar la fase desde el modal; orden de fases en `fixture` (tercer puesto antes de la final) |
| `tests/Feature/Socialevents/TwoLegTieTest.php` | crear ida/vuelta (mismo `tie_id`, legs 1 y 2, equipos invertidos); la regla de duplicados no bloquea la vuelta; dos cargos; global y ganador; empate global con penales; rechazo de ida y vuelta en fase de liga |
| `tests/js/fixture-fases.test.mjs` | los selects consumen `phaseOptions` (sin listas de fases duplicadas en los `.vue`) y el modal de edición incluye el campo Fase |

Comandos (PHP CLI del proyecto sin `pdo_sqlite` por defecto):

```
php -d extension=pdo_sqlite ./vendor/bin/pest tests/Unit/Socialevents/TournamentPhaseLabelsTest.php
php -d extension=pdo_sqlite ./vendor/bin/pest tests/Feature/Socialevents/TwoLegTieTest.php
npm run test:js
npm run build
```

---

## 8. Orden de implementación

1. **Catálogo único**: `TournamentPhaseLabels` (canonical/options/labels) + prueba unitaria.
2. **Migración** de `tie_format`, `decider`, `tie_id`, `leg` + entidad.
3. **Crear partido**: combo de fases, formato de cruce y creación de la vuelta.
4. **Editar partido**: select de fase + formato en el modal; sincronización del cruce.
5. **Fixture admin**: orden de fases, chips Ida/Vuelta, global.
6. **Servicio de cruces** + acta (muerte súbita y definición por penales).
7. **Landing y API** con el mismo payload.
8. **Tabla de posiciones** (si se aprueba §9.1).
9. Pruebas de regresión del fixture y del acta.

---

## 9. Decisiones a confirmar

1. **Tabla de posiciones**: ¿excluir las fases de eliminación del cálculo de la tabla
   general? (recomendado para no inflar con ida y vuelta).
2. **Cobro**: ¿ida y vuelta se cobra como 2 partidos (comportamiento actual si se crean
   dos registros) o como un solo cruce?
3. **Regla de goles de visitante**: ¿se aplica en el global o solo se define por
   penales cuando el global empata? (recomendado: sin gol de visitante).
4. **Avance automático**: ¿el ganador del cruce debe llenar automáticamente el
   placeholder de la siguiente ronda, o se mantiene la asignación manual de hoy?
5. **App móvil**: los cambios de `MatchAdminController` habilitan las fases, pero la
   pantalla Flutter de partidos no muestra todavía Ida/Vuelta ni el global.

---

## 10. Fuera de alcance

- Generar automáticamente el cuadro de octavos a partir de la tabla de grupos.
- Reembolso del cargo si se elimina una pierna.
- Edición masiva de fases de varios partidos a la vez.
