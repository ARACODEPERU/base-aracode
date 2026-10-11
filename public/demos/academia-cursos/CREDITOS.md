# Créditos y reemplazos de la demostración «Academia de Cursos»

Esta es la **primera plantilla de formato sitio web** del Banco de Plantillas: cuatro páginas
enlazadas (`index.html`, `cursos.html`, la ficha de un curso y `matricula.html`) con su CSS y su
JavaScript compartidos dentro de la carpeta. La lista obligatoria que cumple está en
[`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md); la plantilla de referencia para
formato landing es `public/demos/clinica-dental/`.

## Fotografías

Las cinco fotos vienen de Wikimedia Commons a través de [Openverse](https://openverse.org),
descargadas con el filtro de licencias **sin obligaciones** (CC0 / dominio público), así que se
pueden usar en una demostración comercial sin pedir permiso. Están reescaladas a 1024 px de ancho
como máximo y convertidas a WebP, lo que además borra los metadatos EXIF de los originales.

| Archivo | Tamaño | Dónde se usa | Licencia |
| --- | --- | --- | --- |
| `img/portada.webp` | 1024 × 683 | Portada (inicio) | CC0 / dominio público |
| `img/aula.webp` | 1024 × 683 | «Cómo se enseña»: laboratorio | CC0 / dominio público |
| `img/grupo.webp` | 1000 × 667 | «Cómo se enseña»: trabajo en grupo | CC0 / dominio público |
| `img/instalaciones.webp` | 1024 × 683 | «Cómo se enseña»: sala de estudio | CC0 / dominio público |
| `img/egresados.webp` | 1024 × 682 | Certificación: cierre de promoción | CC0 / dominio público |

Nota de trazabilidad: la carpeta temporal de candidatas (20 fotos descargadas con
`storage/tmp/fotos-academia.php`) se borró al terminar, así que no se conservaron los títulos
originales de cada foto. No es un problema de licencia —CC0 y dominio público no exigen
atribución—, pero si se quiere la ficha de origen de una imagen hay que volver a buscarla en
Openverse con las consultas de ese script (`students laptop computers class`, `computer lab
university`, `study group students table`, `graduation students ceremony`, `university library
students`).

`img/mapa.svg` no es una foto: es un plano dibujado a mano para la demo (sin scripts de mapas de
terceros). El botón «Abrir en Google Maps» del HTML sí lleva a la dirección del negocio.

## Qué se reemplaza al publicar el sitio de un cliente

1. **Las cinco fotos** por las del negocio, con proporciones parecidas (3/2 horizontal). Se pueden
   dejar los mismos nombres de archivo y no se toca el HTML.
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
