# Vite en local (Laragon)

## Pantalla en blanco / errores CORS en consola

Suele pasar cuando existe `public/hot` y el navegador carga scripts desde `:5173` con otro origen o protocolo que la página (`https://base-aracode.test` vs `http://127.0.0.1:5173`).

### Modo recomendado con HTTPS (Laragon)

1. En `.env` deja `VITE_USE_BUILD=true` (Laravel ignora `public/hot` y sirve `public/build`).
2. **No** dejes `npm run dev` corriendo si no lo necesitas.
3. Compila assets tras cambios en Vue/JS:
   ```bash
   npm run build
   php artisan config:clear
   ```
4. Recarga `https://base-aracode.test` con Ctrl+F5.

Los JS/CSS salen de `public/build` (mismo dominio, sin CORS).

### Modo desarrollo con recarga en caliente

1. En `.env` descomenta:
   ```
   VITE_DEV_SERVER_URL=http://127.0.0.1:5173
   ```
2. Usa **HTTP** en el navegador: `http://base-aracode.test` (pon `APP_URL=http://base-aracode.test` si hace falta).
3. En otra terminal:
   ```bash
   npm run dev
   ```
4. Vite creará `public/hot` automáticamente.

No mezcles `https://base-aracode.test` en el navegador con Vite en `http://127.0.0.1:5173`: el navegador lo bloquea.
