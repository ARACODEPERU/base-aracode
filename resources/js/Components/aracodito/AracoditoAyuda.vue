<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { AYUDA_SISTEMA, buscarTemas, guiasDePantalla, ubicacionActual } from './catalogo';

/**
 * Aracodito — ayuda global del sistema.
 *
 * Se monta una sola vez en el layout, así que el botón aparece en todas las
 * pantallas autenticadas. Al abrirlo:
 *  - saluda indicando el módulo y la pantalla en la que está la persona;
 *  - ofrece las guías de esa pantalla (si existen) y las preguntas generales;
 *  - permite escribir cualquier duda o el nombre de una opción del menú y
 *    responder con los pasos, además del botón «Abrir pantalla».
 */
const page = usePage();

const abierto = ref(false);
const consulta = ref('');
const temaActivo = ref(null);

const ruta = computed(() => String(page.url || '/').split('?')[0]);
const permisos = computed(() => page.props.auth?.permissions || []);
const ubicacion = computed(() => ubicacionActual(ruta.value, permisos.value));
const guias = computed(() => guiasDePantalla(ruta.value));

// Tesorería tiene su propia guía por pestaña (TreasuryGuide): no duplicamos
// el botón de Aracodito en esas pantallas.
const oculto = computed(() => /^\/treasury(\/|$)/.test(ruta.value));

const resultados = computed(() =>
    buscarTemas({ permisos: permisos.value, ruta: ruta.value, consulta: consulta.value })
);

const temasVisibles = computed(() => {
    if (consulta.value.trim()) {
        return resultados.value;
    }

    // Sin búsqueda: primero lo útil de esta pantalla y luego lo general.
    return [...guias.value, ...AYUDA_SISTEMA].slice(0, 10);
});

const saludo = computed(() => {
    if (ubicacion.value?.pantalla && ubicacion.value.pantalla !== ubicacion.value.modulo) {
        return `Estás en ${ubicacion.value.pantalla}.`;
    }

    if (ubicacion.value?.modulo) {
        return `Estás en ${ubicacion.value.modulo}.`;
    }

    return 'Estoy aquí para enseñarte a usar el sistema.';
});

const abrir = () => {
    abierto.value = true;
    temaActivo.value = null;
};

const cerrar = () => {
    abierto.value = false;
    consulta.value = '';
    temaActivo.value = null;
};

const elegir = (tema) => {
    temaActivo.value = tema;

    // Con Aracodito activo la respuesta también sale en su burbuja.
    if (window.alertaSistema?.enAracodito?.()) {
        window.alertaSistema('Aracodito te ayuda', tema.respuesta, 'info', {
            duration: 0,
            ia: true,
            confirmButtonText: 'Entendido',
        });
    }
};

const volver = () => {
    temaActivo.value = null;
};

const irAPantalla = (tema) => {
    if (!tema?.url) {
        return;
    }

    if (tema.fullPage) {
        window.location.href = tema.url;
        return;
    }

    cerrar();
    router.visit(tema.url);
};

// Al cambiar de pantalla con el panel abierto, se vuelve al inicio de la ayuda
// para no dejar abierta una respuesta de la pantalla anterior.
watch(ruta, () => {
    temaActivo.value = null;
    consulta.value = '';
});
</script>

<template>
    <div v-if="!oculto">
        <!-- Botón flotante: presente en todas las pantallas del sistema -->
        <button
            type="button"
            class="aracodito-ayuda__boton fixed bottom-6 right-6 z-[9997] flex h-14 items-center gap-2 rounded-full px-4 text-sm font-semibold text-white shadow-xl transition hover:scale-[1.03] focus:outline-none focus:ring-2 focus:ring-white/60"
            aria-label="Abrir la ayuda de Aracodito"
            :aria-expanded="abierto"
            @click="abierto ? cerrar() : abrir()"
        >
            <svg width="28" height="28" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                <rect x="6" y="10" width="20" height="15" rx="5" fill="#ffffff" />
                <rect x="6" y="10" width="20" height="15" rx="5" stroke="#0b1f3a" stroke-width="1.5" />
                <circle cx="12.5" cy="17.5" r="2.1" fill="#0b1f3a" />
                <circle cx="19.5" cy="17.5" r="2.1" fill="#0b1f3a" />
                <path d="M12.5 22h7" stroke="#0b1f3a" stroke-width="1.6" stroke-linecap="round" />
                <path d="M16 10V5" stroke="#0b1f3a" stroke-width="1.6" stroke-linecap="round" />
                <circle cx="16" cy="4" r="2" fill="#f59e0b" />
            </svg>
            <span class="hidden sm:inline">Ayuda</span>
            <span class="sm:hidden">?</span>
        </button>

        <!-- Panel de ayuda -->
        <div
            v-if="abierto"
            class="panel fixed bottom-24 right-4 z-[9999] flex w-[min(94vw,400px)] flex-col overflow-hidden p-4 shadow-2xl"
            role="dialog"
            aria-label="Ayuda de Aracodito"
        >
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h5 class="text-base font-semibold">Aracodito te ayuda</h5>
                    <p class="text-xs text-white-dark">{{ saludo }}</p>
                </div>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger px-2 py-1"
                    aria-label="Cerrar la ayuda"
                    @click="cerrar"
                >
                    ✕
                </button>
            </div>

            <template v-if="temaActivo">
                <h6 class="mb-2 text-sm font-semibold text-primary">{{ temaActivo.pregunta }}</h6>
                <p class="mb-3 max-h-64 overflow-y-auto whitespace-pre-line text-sm text-white-dark">
                    {{ temaActivo.respuesta }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="temaActivo.url"
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="irAPantalla(temaActivo)"
                    >
                        Abrir pantalla
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="volver">
                        Otra duda
                    </button>
                </div>
            </template>

            <template v-else>
                <input
                    v-model="consulta"
                    type="search"
                    class="form-input mb-3 w-full"
                    placeholder="Ej: dónde está caja, cómo elimino un paciente"
                    aria-label="Buscar en la ayuda"
                />

                <div class="max-h-72 space-y-1 overflow-y-auto pr-1">
                    <p v-if="!consulta.trim() && guias.length" class="px-2 pt-1 text-xs font-semibold uppercase text-white-dark">
                        En esta pantalla
                    </p>

                    <button
                        v-for="tema in temasVisibles"
                        :key="tema.id"
                        type="button"
                        class="w-full rounded-md px-3 py-2 text-left text-sm transition hover:bg-primary/10 hover:text-primary"
                        @click="elegir(tema)"
                    >
                        {{ tema.pregunta }}
                    </button>

                    <p v-if="consulta.trim() && !temasVisibles.length" class="px-2 py-3 text-sm text-white-dark">
                        No encontré una guía para esa duda. Prueba con otras palabras (por ejemplo el nombre de la
                        opción del menú) o consulta con el administrador del sistema.
                    </p>
                </div>

                <p class="mt-3 border-t border-white-light pt-2 text-xs text-white-dark dark:border-dark">
                    Escribe lo que necesitas y te digo dónde está o cómo se hace.
                </p>
            </template>
        </div>
    </div>
</template>

<style scoped>
/* Botón con la identidad de Aracodito (azul del asistente). */
.aracodito-ayuda__boton {
    background: linear-gradient(135deg, #2196f3 0%, #0b1f3a 100%);
    border: 1px solid rgba(255, 255, 255, 0.25);
}
</style>
