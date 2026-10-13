# Plan — Registro del desempeño individual: cambios y minutos jugados

Estado: **F1 y F2 implementadas** (migraciones, servicio, guardado web y móvil, captura en el acta
y tests en verde). Ver §14 para la evidencia y lo que queda pendiente.
Módulo: `Modules/Socialevents` · Fútbol amateur · afecta web (Vue) y app móvil (Flutter).
La app Flutter vive en un checkout aparte (`aracode-movil`, fuera de este repositorio), por eso sus
rutas se citan como código y no como enlace.

### Decisiones ya tomadas

| Decisión | Resuelto |
| --- | --- |
| Cambios como entidad propia | Tabla nueva `event_edition_match_substitutions`. Un cambio = una fila (sale / entra / minuto). No se codifica dentro de `event_edition_match_player_stats`. |
| Minutos jugados | Se **calculan** desde la alineación + los cambios; el valor manual sigue siendo posible como respaldo. No se obliga a cronometrar cada minuto. |
| Duración del partido | **Nada fijo en el código.** Se configura por tiempo en la edición (`regular_half_minutes`, `regular_halves`) y, si un partido concreto lo necesita, **se puede sobrescribir por partido**. Hoy el preset es 2×20, pero mañana una edición puede jugar 2×45, 2×30 o menos. |
| Valores por defecto | Los de la edición son el respaldo; el **partido manda** sobre la edición. Si nadie configura nada, se usa un preset para que el sistema nunca quede sin referencia, pero es un simple *fallback*, no una regla. |
| Prórroga (`prórroga`) | **Opcional y apagada por defecto.** Solo existe en fases de **eliminación directa** y solo si el organizador la habilita; en liga/todos contra todos nunca aparece. Cuando aplica: dos tiempos suplementarios (10 min cada uno por defecto) y, si el empate persiste, recién ahí penales. |
| Penales | No se tocan: ya existen `penalty_rounds` + `penalties` en `event_edition_matches`. La prórroga se juega **antes** de los penales, no los reemplaza. |
| Captura ligera | Los cambios son **una lista corta** (equipo, sale, entra, minuto). No se convierte el acta en una línea de tiempo de todos los eventos en esta fase. |
| Fase 2 (opcional) | Línea de tiempo completa (gol/asistencia/tarjeta/minuto) en una tabla `event_edition_match_events`. Fuera de esta entrega. |

---

## 1. Objetivo

Poder registrar, al cerrar o corregir el acta de un partido, **quién entró, quién salió, en qué
minuto** y, a partir de eso, **cuántos minutos jugó cada futbolista**, sin obligar al operador a
cronometrar jugada por jugada ni a mantener un reloj en la mano.

Hoy el sistema conoce la estadística gruesa (goles, asistencias, atajadas, MVP, tarjetas y si el
jugador fue titular o suplente) pero **no sabe cuándo entró ni cuándo salió nadie**, así que
`minutes_played` existe en la base de datos y **siempre vale 0** porque ninguna pantalla lo captura.

El plan incorpora además el **formato real del torneo**: hoy los partidos son de **20 minutos por
tiempo** (40 reglamentarios) y, en la **fase de eliminación directa**, si un partido termina empatado
al final del tiempo reglamentario se juegan **dos tiempos suplementarios de 10 minutos** cada uno; si
el empate continúa, se define por **penales**. Eso significa que los minutos jugados de un partido
pueden llegar hasta 60, y los cambios también pueden ocurrir durante la prórroga.

---

## 2. Estado actual (lo que ya existe y se reutiliza)

