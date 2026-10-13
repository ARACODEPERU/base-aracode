# Créditos y reemplazos de la demostración «Restaurante y carta digital»

Es una **landing** del Banco de Plantillas Web, del tipo que abre la plantilla de referencia
`public/demos/clinica-dental/`. La lista obligatoria que cumple está en
[`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md).

## Fotografías

Las seis fotos se descargaron de [Wikimedia Commons](https://commons.wikimedia.org) con el filtro de
licencias **sin obligaciones** (CC0 / dominio público), así que se pueden usar en una demostración
comercial sin pedir permiso ni citar al autor. Están reescaladas, recortadas a las proporciones del
banco y convertidas a WebP, lo que además borra los metadatos EXIF de los originales. Ninguna lleva
marca de agua: se revisaron a tamaño real antes de publicarlas.

Las tres primeras son las del **slider del hero**, a pantalla completa y por eso horizontales (16/11),
y son tres fotos distintas entre sí: el plato estrella, la parrilla y el comedor. La vertical de
`cocina.webp` (4/5) abre la sección «Nuestra cocina» y los dos detalles (16/10) van en esa misma
sección.

| Archivo | Tamaño | Dónde se usa | Foto original | Autor | Licencia |
| --- | --- | --- | --- | --- | --- |
| `img/portada.webp` | 1024 × 704 | Hero (1.ª foto) | [*Ceviche de pescado (La Punta, Callao)*](https://commons.wikimedia.org/wiki/File:Ceviche_de_pescado_(La_Punta,_Callao).jpg) | Jorge G. Mori | Dominio público |
| `img/parrilla.webp` | 1024 × 704 | Hero (2.ª foto) | [*Pollo a la brasa pierna con papas*](https://commons.wikimedia.org/wiki/File:Pollo_a_la_brasa_pierna_con_papas.JPG) | Dtarazona | Dominio público |
| `img/comedor.webp` | 1024 × 704 | Hero (3.ª foto) | [*Eat at the Flamingo Restaurant*](https://commons.wikimedia.org/wiki/File:Eat_at_the_Flamingo_Restaurant_(53468120609).jpg) | evergladesnps | Dominio público |
| `img/cocina.webp` | 819 × 1024 | «Nuestra cocina» (vertical) | [*Yes, Chef!*](https://commons.wikimedia.org/wiki/File:Yes,_Chef!_(8658138).jpg) | U.S. Air Force, Airman 1st Class Zachary Jakel | Dominio público |
| `img/causa.webp` | 1200 × 750 | Detalle de la causa limeña | [*Causa limeña 2023*](https://commons.wikimedia.org/wiki/File:Causa_lime%C3%B1a_2023.jpg) | GORE Callao | Dominio público |
| `img/postres.webp` | 1200 × 750 | Detalle de los postres | [*Los Canarios, restaurant, dessert selection*](https://commons.wikimedia.org/wiki/File:Los_Canarios,_restaurant,_dessert_selection.jpg) | Gerda Arendt | CC0 |

Nota de trazabilidad: las seis fichas se anotaron en el momento de la descarga, con el título del
archivo en Commons y su autor; el informe quedó en `storage/tmp/restaurante-fotos.json` y la búsqueda
se hizo con `storage/tmp/fotos-restaurante-commons.php` (las carpetas temporales de candidatas se
borran al terminar). Las fotos vienen de distintos países, así que **son de ejemplo**: al publicar el
sitio se reemplazan por las del restaurante (ver más abajo).

`img/mapa.svg` no es una foto: es un plano dibujado a mano para la demo (sin scripts de mapas de
terceros). El botón «Abrir en Google Maps» del HTML sí lleva a la dirección del negocio.

## Qué se reemplaza al publicar el sitio de un cliente

1. **Las seis fotos** por las del negocio. Las tres del slider del hero van primero: se ven a
   pantalla completa, así que piden horizontales de 1600 px de ancho o más y **tres fotos distintas**
   entre sí (repetir una se nota al avanzar el carrusel). Se cambian dejando los mismos nombres de
   archivo (`portada`, `parrilla`, `comedor`, `cocina`, `causa`, `postres`) y el HTML no se toca;
   solo su `alt`, que describe la foto. La vertical de la cocina es 4/5 y los detalles, 16/10.
2. **El plano** por el mapa embebido que prefiera el cliente, o se mantiene el plano propio.
3. **La carta y los precios**: los cuatro bloques de platos viven en `index.html` (entradas, fondos
   y parrilla, postres y bebidas) y el aviso de la sección recuerda que son de referencia. Al
   publicar conviene revisar que no quede ningún «S/» de ejemplo suelto.
4. **Las promociones**: las tres tarjetas de la sección «Promociones» llevan las fechas y
   condiciones inventadas (lunes a viernes, martes de ceviche). En el sitio real las activa y
   desactiva el negocio.
5. **Los datos de ejemplo**: nombre del restaurante, dirección, teléfonos, correo, horarios, cifras,
   nombre del chef y testimonios. Están todos en `index.html`; buscar «Fogón Criollo» y «S/» los
   encuentra.
6. **Los enlaces de redes sociales**: hoy apuntan a la plataforma (`instagram.com`, `facebook.com`,
   `tiktok.com`, `wa.me`) porque son de demostración; van a los perfiles del negocio.
7. **El número de WhatsApp**: en la demo es `+51 900 000 000`, un número que no existe, para que
   nadie reciba mensajes de prueba por error.
8. **El formulario de reserva**: valida en el navegador y avisa de lo que falta, pero no envía nada.
   Al publicarlo se conecta al correo del negocio o a un servicio de formularios, y entonces se
   quita `data-demo="true"` del `<form>` (el JavaScript envía solo cuando ese atributo no está).

Las fotos de personas que aparezcan en las fichas definitivas deben ser del propio negocio o tener
permiso de uso: es la diferencia entre una demo y el sitio de un cliente.
