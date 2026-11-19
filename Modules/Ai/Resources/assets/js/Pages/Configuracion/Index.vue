<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { faCircleInfo } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    parameters: { type: Array, default: () => [] },
    providerOptions: { type: Array, default: () => [] },
    effective: { type: Object, default: () => ({}) },
    roles: { type: Array, default: () => [] },
});

const porCodigo = computed(() =>
    Object.fromEntries(props.parameters.map((parameter) => [parameter.code, parameter]))
);

const valores = reactive({});

for (const parameter of props.parameters) {
    // Un confidencial nunca llega con valor: se deja vacío y se conserva el
    // guardado si no se escribe uno nuevo.
    valores[parameter.code] = parameter.is_secret ? '' : parameter.value ?? '';
}

const page = usePage();
const esAdmin = computed(() => (page.props.auth?.roles || []).includes('admin'));

const guardando = ref(false);
const guardandoRoles = ref(false);
const guardandoRolesIa = ref(false);
const limitesRoles = reactive((props.roles || []).map((role) => ({ ...role })));

// El rol admin siempre conserva el acceso y no se puede desmarcar.
const normalizarRolesIa = (roles) =>
    (roles || []).map((role) => ({
        name: role.name,
        aracodito_ia: role.name === 'admin' ? true : Boolean(role.aracodito_ia),
    }));

const rolesIa = ref(normalizarRolesIa(props.roles));

const guardarRolesIa = async () => {
    if (guardandoRolesIa.value) {
        return;
    }

    guardandoRolesIa.value = true;

    try {
        const { data } = await axios.post(route('ai_configuracion_aracodito_ia'), {
            roles: rolesIa.value.filter((role) => role.aracodito_ia).map((role) => role.name),
        });

        rolesIa.value = normalizarRolesIa(data.roles);
        window.alertaSistema('Acceso a IA actualizado', data.message, 'success');
    } catch (error) {
        const errores = error.response?.data?.errors || {};
        const primero = Object.values(errores).flat()[0];

        window.alertaSistema(
            'No se pudo guardar el acceso',
            primero || error.response?.data?.message || 'Revisa los roles e intenta nuevamente.',
            'error'
        );
    } finally {
        guardandoRolesIa.value = false;
    }
};

const guardarRoles = async () => {
    if (guardandoRoles.value) {
        return;
    }

    guardandoRoles.value = true;

    try {
        const { data } = await axios.post(route('ai_configuracion_roles'), { limits: limitesRoles });

        window.alertaSistema('Límites guardados', data.message, 'success');
        router.reload({ only: ['roles'] });
    } catch (error) {
        const errores = error.response?.data?.errors || {};
        const primero = Object.values(errores).flat()[0];

        window.alertaSistema(
            'No se pudieron guardar los límites',
            primero || error.response?.data?.message || 'Revisa los valores e intenta nuevamente.',
            'error'
        );
    } finally {
        guardandoRoles.value = false;
    }
};

const parametro = (code) => porCodigo.value[code] || null;
const descripcion = (code) => parametro(code)?.description || '';
const tieneValor = (code) => Boolean(parametro(code)?.has_value);
const esSecreto = (code) => Boolean(parametro(code)?.is_secret);

const guardar = async () => {
    if (guardando.value) {
        return;
    }

    guardando.value = true;

    try {
        const { data } = await axios.post(route('ai_configuracion_update'), { values: valores });

        window.alertaSistema('Configuración guardada', data.message, 'success');
        router.reload({ only: ['parameters', 'effective'] });
    } catch (error) {
        const errores = error.response?.data?.errors || {};
        const primero = Object.values(errores).flat()[0];

        window.alertaSistema(
            'No se pudo guardar',
            primero || error.response?.data?.message || 'Revisa los datos e intenta nuevamente.',
            'error'
        );
    } finally {
        guardando.value = false;
    }
};
</script>

