<script setup>
/**
 * Botón "?" con la guía de las credenciales de Google Calendar.
 *
 * Se coloca junto a los parámetros confidenciales que se llenan a mano
 * (SC-00011 Client ID, SC-00012 Client Secret y SC-00013 refresh token) en
 * Parámetros del sistema. Si el código del parámetro no tiene guía, no pinta
 * nada, así las pantallas que lo usan no necesitan saber nada de Google.
 *
 * El modal se monta con Teleport para que no lo recorte ni lo desplace el
 * contenedor de la tabla donde vive el botón.
 */
import { computed, ref } from 'vue';
import { faQuestionCircle } from '@fortawesome/free-solid-svg-icons';
import ModalLarge from '@/Components/ModalLarge.vue';
import GoogleCalendarCredentialGuide from './GoogleCalendarCredentialGuide.vue';

const props = defineProps({
    // Código del parámetro (SC-00011, SC-00012, SC-00013, ...).
    parameterCode: {
        type: String,
        default: '',
    },
});

const HELP = {
    'SC-00011': { label: 'Client ID', title: '¿De dónde saco el Client ID de Google?' },
    'SC-00012': { label: 'Client Secret', title: '¿De dónde saco el Client Secret de Google?' },
    'SC-00013': { label: 'refresh token', title: '¿De dónde sale el refresh token de Google?' },
};

const help = computed(() => HELP[props.parameterCode] ?? null);
const show = ref(false);

const close = () => {
    show.value = false;
};
</script>

<template>
    <span v-if="help" class="inline-flex self-center align-middle">
        <button
            type="button"
            :title="help.title"
            :aria-label="help.title"
            class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-gray-300 text-[10px] font-semibold text-gray-500 transition-colors hover:border-primary hover:bg-primary/10 hover:text-primary dark:border-gray-600 dark:text-gray-400"
            @click="show = true"
        >
            <font-awesome-icon :icon="faQuestionCircle" class="h-3 w-3" />
        </button>

        <Teleport to="body">
            <ModalLarge :show="show" :onClose="close">
                <template #title>{{ help.title }}</template>

                <template #message>
                    Credencial OAuth 2.0 del sistema en Google Cloud Console. Se registra una sola vez.
                </template>

                <template #content>
                    <GoogleCalendarCredentialGuide :parameter-code="parameterCode" />
                </template>
            </ModalLarge>
        </Teleport>
    </span>
</template>
