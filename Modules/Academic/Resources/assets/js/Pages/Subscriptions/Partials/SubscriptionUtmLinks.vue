<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import FlatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { Spanish } from 'flatpickr/dist/l10n/es.js';

const props = defineProps({
    utmConfig: { type: Object, default: () => ({ campaign: '', channels: [] }) },
    utmStats: { type: Object, default: () => ({ totals: {}, sources: [], channels: [], plans: [], sales: [] }) },
    landingUrl: { type: String, default: '' },
    defaultCampaign: { type: String, default: 'suscripcion_academy' },
});

// Sección desplegable: cerrada por defecto para no alargar el listado.
const open = ref(false);

// Canales alineados con el clasificador de tráfico (traffic_source).
const baseChannels = [
    { id: 'facebook_reel', label: 'Facebook Reel', icon: 'fa-brands fa-facebook', color: '#1877F2', utm_source: 'facebook', utm_medium: 'reel' },
    { id: 'facebook_estado', label: 'Facebook Estado', icon: 'fa-brands fa-facebook', color: '#1877F2', utm_source: 'facebook', utm_medium: 'estado' },
    { id: 'facebook_historia', label: 'Facebook Historia', icon: 'fa-brands fa-facebook', color: '#1877F2', utm_source: 'facebook', utm_medium: 'historia' },
    { id: 'facebook_live', label: 'Facebook Live', icon: 'fa-brands fa-facebook', color: '#1877F2', utm_source: 'facebook', utm_medium: 'live' },
    { id: 'facebook_post', label: 'Facebook Post', icon: 'fa-brands fa-facebook', color: '#1877F2', utm_source: 'facebook', utm_medium: 'post' },
    { id: 'instagram_reel', label: 'Instagram Reel', icon: 'fa-brands fa-instagram', color: '#E4405F', utm_source: 'instagram', utm_medium: 'reel' },
    { id: 'instagram_historia', label: 'Instagram Historia', icon: 'fa-brands fa-instagram', color: '#E4405F', utm_source: 'instagram', utm_medium: 'historia' },
    { id: 'instagram_post', label: 'Instagram Post', icon: 'fa-brands fa-instagram', color: '#E4405F', utm_source: 'instagram', utm_medium: 'post' },
    { id: 'instagram_live', label: 'Instagram Live', icon: 'fa-brands fa-instagram', color: '#E4405F', utm_source: 'instagram', utm_medium: 'live' },
    { id: 'tiktok_video', label: 'TikTok Video', icon: 'fa-brands fa-tiktok', color: '#000000', utm_source: 'tiktok', utm_medium: 'video' },
    { id: 'tiktok_live', label: 'TikTok Live', icon: 'fa-brands fa-tiktok', color: '#000000', utm_source: 'tiktok', utm_medium: 'live' },
    { id: 'youtube_shorts', label: 'YouTube Shorts', icon: 'fa-brands fa-youtube', color: '#FF0000', utm_source: 'youtube', utm_medium: 'shorts' },
    { id: 'youtube_video', label: 'YouTube Video', icon: 'fa-brands fa-youtube', color: '#FF0000', utm_source: 'youtube', utm_medium: 'video' },
    { id: 'youtube_live', label: 'YouTube Live', icon: 'fa-brands fa-youtube', color: '#FF0000', utm_source: 'youtube', utm_medium: 'live' },
    { id: 'linkedin_post', label: 'LinkedIn Post', icon: 'fa-brands fa-linkedin', color: '#0A66C2', utm_source: 'linkedin', utm_medium: 'post' },
    { id: 'linkedin_video', label: 'LinkedIn Video', icon: 'fa-brands fa-linkedin', color: '#0A66C2', utm_source: 'linkedin', utm_medium: 'video' },
    { id: 'x_post', label: 'X (Twitter) Post', icon: 'fa-brands fa-x-twitter', color: '#000000', utm_source: 'twitter', utm_medium: 'post' },
    { id: 'threads_post', label: 'Threads Post', icon: 'fa-brands fa-threads', color: '#000000', utm_source: 'threads', utm_medium: 'post' },
    { id: 'whatsapp', label: 'WhatsApp', icon: 'fa-brands fa-whatsapp', color: '#25D366', utm_source: 'whatsapp', utm_medium: 'whatsapp' },
    { id: 'whatsapp_canal', label: 'WhatsApp Canal', icon: 'fa-brands fa-whatsapp', color: '#25D366', utm_source: 'whatsapp', utm_medium: 'canal' },
    { id: 'whatsapp_estado', label: 'WhatsApp Estado', icon: 'fa-brands fa-whatsapp', color: '#25D366', utm_source: 'whatsapp', utm_medium: 'estado' },
    { id: 'whatsapp_mensaje', label: 'WhatsApp Mensaje', icon: 'fa-brands fa-whatsapp', color: '#25D366', utm_source: 'whatsapp', utm_medium: 'mensaje' },
    { id: 'spotify_bio', label: 'Spotify Bio', icon: 'fa-brands fa-spotify', color: '#1DB954', utm_source: 'spotify', utm_medium: 'social' },
    { id: 'spotify_ads', label: 'Spotify Ads', icon: 'fa-brands fa-spotify', color: '#1DB954', utm_source: 'spotify', utm_medium: 'cpc' },
    { id: 'email_marketing', label: 'Email Marketing', icon: 'fa-solid fa-envelope', color: '#EA4335', utm_source: 'email', utm_medium: 'email' },
];

