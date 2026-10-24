{{--
    Mi perfil: datos de la matrícula y cambio de contraseña.
    La contraseña inicial es el DNI; desde aquí el alumno puede cambiarla.
--}}
<dialog id="perfilDialog" class="aula-dialog" aria-labelledby="perfilTitle"
        @if ($errors->perfil->any() || session('perfil_status')) data-auto-open="true" @endif>
    <div class="aula-dialog__card">
        <div class="flex items-start justify-between gap-4">
            <h2 id="perfilTitle" class="font-display text-xl text-cole-forest">Mi perfil</h2>
            <button type="button" class="aula-icon-btn" data-profile-close aria-label="Cerrar mi perfil">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>

        <div class="mt-3 flex items-center gap-3 rounded-2xl border border-cole-sand bg-white p-3">
            <span class="aula-avatar">
                @if ($portal['student']['avatar_url'])
                    <img src="{{ $portal['student']['avatar_url'] }}" alt="{{ $portal['student']['full_name'] }}" class="h-full w-full object-cover">
                @else
                    @include('colegio.components.aula.fox', ['class' => 'h-8 w-8'])
                @endif
            </span>
            <div class="min-w-0">
                <p class="truncate text-sm font-extrabold text-cole-forest">{{ $portal['student']['full_name'] }}</p>
                <p class="text-[11px] font-bold text-cole-ink/55">
                    Código {{ $portal['student']['code'] }} · {{ $portal['grade']['label'] }}
                </p>
            </div>
        </div>

        @if (session('perfil_status'))
            <p class="mt-3 rounded-2xl border border-cole-emerald/30 bg-cole-mint px-3.5 py-2.5 text-xs font-bold leading-snug text-cole-emerald" role="status">
                {{ session('perfil_status') }}
            </p>
        @endif

        @if ($errors->perfil->any())
            <p class="mt-3 rounded-2xl border border-[#F3B9B9] bg-[#FFF0F0] px-3.5 py-2.5 text-xs font-bold leading-snug text-[#A83B45]" role="alert">
                {{ $errors->perfil->first() }}
            </p>
        @endif

        <form method="POST" action="{{ route('colegio.aula.profile.password') }}" class="mt-4">
            @csrf

            <p class="text-sm font-extrabold text-cole-forest">Cambiar mi contraseña</p>
            <p class="mt-1 text-[11.5px] font-semibold leading-relaxed text-cole-ink/60">
                La primera vez entraste con tu DNI. Si quieres, elige una contraseña más fácil de recordar.
            </p>

            <div class="mt-3 space-y-3">
                <div>
                    <label for="clave_actual" class="cole-label">Contraseña actual</label>
                    <div class="relative">
                        <input id="clave_actual" name="clave_actual" type="password" autocomplete="current-password"
                               class="cole-input pr-4" placeholder="Tu contraseña de ahora" required>
                    </div>
                </div>

                <div>
                    <label for="clave_nueva" class="cole-label">Nueva contraseña</label>
                    <div class="relative">
                        <input id="clave_nueva" name="clave_nueva" type="password" autocomplete="new-password"
                               class="cole-input pr-4" placeholder="Al menos 6 caracteres" minlength="6" required>
                    </div>
                </div>

                <div>
                    <label for="clave_nueva_confirmation" class="cole-label">Repite la nueva contraseña</label>
                    <div class="relative">
                        <input id="clave_nueva_confirmation" name="clave_nueva_confirmation" type="password"
                               autocomplete="new-password" class="cole-input pr-4" placeholder="Escríbela otra vez" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="cole-btn cole-btn-primary mt-4 h-12 w-full text-sm">
                Guardar contraseña
            </button>
        </form>
    </div>
</dialog>
