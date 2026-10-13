# Créditos y reemplazos de la demostración «Academia de Cursos»

Esta es la **primera plantilla de formato sitio web** del Banco de Plantillas: cuatro páginas
enlazadas (`index.html`, `cursos.html`, la ficha de un curso y `matricula.html`) con su CSS y su
JavaScript compartidos dentro de la carpeta. La lista obligatoria que cumple está en
[`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md); la plantilla de referencia para
formato landing es `public/demos/clinica-dental/`.

## Fotografías

Las siete fotos se descargaron de [Openverse](https://openverse.org) —Wikimedia Commons y
rawpixel— con el filtro de licencias **sin obligaciones** (CC0 / dominio público), así que se
pueden usar en una demostración comercial sin pedir permiso ni citar al autor. Están reescaladas
(1400 px de ancho como máximo) y convertidas a WebP, lo que además borra los metadatos EXIF de los
originales. Ninguna lleva marca de agua: se revisaron a tamaño real antes de publicarlas.

Las tres primeras son las del **slider del hero**, a pantalla completa y por eso horizontales; las
cuatro últimas van en las secciones, dentro de tarjetas.

| Archivo | Tamaño | Dónde se usa | Foto original | Procedencia | Licencia |
| --- | --- | --- | --- | --- | --- |
| `img/clase.webp` | 1400 × 933 | Hero (1.ª foto) | *Arizona Geosciences Student Club 010* (Frank Schulenburg) | Wikimedia Commons vía Openverse | CC0 |
| `img/conferencia.webp` | 1024 × 768 | Hero (2.ª foto) | rawpixel #5959368 | [rawpixel](https://www.rawpixel.com/image/5959368/free-public-domain-cc0-photo) | CC0 |
| `img/practica.webp` | 1024 × 683 | Hero (3.ª foto) | *Woman working laptop* | [rawpixel #5920742](https://www.rawpixel.com/image/5920742/photo-image-background-public-domain-hand) | CC0 |
| `img/aula.webp` | 1024 × 683 | «Cómo se enseña»: laboratorio | — | Wikimedia Commons vía Openverse | CC0 / dominio público |
| `img/grupo.webp` | 1000 × 667 | «Cómo se enseña»: trabajo en grupo | — | Wikimedia Commons vía Openverse | CC0 / dominio público |
| `img/instalaciones.webp` | 1024 × 683 | «Cómo se enseña»: biblioteca | — | Wikimedia Commons vía Openverse | CC0 / dominio público |
| `img/egresados.webp` | 1024 × 682 | Certificación: cierre de promoción | — | Wikimedia Commons vía Openverse | CC0 / dominio público |

Nota de trazabilidad: las cuatro fotos de las secciones salieron de una búsqueda con
`storage/tmp/fotos-academia.php` (consultas `students laptop computers class`, `computer lab
university`, `study group students table`, `graduation students ceremony`, `university library
students`) y su carpeta temporal se borró al terminar, así que de esas no se conservó el título
original. No es un problema de licencia —CC0 y dominio público no exigen atribución—, pero si se
quiere la ficha exacta hay que volver a buscarlas en Openverse con esas consultas.

`img/mapa.svg` no es una foto: es un plano dibujado a mano para la demo (sin scripts de mapas de
terceros). El botón «Abrir en Google Maps» del HTML sí lleva a la dirección del negocio.

## Qué se reemplaza al publicar el sitio de un cliente

1. **Las siete fotos** por las del negocio. Las tres del slider del hero conviene cambiarlas
   primero: se ven a pantalla completa, así que piden horizontales de 1600 px de ancho o más, y son
   las que más canta que sean de un aula en el extranjero. Basta reemplazar los archivos con los
   mismos nombres (`clase`, `conferencia`, `practica`); el HTML no se toca. Las cuatro de las
   secciones son 3/2 horizontales.
2. **El plano** por el mapa embebido que prefiera el cliente, o se mantiene el plano propio.
3. **Los datos de ejemplo**: nombre de la academia, dirección, teléfonos, correo, docentes,
   testimonios, cifras, horarios, precios, fechas de inicio y número de cupos. Están en las cuatro
   páginas y conviene revisarlos con una búsqueda de «S/» y de «Academia Horizonte».
4. **El catálogo de cursos**: los ocho cursos viven en dos sitios que deben mantenerse a la vez:
   las tarjetas de `cursos.html` (con sus atributos `data-slug`, `data-area` y `data-modalidad`,
   que alimentan el filtro) y las opciones del formulario en `matricula.html`. La ficha
   `curso-desarrollo-web.html` es el modelo para las fichas de los demás: se copia y se cambia el
   texto.
5. **Los enlaces de redes sociales**: hoy apuntan a la plataforma (`instagram.com`, `facebook.com`,
   `tiktok.com`, `youtube.com`, `wa.me`) porque son de demostración; van a los perfiles del negocio.
6. **El número de WhatsApp**: en la demo es `+51 900 000 000`, un número que no existe, para que
   nadie reciba mensajes de prueba por error.
7. **El formulario de matrícula**: valida en el navegador y avisa de lo que falta, pero no envía
   nada. Al publicarlo se conecta al correo del negocio o a un servicio de formularios, y entonces
   se quita `data-demo="true"` del `<form>` (el JavaScript envía solo cuando ese atributo no está).

Las fotos de personas que aparezcan en las fichas definitivas deben ser del propio negocio o tener
permiso de uso: es la diferencia entre una demo y el sitio de un cliente.
