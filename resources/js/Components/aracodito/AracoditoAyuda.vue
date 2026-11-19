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
 *
 * Con Aracodito IA activo en la cuenta y el permiso ia_aracodito, la pregunta
 * se responde con el proveedor de IA configurado. La IA solo ayuda con el uso
 * del sistema: no responde temas ajenos. Si falla, se usa el catálogo local.
 */
const page = usePage();

const abierto = ref(false);
const consulta = ref('');
const temaActivo = ref(null);
const preguntando = ref(false);
const respuestaIA = ref(null);
const errorIA = ref('');

const ruta = computed(() => String(page.url || '/').split('?')[0]);
const permisos = computed(() => page.props.auth?.permissions || []);
const ubicacion = computed(() => ubicacionActual(ruta.value, permisos.value));
const guias = computed(() => guiasDePantalla(ruta.value));

const iaDisponible = computed(() => {
    const activo = Boolean(page.props.auth?.user?.aracodito_ia_enabled);

    return activo && permisos.value.includes('ia_aracodito');
});

// La voz solo se usa si la cuenta tiene Aracodito y el audio activos.
const audioActivo = computed(() => Boolean(
    page.props.auth?.user?.aracodito_enabled && page.props.auth?.user?.aracodito_audio_enabled
));

/**
 * Lee un texto en voz alta con el motor de Aracodito (Web Speech API).
 */
const hablar = (texto) => {
    if (!audioActivo.value || !texto) {
        return;
    }

    window.Aracodito?.speak?.(String(texto));
};

/**
 * Corta la voz en curso al cerrar el panel o cambiar de respuesta.
 */
const callar = () => {
    window.Aracodito?.stopSpeaking?.();
};

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
    callar();
    abierto.value = false;
    consulta.value = '';
    temaActivo.value = null;
    respuestaIA.value = null;
    errorIA.value = '';
};

const elegir = (tema) => {
    respuestaIA.value = null;
    errorIA.value = '';
    temaActivo.value = tema;

    const enBurbuja = Boolean(window.alertaSistema?.enAracodito?.());

    // Con Aracodito activo la respuesta también sale en su burbuja, con los
    // mismos botones del panel: «Abrir pantalla» navega de verdad.
    if (enBurbuja) {
        const botones = tema.url
            ? {
                actions: [
                    { label: 'Abrir pantalla', value: 'abrir', action: 'confirm', variant: 'primary' },
                    { label: 'Entendido', value: 'cerrar', action: 'cancel', variant: 'secondary' },
                ],
                onAction: (resultado) => {
                    if (resultado?.value === 'abrir') {
                        irAPantalla(tema);
                    }
                },
            }
            : { confirmButtonText: 'Entendido' };

        window.alertaSistema('Aracodito te ayuda', tema.respuesta, 'info', {
            duration: 0,
            ia: true,
            ...botones,
        });
    } else {
        // Sin burbuja, el panel se encarga de la voz.
        hablar(tema.respuesta);
    }
};

const volver = () => {
    callar();
    temaActivo.value = null;
    respuestaIA.value = null;
    errorIA.value = '';
};

/**
 * Convierte una dirección absoluta en ruta interna. El menú arma sus enlaces
 * con APP_URL; si la persona entró por otro dominio (localhost, IP), navegar
 * con la URL completa puede fallar o cambiar de sesión.
 */
const rutaInterna = (url) => {
    try {
        const destino = new URL(url, window.location.origin);

        return `${destino.pathname}${destino.search}${destino.hash}`;
    } catch {
        return String(url || '');
    }
};

const irAPantalla = (tema) => {
    if (!tema?.url) {
        return;
    }

    const destino = rutaInterna(tema.url);

    if (tema.fullPage) {
        window.location.href = destino;
        return;
    }

    cerrar();
    router.visit(destino);
};

/**
 * Pregunta a la IA. Solo viajan la pregunta (con datos personales
 * enmascarados en el servidor), la ruta actual y las pantallas que el usuario
 * ya puede abrir. El catálogo local sigue visible debajo como respaldo.
 */
const preguntarIA = async () => {
    const pregunta = consulta.value.trim();

    if (!iaDisponible.value || !pregunta || preguntando.value) {
        return;
    }

    preguntando.value = true;
    errorIA.value = '';
    respuestaIA.value = null;
    callar();

    try {
        const { data } = await axios.post(route('ai_aracodito_preguntar'), {
            pregunta,
            ruta: ruta.value,
        });

        if (data.success) {
            respuestaIA.value = data;
            hablar(data.respuesta);
        } else {
            errorIA.value = data.message || 'Aracodito no pudo responder en este momento.';
        }
    } catch (error) {
        errorIA.value =
            error.response?.data?.message ||
            'No se pudo consultar la IA. Te muestro la ayuda del sistema.';
    } finally {
        preguntando.value = false;
    }
};

