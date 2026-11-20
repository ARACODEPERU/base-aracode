<script setup>
import { computed, reactive, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { faPaperPlane, faTriangleExclamation } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    provider: { type: String, default: 'gemini' },
    openAiModel: { type: String, default: '' },
    geminiModel: { type: String, default: '' },
    openAiConfigured: { type: Boolean, default: false },
    geminiConfigured: { type: Boolean, default: false },
    providers: { type: Array, default: () => [] },
});

const form = reactive({
    provider: props.provider,
    mensaje: '',
});

const enviando = ref(false);
const resultado = ref(null);

const configurado = computed(() =>
    form.provider === 'openai' ? props.openAiConfigured : props.geminiConfigured
);

const modelo = computed(() => (form.provider === 'openai' ? props.openAiModel : props.geminiModel));

const número = (valor) => new Intl.NumberFormat('es-PE').format(Number(valor || 0));

const usd = (valor) => {
    const numero = Number(valor || 0);

    return `US$ ${numero.toFixed(numero > 0 && numero < 0.01 ? 6 : 4)}`;
};

const enviar = async () => {
    if (enviando.value) {
        return;
    }

    if (!form.mensaje.trim()) {
        window.alertaSistema('Falta la consulta', 'Escribe un mensaje para probar la IA.', 'warning');

        return;
    }

    enviando.value = true;
    resultado.value = null;

    try {
        const { data } = await axios.post(route('ai_probar_enviar'), { ...form });
        resultado.value = data;
    } catch (error) {
        window.alertaSistema(
            'La IA no respondió',
            error.response?.data?.message || 'Revisa la credencial y el modelo configurado.',
            'error'
        );
    } finally {
        enviando.value = false;
    }
};
</script>

<template>
    <AppLayout title="Probar IA">
        <Head title="Probar IA">
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation :routeModule="route('ai_dashboard')" :titleModule="'IA'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Probar IA</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div
                v-if="!configurado"
                class="panel flex items-start gap-3 border-l-4 border-warning p-4 text-sm text-gray-600 dark:text-gray-300"
            >
                <font-awesome-icon :icon="faTriangleExclamation" class="mt-1 shrink-0 text-warning" />
                <p>
                    Falta la credencial de {{ form.provider === 'openai' ? 'OpenAI' : 'Gemini' }}.
                    Cárgala en <strong>IA → Configuración</strong> o en Parámetros del sistema.
                </p>
            </div>

            <div class="panel">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold">Consulta de prueba</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Esta prueba consume tokens reales y queda registrada en el consumo con tu usuario.
                    </p>
                </div>
                <form class="space-y-4 p-5" @submit.prevent="enviar">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Proveedor</label>
                            <select v-model="form.provider" class="form-select w-full">
                                <option v-for="option in providers" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Modelo</label>
                            <input :value="modelo" type="text" class="form-input w-full" readonly />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Mensaje</label>
                        <textarea
                            v-model="form.mensaje"
                            rows="4"
                            class="form-textarea w-full"
                            placeholder="Escribe una consulta breve para comprobar la conexión."
                        ></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary" :disabled="enviando">
                            <font-awesome-icon :icon="faPaperPlane" class="mr-1" />
                            {{ enviando ? 'Consultando...' : 'Enviar consulta' }}
                        </button>
                    </div>
                </form>
            </div>

            <div v-if="resultado" class="panel">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold">Respuesta</h2>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="rounded bg-primary/10 px-2 py-1 text-primary">
                            {{ resultado.provider }} · {{ resultado.model }}
                        </span>
                        <span class="rounded bg-info/10 px-2 py-1 text-info">
                            {{ número(resultado.total_tokens) }} tokens
                        </span>
                        <span class="rounded bg-success/10 px-2 py-1 text-success">
                            {{ usd(resultado.cost_usd) }}
                        </span>
                    </div>
                </div>
                <div class="space-y-4 p-5">
                    <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">{{ resultado.respuesta }}</p>
                    <div class="grid grid-cols-1 gap-3 border-t border-gray-200 pt-4 text-xs dark:border-gray-700 sm:grid-cols-3">
                        <p class="text-gray-500 dark:text-gray-400">
                            Entrada: <strong>{{ número(resultado.prompt_tokens) }}</strong>
                        </p>
                        <p class="text-gray-500 dark:text-gray-400">
                            Salida: <strong>{{ número(resultado.completion_tokens) }}</strong>
                        </p>
                        <p class="text-gray-500 dark:text-gray-400">
                            Total: <strong>{{ número(resultado.total_tokens) }}</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
