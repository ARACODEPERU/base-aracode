# Publicación móvil — Fase 6

Esta guía cierra la Fase 6 del [plan móvil multi-organización](PLAN_MOVIL_MULTI_ORGANIZACION.md): diagnóstico local de errores, verificación automatizada, compilación y pasos para probar con dos organizaciones antes de publicar.

## 1. Diagnóstico y telemetría

La app incluye una pantalla **Más → Diagnóstico** con:

- Versión instalada y URL de la API.
- Contadores de errores `401`, `403`, `404`, `5xx` y fallos de red.
- Los últimos 40 eventos: método, ruta normalizada, estado, código de negocio y versión.
- Acciones para copiar el resumen y borrar los registros.

Características de la telemetría:

- Es **local**: no se envía a ningún servicio externo ni requiere una cuenta.
- No guarda tokens, correos, cuerpos de respuesta ni datos de jugadores.
- Normaliza los identificadores numéricos de la ruta (`/editions/12/reports` → `/editions/:id/reports`).
- Descarta los `2xx` y los `422` de validación para no llenar el registro con errores esperados.
- Se guarda en `GetStorage` bajo `api_telemetry_v1`; el borrado no afecta la sesión ni los torneos.

La versión mostrada sale de `AppConfig.appVersion` (`--dart-define=APP_VERSION=…`). Una prueba comprueba que coincida con `version` de `pubspec.yaml`.

## 2. Verificación automatizada

| Suite | Resultado |
|-------|-----------|
| `flutter test` | 124 pruebas en verde |
| `flutter analyze` | 0 errores; avisos preexistentes del proyecto |
| Pruebas móviles PHP (5A–5C) | 40 pruebas y 210 aserciones en verde |
| `tests/Feature/Socialevents` | 182 pruebas en verde; 4 fallos de fixture/saldo ajenos a este cambio |

Antes de cada publicación conviene repetir:

```bash
cd /d/laragon/www/aracode-movil
flutter test
flutter analyze

cd /d/laragon/www/base-aracode
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest --filter "MobileEditionReportsApiTest|MobileEditionDisciplineApiTest|MobileTournamentAdminApiTest|MobileOrganizationApiTest"
```

## 3. Compilación

```bash
cd /d/laragon/www/aracode-movil

# APK para instalación directa / pruebas
flutter build apk --release

# App Bundle para Google Play
flutter build appbundle --release

# Contra un servidor de staging
flutter build apk --release \
  --dart-define=API_BASE_URL=https://staging.example.com \
  --dart-define=APP_VERSION=1.0.1+2
```

El artefacto de la compilación verificada queda en `build/app/outputs/flutter-apk/app-release.apk`.

> **Bloqueante antes de tienda:** `android/app/build.gradle.kts` firma la variante `release` con la clave de depuración (hay un `TODO` en el archivo). Para publicar en Google Play hay que crear un keystore propio, cargar `key.properties` fuera del repositorio y configurar `signingConfigs.release`. No se debe subir a la tienda un APK/AAB firmado con la clave de debug.

### 3.1 Prueba en el celular contra el servidor local (sin publicar)

Permite instalar la app en el teléfono y trabajar contra la API del equipo de desarrollo, sin tiendas ni servidores públicos. Requiere que el celular y la PC estén en la misma red Wi-Fi.

1. **Servidor** (dejar la ventana abierta durante las pruebas):

   ```bash
   cd /d/laragon/www/base-aracode
   php artisan serve --host=0.0.0.0 --port=8080
   ```

   Comprobar desde la PC:

   ```bash
   curl -s -o /dev/null -w "%{http_code}\n" http://192.168.100.6:8080/api/socialevents/v1/events
   ```

   Debe responder `200`. `artisan serve` atiende una petición a la vez; alcanza para pruebas individuales, no para varias personas ni para medir desempeño.

2. **Firewall de Windows** (una sola vez). La red Wi-Fi está clasificada como “Pública”, así que Windows bloquea las conexiones entrantes. La regla aplicada acepta solo la subred local:

   ```bash
   netsh advfirewall firewall add rule name="Aracode pruebas movil 8080" dir=in action=allow protocol=TCP localport=8080 profile=any remoteip=192.168.100.0/24
   ```

   Requiere consola de administrador. Si cambia la red o la IP, hay que borrar la regla (`netsh advfirewall firewall delete rule name="Aracode pruebas movil 8080"`) y crearla con la nueva subred.