// Al cambiar la pregunta se descarta la respuesta anterior para no mezclar
// una respuesta de IA con otra consulta.
watch(consulta, () => {
    callar();
    respuestaIA.value = null;
    errorIA.value = '';
});

// Al cambiar de pantalla con el panel abierto, se vuelve al inicio de la ayuda
// para no dejar abierta una respuesta de la pantalla anterior.
watch(ruta, () => {
    temaActivo.value = null;
    consulta.value = '';
    respuestaIA.value = null;
    errorIA.value = '';
});
</script>

<template>
    <div v-if="!oculto">
        <!-- Se monta en <body>: ninguna capa del layout (transform, overflow
             o z-index) puede tapar el panel ni bloquear sus botones. -->
        <Teleport to="body">
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
            class="panel fixed bottom-24 left-3 right-3 z-[10000] flex flex-col overflow-hidden p-4 shadow-2xl sm:left-auto sm:right-4 sm:w-[400px]"
            role="dialog"
            aria-label="Ayuda de Aracodito"
        >
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h5 class="text-base font-semibold text-black dark:text-white-light">Aracodito te ayuda</h5>
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
                <form class="mb-3 flex gap-2" @submit.prevent="preguntarIA">
                    <input
                        v-model="consulta"
                        type="search"
                        class="form-input w-full"
                        placeholder="Ej: dónde está caja, cómo elimino un paciente"
                        aria-label="Buscar en la ayuda"
                    />
                    <button
                        v-if="iaDisponible"
                        type="submit"
                        class="btn btn-sm btn-primary shrink-0"
                        :disabled="preguntando || !consulta.trim()"
                        :title="'Preguntar a Aracodito con IA'"
                    >
                        {{ preguntando ? '…' : 'Preguntar' }}
                    </button>
                </form>

                <div v-if="preguntando" class="mb-3 rounded-md bg-primary/5 px-3 py-2 text-sm text-white-dark">
                    Pensando la respuesta…
                </div>

                <div v-if="respuestaIA" class="mb-3 rounded-md border border-primary/30 bg-primary/5 p-3">
                    <p class="mb-2 whitespace-pre-line text-sm text-white-dark">{{ respuestaIA.respuesta }}</p>
                    <div class="flex flex-wrap gap-2">
                        <a
                            v-if="respuestaIA.url"
                            :href="respuestaIA.url"
                            class="btn btn-sm btn-primary"
                            @click.prevent="irAPantalla({ url: respuestaIA.url })"
                        >
                            Abrir pantalla
                        </a>
                        <span class="self-center text-[11px] text-white-dark">
                            Respuesta de Aracodito solo sobre el uso del sistema.
                        </span>
                    </div>
                </div>

                <p v-if="errorIA" class="mb-3 rounded-md bg-warning/10 px-3 py-2 text-xs text-warning">
                    {{ errorIA }}
                </p>

                <div class="max-h-72 space-y-1 overflow-y-auto pr-1">
                    <p v-if="!consulta.trim() && guias.length" class="px-2 pt-1 text-xs font-semibold uppercase text-white-dark">
                        En esta pantalla
                    </p>

                    <button
                        v-for="tema in temasVisibles"
                        :key="tema.id"
                        type="button"
                        class="w-full rounded-md px-3 py-2 text-left text-sm text-black transition hover:bg-primary/10 hover:text-primary dark:text-white-light"
                        @click="elegir(tema)"
                    >
                        {{ tema.pregunta }}
                    </button>

                    <p v-if="consulta.trim() && !temasVisibles.length && !respuestaIA" class="px-2 py-3 text-sm text-white-dark">
                        No encontré una guía para esa duda. Prueba con otras palabras (por ejemplo el nombre de la
                        opción del menú) o consulta con el administrador del sistema.
                    </p>
                </div>

                <p class="mt-3 border-t border-white-light pt-2 text-xs text-white-dark dark:border-dark">
                    <template v-if="iaDisponible">
                        Aracodito solo ayuda a usar el sistema: pantallas, permisos y pasos de un proceso.
                    </template>
                    <template v-else>
                        Escribe lo que necesitas y te digo dónde está o cómo se hace.
                    </template>
                </p>
            </template>
        </div>
        </Teleport>
    </div>
</template>

<style scoped>
/* Botón con la identidad de Aracodito (azul del asistente). */
.aracodito-ayuda__boton {
    background: linear-gradient(135deg, #2196f3 0%, #0b1f3a 100%);
    border: 1px solid rgba(255, 255, 255, 0.25);
}
</style>
