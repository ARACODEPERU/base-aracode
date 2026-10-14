# Créditos y reemplazos de la demostración «NÉXORA MARKET»

Esta es una plantilla de **formato tienda** del Banco de Plantillas: cinco páginas enlazadas
(`index.html`, `catalogo.html`, `producto.html`, `carrito.html` y `revista.html`) con un solo
`css/estilos.css` y un solo `js/app.js`. La lista obligatoria que cumple está en
[`docs/BANCO_PLANTILLAS.md`](../../../docs/BANCO_PLANTILLAS.md).

**NÉXORA MARKET es una marca ficticia.** El nombre, el logotipo (`img/logo.svg`, dibujo vectorial
propio), el eslogan «Tu mundo, tus productos, a un clic», los productos, los precios, las
promociones, los códigos, las opiniones, las seis marcas del bloque multimarca, la dirección y el
teléfono son inventados, y así se declara en la franja de aviso de las cinco páginas y en el pie.

El diseño es propio y no comparte hoja de estilos ni JavaScript con ninguna otra demo (regla de
diferenciación): **«escaparate de gran almacén»**. El azul noche `#101D30` es el color estructural
(cabecera a sangre, franja de beneficios, panel de canasta y pie), el blanco y el gris `#F2F5F8` son
el papel donde vive el producto, el naranja `#F56A2A` es un único color de acción —lo que se pulsa,
lo rebajado y lo que hay que mirar— y el verde `#209765` solo dice cosas positivas (disponibilidad y
envío sin costo). La arquitectura también es distinta de las otras dos tiendas del banco: la
navegación vive en un **mega panel** con las seis categorías y sus colecciones, el catálogo filtra
por categoría, colección, oferta, favoritos y precio máximo con el estado escrito en la URL, y la
canasta es un panel lateral presente en las cinco páginas. La tipografía es la sans del sistema, con
etiquetas en mayúscula espaciada, titulares de seguimiento cerrado y precios con cifras tabulares.

Al copiar la carpeta para la tienda de un cliente, lo primero que conviene tocar son las variables
del principio de `css/estilos.css` y los arreglos `CATEGORIAS` y `PRODUCTOS` de `js/app.js`.

## Fotografías

