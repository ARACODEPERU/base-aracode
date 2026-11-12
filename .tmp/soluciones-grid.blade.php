            {{-- Siete soluciones con la misma tarjeta del home: el diseño del
                 producto a sangre, el rubro en píldora, el nombre, la frase
                 corta y la píldora blanca «Leer más» sobre el velo navy.
                 Las frases se dejan en dos o tres líneas: la tarjeta tiene
                 alto fijo y el texto vive sobre el pie oscuro. --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- KAPTA LMS --}}
                <x-v2.product-card
                    title="KAPTA LMS"
                    description="Plataforma SaaS para gestión y formación educativa."
                    :href="$sitiosProducto['kapta']"
                    :nuevaPestana="true"
                    image="{{ asset('themes/webpage/images/misc/s1.jpg') }}"
                    badge="Educación"
                    variante="sobrepuesta"
                    etiqueta="Leer más"
                    :delay="1"
                />

                {{-- KIRAFACT: el nombre comercial se escribe como el producto lo
                     publica (config/kirafact.php «titulo_marca» y los títulos de
                     su propio sitio). Una sola grafía en todo el ecosistema. --}}
                <x-v2.product-card
                    title="KIRAFACT"
                    description="Facturación electrónica y gestión comercial para empresas."
                    :href="$sitiosProducto['kirafact']"
                    :nuevaPestana="true"
                    image="{{ asset('themes/webpage/images/misc/s2.jpg') }}"
                    badge="Empresas"
                    variante="sobrepuesta"
                    etiqueta="Leer más"
                    :delay="2"
                />

                {{-- Pichanguero --}}
                <x-v2.product-card
                    title="Pichanguero"
                    description="Plataforma para organizar torneos de fútbol: fixture, posiciones y estadísticas."
                    :href="$sitiosProducto['pichanguero']"
                    :nuevaPestana="true"
                    image="{{ asset('themes/webpage/images/misc/s3.jpg') }}"
                    badge="Deportes"
                    variante="sobrepuesta"
                    etiqueta="Leer más"
                    :delay="3"
                />

                {{-- Desarrollo: enlace interno, así que abre en la misma
                     pestaña. --}}
                <x-v2.product-card
                    title="Desarrollo a Medida"
                    description="Software a medida para las necesidades específicas de tu empresa."
                    :href="route('solucion_desarrollo')"
                    image="{{ asset('themes/webpage/images/productos/desarrollo-medida.webp') }}"
                    badge="Personalizado"
                    variante="sobrepuesta"
                    etiqueta="Leer más"
                    :delay="4"
                />

                {{-- Automatización: servicio, no producto con web propia. El
                     botón abre el formulario de asesoría (`data-asesoria`), no
                     navega a otra página. --}}
                <x-v2.product-card
                    title="Automatización de Procesos"
                    description="Automatiza los procesos manuales y reduce errores y tiempos."
                    :href="route('contacto')"
                    :ctaDatos="['asesoria' => 'true']"
                    image="{{ asset('themes/webpage/images/productos/automatizacion-procesos.webp') }}"
                    badge="Servicios"
                    variante="sobrepuesta"
                    etiqueta="Solicitar asesoría"
                    :delay="5"
                />

                {{-- IA --}}
                <x-v2.product-card
                    title="Inteligencia Artificial"
                    description="Inteligencia artificial aplicada a tus datos para decidir mejor."
                    :href="route('contacto')"
                    image="{{ asset('themes/webpage/images/productos/inteligencia-artificial.webp') }}"
                    badge="Innovación"
                    variante="sobrepuesta"
                    etiqueta="Conocer más"
                    :delay="6"
                />

                {{-- Páginas Web: el servicio que publica una plantilla del Banco
                     de Plantillas con la marca y el dominio del cliente. El
                     enlace es interno y el precio de entrada sale de
                     config/servicio_web.php, igual que en /paginas-web y en el
                     catálogo: aquí no se escribe ningún importe. --}}
                <x-v2.product-card
                    title="Páginas Web"
                    description="Tu web con una plantilla del catálogo, con tu marca y tu dominio. Desde {{ $precioDesdeServicio }} al mes."
                    :href="$urlServicioWeb"
                    image="{{ asset('themes/webpage/images/productos/paginas-web.webp') }}"
                    badge="Servicios"
                    variante="sobrepuesta"
                    etiqueta="Ver los planes"
                    :delay="7"
                />
