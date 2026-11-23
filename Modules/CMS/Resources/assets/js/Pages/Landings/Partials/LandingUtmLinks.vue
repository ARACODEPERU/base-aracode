<script setup>
    import { ref, reactive, computed } from 'vue';
    import axios from 'axios';
    import FlatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from 'flatpickr/dist/l10n/es.js';
    import HelpTip from './HelpTip.vue';

    const props = defineProps({
        landing: { type: Object, default: () => ({}) },
        utmStats: { type: Object, default: () => ({ subscribers: [], social_detail: [], referrer_detail: [] }) },
        // Enlace publico del landing; lo envia el servidor desde OneFreeCourseController.
        publicUrl: { type: String, default: '' },
    });

    const menuId = computed(() => props.landing?.menu_id || '01');
    const slug = computed(() => (props.landing?.slug || '').trim());

    // Enlace público real del landing: la ruta /landing/{menu_id}, no el
    // slug. Es el enlace que se comparte y la base de los enlaces UTM.
    const landingPublicUrl = computed(() => {
        if (props.publicUrl) {
            return props.publicUrl;
        }

        // Respaldo por si la vista se renderiza sin el prop del servidor.
        return typeof route === 'function' ? route('cms_free_course_landing', menuId.value) : '';
    });

    const suggestedBaseUrl = computed(() => landingPublicUrl.value);
    const defaultCampaign = computed(() => `landing_${slug.value.replace(/-/g, '_') || 'curso_gratis'}`);

    // Si la URL base guardada no es la del landing (configuraciones viejas
    // armadas con el slug), se avisa y se ofrece corregirla en un clic.
    const sinBarraFinal = (url) => (url || '').replace(/\/+$/, '');
    const baseUrlDesactualizada = computed(
        () => sinBarraFinal(config.base_url) !== '' && sinBarraFinal(config.base_url) !== sinBarraFinal(landingPublicUrl.value)
    );

    // Canales alineados con el clasificador de trafico (traffic_source).
    const baseChannels = [
        // Facebook
        { id: 'facebook_reel', label: 'Facebook Reel', icon: 'fa-brands fa-facebook', color: '#1877F2', utm_source: 'facebook', utm_medium: 'reel', origen: 'Social' },
        { id: 'facebook_estado', label: 'Facebook Estado', icon: 'fa-brands fa-facebook', color: '#1877F2', utm_source: 'facebook', utm_medium: 'estado', origen: 'Social' },
        { id: 'facebook_historia', label: 'Facebook Historia', icon: 'fa-brands fa-facebook', color: '#1877F2', utm_source: 'facebook', utm_medium: 'historia', origen: 'Social' },
        { id: 'facebook_live', label: 'Facebook Live', icon: 'fa-brands fa-facebook', color: '#1877F2', utm_source: 'facebook', utm_medium: 'live', origen: 'Social' },
        { id: 'facebook_post', label: 'Facebook Post', icon: 'fa-brands fa-facebook', color: '#1877F2', utm_source: 'facebook', utm_medium: 'post', origen: 'Social' },
        // YouTube
        { id: 'youtube_shorts', label: 'YouTube Shorts', icon: 'fa-brands fa-youtube', color: '#FF0000', utm_source: 'youtube', utm_medium: 'shorts', origen: 'Social' },
        { id: 'youtube_video', label: 'YouTube Video', icon: 'fa-brands fa-youtube', color: '#FF0000', utm_source: 'youtube', utm_medium: 'video', origen: 'Social' },
        { id: 'youtube_live', label: 'YouTube Live', icon: 'fa-brands fa-youtube', color: '#FF0000', utm_source: 'youtube', utm_medium: 'live', origen: 'Social' },
        // Instagram
        { id: 'instagram_reel', label: 'Instagram Reel', icon: 'fa-brands fa-instagram', color: '#E4405F', utm_source: 'instagram', utm_medium: 'reel', origen: 'Social' },
        { id: 'instagram_historia', label: 'Instagram Historia', icon: 'fa-brands fa-instagram', color: '#E4405F', utm_source: 'instagram', utm_medium: 'historia', origen: 'Social' },
        { id: 'instagram_post', label: 'Instagram Post', icon: 'fa-brands fa-instagram', color: '#E4405F', utm_source: 'instagram', utm_medium: 'post', origen: 'Social' },
        { id: 'instagram_live', label: 'Instagram Live', icon: 'fa-brands fa-instagram', color: '#E4405F', utm_source: 'instagram', utm_medium: 'live', origen: 'Social' },
        // X (Twitter)
        { id: 'x_post', label: 'X (Twitter) Post', icon: 'fa-brands fa-x-twitter', color: '#000000', utm_source: 'twitter', utm_medium: 'post', origen: 'Social' },
        { id: 'x_live', label: 'X (Twitter) Live', icon: 'fa-brands fa-x-twitter', color: '#000000', utm_source: 'twitter', utm_medium: 'live', origen: 'Social' },
        // Threads
        { id: 'threads_post', label: 'Threads Post', icon: 'fa-brands fa-threads', color: '#000000', utm_source: 'threads', utm_medium: 'post', origen: 'Social' },
        // LinkedIn
        { id: 'linkedin_post', label: 'LinkedIn Post', icon: 'fa-brands fa-linkedin', color: '#0A66C2', utm_source: 'linkedin', utm_medium: 'post', origen: 'Social' },
        { id: 'linkedin_video', label: 'LinkedIn Video', icon: 'fa-brands fa-linkedin', color: '#0A66C2', utm_source: 'linkedin', utm_medium: 'video', origen: 'Social' },
        { id: 'linkedin_live', label: 'LinkedIn Live', icon: 'fa-brands fa-linkedin', color: '#0A66C2', utm_source: 'linkedin', utm_medium: 'live', origen: 'Social' },
        // WhatsApp
        { id: 'whatsapp', label: 'WhatsApp', icon: 'fa-brands fa-whatsapp', color: '#25D366', utm_source: 'whatsapp', utm_medium: 'whatsapp', origen: 'WhatsApp' },
        { id: 'whatsapp_canal', label: 'WhatsApp Canal', icon: 'fa-brands fa-whatsapp', color: '#25D366', utm_source: 'whatsapp', utm_medium: 'canal', origen: 'WhatsApp' },
        { id: 'whatsapp_estado', label: 'WhatsApp Estado', icon: 'fa-brands fa-whatsapp', color: '#25D366', utm_source: 'whatsapp', utm_medium: 'estado', origen: 'WhatsApp' },
        { id: 'whatsapp_mensaje', label: 'WhatsApp Mensaje', icon: 'fa-brands fa-whatsapp', color: '#25D366', utm_source: 'whatsapp', utm_medium: 'mensaje', origen: 'WhatsApp' },
        // TikTok
        { id: 'tiktok_video', label: 'TikTok Video', icon: 'fa-brands fa-tiktok', color: '#000000', utm_source: 'tiktok', utm_medium: 'video', origen: 'Social' },
        { id: 'tiktok_live', label: 'TikTok Live', icon: 'fa-brands fa-tiktok', color: '#000000', utm_source: 'tiktok', utm_medium: 'live', origen: 'Social' },
        // Spotify
        { id: 'spotify_bio', label: 'Spotify Bio', icon: 'fa-brands fa-spotify', color: '#1DB954', utm_source: 'spotify', utm_medium: 'social', origen: 'Social' },
        { id: 'spotify_playlist', label: 'Spotify Playlist', icon: 'fa-brands fa-spotify', color: '#1DB954', utm_source: 'spotify', utm_medium: 'referral', origen: 'Social' },
        { id: 'spotify_ads', label: 'Spotify Ads', icon: 'fa-brands fa-spotify', color: '#1DB954', utm_source: 'spotify', utm_medium: 'cpc', origen: 'Spotify' },
        // Email
        { id: 'email_marketing', label: 'Email Marketing', icon: 'fa-solid fa-envelope', color: '#EA4335', utm_source: 'email', utm_medium: 'email', origen: 'Email' },
    ];

    // ---------- Configuracion persistida (utm_config en cms_landings) ----------
    const config = reactive({ campaign: '', base_url: '', channels: {} });

    const loadConfig = () => {
        const saved = props.landing?.utm_config || {};
        config.campaign = saved.campaign || defaultCampaign.value;
        config.base_url = saved.base_url || suggestedBaseUrl.value;

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

    const landingUrl = computed(() => (config.base_url || suggestedBaseUrl.value || '').replace(/\/+$/, ''));

    // Vuelve a dejar la URL base en el enlace público del landing.
    const usarEnlaceDelLanding = () => {
        config.base_url = landingPublicUrl.value;
        saveConfig(true).then(() => {
            window.alertaSistema(
                'Enlace actualizado',
                'La URL base ahora es el enlace del landing y los enlaces UTM apuntan ahí.',
                'success',
                { duration: 2200 }
            );
        });
    };

    const savingConfig = ref(false);
    const saveConfig = (silent = false) => {
        savingConfig.value = true;

        return axios.put(route('cms_landing_course_free_update_utm_config', menuId.value), {
            campaign: (config.campaign || '').trim() || defaultCampaign.value,
            base_url: landingUrl.value,
            channels: baseChannels.map((ch) => ({
                id: ch.id,
                term: config.channels[ch.id]?.term || '',
                content: config.channels[ch.id]?.content || '',
                active: config.channels[ch.id]?.active !== false,
            })),
        })
            .then(() => {
                if (!silent) {
                    window.alertaSistema('Guardado', 'Configuración UTM guardada.', 'success', { duration: 1500 });
                }
                loadStats();
            })
            .catch(() => {
                if (!silent) {
                    window.alertaSistema('Error', 'No se pudo guardar la configuración.', 'error');
                }
            })
            .finally(() => { savingConfig.value = false; });
    };

    const buildUrl = (channel) => {
        if (channel.active === false) return null;

        const params = new URLSearchParams({
            utm_source: channel.utm_source,
            utm_medium: channel.utm_medium,
            utm_campaign: (config.campaign || '').trim() || defaultCampaign.value,
        });

        if (channel.term) params.set('utm_term', channel.term);
        if (channel.content) params.set('utm_content', channel.content);

        return `${landingUrl.value}?${params.toString()}`;
    };

    // ---------- Mini-dashboard de atribucion (leads del formulario) ----------
    const trafficIcons = {
        facebook_ads: 'fa-brands fa-facebook',
        google_ads: 'fa-brands fa-google',
        cpc: 'fa-solid fa-dollar-sign',
        social: 'fa-solid fa-share-nodes',
        organic: 'fa-solid fa-magnifying-glass',
        email: 'fa-solid fa-envelope',
        referrer: 'fa-solid fa-arrow-up-right-from-square',
    };

    const originRows = [
        { key: 'facebook_ads', label: 'Facebook Ads' },
        { key: 'google_ads', label: 'Google Ads' },
        { key: 'cpc', label: 'CPC' },
        { key: 'social', label: 'Social' },
        { key: 'organic', label: 'Orgánico' },
        { key: 'email', label: 'Email' },
        { key: 'referrer', label: 'Otra página' },
    ];

    const stats = ref(props.utmStats || { subscribers: [], social_detail: [], referrer_detail: [] });
    const loadingStats = ref(false);
    const dates = ref(null);

    const configFlatPickr = {
        dateFormat: 'Y-m-d',
        mode: 'range',
        locale: Spanish,
    };

    const clearDates = () => {
        dates.value = null;
        loadStats();
    };

    const countFor = (key) =>
        (stats.value.subscribers || [])
            .filter((s) => s.traffic_source === key)
            .reduce((acc, s) => acc + Number(s.total || 0), 0);

    const totalLeads = computed(() =>
        (stats.value.subscribers || []).reduce((acc, s) => acc + Number(s.total || 0), 0)
    );

    const knownKeys = originRows.map((r) => r.key);
    const countOthers = () =>
        totalLeads.value - knownKeys.reduce((acc, k) => acc + countFor(k), 0);

    const channelByKey = Object.fromEntries(baseChannels.map((c) => [`${c.utm_source}_${c.utm_medium}`, c]));
    // Alias de datos historicos (antes WhatsApp usaba utm_medium=social).
    channelByKey['whatsapp_social'] = channelByKey['whatsapp_whatsapp'] || baseChannels.find((c) => c.id === 'whatsapp');

    const capitalize = (s) => (s ? s.charAt(0).toUpperCase() + s.slice(1) : '');

    const channelMeta = (key) => {
        const ch = channelByKey[key];
        if (ch) return { label: ch.label, icon: ch.icon, color: ch.color };

        const [s, m] = key.split('_');

        return {
            label: [s, m].filter(Boolean).map(capitalize).join(' / ') || 'Social',
            icon: 'fa-solid fa-share-nodes',
            color: '#6B7280',
        };
    };

    const socialChannels = computed(() =>
        (stats.value.social_detail || [])
            .filter((r) => Number(r.total || 0) > 0)
            .map((r) => {
                const key = `${r.utm_source || ''}_${r.utm_medium || ''}`;

                return { key, total: Number(r.total || 0), ...channelMeta(key) };
            })
            .sort((a, b) => b.total - a.total)
    );

    const referrers = computed(() =>
        (stats.value.referrer_detail || [])
            .map((r) => ({ host: r.host || 'Desconocido', total: Number(r.total || 0) }))
            .filter((r) => r.total > 0)
            .sort((a, b) => b.total - a.total)
    );

    const expandedOrigin = ref(null);
    const referrerLimit = ref(5);
    const visibleReferrers = computed(() => referrers.value.slice(0, referrerLimit.value));

    const hasBreakdown = (key) =>
        (key === 'social' && socialChannels.value.length > 0) ||
        (key === 'referrer' && referrers.value.length > 0);

    const toggleOrigin = (key) => {
        if (!hasBreakdown(key)) return;

        expandedOrigin.value = expandedOrigin.value === key ? null : key;

        if (expandedOrigin.value === 'referrer') {
            referrerLimit.value = 5;
        }
    };

    const showMoreReferrers = () => { referrerLimit.value = referrers.value.length; };

    const loadStats = () => {
        loadingStats.value = true;

        axios.get(route('cms_landing_course_free_utm_stats', menuId.value), {
            params: { dates: dates.value || undefined },
        })
            .then((res) => { stats.value = res.data; })
            .catch(() => {})
            .finally(() => { loadingStats.value = false; });
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
            window.alertaSistema('Canal inactivo', 'Activa el canal para generar el enlace.', 'warning');
            return;
        }

        const ok = await copyText(url);

        if (ok) {
            window.alertaSistema('Copiado', 'El enlace se copió al portapapeles.', 'success', { duration: 1500 });
        } else {
            window.alertaSistema('Error', 'No se pudo copiar el enlace.', 'error');
        }
    };

    const copyAllUrls = async () => {
        const activeChannels = channels.value.filter((ch) => ch.active !== false);

        if (activeChannels.length === 0) {
            window.alertaSistema('Sin canales activos', 'Activa al menos un canal.', 'warning');
            return;
        }

        const lines = ['Canal\tOrigen\tURL'];
        activeChannels.forEach((ch) => lines.push(`${ch.label}\t${ch.origen}\t${buildUrl(ch)}`));

        const ok = await copyText(lines.join('\n'));

        if (ok) {
            window.alertaSistema('Todas las URLs copiadas', 'Formato de tabla (canal / origen / url), pega directo en Excel.', 'success', { duration: 2500 });
        } else {
            window.alertaSistema('Error', 'No se pudieron copiar los enlaces.', 'error');
        }
    };
