<script setup>
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
        show: {
            type: Boolean,
            default: false,
        },
    });
</script>
<template>
    <TransitionRoot appear :show="show" as="template">
        <Dialog as="div" class="relative z-50">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <DialogOverlay class="fixed inset-0 bg-[black]/60" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center px-4 py-8">
                <TransitionChild
                    as="template"
                    enter="duration-300 ease-out"
                    enter-from="opacity-0 scale-95"
                    enter-to="opacity-100 scale-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100 scale-100"
                    leave-to="opacity-0 scale-95"
                >
                    <DialogPanel class="overflow-hidden w-full max-w-lg">

                    <!-- From Uiverse.io by Amerss -->
                        <div class="mx-auto w-[500px] bg-gray-950 rounded-xl overflow-hidden drop-shadow-xl">
                            <div class="bg-[#333] flex items-center p-[5px] text-whitec relative">
                                <div class="flex-1 text-center text-white"><slot name="title" /></div>
                                <div class="flex absolute right-3">
                                    <!-- <span class="h-3.5 w-3.5 bg-[#ff605c] rounded-xl mr-2"></span>
                                    <span class="h-3.5 w-3.5 bg-[#ffbd44] rounded-xl mr-2"></span> -->
                                    <button @click="onClose" class="h-4 w-4 bg-[#00ca4e] rounded-xl flex items-center justify-center"><icon-x class="h-3.5 w-3.5" /></button>
                                </div>
                            </div>
                            <div class="p-2.5 text-[#0f0]">
                                <slot name="content" />
                            </div>
                        </div>

                    </DialogPanel>
                </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
