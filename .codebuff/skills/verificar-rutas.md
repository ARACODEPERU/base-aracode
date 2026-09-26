# Skill: Verificar rutas antes de crear o modificar

## Cuándo aplicar
Siempre que el trabajo implique crear, renombrar, redirigir o consumir rutas de Laravel (web, api, módulos nwidart), y siempre que se llame `route('nombre', ...)` desde PHP, Blade o Vue.

## Regla crítica
**Antes de crear una ruta nueva, listar las rutas existentes y confirmar que el nombre y el path no existen ya.** Duplicar un nombre genera `Route Duplicate` y errores silenciosos de resolución (`route()` devuelve el primero que registra).

## Procedimiento obligatorio

1. **Inventariar rutas existentes** con la salida real del framework, no a ojo:
   ```bash
   php artisan route:list | grep -i "palabra_clave"
   php artisan route:list --name=web_curso   # por nombre exacto
   php artisan route:list --path=curso       # por path
   ```
   Los módulos nwidart registran sus rutas en `Modules/*/Routes/web.php` (con prefijo y nombre `modulo_*`); revisarlas también.

2. **Antes de llamar `route('nombre')` en cualquier código**, verificar que la ruta exista:
   ```bash
   php artisan route:list --name=nombre_de_ruta
   ```
   Si el nombre no existe, `route()` lanza error en producción. Nunca introducir llamadas a rutas no verificadas (caso real: `course_url_slug` y `web_course_description` se usaban en 3 archivos sin estar definidas — el catálogo público y el panel del alumno quedaban rotos).

3. **Crear la ruta nueva solo si**:
   - El nombre no existe (elegir convención del proyecto: `web_` para páginas públicas, `modulo_accion` en módulos).
   - El path no colisiona con otro GET del mismo segmento (recordar orden: las rutas estáticas van ANTES de las dinámicas `/{slug}` o `/{id}`).
   - Si se reemplaza una ruta existente: mantener compatibilidad (redirigir 301 los ids/paths antiguos hacia la nueva URL amigable) y actualizar **todos** los usos con `git grep -n "nombre_viejo"` / `git grep -n "path_viejo"`.

4. **Después de crear/modificar rutas, validar siempre**:
   ```bash
   php artisan route:list | grep -i "palabra"   # aparecen una sola vez
   php -l archivo.php                            # sintaxis de archivos tocados
   ```

5. **Verificar consumos cruzados**: buscar con `git grep` cada nombre de ruta nuevo/cambiado en `*.php`, `*.blade.php`, `*.vue` y `*.js` (excluyendo `public/build` y `bootstrap/ssr`) para confirmar que todos los llamadores existen y pasan los parámetros correctos.

## Errores que esta skill evita
- `Unable to generate a URL for the named route` en producción.
- Nombres duplicados que hacen que `route()` apunte a un controlador equivocado.
- Rutas dinámicas que capturan tráfico de rutas estáticas por mal orden de registro.
- Enlaces muertos cuando se renombra una ruta sin actualizar todos los usos.
