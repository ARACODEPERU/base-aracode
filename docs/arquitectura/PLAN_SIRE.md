# SIRE — estado y plan de fases

Registro de Ventas e Ingresos Electrónico (RVIE) y Registro de Compras Electrónico (RCE) del
Sistema Integrado de Registros Electrónicos de SUNAT, integrados al ERP ARACODE sobre los
comprobantes que ya se emiten con Greenter.

Base normativa: R.S. N.° 112-2021/SUNAT y sus anexos (Anexo N.° 1 tablas, Anexo N.° 2
complemento, Anexo N.° 3 reemplazo/comparación, Anexos N.° 4 y 5 ajustes posteriores),
modificada por la R.S. N.° 138-2023/SUNAT.

---

## Fase 1 — RVIE de ventas por archivo plano (entregada)

| Pieza | Ubicación |
|---|---|
| Generador del registro | `Modules/Sales/Services/Sire/SireRvieVentasService.php` |
| Pantalla y descargas | `Modules/Sales/Http/Controllers/SireRvieVentasController.php`, `Pages/Sire/RvieVentas.vue` |
| Rutas | `sales/sire/rvie-ventas`, `.../txt`, `.../zip` |
| Permiso | `sire_rvie_ventas` (migración `2026_11_18_000001`) |

Alcance: facturas, boletas, notas de crédito y débito del período; 33 campos del Anexo N.° 3;
nombre de archivo de la tabla 6 (`LERRRRRRRRRRRAAAAMM0014040002OIM2.TXT`); TXT y ZIP; vista
previa acotada (200 filas) y sin el TXT completo en los props de Inertia; observaciones previas
a presentar.

Reglas ya implementadas:

- Anulados, por anular y rechazados se anotan en cero.
- Nota de crédito del mismo período: base e IGV negativos en los campos 15 y 17.
- Nota de crédito que modifica un comprobante de un período anterior: campos 16 y 18 negativos
  y 15/17 en cero (criterio de las preguntas frecuentes de SUNAT).
- Tipo de cambio vacío en soles y con tres decimales en moneda extranjera (campo 28 solo es
  obligatorio cuando la moneda no es PEN).

Pendiente dentro de la fase: catálogo SUNAT de tipo de documento de identidad (tabla 1) validado
contra `sale_documents.client_type_doc`, y confirmación en un primer envío real con la opción
"comparar" del módulo SIRE.

---

## Benchmark: qué tienen los sistemas completos del mercado

Funciones observadas en soluciones peruanas especializadas (Bizlinks Biz-SIRE, Facturalo,
iSiore GO, SIRE Web, Telofacturo, ERP con SIRE de MiAPI):

1. Descarga de la propuesta SUNAT y comparación automática contra la contabilidad, con
   diferencias línea por línea, faltantes en cada lado y diferencias de importes, IGV, datos del
   cliente, moneda y tipo de cambio.
2. Bandeja de inconsistencias de SUNAT por comprobante (código, descripción, importe).
3. Flujo completo de propuesta: aceptar, complementar, reemplazar, registrar preliminar y
   revertirlo.
4. Ejecución asíncrona con tickets y descargas diferidas, con seguimiento de estado.
5. Ajustes posteriores de períodos anteriores (Anexos 4 y 5) con correlativo propio.
6. RVIE **y** RCE en el mismo período, con resumen de ambos antes de presentar.
7. Multiempresa/multi-RUC desde una sola cuenta, y exportación a Excel/API para el contador.
8. Alertas de plazos del cronograma SUNAT y bloqueo o cierre del período presentado.
9. Constancias de recepción, reporte de casillas 621 y reporte de exportadores.

El ERP ya cubre el punto de partida (registro propio y archivo plano); las fases siguientes
agregan lo que distingue a esas soluciones.

---

## Fase 2 — RCE de compras (base entregada)

Objetivo: segundo registro del SIRE, con la misma calidad que el de ventas.

Entregado:

- Columnas nuevas en `purc_documents` (`exchange_rate`, `invoice_status`, `document_id`) y
  permiso `sire_rce_compras` en la migración `2026_11_18_000002`.
- `Modules/Sales/Services/Sire/SireArchivoPlano`: formato común de los dos registros (importes,
  fechas, texto libre, tipo de cambio, contenido, ZIP y nombre de archivo por libro).