3. **APK de pruebas**: ya compilado contra `http://192.168.100.6:8080` en
   `build/pruebas-lan/pichanguero-pruebas-lan-192.168.100.6.apk` (≈91 MB; `build/` está ignorado por git). Instalarlo por USB con `adb install -r <ruta>` o copiar el archivo al teléfono y abrirlo.

4. **Confirmar el destino**: en la app, abrir **Más → Diagnóstico**; la URL de la API debe ser `http://192.168.100.6:8080/api`. Si aparece `https://pichanguero.com/api` se instaló el APK por defecto (servidor público de pruebas); `https://avc.pichanguero.com` quedó comentado en `app_config.dart` por ahora.

5. **Si cambia la IP o la red**: obtener la IPv4 con `ipconfig`, ajustar la regla de firewall y recompilar:

   ```bash
   cd /d/laragon/www/aracode-movil
   flutter build apk --release --dart-define=API_BASE_URL=http://NUEVA_IP:8080
   ```

   Copiar el APK, reinstalar y repetir el paso 4. Este APK es solo para pruebas: el de tienda se compila sin `--dart-define`, contra el dominio público.

## 4. Prueba manual con dos organizaciones (staging)

Preparar dos cuentas con organización y acceso vigente: **A** y **B**. En A crear un torneo con edición, dos equipos, jugadores, un partido con resultado y un acta; en B al menos un torneo distinto.

1. **Aislamiento**: iniciar sesión como A y comprobar que “Mis torneos” solo lista los eventos de A. Repetir con B.
2. **Edición activa**: cambiar de edición en A y confirmar que equipos, jugadores, galería, disciplina, reportes y publicación usan esa edición.
3. **Publicación**: en A activar landing y app, guardar y abrir el enlace público en el navegador. Despublicar la landing y comprobar que el enlace deja de abrir; el enlace debe seguir visible en la app.
4. **Reportes/PDF**: abrir el acta, la planilla de un partido y la lista de sanciones. Verificar que el navegador descarga/muestra el PDF y que al volver la app sigue en la pantalla.
5. **Usuarios**: en A ver propietario y miembros, con “Tú” en la cuenta actual. Confirmar que B no ve las cuentas de A.
6. **Solo lectura**: vencer la prueba de A desde la web (o usar una organización vencida). Comprobar que la app permite leer torneos, reportes y publicación, pero bloquea formularios y responde `ORG_READ_ONLY` en las escrituras. Recargar saldo y confirmar que la escritura se habilita sin reinstalar.
7. **Sesión**: cerrar sesión y entrar con B; no deben quedar torneos, caché ni cola offline de A. Probar un `401` (token revocado desde la web) y confirmar el aviso de sesión expirada.
8. **Diagnóstico**: tras provocar un `404` (por ejemplo, una edición ajena) abrir Más → Diagnóstico, comprobar el contador, copiar el resumen y borrar los registros.

Registrar el resultado de cada paso con dispositivo, versión de Android y versión de la app.

## 5. Despliegue gradual

1. **Interna**: APK/AAB en pista interna con 5–10 organizadores reales durante 3–5 días.
2. **Beta cerrada**: 10–25 % de los organizadores; revisar diagnóstico local y reportes de soporte.
3. **Producción gradual**: 10 % → 50 % → 100 %, con al menos 24 horas entre pasos.

Señales para detener el despliegue:

- Aumento de `401` que no se explique por cierre de sesión voluntario.
- `403` en rutas que deberían estar permitidas (posible regresión de permisos).
- `404` en ediciones propias (posible problema de pertenencia).
- Errores de conexión sostenidos o PDF que no abren.

## 6. Pendientes de esta fase

- Instalar el APK de pruebas LAN en el teléfono y recorrer el checklist de la sección 4 contra el servidor local; es la validación previa a staging.
- Ejecutar la prueba manual de la sección 4 en staging con dos organizaciones reales; no se puede completar desde el entorno de desarrollo.
- Configurar la firma de tienda y el keystore.
- Decidir si la telemetría local se complementa con un servicio remoto de errores; si se elige uno, documentar proveedor, retención y consentimiento.
- Confirmar la política de versiones: hoy el pie y el diagnóstico leen `APP_VERSION` y deben actualizarse junto con `pubspec.yaml`.