Las 43 fotos se descargaron de dos fuentes de material libre —[Wikimedia
Commons](https://commons.wikimedia.org) y la API de [Openverse](https://openverse.org)— con el
filtro de licencias del banco: **sin obligaciones** (CC0 o dominio público) o **con atribución
simple** (CC BY). Quedan fuera las que añaden condiciones que una plantilla que se revende no puede
cumplir: compartir igual (SA), no comercial (NC) y sin obra derivada (ND); el recorte y el reescalado
a WebP son obra derivada.

De las 43, **17 son CC BY** y su atribución obligatoria es la de la tabla de
abajo (autor y enlace a la ficha original). Las demás son CC0 o de dominio público y no exigen
crédito, pero se anotan igual para poder rastrear el origen.

Se buscan con `storage/tmp/fotos-nexora-fuentes.php` (términos en `storage/tmp/nexora-temas*.json`),
se revisan con `storage/tmp/nexora-finalistas.php` y `storage/tmp/nexora-auditar.php`, y lo elegido
se recorta y se pasa a WebP con `storage/tmp/fotos-nexora-preparar.php`, que lee la receta de
`storage/tmp/nexora-seleccion.json`. Este informe y la tabla se generan con
`storage/tmp/nexora-creditos.php`.

Ninguna pesa más de 250 KB y ninguna lleva marca de agua. Los recortes son 16/10 en la portada, 4/3
en las tarjetas de categoría, 3/2 en la franja promocional y en las guías de compra, 11/8 en los dos
bloques editoriales, 3/4 en la foto vertical del bloque de confianza y 1/1 en las tarjetas de
producto; el paso a WebP borra además los metadatos EXIF.

| Archivo | Tamaño | Dónde se usa | Foto original | Autor | Licencia | Ficha |
| --- | --- | --- | --- | --- | --- | --- |
| `img/portada-tecnologia.webp` | 1280 × 800 | PORTADA 1 · tienda de tecnología | *Sonos products at Walmart* | Nielsoncaetanosalmeron | CC BY | [Openverse](https://commons.wikimedia.org/w/index.php?curid=177841902) |
| `img/portada-moda.webp` | 1280 × 800 | PORTADA 2 · boutique de moda | *Man examines clothing in a boutique while shopping for fa…* | Shixart1985 | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Man_examines_clothing_in_a_boutique_while_shopping_for_fashionable_attire_in_a_trendy_store_setting.jpg) |
| `img/portada-hogar.webp` | 1280 × 800 | PORTADA 3 · hogar | *Modern kitchen and dining area in a simple home setting s…* | Shixart1985 | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Modern_kitchen_and_dining_area_in_a_simple_home_setting_showing_light_colors_and_minimal_decor.jpg) |
| `img/rubro-tecnologia.webp` | 800 × 600 | RUBRO tecnología | *A flat lay of everyday items on a wooden surface, includi…* | Nur Nobi | CC0 | [Openverse](https://wordpress.org/photos/photo/14568e4010/) |
| `img/rubro-moda.webp` | 800 × 600 | RUBRO moda | *JP 沖繩 Okinawa 那霸市 Naha City shop Suit Select male clothin…* | NORTLAUKAU 26088 JP | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:JP_%E6%B2%96%E7%B9%A9_Okinawa_%E9%82%A3%E9%9C%B8%E5%B8%82_Naha_City_shop_Suit_Select_male_clothing_store_February_2026_N13P_01.jpg) |
| `img/rubro-hogar.webp` | 800 × 600 | RUBRO hogar | *Modern kitchen and dining area with stylish furnishings a…* | Shixart1985 | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Modern_kitchen_and_dining_area_with_stylish_furnishings_and_natural_light_in_a_contemporary_home_setting.jpg) |
| `img/rubro-belleza.webp` | 800 × 600 | RUBRO belleza | *HK KT 觀塘 Kwun Tong 創紀之城 APM mall shop beautycare make-up…* | MAISONWM 6628 LSINGAOR | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:HK_KT_%E8%A7%80%E5%A1%98_Kwun_Tong_%E5%89%B5%E7%B4%80%E4%B9%8B%E5%9F%8E_APM_mall_shop_beautycare_make-up_cosmetic_products_January_2026_N13P_01.jpg) |
| `img/rubro-deportes.webp` | 800 × 600 | RUBRO deportes | *Fitness equipment in a modern gym with various machines a…* | Shixart1985 | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Fitness_equipment_in_a_modern_gym_with_various_machines_and_workout_space_during_the_morning_hours.jpg) |
| `img/rubro-juguetes.webp` | 800 × 600 | RUBRO juguetes | *Caregiver reading to child in a bright, colorful playroom…* | Shixart1985 | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Caregiver_reading_to_child_in_a_bright,_colorful_playroom_during_a_warm_afternoon.jpg) |
| `img/franja-tecnologia.webp` | 1200 × 800 | FRANJA tecnología | *Stockholm man with headphones (Unsplash)* | Yingchou Han hyingchou | CC0 | [Openverse](https://commons.wikimedia.org/w/index.php?curid=61850571) |
| `img/editorial-estilo.webp` | 1100 × 800 | EDITORIAL estilo | *A hand confidently holds a makeup brush* | Shixart1985 | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:A_hand_confidently_holds_a_makeup_brush.jpg) |
| `img/editorial-hogar.webp` | 1100 × 800 | EDITORIAL hogar | *Balanced Modern Bedroom Design with Neutral Tones and Lay…* | SPL Interiors1 | CC BY 4.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Balanced_Modern_Bedroom_Design_with_Neutral_Tones_and_Layered_Lighting.jpg) |
| `img/guia-tecnologia.webp` | 900 × 600 | BLOG accesorios de tecnología | *Professional woman enjoying coffee while working on lapto…* | Shixart1985 | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Professional_woman_enjoying_coffee_while_working_on_laptop_at_a_cozy_desk_in_a_well-lit_room_during_morning_hours.jpg) |
| `img/guia-hogar.webp` | 900 × 600 | BLOG renovar el hogar | *Cordless vacuum cleaner in the kitchen closeup* | Shixart1985 | CC BY | [Openverse](https://commons.wikimedia.org/w/index.php?curid=187705076) |
| `img/guia-estilo.webp` | 900 × 600 | BLOG tendencias de estilo | *Woman in professional attire typing on laptop with handcu…* | Shixart1985 | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Woman_in_professional_attire_typing_on_laptop_with_handcuffs_on_desk_near_coffee_cup_in_modern_office_setting.jpg) |
| `img/confianza-surtido.webp` | 1000 × 1333 | VERTICAL confianza | *非耐久財 家事用消耗品 (52479950072)* | DLKR from Tokyo, Japan | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:%E9%9D%9E%E8%80%90%E4%B9%85%E8%B2%A1_%E5%AE%B6%E4%BA%8B%E7%94%A8%E6%B6%88%E8%80%97%E5%93%81_(52479950072).jpg) |
| `img/p-auriculares.webp` | 800 × 800 | PRODUCTO auriculares over-ear | *Audio Technica ATH-M50 Headphones* | Fletcher6 | CC BY | [Openverse](https://commons.wikimedia.org/w/index.php?curid=39415848) |
| `img/p-earbuds.webp` | 800 × 800 | PRODUCTO auriculares inalámbricos | *Technics-EAH-AZ60M2 09* | RuinDig/Yuki Uchida | CC BY | [Openverse](https://commons.wikimedia.org/w/index.php?curid=140784750) |
| `img/p-reloj.webp` | 800 × 800 | PRODUCTO reloj inteligente | *Teal leather handbag clipart, fashion* | sin firma en el origen | CC0 | [Openverse](https://www.rawpixel.com/image/6588682/image-public-domain-blue-illustrations) |
| `img/p-altavoz.webp` | 800 × 800 | PRODUCTO altavoz portátil | *JBL CHARGE3 という Portable Bluetooth Speaker 、疲れない音ですね。車載する…* | Masahiko OHKUBO from Kobe, Japan | CC BY | [Openverse](https://commons.wikimedia.org/w/index.php?curid=131416456) |
| `img/p-altavoz-inteligente.webp` | 800 × 800 | PRODUCTO altavoz inteligente | *Alexa Amazon Echo Dot 3rd Gen Smart Speaker - Sandstone F…* | Smart Home Perfected | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Alexa_Amazon_Echo_Dot_3rd_Gen_Smart_Speaker_-_Sandstone_Fabric_(Creative_Commons)_(51048461088).jpg) |
| `img/p-camara.webp` | 800 × 800 | PRODUCTO cámara mirrorless | *Canon EOS R10.tif* | PJ | CC BY 4.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Canon_EOS_R10.tif) |
| `img/p-raton.webp` | 800 × 800 | PRODUCTO ratón gamer | *Logitech MX-518* | Xanor | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Logitech_MX-518.JPG) |
| `img/p-dron.webp` | 800 × 800 | PRODUCTO dron | *Drone flying and surveying (50272629796)* | Oregon Department of Transportation | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Drone_flying_and_surveying_(50272629796).jpg) |
| `img/p-smartphone.webp` | 800 × 800 | PRODUCTO smartphone | *Black and red tech (Unsplash twE-bTk0BnM)* | Daria Nepriakhina epicantus | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Black_and_red_tech_(Unsplash_twE-bTk0BnM).jpg) |
| `img/p-zapatillas.webp` | 800 × 800 | PRODUCTO zapatillas | *Nike Air Monarch IV* | LeDroider | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Nike_Air_Monarch_IV.jpg) |
| `img/p-gafas.webp` | 800 × 800 | PRODUCTO gafas de sol | *Jan 05 2011 -Day 065- "Retro Sunglasses" (5328176833)* | JamesInOregon | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Jan_05_2011_-Day_065-_%22Retro_Sunglasses%22_(5328176833).jpg) |
| `img/p-bolso.webp` | 800 × 800 | PRODUCTO bolso de cuero | *HK SKD 寶琳 Po Lam MCL two Central mall shop 包浩斯 Bauhaus 手袋…* | Chayquo Shumo | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:HK_SKD_%E5%AF%B6%E7%90%B3_Po_Lam_MCL_two_Central_mall_shop_%E5%8C%85%E6%B5%A9%E6%96%AF_Bauhaus_%E6%89%8B%E8%A2%8B_handbag_store_window_display_May_2024_R12S_39.jpg) |
| `img/p-cafetera.webp` | 800 × 800 | PRODUCTO cafetera | *Dolce Gusto* | Andy1982 | CC BY 3.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Dolce_Gusto.JPG) |
| `img/p-hervidor.webp` | 800 × 800 | PRODUCTO hervidor eléctrico | *Electric water boiler 2014* | Lesekreis | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Electric_water_boiler_2014.JPG) |
| `img/p-licuadora.webp` | 800 × 800 | PRODUCTO licuadora | *Juice Blender* | Nana Pearls | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Juice_Blender.jpg) |
| `img/p-ollas.webp` | 800 × 800 | PRODUCTO juego de ollas | *Cooking pots* | Poecus | Public domain | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Cooking_pots.jpg) |
| `img/p-jarron.webp` | 800 × 800 | PRODUCTO jarrón cerámico | *A. Jans - Vase with floral decor* | Antoine Jans | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:A._Jans_-_Vase_with_floral_decor.jpg) |
| `img/p-cepillo.webp` | 800 × 800 | PRODUCTO cepillo dental eléctrico | *Electric Toothbrush Head (11694299396)* | William Warby from London, England | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Electric_Toothbrush_Head_(11694299396).jpg) |
| `img/p-perfume.webp` | 800 × 800 | PRODUCTO perfume | *Clevelandart 1977.64* | sin firma en el origen | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Clevelandart_1977.64.jpg) |
| `img/p-secador.webp` | 800 × 800 | PRODUCTO secador de pelo | *Hair dryer picture* | Wiki ankit rautela | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Hair_dryer_picture.jpg) |
| `img/p-mancuernas.webp` | 800 × 800 | PRODUCTO mancuernas | *Pair of 8kg dumbbells (Unsplash)* | Cyril Saulnier c_reel | CC0 | [Openverse](https://commons.wikimedia.org/w/index.php?curid=62290561) |
| `img/p-balon.webp` | 800 × 800 | PRODUCTO balón de fútbol | *Soccer ball on ground* | Petey21 | CC0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Soccer_ball_on_ground.jpg) |
| `img/p-yoga.webp` | 800 × 800 | PRODUCTO esterilla de yoga | *Woman on a yoga mat next to a window doing lower back exe…* | Nenad Stojkovic | CC BY | [Openverse](https://commons.wikimedia.org/w/index.php?curid=94842274) |
| `img/p-bloques.webp` | 800 × 800 | PRODUCTO bloques de madera | *Children's Play Blocks* | Unknown authorUnknown author | Public domain | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Children%27s_Play_Blocks.JPG) |
| `img/p-coche.webp` | 800 × 800 | PRODUCTO coche teledirigido | *MOBO TOYS (23935425644)* | JOHN K THORNE PHOTOGRAPHY (JOHN K THORNE FILMS) from NARNIA , Narnia | CC BY 2.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:MOBO_TOYS_(23935425644).jpg) |
| `img/p-peluche.webp` | 800 × 800 | PRODUCTO peluche | *2023-03-01 12-28-00 peluchesTDF* | Thomas Bresson | CC BY 4.0 | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:2023-03-01_12-28-00_peluchesTDF.jpg) |
| `img/p-tren.webp` | 800 × 800 | PRODUCTO tren de madera | *Brio toy train track* | Intothewoods29 (talk) | Public domain | [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Brio_toy_train_track.JPG) |