- `Modules/Sales/Services/Sire/SireRceComprasService`: 37 campos del Anexo N.° 11, notas en
  negativo, anulados excluidos y archivo `...0008040002OIM2.TXT`.
- Pantalla compartida `Pages/Sire/Registro.vue` para ventas y compras, rutas
  `sales/sire/rce-compras` (`txt` y `zip`) y entrada de menú.
- Pruebas: campos, importes, nota de crédito con referencia, anulados excluidos, nombre de
  archivo, pantalla y permiso.

Pendiente en la fase:

1. Separar adquisiciones DG / DGNG / DNG cuando el ERP registre el destino del gasto; hoy la
   base gravada va completa al campo 15 y los campos 17 a 20 quedan en cero.
2. ICBPER de compras (campo 23) cuando exista el dato en el documento.
3. Clasificación de bienes y servicios (campo 33, contribuyentes con ingresos > 1 500 UIT) y
   operadores/partícipes (campos 34 y 35).
4. Comprobantes de no domiciliados en su propio registro (Anexo N.° 9).

## Fase 3 — Conciliación con la propuesta de SUNAT

Objetivo: dejar de presentar a ciegas; es la función que más valoran los sistemas del mercado.

1. Importar la propuesta descargada de SOL (TXT) y guardarla por período con su hash.
2. Motor de comparación: claves por tipo/serie/número, diferencias de fecha, cliente, base, IGV,
   total, moneda y tipo de cambio; faltantes en SUNAT y faltantes en el ERP.
3. Bandeja de diferencias con acciones: corregir el comprobante origen o marcar la diferencia
   como aceptada con motivo.
4. Reporte de conciliación exportable y resumen por tipo de comprobante.

## Fase 4 — API SIRE

Objetivo: presentar sin salir del ERP.

1. Cliente REST con OAuth2 usando el usuario y la clave API que se generan en SOL; secretos
   cifrados y nunca en el cliente.
2. Servicios: periodo, propuesta, inconsistencias, tipo de cambio, aceptar, reemplazar,
   registrar preliminar y revertirlo, estado de ticket y descarga.
3. Cola con reintentos y bitácora de cada llamada (ticket, código, mensaje, duración).
4. Pantalla de procesos con estado por período y aviso cuando SUNAT termina.
5. Pruebas contra el ambiente de SUNAT con credenciales de empresa de pruebas.

## Fase 5 — Ajustes posteriores y control del período

1. Anexos 4 y 5: rectificar o incorporar operaciones omitidas, con correlativo de ajuste en el
   nombre del archivo.
2. Cierre del período: bloquear emisión retroactiva una vez presentado, salvo permiso especial.
3. Historial por período: archivo presentado, constancia, quién y cuándo lo presentó.
4. Alertas de plazos según el cronograma SUNAT y estado de avance mensual.

## Fase 6 — Gobierno y escala

1. Inmutabilidad: guardar el TXT presentado con hash y snapshot de sus líneas.
2. Multiempresa/multi-RUC y multiestablecimiento, con permisos por empresa.
3. Exportación a Excel y API para el contador externo.
4. Reportes de casillas 621, exportadores y estadística de inconsistencias por período.
5. Auditoría de cambios sobre comprobantes ya anotados.

---

## Orden recomendado y dependencias

| Fase | Depende de | Valor |
|---|---|---|
| 2 RCE | Fase 1 (formateador común) | Cierra el SIRE completo |
| 3 Conciliación | Fase 1 y 2 | Evita rectificaciones y observaciones |
| 4 API | Fase 3 (mismas reglas de comparación) | Automatiza la presentación |
| 5 Ajustes | Fases 2 a 4 | Cubre rectificaciones y cierre |
| 6 Gobierno | Todas | Escala a varias empresas y auditoría |

## Riesgos y decisiones abiertas

- Credenciales API de SOL: sin ellas la Fase 4 no se puede probar; el resto avanza con archivo
  plano.
- Catálogos SUNAT (tipo de documento de identidad, tipo de documento de compra) deben quedar en
  tablas del ERP, no en constantes del servicio.
- El tipo de cambio en soles y las reglas de notas ya están alineados al Anexo N.° 3, pero el
  primer envío real debe hacerse con la opción "comparar" antes de reemplazar la propuesta.
- La migración pendiente `2026_11_17_100000_create_even_local_areas_and_rental_items` (tabla ya
  existente) bloquea `php artisan migrate` completo en local; hay que resolverla antes de
  desplegar las fases siguientes.
