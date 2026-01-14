<template>
    <div class="relative w-full min-h-[70vh] h-auto bg-gray-100 rounded-lg overflow-hidden">

        <div v-if="isLoading || rendering"
             class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white bg-opacity-90">
            <div class="w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
            <p class="mt-4 text-indigo-700 font-medium">
                {{ isLoading ? 'Generando documento oficial...' : 'Preparando vista previa...' }}
            </p>
        </div>

        <div class="h-full overflow-y-auto p-4">
            <vue-pdf-embed
                v-if="pdfUrl"
                :source="pdfUrl"
                @rendered="onRendered"
                @loading-failed="onFailed"
            />
        </div>
    </div>
</template>

<script setup>
    import { ref, watch } from 'vue';
    import VuePdfEmbed from 'vue-pdf-embed';

    const props = defineProps({
        pdfUrl: String,
        isLoading: Boolean
    });

    const rendering = ref(false);

    // Si llega una nueva URL, activamos el estado de renderizado interno
    watch(() => props.pdfUrl, (newUrl) => {
        if (newUrl) rendering.value = true;
    });

    const onRendered = () => {
        rendering.value = false;
    };

    const onFailed = () => {
        rendering.value = false;
        alert("No se pudo cargar la vista previa.");
    };
</script>