<template>
    <AppLayout title="Configuración de IA">
        <Head title="Configuración de IA">
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation :routeModule="route('ai_dashboard')" :titleModule="'IA'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Configuración</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex items-start gap-3 border-l-4 border-info p-4 text-sm text-gray-600 dark:text-gray-300">
                <font-awesome-icon :icon="faCircleInfo" class="mt-1 shrink-0 text-info" />
                <p>
                    Estos son los mismos parámetros del sistema (pestaña <strong>IA</strong> en
                    Parámetros). Un cambio aquí se ve al instante en Parámetros, y un cambio
                    en Parámetros se ve aquí.
                </p>
            </div>

            <form class="space-y-5" @submit.prevent="guardar">
                <div class="panel">
                    <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 class="text-lg font-semibold">Proveedor y modelos</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            El proveedor activo lo usan los chats y Aracodito.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Proveedor</label>
                            <select v-model="valores['IA-00002']" class="form-select w-full">
                                <option v-for="option in providerOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ descripcion('IA-00002') }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Modelo de OpenAI</label>
                            <input v-model="valores['IA-00005']" type="text" class="form-input w-full" placeholder="gpt-4.1-mini" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ descripcion('IA-00005') }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Modelo de Gemini</label>
                            <input v-model="valores['IA-00006']" type="text" class="form-input w-full" placeholder="gemini-2.5-flash" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ descripcion('IA-00006') }}</p>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 class="text-lg font-semibold">Límites y respuestas</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Un valor 0 en presupuesto o límite diario significa «sin límite».
                        </p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Presupuesto mensual (USD)</label>
                            <input v-model="valores['IA-00007']" type="number" min="0" step="0.01" class="form-input w-full" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ descripcion('IA-00007') }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Consultas por usuario y día</label>
                            <input v-model="valores['IA-00008']" type="number" min="0" step="1" class="form-input w-full" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ descripcion('IA-00008') }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Máximo de tokens de salida</label>
                            <input v-model="valores['IA-00010']" type="number" min="1" max="32000" step="1" class="form-input w-full" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ descripcion('IA-00010') }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Temperatura (0 a 2)</label>
                            <input v-model="valores['IA-00011']" type="number" min="0" max="2" step="0.1" class="form-input w-full" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ descripcion('IA-00011') }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Aviso al % del presupuesto</label>
                            <input v-model="valores['IA-00013']" type="number" min="0" max="100" step="5" class="form-input w-full" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ descripcion('IA-00013') }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Corte al superar el presupuesto</label>
                            <label class="mt-2 flex items-center gap-2 text-sm">
                                <input
                                    v-model="valores['IA-00014']"
                                    type="checkbox"
                                    true-value="1"
                                    false-value="0"
                                    class="form-checkbox"
                                />
                                Cortar las consultas al llegar al 100%
                            </label>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ descripcion('IA-00014') }}</p>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 p-5 dark:border-gray-700">
                        <label class="flex items-start gap-3 text-sm">
                            <input
                                v-model="valores['IA-00009']"
                                type="checkbox"
                                true-value="1"
                                false-value="0"
                                class="form-checkbox mt-0.5"
                            />
                            <span>
                                Guardar el texto de las consultas para auditoría
                                <span class="block text-xs text-gray-500 dark:text-gray-400">
                                    {{ descripcion('IA-00009') }}. Aracodito nunca guarda datos de pacientes: la pregunta se enmascara antes de salir del sistema.
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="panel">
                    <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 class="text-lg font-semibold">Credenciales y agente de OpenAI</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Las claves nunca vuelven al navegador: si el campo queda vacío se conserva la guardada.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">API Key de OpenAI</label>
                            <input
                                v-model="valores['IA-00001']"
                                type="password"
                                autocomplete="new-password"
                                class="form-input w-full"
                                :placeholder="tieneValor('IA-00001') ? '•••••••• (guardada)' : 'sk-...'"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ descripcion('IA-00001') }}
                                <span v-if="esSecreto('IA-00001') && tieneValor('IA-00001')" class="text-success">· Hay una clave guardada</span>
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">API Key de Gemini</label>
                            <input
                                v-model="valores['IA-00012']"
                                type="password"
                                autocomplete="new-password"
                                class="form-input w-full"
                                :placeholder="tieneValor('IA-00012') ? '•••••••• (guardada)' : 'AIza...'"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ descripcion('IA-00012') }}
                                <span v-if="esSecreto('IA-00012') && tieneValor('IA-00012')" class="text-success">· Hay una clave guardada</span>
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">ID del agente de OpenAI</label>
                            <input v-model="valores['IA-00003']" type="text" class="form-input w-full" placeholder="agent_..." />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ descripcion('IA-00003') }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Espera máxima del agente (minutos)</label>
                            <input v-model="valores['IA-00004']" type="number" min="1" max="30" step="1" class="form-input w-full" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ descripcion('IA-00004') }}</p>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex flex-col gap-2 border-b border-gray-200 p-5 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold">Límites por rol</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Un 0 no agrega límite propio: el rol usa el límite diario y el presupuesto globales.
                                El presupuesto mensual por rol se aplica al gasto de cada usuario de ese rol.
                            </p>
                        </div>
                        <button type="button" class="btn btn-primary shrink-0" :disabled="guardandoRoles" @click="guardarRoles">
                            <span v-if="guardandoRoles" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            {{ guardandoRoles ? 'Guardando...' : 'Guardar límites' }}
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table-hover w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                                    <th class="p-4 font-semibold">Rol</th>
                                    <th class="p-4 font-semibold">Consultas por día</th>
                                    <th class="p-4 font-semibold">Presupuesto mensual (USD)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="role in limitesRoles" :key="role.name" class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="p-4 font-medium">{{ role.name }}</td>
                                    <td class="p-4">
                                        <input v-model.number="role.daily_limit" type="number" min="0" step="1" class="form-input w-40" />
                                    </td>
                                    <td class="p-4">
                                        <input v-model.number="role.monthly_budget_usd" type="number" min="0" step="0.01" class="form-input w-40" />
                                    </td>
                                </tr>
                                <tr v-if="!limitesRoles.length">
                                    <td colspan="3" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                        No hay roles registrados en el sistema.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="esAdmin" class="panel">
                    <div class="flex flex-col gap-2 border-b border-gray-200 p-5 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold">Acceso a Aracodito IA</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Solo el rol admin ve el interruptor de IA en su perfil. Los roles marcados usan
                                Aracodito IA sin interruptor propio; al desmarcarlos pierden el acceso de inmediato.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="btn btn-primary shrink-0"
                            :disabled="guardandoRolesIa"
                            @click="guardarRolesIa"
                        >
                            <span
                                v-if="guardandoRolesIa"
                                class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                            ></span>
                            {{ guardandoRolesIa ? 'Guardando...' : 'Guardar acceso' }}
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-2 p-5 sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="role in rolesIa"
                            :key="role.name"
                            class="flex items-center gap-2 text-sm"
                        >
                            <input
                                v-model="role.aracodito_ia"
                                type="checkbox"
                                class="form-checkbox"
                                :disabled="role.name === 'admin'"
                            />
                            <span>{{ role.name }}</span>
                            <span v-if="role.name === 'admin'" class="text-xs text-gray-500 dark:text-gray-400">
                                (siempre)
                            </span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary" :disabled="guardando">
                        <span v-if="guardando" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        {{ guardando ? 'Guardando...' : 'Guardar configuración' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
