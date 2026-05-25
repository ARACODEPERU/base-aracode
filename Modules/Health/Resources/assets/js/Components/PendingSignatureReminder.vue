<script setup>
import { onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import IconX from '@/Components/vristo/icon/icon-x.vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const show = ref(false);

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

onMounted(() => {
    show.value = props.items.length > 0;
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 px-4 py-6">
        <div class="panel w-full max-w-3xl overflow-hidden rounded-lg border-0 p-0">
            <div class="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
                <div>
                    <h3 class="text-lg font-semibold">Atenciones pendientes de firma</h3>
                    <p class="text-sm text-white-dark">Revisa y firma las atenciones que aún están abiertas.</p>
                </div>
                <button type="button" class="text-white-dark hover:text-danger" @click="show = false">
                    <IconX class="h-5 w-5" />
                </button>
            </div>

            <div class="max-h-[60vh] overflow-y-auto p-5">
                <div class="space-y-3">
                    <div
                        v-for="item in items"
                        :key="item.id"
                        class="flex flex-col gap-3 rounded border border-[#e0e6ed] p-3 dark:border-[#1b2e4b] sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <div class="font-semibold">{{ item.patient_name || 'Paciente no registrado' }}</div>
                            <div class="mt-1 text-sm text-white-dark">
                                {{ formatDate(item.attention_at) }} · {{ item.doctor_name || 'Sin doctor' }}
                            </div>
                        </div>
                        <Link :href="route('heal_attentions_edit', item.id)" class="btn btn-primary btn-sm">
                            Ver atención
                        </Link>
                    </div>
                </div>
            </div>

            <div class="flex justify-end border-t border-[#e0e6ed] px-5 py-4 dark:border-[#1b2e4b]">
                <button type="button" class="btn btn-outline-dark" @click="show = false">Cerrar</button>
            </div>
        </div>
    </div>
</template>
