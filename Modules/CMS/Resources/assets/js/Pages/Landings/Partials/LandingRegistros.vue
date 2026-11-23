<script setup>
    import { computed, ref } from 'vue';
    import axios from 'axios';
    import FlatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from 'flatpickr/dist/l10n/es.js';
    import HelpTip from './HelpTip.vue';

    const props = defineProps({
        landing: { type: Object, default: () => ({}) },
    });

    const menuId = computed(() => props.landing?.menu_id || '01');

    // El panel arranca oculto: el usuario decide cuando ver los registros.
    const visible = ref(false);
    const loading = ref(false);
    const registros = ref([]);
    const total = ref(0);
    const page = ref(1);
    const lastPage = ref(1);
    const campaign = ref('');
    const search = ref('');
    const dates = ref(null);

    const configFlatPickr = {
        dateFormat: 'Y-m-d',
        mode: 'range',
        locale: Spanish,
    };

    const filtros = () => ({
        dates: dates.value || undefined,
        search: search.value || undefined,
    });

    const cargar = (pagina = 1) => {
        loading.value = true;
        page.value = pagina;

        axios.get(route('cms_landing_course_free_registros', menuId.value), {
            params: { ...filtros(), page: pagina },
        })
            .then((res) => {
                registros.value = res.data.data || [];
                total.value = res.data.total || 0;
                lastPage.value = res.data.last_page || 1;
                campaign.value = res.data.campaign || '';
            })
            .catch(() => {
                window.alertaSistema('Error', 'No se pudieron cargar los registros del landing.', 'error');
            })
            .finally(() => { loading.value = false; });
    };

    const alternar = () => {
        visible.value = !visible.value;

        if (visible.value && registros.value.length === 0) {
            cargar(1);
        }
    };

    const limpiarFechas = () => {
        dates.value = null;
        cargar(1);
    };

    const exportar = (formato) => {
        const nombreRuta = formato === 'excel'
            ? 'cms_landing_course_free_registros_excel'
            : 'cms_landing_course_free_registros_pdf';

        const params = new URLSearchParams();

        if (dates.value) params.set('dates', dates.value);
        if (search.value) params.set('search', search.value);

        const base = route(nombreRuta, menuId.value);
        window.open(params.toString() ? `${base}?${params.toString()}` : base, '_blank');

        window.alertaSistema(
            'Exportación iniciada',
            formato === 'excel'
                ? 'El archivo Excel se está descargando con los filtros actuales.'
                : 'El PDF se está descargando con los filtros actuales.',
            'info',
            { duration: 2600 }
        );
    };
</script>

<template>
    <div>
        <!-- Acceso: muestra u oculta la tabla de registros -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Registros del landing
                    <HelpTip text="Personas que enviaron el formulario de este landing. Se cuentan por la campaña UTM configurada o por la url del landing." />
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Datos obtenidos por el formulario, listos para revisar o descargar.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="btn text-xs uppercase"
                    :class="visible ? 'btn-outline-primary' : 'btn-primary'"
                    @click="alternar"
                >
                    <svg v-if="visible" class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                    <svg v-else class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    {{ visible ? 'Ocultar registros' : 'Mostrar registros' }}
                </button>
            </div>
        </div>

        <div v-if="visible">
            <!-- Filtros y exportaciones -->
            <div class="flex flex-wrap items-end gap-3 mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="relative">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Rango de fechas</label>
                    <FlatPickr v-model="dates" :config="configFlatPickr" class="form-input w-56" placeholder="Todas las fechas" @on-change="cargar(1)" />
                    <button v-if="dates" @click="limpiarFechas" type="button" class="absolute right-3 top-[34px] text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" aria-label="Limpiar fechas">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Buscar</label>
                    <input v-model="search" type="text" class="form-input" placeholder="Nombre, email o teléfono" @keyup.enter="cargar(1)" />
                </div>
                <button @click="cargar(1)" :disabled="loading" class="btn btn-primary text-xs uppercase" :class="{ 'opacity-25': loading }">
                    Actualizar
                </button>
                <button @click="exportar('excel')" class="btn btn-outline-primary text-xs uppercase" title="Descarga un Excel con los registros y filtros actuales">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 4h16v16H4z" />
                    </svg>
                    Excel
                </button>
                <button @click="exportar('pdf')" class="btn btn-outline-primary text-xs uppercase" title="Descarga un PDF con los registros y filtros actuales">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9l-6-6H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6M9 17h4" />
                    </svg>
                    PDF
                </button>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 mb-2 text-xs text-gray-500 dark:text-gray-400">
                <span>
                    {{ total }} registro(s) · campaña
                    <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">{{ campaign || 'sin campaña' }}</code>
                </span>
                <span v-if="loading">Cargando...</span>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nombre</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Email</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Teléfono</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Curso(s) gratis</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Temas de interés</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Origen</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">UTM</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="registro in registros" :key="registro.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                            <td class="px-3 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ registro.fecha }}</td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ registro.nombre || '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300 break-all">{{ registro.email }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ registro.telefono || '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ registro.asunto || '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ registro.temas || '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ registro.traffic_source || '—' }}</td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400">
                                <span v-if="registro.utm_source || registro.utm_campaign" class="font-mono text-xs">
                                    {{ registro.utm_source || '—' }}<span v-if="registro.utm_medium"> / {{ registro.utm_medium }}</span>
                                    <span v-if="registro.utm_campaign" class="block text-[11px]">{{ registro.utm_campaign }}</span>
                                </span>
                                <span v-else>—</span>
                            </td>
                        </tr>
                        <tr v-if="!loading && registros.length === 0">
                            <td colspan="8" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                                Todavía no hay registros para este landing con los filtros aplicados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="lastPage > 1" class="flex items-center justify-between mt-3 text-sm">
                <button
                    type="button"
                    class="btn btn-outline-primary btn-sm text-xs"
                    :disabled="page <= 1 || loading"
                    :class="{ 'opacity-25': page <= 1 || loading }"
                    @click="cargar(page - 1)"
                >
                    Anterior
                </button>
                <span class="text-gray-500 dark:text-gray-400">Página {{ page }} de {{ lastPage }}</span>
                <button
                    type="button"
                    class="btn btn-outline-primary btn-sm text-xs"
                    :disabled="page >= lastPage || loading"
                    :class="{ 'opacity-25': page >= lastPage || loading }"
                    @click="cargar(page + 1)"
                >
                    Siguiente
                </button>
            </div>
        </div>
    </div>
</template>
