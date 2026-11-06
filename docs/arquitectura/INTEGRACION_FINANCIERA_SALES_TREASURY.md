# Integración financiera Ventas → Tesorería

Documento de la capa de integración implementada sobre el código real del
proyecto. No describe funcionalidades futuras como si existieran: lo que está
pendiente aparece marcado explícitamente en la sección 15.

---

## 1. Objetivo y alcance

Unificar el registro del dinero que entra por Ventas en un único libro
(`treasury_transactions`), con una tabla puente que registra la operación tal
como la reporta el módulo de origen, su clave de idempotencia y el resultado de
su procesamiento.

**En alcance (implementado):** ventas al contado (nota de venta, comprobante y
venta rápida), ventas al crédito con cuota inicial opcional, abonos de cuota,
pago total de deuda y cobros del cronograma de pagos.

**Fuera de alcance de esta etapa (no implementado):** Compras, Proveedores, Caja
Chica, Académico y Contratos como orígenes. La tabla puente ya los admite
(`module`, `source_type`, `source_id`, `operation`) sin cambiar el esquema.

**No se modificó:** migraciones históricas, columnas existentes, facturación
electrónica, Greenter, ni la exclusión actual de métodos de pago.

---

## 2. Principios de diseño

1. **Un solo libro.** `treasury_transactions` sigue siendo el libro de bancos.
   La tabla puente no guarda saldos ni duplica movimientos.
2. **La base de datos garantiza la idempotencia.** La clave única
   `treasury_source_movements.idempotency_key` es la que impide duplicados, no
   una comprobación en PHP. La comprobación previa es solo una optimización.
3. **Nada de dinero que no existe.** Un pago parcial registra lo recibido; un
   crédito sin cuota inicial no registra ingreso; una nota de crédito o una baja
   no generan una salida de dinero.
4. **Sin mezcla de monedas.** El monto se guarda en la moneda del documento y su
   equivalente en PEN (`amount_base`). Solo se registra si la moneda de la
   cuenta es compatible.
5. **Trazabilidad completa.** Cada fila guarda origen, operación, usuario,
   fecha valor, cuenta resuelta, movimiento del libro y, si falla, el error.
6. **Nunca romper el flujo de venta.** Un fallo de tesorería no revierte la
   venta: queda como `failed` y se recupera con un comando administrativo.

---

## 3. Modelo de datos

### `treasury_source_movements` (nueva, aditiva)

| Columna | Tipo | Notas |
|---|---|---|
| `module` | string(40) | `sales` hoy; admite otros módulos |
| `source_type` | string(60) | `sale`, `sale_document`, `sale_payment_quota`, `sale_payment_schedule_destination` |
| `source_id` | unsignedBigInteger | Sin FK a las tablas de origen (desacople) |
| `source_line` | unsignedInteger null | Índice del pago dentro del JSON `sales.payments` |
| `operation` | string(60) | `sale_income`, `down_payment`, `quota_payment`, `full_payment`, `installment_payment`, `*_reversal` |
| `type` | enum | `income` / `expense` |
| `status` | enum | `pending`, `processing`, `processed`, `failed`, `ignored`, `reversed` |
| `amount` | decimal(12,2) | En la moneda del documento, siempre positivo |
| `currency` / `exchange_rate` | string(3) / decimal(12,4) | `PEN` o la moneda del comprobante |
| `amount_base` | decimal(12,2) | Equivalente en PEN |
| `movement_date` | date | Fecha valor del dinero, no la de registro |
| `payment_method_id` | FK null | Método reportado |
| `treasury_account_id` | FK null | Cuenta resuelta por la capa |
| `treasury_transaction_id` | FK null | Movimiento del libro creado |
| `reversal_of_id` | FK null (self) | Fila original cuando esta es su reversión |
| `idempotency_key` | string(191) **unique** | `module\|source_type\|source_id\|source_line\|operation\|reversal_of` |
| `source` | enum | `auto`, `manual`, `backfill` |
| `meta` | json | Datos de contexto (sale_id, document_id, quota_id...) |
| `error_message` | text | Detalle del fallo |

Índices: `(module, source_type, source_id)` y `(status, movement_date)`.

No se añadió ninguna columna a `treasury_transactions`: el vínculo vive en la
tabla puente (`treasury_transaction_id`) y evita duplicar la referencia.

### Entidades y servicios

