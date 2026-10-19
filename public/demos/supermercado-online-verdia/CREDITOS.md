# Créditos y reemplazos de la demostración «Supermercado Online — VERDIA Market»

Esta es una plantilla de **formato tienda** del Banco de Plantillas: cuatro páginas enlazadas
(`index.html`, `tienda.html`, `producto.html` y `canasta.html`) con un solo `css/estilos.css` y un
solo `js/app.js`, más `js/datos.js` con el catálogo de la demostración. La lista obligatoria que
cumple está en [`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md).

**VERDIA Market es una marca ficticia.** El nombre, el logotipo (`img/logo.svg`, dibujo vectorial
propio), los productos, los precios, el código de bienvenida `VERDIA10`, el teléfono, el correo y la
dirección son inventados y así se declara en la barra de aviso de las cuatro páginas y en el pie.

El diseño es propio y no comparte hoja de estilos ni JavaScript con ninguna otra demo (regla de
diferenciación): **«supermercado de barrio contemporáneo»**. Fondo marfil, superficies blancas,
tinta verde oscura para el texto, **verde bosque** `#17633D` para la acción y **naranja** `#F39A35`
reservado a las ofertas y a los precios rebajados, con verde claro `#EAF4E7` en los fondos suaves.
Las tarjetas de producto son cuadradas con la foto grande, el precio en número grande y el botón de
añadir sólido; las esquinas son redondeadas y las sombras bajas, para que el color lo pongan las
fotos de comida. La tipografía es la sans del sistema, con titulares juntos y precios en negrita.

Al copiar la carpeta para la tienda de un cliente, lo primero que conviene revisar son las variables
de color del principio de `css/estilos.css` y el objeto `VERDIA_TIENDA` de `js/datos.js`.

## Fotografías

Las 39 fotos se descargaron de [Wikimedia Commons](https://commons.wikimedia.org) —lotes del
banco de imágenes Unsplash subidos a Commons y fotos sueltas de dominio público— con el filtro de
licencias **sin obligaciones** (CC0 o dominio público), así que se pueden usar en una demostración
comercial sin pedir permiso ni citar al autor.

Se buscan con `storage/tmp/fotos-verdia-commons.php` (términos en `storage/tmp/verdia-temas*.json`),
se revisan con `storage/tmp/verdia-revisar.php`, que arma hojas de contactos para mirarlas antes de
recortarlas, y lo elegido se recorta y se pasa a WebP con `storage/tmp/fotos-verdia-preparar.php`.
Este informe, la tabla de abajo y el `CREDITOS.md` se generan desde `storage/tmp/verdia-creditos.php`.

Ninguna pesa más de 250 KB y ninguna lleva marca de agua. Los recortes son 16/10 en la portada, 3/2
en las franjas y el bloque de confianza, 4/3 en los pasillos, 1/1 en las tarjetas de producto y 16/9
en el banner de bienvenida; el paso a WebP borra además los metadatos EXIF.

| Archivo | Tamaño | Dónde se usa | Foto original | Autor | Licencia | Ficha |
| --- | --- | --- | --- | --- | --- | --- |
| `img/portada-cesta.webp` | 1280 × 800 | Portada (1.ª foto) | *Healthy Grocery Shopping (Unsplash)* | leonie wise leoniewise | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Healthy_Grocery_Shopping_(Unsplash).jpg) |
| `img/portada-mercado.webp` | 1280 × 800 | Portada (2.ª foto) | *Farmer's Market Produce (Unsplash)* | Julian Hanslmaier j_h | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Farmer%27s_Market_Produce_(Unsplash).jpg) |
| `img/portada-pan.webp` | 1280 × 800 | Portada (3.ª foto) | *Man holding bread (Unsplash)* | Kate Remmer studioktr | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Man_holding_bread_(Unsplash).jpg) |
| `img/banda-frescos.webp` | 1200 × 800 | Franja «Frescos del día» | *Healthy Fruit (Unsplash)* | Tim Gouw punttim | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Healthy_Fruit_(Unsplash).jpg) |
| `img/banda-abarrotes.webp` | 1200 × 800 | Franja «Despensa de fondo» | *Bellingham Farmers Market 1 (Unsplash)* | Amelia Bartlett ameliabartlett | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Bellingham_Farmers_Market_1_(Unsplash).jpg) |
| `img/banda-saludable.webp` | 1200 × 800 | Franja «Natural y saludable» | *Brooke Lark 2017-03-27 (Unsplash)* | Brooke Lark brookelark | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Brooke_Lark_2017-03-27_(Unsplash).jpg) |
| `img/rubro-frutas.webp` | 800 × 600 | Pasillo · Frutas y verduras | *Green shop (Unsplash)* | ja ma ja_ma | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Green_shop_(Unsplash).jpg) |
| `img/rubro-lacteos.webp` | 800 × 600 | Pasillo · Lácteos y huevos | *Fresh Eggs (Unsplash fBturNUtd8)* | Brina Blum brina_blum | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Fresh_Eggs_(Unsplash_fBturNUtd8).jpg) |
| `img/rubro-carnes.webp` | 800 × 600 | Pasillo · Carnes y pescados | *Butcher's Shop (Unsplash)* | Lukas Budimaier lukasbudimaier | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Butcher%27s_Shop_(Unsplash).jpg) |
| `img/rubro-panaderia.webp` | 800 × 600 | Pasillo · Panadería | *Fresh Bread and Butter (Unsplash)* | Brooke Cagle brookecagle | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Fresh_Bread_and_Butter_(Unsplash).jpg) |
| `img/rubro-abarrotes.webp` | 800 × 600 | Pasillo · Abarrotes | *Pantry shelf (Unsplash)* | Chris Lawton chrislawton | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Pantry_shelf_(Unsplash).jpg) |
| `img/rubro-bebidas.webp` | 800 × 600 | Pasillo · Bebidas | *Brooke Lark 2016 (Unsplash)* | Brooke Lark brookelark | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Brooke_Lark_2016_(Unsplash).jpg) |
| `img/rubro-snacks.webp` | 800 × 600 | Pasillo · Snacks | *Cookies (Unsplash)* | Mara Conan maraconan | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Cookies_(Unsplash).jpg) |
| `img/rubro-limpieza.webp` | 800 × 600 | Pasillo · Limpieza del hogar | *Jay Wennington 2014-10-28 (Unsplash OLIcAFggdZE)* | Jay Wennington jaywennington | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Jay_Wennington_2014-10-28_(Unsplash_OLIcAFggdZE).jpg) |
| `img/rubro-cuidado.webp` | 800 × 600 | Pasillo · Cuidado personal | *Hand crafted soap (Unsplash)* | Kristina Balić daisyk9 | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Hand_crafted_soap_(Unsplash).jpg) |
| `img/p-naranjas.webp` | 700 × 700 | Producto · Naranjas de jugo | *Basket of blood oranges (Unsplash)* | Linh Pham lhpham12 | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Basket_of_blood_oranges_(Unsplash).jpg) |
| `img/p-arandanos.webp` | 700 × 700 | Producto · Arándanos frescos | *Blueberries at the Market (Unsplash)* | veeterzy veeterzy | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Blueberries_at_the_Market_(Unsplash).jpg) |
| `img/p-pepinos.webp` | 700 × 700 | Producto · Pepino criollo | *Cucumbers (Unsplash)* | Harshal Hirve harshalhirve | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Cucumbers_(Unsplash).jpg) |
| `img/p-zanahorias.webp` | 700 × 700 | Producto · Zanahoria fresca | *Fresh Carrots (Unsplash)* | Jonathan Pielmayer jonathanpielmayer | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Fresh_Carrots_(Unsplash).jpg) |
| `img/p-pan.webp` | 700 × 700 | Producto · Pan de campo integral | *Fresh Bread and Butter (Unsplash)* | Brooke Cagle brookecagle | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Fresh_Bread_and_Butter_(Unsplash).jpg) |
| `img/p-huevos.webp` | 700 × 700 | Producto · Huevos de campo | *Basket Eggs Colors (Unsplash)* | Autumn Mott autumnmott | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Basket_Eggs_Colors_(Unsplash).jpg) |
| `img/p-yogur.webp` | 700 × 700 | Producto · Yogur natural con frutas | *Chia jars with bleuberries (Unsplash)* | Brenda Godinez cravethebenefits | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Chia_jars_with_bleuberries_(Unsplash).jpg) |
| `img/p-queso.webp` | 700 × 700 | Producto · Queso semiduro al corte | *Brie and crackers (Unsplash)* | Anita Peeples anitapeeples | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Brie_and_crackers_(Unsplash).jpg) |
| `img/p-cafe.webp` | 700 × 700 | Producto · Café tostado en grano | *A lot of coffee beans* | www.Pixel.la Free Stock Photos | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:A_lot_of_coffee_beans.jpg) |
| `img/p-pescado.webp` | 700 × 700 | Producto · Filete de pescado fresco | *Fish at the Market (Unsplash)* | Jakub Kapusnak foodiesfeed | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Fish_at_the_Market_(Unsplash).jpg) |
| `img/p-galletas.webp` | 700 × 700 | Producto · Galletas de avena y canela | *Christmas Tabletop (Unsplash)* | Mira Bozhko miroslava | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Christmas_Tabletop_(Unsplash).jpg) |
| `img/p-platanos.webp` | 700 × 700 | Producto · Plátano de seda | *Green Plantains (Unsplash)* | Henry Doe henrydoe | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Green_Plantains_(Unsplash).jpg) |
| `img/p-tomates.webp` | 700 × 700 | Producto · Tomate italiano | *Tomato Garden (Unsplash)* | Elaine Casap ecasap | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Tomato_Garden_(Unsplash).jpg) |
| `img/p-cereal.webp` | 700 × 700 | Producto · Cereal de avena y miel | *Cereal with Milk (Unsplash)* | Ryan Pouncy pixelperfect | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Cereal_with_Milk_(Unsplash).jpg) |
| `img/p-croissant.webp` | 700 × 700 | Producto · Croissants de mantequilla | *Latte and croissant (Unsplash)* | Sergey Zolkin szolkin | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Latte_and_croissant_(Unsplash).jpg) |
| `img/p-hogaza.webp` | 700 × 700 | Producto · Hogaza artesana | *Bread (Unsplash)* | Kim Cruickshanks helloimkim | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Bread_(Unsplash).jpg) |
| `img/p-pasta.webp` | 700 × 700 | Producto · Pasta fresca artesanal | *Handmade pasta noodles (Unsplash)* | Sarah Boyle sboyle | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Handmade_pasta_noodles_(Unsplash).jpg) |
| `img/o-judias.webp` | 800 × 800 | Oferta · Judías verdes | *Fresh Green Beans (Unsplash)* | Sonja Langford unmorceau | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Fresh_Green_Beans_(Unsplash).jpg) |
| `img/o-fresas.webp` | 800 × 800 | Oferta · Fresas del valle | *Bowl of Strawberries (Unsplash)* | Roberta Sorge robertina | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Bowl_of_Strawberries_(Unsplash).jpg) |
| `img/o-filete.webp` | 800 × 800 | Oferta · Filete de res | *Steak and Herbs (Unsplash)* | Jez Timms jeztimms | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Steak_and_Herbs_(Unsplash).jpg) |
| `img/o-tomate.webp` | 800 × 800 | Oferta · Salsa de tomate casera | *Fresh Tomato Sauce (Unsplash)* | Dennis Klein klein2 | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Fresh_Tomato_Sauce_(Unsplash).jpg) |
| `img/confianza-puesto.webp` | 1000 × 667 | Bloque de confianza (vertical) | *Street Fruit Seller at work (Unsplash)* | Igor Ovsyannykov igorovsyannykov | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Street_Fruit_Seller_at_work_(Unsplash).jpg) |
| `img/confianza-mercado.webp` | 1000 × 667 | Bloque de confianza | *Asian food market (Unsplash)* | Les Anderson lesanderson | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Asian_food_market_(Unsplash).jpg) |
| `img/bienvenida.webp` | 1280 × 720 | Banner de bienvenida | *Fruity breakfast (Unsplash)* | Nirzar Pangarkar nirzar | CC0 | [Commons](https://commons.wikimedia.org/wiki/File:Fruity_breakfast_(Unsplash).jpg) |

El **logotipo** (`img/logo.svg`) y el **plano** no existen: no hay plano en esta demo, así que no se
carga ningún script de mapas ni se muestra una dirección sobre un mapa. La dirección del pie es
ficticia y está marcada como tal.

## Qué reemplazar en un supermercado real

1. **Fotos de producto.** Las del catálogo son de banco: al publicar se cambian por las del producto
   que se vende, con el mismo nombre de archivo o actualizando `foto` en `VERDIA_PRODUCTOS`.
2. **Datos del negocio.** Nombre, razón social, teléfono, WhatsApp, correo, dirección y horarios
   están en el objeto `VERDIA_TIENDA` de `js/datos.js` y, copiados, en el pie de las cuatro páginas;
   están marcados con comentarios para reemplazarlos de una vez.
3. **Catálogo y precios.** Los veintiún productos, sus presentaciones, precios y precios anteriores
   viven en `VERDIA_PRODUCTOS` (`js/datos.js`): en una tienda real llegan de la base de datos y del
   sistema del negocio. Los montos de la demostración son de referencia y no incluyen IGV.
4. **Pasillos y necesidades.** Los nueve pasillos y las cuatro selecciones de «Compra por necesidad»
   están en `VERDIA_RUBROS` y `VERDIA_NECESIDADES`; se ajustan a la góndola del cliente.
5. **El cierre del pedido.** Hoy el botón arma un recibo de prueba y avisa de que no se envió nada.
   En producción se conecta a WhatsApp (enlace `wa.me` con el mensaje ya escrito), a un correo o a la
   pasarela de pago; el detalle que se muestra en el recibo es el mismo texto que viajaría.
6. **La entrega.** Las opciones del formulario («Recojo en tienda», «Entrega a domicilio») y el
   monto de envío gratis (`VERDIA_TIENDA.envioGratis`) son del medio peruano y se ajustan al negocio.
7. **Textos legales.** No hay política de privacidad ni términos: en un sitio real se agregan, y el
   aviso de la casilla de consentimiento se ajusta a la ley aplicable.
