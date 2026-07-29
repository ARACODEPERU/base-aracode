<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    course: { type: Object, default: () => ({}) },
    landing: { type: Object, default: () => ({}) },
});

const baseUrl = assetUrl;
const landingUrl = computed(() => `${baseUrl}curso/${props.landing.url_slug || ''}`);
const campaignName = computed(() => `curso_${(props.landing.url_slug || '').replace(/-/g, '_')}`);

const utmLinks = computed(() => [
    { channel: 'Facebook CPC', icon: 'fa-brands fa-facebook', color: '#1877F2', utm_source: 'facebook', utm_medium: 'cpc' },
    { channel: 'Facebook Ads', icon: 'fa-brands fa-facebook', color: '#1877F2', utm_source: 'facebook', utm_medium: 'ads' },
    { channel: 'Google Ads', icon: 'fa-brands fa-google', color: '#4285F4', utm_source: 'google', utm_medium: 'cpc', note: 'El gclid se agrega automáticamente al hacer clic en el anuncio.' },
    { channel: 'Google Organic', icon: 'fa-brands fa-google', color: '#34A853', utm_source: 'google', utm_medium: 'organic' },
    { channel: 'Instagram', icon: 'fa-brands fa-instagram', color: '#E4405F', utm_source: 'instagram', utm_medium: 'social' },
    { channel: 'TikTok', icon: 'fa-brands fa-tiktok', color: '#000000', utm_source: 'tiktok', utm_medium: 'social' },
    { channel: 'YouTube', icon: 'fa-brands fa-youtube', color: '#FF0000', utm_source: 'youtube', utm_medium: 'social' },
    { channel: 'LinkedIn', icon: 'fa-brands fa-linkedin', color: '#0A66C2', utm_source: 'linkedin', utm_medium: 'social' },
    { channel: 'Twitter / X', icon: 'fa-brands fa-x-twitter', color: '#000000', utm_source: 'twitter', utm_medium: 'social' },
    { channel: 'WhatsApp', icon: 'fa-brands fa-whatsapp', color: '#25D366', utm_source: 'whatsapp', utm_medium: 'social' },
    { channel: 'Email Marketing', icon: 'fa-solid fa-envelope', color: '#EA4335', utm_source: 'email', utm_medium: 'email' },
]);

const buildUrl = (link) => {
    const params = new URLSearchParams({
        utm_source: link.utm_source,
        utm_medium: link.utm_medium,
        utm_campaign: campaignName.value,
    });
    return `${landingUrl.value}?${params.toString()}`;
};

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
    const ok = await copyText(url);
    Swal.fire({ icon: ok ? 'success' : 'error', title: ok ? 'Copiado' : 'Error', text: ok ? '' : 'No se pudo copiar', timer: ok ? 1500 : 2000, showConfirmButton: !ok });
};

const copyAllUrls = async () => {
    const allText = utmLinks.value.map(link => `${link.channel}: ${buildUrl(link)}`).join('\n');
    const ok = await copyText(allText);
    Swal.fire({ icon: ok ? 'success' : 'error', title: ok ? 'Todas las URLs copiadas' : 'Error', text: ok ? '' : 'No se pudieron copiar', timer: ok ? 2000 : 2000, showConfirmButton: !ok });
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Enlaces UTM por canal</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    URLs listas para usar en tus campañas de marketing. Copia y pégala en la configuración de cada canal.
                </p>
            </div>
            <button @click="copyAllUrls" class="btn btn-primary text-xs uppercase">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Copiar todas
            </button>
        </div>

        <!-- URL base -->
        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">URL base de la landing:</span>
            <p class="text-sm font-mono text-gray-700 dark:text-gray-300 mt-1 break-all">{{ landingUrl }}</p>
        </div>

        <!-- Lista de enlaces -->
        <div class="space-y-2">
            <div
                v-for="(link, index) in utmLinks"
                :key="index"
                class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden transition-all duration-200"
                :class="expandedIndex === index ? 'ring-2 ring-blue-500' : 'hover:border-gray-300 dark:hover:border-gray-600'"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between px-4 py-3 cursor-pointer bg-white dark:bg-gray-800"
                    @click="toggleExpand(index)"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm"
                            :style="{ backgroundColor: link.color }"
                        >
                            <i :class="link.icon"></i>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white">{{ link.channel }}</span>
                            <span v-if="link.note" class="ml-2 text-xs text-amber-600 dark:text-amber-400">
                                <i class="fa-solid fa-circle-info"></i>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click.stop="copyUrl(buildUrl(link))"
                            class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors"
                        >
                            <svg class="w-3.5 h-3.5 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Copiar
                        </button>
                        <svg
                            class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="expandedIndex === index ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>

                <!-- Detalle expandido -->
                <div v-if="expandedIndex === index" class="px-4 pb-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
                    <div class="mt-3 space-y-2">
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="px-2 py-1 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 font-mono">utm_source={{ link.utm_source }}</span>
                            <span class="px-2 py-1 rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 font-mono">utm_medium={{ link.utm_medium }}</span>
                            <span class="px-2 py-1 rounded bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 font-mono">utm_campaign={{ campaignName }}</span>
                        </div>
                        <div class="p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                            <code class="text-xs text-gray-600 dark:text-gray-300 break-all">{{ buildUrl(link) }}</code>
                        </div>
                        <p v-if="link.note" class="text-xs text-amber-600 dark:text-amber-400">
                            <i class="fa-solid fa-circle-info mr-1"></i>{{ link.note }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