| Archivo | Rol |
|---|---|
| `Modules/Treasury/Entities/TreasurySourceMovement.php` | Estados, scopes y relaciones de la puente |
| `Modules/Treasury/Services/FinancialMovement.php` | DTO inmutable de la operación reportada |
| `Modules/Treasury/Services/TreasuryIntegrationService.php` | Idempotencia, resolución de cuenta, conversión, registro y reversión |
| `Modules/Treasury/Services/TreasuryHooks.php` | Adaptador Ventas: traduce hechos a `FinancialMovement` |
| `Modules/Treasury/Services/TreasuryLedgerService.php` | Libro de bajo nivel (sin cambios de esquema) |
| `Modules/Treasury/Console/RetryTreasuryIntegrationCommand.php` | Recuperación administrativa |

---

## 4. Flujo de una operación

```
Venta / abono / cobro
        │  (misma transacción de la base)
        ▼
TreasuryHooks  ──►  FinancialMovement
        │
        ▼
TreasuryIntegrationService::register()
        │  1. valida monto > 0 y origen identificable
        │  2. calcula amount_base (moneda del documento)
        │  3. INSERT puente (pending) — clave única
        │  4. resuelve cuenta (método → cuenta)
        │  5. convierte a la moneda de la cuenta
        │  6. registra en el libro (sin firma histórica)
        │  7. marca processed + treasury_transaction_id
        ▼
treasury_transactions (único libro)
```

Los estados posibles de una fila puente:

- `pending` → recién insertada.
- `processing` → tomada por un proceso; si supera 5 minutos se considera
  abandonada y puede reprocesarse.
- `processed` → el libro tiene el movimiento.
- `failed` → no se pudo registrar; requiere reintento.
- `ignored` → no aplica al libro (efectivo excluido, método sin cuenta, cuenta
  inactiva). No es un error.
- `reversed` → su movimiento fue anulado; la fila se conserva.

---

## 5. Idempotencia

La clave se compone del origen y la operación:

```
sales|sale|123|0|sale_income|-
sales|sale|123|1|sale_income|-          ← segundo pago del mismo JSON
sales|sale_payment_quota|55|-|quota_payment|-
sales|sale_document|88|-|down_payment|-
sales|sale|123|0|sale_income|41         ← reversión de la fila 41
```

Consecuencias verificadas en pruebas:

- Registrar dos veces el mismo pago devuelve la fila existente y no toca el
  libro.
- Dos pagos del **mismo importe y fecha** para una venta ahora generan dos
  movimientos: la firma histórica
  `(origin_type, origin_id, type, fecha, monto)` ya no se usa en este camino
  (`register(..., checkSignature: false)`), porque bloqueaba pagos legítimos.
- Dos procesos concurrentes con el mismo movimiento: uno inserta y el otro
  recibe la violación de clave única y devuelve la fila ganadora.

---

## 6. Cuenta y moneda

1. Si el movimiento trae `treasury_account_id`, se usa esa cuenta (debe estar
   activa).
2. Si no, `PaymentAccountResolver` mapea el método de pago a una cuenta de
   banco o billetera. `efectivo` está excluido por configuración
   (`treasury.excluded_payment_methods`) y se maneja en Caja Chica.
3. Si no hay cuenta aplicable, la fila queda `ignored` (no es un fallo).
4. Moneda:
   - cuenta PEN + documento PEN → `amount`.
   - cuenta PEN + documento USD → `amount_base` (`amount × exchange_rate`).
   - cuenta USD + documento USD → `amount`.
   - cuenta USD + documento PEN → `failed` (no se inventa un tipo de cambio
     inverso).
5. Sin tipo de cambio disponible para una moneda extranjera, la fila queda
   `failed` con el detalle; no se registra un importe en soles sin respaldo.

La referencia del movimiento conserva la anotación `TC USD 3.75` para poder
auditar la conversión.

### Vinculación método de pago → cuenta bancaria

`payment_methods.bank_account_id` **ya existía** (migración
`Modules/Sales/Database/Migrations/2025_07_15_124236_add_column_payment_bank.php`,
FK `bank_account_id_fk` hacia `bank_accounts` con `ON DELETE SET NULL`). No se
creó ninguna migración nueva: la vinculación se apoya en esa columna existente.

`PaymentAccountResolver` resuelve así, en este orden:

1. Si el método tiene `bank_account_id`, busca la cuenta bancaria (debe existir
   y estar activa) y su cuenta de tesorería activa de tipo `bank` con la misma
   moneda. Si algo no cuadra, devuelve `null` y el movimiento queda `ignored`:
   nunca se registra en una cuenta equivocada.
2. Si no hay cuenta bancaria vinculada, resuelve por nombre de billetera.
3. Efectivo (excluido por configuración) o sin coincidencia: `ignored`.

