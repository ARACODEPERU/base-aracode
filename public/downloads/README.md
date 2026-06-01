# Descargas públicas

## App móvil de torneos (Android)

- **Archivo:** `aracode-torneos.apk`
- **Origen:** proyecto Flutter `aracode-movil` (`flutter build apk --release`)
- **Landing:** se enlaza automáticamente cuando la edición tiene `mobile_enabled` y el APK existe.

Para actualizar el instalador:

```bash
cd ../aracode-movil
flutter build apk --release
copy build\app\outputs\flutter-apk\app-release.apk ..\base-aracode\public\downloads\aracode-torneos.apk
```

Actualice también `mobile_app_version` en `Modules/Socialevents/Config/config.php` si cambia la versión.
