@extends('layouts.webpage')

@section('content')

    <!-- App Header Wrapper-->
    <x-nav />

    <!-- Sidebar -->
    <x-slidebar />

    <!-- Main Content Wrapper -->
    <main class="main-content w-full px-[var(--margin-x)] pb-8">


        <div class="row mt-4">
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <div class="card p-5">
                    <h1 style="font-size: 18px;"><b>¡Felicidades {{ $sale->clie_full_name }}!</b> 🎉</h1>
                    <p class="mt-2">
                        Bienvenido a <b>Global CPA</b>. Has invertido en tu futuro profesional, y estamos felices de acompañarte.
                    </p>
                    <p class="mt-2">
                        📩 Tus accesos al campus virtual han sido enviados a <b>{{ $sale->email }}</b>.
                        Revisa tu bandeja de entrada y, si no los ves, busca en correos no deseados.
                    </p>
                    <p class="mt-2">
                        Si necesitas ayuda, nuestro equipo está aquí para apoyarte.
                    </p>
                    <p class="mt-2">
                        🚀 ¡Nos vemos en el campus!
                    </h2>
                    <br>

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
                                    Precio
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($courses as $course)

                                <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
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
                                                {{ $course->name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-navy-100 sm:px-5">
                                        S/ {{ $course->price }}
                                    </td>
                                </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                    <button class="btn mt-1 h-11 justify-between bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                        <span>TOTAL:</span>
                        <span><i class="fa fa-heart" aria-hidden="true"></i>&nbsp; <div id="totalid">S/ {{ $sale->total }}</div></span>
                    </button>
                    <br>

                    <div class="mt-4 mb-4 ml-4 mr-4">
                        <a href="{{ route('login') }}">
                            <button class="boton-degradado-campus">Campus Virtual</button>
                        </a>
                    </div>

                </div>
            </div>
            <div class="col-md-4"></div>
        </div>
    </main>

    <br>
    <br>
    <br>



    <div id="whatsapp">
        <a href="https://wa.link/4bu45u" class="wtsapp" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <i class="fa fa-whatsapp" aria-hidden="true"></i>
        </a>
    </div>

    <style type="text/css">
        #whatsapp .wtsapp{
            position: fixed;
            transform: all .5s ease;
            background-color: #25D366;
            display: block;
            text-align: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            border-radius: 50px;
            border-right: none;
            color: #fff;
            font-weight: 700;
            font-size: 30px;
            bottom: 40px;
            right: 20px;
            border: 0;
            z-index: 9999;
            width: 50px;
            height: 50px;
            line-height: 50px;
        }

        #whatsapp .wtsapp:before{
            content: "";
            position: absolute;
            z-index: -1;
            left: 50%;
            top: 50%;
            transform: translateX(-50%) translateY(-50%);
            display: block;
            width: 60px;
            height: 60px;
            background-color: #25D366;
            border-radius: 50%;
            -webkit-animation: pulse-border 1500ms ease-out infinite;
            animation: pulse-border 1500ms ease-out infinite;
        }

        #whatsapp .wtsapp:focus{
            border: none;
            outline: none;
        }

        @keyframes pulse-border{
            0%{
                transform: translateX(-50%) translateY(-50%) translateZ(0) scale(1);
                opacity: 1;
            }
            100%{
                transform: translateX(-50%) translateY(-50%) translateZ(0) scale(1.5);
                opacity: 0;
            }
        }



        .slider {
            position: relative;
            overflow: hidden;
        }
        .slides {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }
        .slide {
            min-width: 100%;
        }

    </style>

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
                    h.querySelector('.accordion-icon-aracode').textContent = '►'; // Restablecer icono
                    h.setAttribute('aria-expanded', 'false');
                });

                // Mostrar el contenido del header clicado
                if (!isVisible) {
                    content.style.maxHeight = content.scrollHeight + "px";
                    content.style.padding = '15px';
                    this.classList.add('active'); // Añadir clase activa al encabezado clicado
                    this.querySelector('.accordion-icon-aracode').textContent = '▼'; // Cambiar icono al expandido
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
        document.getElementById('cart').innerHTML =
            ""; // BORRAR contenido de la vista, antes de cargar de la base de datos
        let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
        myIds = [];
        carrito.forEach(function(item) {
            // Hacer algo con cada elemento del carrito

            myIds.push(parseInt(item.id));
        });

        btnCrear = document.getElementById("btn-crear-cuenta");
                    btnCrear.setAttribute("disabled", "disabled");
        realizarConsulta(myIds);
    }

    function realizarConsulta(ids) {
        // Realizar la petición Ajax
        var csrfToken = "{{ csrf_token() }}";


        $.ajax({
            url: "{{ route('onlineshop_get_item_carrito') }}",
            type: 'POST',
            data: {
                ids: ids
            },
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(respuesta) {
                // Obtén una referencia al elemento div por su ID
                var divCartHidden = document.getElementById("divCartHidden");

                respuesta.items.forEach(function(item) {
                    // Accede a las propiedades del objeto
                    renderProducto(item);
                    // Crea un elemento input oculto
                    let inputHidden = document.createElement("input");
                    // Establece los atributos del input
                    inputHidden.type = "hidden";
                    inputHidden.name = "item_id[]"; // Asigna el nombre que desees
                    inputHidden.value = item.id; // Asigna el valor que desees
                    // Agrega el input al div
                    divCartHidden.appendChild(inputHidden);
                });

                btnCrear = document.getElementById("btn-crear-cuenta");
                    btnCrear.removeAttribute("disabled");

            },
            error: function(xhr) {
                // Ocurrió un error al realizar la consulta
                console.log(xhr.responseText);
                // Aquí puedes manejar el error de alguna manera
            }
        });

    }

    function renderProducto(respuesta) {

        var cart = document.getElementById('cart');
        if (cart != null) {
            var id = respuesta.id;
            var teacher = respuesta.teacher;
            var teacher_id = respuesta.teacher_id;
            var avatar = respuesta.avatar;
            var image = respuesta.image;
            var name = respuesta.name;
            var price = respuesta.price;
            var modalidad = respuesta.additional;
            var url_campus = "";
            var url_descripcion_programa = "/descripcion-programa/"+id; // esta ruta deberá corregirse si se cambia el el get de la RUTA :S


            cart.innerHTML += `
        <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500" id="` + id + `_pc">
                                        <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                            <div class="flex items-center space-x-4">
                                                <div class="avatar">
                                                <img
                                                    class="rounded-full"
                                                    src="` + image + `"
                                                    alt="avatar"
                                                />
                                                </div>

                                                <span class="font-medium text-slate-700 dark:text-navy-100">
                                                    <a href="`+url_descripcion_programa+`" target="_blank">` + name + `</a>
                                                </span>
                                            </div>
                                        </td>
                                        <td
                                        class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-navy-100 sm:px-5">
                                        <b>` + modalidad + `</b>
                                        </td>
                                        <td
                                        class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-navy-100 sm:px-5">
                                        <b>S/ ` + price + `</b>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-700 dark:text-navy-100 sm:px-5">
                                            <button class="boton-degradado-trash" onclick="eliminarproducto({ id: ` + id + `, nombre: '` +
                              name + `', precio: ` + price + ` });">
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

<script>
function confirmSubmit(event) {
event.preventDefault(); // Evita que el formulario se envíe automáticamente
carrito = JSON.parse(localStorage.getItem("carrito")) || [];
console.log(carrito);
if(carrito.length>0){
console.log(event);
event.target.form.submit();
}else
alert("No has elegido ningún curso");

}
</script>


<script>
function onSubmit(token) {
  document.getElementById("CartForm").submit();
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