**Configuración desde el sistema** (permiso `treasury_cuentas_editar`):

1. Crear la cuenta bancaria con su banco, número y moneda (Configuración →
   Empresa → Cuentas bancarias).
2. En **Tesorería → Cuentas → Nueva cuenta**, crear la cuenta de tesorería de
   tipo banco apuntando a esa cuenta bancaria, con la **misma moneda** y activa.
3. En **Tesorería → Cuentas → Asignación de métodos de pago**, elegir el método
   (p. ej. *Transferencia BCP*) y la cuenta bancaria destino, y guardar.
4. Yape, Plin y demás billeteras no necesitan asignación: se resuelven por
   nombre contra la billetera de empresa.

---

## 7. Contratos por operación (Ventas)

| Operación | Origen | Cuándo se registra |
|---|---|---|
| `sale_income` | `sales.payments` (una fila por pago) | Venta al contado, dentro de la transacción de la venta |
| `down_payment` | `sale_document` + `initial_payment` | Crédito con cuota inicial; solo el importe recibido |
| `quota_payment` | `sale_payment_quota` | Abono de cuota, dentro de la transacción del abono |
| `full_payment` | `sale_document` | `storePayFull`: un ingreso por el dinero cobrado, no uno por cuota de relleno |
| `installment_payment` | `sale_payment_schedule_destination` | Cada cobro del cronograma |

### Casos que **no** generan ingreso

- **Crédito sin cuota inicial:** el formulario envía `payments` en modo crédito
  (el total), pero el backend no los guarda en `sales.payments`; por eso no se
  registra ningún cobro. Verificado en prueba.
- **Filas de relleno de `storePayFull`** (`sale_payment_quotas.estado = false`):
  documentan el saldo cancelado, no dinero recibido.
- **Efectivo:** excluido del libro bancario.
- **Nota de crédito o baja del comprobante:** no existe hook hacia tesorería;
  emitir una NC no devuelve dinero por sí sola.

---

## 8. Reversiones

`TreasuryIntegrationService::reverse()`:

1. Anula el movimiento del libro (`voided_at`), conservándolo.
2. Crea una fila puente de reversión (`operation_reversal`, tipo contrario,
   `reversal_of_id`) con su propia clave única.
3. Marca la fila original como `reversed`.

Una segunda reversión devuelve `null`: no se crea otra fila ni se vuelve a
tocar el libro. Esto protege contra el doble clic y contra reintentos.

La anulación de una venta revierte, en la misma transacción, todo lo cobrado a
su nombre: pagos del JSON, cuota inicial, pago total, abonos de cuota y cobros
del cronograma.

**Devolución real de dinero:** `registerRefund()` registra un **egreso**
(asType expense) con su propio origen. Es distinto de revertir una anulación.
Hoy no hay ningún flujo de Ventas que lo invoque (ver sección 15).

---

## 9. Backfill e informe de diferencias

`php artisan treasury:backfill --since=2020-01-01 --dry-run`

- Recorre ventas al contado, abonos de cuota (`estado = true`) y cobros del
  cronograma (`status = paid`).
- **Dry-run:** informe de diferencias. Lista cada movimiento con `+` (se
  crearía) o `=` (ya registrado) y no escribe nada.
- Sin `--dry-run`: registra por la misma capa, con las mismas claves de
  idempotencia que el registro en línea, así que no duplica lo ya procesado.
- **Ventas con cobranza diferida** (documento con `forma_pago = Credito`,
  cronograma o cuotas) se omiten del JSON de pagos: su dinero se importa por
  las cuotas o el cronograma, nunca como si se hubiera cobrado el total.
- No se recrean anulados: una fila `reversed` mantiene su clave y su reversión.

---

## 10. Recuperación administrativa

`php artisan treasury:integration-retry [--id=] [--limit=200] [--dry-run]`

- Reproduce los `failed` y `pending` (y los `processing` abandonados).
- No duplica: la clave única y el estado se verifican antes de escribir.
- Devuelve código de salida distinto de cero si algo sigue fallando, para poder
  usarlo en un job o en una alerta.
- Autorización: es un comando de consola; no hay endpoint HTTP de recuperación.

---

## 11. Trazabilidad y auditoría

Cada movimiento del libro conserva `origin_type` + `origin_id` y
`source = auto|backfill`. La fila puente añade: operación, línea de origen,
método, cuenta, moneda, tipo de cambio, usuario, fecha valor, `meta` y error.
La reversión queda enlazada al original. Nada se borra: se anula.

---

## 12. Seguridad y validaciones

