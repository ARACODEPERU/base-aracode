# Sitio web de KAPTA LMS

Sitio independiente del producto **KAPTA LMS**, hecho en **Blade puro** (sin Inertia, sin Vue y sin
assets de ARACODE). Está pensado para copiarse a su propio dominio junto con todo su ecosistema.

## Qué contiene

| Zona | Contenido |
| --- | --- |
| `resources/views/kapta/` | `layouts/`, `components/` y `pages/` del sitio |
| `resources/css/kapta/` | Fuente de estilos (`kapta.css` + `tailwind.config.js` propio) |
| `public/themes/kapta/` | `css/` compilado, `js/app.js` y `images/` que sirve el navegador |
| `routes/kapta.php` | Rutas del sitio (Blade puro, sin controladores) |

## Rutas

| URL en este repositorio | Vista |
| --- | --- |
| `/site/kapta` | `kapta.pages.home` |
| `/site/kapta/planes` | `kapta.pages.planes` |
| `/site/kapta/contacto` | `kapta.pages.contacto` |

El prefijo `site/kapta` existe solo para convivir con el sitio ARACODE en este repositorio.

## Compilar el CSS

```bash
npm run build:kapta
```

Genera `public/themes/kapta/css/kapta.css` a partir de `resources/css/kapta/kapta.css`
y de su propia config de Tailwind.

## Copiar el sitio a su propio dominio

1. Copia estas cuatro zonas juntas:
   - `resources/views/kapta/`
   - `resources/css/kapta/`
   - `public/themes/kapta/`
   - y el archivo de rutas `routes/kapta.php`
2. En `routes/kapta.php` cambia `$prefix` a `''` para que el sitio quede en la raíz.
3. Vuelve a compilar el CSS si tocas estilos: `npm run build:kapta`.

## Notas

- La identidad visual es propia de KAPTA: no depende del `tailwind.config.js` de la raíz
  ni de `webpage-v2.css`.
- `js/app.js` es vanilla, sin bundler: se sirve tal cual desde el tema.
- Los precios de los planes están en `resources/views/kapta/pages/planes.blade.php`.
