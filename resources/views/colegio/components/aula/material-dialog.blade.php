{{--
    Modal de materiales de apoyo.

    Muestra en un solo lugar todos los materiales que el administrador subió
    al curso (imágenes, PDF y otros archivos). La vista previa y la descarga
    se resuelven en las rutas del aula, que validan que el material sea del
    curso del alumno.
--}}
@php
    $materialCount = count($materials ?? []);
@endphp

@if ($materialCount > 0)
    <dialog id="materialDialog" class="aula-dialog aula-dialog--material" aria-labelledby="materialDialogTitle">
        <div class="aula-dialog__card material-dialog">
            <header class="material-dialog__head">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="material-dialog__icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A1.5 1.5 0 0 1 5.5 4H10l2 2h6.5A1.5 1.5 0 0 1 20 7.5v11A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5z"/>
                        </svg>
                    </span>

                    <div class="min-w-0">
                        <h2 id="materialDialogTitle" class="font-display text-xl text-cole-forest">Materiales de apoyo</h2>
                        <p class="mt-0.5 text-[11.5px] font-bold leading-snug text-cole-ink/55">
                            {{ $materialCount }} {{ $materialCount === 1 ? 'material disponible' : 'materiales disponibles' }}.
                            Toca uno para verlo aquí mismo.
                        </p>
                    </div>
                </div>

                <button type="button" class="aula-icon-btn" data-material-close aria-label="Cerrar los materiales">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </header>

            <div class="material-dialog__body">
                {{-- Lista de todos los materiales del curso --}}
                <nav class="material-dialog__list" aria-label="Materiales del curso">
                    @foreach ($materials as $material)
                        <button type="button"
                                class="material-item"
                                data-material-item
                                data-material-id="{{ $material['id'] }}"
                                data-kind="{{ $material['kind'] }}"
                                data-title="{{ $material['title'] }}"
                                data-origin="{{ $material['origin'] }}"
                                @if ($material['size']) data-size="{{ $material['size'] }}" @endif
                                @if ($material['preview_url']) data-preview="{{ $material['preview_url'] }}" @endif
                                @if ($material['download_url']) data-download="{{ $material['download_url'] }}" @endif
                                @if ($material['external_url']) data-external="{{ $material['external_url'] }}" @endif
                                aria-pressed="false">
                            <span class="material-item__icon material-item__icon--{{ $material['kind'] }}" aria-hidden="true">
                                @switch($material['kind'])
                                    @case('image')
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3.5" y="4.5" width="17" height="15" rx="3"/>
                                            <circle cx="9" cy="10" r="1.6"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 17 4.5-4.5 3.5 3.5 2.5-2.5L20 17"/>
                                        </svg>
                                        @break

                                    @case('pdf')
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.5h7l5 5v12H6z"/>
                                            <path stroke-linecap="round" d="M13 3.5v5h5"/>
                                            <path stroke-linecap="round" d="M9 14h6M9 17h4"/>
                                        </svg>
                                        @break

                                    @case('link')
                                    @case('embed')
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 13.5a4 4 0 0 1 0-5.7l2-2a4 4 0 0 1 5.7 5.7l-1.3 1.3M13.5 10.5a4 4 0 0 1 0 5.7l-2 2a4 4 0 0 1-5.7-5.7l1.3-1.3"/>
                                        </svg>
                                        @break

                                    @default
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.5h7l5 5v12H6z"/>
                                            <path stroke-linecap="round" d="M13 3.5v5h5"/>
                                        </svg>
                                @endswitch
                            </span>

                            <span class="min-w-0">
                                <span class="material-item__title">{{ $material['title'] }}</span>
                                <span class="material-item__meta">
                                    {{ $material['origin'] }}@if ($material['size']) · {{ $material['size'] }} @endif
                                </span>
                            </span>
                        </button>
                    @endforeach
                </nav>

                {{-- Visor del material elegido --}}
                <section class="material-viewer" aria-label="Vista del material">
                    <div class="material-viewer__head">
                        <div class="min-w-0">
                            <p class="material-viewer__eyebrow" data-material-origin>Material del curso</p>
                            <h3 class="material-viewer__title" data-material-title>Elige un material</h3>
                        </div>

                        <div class="material-viewer__actions">
                            <a class="cole-btn cole-btn-primary material-viewer__download"
                               data-material-download href="#" download hidden>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0 4-4m-4 4-4-4"/>
                                    <path stroke-linecap="round" d="M5 18.5h14"/>
                                </svg>
                                Descargar
                            </a>

                            <a class="cole-btn cole-btn-ghost material-viewer__external"
                               data-material-external href="#" target="_blank" rel="noopener noreferrer" hidden>
                                Abrir enlace
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 4h6v6M20 4l-8.5 8.5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 14v4.5A1.5 1.5 0 0 1 16.5 20h-11A1.5 1.5 0 0 1 4 18.5v-11A1.5 1.5 0 0 1 5.5 6H10"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="material-viewer__stage">
                        {{-- Imágenes --}}
                        <img class="material-viewer__frame" data-material-image src="" alt="" hidden>

                        {{-- PDF y contenido integrado --}}
                        <iframe class="material-viewer__frame" data-material-frame title="Vista previa del material" hidden></iframe>

                        {{-- Otros archivos o enlaces: se ofrecen para descargar o abrir --}}
                        <div class="material-viewer__fallback" data-material-fallback hidden>
                            <span class="material-viewer__fallback-icon" aria-hidden="true">
                                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.5h7l5 5v12H6z"/>
                                    <path stroke-linecap="round" d="M13 3.5v5h5"/>
                                </svg>
                            </span>
                            <p class="material-viewer__fallback-title" data-material-fallback-title>
                                Este material se descarga para abrirlo
                            </p>
                            <p class="material-viewer__fallback-text">
                                Usa el botón «Descargar» y ábrelo en tu computadora o tablet.
                            </p>
                        </div>
                    </div>

                    <div class="material-viewer__nav">
                        <button type="button" data-material-prev>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14 6-6 6 6 6"/>
                            </svg>
                            Anterior
                        </button>

                        <span class="material-viewer__position" data-material-position>1 de {{ $materialCount }}</span>

                        <button type="button" data-material-next>
                            Siguiente
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m10 6 6 6-6 6"/>
                            </svg>
                        </button>
                    </div>
                </section>
            </div>

            {{-- Contenido integrado: se muestra en el iframe, aislado del modal --}}
            @foreach ($materials as $material)
                @if ($material['kind'] === 'embed' && $material['embed_html'])
                    <template data-material-embed="{{ $material['id'] }}">{!! $material['embed_html'] !!}</template>
                @endif
            @endforeach
        </div>
    </dialog>
@endif
