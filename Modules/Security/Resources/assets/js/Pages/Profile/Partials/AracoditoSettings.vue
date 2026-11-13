<script setup>
    import { computed, watch } from 'vue';
    import { useForm, usePage } from '@inertiajs/vue3';

    const props = defineProps({
        // Usuario a configurar. Por defecto, el usuario autenticado.
        user: {
            type: Object,
            default: () => ({}),
        },
    });

    const authUser = usePage().props?.auth?.user ?? {};

    // Los nombres coinciden con las columnas de la tabla users, para que el
    // backend pueda guardarlos con un update() directo.
    const settings = useForm({
        aracodito_enabled: Boolean(props.user?.aracodito_enabled ?? authUser.aracodito_enabled ?? false),
        aracodito_audio_enabled: Boolean(props.user?.aracodito_audio_enabled ?? authUser.aracodito_audio_enabled ?? false),
        aracodito_ia_enabled: Boolean(props.user?.aracodito_ia_enabled ?? authUser.aracodito_ia_enabled ?? false),
    });

    const bubbleText = computed(() => {
        if (!settings.aracodito_enabled) {
            return 'Alertas clásicas activas: SweetAlert2 (swal).';
        }
        if (settings.aracodito_ia_enabled) {
            return '¡Hola! Soy Aracodito. Pregúntame cómo usar el sistema.';
        }
        if (settings.aracodito_audio_enabled) {
            return '¡Hola! Soy Aracodito. Te acompaño con voz.';
        }
        return '¡Hola! Soy Aracodito. Te muestro los avisos en texto.';
    });

    // El interruptor manda de inmediato: los avisos de la vista usan el mismo
    // valor que se ve en pantalla, sin esperar a guardar ni recargar.
    watch(
        () => [settings.aracodito_enabled, settings.aracodito_audio_enabled, settings.aracodito_ia_enabled],
        ([enabled, audio, ia]) => {
            window.USER_CONFIG = {
                ...(window.USER_CONFIG || {}),
                aracodito_enabled: Boolean(enabled),
                aracodito_audio_enabled: Boolean(audio),
                aracodito_ia_enabled: Boolean(ia),
            };
        },
        { immediate: true }
    );

    // Vistas previas reales: no guardan nada y no reproducen audio.
    const previews = [
        {
            type: 'success',
            label: 'Success',
            message: 'La operación se completó correctamente.',
            button: 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20',
            dot: 'bg-emerald-500',
        },
        {
            type: 'warning',
            label: 'Warning',
            message: 'Revisa los datos: hay algo que necesita tu atención.',
            button: 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20',
            dot: 'bg-amber-500',
        },
        {
            type: 'error',
            label: 'Error',
            message: 'No se pudo completar la operación. Revisa los datos e inténtalo de nuevo.',
            button: 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20',
            dot: 'bg-rose-500',
        },
        {
            type: 'confirm',
            label: 'Confirmación',
            message: '¿Confirmas que deseas guardar los cambios?',
            button: 'border-teal-200 bg-teal-50 text-teal-700 hover:bg-teal-100 dark:border-teal-500/30 dark:bg-teal-500/10 dark:text-teal-300 dark:hover:bg-teal-500/20',
            dot: 'bg-teal-500',
        },
        {
            type: 'help',
            label: 'Ayuda',
            message: 'Estoy aquí para ayudarte. ¿Qué necesitas?',
            button: 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20',
            dot: 'bg-indigo-500',
        },
        {
            type: 'info',
            label: 'Informativo',
            message: 'Este es un aviso informativo. No requiere ninguna acción.',
            button: 'border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:bg-sky-500/20',
            dot: 'bg-sky-500',
        },
        {
            type: 'question',
            label: 'Pregunta',
            message: '¿Qué estás intentando hacer? Cuéntame y te guío.',
            button: 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-700 hover:bg-fuchsia-100 dark:border-fuchsia-500/30 dark:bg-fuchsia-500/10 dark:text-fuchsia-300 dark:hover:bg-fuchsia-500/20',
            dot: 'bg-fuchsia-500',
        },
    ];

    const preview = (item) => {
        if (typeof window !== 'undefined' && window.Aracodito?.showMessage) {
            const asksForDecision = item.type === 'confirm' || item.type === 'question';
            const simulateProcess = item.type === 'confirm'
                ? () => new Promise((resolve) => {
                    window.setTimeout(() => resolve({
                        message: 'Cambios guardados correctamente.',
                        type: 'success',
                        duration: 4500,
                    }), 900);
                })
                : undefined;

            window.Aracodito.showMessage(item.message, item.type, {
                duration: 0,
                audio: false,
                ia: false,
                showConfirmButton: true,
                showCancelButton: asksForDecision,
                confirmButtonText: item.type === 'confirm' ? 'Sí, guardar' : 'OK',
                cancelButtonText: 'Cancelar',
                loadingText: 'Guardando los cambios…',
                minLoadingDuration: 650,
                ...(simulateProcess ? { onConfirm: simulateProcess } : {}),
            });
        }
    };

    const save = () => {
        settings.put(route('profile.aracodito.update'), {
            preserveScroll: true,
            onSuccess: () => {
                if (typeof window.alertaSistema === 'function') {
                    window.alertaSistema(
                        'Configuración guardada',
                        'La configuración de Aracodito se guardó correctamente.',
                        'success'
                    );
                }
            },
        });
    };

    // Expone el formulario por si otra vista necesita reutilizarlo.
    defineExpose({ settings });
