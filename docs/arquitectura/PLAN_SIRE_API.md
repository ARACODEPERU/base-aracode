# SIRE por API — estudio, hallazgos y diseño de los dos modos

Complemento de `PLAN_SIRE.md` (fases 1 y 2 ya entregadas). Objetivo: que la pantalla del SIRE
ofrezca **Modo TXT / ZIP** (lo actual) y **Modo automático por API** (token, propuesta,
comparación, reemplazo, ticket y bitácora), con validación previa del archivo.

Fuentes: manuales oficiales SUNAT «Servicios Web Api – SIRE Ventas v32» y «SIRE Compras v29»
(octubre 2026), portal `sire.sunat.gob.pe` y documentación pública de integradores.

---

## 1. Cómo lo resuelven otros sistemas

Todos los integradores peruanos siguen el mismo patrón, porque SUNAT expone un solo flujo:

1. **Credenciales.** El contribuyente genera en SOL (Empresas → Credenciales de API SUNAT) un
   `client_id` y `client_secret`, y usa además su **usuario y clave SOL**. Las cuatro piezas son
   obligatorias: las dos primeras identifican a la aplicación, las dos últimas al contribuyente.
2. **Token.** `POST` al endpoint de seguridad con `grant_type=password`; el token dura ~1 hora y
   se envía como `Authorization: Bearer`. No se pide token por operación: se cachea y se renueva
   al vencer o ante un 401.
3. **Propuesta.** `GET` de exportación de la propuesta del período. SUNAT **no devuelve el
   archivo en esa llamada**: devuelve un `numTicket`. El archivo se recoge después en dos pasos
   (consultar estado del ticket → descargar archivo). El estado `06` significa «Terminado»; `EXP`
   es ticket expirado y obliga a solicitar uno nuevo.
4. **Comparación.** El archivo de propuesta es un TXT con la misma estructura del Anexo N.° 3 o
   N.° 11. El sistema lo parsea y lo compara contra sus propios comprobantes: faltantes en el
   ERP, sobrantes en SUNAT, diferencias de importes y de estado.
5. **Envío / reemplazo.** El reemplazo se hace **subiendo el mismo TXT que ya generamos**, no un
   JSON: SUNAT recibe un ZIP/TXT por el endpoint `.../receptorpropuesta/web/propuesta/upload`
   con metadatos en cabeceras (protocolo TUS), y responde un `numTicket`. «Aceptar propuesta» es
   otra llamada distinta, sin archivo.
6. **Confirmación.** Se guarda el ticket, la fecha, el estado y la respuesta cruda. El estado
   final se consulta por ticket; el sistema muestra «en proceso» hasta el `06`.
7. **Reintentos.** Solo tiene sentido reintentar operaciones **idempotentes**: token, consulta de
   estado y descarga. Un reemplazo no se reintenta a ciegas: si la respuesta se perdió, primero se
   consulta el ticket; si no hay ticket, se vuelve a enviar con confirmación del usuario.

## 2. Greenter: no sirve para el SIRE

Revisado el catálogo real de paquetes (`packagist.org/packages/greenter/`):

| Paquete | Qué hace |
|---|---|
| `greenter/greenter`, `greenter/lite`, `greenter/ws` | UBL, firma, SOAP de facturas/boletas/resúmenes/guías |
| `greenter/gre-api` | Guías de remisión por la nueva API REST |
| `greenter/consulta-cpe` | Consulta integrada de comprobantes |
| `greenter/*` restantes | XML, reportes, validadores UBL |

**No existe `greenter/sire` ni equivalente.** La API del SIRE es REST con OAuth2 y tickets
asíncronos, nada que ver con el SOAP de comprobantes. Conclusión: cliente propio y delgado sobre
`Illuminate\Support\Facades\Http` (ya disponible en Laravel 13), sin agregar dependencias. Lo que
sí conviene conservar de Greenter es lo que ya usa el ERP para emitir comprobantes.

## 3. Hallazgo importante: el SIRE no tiene ambiente de pruebas

