# Plan — Registro de asistencia en portería por QR

Estado: **Fase 1 implementada** (ver §14 para la evidencia y lo que queda pendiente).
Módulo: `Modules/Academic` · Módulo escolar (`aca_school_*`).

### Decisiones ya tomadas

| Decisión | Resuelto |
| --- | --- |
| Lector de QR en el navegador | **`jsqr`** como camino principal + **`BarcodeDetector`** nativo como atajo cuando exista. Cubre iPad/iPhone, Android, Chrome, Edge y Firefox. |
| Alcance | **Entrada y salida desde el inicio.** La entrada es el flujo principal; la salida existe y es **opcional**: no marcarla no genera falta, tardanza ni penalidad alguna. |
| Usuario de la tablet de la puerta | **Rol dedicado** (`Portero`) con un permiso propio de escaneo; sin acceso a notas, cobros ni matrículas. |

---

## 1. Objetivo

Una sola pantalla web, responsive, que un operador abre en **celular, tablet, laptop o PC de
escritorio** y que registra la asistencia del alumno al mostrar su **carné con QR** frente a la
cámara. Debe funcionar **también con pistola lectora** (las que se comportan como teclado) y ser
**lo bastante rápida para que no se forme cola en la puerta**.

La pantalla no pide confirmación: cada lectura correcta muestra un *toast* con check que se
**disuelve solo** y deja lista la siguiente lectura.

### Contexto ya existente que se reutiliza