| Pieza | Dónde | Qué aporta / qué falta |
| --- | --- | --- |
| Estadística por jugador | `event_edition_match_player_stats` · [EventEditionMatchPlayerStat.php](../Modules/Socialevents/Entities/EventEditionMatchPlayerStat.php) | Ya tiene `minutes_played`, `goals`, `assists`, `saves`, `is_mvp`, `clean_sheet`. **Falta capturarlo.** |
| Participación (rol) | `event_edition_match_participations` · [EventEditionMatchParticipation.php](../Modules/Socialevents/Entities/EventEditionMatchParticipation.php) | Guarda `role = starter \| substitute`. Es la base para saber quién estaba en cancha al inicio. |
| Actas / sanciones | `event_edition_match_reports`, `event_edition_match_sanctions` | El cierre de acta y las tarjetas ya se registran. Un expulsado no puede "entrar" en un cambio. |
| Penales | `event_edition_matches.penalty_rounds` + `penalties` (JSON) · [migración de penales](../Modules/Socialevents/Database/Migrations/2026_04_20_000001_add_penalties_to_event_edition_matches_table.php) | Ya funcionan (rondas + patadas). **Falta la prórroga previa**, que este plan agrega. |
| Guardado de stats (web) | [EventEditionMatchController.php:419](../Modules/Socialevents/Http/Controllers/EventEditionMatchController.php#L419) `savePlayerStats()` y `saveMatchPlayerParticipated()` (línea 498) | Aquí se persiste cada jugador. **No hay lógica de cambios.** |
| Guardado de stats (móvil) | [MatchAdminController.php:446](../Modules/Socialevents/Http/Controllers/Api/MatchAdminController.php#L446) | Duplica la misma lógica. Debe recibir los cambios igual que la web. |
| Rankings / agregados | [TournamentRankingsService.php:332](../Modules/Socialevents/Services/TournamentRankingsService.php#L332) | **Ya suma `minutes_played`** (total y por jugador). Queda listo para explotar el dato. |
| API pública de stats | [PlayerStatsApiController.php](../Modules/Socialevents/Http/Controllers/Api/PlayerStatsApiController.php) | Devuelve goles/asistencias/atajadas/MVP desde `event_edition_match_player_stats`. |
| Landing pública | [torneos-landing.js](../Modules/Socialevents/Resources/assets/js/torneos-landing.js) · [TournamentLandingPresenter.php](../Modules/Socialevents/Support/TournamentLandingPresenter.php) | Muestra tablas del torneo; es donde se pueden publicar "minutos jugados" y los cambios del partido. |
| Captura de minutos (móvil) | `lib/presentation/pages/match_result_page.dart` (línea ~922) | La app **ya tiene un campo de minutos** por jugador; solo no llega desde la web ni existe "cambios" en ningún lado. |
| Acta en web | [RoundRobinPlayoff.vue](../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/RoundRobinPlayoff.vue) y [SingleElimination.vue](../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/SingleElimination.vue) | Ahí vive el modal de resultados; es donde van los cambios y el campo de minutos. |

---

## 3. Criterios de aceptación

1. En el acta (web y móvil) se puede registrar, por equipo, una lista de cambios con
   **jugador que sale, jugador que entra y minuto**, y se guardan correctamente.
2. `minutes_played` de cada jugador queda **calculado y coherente**: titular sin cambio = duración
   efectiva completa; suplente que entra en el minuto `m` = `duración efectiva − m`; titular que sale
   en `m` = `m`. La duración efectiva sale de la **configuración del partido** (override) o de la
   edición: 40 en un 2×20 (60 con prórroga), 90 en un 2×45, y así según lo que se configure.
   **Ningún valor de minutos está fijo en el código.**
3. Un jugador con `match_role = null` ("no participó") **no** genera fila de stats ni minutos
   (comportamiento que ya existe y debe respetarse).
4. No se pueden guardar cambios inválidos: que salga y entre el mismo jugador, dos cambios con el
   mismo jugador saliendo dos veces seguidas, un minuto fuera de rango o negativo, o un jugador de
   otro equipo. El backend responde con error claro y **no** escribe nada.
5. Guardar dos veces el mismo partido (o corregir el acta) **no duplica** cambios ni minutos.
6. Al corregir un acta y **quitar a un jugador de la alineación**, sus cambios asociados y sus
   minutos desaparecen, igual que ya ocurre con sus goles o su MVP.
7. El total de minutos de un partido por equipo es razonable (`≤ jugadores en cancha × duración`) y
   el ranking del torneo muestra la columna de minutos jugados.
8. Se muestran los cambios del partido en la vista pública (detalle del partido) si la edición está
   publicada.
9. Todo esto funciona **sin** exigir captura cronometrada: si el operador no toca nada, el acta se
   guarda como hoy y los minutos se reparten con el criterio por defecto.
10. En **eliminación directa**, un partido empatado al final del tiempo reglamentario puede
    registrar la prórroga y, si sigue empatado, los penales que ya existen. Los minutos suman la
    prórroga y los cambios hechos en ella se guardan con su tiempo correcto (`extra_first` /`extra_second`).
11. Si el partido se define en el tiempo reglamentario, **nada** de la prórroga se activa y la
    duración efectiva es la reglamentaria configurada.
12. Cambiar los tiempos de una edición (p. ej. de 2×20 a 2×45) recalcula los minutos de sus partidos
    sin tocar datos históricos a mano; un **override por partido gana** sobre la configuración de la
    edición. En liga/todos contra todos la prórroga **nunca** aparece y el empate es un resultado válido.

---

## 4. Modelo de datos (Fase 1)

### 4.1 Tabla nueva: `event_edition_match_substitutions`

Migración `2026_10_11_000001_create_event_edition_match_substitutions_table.php`:

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint | |
| `match_id` | FK `event_edition_matches` (cascade) | |
| `team_id` | FK `event_teams` (cascade) | Necesario para separar local/visitante sin JOIN extra. |
| `player_out_id` | FK `people` (cascade) | Quien sale. |
| `player_in_id` | FK `people` (cascade) | Quien entra. |
| `minute` | unsignedSmallInteger | **Minuto absoluto** del cambio (0…duración efectiva). Se deriva de `period` + `minute_in_period`. |
| `minute_in_period` | unsignedSmallInteger | Minuto **dentro del tiempo jugado**, tal como se escribe en pantalla. |
| `period` | string(20), nullable | `first`, `second`, `extra_first`, `extra_second`. |
| `reason` | string(20), nullable | `tactical`, `injury`, `red_card`, `other`. Opcional. |
| `created_by` | FK `users`, nullable | Auditoría. |
| timestamps | | |

Índices: `unique(match_id, player_out_id)` y `index(match_id, team_id, minute)`. El `unique` sobre
`player_out_id` evita que un mismo jugador salga dos veces en el mismo partido.

### 4.2 Columnas nuevas en `event_editions`

Migración `2026_10_11_000002_add_match_minutes_settings_to_event_editions.php`:

| Columna | Tipo | Default | Para qué |
| --- | --- | --- | --- |
| `regular_half_minutes` | unsignedSmallInteger | 20 | Minutos de **cada tiempo reglamentario** (hoy 20). |
| `regular_halves` | unsignedTinyInteger | 2 | Cantidad de tiempos reglamentarios. Total reglamentario = `regular_half_minutes × regular_halves` (con el preset 2×20 da 40). |
| `extra_half_minutes` | unsignedSmallInteger | 10 | Minutos de **cada tiempo suplementario** de la prórroga. |
| `extra_halves` | unsignedTinyInteger | 2 | Cantidad de tiempos suplementarios (con el preset 2×10, prórroga completa = 20). |
| `extra_time_enabled` | boolean | **false** | Habilita la prórroga. **Apagada por defecto**; solo tiene efecto en fases de eliminación directa, en liga/grupos se ignora. |
| `max_substitutions` | unsignedTinyInteger, nullable | null | Solo avisa (no bloquea) si se supera el límite reglamentario de cambios. |
| `extra_substitution_enabled` | boolean | true | Habilita el cupo **adicional** de cambios durante la prórroga (solo si hay prórroga). |

Los valores por defecto son solo un **preset**: cada edición puede jugar 2×45, 2×30, 2×20 o lo que su
reglamento diga. Ninguno de estos números queda escrito en el código del cálculo.

### 4.3 Columnas nuevas en `event_edition_matches`

Migración `2026_10_11_000003_add_extra_time_to_event_edition_matches.php`. Para saber **si el
partido llegó a la prórroga** y calcular minutos con la duración real:

| Columna | Tipo | Default | Para qué |
| --- | --- | --- | --- |
| `half_minutes_override` | unsignedSmallInteger, nullable | null | **Sobrescribe** los minutos por tiempo de la edición **solo para este partido**. Si queda `null`, se usa la edición. |
| `halves_override` | unsignedTinyInteger, nullable | null | Sobrescribe la cantidad de tiempos reglamentarios para este partido. |
| `extra_half_minutes_override` | unsignedSmallInteger, nullable | null | Sobrescribe los minutos de cada tiempo suplementario para este partido. |
| `extra_time_played` | boolean | false | La prórroga se jugó (el partido seguía empatado al final del tiempo reglamentario). |
| `score_regular_h` | unsignedTinyInteger, nullable | null | Marcador local **al final del tiempo reglamentario**, antes de la prórroga. |
| `score_regular_a` | unsignedTinyInteger, nullable | null | Ídem visitante. |

`score_h` / `score_a` siguen siendo el **marcador final** e incluyen los goles de la prórroga (como en
el reglamento FIFA); `score_regular_*` conserva el empate que obligó a jugarla. Los penales no se
tocan (`penalty_rounds` + `penalties`).

### 4.4 Entidades

- Nueva `Entities/EventEditionMatchSubstitution.php` (fillable + relaciones `match()`,
  `playerOut()`, `playerIn()`, `team()`), siguiendo el patrón de las entidades existentes.
- `EventEditionMatch` suma a su `fillable`: `extra_time_played`, `score_regular_h`, `score_regular_a`,
  `half_minutes_override`, `halves_override` y `extra_half_minutes_override`.
- `EventEdition` suma `regular_half_minutes`, `regular_halves`, `extra_half_minutes`, `extra_halves`,
  `extra_time_enabled`, `max_substitutions` y `extra_substitution_enabled`.
- `EventEditionMatchPlayerStat` no cambia de esquema, pero `minutes_played` pasa a ser un valor
  **calculado** (ver §4.5).

### 4.5 Cómo se calculan los minutos

Regla por defecto, aplicada en un único servicio (`MatchMinutesCalculator`) para no repetir lógica.

**Orden de resolución de los tiempos** (el partido gana sobre la edición; nada fijo)

```
halfMinutes  = match.half_minutes_override       ?? edition.regular_half_minutes ?? 20
halves       = match.halves_override             ?? edition.regular_halves       ?? 2
extraHalf    = match.extra_half_minutes_override ?? edition.extra_half_minutes   ?? 10
extraHalves  = edition.extra_halves              ?? 2
```

**Duración efectiva del partido**

```
regularTotal     = halfMinutes × halves
prorrogaTotal    = extraHalf × extraHalves
duracionEfectiva = regularTotal + (match.extra_time_played ? prorrogaTotal : 0)
```

Con el preset actual (2×20) da 40, o 60 con prórroga; con 2×45 da 90, o 110 con prórroga; con 2×30,
60. El cálculo es el mismo en todos los casos porque no asume minutos.

**Minutos por jugador**

- Titular sin cambio registrado → `minutos = duracionEfectiva` (la que resulte de lo configurado).
- Titular que sale en el minuto absoluto `m` → `minutos = m`.
- Suplente que entra en `m` → `minutos = duracionEfectiva − m`.
- Suplente que entra en `m1` y sale en `m2` → `minutos = m2 − m1`.
- Suplente sin cambio de entrada registrado → `0` (no se inventan minutos).
- Expulsado (`red` / `double_yellow` en sanciones): si no hay cambio, su salida se asume en el
  minuto del hecho si se capturó, o se deja como "terminó en cancha" si no.
- Si el operador **escribe** un valor manual en el campo de minutos, ese valor manda (override) y no
  se recalcula; se marca internamente como manual para no perderlo al guardar.

### 4.6 Minutos en tiempo absoluto y por tiempo jugado

El operador piensa en "minuto 15 del segundo tiempo", no en "minuto 35 del partido". La UI captura
`period` + `minute_in_period` y el sistema guarda el **minuto absoluto**:

| Periodo | Cálculo del minuto absoluto |
| --- | --- |
| 1T (`first`) | `minute_in_period` |
| 2T (`second`) | `halfMinutes + minute_in_period` |
| 1er suplementario (`extra_first`) | `regularTotal + minute_in_period` |
| 2º suplementario (`extra_second`) | `regularTotal + extraHalf + minute_in_period` |

Con el preset 2×20, el 2T arranca en 20 y el 1er suplementario en 40; con 2×45, el 2T arranca en 45.
Los tiempos disponibles se generan desde la configuración, no de una lista fija.

Así el cálculo de minutos es una simple resta y la pantalla sigue mostrando el minuto tal como lo
canta el árbitro. El mismo mapeo se usa para validar el rango máximo de cada tiempo.

---

## 5. Reglas de negocio y validaciones

### 5.1 Cuándo se juega la prórroga

Aplica **solo a fases de eliminación directa** (`round_16`, `round_8`, `quarter`, `quarterfinals`,
`semi`, `semifinals`, `third_place`, `final`, `playoff`, según
[TournamentPhaseLabels.php](../Modules/Socialevents/Support/TournamentPhaseLabels.php)). El flujo es:

1. **Tiempo reglamentario:** los tiempos que tenga configurados la edición o el partido (preset 2×20,
   pero puede ser 2×45 o menos). Al terminar el último tiempo reglamentario se guarda el marcador en
   `score_regular_h` / `score_regular_a`.
2. **¿Empate?** Si `score_regular_h == score_regular_a`, la fase es de eliminación directa y la edición
   tiene la prórroga **habilitada** (`extra_time_enabled`, apagada por defecto), se habilitan los
   tiempos suplementarios con los minutos configurados (`extra_half_minutes`, 10 por defecto) sobre el
   mismo partido; se marca `extra_time_played = true`.
3. **¿Sigue el empate?** Recién entonces se pasa a **penales**, que ya existen
   (`penalty_rounds` + `penalties`) y no requieren cambios.
4. En liga/grupos la prórroga queda deshabilitada y un empate es un resultado válido. También queda
   deshabilitada si el organizador simplemente **no la activó**: no se activa sola nunca.

El acta muestra los tiempos jugados según `extra_time_played` y el reloj de cada tiempo, para que el
operador no tenga que recordar en qué tiempo entró nadie.

### 5.2 Validaciones de cambios

Validación en `FormRequest` (web) y en la validación de la API (móvil), compartida por un servicio:

1. `player_out_id` y `player_in_id` deben pertenecer al `team_id` del cambio y al acta del partido.
2. `player_out_id ≠ player_in_id`.
3. `player_out_id` no puede repetirse en el mismo partido (coherente con el `unique`).
4. `player_in_id` no puede haber salido antes (un expulsado o cambiado no reingresa, salvo que la
   edición active reingreso en fase futura).
5. `minute` (absoluto) entre `0` y la **duración efectiva**; `minute_in_period` no puede exceder los
   minutos del tiempo elegido (los resueltos en §4.5: `halfMinutes` en 1T/2T, `extraHalf` en cada
   suplementario).
6. Un cambio en un tiempo suplementario solo se acepta si `extra_time_played = true`.
7. Un cambio no puede dejar al equipo con menos de `min_players_per_team` en cancha (aviso/bloqueo
   configurable). Tampoco se puede registrar un cambio en un tiempo que el equipo todavía no está
   jugando (sin `extra_time_played`).
8. Al guardar, primero se borran los cambios del partido y se reinsertan (igual que hoy hacen
   sanciones y participaciones) dentro de la misma transacción: garantiza idempotencia al corregir.

---

## 6. Backend

1. **Servicio nuevo** `Services/MatchPerformanceService.php`:
   - `syncSubstitutions(int $matchId, array $subsH, array $subsA)`.
   - `recalculateMinutes(int $matchId, Collection $participations, array $substitutions)`.
   - Devuelve el mapa `player_id => minutes_played` que consumen ambos controladores.
2. **Web**: en [EventEditionMatchController.php](../Modules/Socialevents/Http/Controllers/EventEditionMatchController.php)
   leer `substitutions_h` / `substitutions_a` del request, persistir y calcular minutos antes de
   `savePlayerStats()`. Mismo tratamiento en el flujo de **corrección de acta**.
3. **Móvil**: en [MatchAdminController.php](../Modules/Socialevents/Http/Controllers/Api/MatchAdminController.php)
   aceptar el mismo par de arreglos y delegar en el servicio (evita que web y móvil diverjan).
4. **Borrado del partido**: sumar `EventEditionMatchSubstitution::where('match_id', …)->delete()` al
   `destroy()` existente para no dejar huérfanos.
5. **API de lectura**: exponer `substitutions` y `minutes_played` en el detalle del partido que
   consume la app ([MatchAdminController::show](../Modules/Socialevents/Http/Controllers/Api/MatchAdminController.php#L291))
   y en el detalle público.
6. **Permisos**: reutilizar `even_ediciones_partido_resultado` para capturar cambios y minutos; no
   hace falta un permiso nuevo si se acepta esa agrupación (alternativa: crear
   `even_ediciones_partido_cambios` con el patrón de [2026_08_17_000002_add_event_edition_gallery_permissions.php](../Modules/Socialevents/Database/Migrations/2026_08_17_000002_add_event_edition_gallery_permissions.php)).

---

## 7. Frontend web (Vue)

En el modal de resultados de [RoundRobinPlayoff.vue](../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/RoundRobinPlayoff.vue)
y [SingleElimination.vue](../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/SingleElimination.vue):

> Nota de implementación: el acta se captura en el modal de `RoundRobinPlayoff`, que es el que se
> usa en liga/grupos/playoff. En `single_elimination` / `relampago` el bracket solo permite fecha,
> equipos y marcador, así que **no hay acta en la fase de eliminación** (situación previa a este
> trabajo). Los datos de tiempos y cambios sí llegan a ese bracket; falta la pantalla de acta ahí.

1. Nueva columna **"Minutos"** en la tabla de jugadores (input numérico compacto, junto a Goles /
   Asistencias / Atajadas), con el valor precalculado y posibilidad de sobrescribirlo.
2. Botón **"Cambios"** por equipo que abre un bloque/modal con lista de filas:
   `Sale (select de titulares en cancha)` · `Entra (select de suplentes)` · `Minuto (número)` ·
   `Motivo (opcional)`. Botón "+ Agregar cambio" y eliminar por fila.
3. Los `select` se filtran con los jugadores del equipo con `match_role` definido: **salen** solo
   titulares/ quienes ya entraron, **entran** solo suplentes que no hayan entrado todavía.
4. Al cambiar/entrar/salir, recalcular los minutos en vivo en el mismo cliente (misma fórmula del
   servicio) para que el operador vea el efecto sin guardar.
5. Enviar `substitutions_h` / `substitutions_a` dentro de `formScore` y al corregir el acta.
6. En la configuración de la edición: tiempos reglamentarios, suplementarios, si la prórroga está
   habilitada (apagada por defecto) y el límite de cambios. En el acta, un selector de **tiempo jugado**
   (generado desde los tiempos configurados) junto al minuto, y el bloque de **prórroga y penales**
   solo cuando el marcador reglamentario está empatado y la fase es de eliminación directa.
7. **Override por partido**: en el propio acta se puede ajustar los minutos por tiempo de ese partido
   (p. ej. un partido de liga con tiempos más cortos) sin cambiar toda la edición. Si el campo queda
   vacío, se usa lo de la edición.

---

## 8. App móvil (Flutter)

- `lib/presentation/pages/match_result_page.dart` ya captura minutos;
  añadir una sección **"Cambios"** por equipo con el mismo contrato de datos
  (`substitutions_h` / `substitutions_a`), un selector de **tiempo jugado** y el bloque de prórroga
  **solo si aplica** antes de los penales, mostrando la duración configurada (edición u override del
  partido).
- Actualizar el modelo de partido admin (`lib/data/models/admin_match_model.dart`)
  para leer `substitutions` y `minutes_played` de la respuesta.
- `lib/core/services/api_service.dart`: incluir los cambios en el payload de
  guardado de acta.

---

## 9. Rankings y vistas públicas

- [TournamentRankingsService.php](../Modules/Socialevents/Services/TournamentRankingsService.php)
  ya acumula minutos: convertir el dato en columnas visibles ("minutos jugados", "partidos jugados").
- [PlayerStatsApiController.php](../Modules/Socialevents/Http/Controllers/Api/PlayerStatsApiController.php):
  agregar `SUM(minutes_played)` y un filtro `minutes` al ranking público.
- Detalle público del partido: mostrar la lista de cambios (minuto, entra/sale) y los minutos por
  jugador, solo si `landing_published`.
- Invalidar [TournamentLandingCache](../Modules/Socialevents/Support/TournamentLandingCache.php) al
  guardar cambios (el cierre de acta ya lo hace).

---

## 10. Fases y alcance

| Fase | Contenido | Depende de |
| --- | --- | --- |
| **F1 · Datos** | Migraciones, entidad, servicio de cálculo, persistencia web y borrado en cascada. | — |
| **F2 · Captura web** | Columna de minutos + bloque de cambios con selector de tiempo + recalculo en vivo + configuración de tiempos y prórroga en la edición. | F1 |
| **F3 · Móvil** | Sección de cambios y contrato compartido en la app. | F1 |
| **F4 · Explotación** | Ranking con minutos, detalle público de cambios, caché. | F2 |
| **F5 (opcional)** | Línea de tiempo completa de eventos (gol/tarjeta/cambio con minuto) en `event_edition_match_events`. | F2–F4 |

F1–F2 entregan el valor pedido (cambios + minutos) sin tocar el modelo de sanciones ni de goles.

---

## 11. Pruebas y verificación

Tests Pest en `tests/Feature/Socialevents/` (patrón de [AdminEditionPlayersApiTest.php](../tests/Feature/Socialevents/AdminEditionPlayersApiTest.php)):

1. Guardar acta con 2 cambios por equipo → filas creadas y minutos calculados correctamente.
2. Cambio con `player_out_id == player_in_id` → 422 y nada escrito.
3. Cambio con jugador de otro equipo → 422.
4. Minuto fuera de rango → 422.
5. Re-guardar el acta (corrección) → sin duplicados y minutos recalculados.
6. Quitar a un jugador de la alineación → se borran sus cambios y sus stats.
7. Con el preset 2×20: titular sin cambios → 40 min; suplente que entra en el minuto 25 → 15.
8. Override manual de minutos persiste y no lo pisa el cálculo.
9. Web y móvil producen el mismo resultado ante el mismo payload.
10. Partido de eliminación con `extra_time_played = true` (preset 2×20 + 2×10): titular sin cambios →
    60 min; suplente que entra en el minuto 5 del 1er suplementario (absoluto 45) → 15 min.
11. Un cambio con `period = extra_first` en un partido con `extra_time_played = false` → 422.
12. Partido de liga empatado: la prórroga no se ofrece y el empate se guarda como resultado válido.
13. Edición configurada en 2×45 → titular sin cambios = 90 min; y un partido con
    `half_minutes_override = 30` → 60 min, sin afectar al resto de la edición.

Comprobación manual: `npx vite build` del módulo, acta completa de un partido de prueba y revisión
del ranking del torneo con la columna de minutos.

---

## 12. Riesgos y decisiones abiertas

| Tema | Riesgo | Postura propuesta |
| --- | --- | --- |
| Duración del partido | Un valor fijo en código deja el sistema rígido: hay torneos de 2×45 y otros de 2×20 o menos. | **Configurable en la edición y sobrescribible por partido**; los defaults son solo un preset. Los minutos se derivan de la duración efectiva resuelta. |
| Prórroga | Activarla siempre o en liga complica y confunde. | **Opcional y apagada por defecto**; solo se ofrece en fases de eliminación directa cuando el organizador la habilita. |
| Preset vs. configuración | Confundir el preset (2×20) con una regla del sistema. | El preset solo evita quedar sin referencia; la fuente de verdad es la edición, y el partido puede pisarla. |
| Goles en la prórroga | Se podrían confundir con los del tiempo reglamentario. | `score_h`/`score_a` es el final (incluye prórroga); `score_regular_*` guarda el empate del minuto 40. |
| Limite de cambios | Cada liga tiene su regla. | `max_substitutions` solo avisa, no bloquea, para no frenar al operador. |
| Minutos manuales vs. calculados | Mezclar ambos confunde. | Override explícito por jugador; si es manual, se respeta y se marca. |
| Doble vía de escritura (web/móvil) | Divergencia de lógica. | Un único `MatchPerformanceService` reutilizado por ambos controladores. |

---

## 13. Archivos que se tocarían (resumen)

- Nuevos: 3 migraciones, `EventEditionMatchSubstitution`, `MatchPerformanceService`, `MatchSubstitutionRequest`, tests Pest.
- Modificados: [EventEditionMatchController.php](../Modules/Socialevents/Http/Controllers/EventEditionMatchController.php),
  [MatchAdminController.php](../Modules/Socialevents/Http/Controllers/Api/MatchAdminController.php),
  [EventEdition.php](../Modules/Socialevents/Entities/EventEdition.php),
  [RoundRobinPlayoff.vue](../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/RoundRobinPlayoff.vue),
  [SingleElimination.vue](../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/SingleElimination.vue),
  [TournamentRankingsService.php](../Modules/Socialevents/Services/TournamentRankingsService.php),
  [PlayerStatsApiController.php](../Modules/Socialevents/Http/Controllers/Api/PlayerStatsApiController.php),
  y en la app (`aracode-movil`): `lib/presentation/pages/match_result_page.dart`,
  `lib/data/models/admin_match_model.dart`, `lib/core/services/api_service.dart`.

---

## 14. Lo implementado (evidencia)

**F1 · Datos y lógica**

- Migraciones: `2026_10_11_000001_create_event_edition_match_substitutions_table`,
  `2026_10_11_000002_add_match_minutes_settings_to_event_editions`,
  `2026_10_11_000003_add_extra_time_to_event_edition_matches`.
- Entidad `Entities/EventEditionMatchSubstitution.php`; `fillable` y `casts` nuevos en `EventEdition`
  (tiempos y prórroga) y en `EventEditionMatch` (override por partido, `extra_time_played`,
  `score_regular_h/a`), más la relación `substitutions()`.
- Servicio [MatchPerformanceService.php](../Modules/Socialevents/Services/MatchPerformanceService.php):
  `resolvedTiming()` (partido → edición → preset, sin minutos fijos), `prepareSubstitutions()`
  (valida y calcula el minuto absoluto), `syncSubstitutions()`, `computeMinutes()`, `roster()`.
- [EventEditionMatchController.php](../Modules/Socialevents/Http/Controllers/EventEditionMatchController.php):
  valida los cambios **antes** de escribir (si algo falla, el acta no se guarda), persiste cambios,
  prórroga y overrides, calcula minutos y respeta el valor manual (`minutes_manual`). El `destroy()`
  borra también los cambios.
- [MatchAdminController.php](../Modules/Socialevents/Http/Controllers/Api/MatchAdminController.php):
  mismo contrato para la app (misma lógica, sin duplicar criterios).
- [EventEditionController.php](../Modules/Socialevents/Http/Controllers/EventEditionController.php):
  guarda los tiempos del torneo al crear y editar la edición.

**F2 · Captura web**

- [RoundRobinPlayoff.vue](../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/RoundRobinPlayoff.vue):
  columna **Minutos** por jugador (auto/manual), bloque **Cambios y minutos** por equipo
  (sale / entra / tiempo / minuto / quitar), selector de tiempo generado desde la configuración,
  interruptor **Se jugó prórroga**, override de minutos por partido y recálculo en vivo.
- [CreateForm.vue](../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/CreateForm.vue) y
  [EditForm.vue](../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/EditForm.vue):
  sección **Tiempos del torneo** (minutos por tiempo, cantidad de tiempos, suplementarios, límite de
  cambios y prórroga **apagada por defecto**).

**F4 · Parcial**

- [PlayerStatsApiController.php](../Modules/Socialevents/Http/Controllers/Api/PlayerStatsApiController.php):
  `SUM(minutes_played)` y filtro `minutes` en el ranking público.

**Verificación ejecutada**

| Comprobación | Comando | Resultado |
| --- | --- | --- |
| Migraciones en el entorno local | `php artisan migrate --force` | 3 migraciones `DONE` |
| Tiempos resueltos con datos reales | `php artisan tinker` | `{half_minutes:20, halves:2, duration:40}` para una edición existente |
| Tests del módulo | `php -d extension=pdo_sqlite vendor/bin/pest tests/Feature/Socialevents` | **29 passed (102 assertions)** |
| Build del frontend | `npx vite build` | ✓ built, exit 0 |

**Arreglos colaterales necesarios para poder correr los tests** (el suite completo no ejecutaba:
usaba SQL crudo de MySQL contra la base sqlite de `phpunit.xml`). Se aplicó el patrón que el repo ya
usa en otras migraciones (`getDriverName()`), sin cambiar el comportamiento en MySQL:

- `2026_09_29_000001_add_no_points_status_to_event_edition_matches_table` (Socialevents)
- `2026_10_03_000011_create_aca_school_fees_system` y `2026_10_03_000012_add_sale_document_to_aca_school_charges` (Academic)

**Pendiente**

1. **F3 · App móvil**: sección de cambios y envío de `substitutions_h/a` (el backend ya los acepta).
2. **F4 · UI pública**: columna de minutos y cambios en la landing, y la columna en el ranking web.
3. **Acta en fases de eliminación directa** (hoy el bracket solo registra el marcador).
4. **E2E del modal** en el navegador: no se pudo hacer clic real sin credenciales de la aplicación;
   la lógica de minutos del cliente replica la del servicio, que sí está cubierta por tests.

**Decisión abierta que sigue en pie**: qué hacer con los minutos ya guardados si se cambian los
tiempos de una edición con partidos cerrados (recalcular o congelar lo guardado).

---

## 15. Detalle del gol por jugador y detalle de atajadas (evidencia)

**Detalle del gol** (entrega anterior, sigue vigente)

- Tabla `event_edition_match_goals` (una fila por gol) + migración
  `2026_10_12_000002_add_goal_type_to_event_edition_match_goals` y entidad
  `Entities/EventEditionMatchGoal.php`. El tipo (`goal_type`) y el asistente (`assist_player_id`)
  viven **dentro del gol**: no hay contadores paralelos que puedan contradecirse.
- Regla del acta: **cuando el detalle viaja, el detalle manda**. `goals`, `assists` y `saves` de
  `event_edition_match_player_stats` se derivan del detalle (`goalsMapForRoster()`,
  `assistsMapForRoster()`, `savesMapForRoster()`); si el acta no envía el detalle (app móvil
  antigua), se conserva el contador que envía, como antes.
- El modal de goles del acta se abre **para el jugador en el que se hizo clic**: muestra solo a ese
  jugador con el nombre de su equipo y sus goles, sin el combo de autor. El botón de la pelota de la
  fila registra el gol de ese jugador y abre su detalle.

**Detalle de las atajadas (nuevo)**

- Migración `2026_10_12_000003_create_event_edition_match_saves_table` y entidad
  [EventEditionMatchSave.php](../Modules/Socialevents/Entities/EventEditionMatchSave.php). Una fila
  por atajada: arquero (`player_id`), equipo (`team_id`), tiempo (`period`, admite
  `penalty_shootout`), minuto dentro del tiempo y **minuto absoluto**, tipo (`save_type`), si fue
  penal (`is_penalty`) y **quién la pateó** (`shooter_player_id`, jugador del equipo rival).
- [MatchPerformanceService.php](../Modules/Socialevents/Services/MatchPerformanceService.php):
  `SAVE_TYPES` (`shot`, `one_on_one`, `header`, `olympic`, `other`), `PENALTY_SHOOTOUT_PERIOD`,
  `availableSavePeriods()`, `prepareSaves()` (valida arquero del acta, pateador rival, tiempo y
  minuto; descarta el pateador que ya no está en el acta, igual que la asistencia de un gol),
  `syncSaves()`, `savesCountByPlayer()`, `savesMapForRoster()` y `savesForMatch()`.
- Reglas propias de la atajada: la **tanda de penales** solo se acepta si el partido la jugó
  (`penalties`) y **no guarda minuto de juego**; la prórroga solo si `extra_time_played`.
- [EventEditionMatchController.php](../Modules/Socialevents/Http/Controllers/EventEditionMatchController.php)
  y [MatchAdminController.php](../Modules/Socialevents/Http/Controllers/Api/MatchAdminController.php):
  reciben `saves_h` / `saves_a` con el mismo guardado (`hasSaves`); la vista del acta recibe
  `match.saves` y la API de la app también lo devuelve.
- [MatchScore.vue](../Modules/Socialevents/Resources/assets/js/Pages/Editions/MatchScore.vue): el
  stepper de atajadas de la fila del jugador se reemplaza por un **botón con el guante** que muestra
  el total y abre el **modal de atajadas** (mismo formato que el de goles) con el equipo y el arquero
  ya seleccionados; dentro se registra cómo fue, si fue penal, quién la pateó y en qué tiempo, más un
  enlace con los minutos de cada atajada.

**Verificación ejecutada**

| Comprobación | Comando | Resultado |
| --- | --- | --- |
| Migración en el entorno local | `php artisan migrate --force` | `2026_10_12_000003_create_event_edition_match_saves_table` DONE |
| Suite completa | `php-8.3.21 .../php vendor/bin/pest` | **309 passed (1491 assertions)** |
| Tests del acta | `pest tests/Feature/Socialevents/MatchPerformanceTest.php` | **53 passed** (10 nuevos de atajadas) |
| Build del frontend | `npx vite build` | ✓ built, exit 0 · `MatchScore-DHv_me5l.js` contiene el modal |

**Ajuste posterior en el acta web**

- Se quitó la columna **Minutos** de la lista de jugadores de
  [MatchScore.vue](../Modules/Socialevents/Resources/assets/js/Pages/Editions/MatchScore.vue). Ojo: esa columna **no** era el
  minuto del gol (ese se registra en el modal del gol) sino `minutes_played`, la estadística que alimenta los rankings.
  Los minutos **siguen calculándose y guardándose solos** desde la alineación y los cambios; lo que desapareció de la web
  es la corrección manual del número (el `manual`), y con ella el botón "Recalcular minutos" del modal de cambios, que
  solo deshacía esa marca. La app móvil conserva su campo de minutos y el backend sigue respetando `minutes_manual`
  cuando llega marcado.

**Pendiente**

1. **App móvil**: el acta móvil todavía usa el stepper de atajadas y no envía `saves_h`/`saves_a`.
   El backend conserva su contador mientras no mande el detalle, pero para que el detalle sea la
   única fuente también allí falta su modal (y el envío de goles, que ya hace).
2. **Reportes**: exponer las atajadas por tipo (penal, mano a mano, cabeza, tiro olímpico) y por
   tiempo en los reportes del partido y en la landing pública.

---

## 16. Revisión del detalle de goles y atajadas: qué se rompía y qué se corrigió

Revisión pedida: comprobar que registrar goles y el detalle de portero no rompiera nada de lo que ya
funcionaba. Se revisaron los dos escritores del acta (web y app), el ranking público, la landing y la
tabla de posiciones. No había roturas en el guardado, pero sí **tres huecos reales** que se
corrigieron, y un reparto del ranking que ya no se sostenía:

| Hueco | Qué pasaba | Corrección |
| --- | --- | --- |
| Gol en propia puerta | La casilla *En propia puerta* se guardaba pero **no cambiaba nada**: el gol se le sumaba al jugador que lo metió en su arco, así que aparecía en la tabla de goleadores y en el ranking con 3 puntos por un gol que no hizo. Lo mismo con la asistencia, que no existe en un gol en propia. | `goalsCountByPlayer()` y `assistsCountByPlayer()` ignoran las filas con `is_own_goal`. El gol sigue en el detalle del partido (es un gol del juego) y en [MatchScore.vue](../Modules/Socialevents/Resources/assets/js/Pages/Editions/MatchScore.vue) se avisa "N en propia puerta" en la fila del jugador para que no quede invisible al corregir. |
| Minutos del móvil | Al pasarlos a calcular en el servidor, el número escrito en la app (que **no** marca `minutes_manual` ni envía `substitutions_h`) quedaba pisado: un suplente con 30 minutos escritos a mano se guardaba en 0. | Los minutos calculados solo mandan **cuando el acta envía el bloque de cambios**, igual que los goles y las atajadas. Sin ese bloque se conserva el valor del formulario; la web, que siempre lo envía, sigue recalculando. |
| Caché de la landing | Guardar desde la app no invalidaba `TournamentLandingCache`, así que la tabla y los rankings actualizados podían seguir mostrando el estado anterior (la web sí lo invalidaba). | `saveMatchResult()` del API suelta la caché de la edición al terminar. |

**Ranking de jugadores: el que arquero se reconoce por su posición y por el detalle**

[TournamentRankingsService.php](../Modules/Socialevents/Services/TournamentRankingsService.php) partía
las listas con `saves > 0`: un arquero que jugó sin atajar quedaba entre los **jugadores de campo** (y
con puntaje 0 confundía la lista), y cualquier jugador de campo que atajara una vez aparecía como
arquero. Ahora la clasificación sale de los datos que registró la persona:

- es arquero si su **posición registrada** es de arquero (`Arquero`, `Guardameta`, `Portero`, `Golero`,
  `goalkeeper`, `GK`, `ARQ` — la misma lista que usa la app móvil en `MatchAdminModel.isGoalkeeper`);
- si la plantilla no tiene posición cargada, el respaldo es el **detalle de atajadas** (quien atajó
  jugó de arquero), que es lo que hacía antes;
- el partido de un arquero sin atajadas ahora **sí le cuenta** en su lista (su valla invicta y su MVP),
  y en la de jugadores de campo ya no aparece;
- `playerDetail()` (el modal de la landing) usa la misma clasificación, así que el puntaje del detalle
  sigue coincidiendo con el de la tarjeta.

Los **pesos del puntaje no cambiaron** a propósito: la fórmula sigue en
[config.php](../Modules/Socialevents/Config/config.php) y la landing y la app solo pintan lo que
devuelve el servicio, de modo que no hay dos fórmulas que puedan divergir.

**Verificación ejecutada**

| Comprobación | Comando | Resultado |
| --- | --- | --- |
| **Suite completa** | `pest` (PHP 8.3 de Laragon) | **319 passed (1528 assertions)**, exit 0, 509 s |
| Tests del módulo (incluye los nuevos) | `pest tests/Feature/Socialevents` | **72 passed (316 assertions)**, exit 0 |
| Nuevo `TournamentRankingsTest` | `pest tests/Feature/Socialevents/TournamentRankingsTest.php` | **6 passed** (6 nuevos) |
| Nuevos casos del acta | `pest tests/Feature/Socialevents/MatchPerformanceTest.php` | 3 nuevos: minutos del móvil, minutos de la web, gol en propia puerta |
| Build del frontend | `npx vite build` | ✓ built, exit 0 · `MatchScore-BvV3hyzR.js` contiene la marca de propia puerta |
| App móvil | `flutter analyze --no-pub` | 0 errores y ningún aviso en `match_result_page.dart` (los 186 avisos del reporte son previos y de otros archivos) |

**Pendiente / siguiente decisión**

1. **La posición manda sobre las atajadas para clasificar**: hoy una persona con posición de campo que
   ataja sigue contando como arquero (comportamiento de antes), para que nadie desaparezca de donde
   estaba. Si se quiere que la lista se parta **solo** por la posición registrada, es un cambio de una
   línea.
2. **El detalle todavía no pesa por sí mismo** en el puntaje: una atajada de penal vale igual que
   cualquier otra (`saves × 0.5`) y un gol de penal vale igual que uno de jugada, aunque el detalle ya
   los distingue (`is_penalty`, `goal_type`). Ponerles peso propio es decisión del negocio.
3. **La suite completa tarda ~8,5 minutos y se queda muda varios minutos** mientras corre
   `Tests\Unit\Database\FreshInstallSeederTest` (Pest solo vuelca el bloque del archivo cuando termina,
   así que parece colgada sin estarlo). Con el PHP 8.3 de Laragon cierra entera:
   `pest` → **319 passed (1528 assertions)**, exit 0, 509 s. Para revisar rápido durante el desarrollo,
   `pest tests/Feature/Socialevents` (48 s) es el atajo sin perder cobertura de estos cambios.
4. **El listado de partidos no tiene test**: la página del fixture usa `orderByRaw("NOW()")` de MySQL y
   en sqlite (el motor de las pruebas) esa función no existe, así que el listado no se puede renderizar
   en pruebas. El armado del partido (`buildMatchPayload()`) sí queda cubierto por los tests de la vista
   dedicada del acta, que es la misma función.

