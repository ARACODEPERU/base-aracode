# Créditos y reemplazos de la demostración «Clínica Dental»

Esta demo es la **plantilla de referencia** del Banco de Plantillas Web: lo que trae aquí es lo
que debe traer toda plantilla nueva. La lista obligatoria está en
[`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md).

## Fotografías

Las tres fotos vienen de Wikimedia Commons a través de [Openverse](https://openverse.org),
filtradas por licencias **sin obligaciones** (CC0 / dominio público), así que se pueden usar en una
demostración comercial sin pedir permiso ni citar al autor. Están reescaladas y convertidas a WebP
(a 1024 px de ancho como máximo), que además borra los metadatos EXIF de los originales.

| Archivo | Tamaño | Foto original | Licencia |
| --- | --- | --- | --- |
| `img/portada.webp` | 1024 × 686 | *Dental clinic* | CC0 |
| `img/atencion.webp` | 681 × 1024 | *110704-F-NJ219-012* | CC0 |
| `img/instrumental.webp` | 1200 × 900 | *Collection of dental instruments attached to a dental chair* | CC0 |

`img/mapa.svg` no es una foto: es un plano dibujado a mano para la demo (sin scripts de mapas de
terceros). El botón «Abrir en Google Maps» del HTML sí lleva a la dirección del negocio.

## Qué se reemplaza al publicar el sitio de un cliente

1. **Las tres fotos** por las del negocio, con las mismas proporciones: portada 16/11, atención
   vertical 4/5, detalle 16/10. Se pueden dejar los mismos nombres de archivo y no se toca el HTML.
2. **El plano** por el mapa embebido que prefiera el cliente, o se mantiene el plano propio.
3. **Los datos de ejemplo**: dirección, teléfonos, correo, nombre de la profesional, precios,
   testimonios y horarios. Están todos en `index.html`.
4. **Los enlaces de redes sociales**: hoy apuntan a la plataforma (`instagram.com`, `facebook.com`,
   `tiktok.com`, `wa.me`) porque son de demostración; van a los perfiles del negocio.
5. **El número de WhatsApp**: en la demo es `+51 900 000 000`, un número que no existe, para que
   nadie reciba mensajes de prueba por error.

Las fotos de personas que aparezcan en las fichas definitivas deben ser del propio negocio o
tener permiso de uso: es la diferencia entre una demo y el sitio de un cliente.