</script>

<template>
    <div>
        <!-- Enlace público del landing: el que se comparte y la base de los UTM -->
        <div class="mb-6 p-4 rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        Enlace del landing
                        <HelpTip text="Es el enlace público que se comparte. Cada enlace UTM de abajo es este mismo enlace con los parámetros de su canal." />
                    </h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Compártelo tal cual, o usa un enlace UTM por canal para saber de dónde llegó cada persona.
                    </p>
                    <code class="mt-2 block text-sm font-mono text-blue-700 dark:text-blue-300 break-all">{{ landingPublicUrl }}</code>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="copyUrl(landingPublicUrl)" class="btn btn-primary text-xs uppercase">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Copiar enlace
                    </button>
                    <a :href="landingPublicUrl" target="_blank" rel="noopener" class="btn btn-outline-primary text-xs uppercase">Abrir</a>
                </div>
            </div>

            <p v-if="baseUrlDesactualizada" class="mt-3 text-xs text-amber-700 dark:text-amber-400 flex flex-wrap items-center gap-1">
                <i class="fa-solid fa-triangle-exclamation"></i>
                La URL base guardada no es el enlace del landing.
                <button type="button" @click="usarEnlaceDelLanding" class="underline font-semibold">Usar el enlace del landing</button>
            </p>
        </div>

        <!-- RESUMEN DE LEADS -->
        <div class="mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Leads del formulario por canal
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(personas que enviaron el formulario)</span>
                    <HelpTip
                        text="Cuenta los envíos del formulario que llegaron con la campaña del landing o desde su url. Se pueden filtrar por fechas."
                    />
                </h3>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <FlatPickr v-model="dates" :config="configFlatPickr" class="form-input w-56" placeholder="Rango de fechas" @on-change="loadStats" />
                        <button v-if="dates" @click="clearDates" type="button" class="absolute right-3 top-2.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none" aria-label="Limpiar fechas">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <button @click="loadStats" :disabled="loadingStats" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors">
                        <svg v-if="loadingStats" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Actualizar
                    </button>
                </div>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Envíos atribuidos a la campaña <code class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">{{ config.campaign || defaultCampaign }}</code> y a este landing.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                <div class="p-4 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total leads</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ totalLeads }}</div>
                </div>
                <div class="p-4 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Campaña</div>
                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white break-all">{{ stats.campaign || config.campaign }}</div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Origen</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Leads</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <template v-for="row in originRows" :key="row.key">
                            <tr
                                @click="toggleOrigin(row.key)"
                                :class="[
                                    hasBreakdown(row.key) ? 'cursor-pointer' : '',
                                    expandedOrigin === row.key ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/40',
                                    'transition-colors'
                                ]"
                            >
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                    <i :class="trafficIcons[row.key] || 'fa-solid fa-link'" class="w-4 text-gray-400 mr-2"></i>
                                    {{ row.label }}
                                    <svg v-if="hasBreakdown(row.key)" class="w-3.5 h-3.5 inline-block ml-1 text-gray-400 transition-transform duration-200" :class="expandedOrigin === row.key ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </td>
                                <td class="px-4 py-2 text-right font-medium text-gray-900 dark:text-white" :class="{ 'text-blue-600 dark:text-blue-400': countFor(row.key) > 0 }">
                                    {{ countFor(row.key) }}
                                </td>
                            </tr>

                            <!-- Desglose del trafico Social por canal -->
                            <template v-if="row.key === 'social' && expandedOrigin === 'social'">
                                <tr v-for="ch in socialChannels" :key="'soc-' + ch.key" class="bg-blue-50/60 dark:bg-blue-900/10">
                                    <td class="px-4 py-1.5 pl-9 text-sm text-gray-600 dark:text-gray-300">
                                        <i :class="ch.icon" :style="{ color: ch.color }" class="w-4 mr-2"></i>
                                        {{ ch.label }}
                                    </td>
                                    <td class="px-4 py-1.5 text-right text-sm font-medium text-gray-900 dark:text-white" :class="{ 'text-blue-600 dark:text-blue-400': ch.total > 0 }">{{ ch.total }}</td>
                                </tr>
                            </template>

                            <!-- Desglose de Otra pagina: top 5 + Ver mas -->
                            <template v-if="row.key === 'referrer' && expandedOrigin === 'referrer'">
                                <tr v-for="ref in visibleReferrers" :key="'ref-' + ref.host" class="bg-blue-50/60 dark:bg-blue-900/10">
                                    <td class="px-4 py-1.5 pl-9 text-sm text-gray-600 dark:text-gray-300">
                                        <i class="fa-solid fa-arrow-up-right-from-square w-4 text-gray-400 mr-2"></i>
                                        {{ ref.host }}
                                    </td>
                                    <td class="px-4 py-1.5 text-right text-sm font-medium text-gray-900 dark:text-white">{{ ref.total }}</td>
                                </tr>
                                <tr v-if="referrers.length > referrerLimit">
                                    <td colspan="2" class="px-4 py-2 text-center">
                                        <button @click.stop="showMoreReferrers" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                            Ver más... ({{ referrers.length - referrerLimit }} más)
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </template>
                        <tr v-if="countOthers() > 0" class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                <i class="fa-solid fa-ellipsis w-4 text-gray-400 mr-2"></i>
                                Otros / Desconocido
                            </td>
                            <td class="px-4 py-2 text-right font-medium text-gray-900 dark:text-white">{{ countOthers() }}</td>
                        </tr>
                        <tr class="bg-gray-50 dark:bg-gray-800/60">
                            <td class="px-4 py-2 font-semibold text-gray-700 dark:text-gray-300">Total</td>
                            <td class="px-4 py-2 text-right font-bold text-gray-900 dark:text-white">{{ totalLeads }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CONFIGURACION DE CAMPANA -->
        <div class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        Nombre de campaña (utm_campaign)
                        <HelpTip text="Nombre interno de la campaña. Agrupa todas las visitas y leads de este landing; se usa en todos los enlaces." />
                    </label>
                    <input v-model="config.campaign" type="text" class="form-input" placeholder="landing_curso_gratis" />
                </div>
                <div class="flex-[2] min-w-[280px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        URL base del landing
                        <HelpTip text="Dirección pública del landing. Verifícala antes de copiar los enlaces: se usa como base de todos los canales." />
                    </label>
                    <input v-model="config.base_url" type="text" class="form-input" :placeholder="suggestedBaseUrl" />
                </div>
                <button @click="saveConfig()" :disabled="savingConfig" :class="{ 'opacity-25': savingConfig }" class="btn btn-primary text-xs uppercase">
                    <svg v-if="savingConfig" class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Guardar configuracion
                </button>
            </div>
            <p class="mt-2 text-xs text-gray-400">La campaña se usa en todos los enlaces. Verifica la URL base antes de copiar los links.</p>
        </div>

        <!-- ENLACES -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Enlaces UTM por canal
                    <HelpTip text="Cada canal genera una url con utm_source, utm_medium y utm_campaign. Cópiala y pégala en la publicación de ese canal." />
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    URLs listas para usar en tus campañas de marketing. Copia y pégala en la configuración de cada canal.
                </p>
            </div>
            <button @click="copyAllUrls" class="btn btn-primary text-xs uppercase" title="Copia canal, origen y url de los canales activos, listo para pegar en Excel">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Copiar todas
            </button>
        </div>

        <!-- URL base -->
        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">URL base del landing:</span>
            <p class="text-sm font-mono text-gray-700 dark:text-gray-300 mt-1 break-all">{{ landingUrl }}</p>
        </div>

        <!-- Lista de enlaces -->
        <div class="space-y-2">
            <div
                v-for="(channel, index) in channels"
                :key="channel.id"
                class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden transition-all duration-200"
                :class="expandedIndex === index ? 'ring-2 ring-blue-500' : 'hover:border-gray-300 dark:hover:border-gray-600'"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 cursor-pointer bg-white dark:bg-gray-800" @click="toggleExpand(index)">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm" :style="{ backgroundColor: channel.color }">
                            <i :class="channel.icon"></i>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white">{{ channel.label }}</span>
                            <span class="ml-2 text-xs font-medium px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                <i class="fa-solid fa-tag mr-0.5"></i>{{ channel.origen }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer" @click.stop title="Activar/desactivar canal">
                            <input type="checkbox" v-model="config.channels[channel.id].active" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                        <button @click.stop="copyUrl(buildUrl(channel))" :class="{ 'opacity-40 pointer-events-none': config.channels[channel.id].active === false }" class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Copiar
                        </button>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="expandedIndex === index ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>

                <!-- Detalle expandido -->
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
                            <span class="px-2 py-1 rounded bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 font-mono">utm_campaign={{ config.campaign || defaultCampaign }}</span>
                            <span v-if="channel.term" class="px-2 py-1 rounded bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300 font-mono">utm_term={{ channel.term }}</span>
                            <span v-if="channel.content" class="px-2 py-1 rounded bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300 font-mono">utm_content={{ channel.content }}</span>
                        </div>
                        <div class="p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                            <code class="text-xs text-gray-600 dark:text-gray-300 break-all">{{ buildUrl(channel) }}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