// ---------- Configuración persistida ----------
const config = reactive({ campaign: '', channels: {} });

const loadConfig = () => {
    const saved = props.utmConfig || {};
    config.campaign = saved.campaign || props.defaultCampaign;
    baseChannels.forEach((ch) => {
        const savedCh = (saved.channels || []).find((c) => c.id === ch.id);
        config.channels[ch.id] = {
            term: savedCh?.term || '',
            content: savedCh?.content || '',
            active: savedCh?.active !== false,
        };
    });
};
loadConfig();

const channels = computed(() =>
    baseChannels.map((ch) => ({
        ...ch,
        term: config.channels[ch.id]?.term || '',
        content: config.channels[ch.id]?.content || '',
        active: config.channels[ch.id]?.active !== false,
    }))
);

const campaign = computed(() => (config.campaign || '').trim() || props.defaultCampaign);

const savingConfig = ref(false);
const saveConfig = () => {
    savingConfig.value = true;
    axios.put(route('aca_subscriptions_utm_config'), {
        campaign: campaign.value,
        channels: baseChannels.map((ch) => ({
            id: ch.id,
            utm_source: ch.utm_source,
            utm_medium: ch.utm_medium,
            term: config.channels[ch.id]?.term || '',
            content: config.channels[ch.id]?.content || '',
            active: config.channels[ch.id]?.active !== false,
        })),
    })
        .then(() => {
            Swal.fire({ icon: 'success', title: 'Guardado', text: 'Configuración UTM guardada.', timer: 1500, showConfirmButton: false });
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar la configuración.' });
        })
        .finally(() => { savingConfig.value = false; });
};

const buildUrl = (channel) => {
    if (!props.landingUrl || channel.active === false) return null;
    const params = new URLSearchParams({
        utm_source: channel.utm_source,
        utm_medium: channel.utm_medium,
        utm_campaign: campaign.value,
    });
    if (channel.term) params.set('utm_term', channel.term);
    if (channel.content) params.set('utm_content', channel.content);
    return `${props.landingUrl}?${params.toString()}`;
};

// ---------- Copiar enlaces ----------
const expandedIndex = ref(null);
const toggleExpand = (index) => {
    expandedIndex.value = expandedIndex.value === index ? null : index;
};

