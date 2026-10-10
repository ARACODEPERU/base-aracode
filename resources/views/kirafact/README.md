# Sitio web de KiraFact

Sitio independiente del producto **KiraFact**, hecho en **Blade puro** (sin Inertia, sin Vue y sin
assets de ARACODE). Está pensado para copiarse a su propio dominio junto con todo su ecosistema.

## Qué contiene

| Zona | Contenido |
| --- | --- |
| `resources/views/kirafact/` | `layouts/`, `components/` y `pages/` del sitio |
| `resources/css/kirafact/` | Fuente de estilos (`kirafact.css` + `tailwind.config.js` propio) |
| `public/themes/kirafact/` | `css/` compilado, `js/app.js` y `images/` que sirve el navegador |
| `routes/kirafact.php` | Rutas del sitio (Blade puro, sin controladores) |

## Rutas

| URL en este repositorio | Vista |
| --- | --- |
| `/site/kirafact` | `kirafact.pages.home` |
| `/site/kirafact/planes` | `kirafact.pages.planes` |
| `/site/kirafact/contacto` | `kirafact.pages.contacto` |

El prefijo `site/kirafact` existe solo para convivir con el sitio ARACODE en este repositorio.

## Compilar el CSS

```bash
npm run build:kirafact
```

Genera `public/themes/kirafact/css/kirafact.css` a partir de `resources/css/kirafact/kirafact.css`
y de su propia config de Tailwind.

## Copiar el sitio a su propio dominio

1. Copia estas cuatro zonas juntas:
   - `resources/views/kirafact/`
   - `resources/css/kirafact/`
   - `public/themes/kirafact/`
   - y el archivo de rutas `routes/kirafact.php`
2. En `routes/kirafact.php` cambia `$prefix` a `''` para que el sitio quede en la raíz.
3. Vuelve a compilar el CSS si tocas estilos: `npm run build:kirafact`.

## Notas

- La identidad visual es propia de KiraFact: no depende del `tailwind.config.js` de la raíz
  ni de `webpage-v2.css`.
- `js/app.js` es vanilla, sin bundler: se sirve tal cual desde el tema.
- Los precios de los planes están en `resources/views/kirafact/pages/planes.blade.php`.