Los manuales solo publican el host de producción (`api-sire.sunat.gob.pe`) y los integradores lo
confirman de forma explícita: *todos los endpoints del SIRE operan contra producción SUNAT; no
existe entorno de pruebas*. A diferencia de la facturación electrónica (que tiene
`e-beta.sunat.gob.pe`), aquí no hay beta.

Consecuencias para «probar antes de producción»:

- **Pruebas locales:** cliente HTTP contra un doble (`Http::fake` y fixtures grabados). Es lo que
  permite verificar el flujo completo sin tocar SUNAT, y es lo que se implementará con pruebas.
- **Prueba real controlada:** con credenciales reales, empezar por lo **de solo lectura**
  (token, consultar períodos, exportar y descargar propuesta). No altera el registro.
- **Reemplazo real:** recién cuando la comparación cuadre, con confirmación explícita y sobre un
  período ya declarado. Un reemplazo mal enviado se corrige con otro reemplazo o con ajustes
  posteriores, no se «deshace».
- Queda descartado apuntar a un host de pruebas inexistente; el «modo simulación» será local y
  estará rotulado como tal en la pantalla.

## 4. Endpoints que hay que consumir

### 4.1 Token

```
POST https://api-seguridad.sunat.gob.pe/v1/clientessol/{client_id}/oauth2/token/
Content-Type: application/x-www-form-urlencoded

grant_type=password
scope=https://api-sire.sunat.gob.pe
client_id=...
client_secret=...
username={RUC}{USUARIO_SOL}
password={CLAVE_SOL}
```

Respuesta: `access_token`, `token_type`, `expires_in`. Se cachea por empresa hasta 60 s antes de
vencer.

### 4.2 RVIE (ventas) — libro `140000`

| Acción | Método y ruta | Salida |
|---|---|---|
| Exportar propuesta | `GET /v1/contribuyente/migeigv/libros/rvie/propuesta/web/propuesta/{AAAAMM}/exportapropuesta?codTipoArchivo=0` | `numTicket` |
| Aceptar propuesta | `POST /v1/contribuyente/migeigv/libros/rvie/propuesta/web/registroslibros/{AAAAMM}/aceptarpropuesta` | `numTicket` |
| Reemplazar | `POST /v1/contribuyente/migeigv/libros/rvierce/receptorpropuesta/web/propuesta/upload` + metadatos TUS | `numTicket` |
| Estado de ticket | `GET /v1/contribuyente/migeigv/libros/rvierce/gestionprocesosmasivos/web/masivo/consultaestadotickets?perIni=&perFin=&page=1&perPage=100&numTicket=` | estado y nombre de archivo |
| Descargar archivo | `GET /v1/contribuyente/migeigv/libros/rvierce/gestionprocesosmasivos/web/masivo/archivoreporte?nomArchivoReporte=&codTipoArchivoReporte=&perTributario=&codProceso=&numTicket=&codLibro=140000` | ZIP/TXT |

### 4.3 RCE (compras) — libro `080000`

| Acción | Método y ruta | Salida |
|---|---|---|
| Exportar propuesta | `GET /v1/contribuyente/migeigv/libros/rce/propuesta/web/propuesta/{AAAAMM}/exportacioncomprobantepropuesta?codTipoArchivo=0&codOrigenEnvio=2` | `numTicket` |
| Aceptar propuesta | `POST /v1/contribuyente/migeigv/libros/rce/propuesta/web/registroslibros/{AAAAMM}/aceptarpropuesta` | `numTicket` |
| Reemplazar | mismo `upload` con `codLibro=080000` y `codProceso=61` | `numTicket` |
| Estado / descarga | mismos servicios masivos con `codLibro=080000` | estado / archivo |

### 4.4 Metadatos del reemplazo

Cabecera `Upload-Metadata` (TUS), valores en base64:

```
filename, filetype, numRuc, perTributario, codOrigenEnvio=2,
codProceso (3 RVIE / 61 RCE), codTipoCorrelativo=01,
nomArchivoImportacion, codLibro (140000 / 080000)
```

