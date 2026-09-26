@extends('layouts.webpage')

@section('content')
    <div class="page-wrapper" id="pageWrapper">
        <!-- Page Header Start-->
        <x-header />

        <!-- Page Body Start-->
        <div class="page-body-wrapper">
            <div class="page-body dark:bg-[#111c2d] transition-colors duration-300">
                <section class="py-5">
                    <div class="container">
                        <div class="row g-4">
                            {{-- Columna principal: informacion del curso --}}
                            <div class="col-lg-8">
                                <div class="card shadow-sm border-0 mb-4">
                                    <div class="card-body p-4">
                                        <span class="badge bg-primary mb-2">
                                            {{ $course?->category?->description ?? 'Curso' }}
                                        </span>
                                        <h1 class="h3 fw-bold mb-2">{{ $course?->description }}</h1>
                                        <p class="text-muted mb-3">
                                            {{ $course?->modality?->description }}
                                            @if($course?->type_description)
                                                · {{ $course->type_description }}
                                            @endif
                                        </p>

                                        @if($course?->image)
                                            <img src="{{ asset('storage/' . $course->image) }}"
                                                alt="{{ $course->description }}"
                                                class="img-fluid rounded mb-4 w-100"
                                                style="max-height: 380px; object-fit: cover;">
                                        @endif

                                        <h2 class="h5 fw-bold mt-4">Sobre el curso</h2>
                                        <p class="text-secondary">{{ $course?->description }}</p>

                                        @if($course?->sector_description)
                                            <p class="text-secondary"><b>Sector:</b> {{ $course->sector_description }}</p>
                                        @endif

                                        @if($course?->certificate_description || $course?->certificate_title)
                                            <h2 class="h5 fw-bold mt-4">Certificación</h2>
                                            <p class="text-secondary">
                                                {{ $course->certificate_title }}
                                                {{ $course->certificate_description ? ' — ' . $course->certificate_description : '' }}
                                            </p>
                                        @endif

                                        @if($course && $course->modules->count() > 0)
                                            <h2 class="h5 fw-bold mt-4">Temario</h2>
                                            <ul class="list-group list-group-flush">
                                                @foreach($course->modules as $module)
                                                    <li class="list-group-item">{{ $module->description ?? 'Módulo ' . ($loop->iteration) }}</li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        @if($course && $course->teachers->count() > 0)
                                            <h2 class="h5 fw-bold mt-4">Docentes</h2>
                                            <ul class="list-unstyled mb-0">
                                                @foreach($course->teachers as $teacherCourse)
                                                    <li class="mb-1">
                                                        <i class="fa fa-user-tie me-2 text-primary"></i>
                                                        {{ $teacherCourse?->teacher?->person?->formatted_name ?? 'Docente' }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Columna lateral: precio, brochure e inscripcion --}}
                            <div class="col-lg-4">
                                <div class="card shadow-sm border-0 mb-4 position-sticky" style="top: 90px;">
                                    <div class="card-body p-4">
                                        <h3 class="h4 fw-bold mb-1">
                                            S/ {{ number_format((float) ($course?->price ?? 0), 2, '.', '') }}
                                        </h3>
                                        @if($course?->discount && $course->discount_applies)
                                            <p class="text-success mb-3">Incluye descuento ({{ $course->discount }}%)</p>
                                        @else
                                            <p class="text-muted mb-3">Pago único</p>
                                        @endif

                                        @if($course?->brochure && $course->brochure->path_file)
                                            <a href="{{ asset('storage/' . $course->brochure->path_file) }}"
                                                target="_blank"
                                                class="btn btn-outline-primary w-100 mb-3">
                                                <i class="fa fa-download me-1"></i> Descargar brochure
                                            </a>
                                        @endif

                                        <button type="button" class="btn btn-primary w-100" onclick="procederInscripcionDesc()">
                                            Inscribirme ahora
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Otros cursos --}}
                        @if(isset($latest_courses) && count($latest_courses) > 0)
                            <h2 class="h5 fw-bold mt-5 mb-3">Otros cursos que te pueden interesar</h2>
                            <div class="row g-4">
                                @foreach($latest_courses as $latest)
                                    <div class="col-md-4">
                                        <div class="card h-100 shadow-sm border-0">
                                            @if($latest->course?->image)
                                                <img src="{{ asset('storage/' . $latest->course->image) }}"
                                                    class="card-img-top" alt="{{ $latest->course->description }}"
                                                    style="height: 160px; object-fit: cover;">
                                            @endif
                                            <div class="card-body">
                                                <h3 class="h6 fw-bold">{{ $latest->course?->description }}</h3>
                                                <a href="{{ route('web_curso_descripcion', $latest->course?->slug ?? $latest->id) }}"
                                                    class="btn btn-sm btn-outline-primary mt-2">Ver curso</a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
        <!-- footer start-->
        <x-footer />
    </div>
@endsection

@section('javascripts')
    <script>
        function procederInscripcionDesc() {
            if (window.Swal === undefined) {
                console.error("SweetAlert2 (Swal) no está cargado.");
                return;
            }

            Swal.fire({
                title: '¿Inscribirte en este curso?',
                text: 'Se agregará al carrito para completar tu compra.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar',
                padding: '2em',
                customClass: 'sweet-alerts',
            }).then((result) => {
                if (result.isConfirmed) {
                    // 1. Limpiar el carrito (mismo flujo que la landing)
                    localStorage.removeItem('carrito');

                    // 2. Crear el producto con el item del curso
                    var producto = {
                        id: @json($onli_item_id ?? 0),
                        nombre: @json($course?->description ?? 'Curso'),
                        precio: @json($course?->price ?? 0),
                        image: "{{ $course?->image ?? '' }}"
                    };

                    // 3. Agregar al localStorage
                    var carrito = [];
                    carrito.push(producto);
                    localStorage.setItem('carrito', JSON.stringify(carrito));

                    // 4. Redireccionar al carrito
                    window.location.href = "{{ route('web_carrito') }}";
                }
            });
        }
    </script>
@endsection
