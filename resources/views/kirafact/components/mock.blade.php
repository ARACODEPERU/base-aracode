{{--
    Composición del software (imagen referencial).

    Está hecha con HTML y CSS propios, no con imágenes: no es una captura del
    sistema y por eso siempre se acompaña del rótulo «Imagen referencial».

    No contiene RUC, nombres de clientes, series ni importes: los valores van
    representados con barras para que nada se pueda confundir con un registro
    real.

    Variables:
    - $titulo: rótulo de la barra superior (por defecto 'KIRAFACT').
    - $vista: 'comprobante' (por defecto) u 'operacion'.
--}}
@php
    $titulo = $titulo ?? 'KIRAFACT';
    $vista = $vista ?? 'comprobante';
@endphp

<div class="kf-mock">
    <div class="kf-mock-bar">
        <span class="kf-mock-dot"></span>
        <span class="kf-mock-dot"></span>
        <span class="kf-mock-dot"></span>
        <span class="kf-mock-title">{{ $titulo }}</span>
    </div>

    <div class="kf-mock-body">
        @if ($vista === 'operacion')
            {{-- Vista de operación: listado y resumen del periodo --}}
            <div class="grid gap-4 sm:grid-cols-[1.25fr_0.75fr]">
                <div class="kf-mock-panel">
                    <p class="kf-slate text-[0.6875rem] font-bold uppercase tracking-wider">Comprobantes del día</p>
                    <div class="mt-4 space-y-3.5">
                        @foreach ([92, 74, 84, 66] as $ancho)
                            <div class="flex items-center gap-3">
                                <span class="kf-mock-dot"></span>
                                <span class="kf-bar"><span style="width: {{ $ancho }}%"></span></span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="kf-mock-panel">
                    <p class="kf-slate text-[0.6875rem] font-bold uppercase tracking-wider">Resumen</p>
                    <div class="mt-4 space-y-3">
                        <div class="kf-bar"><span style="width: 68%"></span></div>
                        <div class="kf-bar"><span style="width: 44%"></span></div>
                        <div class="kf-bar"><span style="width: 82%"></span></div>
                    </div>
                    <span class="kf-badge kf-badge-neutral mt-4">Ventas del periodo</span>
                </div>
            </div>
        @else
            {{-- Vista de emisión: datos del comprobante y estado del envío --}}
            <div class="grid gap-4 sm:grid-cols-[1.15fr_0.85fr]">
                <div class="kf-mock-panel">
                    <p class="kf-slate text-[0.6875rem] font-bold uppercase tracking-wider">Comprobante electrónico</p>
                    <div class="mt-4 space-y-3.5">
                        <div class="flex items-center gap-3">
                            <span class="kf-slate w-16 shrink-0 text-[0.6875rem]">Cliente</span>
                            <span class="kf-bar"><span style="width: 78%"></span></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="kf-slate w-16 shrink-0 text-[0.6875rem]">Detalle</span>
                            <span class="kf-bar"><span style="width: 62%"></span></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="kf-slate w-16 shrink-0 text-[0.6875rem]">Total</span>
                            <span class="kf-bar"><span style="width: 40%"></span></span>
                        </div>
                    </div>
                </div>
                <div class="kf-mock-panel">
                    <p class="kf-slate text-[0.6875rem] font-bold uppercase tracking-wider">Estado del envío</p>
                    <div class="mt-4 flex items-center gap-2">
                        @include('kirafact.components.icon', ['icono' => 'sunat', 'iconoClase' => 'h-4 w-4'])
                        <span class="kf-badge kf-badge-neutral">Registrado</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div class="kf-bar"><span style="width: 88%"></span></div>
                        <div class="kf-bar"><span style="width: 52%"></span></div>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        @include('kirafact.components.icon', ['icono' => 'documento', 'iconoClase' => 'h-4 w-4'])
                        <span class="kf-slate text-[0.6875rem]">XML y PDF del comprobante</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
