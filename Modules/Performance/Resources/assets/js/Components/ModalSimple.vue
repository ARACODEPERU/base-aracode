<script setup>
/**
 * Modal propio del módulo Performance.
 *
 * No usa HeadlessUI: su diálogo dejaba un fondo invisible montado al cerrarse
 * (con la trampa de foco activa), lo que bloqueaba la página. Este se monta y
 * desmonta con v-if, así que al cerrarlo desaparece de verdad.
 */
import DangerButton from '@/Components/DangerButton.vue';
import IconX from '@/Components/vristo/icon/icon-x.vue';

defineProps({
    show: { type: Boolean, default: false },
    onClose: { type: Function, default: () => {} },
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="onClose"></div>

            <div class="flex min-h-full items-center justify-center px-4 py-8">
                <div
                    class="relative flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl border border-gray-200 bg-white text-black shadow-2xl dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <button
                        type="button"
                        class="absolute top-4 rounded-full bg-gray-100 p-2 text-gray-500 outline-none transition-colors hover:bg-gray-200 hover:text-gray-700 ltr:right-4 rtl:left-4 dark:bg-gray-700 dark:hover:bg-gray-600 dark:hover:text-gray-300"
                        aria-label="Cerrar"
                        @click="onClose"
                    >
                        <IconX class="h-4 w-4" />
                    </button>

                    <div
                        class="border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 pr-16 dark:border-gray-700 dark:from-gray-700 dark:to-gray-600"
                    >
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            <slot name="title" />
                        </h3>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            <slot name="message" />
                        </div>
                    </div>

                    <div class="overflow-y-auto bg-gray-50 px-6 py-5 dark:bg-gray-700">
                        <slot name="content" />
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-600">
                        <slot name="buttons" />
                        <DangerButton type="button" @click="onClose">Cerrar</DangerButton>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
