# Créditos y reemplazos de la demostración «Clínica Dental»

Esta demo es la **plantilla de referencia** del Banco de Plantillas Web: lo que trae aquí es lo
que debe traer toda plantilla nueva. La lista obligatoria está en
[`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md).

## Fotografías

Las cinco fotos se descargaron de [Openverse](https://openverse.org) con el filtro de licencias
**sin obligaciones** (CC0 / dominio público), así que se pueden usar en una demostración comercial
sin pedir permiso ni citar al autor. Están reescaladas y convertidas a WebP (a 1024 px de ancho como
máximo), que además borra los metadatos EXIF de los originales. Ninguna lleva marca de agua: se miró
a tamaño real antes de publicarla (una primera candidata, con marca de `rawpixel` y uniforme
militar, se descartó por eso).

Las tres primeras son las del **slider del hero** —el fondo a pantalla completa, por eso son
horizontales y tienen que ser tres fotos distintas entre sí—; la vertical de `atencion.webp` y el
detalle de `instrumental.webp` van dentro de las secciones.

| Archivo | Tamaño | Dónde se usa | Foto original | Procedencia | Licencia |
| --- | --- | --- | --- | --- | --- |
| `img/portada.webp` | 1024 × 686 | Hero (1.ª foto) | *Dental clinic* | [rawpixel #6020787](https://www.rawpixel.com/image/6020787/dental-clinic-free-public-domain-cc0-photo) | CC0 |
| `img/consulta.webp` | 1024 × 683 | Hero (2.ª foto) | *CP22 - WPS Provides Medical* (U.S. Navy Medicine) | [rawpixel #11073079](https://www.rawpixel.com/image/11073079/photo-image-face-person-medicine) | CC0 |
| `img/diagnostico.webp` | 1024 × 680 | Hero (3.ª foto) | *Lilongwe, Malawi – Col. Sheldon* | [rawpixel #8759403](https://www.rawpixel.com/image/8759403/photo-image-public-domain-doctor-sunglasses) | CC0 |
| `img/atencion.webp` | 681 × 1024 | Sección de confianza (vertical) | *110704-F-NJ219-012* | Wikimedia Commons vía Openverse | CC0 |
| `img/instrumental.webp` | 1200 × 900 | Detalle de instrumental | *Collection of dental instruments attached to a dental chair* | Wikimedia Commons vía Openverse | CC0 |

Nota de trazabilidad: las fichas de las tres fotos del hero se comprobaron comparando la huella de
luminancia de cada archivo con las miniaturas de Openverse (coincidencia exacta); las dos de las
secciones se anotaron al descargarlas. Las tres del slider salen de la colección CC0 de rawpixel,
que republica fotos del Ejército y la Armada de EE. UU.; el original de `diagnostico.webp` también
circula en Flickr bajo CC BY 2.0, pero la ficha que se usó —y la que se cita— es la CC0.

`img/mapa.svg` no es una foto: es un plano dibujado a mano para la demo (sin scripts de mapas de
terceros). El botón «Abrir en Google Maps» del HTML sí lleva a la dirección del negocio.

## Qué se reemplaza al publicar el sitio de un cliente

1. **Las cinco fotos** por las del negocio. Las tres del slider del hero van primero: se ven a
   pantalla completa, así que piden horizontales de 1600 px de ancho o más y **tres fotos distintas**
   entre sí (repetir una se nota al avanzar el carrusel). Se cambian dejando los mismos nombres de
   archivo (`portada`, `consulta`, `diagnostico`) y el HTML no se toca; solo su `alt`, que describe
   la foto. La vertical de atención es 4/5 y el detalle de instrumental, 16/10.
2. **El plano** por el mapa embebido que prefiera el cliente, o se mantiene el plano propio.
3. **Los datos de ejemplo**: dirección, teléfonos, correo, nombre de la profesional, precios,
   testimonios y horarios. Están todos en `index.html`.
4. **Los enlaces de redes sociales**: hoy apuntan a la plataforma (`instagram.com`, `facebook.com`,
   `tiktok.com`, `wa.me`) porque son de demostración; van a los perfiles del negocio.
5. **El número de WhatsApp**: en la demo es `+51 900 000 000`, un número que no existe, para que
   nadie reciba mensajes de prueba por error.

Las fotos de personas que aparezcan en las fichas definitivas deben ser del propio negocio o
tener permiso de uso: es la diferencia entre una demo y el sitio de un cliente.