El **logotipo** (`img/logo.svg`) y el **plano** (`img/mapa.svg`) son dibujos vectoriales propios de
esta plantilla: no vienen de ninguna fuente externa. El plano es una ilustración de ejemplo, no una
captura de un servicio de mapas, y el enlace del bloque de ubicación abre Google Maps con la
dirección ficticia.

## Qué reemplazar en una tienda real

1. **Fotos de producto.** Las de la tabla son de banco o de dominio público: al publicar se cambian
   por las del producto que se vende, con el mismo nombre de archivo o actualizando `foto` en
   `PRODUCTOS` (si son CC BY de otra fuente, la atribución nueva va aquí).
2. **Datos del negocio.** Nombre, eslogan, WhatsApp, teléfono, correo, dirección y horarios están en
   la constante `WHATSAPP` de `js/app.js` y en la cabecera y el pie de las cinco páginas, marcados
   con comentarios para reemplazarlos de una vez.
3. **Catálogo y precios.** Las seis categorías (`CATEGORIAS`), sus colecciones y los veintisiete
   productos (`PRODUCTOS`) viven en `js/app.js`: en una tienda real llegan de la base de datos y del
   sistema del negocio. Los montos son de referencia, no incluyen impuestos y no hay stock real.
4. **Marcas.** Las seis marcas del bloque multimarca (`MARCAS`) son inventadas: se reemplazan por las
   del catálogo real o se quita el bloque.
5. **Opiniones.** Las tres opiniones de la portada son de ejemplo y están rotuladas como tales
   («contenido ficticio»). Se reemplazan por opiniones verificables o se quitan.
6. **El cierre del pedido.** El formulario de `carrito.html` valida en el navegador y muestra un
   comprobante: no cobra, no genera pedidos y no envía datos. El mismo texto del comprobante es el
   que viajaría por WhatsApp (`wa.me` con el mensaje ya escrito) el día que se conecte.
7. **Pagos, envíos y políticas.** Los medios de pago del pie son solo una representación visual, las
   opciones de entrega y el envío sin costo desde S/ 199 son de ejemplo, y no hay política de
   privacidad ni términos: en un sitio real se redactan.
8. **Buscador.** En la demostración busca dentro del catálogo local. En producción se conecta al
   buscador del sistema del negocio.
