<script setup>
    import { Link, router } from '@inertiajs/vue3';
    import { computed, onMounted } from 'vue';

    const props = defineProps({
        preview: {
            type: Object,
            required: true,
        },
    });

    const contentModes = computed(() => props.preview.modes.filter((mode) => mode.kind === 'all'));
    const subscriptionModes = computed(() => props.preview.modes.filter((mode) => mode.kind === 'subscription'));
    const purchaseModes = computed(() => props.preview.modes.filter((mode) => mode.kind === 'purchase'));

    const changeMode = (event) => {
        const url = new URL(window.location.href);
        url.searchParams.set('mode', event.target.value);
        router.get(url.pathname + url.search, {}, { preserveScroll: true, replace: true });
    };

    const savedProgress = () => {
        try {
            return JSON.parse(localStorage.getItem(props.preview.progress_key) || '[]');
        } catch (error) {
            return [];
        }
    };

    const resetProgress = async (confirmar = true) => {
        if (confirmar) {
            const result = await window.alertaSistema(
                'Reiniciar progreso de la vista previa',
                'Se pondrán en 0 los contenidos marcados como vistos. El avance real del curso no se modifica.',
                'question',
                {
                    duration: 0,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, reiniciar',
                    cancelButtonText: 'No, continuar',
                    reverseButtons: true,
                }
            );

            if (!result?.isConfirmed) {
                return;
            }
        }

        localStorage.removeItem(props.preview.progress_key);
        window.dispatchEvent(new CustomEvent('academic-preview-progress-reset', {
            detail: { key: props.preview.progress_key },
        }));
        router.reload({ preserveScroll: true, preserveState: true });

        window.alertaSistema('Progreso reiniciado', 'La vista previa volvió a 0.', 'success');
    };

    onMounted(() => {
        if (!props.preview.prompt_reset) {
            return;
        }

        // Al entrar al curso se pregunta si se desea empezar de cero.
        if (savedProgress().length > 0) {
            resetProgress(true);
        }
    });
</script>

<template>
    <div class="sticky top-[70px] z-30 mb-5 overflow-hidden rounded-xl border border-amber-300 bg-amber-50 shadow-lg dark:border-amber-700 dark:bg-amber-900/30">
        <div class="flex flex-col gap-3 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-amber-500 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">
                        Vista previa de curso
                    </span>
                    <span class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                        — {{ preview.mode_label }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-amber-800 dark:text-amber-200">
                    Estás viendo el curso como alumno. No se registra avance real.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <label class="sr-only" for="preview-mode">Tipo de acceso</label>
                <select
                    id="preview-mode"
                    class="form-select w-full sm:w-64"
                    :value="preview.mode"
                    @change="changeMode"
                >
                    <optgroup v-if="contentModes.length" label="Contenido">
                        <option v-for="mode in contentModes" :key="mode.value" :value="mode.value">
                            {{ mode.label }}
                        </option>
                    </optgroup>
                    <optgroup v-if="subscriptionModes.length" label="Suscripciones">
                        <option v-for="mode in subscriptionModes" :key="mode.value" :value="mode.value">
                            {{ mode.label }}
                        </option>
                    </optgroup>
                    <optgroup v-if="purchaseModes.length" label="Compra">
                        <option v-for="mode in purchaseModes" :key="mode.value" :value="mode.value">
                            {{ mode.label }}
                        </option>
                    </optgroup>
                </select>

                <button type="button" class="btn btn-outline-warning" @click="resetProgress(true)">
                    Reiniciar progreso
                </button>
                <Link :href="preview.back_url" class="btn btn-outline-primary">
                    Volver a cursos
                </Link>
            </div>
        </div>

        <div
            v-if="!preview.has_access"
            class="border-t border-amber-300 bg-amber-100 px-4 py-2 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-900/50 dark:text-amber-100"
        >
            {{ preview.access_reason }} Puedes revisar la pantalla, pero este plan no incluye el curso.
        </div>
    </div>
</template>