El cuerpo es el ZIP con el TXT que genera la Fase 1/2. El nombre del archivo debe respetar la
tabla 6/13 (ya implementado).

### 4.5 Estados de ticket

`01` cargado/solicitado, `05` en proceso, `06` terminado, `EXP` expirado (> 3 min). Para descargar,
el proceso debe estar terminado y `codProceso` del ticket debe ser 3 o 4.

## 5. Diseño propuesto en el ERP

### 5.1 Datos

`companies` (migración aditiva):

- `sire_mode` (`txt` por defecto, `api`).
- `sire_client_id`, `sire_client_secret`, `sire_sol_user`, `sire_sol_key` con cast `encrypted`.
- Se reutiliza `companies.ruc` como `numRuc`. Evaluar reutilizar `user_sunat`/`key_sunat` si el
  contribuyente usa el mismo usuario SOL para facturación y SIRE (decisión de configuración, no
  de código).

Tablas nuevas:

- `sire_submissions`: empresa, tipo (`ventas`/`compras`), período, acción
  (`exportar`/`aceptar`/`reemplazar`), ticket, estado, archivo, hash del TXT, respuesta JSON,
  intentos, usuario, fechas.
- `sire_submission_logs`: envío, intento, código HTTP, mensaje, duración, payload recortado.

### 5.2 Servicios

| Clase | Responsabilidad |
|---|---|
| `SireApiClient` | token cacheado, reintentos con backoff (401 → renueva una vez; 429/5xx → 3 intentos), log de cada llamada |
| `SirePropuestaService` | exportar → polling de ticket → descarga del ZIP → TXT en memoria |
| `SireComparadorService` | parsea la propuesta y devuelve faltantes, sobrantes y diferencias contra las filas locales |
| `SireSubmissionService` | arma ZIP, sube reemplazo, guarda ticket y estado, consulta hasta terminar |
| `SireValidador` | bloquea la descarga/envío si hay errores; las observaciones actuales pasan a advertencias |

### 5.3 Validador interno (aplica a los dos modos)

Errores que **bloquean** la descarga y el envío:

- RUC de la empresa vacío o distinto de 11 dígitos.
- Campos obligatorios vacíos según el anexo (tipo, serie, número, fecha, proveedor/cliente).
- RUC/DNI del cliente o proveedor vacío, o con longitud inválida para su tipo.
- Importes que no cuadran: total ≠ suma de bases + IGV + ISC + otros tributos (±0.01).
- Nota sin comprobante de referencia.
- Moneda distinta de PEN sin tipo de cambio positivo.

Advertencias que no bloquean: boleta ≥ S/ 700 sin documento, proveedor sin RUC, fechas futuras.

### 5.4 Pantalla

En `Pages/Sire/Registro.vue`, un selector de modo por empresa:

- **Descarga manual (TXT / ZIP):** lo actual, más el validador antes de habilitar los botones. Si
  hay errores, se listan y el botón queda deshabilitado con el motivo.
- **Envío automático (API):** estado de credenciales, botones *Descargar propuesta*, *Comparar*,
  *Reemplazar propuesta* y *Aceptar propuesta* (este último con confirmación explícita), panel de
  ticket con estado y bitácora. Si no hay credenciales, la pestaña explica cómo generarlas en SOL.

### 5.5 Reintentos y bitácora

- Renovación de token y consultas: automático.
- Reemplazo: **no** se reintenta solo. Ante timeout, se consulta el ticket; si no existe, se
  ofrece reintentar con confirmación.
- Toda llamada deja fila en `sire_submission_logs` con código HTTP y mensaje de SUNAT.

## 6. Fases

**Fase A — base y solo lectura**
Credenciales cifradas + pantalla de configuración, `SireApiClient` con token y bitácora,
exportar/descargar propuesta, comparador, validador. Pruebas con `Http::fake` y fixtures.
Criterio: token renovado, ticket consultado hasta `06`, comparación mostrada, sin escrituras en
SUNAT.

