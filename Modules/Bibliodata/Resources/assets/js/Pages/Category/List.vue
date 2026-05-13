<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import iconArrowLeft from '@/Components/vristo/icon/icon-arrow-left.vue';
    import { Link, router, useForm } from '@inertiajs/vue3';
    import Swal from 'sweetalert2';
    import PrimaryButton from "@/Components/PrimaryButton.vue";
    import Keypad from '@/Components/Keypad.vue';
    import { Empty } from 'ant-design-vue';
    import ModalSmall from "@/Components/ModalSmall.vue";
    import { icon } from "@fortawesome/fontawesome-svg-core";
    import { ref } from 'vue';
    import TextInput from "@/Components/TextInput.vue";
    import InputLabel from "@/Components/InputLabel.vue";
    import InputError from "@/Components/InputError.vue";
    import axios from 'axios';
    import IconLoader from '@/Components/vristo/icon/icon-loader.vue';


    const props = defineProps({
        categories: {
            type: Object,
            default: () => ({})
        }
    });

    const formCategory = useForm({
        id: null,
        name: null,
        description: null
    });

    const displayModalFormCategory = ref(false);

    const openModalFormCategory = (category = null) => {
        formCategory.reset();
        if(category){
            formCategory.id = category.id;
            formCategory.name = category.name;
            formCategory.description = category.description;
        }
        displayModalFormCategory.value = true;
    }

    const closeModalFormCategory = () => {
        displayModalFormCategory.value = false;
    }

    const saveCategory = () => {
        formCategory.post(route('bib_categories_store_update'), {
            preserveScroll: true,
            onSuccess: () => {
                formCategory.reset()
                displayModalFormCategory.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Enhorabuena',
                    text: 'Se registró correctamente',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                })
            },
        });
    }

    const deleteCategory = (id) => {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción eliminará permanentemente la categoría.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            preConfirm: () => {
                return axios.delete(route('bib_categories_destroy', id)).then((res) => {
                    if (res.data && !res.data.success) {
                        Swal.showValidationMessage(res.data.message || 'Error al eliminar')
                    }
                    return res
                }).catch((error) => {
                    Swal.showValidationMessage(error.response?.data?.message || 'Error de conexión')
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Enhorabuena',
                    text: 'Se eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('bib_categories'), {
                    replace: false,
                    method: 'get',
                    preserveState: true,
                    preserveScroll: true,
                    only: ['categories'],
                });
            }
        });
    }

</script>
<template>
    <AppLayout title="Categoría">
        <Navigation :routeModule="route('bib_dashboard')" :titleModule="'Biblio Data'"
            :data="[
                {title: 'Categoría'}
            ]"
        />
        <div class="pt-5 space-y-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Categoría </h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <Keypad>
                            <template #botones>
                                <PrimaryButton v-can="'bib_categorias_nuevo'" type="button" @click="openModalFormCategory">Nuevo</PrimaryButton>
                            </template>
                        </Keypad>

                    </div>
                </div>
            </div>

            <div class="panel">
                <div v-if="categories.length > 0">
                    <!-- Grid -->
                    <div class="grid sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6">
                        <!-- Card -->
                        <div v-for="category in categories" class="group flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl hover:shadow-md focus:outline-hidden focus:shadow-md transition dark:bg-neutral-900 dark:border-neutral-800" href="#">
                            <div class="p-4 md:p-5">
                                <div class="flex justify-between items-center gap-x-3">
                                    <div class="grow">
                                        <h3 class="group-hover:text-blue-600 font-semibold text-gray-800 dark:group-hover:text-neutral-400 dark:text-neutral-200">
                                        {{ category.name }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-neutral-500">
                                        {{ category.description }}
                                        </p>
                                    </div>
                                    <div>
                                        <svg class="shrink-0 size-5 text-gray-800 dark:text-neutral-200" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </div>
                                </div>
                                <div class="flex gap-3 mt-4 pt-3 border-t border-gray-100 dark:border-neutral-700">
                                    <button @click="openModalFormCategory(category)" v-can="'bib_categorias_nuevo'"
                                        class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1 transition">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M441 58.9L453.1 71c9.4 9.4 9.4 24.6 0 33.9L424 134.1 377.9 88 407 58.9c9.4-9.4 24.6-9.4 33.9 0zM209.8 256.2L344 121.9 390.1 168 255.8 302.2c-2.9 2.9-6.5 5-10.4 6.1l-58.5 16.7 16.7-58.5c1.1-3.9 3.2-7.5 6.1-10.4zM373.1 25L175.8 222.2c-8.7 8.7-15 19.4-18.3 31.1l-28.6 100c-2.4 8.4-.1 17.4 6.1 23.6s15.2 8.5 23.6 6.1l100-28.6c11.8-3.4 22.5-9.7 31.1-18.3L487 138.9c28.1-28.1 28.1-73.7 0-101.8L474.9 25C446.8-3.1 401.2-3.1 373.1 25zM88 64C39.4 64 0 103.4 0 152L0 424c0 48.6 39.4 88 88 88l272 0c48.6 0 88-39.4 88-88l0-112c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 112c0 22.1-17.9 40-40 40L88 464c-22.1 0-40-17.9-40-40l0-272c0-22.1 17.9-40 40-40l112 0c13.3 0 24-10.7 24-24s-10.7-24-24-24L88 64z"/></svg>
                                        Editar
                                    </button>
                                    <button @click="deleteCategory(category.id)" v-can="'bib_categorias_eliminar'"
                                        class="text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 flex items-center gap-1 transition">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M135.2 17.7C140.6 6.8 151.7 0 163.8 0L284.2 0c12.1 0 23.2 6.8 28.6 17.7L320 32l96 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 96C14.3 96 0 81.7 0 64S14.3 32 32 32l96 0 7.2-14.3zM32 128l384 0 0 320c0 35.3-28.7 64-64 64L96 512c-35.3 0-64-28.7-64-64l0-320zm96 64c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16z"/></svg>
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- End Card -->
                    </div>
                </div>
                <div v-else class="flex justify-center">
                    <Empty
                        image="/img/empty-box.png"
                        :image-style="{
                            height: '60px',
                        }"
                    >
                    </Empty>
                </div>
            </div>
        </div>

        <ModalSmall
            :show="displayModalFormCategory"
            :onClose="closeModalFormCategory"
            :icon="'/img/lista.png'"
        >
            <template #title>{{ formCategory.id ? 'Editar Categoría' : 'Nueva Categoría' }}</template>
            <template #message>Los campos con * son obligatorios</template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Nombre *" />
                        <TextInput type="text" v-model="formCategory.name" />
                        <InputError v-if="formCategory.errors.name" />
                    </div>
                    <div>
                        <InputLabel value="Descripcíon" />
                        <TextInput type="text" v-model="formCategory.description" />
                        <InputError v-if="formCategory.errors.description" />
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton @click="saveCategory" type="button" :disabled="formCategory.processing">
                    <IconLoader v-if="formCategory.processing" class="w-4 h-4 mr-2 animate-spin" />
                    {{ formCategory.id ? 'Actualizar' : 'Guardar' }}
                </PrimaryButton>
            </template>
        </ModalSmall>
    </AppLayout>
</template>