const copyText = async (text) => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
        } else {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }
        return true;
    } catch {
        return false;
    }
};

const copyUrl = async (url) => {
    if (!url) {
        Swal.fire({ icon: 'warning', title: 'Canal inactivo', text: 'Activa el canal para generar el enlace.' });
        return;
    }
    const ok = await copyText(url);
    Swal.fire({ icon: ok ? 'success' : 'error', title: ok ? 'Copiado' : 'Error', text: ok ? '' : 'No se pudo copiar', timer: ok ? 1500 : 2000, showConfirmButton: !ok });
};

const copyAllUrls = async () => {
    const activeChannels = channels.value.filter((ch) => ch.active !== false);
    if (activeChannels.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Sin canales activos', text: 'Activa al menos un canal.' });
        return;
    }
    const lines = ['Canal\tOrigen\tURL'];
    activeChannels.forEach((ch) => lines.push(`${ch.label}\t${ch.utm_source}/${ch.utm_medium}\t${buildUrl(ch)}`));
    const ok = await copyText(lines.join('\n'));
    Swal.fire({ icon: ok ? 'success' : 'error', title: ok ? 'Todas las URLs copiadas' : 'Error', text: ok ? 'Formato de tabla (canal / origen / url), pega directo en Excel.' : 'No se pudieron copiar', timer: ok ? 2500 : 2000, showConfirmButton: !ok });
};

// ---------- Atribución ----------
const stats = ref(props.utmStats || { totals: {}, sources: [], channels: [], plans: [], sales: [] });
const loadingStats = ref(false);
const dates = ref(null);

const configFlatPickr = {
    dateFormat: 'Y-m-d',
    mode: 'range',
    locale: Spanish,
};

const loadStats = () => {
    loadingStats.value = true;
    axios.get(route('aca_subscriptions_utm_stats'), { params: { dates: dates.value || undefined } })
        .then((res) => { stats.value = res.data; })
        .catch(() => {})
        .finally(() => { loadingStats.value = false; });
};

const clearDates = () => {
    dates.value = null;
    loadStats();
};

const sourceMeta = {
    facebook_ads: { label: 'Facebook Ads', icon: 'fa-brands fa-facebook', color: 'text-blue-600' },
    google_ads: { label: 'Google Ads', icon: 'fa-brands fa-google', color: 'text-red-500' },
    cpc: { label: 'CPC', icon: 'fa-solid fa-dollar-sign', color: 'text-amber-600' },
    social: { label: 'Redes sociales', icon: 'fa-solid fa-share-nodes', color: 'text-sky-600' },
    organic: { label: 'Orgánico', icon: 'fa-solid fa-magnifying-glass', color: 'text-gray-500' },
    email: { label: 'Email', icon: 'fa-solid fa-envelope', color: 'text-rose-500' },
    referrer: { label: 'Otra página', icon: 'fa-solid fa-arrow-up-right-from-square', color: 'text-indigo-500' },
    whatsapp: { label: 'WhatsApp', icon: 'fa-brands fa-whatsapp', color: 'text-green-600' },
};

const metaFor = (key) => sourceMeta[key] || { label: key || 'Desconocido', icon: 'fa-solid fa-link', color: 'text-gray-500' };

const money = (value) => `S/ ${Number(value || 0).toLocaleString('es-PE', { maximumFractionDigits: 2 })}`;

const totals = computed(() => stats.value.totals || {});
const mainSource = computed(() => (stats.value.sources || [])[0]);

const channelLabel = (row) => {
    if (!row.utm_source) return 'Sin UTM';
    return row.utm_medium ? `${row.utm_source} / ${row.utm_medium}` : row.utm_source;
};
</script>

