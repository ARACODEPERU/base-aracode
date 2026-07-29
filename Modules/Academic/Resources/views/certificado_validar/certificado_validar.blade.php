@extends('layouts.webpage')
{{-- @section('title', '- Validar Certificado') <-- Agrega esta línea --}}
@section('content')


    <div class="tap-top"><i data-feather="chevrons-up"></i></div>

    <div class="page-wrapper" id="pageWrapper">
        <x-header />

        <div class="page-body-wrapper">
            <x-sidebar />

            <div class="page-body" x-data>
                <div class="container-fluid pb-5">

        <div class="mt-5 w-full transition-all duration-[.25s] sm:mt-5 lg:mt-6">
            <div style="text-align:center;">
                <h1 class="title_aracode" style="font-size: 45px; line-height: 1.1; font-weight: 700;">
                    Validar Certificado
                </h1>
            </div>
        </div>

        @if ($person=="")

        <form id="search-form" class="max-w-md mx-auto" method="get">
            <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                </div>
                <input type="search" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Ingresa Numero DNI" required
                name="dni"/>
                <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"></button>
            </div>
        </form>

        <script>
            document.getElementById('search-form').addEventListener('submit', function(event) {
                event.preventDefault(); // Detiene el envío normal del formulario

                const dniInput = document.getElementById('default-search');
                const dniValue = dniInput.value;

                // Construye la URL usando el valor del input
                const newAction = `{{ route('certificado_validar', ['dni' => '__DNI__']) }}`.replace('__DNI__', dniValue);

                // Redirige al usuario a la nueva URL
                window.location.href = newAction;
            });
        </script>

        @else
                    <div>
                        <div class="text-2xl">{{ $person->full_name }}</div>
                    <div class="profile-image-container">
                        <img src="{{ asset('storage/'.$person->image)}}" alt="{{ $person->full_name }}" class="profile-image">
                    </div>
                </div>

                <style>
                .profile-image-container {
                    width: 60px;
                    height: 60px;
                    border: 2px solid #ccc; /* Borde del círculo vacío */
                    border-radius: 50%; /* Forma circular */
                    overflow: hidden; /* Oculta cualquier parte de la imagen que se salga */
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .profile-image {
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: cover; /* Para que la imagen cubra el contenedor sin deformarse */
                }
                </style>
                <br><hr>
                @if(is_array($certificates) || $certificates instanceof \Illuminate\Support\Collection)
                <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr class="text-2xl">
                                <th scope="col" class="py-3 px-6">
                                    Curso
                                </th>
                                <th scope="col" class="py-3 px-6">
                                    Fecha entregado
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($certificates as $certificate)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    <a href="{{ route('certificado_validar', ['dni'=>$person->number, 'course_id'=>$certificate->course_id]) }}">{{ $certificate->description }}</a>
                                </td>
                                <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ explode(' ', $certificate->fecha)[0] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
        @endif

        {{-- Validación de certificado de módulo --}}
        @if ($module)
            <hr>
            <div class="mt-4">
                @if (!$person)
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-center">
                        <p class="text-red-600 dark:text-red-400 font-medium">No se encontró la persona con el número de documento proporcionado.</p>
                    </div>
                @elseif (!$isEnrolled)
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-center">
                        <p class="text-red-600 dark:text-red-400 font-medium">La persona no está inscrita en este curso.</p>
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-500 px-6 py-4">
                            <h3 class="text-white font-bold text-lg">Certificado de Módulo - Verificación</h3>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-4 mb-6">
                                @if($person->image)
                                    <img src="{{ asset('storage/'.$person->image) }}" alt="{{ $person->full_name }}" class="w-16 h-16 rounded-full object-cover border-2 border-gray-200">
                                @else
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($person->full_name) }}&size=64&rounded=true" alt="{{ $person->full_name }}" class="w-16 h-16 rounded-full">
                                @endif
                                <div>
                                    <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ $person->full_name }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">DNI: {{ $person->number }}</p>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mb-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Curso</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $module->course->description ?? 'N/A' }}</p>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mb-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Módulo</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $module->description ?? 'N/A' }}</p>
                            </div>

                            @if($moduleThemes->count() > 0)
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Contenido del Módulo</p>
                                    <div class="space-y-3">
                                        @foreach($moduleThemes as $index => $theme)
                                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                                <h5 class="font-medium text-gray-900 dark:text-white mb-2">
                                                    <span class="text-blue-500">{{ $index + 1 }}.</span> {{ $theme['description'] }}
                                                </h5>
                                                @if($theme['contents']->count() > 0)
                                                    <ul class="ml-6 space-y-1">
                                                        @foreach($theme['contents'] as $content)
                                                            <li class="text-sm text-gray-600 dark:text-gray-300 flex items-center gap-2">
                                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                                </svg>
                                                                {{ $content['description'] }}
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <hr>
        @if ($course != "" && !empty($certificates) && is_iterable($certificates) && $certificates->count() > 0)
        <div class="card group p-5"><p class="">
            {!! $certificates->last()->curriculum_plan ?? 'No existe Registro, Verifica el curso y el número del Alumno.' !!}
          </p>
        </div>
        @endif

                    <x-courses.list-card />
                    <x-social-networks />

                    <x-footer />
                </div>
            </div>
        </div>
    </div>

    <br>
    <br>


    <script>
        let currentIndex = 0;
        const slides = document.querySelector('.slides');
        const totalSlides = document.querySelectorAll('.slide').length;

        function showNextSlide() {
            currentIndex = (currentIndex + 1) % totalSlides;
            const offset = -currentIndex * 100;
            slides.style.transform = `translateX(${offset}%)`;
        }

        setInterval(showNextSlide, 3000); // Cambia cada 3 segundos
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

@stop
