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

    const openModalFormCategory = (category = []) => {
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
                    text: 'Se registro correctamente',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                })
            },
        });
    }

</script>
<template>
    <AppLayout title="Categoría">
        <Navigation :routeModule="route('bib_dashboard')" :titleModule="'Biblio Data'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Categoría</span>
            </li>
        </Navigation>
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
                <PrimaryButton @click="saveCategory" type="button">
                    {{ formCategory.id ? 'Actualizar' : 'Guardar' }}
                </PrimaryButton>
            </template>
        </ModalSmall>
    </AppLayout>
</template>