- Montos siempre positivos; `amount <= 0` lanza `InvalidArgumentException` en la
  capa (no se crea fila ni movimiento).
- Tipo de movimiento restringido a `income`/`expense`.
- Origen obligatorio (`source_id > 0`, módulo, tipo y operación no vacíos).
- Cuenta debe estar activa.
- Registro y reversión dentro de transacciones de base de datos.
- `processing` con marca temporal para detectar procesos abandonados.
- Los errores se registran en `error_message` y en el log, sin exponer datos
  sensibles al usuario.
- `storePayFull` normaliza el importe recibido; la cuota inicial se valida
  contra el total (cuota inicial + cuotas = total).

---

## 13. Pruebas

`tests/Feature/Treasury/TreasuryIntegrationTest.php` — 20 pruebas, 74
aserciones; `tests/Feature/Treasury/PaymentMethodBankAccountTest.php` — 9
pruebas, 30 aserciones. Ambas se ejecutan con
`php artisan test tests/Feature/Treasury` (29 pruebas, 104 aserciones).

| # | Escenario requerido | Cobertura |
|---|---|---|
| 1 | Venta al contado, un método | Automática |
| 2 | Venta al contado, varios métodos → un movimiento por pago | Automática |
| 3 | Dos pagos iguales, misma fecha → no se bloquean | Automática |
| 4 | Registrar dos veces el mismo pago → sin duplicado | Automática |
| 5 | Efectivo → no ingreso bancario | Automática |
| 6 | Crédito sin cuota inicial → sin ingreso ficticio | Automática |
| 7 | Crédito con cuota inicial → solo lo recibido | Automática |
| 8 | Abono parcial de cuota | Automática |
| 9 | Abono total de cuota | Automática |
| 10 | Pago total (`storePayFull`) → un solo ingreso | Automática |
| 11 | Anulación de abono → reversión sin duplicar | Automática |
| 12 | Anulación de venta → revierte todos sus cobros | Automática |
| 13 | Reversión repetida → bloqueada | Automática |
| 14 | Comprobante en USD → conversión con TC | Automática |
| 15 | Cuenta USD + pago PEN → rechazo, sin mezclar | Automática |
| 16 | Método sin cuenta mapeada → `ignored` | Automática (efectivo) |
| 16b | Error al crear el movimiento del libro → `failed` y reintento | Automática (libro simulado sin escritura) |
| 16c | Devolución efectiva de dinero → egreso | Automática (`registerRefund`) |
| 17 | Cobro de cronograma → movimiento | Automática |
| 18 | Backfill primera ejecución | Automática |
| 19 | Backfill segunda ejecución → sin duplicados | Automática |
| 20 | Backfill dry-run → sin escrituras | Automática |
| 21 | Fallo + reintento → recuperación sin duplicar | Automática |
| 22 | Monto no positivo → rechazo | Automática |
| 23 | Dos abonos de una misma cuota → dos movimientos | Automática |
| 24 | Dos solicitudes del mismo pago (instancias distintas) → una fila | Automática |
| 25 | Clave única rechaza el duplicado en base de datos | Automática |
| — | Venta al contado mediante transferencia bancaria | Automática (`PaymentMethodBankAccountTest`) |
| — | Método vinculado a cuenta inexistente, inactiva o de otra moneda | Automática |
| — | NC/baja no revierte ingresos | Verificado en código: `SaleCreditNotesController`, `SaleLowCommunicationController` y `SaleDocumentController@cancelDocument` (estado 3) no invocan tesorería |

Las pruebas usan sqlite en memoria (`phpunit.xml`), con claves foráneas
desactivadas; por eso los orígenes se crean con las columnas mínimas reales.

---

## 14. Extensión a otros módulos

Un módulo nuevo solo necesita:

1. Construir un `FinancialMovement` con `module`, `source_type`, `source_id`,
   `operation`, `type`, `amount`, `date`, moneda/TC y método de pago.
2. Llamar a `TreasuryIntegrationService::register()`.
3. Definir en sus propios hooks cómo revertir (`reverseOrigin()`).

No hay FKs hacia las tablas de origen ni cambios en el esquema del libro. Para
Compras y Caja Chica, la operación sería `expense`; la capa ya soporta ambos
tipos y la categoría se resuelve por operación.

---

## 15. Limitaciones y pendientes (explícitos)

1. **UI de cuota inicial:** el backend acepta `initial_payment` en
   `SaleDocumentController@store` (validado contra el total), pero la pantalla
   `Documents/Create.vue` todavía no lo envía. Mientras no se envíe, el
   comportamiento es el actual: crédito sin cuota inicial.
