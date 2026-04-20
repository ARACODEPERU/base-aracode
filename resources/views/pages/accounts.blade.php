@extends('layouts.webpage')

@section('content')
    <script src="https://sdk.mercadopago.com/js/v2"></script>




    <!-- App Header Wrapper-->
    <x-nav />

    <!-- Sidebar -->
    <x-slidebar />

    <!-- Main Content Wrapper -->
    <main class="main-content w-full px-[var(--margin-x)] pb-8">
        <br>
        <div class="grid grid-cols-12 gap-4 sm:gap-5 lg:gap-6">
            <div class="col-span-12 lg:col-span-8">
                <div class="mt-4 sm:mt-5 lg:mt-6" x-data="{activeTab:'tabRecent'}">
                    <div class="flex items-center justify-between space-x-2">
                        <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">

                        </h2>

                        <div class="is-scrollbar-hidden overflow-x-auto rounded-lg bg-slate-200 text-slate-600
                            dark:bg-navy-800 dark:text-navy-200">
                            <div class="tabs-list flex p-1">
                                <button
                                    @click="activeTab = 'tabRecent'"
                                    :class="activeTab === 'tabRecent' ? 'bg-white shadow dark:bg-navy-500 dark:text-navy-100' : 'hover:text-slate-800 focus:text-slate-800 dark:hover:text-navy-100 dark:focus:text-navy-100'"
                                    class="btn shrink-0 px-3 py-1 text-xs+ font-medium">
                                    <span id = "total_productos">0 programas en el carrito.</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card mt-4">
                        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                            <table class="is-hoverable w-full text-left">
                                <thead>
                                    <tr>
                                        <th
                                        class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                                        >
                                        Producto
                                        </th>
                                        <th
                                        class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                                        >
                                        Tipo
                                        </th>
                                        <th
                                        class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                                        >
                                        Precio
                                        </th>
                                        <th
                                        class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5"
                                        >
                                        Acción
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="cart">
                                    <tr
                                        class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500"
                                    >
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                            <div class="flex items-center space-x-4">
                                                <div class="avatar">
                                                <img
                                                    class="rounded-full"
                                                    src="{{ asset('themes/webpage/images/object/object-15.jpg') }}"
                                                    alt="avatar"
                                                />
                                                </div>

                                                <span class="font-medium text-slate-700 dark:text-navy-100">
                                                    Título del curso
                                                </span>
                                            </div>
                                        </td>
                                        <td
                                        class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-navy-100 sm:px-5"
                                        >
                                        Cursos Taller
                                        </td>
                                        <td
                                        class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-navy-100 sm:px-5"
                                        >
                                        S/ 0.00
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <button class="btn mt-1 h-11 justify-between bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                            <span>TOTAL:</span>
                            <span><i class="fa fa-heart" aria-hidden="true"></i>&nbsp; <div id="totalid">S/ 0.00</div></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-span-12 grid grid-cols-1 gap-4 sm:gap-5 lg:col-span-4 lg:gap-6">
                <div class="card pb-4">
                    <br>
                    <div class="px-4 sm:px-5">
                        <h1 class="tracking-wide text-slate-700 dark:text-navy-100 mt-4"
                            style="font-size: 20px;"
                        >
                            Excelente estás a punto de aprender con nosotros, Bienvenido a GlobalCpa Perú.
                        </h1>
                        <br>
                        <p style="font-size: 18px;">
                            Puede realizar el pago a traves de nuestros
                            medios digitales:
                        </p>
                        <br>
                        <h2 style="font-size: 18px;">
                            <b>Banco de Credito del Perú</b>
                        </h2>
                        <ul style="font-size: 16px;">
                            <li>cta. 111-1111111-1</li>
                            <li>cci. 111-1111111-1</li>
                        </ul>
                        <br>
                        <h2 style="font-size: 18px;">
                            <b>Yape ó Plin</b>
                        </h2>
                        <ul style="font-size: 16px;">
                            <li>N°. 977627207</li>
                        </ul>
                        <br>
                        <a href="https://wa.link/4bu45u">
                            <button class="mt-8 boton-degradado-transferencia">
                                <b>TRANSFERENCIA | YAPE ó PLIN</b>
                            </button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <br>
    <br>
    <br>

    <script>
    </script>


    <script>
        const headers = document.querySelectorAll('.accordion-header-aracode');
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const isVisible = content.style.maxHeight;

                // Ocultar todos los contenidos y resetear iconos
                document.querySelectorAll('.accordion-content-aracode').forEach(item => {
                    item.style.maxHeight = null;
                    item.style.padding = '0';
                    item.setAttribute('aria-hidden', 'true');
                });
                headers.forEach(h => {
                    h.classList.remove('active');
                    h.querySelector('.accordion-icon-aracode').textContent =
                        '►'; // Restablecer icono
                    h.setAttribute('aria-expanded', 'false');
                });

                // Mostrar el contenido del header clicado
                if (!isVisible) {
                    content.style.maxHeight = content.scrollHeight + "px";
                    content.style.padding = '15px';
                    this.classList.add('active'); // Añadir clase activa al encabezado clicado
                    this.querySelector('.accordion-icon-aracode').textContent =
                        '▼'; // Cambiar icono al expandido
                    this.setAttribute('aria-expanded', 'true');
                    content.setAttribute('aria-hidden', 'false');
                }
            });
        });
    </script>
    {{-- codigo de recapcha --}}
    <script type="text/javascript">
        function callbackThen(response) {

            // read HTTP status

            console.log(response.status);

            // read Promise object

            response.json().then(function(data) {

                console.log(data);

            });

        }

        function callbackCatch(error) {

            console.error('Error:', error)

        }
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        cargarItemsCarritoBD();

        function cargarItemsCarritoBD() {
            document.getElementById('cart').innerHTML = "";
            let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
            let myIds = [];
            carrito.forEach(function(item) {
                myIds.push(parseInt(item.id));
            });

            let btnCrear = document.getElementById("btn-crear-cuenta");
            if (btnCrear) btnCrear.setAttribute("disabled", "disabled");

            if (myIds.length > 0) {
                realizarConsulta(myIds);
            }
        }

        function realizarConsulta(ids) {
            var token_csrf = $('meta[name="csrf-token"]').attr('content');
            if (!token_csrf) token_csrf = "{{ csrf_token() }}";

            $.ajax({
                url: "{{ route('onlineshop_get_item_carrito') }}",
                type: 'POST',
                data: { ids: ids },
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': token_csrf },
                success: function(respuesta) {
                    var divCartHidden = document.getElementById("divCartHidden");

                    respuesta.items.forEach(function(item) {
                        renderProducto(item);
                        if (divCartHidden) {
                            let inputHidden = document.createElement("input");
                            inputHidden.type = "hidden";
                            inputHidden.name = "item_id[]";
                            inputHidden.value = item.id;
                            divCartHidden.appendChild(inputHidden);
                        }
                    });

                    let btnCrear = document.getElementById("btn-crear-cuenta");
                    if (btnCrear) btnCrear.removeAttribute("disabled");
                },
                error: function(xhr) {
                    console.log('Error cargando carrito:', xhr.responseText);
                }
            });
        }

        function renderProducto(respuesta) {
            var cart = document.getElementById('cart');
            if (cart != null) {
                var id = respuesta.id;
                var image = respuesta.image;
                var name = respuesta.name;
                var price = respuesta.price;
                var modalidad = respuesta.additional;
                var url_descripcion_programa = "/curso-descripcion/" + id;

                cart.innerHTML += `
                    <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500" id="${id}_pc">
                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                            <div class="flex items-center space-x-4">
                                <div class="avatar">
                                    <img class="rounded-full" src="${image}" alt="avatar" />
                                </div>
                                <span class="font-medium text-slate-700 dark:text-navy-100">
                                    <a href="${url_descripcion_programa}" target="_blank">${name}</a>
                                </span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-navy-100 sm:px-5">
                            <b>${modalidad}</b>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-navy-100 sm:px-5">
                            <b>S/ ${price}</b>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-700 dark:text-navy-100 sm:px-5">
                            <button class="boton-degradado-trash" onclick="eliminarproducto({ id: ${id}, nombre: '${name}', precio: ${price} });">
                                <i class="fa fa-trash" aria-hidden="true" style="font-size: 16px;">
                                    <a title="Eliminar este Curso" class="remove"></a>
                                </i>
                            </button>
                        </td>
                    </tr>
                `;
            }
        }
    </script>
    <script src="https://www.google.com/recaptcha/api.js?render={{ env('RECAPTCHA_SITE_KEY') }}"></script>
    <script>
        function callbackThen(response) {
            // Tu lógica existente para éxito
            console.log("Token generado:", response);
        }

        function callbackCatch(error) {
            // Tu lógica existente para error
            console.error("Error en reCAPTCHA:", error);
        }

        // Esta es la función que llama tu formulario
        function onSubmit(e) {
            if (e) e.preventDefault();
            grecaptcha.ready(function() {
                grecaptcha.execute("{{ env('RECAPTCHA_SITE_KEY') }}", {action: 'submit'}).then(function(token) {
                    // Añadimos el token al formulario y lo enviamos
                    let form = document.getElementById("CartForm");
                    let input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'g-recaptcha-response';
                    input.value = token;
                    form.appendChild(input);
                    form.submit();
                });
            });
        }
    </script>

@stop
