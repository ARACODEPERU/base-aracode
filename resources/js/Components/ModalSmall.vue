<script setup>
    import DangerButton from '@/Components/DangerButton.vue';
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogOverlay,
    } from "@headlessui/vue";
    import IconX from "@/Components/vristo/icon/icon-x.vue";

    const props = defineProps({
        onClose: {
            type: Function,
            default: () => {},
        },
        show:{
            type: Boolean,
            default: false,
        },
        icon:{
            type: String,
            default: () => {},
        }
    });
</script>
<template>
  <TransitionRoot appear :show="show" as="template">
        <Dialog as="div" class="relative z-50">
            <TransitionChild
              as="template"
              enter="duration-300 ease-out"
              enter-from="opacity-0"
              enter-to="opacity-100"
              leave="duration-200 ease-in"
              leave-from="opacity-100"
              leave-to="opacity-0"
            >
              <DialogOverlay class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="onClose" />
            </TransitionChild>
            <div class="fixed inset-0 overflow-y-auto">
              <div class="flex min-h-full items-center justify-center px-4 py-8">
                <DialogPanel @click.stop class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-2xl rounded-xl overflow-hidden w-full max-w-sm text-black dark:text-white animate__animated animate__zoomInUp transform transition-all">
                  <button type="button" class="absolute top-4 ltr:right-4 rtl:left-4 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 p-2 rounded-full text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 outline-none transition-colors" @click="onClose">
                    <icon-x class="w-4 h-4" />
                  </button>
                  <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-4">
                      <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-800">
                        <img v-if="icon" :src="icon" class="h-8 w-8" />
                        <svg v-else class="h-6 w-6 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                      </div>
                      <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white" id="modal-title">
                          <slot name="title" />
                        </h3>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                          <slot name="message" />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="px-6 py-5 bg-gray-50 dark:bg-gray-700">
                    <slot name="content" />
                    <div class="flex justify-end items-center space-x-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                        <slot name="buttons" />
                        <DangerButton @click="onClose" type="button" class="px-4 py-2">
                          Cerrar
                        </DangerButton>
                    </div>
                  </div>
                </DialogPanel>
              </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>