2. **Reversión en Ventas:** solo se dispara al anular la venta o al eliminar un
   abono. La emisión de una nota de crédito o la baja del comprobante no
   revierten tesorería (correcto: no implican salida de dinero). Si se quiere
   reflejar una devolución, debe usarse `registerRefund()` desde el flujo que
   realmente devuelve el dinero.
3. **`QuickSaleService`:** registra los ingresos al final de su operación, que
   no está envuelta en una transacción propia. Un fallo queda como `failed` en
   la puente (recuperable), pero la venta y el movimiento no son atómicos.
4. **Métodos de pago con cuenta bancaria:** la columna existe y la vinculación
   funciona, pero **nadie la configura sola**: hasta que un administrador asigne
   el método desde Tesorería → Cuentas, una transferencia bancaria queda
   `ignored` (no entra al libro). No es un defecto de esquema, es configuración
   pendiente en cada instalación. Las cuentas de tesorería no tienen
   `company_id` (sistema de una sola empresa); la unicidad de la vinculación se
   resuelve por método, no por empresa.
5. **Montos enteros del cronograma:** `sale_payment_schedules` guarda montos
   `integer`; la capa registra lo que el cronograma reporta, sin redondear de
   nuevo.
6. **`sale_payment_quotas.estado`** sigue siendo la marca de fila de relleno; no
   se renombró ni se eliminó.
7. **Backfill histórico:** se ejecutó solo en pruebas. En producción debe
   correrse primero con `--dry-run` y revisar el informe de diferencias antes de
   escribir.

---

## 16. Verificación end-to-end de los flujos de Sales

Pruebas en `tests/Feature/Treasury/SalesTreasuryFlowsTest.php` (5 pruebas, 45
aserciones). Entran por la ruta HTTP del módulo, con permisos Spatie reales, y
terminan comprobando `treasury_source_movements` y `treasury_transactions`.

| Flujo | Controlador | Invocación a la integración | Resultado | Prueba |
|---|---|---|---|---|
| Contado por transferencia | `SaleDocumentController@store` | `TreasuryHooks::recordSaleIncomes` dentro de `DB::transaction` | 1 movimiento, 1 ingreso en la cuenta bancaria | `test_flujo_venta_contado_por_transferencia` |
| Crédito con cuota inicial | `SaleDocumentController@store` | `recordDownPayment` por pago inicial + `recordSaleIncomes` | 1 movimiento por la inicial; el saldo no genera ingreso | `test_flujo_venta_al_credito_con_cuota_inicial_por_transferencia` |
| Abono de cuota | `SalePaymentQuotaController@store` | `recordQuotaPayment` antes del commit | 1 movimiento por el abono; cuota actualizada | `test_flujo_abono_de_cuota_por_transferencia` |
| Cancelación total | `SalePaymentQuotaController@storePayFull` | `recordFullPayment` con el importe recibido | 1 solo movimiento (no uno por cuota de relleno) | `test_flujo_cancelacion_total_de_la_deuda_por_transferencia` |
| Cobro de cronograma | `AccountsReceivableController@storeSpacePayments` | `recordScheduleDestination` por cada destino | 1 movimiento por cobro, con su cuenta bancaria | `test_flujo_cobro_de_cronograma_por_transferencia` |

### Defecto encontrado y corregido

`SaleDocumentController@store` tenía la inicialización `$status_pay = true;`
absorbida por un comentario de línea (`// Obtener la forma de pago del request
---  $status_pay = true;`). Toda venta al contado por comprobante electrónico
fallaba con `Undefined variable $status_pay`, la transacción se revertía y el
controlador respondía **HTTP 200** con `{"success": false}`. Corregido separando
el comentario de la asignación; el flujo quedó cubierto por la prueba anterior.

### Fuera de una transacción

`QuickSaleService` (venta rápida) llama a `recordSaleIncomes` al final de su
operación, sin `DB::transaction`. Un fallo del libro deja la venta guardada y la
fila puente en `failed`, recuperable con `treasury:integration-retry`. No hay
prueba de ese flujo: solo se verificó por lectura de código.

### Límite de la idempotencia

La clave única protege contra reprocesar el **mismo registro de origen**. No
protege contra un doble envío del formulario de abono: cada POST crea un nuevo
`sale_payment_quotas` con su propio `source_id` y, por tanto, un segundo ingreso
legítimo a ojos de la capa. Para cerrarlo haría falta una clave de idempotencia
de solicitud enviada por el cliente (o un control de referencia duplicada), que
no existe hoy.
