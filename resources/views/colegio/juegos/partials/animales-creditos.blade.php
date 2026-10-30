{{--
    Créditos de las fotografías y los sonidos. Se generan desde
    config/colegio_animales.php para no duplicar datos ni dejar archivos sin
    atribución. Los enlaces abren la ficha original en Wikimedia Commons.
--}}
<dialog id="creditosAnimales" class="aula-dialog" aria-labelledby="creditosAnimalesTitle">
    <div class="aula-dialog__card animales-creditos">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="animales-creditos__eyebrow">Fotografías y sonidos</p>
                <h2 id="creditosAnimalesTitle" class="font-display text-xl text-cole-forest">Créditos y licencias</h2>
            </div>
            <button type="button" class="aula-icon-btn" data-credits-close aria-label="Cerrar los créditos">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>

        <p class="mt-2 text-xs font-semibold leading-relaxed text-cole-ink/65">
            Todas las imágenes y los sonidos son de animales reales y provienen de
            <a href="https://commons.wikimedia.org/" target="_blank" rel="noopener noreferrer" class="animales-creditos__link">Wikimedia Commons</a>.
            Los audios se convirtieron a MP3 para que funcionen en cualquier navegador.
        </p>

        <ul class="animales-creditos__list">
            @foreach ($animals as $animal)
                <li class="animales-creditos__item">
                    <img src="{{ $animal['photo_url'] }}" alt="" loading="lazy" decoding="async" class="animales-creditos__foto">
                    <div class="min-w-0">
                        <p class="animales-creditos__nombre">{{ \Illuminate\Support\Str::ucfirst($animal['name']) }}</p>

                        <p class="animales-creditos__linea">
                            <span class="animales-creditos__tipo">Foto</span>
                            {{ $animal['photo_credit']['author'] }} ·
                            @if ($animal['photo_credit']['license_url'])
                                <a href="{{ $animal['photo_credit']['license_url'] }}" target="_blank" rel="noopener noreferrer" class="animales-creditos__link">{{ $animal['photo_credit']['license'] }}</a>
                            @else
                                {{ $animal['photo_credit']['license'] }}
                            @endif
                            · <a href="{{ $animal['photo_credit']['page'] }}" target="_blank" rel="noopener noreferrer" class="animales-creditos__link">ficha</a>
                        </p>

                        <p class="animales-creditos__linea">
                            <span class="animales-creditos__tipo">Sonido</span>
                            {{ $animal['audio_credit']['author'] }} ·
                            @if ($animal['audio_credit']['license_url'])
                                <a href="{{ $animal['audio_credit']['license_url'] }}" target="_blank" rel="noopener noreferrer" class="animales-creditos__link">{{ $animal['audio_credit']['license'] }}</a>
                            @else
                                {{ $animal['audio_credit']['license'] }}
                            @endif
                            · <a href="{{ $animal['audio_credit']['page'] }}" target="_blank" rel="noopener noreferrer" class="animales-creditos__link">ficha</a>
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>

        <p class="animales-creditos__nota">
            Las licencias CC BY y CC BY-SA permiten usar estos recursos citando autor y licencia.
            Si un archivo cambia en su ficha original, prevalece la información publicada allí.
        </p>

        <button type="button" class="cole-btn cole-btn-primary mt-4 h-12 w-full text-sm" data-credits-close>
            Entendido
        </button>
    </div>
</dialog>
