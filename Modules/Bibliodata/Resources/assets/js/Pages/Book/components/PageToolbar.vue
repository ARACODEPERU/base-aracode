<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

defineProps({
    pageLabel: { type: String, default: '' },
    hasPage: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    practicalCasesCount: { type: Number, default: 0 },
});

defineEmits(['save', 'open-practical-cases']);
</script>

<template>
    <div
        class="bib-workspace-bar flex items-center justify-between gap-3 px-3 border-b border-gray-200 dark:border-zinc-700 bg-gray-50/80 dark:bg-zinc-800/50 shrink-0 box-border"
    >
        <p
            v-if="pageLabel"
            class="text-sm font-medium text-gray-800 dark:text-neutral-200 truncate flex-1 min-w-0 m-0"
        >
            {{ pageLabel }}
        </p>
        <p v-else class="text-sm text-gray-400 dark:text-gray-500 flex-1 min-w-0 m-0">
            Seleccione una página del árbol
        </p>
        <div v-if="hasPage" class="flex items-center gap-2 shrink-0">
            <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                :disabled="saving || loading"
                @click="$emit('open-practical-cases')"
            >
                Casos prácticos
                <span
                    v-if="practicalCasesCount > 0"
                    class="ml-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-primary/10 px-1.5 py-0.5 text-[10px] font-semibold text-primary"
                >
                    {{ practicalCasesCount }}
                </span>
            </button>
            <PrimaryButton
                type="button"
                class="btn-sm shrink-0"
                :disabled="saving || loading"
                @click="$emit('save')"
            >
                <IconLoader v-if="saving" class="w-4 h-4 animate-spin inline mr-1" />
                Guardar cambios
            </PrimaryButton>
        </div>
    </div>
</template>

<style scoped>
.bib-workspace-bar {
    height: 3rem;
    min-height: 3rem;
}
</style>
