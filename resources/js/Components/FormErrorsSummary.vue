<script setup>
import { computed } from 'vue';

/**
 * Resumen visible de errores de validacion de un formulario de Inertia.
 *
 * Se creo porque CreateForm.vue y EditForm.vue de Academic lo importan desde el
 * commit ce6962ab: sin el archivo el build de Vite falla y las paginas quedan
 * con el bundle viejo (los selects de categoria/tipo/modalidad/sector dejaban
 * de aparecer en la edicion de cursos).
 *
 * Los errores llegan como el objeto `form.errors` de Inertia: pares
 * campo -> mensaje. Se muestran solo los mensajes unicos porque varios campos
 * suelen compartir el mismo texto (por ejemplo "El campo X es obligatorio").
 */
const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
    title: {
        type: String,
        default: 'Por favor corrija lo siguiente:',
    },
});

const messages = computed(() => {
    const unique = [];

    Object.values(props.errors ?? {}).forEach((message) => {
        const text = String(message ?? '').trim();

        if (text !== '' && !unique.includes(text)) {
            unique.push(text);
        }
    });

    return unique;
});
</script>

<template>
    <div v-if="messages.length > 0" class="col-span-6 mb-2 rounded-md bg-danger-light bg-opacity-20 p-4 dark:bg-danger dark:bg-opacity-20" role="alert">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-danger" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <div class="text-sm text-danger">
                <p class="font-semibold">{{ title }}</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    <li v-for="(message, index) in messages" :key="index">{{ message }}</li>
                </ul>
            </div>
        </div>
    </div>
</template>
