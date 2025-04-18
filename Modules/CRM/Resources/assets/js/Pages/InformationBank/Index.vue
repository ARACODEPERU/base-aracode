<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ref } from 'vue';
    import VueCollapsible from 'vue-height-collapsible/vue3';
    import iconPencil from '@/Components/vristo/icon/icon-pencil.vue';
    import iconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
    import { useForm, Link, router } from '@inertiajs/vue3';
    import Swal2 from 'sweetalert2';

    const props = defineProps({
        ibanks: {
            type: Object,
            default: () => ({})
        }
    });

    const accordians = ref(null);
    const formIbank = useForm({
        question_text: null,
        response_text: null,
        status: true,
    });

    const updateIbank = (item) => {
        formIbank.question_text = item.question_text;
        formIbank.response_text = item.response_text;
        formIbank.status = !item.status;

        formIbank.put(route('crm_common_questions_update',item.id), {
            preserveScroll: true,
            onSuccess: () => formIbank.reset(),
        });
    }

    const destroyIbank = (id) => {
        Swal2.fire({
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            preConfirm: () => {
                return axios.delete(route('crm_common_questions_destroy', id)).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message)
                    }
                    return res
                });
            },
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('crm_common_questions'), {
                    method: 'GET',
                    replace: false,
                    preserveState: true,
                    preserveScroll: true,
                    onFinish: visit => {},
                });
                accordians.value = null;
            }
        });
    }
</script>
<template>
    <AppLayout title="Contactos">
        <Navigation >
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Dudas Comunes</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <!-- FAQ -->
            <div class="w-full px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
                <!-- Grid -->
                <div class="grid grid-cols-6 gap-10">
                    <div class="col-span-6 sm:col-span-2">
                        <div class="max-w-xs">
                            <h2 class="text-2xl font-bold md:text-4xl md:leading-tight dark:text-white">Dudas Comunes</h2>
                            <p class="mt-1 hidden md:block text-gray-600 dark:text-neutral-400">Respuestas a las preguntas más frecuentes.</p>
                        </div>
                    </div>
                    <!-- End Col -->

                    <div class="col-span-6 sm:col-span-4">
                        <!-- Accordion -->
                        <div class="space-y-2 font-semibold">
                            <template v-for="(ibank, key) in ibanks">
                                <div class="bg-white border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                    <button
                                        class="p-4 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                        :class="{ '!text-primary': accordians === key }"
                                        @click="accordians === key ? (accordians = null) : (accordians = key)"
                                    >
                                        <svg class="ltr:mr-2 rtl:ml-2 w-5 h-5 text-primary shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                            <path fill="currentColor" d="M80 160c0-35.3 28.7-64 64-64l32 0c35.3 0 64 28.7 64 64l0 3.6c0 21.8-11.1 42.1-29.4 53.8l-42.2 27.1c-25.2 16.2-40.4 44.1-40.4 74l0 1.4c0 17.7 14.3 32 32 32s32-14.3 32-32l0-1.4c0-8.2 4.2-15.8 11-20.2l42.2-27.1c36.6-23.6 58.8-64.1 58.8-107.7l0-3.6c0-70.7-57.3-128-128-128l-32 0C73.3 32 16 89.3 16 160c0 17.7 14.3 32 32 32s32-14.3 32-32zm80 320a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/>
                                        </svg>

                                        <div class="text-left" v-html="ibank.question_text"></div>
                                        <svg :class="{ 'rotate-180': accordians === key }" class="shrink-0 size-5 text-gray-600 group-hover:text-gray-500 dark:text-neutral-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <vue-collapsible :isOpen="accordians === key">
                                        <div
                                            class="space-y-2 p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]"

                                        >
                                            <div v-can="'crm_dudas_comunes_edicion'" class="flex justify-end">
                                                <div class="relative inline-flex align-middle">
                                                    <button
                                                        @click="updateIbank(ibank)"
                                                        type="button"
                                                        class="btn btn-outline-primary btn-sm text-xs ltr:rounded-r-none rtl:rounded-l-none"
                                                        v-tippy="{ content: ibank.status ? 'Visible para los alumnos' : 'Los alumnos no pueden verlo', placement: 'bottom'}"
                                                    >
                                                        <svg v-if="ibank.status" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                                            <path fill="currentColor" d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64c-7.1 0-13.9-1.2-20.3-3.3c-5.5-1.8-11.9 1.6-11.7 7.4c.3 6.9 1.3 13.8 3.2 20.7c13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3z"/>
                                                        </svg>
                                                        <svg v-else class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                                            <path fill="currentColor" d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zM223.1 149.5C248.6 126.2 282.7 112 320 112c79.5 0 144 64.5 144 144c0 24.9-6.3 48.3-17.4 68.7L408 294.5c8.4-19.3 10.6-41.4 4.8-63.3c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3c0 10.2-2.4 19.8-6.6 28.3l-90.3-70.8zM373 389.9c-16.4 6.5-34.3 10.1-53 10.1c-79.5 0-144-64.5-144-144c0-6.9 .5-13.6 1.4-20.2L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5L373 389.9z"/>
                                                        </svg>
                                                    </button>
                                                    <Link :href="route('crm_common_questions_edit', ibank.id)"
                                                        type="button"
                                                        class="btn btn-outline-success btn-sm text-xs rounded-none"
                                                        v-tippy="{ content: 'Editar', placement: 'bottom'}"
                                                    >
                                                        <icon-pencil class="w-4 h-4" />
                                                    </Link>
                                                    <button @click="destroyIbank(ibank.id)" type="button" class="btn btn-outline-danger btn-sm text-xs ltr:rounded-l-none rtl:rounded-r-none">
                                                        <icon-trash-lines class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        <div v-html="ibank.response_text"></div>
                                        </div>
                                    </vue-collapsible>
                                </div>
                            </template>
                        </div>
                        <!-- End Accordion -->
                    </div>
                    <!-- End Col -->
                </div>
                <!-- End Grid -->
            </div>
            <!-- End FAQ -->
        </div>
    </AppLayout>
</template>