</script>

<template>
    <!-- Sección "Asistente": se coloca debajo de la sección de Apariencia. -->
    <div class="panel mt-5">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
            <div>
                <h5 class="font-semibold text-lg">Configuración de Aracodito (Asistente Virtual)</h5>
                <p class="text-sm text-white-dark mt-1">
                    Elige cómo quieres que el sistema te avise y si el asistente debe hablar o responder preguntas.
                </p>
            </div>
            <span
                class="badge py-1.5 px-3 rounded-full text-xs font-semibold"
                :class="settings.aracodito_enabled ? 'bg-success-light text-success' : 'bg-danger-light text-danger'"
            >
                {{ settings.aracodito_enabled ? 'Aracodito activo' : 'Alertas clásicas' }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Controles -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Método de alerta -->
                <div class="border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <label for="aracodito_enabled" class="font-semibold cursor-pointer">Método de Alerta</label>
                            <p class="text-sm text-white-dark mt-1">
                                <span :class="settings.aracodito_enabled ? 'text-primary font-semibold' : ''">Usar Aracodito</span>
                                <span class="mx-1">/</span>
                                <span :class="!settings.aracodito_enabled ? 'text-primary font-semibold' : ''">Usar Alertas Clásicas (SwalAlert)</span>
                            </p>
                            <p class="text-xs text-white-dark mt-1">
                                Al activarlo se habilitan el modo de salida y la asistencia con IA.
                            </p>
                        </div>
                        <!-- Interruptor: usa el mismo patrón custom_switch del tema Vristo -->
                        <label class="w-12 h-6 relative shrink-0 cursor-pointer">
                            <input
                                id="aracodito_enabled"
                                name="aracodito_enabled"
                                v-model="settings.aracodito_enabled"
                                type="checkbox"
                                class="custom_switch absolute w-full h-full opacity-0 z-10 cursor-pointer peer"
                            />
                            <span
                                class="bg-[#ebedf2] dark:bg-dark block h-full rounded-full before:absolute before:left-1 before:bg-white dark:before:bg-white-dark dark:peer-checked:before:bg-white before:bottom-1 before:w-4 before:h-4 before:rounded-full peer-checked:before:left-7 peer-checked:bg-primary before:transition-all before:duration-300"
                            ></span>
                        </label>
                    </div>
                </div>

                <!-- Modo de salida -->
                <div class="border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-4" :class="{ 'opacity-60': !settings.aracodito_enabled }">
                    <p class="font-semibold">Modo de Salida</p>
                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label
                            for="aracodito_salida_texto"
                            class="flex items-start gap-3 border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-3 cursor-pointer"
                            :class="{ 'border-primary/60 bg-primary/5': !settings.aracodito_audio_enabled }"
                        >
                            <input
                                id="aracodito_salida_texto"
                                name="aracodito_audio_enabled"
                                v-model="settings.aracodito_audio_enabled"
                                :value="false"
                                type="radio"
                                class="form-radio mt-0.5 shrink-0"
                                :disabled="!settings.aracodito_enabled"
                            />
                            <span>
                                <span class="block font-semibold">Solo Texto</span>
                                <span class="block text-xs text-white-dark">La burbuja muestra el mensaje, sin sonido.</span>
                            </span>
                        </label>
                        <label
                            for="aracodito_salida_audio"
                            class="flex items-start gap-3 border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-3 cursor-pointer"
                            :class="{ 'border-primary/60 bg-primary/5': settings.aracodito_audio_enabled }"
                        >
                            <input
                                id="aracodito_salida_audio"
                                name="aracodito_audio_enabled"
                                v-model="settings.aracodito_audio_enabled"
                                :value="true"
                                type="radio"
                                class="form-radio mt-0.5 shrink-0"
                                :disabled="!settings.aracodito_enabled"
                            />
                            <span>
                                <span class="block font-semibold">Texto y Audio (TTS)</span>
                                <span class="block text-xs text-white-dark">Aracodito reproduce la voz y mueve la boca.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Interactividad -->
                <div class="border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-4" :class="{ 'opacity-60': !settings.aracodito_enabled }">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <label for="aracodito_ia_enabled" class="font-semibold cursor-pointer">Interactividad</label>
                            <p class="text-sm text-white-dark mt-1">
                                Activa la <span class="text-primary font-semibold">Asistencia con IA</span> para que Aracodito
                                responda preguntas sobre el uso del sistema.
                            </p>
                        </div>
                        <label class="w-12 h-6 relative shrink-0 cursor-pointer" :class="{ 'cursor-not-allowed': !settings.aracodito_enabled }">
                            <input
                                id="aracodito_ia_enabled"
                                name="aracodito_ia_enabled"
                                v-model="settings.aracodito_ia_enabled"
                                type="checkbox"
                                class="custom_switch absolute w-full h-full opacity-0 z-10 peer"
                                :class="settings.aracodito_enabled ? 'cursor-pointer' : 'cursor-not-allowed'"
                                :disabled="!settings.aracodito_enabled"
                            />
                            <span
                                class="bg-[#ebedf2] dark:bg-dark block h-full rounded-full before:absolute before:left-1 before:bg-white dark:before:bg-white-dark dark:peer-checked:before:bg-white before:bottom-1 before:w-4 before:h-4 before:rounded-full peer-checked:before:left-7 peer-checked:bg-primary before:transition-all before:duration-300"
                            ></span>
                        </label>
                    </div>
                </div>

                <p v-if="!settings.aracodito_enabled" class="text-xs text-white-dark">
                    Los modos de salida y la IA se habilitan al activar «Usar Aracodito».
                </p>
            </div>

            <!-- Vista previa estática del personaje -->
            <div class="rounded-md p-4 bg-gradient-to-b from-[#eaf1ff] to-white dark:from-[#0e1726] dark:to-[#060818] border border-[#ebedf2] dark:border-[#191e3a]">
                <p class="text-xs font-semibold uppercase tracking-wide text-white-dark mb-3">Vista previa</p>

                <div class="flex justify-center mb-4" :class="{ 'aracodito-talking': settings.aracodito_enabled && settings.aracodito_audio_enabled }">
                    <!-- Robot androide (personaje 3). Reemplazar este SVG por el asset
                         definitivo cuando esté disponible, p. ej.:
                         <img src="/assets/images/aracodito-3.png" alt="Aracodito" class="w-32 h-32 object-contain" /> -->
                    <svg viewBox="0 0 120 130" class="w-32 h-32" role="img" aria-label="Aracodito, asistente virtual">
                        <defs>
                            <linearGradient id="aracoditoBody" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#7dd3fc" />
                                <stop offset="100%" stop-color="#2563eb" />
                            </linearGradient>
                        </defs>
                        <!-- antena -->
                        <line x1="60" y1="14" x2="60" y2="26" stroke="#2563eb" stroke-width="4" stroke-linecap="round" />
                        <circle cx="60" cy="10" r="5" fill="#38bdf8" />
                        <!-- cabeza -->
                        <rect x="22" y="26" width="76" height="62" rx="24" fill="url(#aracoditoBody)" />
                        <!-- visor -->
                        <rect x="32" y="42" width="56" height="28" rx="14" fill="#0f172a" />
                        <!-- ojos -->
                        <circle cx="48" cy="56" r="5" fill="#38bdf8" />
                        <circle cx="72" cy="56" r="5" fill="#38bdf8" />
                        <!-- boca -->
                        <rect class="aracodito-mouth" x="52" y="64" width="16" height="4" rx="2" fill="#7dd3fc" />
                        <!-- orejas -->
                        <rect x="14" y="48" width="8" height="20" rx="4" fill="#2563eb" />
                        <rect x="98" y="48" width="8" height="20" rx="4" fill="#2563eb" />
                        <!-- cuello y cuerpo -->
                        <rect x="52" y="88" width="16" height="8" fill="#1d4ed8" />
                        <rect x="34" y="96" width="52" height="26" rx="12" fill="#1e40af" />
                        <circle cx="60" cy="109" r="6" fill="#38bdf8" />
                    </svg>
                </div>

                <!-- Burbuja de conversación -->
                <div class="relative bg-gradient-to-b from-white to-[#f5f9ff] dark:from-[#1a2742] dark:to-[#101a2e] border border-[#dce9ff] dark:border-[#2a3b5f] rounded-2xl px-4 py-3 shadow-lg shadow-slate-900/10 dark:shadow-black/50">
                    <span class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-white dark:bg-[#1a2742] border-l border-t border-[#dce9ff] dark:border-[#2a3b5f] rotate-45"></span>
                    <div class="flex items-start gap-2.5">
                        <span class="inline-flex items-center justify-center w-7 h-7 shrink-0 rounded-full bg-primary/10 text-primary border border-primary/20">
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <p class="text-sm leading-relaxed text-[#10203a] dark:text-[#eef4ff]">{{ bubbleText }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 mt-4">
                    <span class="badge text-[11px] px-2 py-1 rounded" :class="settings.aracodito_enabled ? 'bg-primary/10 text-primary' : 'bg-[#ebedf2] dark:bg-dark text-white-dark'">Texto</span>
                    <span class="badge text-[11px] px-2 py-1 rounded" :class="settings.aracodito_enabled && settings.aracodito_audio_enabled ? 'bg-primary/10 text-primary' : 'bg-[#ebedf2] dark:bg-dark text-white-dark'">TTS</span>
                    <span class="badge text-[11px] px-2 py-1 rounded" :class="settings.aracodito_enabled && settings.aracodito_ia_enabled ? 'bg-primary/10 text-primary' : 'bg-[#ebedf2] dark:bg-dark text-white-dark'">IA</span>
                </div>
            </div>
        </div>

        <!-- Vistas previas de los tipos de mensaje de Aracodito -->
        <div class="mt-5 pt-5 border-t border-[#ebedf2] dark:border-[#191e3a]">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                <div>
                    <h6 class="font-semibold">Vistas previas de Aracodito</h6>
                    <p class="text-sm text-white-dark mt-1">
                        Haz clic en un tipo para ver el aviso real con sus botones. La confirmación simula
                        un proceso para mostrar la mini consola del pecho; no guarda cambios ni reproduce audio.
                    </p>
                </div>
                <span class="badge bg-primary/10 text-primary text-xs px-2.5 py-1 rounded-full">7 estilos</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                <button
                    v-for="item in previews"
                    :key="item.type"
                    type="button"
                    class="flex items-center justify-center gap-2 rounded-lg border px-3 py-2.5 text-sm font-semibold transition hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                    :class="item.button"
                    :aria-label="`Vista previa de Aracodito tipo ${item.label}`"
                    @click="preview(item)"
                >
                    <span class="h-2.5 w-2.5 rounded-full" :class="item.dot"></span>
                    <span>{{ item.label }}</span>
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 mt-5 pt-4 border-t border-[#ebedf2] dark:border-[#191e3a]">
            <button
                id="aracodito-save"
                type="button"
                class="btn btn-primary"
                :disabled="settings.processing"
                @click="save"
            >
                {{ settings.processing ? 'Guardando...' : 'Guardar configuración' }}
            </button>
        </div>
    </div>
</template>

<style scoped>
    .aracodito-mouth {
        transform-box: fill-box;
        transform-origin: center;
    }

    .aracodito-talking .aracodito-mouth {
        animation: aracodito-talk 0.45s ease-in-out infinite;
    }

    @keyframes aracodito-talk {
        0%,
        100% {
            transform: scaleY(0.5);
        }
        50% {
            transform: scaleY(1.8);
        }
    }
</style>