<template>
    <div class="panel">
        <!-- Cabecera desplegable -->
        <button
            type="button"
            class="w-full flex items-center justify-between gap-4 text-left"
            @click="open = !open"
            :aria-expanded="open"
        >
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 text-primary">
                    <i class="fa-solid fa-link"></i>
                </span>
                <div>
                    <h5 class="font-semibold text-lg dark:text-white-light">Enlaces UTM y origen de las suscripciones</h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Genera enlaces por red social para la landing pública y revisa de dónde llegó cada persona y qué plan eligió.
                    </p>
                </div>
            </div>
            <span class="flex items-center gap-2 text-sm font-semibold text-gray-500 dark:text-gray-400">
                {{ open ? 'Ocultar' : 'Mostrar' }}
                <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </span>
        </button>

        <div v-show="open" class="mt-6">
            <!-- ATRIBUCIÓN -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h6 class="font-semibold text-base dark:text-white-light">Dónde llegaron y qué plan escogieron</h6>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <FlatPickr v-model="dates" :config="configFlatPickr" class="form-input w-56" placeholder="Rango de fechas" @on-change="loadStats" />
                        <button v-if="dates" @click="clearDates" type="button" class="absolute right-3 top-2.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" aria-label="Limpiar fechas">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <button @click="loadStats" :disabled="loadingStats" class="btn btn-outline-primary text-xs">
                        <i class="fa-solid fa-rotate mr-1" :class="{ 'animate-spin': loadingStats }"></i>
                        Actualizar
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Suscripciones</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ totals.count || 0 }}</div>
                </div>
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Personas</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ totals.people || 0 }}</div>
                </div>
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Ingreso</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ money(totals.amount) }}</div>
                </div>
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Origen principal</div>
                    <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i :class="[metaFor(mainSource?.key).icon, metaFor(mainSource?.key).color]"></i>
                        {{ mainSource ? metaFor(mainSource.key).label : 'Sin datos' }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <!-- Por origen -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">De dónde llegaron</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Compras</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="row in stats.sources" :key="'src-' + row.key">
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                    <i :class="[metaFor(row.key).icon, metaFor(row.key).color]" class="mr-2"></i>{{ metaFor(row.key).label }}
                                </td>
                                <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">{{ row.total }}</td>
                            </tr>
                            <tr v-if="!stats.sources || stats.sources.length === 0">
                                <td colspan="2" class="px-3 py-4 text-center text-gray-400">Sin datos en el periodo.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Por red social -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Red / canal (UTM)</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Compras</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="row in stats.channels" :key="'ch-' + row.key">
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ channelLabel(row) }}</td>
                                <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">{{ row.total }}</td>
                            </tr>
                            <tr v-if="!stats.channels || stats.channels.length === 0">
                                <td colspan="2" class="px-3 py-4 text-center text-gray-400">Sin datos en el periodo.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Por plan -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Plan elegido</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Compras</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Ingreso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="row in stats.plans" :key="'plan-' + row.key">
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ row.key }}</td>
                                <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">{{ row.total }}</td>
                                <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">{{ money(row.amount) }}</td>
                            </tr>
                            <tr v-if="!stats.plans || stats.plans.length === 0">
                                <td colspan="3" class="px-3 py-4 text-center text-gray-400">Sin datos en el periodo.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detalle por persona -->
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 mb-8">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Persona</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Plan</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Llegó desde</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Campaña / contenido</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="row in stats.sales" :key="'sale-' + row.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-3 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ row.date }}</td>
                            <td class="px-3 py-2">
                                <div class="font-semibold text-gray-800 dark:text-gray-200">{{ row.name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ row.email }}</div>
                            </td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ row.plan }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                <i :class="[metaFor(row.traffic_source).icon, metaFor(row.traffic_source).color]" class="mr-1"></i>
                                {{ metaFor(row.traffic_source).label }}
                                <div v-if="row.utm_source" class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ row.utm_source }}<span v-if="row.utm_medium"> / {{ row.utm_medium }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                <div class="text-xs">{{ row.utm_campaign || '—' }}</div>
                                <div v-if="row.utm_content" class="text-xs text-gray-500 dark:text-gray-400">Contenido: {{ row.utm_content }}</div>
                            </td>
                            <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">{{ money(row.amount) }}</td>
                        </tr>
                        <tr v-if="!stats.sales || stats.sales.length === 0">
                            <td colspan="6" class="px-3 py-6 text-center text-gray-400">
                                Aún no hay suscripciones vendidas con datos de origen.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- CONFIGURACIÓN DE CAMPAÑA -->
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Nombre de campaña (utm_campaign)</label>
                        <input v-model="config.campaign" type="text" class="form-input" placeholder="suscripcion_academy" />
                    </div>
                    <button @click="saveConfig" :disabled="savingConfig" :class="{ 'opacity-25': savingConfig }" class="btn btn-primary text-xs uppercase">
                        Guardar configuración
                    </button>
                </div>
                <p class="mt-2 text-xs text-gray-400">Se usa en todos los enlaces. Guarda antes de copiar los links.</p>
            </div>

            <!-- ENLACES -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h6 class="font-semibold text-base dark:text-white-light">Enlaces UTM por red social</h6>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Cada enlace abre la landing pública de suscripciones, que solo muestra los planes activos.
                    </p>
                </div>
                <button @click="copyAllUrls" class="btn btn-primary text-xs uppercase">
                    <i class="fa-solid fa-copy mr-1"></i>
                    Copiar todas
                </button>
            </div>

            <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">URL base de la landing:</span>
                <p class="text-sm font-mono text-gray-700 dark:text-gray-300 mt-1 break-all">{{ landingUrl }}</p>
            </div>

            <div class="space-y-2">
                <div
                    v-for="(channel, index) in channels"
                    :key="channel.id"
                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                    :class="expandedIndex === index ? 'ring-2 ring-primary' : ''"
                >
                    <div class="flex items-center justify-between px-4 py-3 cursor-pointer bg-white dark:bg-gray-800" @click="toggleExpand(index)">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm flex-none" :style="{ backgroundColor: channel.color }">
                                <i :class="channel.icon"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="text-sm font-semibold text-gray-800 dark:text-white">{{ channel.label }}</span>
                                <span class="ml-2 text-xs font-medium px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 font-mono">
                                    {{ channel.utm_source }}/{{ channel.utm_medium }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-none">
                            <label class="relative inline-flex items-center cursor-pointer" @click.stop title="Activar/desactivar canal">
                                <input type="checkbox" v-model="config.channels[channel.id].active" class="sr-only peer" />
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                            <button
                                @click.stop="copyUrl(buildUrl(channel))"
                                :class="{ 'opacity-40 pointer-events-none': config.channels[channel.id].active === false }"
                                class="px-3 py-1.5 text-xs font-medium rounded-md bg-primary/10 text-primary hover:bg-primary/20"
                            >
                                <i class="fa-solid fa-copy mr-1"></i> Copiar
                            </button>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="expandedIndex === index ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <div v-if="expandedIndex === index" class="px-4 pb-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
                        <div class="mt-3 space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">utm_term <span class="font-normal">(palabra clave)</span></label>
                                    <input v-model="config.channels[channel.id].term" type="text" class="form-input" placeholder="Opcional" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">utm_content <span class="font-normal">(anuncio / variante)</span></label>
                                    <input v-model="config.channels[channel.id].content" type="text" class="form-input" placeholder="Opcional" />
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="px-2 py-1 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 font-mono">utm_source={{ channel.utm_source }}</span>
                                <span class="px-2 py-1 rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 font-mono">utm_medium={{ channel.utm_medium }}</span>
                                <span class="px-2 py-1 rounded bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 font-mono">utm_campaign={{ campaign }}</span>
                            </div>
                            <div class="p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                                <code class="text-xs text-gray-600 dark:text-gray-300 break-all">{{ buildUrl(channel) }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
