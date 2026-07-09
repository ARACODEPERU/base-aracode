<script setup>
import {
    Dialog,
    DialogOverlay,
    DialogPanel,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import IconX from '@/Components/vristo/icon/icon-x.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import ReaderPracticalCaseContent from './ReaderPracticalCaseContent.vue';

defineProps({
    show: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    caseItem: { type: Object, default: null },
});

const emit = defineEmits(['close']);
</script>

<template>
    <TransitionRoot appear :show="show" as="template">
        <Dialog as="div" class="relative z-50" @close="emit('close')">
            <TransitionChild
                as="template"
                enter="duration-200 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-150 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <DialogOverlay class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto p-4">
                <div class="flex min-h-full items-center justify-center">
                    <TransitionChild
                        as="template"
                        enter="duration-200 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-150 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel
                            class="bib-reader-case-modal relative w-full max-w-3xl rounded-xl bg-white shadow-xl dark:bg-zinc-900"
                            @click.stop
                        >
                            <button
                                type="button"
                                class="absolute top-3 right-3 rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-zinc-800"
                                aria-label="Cerrar"
                                @click="emit('close')"
                            >
                                <IconX class="h-5 w-5" />
                            </button>

                            <div class="bib-reader-case-modal__header border-b border-slate-200 px-4 sm:px-6 py-4 dark:border-zinc-700">
                                <p class="text-xs font-medium uppercase tracking-wide text-cyan-600">
                                    Caso práctico
                                </p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-800 dark:text-slate-100">
                                    {{ caseItem?.title || 'Cargando...' }}
                                </h3>
                            </div>

                            <div class="bib-reader-case-modal__body max-h-[min(70vh,640px)] overflow-y-auto p-4 sm:p-6">
                                <div v-if="loading" class="flex justify-center py-12">
                                    <IconLoader class="h-10 w-10 animate-spin text-cyan-500" />
                                </div>
                                <ReaderPracticalCaseContent v-else-if="caseItem" :case-item="caseItem" />
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
