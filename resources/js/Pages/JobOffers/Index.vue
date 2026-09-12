<script setup>
    import { onBeforeUnmount, onMounted, ref } from 'vue';
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    const props = defineProps({
        iframeCode: {
            type: String,
            default: null,
        },
    });

    // Atributos del <iframe> extraídos del código guardado (null si no hay iframe)
    const iframeAttrs = ref(null);

    // Estado del aviso de carga
    const isLoading = ref(true);
    const isSlow = ref(false);
    const hasError = ref(false);

    // A partir de este tiempo se ofrece abrir el contenido en una pestaña nueva
    const SLOW_AFTER_MS = 25000;
    let slowTimer = null;

    const clearSlowTimer = () => {
        if (slowTimer) {
            clearTimeout(slowTimer);
            slowTimer = null;
        }
    };

    /**
     * Extrae el primer <iframe> del código configurado en el parámetro P000032
     * para renderizarlo con Vue y así poder escuchar su carga (con v-html no se
     * puede). Si el código no trae un <iframe> (por ejemplo un widget con
     * <script>), se devuelve null y se inyecta con v-html como respaldo.
     */
    const parseIframe = () => {
        const code = props.iframeCode;

        if (!code || typeof window === 'undefined' || typeof DOMParser === 'undefined') {
            iframeAttrs.value = null;
            return;
        }

        const parsed = new DOMParser().parseFromString(code, 'text/html');
        const frame = parsed.querySelector('iframe');
        const src = frame ? frame.getAttribute('src') : null;

        if (!frame || !src) {
            iframeAttrs.value = null;
            return;
        }

        const attrs = {
            src: src,
            title: frame.getAttribute('title') || 'Ofertas laborales',
        };

        ['allow', 'allowfullscreen', 'sandbox', 'referrerpolicy', 'loading', 'name', 'id'].forEach((name) => {
            const value = frame.getAttribute(name);

            if (value !== null) {
                attrs[name] = value;
            }
        });

        iframeAttrs.value = attrs;
    };

    const startSlowTimer = () => {
        clearSlowTimer();
        slowTimer = setTimeout(() => {
            isSlow.value = true;
        }, SLOW_AFTER_MS);
    };

    const onIframeLoad = () => {
        isLoading.value = false;
        isSlow.value = false;
        clearSlowTimer();
    };

    const onIframeError = () => {
        isLoading.value = false;
        isSlow.value = false;
        hasError.value = true;
        clearSlowTimer();
    };

    onMounted(() => {
        parseIframe();

        if (iframeAttrs.value) {
            startSlowTimer();
        }
    });

    onBeforeUnmount(clearSlowTimer);
</script>

<template>
    <AppLayout title="Ofertas Laborales">
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Ofertas Laborales</span>
            </li>
        </Navigation>

        <div class="mt-5">
            <div class="panel p-0 overflow-hidden">
                <!-- Código configurado en el parámetro del sistema P000032 -->
                <div v-if="iframeCode" class="job-offers-embed relative w-full">
                    <template v-if="iframeAttrs">
                        <iframe v-bind="iframeAttrs" @load="onIframeLoad" @error="onIframeError"></iframe>

                        <!-- Aviso de carga: el embed de Airtable puede tardar -->
                        <div
                            v-if="isLoading && !hasError"
                            class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/90 dark:bg-[#0e1726]/90"
                        >
                            <svg class="animate-spin h-10 w-10 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-4 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ isSlow ? 'Airtable está tardando más de lo normal…' : 'Cargando ofertas laborales, espera un momento...' }}
                            </p>
                            <a
                                v-if="isSlow"
                                :href="iframeAttrs.src"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-4 inline-block px-6 py-2.5 bg-primary text-white text-xs font-semibold uppercase rounded shadow-md transition hover:opacity-90"
                            >
                                Abrir en una pestaña nueva
                            </a>
                        </div>

                        <!-- El embed no se pudo cargar -->
                        <div
                            v-if="hasError"
                            class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/90 dark:bg-[#0e1726]/90"
                        >
                            <i class="ri-error-warning-line text-5xl text-amber-500"></i>
                            <p class="mt-4 text-sm font-semibold text-gray-700 dark:text-gray-200">No pudimos cargar las ofertas laborales.</p>
                            <a
                                :href="iframeAttrs.src"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-4 inline-block px-6 py-2.5 bg-primary text-white text-xs font-semibold uppercase rounded shadow-md transition hover:opacity-90"
                            >
                                Abrir en una pestaña nueva
                            </a>
                        </div>
                    </template>

                    <!-- Respaldo: código sin <iframe> (widgets con <script>) -->
                    <div v-else class="w-full" v-html="iframeCode"></div>
                </div>

                <div v-else class="p-10 text-center">
                    <i class="ri-briefcase-line text-5xl text-gray-300 dark:text-gray-600"></i>
                    <h4 class="mt-4 font-semibold text-gray-700 dark:text-gray-200">Aún no hay ofertas laborales disponibles</h4>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Vuelve pronto, publicaremos nuevas oportunidades.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.job-offers-embed iframe {
    width: 100%;
    height: 78vh;
    border: 0;
    display: block;
}
</style>