| Pieza | Dónde | Qué aporta al plan |
| --- | --- | --- |
| QR del carné | [carnet_item.blade.php](../Modules/Academic/Resources/views/cards/partials/carnet_item.blade.php#L40) | El payload del QR **ya está impreso**: es el `student_code` (p. ej. `2026-0001`). El escáner debe aceptar exactamente ese valor, sin reimprimir carnés. |
| Generación del QR | [AcaSchoolStudentController.php:277](../Modules/Academic/Http/Controllers/AcaSchoolStudentController.php#L277) | `endroid/qr-code`, hoy duplicado en carné individual y masivo. Se extrae a un único helper (ver §7). |
| Tabla de asistencia de aula | `aca_school_attendances` | Ya tiene `unique(enrollment_id, attendance_date)` → idempotencia gratis y estado `A/T/J/F`. **No se modifica.** |
| Asistencia institucional (nueva) | `aca_school_gate_attendances` | Tabla ya creada en esta iteración: una fila por alumno y día con `entry_at`, `entry_status`, `exit_at`, `early_exit`, `scans_count` y `last_event`. Ver §6. |
| Jornada y tolerancia | [AcaSchoolJourney::forSection()](../Modules/Academic/Entities/AcaSchoolJourney.php#L68) | Devuelve `entry_time` y `tolerance_minutes` por nivel/turno. De aquí sale si la marca es **A** (asistencia) o **T** (tardanza). Su propio docblock ya anticipa el uso en portería. |
| Permisos y rutas | `Modules/Academic/Routes/web.php`, `PermissionTableSeeder.php` | Patrón ya establecido para registrar permiso + ruta + entrada de menú. |

---

## 2. Criterios de aceptación

1. `GET /academic/school/gate/scanner` abre una vista usable en 320 px de ancho (celular) y en
   pantalla completa de PC, sin scroll horizontal ni controles fuera de vista.
2. Con la **cámara** (webcam integrada, USB o trasera del celular) la lectura de un QR del carné
   registra la asistencia del día; el *toast* aparece sin ningún botón y desaparece solo.
3. Con una **pistola lectora** en modo teclado (HID), disparar sobre el carné registra la
   asistencia **sin necesidad de hacer clic en ningún campo** de la pantalla.
4. El mismo alumno escaneado dos veces el mismo día **no duplica filas** y avisa
   "ya registrado a las HH:MM".
5. Un código inexistente / de otro colegio / sin matrícula activa muestra el error correspondiente
   **sin** dejar la pantalla bloqueada.
6. La marca de **entrada** es `T` (tardanza) si la lectura supera `entry_time + tolerance_minutes`;
   `A` (asistencia) si está dentro. La lectura de **salida** se registra con su hora y **no**
   altera la letra del día.
7. Se guarda `user_id_registers` (quién operó la portería) en cada marca y `user_id` en cada evento
   de portería.
8. Ciclo percibido por escaneo **< 500 ms** y respuesta del endpoint **< 150 ms** con 200 alumnos
   seguidos (medido, ver §11, punto 11).

---

## 3. Modos de entrada (los tres conviven en la misma pantalla)

La vista detecta el modo por sí sola; el operador no configura nada para empezar.

### 3.1 Cámara del dispositivo (celular / tablet / laptop / PC con webcam)
- `navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })` (la trasera en
  celular/tablet; la integrada o USB en laptop/PC).
- `getUserMedia` exige **contexto seguro (HTTPS o localhost)**: en producción ya se sirve HTTPS y
  en local el proyecto usa `@vitejs/plugin-basic-ssl`; el plan lo verifica explícitamente.
- Se decodifica sobre un `<canvas>` reducido (≈ 320×240) a ~10 fps en lugar de la resolución
  completa: basta para leer un QR de 24 mm a 20-30 cm y baja el consumo de CPU.
- **Pausa tras acierto**: al leer con éxito se detiene la captura ~800 ms y se limpia el buffer.
  Sin esto, el QR se decodifica en 10 fotogramas seguidos y el mismo alumno se envía 10 veces.

### 3.2 Pistola lectora (keyboard wedge / HID)
- Se configura la pistola en **modo teclado (HID)**, no en USB-COM: el sistema recibe los dígitos
  como si alguien los tecleara, terminados en `Enter` (algunas envían `Tab`).
- La pantalla escucha `keydown` **a nivel de documento**, sin exigir foco en un campo:
  acumula caracteres en un buffer y cierra el código con `Enter`/`Tab`. Un *timeout* de ~120 ms
  sin teclas descarta el buffer (evita que el tecleo humano normal se tome como código).
- Se acepta cualquier pistola 1D/2D que lea el QR y teclee su contenido; **no** se añade
  decodificación de códigos de barras 1D en JavaScript (la pistola ya lo hace).

### 3.3 Campo manual (respaldo)
- Input visible "Escribir código a mano" para cuando fallan cámara y pistola, con el mismo
  endpoint. Se conserva como último recurso, discretamente colocado.

---

## 4. Flujo de pantalla

Una sola pantalla, sin pasos ni modales:

```
┌──────────────────────────────────────────────┐
│ Portería · I.E. …            [modo: Entrada] │
│                                              │
│      ┌──────────────────────────┐            │
│      │   cámara / visor QR      │  ← grande,  │
│      │                          │    con guía │
│      └──────────────────────────┘            │
│   Escaneados hoy: 137 · A 129 · T 8          │
│   Último: JUAN PÉREZ · 3° A · 07:42  (A)     │
│                              [Activar cámara]│
└──────────────────────────────────────────────┘
```

- **Responsive**: una columna en celular (cámara arriba, último escaneado debajo, texto grande);
  dos columnas en tablet/PC (cámara al centro, panel lateral con último escaneo y contador).
- El **último escaneado** queda fijo en pantalla en tipografía grande para que el operador —y el
  propio alumno— confirmen a simple vista.
- **Flash de color** a pantalla completa 250 ms (verde/ámbar/rojo) además del toast: el check solo
  no se lee a 2 m de distancia en una puerta.
- **Sonido y vibración**: *beep* corto distinto por resultado y `navigator.vibrate` en móvil.
  Con interruptor para silenciar (accesibilidad y turnos de examen).
- **Sin botón de aceptar**: el toast es de SweetAlert2 (`sweetalert2` ya está en el proyecto) con
  `toast: true, timer: 1200, showConfirmButton: false, timerProgressBar: true`, esquina superior.
- Estados: verde (*check* + nombre + sección + hora) · ámbar ("ya estaba registrado a las 07:42") ·
  rojo ("código no encontrado", "alumno sin matrícula activa en 2026", "no pertenece a este
  colegio") · gris si falla la red, con **reintento automático** del mismo código.
- **Conmutador Entrada / Salida** visible y grande, con la tecla **F2** como atajo (no se usa
  `Espacio` porque el espacio forma parte del código que teclea la pistola) y la Entrada por
  defecto.
- **Contador del día** (total / asistencias / tardanzas / salidas) alimentado por la respuesta del
  endpoint y por un `GET` inicial, para no consultar la base en cada lectura.

---

## 5. Backend

### 5.1 Endpoint de escaneo

`POST /academic/school/gate/scan` · JSON · permiso `aca_school_porteria_escaner`.

Entrada (tolerante, porque el origen es una pistola):
```json
{ "code": "2026-0001", "mode": "in" }
```
Normalización: `trim` + mayúsculas + quitar `\r\n` y espacios internos (las pistolas agregan
retorno de carro). Se conserva el payload **tal como está impreso hoy** (`student_code`).

La hora que se guarda es **la del servidor**, no la del equipo de la puerta: así todas las
lecturas son comparables entre sí y no se puede retrasar una marca desde un reloj manipulado. Por
eso el cuerpo no lleva `scanned_at`.

Resolución en este orden, todo acotado al colegio del contexto:
1. `AcaSchoolStudent` por `school_id` + `student_code` (índice compuesto, §6).
2. Si no aparece: reintento por `person.number` (DNI, si el colegio imprimió otro carné) —
   **opcional**, queda tras una bandera porque el DNI puede colisionar entre colegios.
3. Matrícula **activa** del año escolar activo → de ahí salen `year_id` y `section_id`.
4. Estado: `A` o `T` comparando la hora de la lectura con
   `AcaSchoolJourney::forSection($seccion)` → `entry_time + tolerance_minutes`.
   Si no hay jornada configurada para el nivel/turno, se registra `A` y se anota en el log.

Respuesta (mínima y honesta):
```json
{ "result": "ok",              // ok | duplicate | not_found | no_enrollment | error
  "student": "JUAN PÉREZ QUISPE",
  "code": "2026-0001",
  "section": "3° A · Secundaria",
  "status": "A", "time": "07:42",
  "duplicate": false,
  "counters": { "total": 138, "a": 130, "t": 8, "out": 96 } }
```

### 5.1.1 Entrada y salida sobre la asistencia institucional

`mode: 'in' | 'out'`. El operador alterna con un conmutador grande y visible en la pantalla; por
defecto la pantalla abre en **Entrada**.

Todo se escribe en **`aca_school_gate_attendances`** (una fila por alumno y día), nunca en la
tabla de aula salvo por la marca `A/T` que genera la entrada:

**Entrada**
1. No existe la fila del día → se crea con `entry_at`, `entry_status` (`A` o `T` según la jornada),
   `scans_count = 1`, `last_event = 'in'`, y se hace `upsert` de la marca de aula en
   `aca_school_attendances` guardando su id en `classroom_attendance_id`.
2. Ya existe la fila con `entry_at` → `duplicate`: se devuelve la hora original y solo se incrementa
   `scans_count`. No se reescribe la hora ni la letra.

**Salida** — actualiza la misma fila del día, **sin tocar la letra de aula**.
- `exit_at` pasa a la hora del escaneo, `last_event = 'out'` y `scans_count` incrementa.
- Nunca cambia la letra `A/T/J/F` ni genera falta: si nadie escanea la salida, la fila queda con
  `exit_at` nulo y no ocurre nada.
- Si la salida es antes de `exit_time` (menos un margen configurable), `early_exit = true` para que
  dirección lo revise; en Fase 1 solo se avisa en pantalla, no se exige motivo.
- Varias pasadas por la puerta (recreo, trámites) se cuentan en `scans_count`; la **primera** entrada
  y la **última** salida del día son las que quedan en la fila.

Esta tabla es la asistencia de la **institución**; la de aula sigue siendo la planilla mensual
SIAGIE del docente. Son dos registros distintos y por eso no comparten tabla.

### 5.2 Idempotencia y concurrencia
- La asistencia institucional del día se hace con `updateOrCreate(['enrollment_id', 'attendance_date'], …)`
  sobre `aca_school_gate_attendances`, apoyado en su índice único. Si dos porterías escanean al mismo
  alumno a la vez, una de las dos inserciones choca contra el índice: se captura `QueryException` de
  clave duplicada y se responde `duplicate` en lugar de un 500.
- La marca de aula se hace con `updateOrCreate(['enrollment_id', 'attendance_date'], …)` sobre
  `aca_school_attendances` (mismo índice único que ya existía).
- Segunda lectura de entrada del día → `duplicate: true` con la hora original; **no** sobrescribe una
  marca que el docente ya haya puesto (p. ej. `J` justificada por dirección).
- La hora exacta del escaneo queda en `entry_at` / `exit_at`; `attendance_date` guarda solo la fecha.

### 5.3 Permisos, rutas y menú
- Migración nueva siguiendo el patrón del módulo
  (`2026_10_01_000004_add_aca_smsgate_permissions.php`): `firstOrCreate` del permiso, asignación al
  rol `admin` y registro en `model_has_permissions` para el módulo `M007`.
  Permisos: `aca_school_porteria_escaner`, `aca_school_porteria_reporte`.
- Se añaden también a `PermissionTableSeeder` (**idempotente**) y a
  `PermissionsReconcileSeeder`, como el resto del módulo.
- Rol nuevo **`Portero`** (o `Auxiliar de portería`) con solo el permiso del escáner: así la
  tablet de la puerta entra con un usuario de mínimos privilegios, no con la cuenta del director.
- Rutas (con el mismo estilo del archivo):
  ```php
  Route::middleware(['middleware' => 'permission:aca_school_porteria_escaner'])
      ->get('school/gate/scanner', [AcaSchoolGateController::class, 'scanner'])->name('aca_school_gate_scanner');
  Route::middleware(['middleware' => 'permission:aca_school_porteria_escaner'])
      ->post('school/gate/scan', [AcaSchoolGateController::class, 'scan'])->name('aca_school_gate_scan');
  Route::middleware(['middleware' => 'permission:aca_school_porteria_escaner'])
      ->get('school/gate/day', [AcaSchoolGateController::class, 'day'])->name('aca_school_gate_day');
  Route::middleware(['middleware' => 'permission:aca_school_porteria_reporte'])
      ->get('school/gate/report', [AcaSchoolGateController::class, 'report'])->name('aca_school_gate_report');
  ```
- Entrada de menú "Portería — Escanear asistencia" en
  [Menu.js](../Modules/Academic/Resources/assets/js/Menu.js) junto a "Registro de Asistencias",
  visible solo con el permiso del escáner.

---

## 6. Base de datos

- **Índice necesario**: `aca_school_students (school_id, student_code)` — la búsqueda es por código
  en cada lectura, no puede hacer *full scan*. Migración nueva con `Schema::table(... index(...))`.
  Verificar y anotar si `student_code` ya es único por colegio; si lo es, reutilizar ese índice.
- **Creada en esta iteración** ✅: `aca_school_gate_attendances` — la **asistencia institucional**
  (una fila por alumno y día), en
  [2026_10_09_000001_create_aca_school_gate_attendances_table.php](../Modules/Academic/Database/Migrations/2026_10_09_000001_create_aca_school_gate_attendances_table.php):
  `id, school_id, year_id, section_id, enrollment_id, student_id, attendance_date,
  entry_at (nullable), entry_status (A|T, nullable), exit_at (nullable), early_exit,
  scans_count, last_event ('in'|'out'), observations,
  classroom_attendance_id (nullable → aca_school_attendances), user_id_registers, timestamps`.
  Índices: `unique(enrollment_id, attendance_date)` (una asistencia institucional por alumno y día),
  `(school_id, attendance_date)` para el reporte del día y `(student_id, attendance_date)` para el
  historial del alumno. Modelo:
  [AcaSchoolGateAttendance](../Modules/Academic/Entities/AcaSchoolGateAttendance.php).
- **Sin migración de datos** en `aca_school_attendances`: se usa tal cual.

---

## 7. Cambios por archivo

| Archivo | Acción | Contenido |
| --- | --- | --- |
| `docs/PLAN_ASISTENCIA_QR.md` | hecho ✅ | Este plan. |
| `Modules/Academic/Services/GateScanService.php` | hecho ✅ | Normaliza el payload, resuelve alumno → matrícula → sección, calcula A/T con la jornada y hace el alta idempotente. Deja el controlador delgado y el servicio testeable sin HTTP. |
| `Modules/Academic/Http/Controllers/AcaSchoolGateController.php` | hecho ✅ | `scanner()`, `scan()`, `day()`, `report()` (CSV del día). Nada de lógica de negocio. |
| `Modules/Academic/Services/StudentCardQr.php` | hecho ✅ | **Única fuente del payload**: `payload()`, `normalize()` y `dataUri()`. El bloque de `chillerlan/qr-code` estaba duplicado en carné individual y masivo; ambos pasan a usarlo y el escáner queda atado al mismo contrato. |
| `Modules/Academic/Http/Controllers/AcaSchoolStudentController.php` | modificar ✅ | Las dos construcciones inline de `QRCode` pasan a `StudentCardQr::dataUri()`. |
| `Modules/Academic/Resources/assets/js/Pages/School/Gate/Scanner.vue` | hecho ✅ | Pantalla completa: conmutador Entrada/Salida, contadores, toasts que se disuelven solos, flash de color, sonido, vibración y layout responsive. |
| `…/Gate/Partials/QrCamera.vue` | hecho ✅ | `getUserMedia`, canvas reducido, `BarcodeDetector` + `jsQR`, pausa tras acierto, selector de cámara y mensajes de permiso denegado / sin cámara / sin HTTPS. |
| `…/Gate/Partials/GateScanFeed.vue` | hecho ✅ | Últimas lecturas del día y contadores. |
| `…/Gate/composables/useHidScanner.js` | hecho ✅ | Buffer de la pistola: `keydown` global, timeout de 120 ms, `Enter`/`Tab` como terminador e ignorado cuando el foco está en un campo. |
| `Modules/Academic/Routes/web.php` | modificar ✅ | 4 rutas con su permiso (§5.3). |
| `Modules/Academic/Database/Migrations/2026_10_09_000002_add_aca_school_porteria_permissions.php` | hecho ✅ | Permisos + rol `Portero`, enlazados al módulo M007. |
| `Modules/Academic/Database/Migrations/2026_10_09_000003_add_index_to_aca_school_students_code.php` | hecho ✅ | Índice `(school_id, student_code)`. |
| `Modules/Academic/Database/Migrations/2026_10_09_000001_create_aca_school_gate_attendances_table.php` | **hecho** ✅ | Asistencia institucional: una fila por alumno y día con entrada, salida y contador de pasadas (§6). |
| `Modules/Academic/Entities/AcaSchoolGateAttendance.php` | **hecho** ✅ | Modelo de la asistencia institucional: constantes de evento, estados de entrada, relaciones y ayudantes `hasEntered()` / `isInside()` / `entryStatusLabel()`. |
| `Modules/Academic/Database/Seeders/PermissionTableSeeder.php` | modificar | Permisos idempotentes + `Portero`. |
| `database/seeders/PermissionsReconcileSeeder.php` | modificar | Reconciliación para instalaciones existentes. |
| `Modules/Academic/Resources/assets/js/Menu.js` | modificar | Entrada "Portería". |
| `package.json` | modificar | Solo la dependencia de decodificación (§8). |

---

## 8. Dependencia para decodificar el QR

El proyecto ya tiene `qrcode` (generación), y la pistola no decodifica nada en JS, así que **no hay
lector de QR en el navegador todavía**: hay que añadir uno. Decidido: **`jsqr`** como camino
principal y **`BarcodeDetector`** nativo como atajo cuando exista.

| Opción | Peso | Cobertura | Notas |
| --- | --- | --- | --- |
| **`jsqr`** (recomendada) | ~40 KB, 0 dependencias | Chrome, Edge, Safari (incl. iOS), Firefox | API simple: `jsQR(imageData, w, h)`. Es *solo* QR, que es justo lo que se necesita. |
| `@zxing/browser` | ~200 KB | Muy amplia, incl. 1D | Útil solo si algún día se quieren leer códigos de barras con la cámara; hoy la pistola cubre eso. |
| `BarcodeDetector` nativo | 0 KB | **No** en iOS Safari ni Firefox hoy | Se usa como **atajo** cuando existe (Android/Chrome), con `jsqr` como respaldo. Riesgo de depender solo de él: la mitad de las tablets del colegio fallarían. |

Propuesta: **`jsqr` como principal + `BarcodeDetector` nativo como atajo opcional**. Es una
dependencia ya normalizada en el ecosistema Vue/Vite y no toca el *bundle* PHP.

---

## 9. Rendimiento (objetivo: no hacer cola)

Presupuesto por alumno, 1 operador, 1 cámara:

| Etapa | Objetivo | Cómo se logra |
| --- | --- | --- |
| Detección del QR | 150-400 ms | Canvas 320×240 a ~10 fps; el alumno acerca el carné, no hay que encuadrar. |
| Envío y guardado | < 150 ms | Una sola consulta indexada + un `upsert`; sin consultas por relación dentro del bucle. |
| Retroalimentación | inmediata | El toast de éxito es visual e inmediato; no se espera confirmación del usuario. |
| Repetición del mismo QR | — | *Dedupe* en cliente (`Set` de códigos con ventana de 5 s) + pausa de captura de 800 ms. |
| Reintento de red | — | Un reintento automático con el mismo código antes de mostrar el gris. |

Escenario objetivo de puerta: **200 alumnos en menos de 5 minutos** (~1,5 s por alumno, ritmo
realista con mochila y saludo). La medición está en §11 (punto 11) y se reporta el número obtenido,
no el esperado.

Fuera del alcance de la Fase 1 (pero anotado): *batch* de lecturas (enviar 5 códigos en un POST),
y *feedback* sonoro antes de tener la respuesta para ganar los ~100 ms de red.

---

## 10. Fases

**Fase 1 — MVP de entrada y salida (núcleo de lo pedido)** — **hecha ✅**
1. Helper `StudentCardQr` + endpoint `scan`/`day` + servicio + rutas con permiso. ✅
2. Migración de la asistencia institucional, índice de búsqueda por código y permisos. ✅
3. Vista `Scanner.vue` con cámara (`jsqr`), pistola (`useHidScanner`), **conmutador Entrada/Salida**,
   toast auto-dismiss, flash de color, contador y último escaneado. ✅
4. Permisos, rol `Portero` y entrada de menú. ✅
5. Verificación del backend y build. ✅ — queda la verificación en navegador (§14).

**Fase 2 — Operación real de portería**
- Reporte del día exportable (CSV/PDF) con las lecturas de portería y comparativo contra la
  asistencia que marcó el docente.
- Motivo obligatorio para la salida anticipada, con aviso al apoderado.
- Modo kiosco (pantalla completa, sin menú) + PWA instalable en la tablet fija de la puerta.
- Aviso por SMS al apoderado cuando el alumno no ha registrado entrada a cierta hora
  (el módulo SMSGate ya existe).

**Fase 3 — Endurecimiento**
- Payload firmado (`ACA1.<base64>` con HMAC) para que un alumno no pueda imprimir el código de
  otro; el escáner acepta **los dos formatos** para no invalidar los carnés ya impresos.
- Cola sin conexión (IndexedDB) para cortes de red: se reenvía al volver la señal.
- Identificación por DNI (lector de DNI o QR del DNI) si el colegio lo prefiere.

---

## 11. Pruebas y verificación planeadas

**Backend (sin HTTP, con el mismo estilo de sondas ya usado en el proyecto)**
1. `student_code` válido → fila creada con `A` y `user_id_registers` correcto.
2. Mismo código dos veces el mismo día → `duplicate: true`, **una sola fila**.
3. Código inexistente / de otro colegio → `not_found`, sin excepción.
4. Alumno sin matrícula activa → `no_enrollment`.
5. Lectura simulada después de `entry_time + tolerance_minutes` → `T`; antes → `A`.
6. Dos POST simultáneos del mismo código → 1 fila, segunda respuesta `duplicate` (no 500).
7. **Base intacta**: transacciones revertidas y conteos antes/después iguales.

**Frontend**
8. `npm run build` con *exit status* real (sin tuberías que lo oculten).
9. Verificación en navegador con un QR real en pantalla (tablet y PC) mediante la vista previa del
   proyecto: lectura → toast → segunda lectura → toast ámbar → código falso → toast rojo.
10. **Pistola física**: confirmar que dispara sobre el carné sin clic previo y que el `Enter` del
    lector no envía nada raro. Requiere el hardware en la puerta — si no está disponible, se
    reporta como no verificado en vez de darlo por bueno.
11. Medición real de 30 escaneos seguidos: p50 y p95 del ciclo completo, y el tiempo de los 200
    alumnos si se puede simular.

**Límites a declarar explícitamente al cerrar**
- La cámara solo funciona en **HTTPS/localhost** (contexto seguro): se documenta para el despliegue.
- Safari de iOS no soporta `BarcodeDetector`; por eso `jsqr` es el camino principal.
- El QR actual (código correlativo) es **predecible**; hasta la Fase 3 un alumno podría usar el
  código de otro. Se documenta como riesgo aceptado y con plan de mitigación.

---

## 12. Riesgos

| Riesgo | Impacto | Mitigación |
| --- | --- | --- |
| Cámara del equipo de la puerta es de baja calidad o hay contraluz | No lee el QR | Guía de encuadre en pantalla, canvas con realce de contraste, y la pistola como respaldo. |
| La red de la puerta se cae justo a la hora de entrada | Cola | Fase 1: reintento automático. Fase 3: cola local. |
| Dos porterías registran a la vez | Choque de clave única | `updateOrCreate` + captura de duplicado → `duplicate`, nunca 500. |
| Un alumno pasa el QR de otro | Suplantación | Riesgo aceptado en Fase 1; Fase 3 con payload firmado. |
| Tablet de la puerta queda con sesión de un usuario con demasiados permisos | Seguridad | Rol `Portero` con un único permiso. |
| Sin jornada configurada para un nivel | No se distingue tardanza | Se registra `A` y se avisa en pantalla al administrador para que configure la jornada. |

---

## 13. Decisiones resueltas

1. **Lector de QR**: `jsqr` + `BarcodeDetector` nativo como atajo. ✔
2. **Alcance**: entrada y salida desde la Fase 1; la entrada es el flujo principal y la salida es
   opcional (no marcarla no genera penalidad). ✔
3. **Usuario de la puerta**: rol `Portero` dedicado con permiso propio. ✔

Detalles menores que se resuelven en la implementación, salvo indicación contraria:

- **Marca de tardanza** con `entry_time + tolerance_minutes` de la jornada, porque la tolerancia ya
  se configura por nivel y turno en el módulo de horario.
- **Ámbito del código**: por colegio (cada I.E. con su propio correlativo y su contexto activo).
- **Modo por defecto** de la pantalla: Entrada.

---

## 14. Estado de la implementación (Fase 1)

### Endpoints en servicio

| Ruta | Nombre | Permiso |
| --- | --- | --- |
| `GET academic/school/gate/scanner` | `aca_school_gate_scanner` | `aca_school_porteria_escaner` |
| `POST academic/school/gate/scan` | `aca_school_gate_scan` | `aca_school_porteria_escaner` |
| `GET academic/school/gate/day` | `aca_school_gate_day` | `aca_school_porteria_escaner` |
| `GET academic/school/gate/report` | `aca_school_gate_report` | `aca_school_porteria_reporte` |

### Evidencia recogida

- **Migraciones**: `aca_school_gate_attendances`, los permisos con el rol `Portero` y el índice
  `(school_id, student_code)` corrieron sin error; `db:table` confirma el índice compuesto.
- **Permisos en base**: `aca_school_porteria_escaner` → `admin, Administrador, Portero`;
  `aca_school_porteria_reporte` → `admin, Administrador`; el rol `Portero` tiene **un solo**
  permiso; los dos permisos están enlazados al módulo M007.
- **Rutas**: `route:list -v` muestra el `PermissionMiddleware` correcto en las cuatro.
- **Servicio** (sonda contra la base real, transacción revertida, base intacta al terminar):
  entrada dentro de jornada → `A`; segunda entrada del día → `duplicate` con la hora original;
  salida después de la hora oficial → `exit_at` y sin salida anticipada; entrada pasada la
  tolerancia → `T`; salida antes de la hora oficial → `early_exit = true`; salida sin entrada
  previa → fila con `entry_at` nulo; código inexistente → `not_found`; código con mayúsculas,
  espacios y retorno de carro (pistola) → reconocido; una marca `J` del docente **no se pisa** y la
  fila de portería la enlaza en `classroom_attendance_id`; matrícula retirada → `no_enrollment`.
  Una sola fila por alumno y día en los cinco días probados.
- **Build**: `npm run build` con salida 0; los chunks `Scanner` y `QrCamera` existen en cliente y
  SSR (con `jsqr` dentro) y las clases usadas por la pantalla están en el CSS compilado.

### Medición de la cola (hecha)

200 escaneos seguidos por el **kernel HTTP real** (routing + middleware de permisos + controlador +
servicio + MySQL), midiendo el tiempo de servidor de cada uno y revirtiendo todo al terminar:

| Métrica | Valor |
| --- | --- |
| Total de 200 escaneos | 6,87 s (34,4 ms por alumno) |
| Mínimo | 22,8 ms |
| **p50** | **27,8 ms** |
| **p95** | **39,9 ms** |
| Máximo | 831 ms (la primera lectura: arranque en frío del framework) |
| Escaneo repetido (camino `duplicate`) | 25,4 ms |
| Respuestas HTTP | 200 de 200 con `200` |

Los 200 alumnos de la prueba se simularon con matrículas reales y se revirtieron después: la base de
desarrollo quedó intacta. **El objetivo era no hacer cola y se cumple con holgura**: aun sumando la
lectura del QR (100-300 ms por alumno con el carné presentado a mano), un aula de 200 alumnos pasa
por la puerta en menos de un minuto de cómputo, contra los 5 minutos del objetivo.

La medición **no incluye el viaje de red** (es tiempo de servidor) ni el tiempo del operador
alcanzando el carné, que son los que dominan en la puerta real.

### Pendiente de verificar

- **La pantalla en el navegador**: la ruta responde 302 a `/login` (sesión expirada), así que la
  revisión visual y el escaneo real desde la interfaz quedan por hacer. Lo que falta confirmar ahí
  es el aviso en pantalla (toast y flash), no el registro, que ya está medido y probado.
- **Cámara y pistola físicas**: no probadas; el camino de la pistola (buffer HID) y el permiso de
  cámara solo se pueden confirmar en el equipo de la puerta.
- **Suite PHP del proyecto**: no corre en esta máquina. `phpunit.xml` fuerza sqlite en memoria y el
  PHP CLI solo tiene el driver `pdo_mysql`, así que `php artisan test` falla con *"could not find
  driver"* en pruebas que ya existían (`ProfileTest`). Es una condición previa, ajena a este
trabajo; por eso la verificación del endpoint se hizo con sondas contra la base real y no con
pruebas automatizadas.
- **Riesgo aceptado**: el QR actual es un código correlativo predecible; hasta la Fase 3 alguien con
  el código de otro alumno podría pasar. Mitigación planificada: payload firmado.