**Fase B — reemplazo**
ZIP + metadatos TUS, envío de reemplazo RVIE y RCE, guardado de ticket, consulta de estado,
pantalla de resultado, reintento manual seguro. Criterio: un período de prueba reemplazado y
confirmado con ticket terminado.

**Fase C — complementos**
Aceptar propuesta, ajustes posteriores, no domiciliados, descarga del RCE generado y constancia.

## 7. Lo que se necesita para arrancar

1. `client_id` y `client_secret` generados en SOL (Empresas → Credenciales de API SUNAT) y el
   usuario/clave SOL del contribuyente. Se cargan en la pantalla de configuración, nunca en el
   chat ni en el repositorio.
2. Confirmar el alcance de la Fase A (solo lectura y comparación) antes de habilitar reemplazos.
3. Un período ya declarado para la primera prueba de lectura; no se enviará nada sin confirmación.

## 8. Estado de la Fase A (entregada)

Solo lectura y validación previa; **no hay envío ni reemplazo de propuestas**.

| Pieza | Ubicación |
|---|---|
| Migración aditiva (credenciales, modo, envíos y bitácora) | `Modules/Sales/Database/Migrations/2026_11_18_000003_create_sire_api_tables.php` |
| Cliente con token, caché, renovación y bitácora | `Modules/Sales/Services/Sire/SireApiClient.php` |
| Propuesta: exportar → ticket → descargar TXT/ZIP | `Modules/Sales/Services/Sire/SirePropuestaService.php` |
| Validador interno (bloquea TXT/ZIP) | `Modules/Sales/Services/Sire/SireValidador.php` |
| Pantalla de credenciales y bitácora | `Modules/Sales/Http/Controllers/SireConfiguracionController.php`, `Pages/Sire/Configuracion.vue` |
| Acciones de solo lectura por registro | `SirePropuestaController`, rutas `sire_rvie_ventas_propuesta` y `sire_rce_compras_propuesta` |
| Selector de modo y errores bloqueantes | `Pages/Sire/Registro.vue` |

Decisiones de implementación:

- Las cuatro piezas de la credencial (`sire_client_id`, `sire_client_secret`, `sire_sol_user`,
  `sire_sol_key`) se guardan con cast `encrypted`; la pantalla solo devuelve una pista enmascarada
  y un campo vacío conserva el valor anterior.
- El token se cachea por empresa y `client_id` hasta un minuto antes de vencer; un 401 lo renueva
  una vez y repite la llamada; 429/5xx se reintentan con espera creciente.
- La propuesta se guarda en `storage/app/sire/{empresa}/{AAAAMM}/` con hash SHA-256, cantidad de
  líneas y ticket. Si SUNAT entrega un ZIP, se extraen todos sus TXT y se concatenan.
- Los logs nunca guardan tokens ni credenciales: acción, endpoint sin query sensible, código HTTP,
  duración y mensaje de SUNAT.
- El validador revisa 33/37 campos y bloquea la descarga tanto en la pantalla como en el guardia
  del servidor (`txt`/`zip` redirigen con error). En el RCE se acepta el total sin las no gravadas
  porque las operaciones gratuitas no suman al total.

Pendiente para la Fase B: comparador propuesta vs. ERP, ZIP + metadatos TUS, reemplazo, aceptación
con confirmación explícita y reintento manual seguro.

## 9. Riesgos

- **Sin sandbox:** cualquier prueba de escritura afecta el registro real. Mitigación: fase de solo
  lectura, confirmaciones y bitácora.
- **TUS:** el manual documenta el alta con cliente TUS; hay que verificar en la primera prueba si
  el endpoint acepta un POST binario simple o exige el flujo TUS completo. El cliente se escribirá
  con el flujo TUS y una alternativa simple detrás de la misma interfaz.
- **Token y credenciales:** rotación en SOL invalida el token; el cliente debe renovar y avisar.
- **Volumen:** la propuesta puede venir particionada en varios ZIP; el comparador debe recorrer
  todas las partes.
- **Cola:** `QUEUE_CONNECTION=sync`; las consultas largas se resolverán con polling desde la
  pantalla y, si el volumen crece, con cola `database` y job dedicado.
