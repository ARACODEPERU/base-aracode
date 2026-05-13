<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Keypad from '@/Components/Keypad.vue';
import { Empty } from 'ant-design-vue';
import ModalSmall from "@/Components/ModalSmall.vue";
import { ref } from 'vue';
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    tags: { type: Array, default: () => [] }
});

const formTag = useForm({
    id: null,
    name: null,
});

const displayModal = ref(false);

const openModal = (tag = null) => {
    formTag.reset();
    if (tag) {
        formTag.id = tag.id;
        formTag.name = tag.name;
    }
    displayModal.value = true;
};

const closeModal = () => {
    displayModal.value = false;
};

const saveTag = () => {
    formTag.post(route('bib_tags_store_update'), {
        preserveScroll: true,
        onSuccess: () => {
            formTag.reset();
            displayModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Enhorabuena',
                text: 'Se registró correctamente',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};

const deleteTag = (id) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción eliminará permanentemente el tag.',
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
            return axios.delete(route('bib_tags_destroy', id)).then((res) => {
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
            router.visit(route('bib_tags'), {
                replace: false,
                method: 'get',
                preserveState: true,
                preserveScroll: true,
                only: ['tags'],
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Tags">
        <Navigation :routeModule="route('bib_dashboard')" :titleModule="'Biblio Data'"
            :data="[
                {title: 'Tags'}
            ]"
        />
        <div class="pt-5 space-y-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Tags / Etiquetas</h2>
                <Keypad>
                    <template #botones>
                        <PrimaryButton v-can="'bib_tags_nuevo'" type="button" @click="openModal()">Nuevo Tag</PrimaryButton>
                    </template>
                </Keypad>
            </div>

            <div class="panel">
                <div v-if="tags.length > 0">
                    <div class="flex flex-wrap gap-3">
                        <div v-for="tag in tags" :key="tag.id"
                            class="group flex items-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-full hover:shadow-md transition">
                            <span class="text-sm font-medium text-blue-700 dark:text-blue-300">{{ tag.name }}</span>
                            <button @click="openModal(tag)"
                                class="opacity-0 group-hover:opacity-100 text-blue-500 hover:text-blue-700 transition">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M441 58.9L453.1 71c9.4 9.4 9.4 24.6 0 33.9L424 134.1 377.9 88 407 58.9c9.4-9.4 24.6-9.4 33.9 0zM209.8 256.2L344 121.9 390.1 168 255.8 302.2c-2.9 2.9-6.5 5-10.4 6.1l-58.5 16.7 16.7-58.5c1.1-3.9 3.2-7.5 6.1-10.4zM373.1 25L175.8 222.2c-8.7 8.7-15 19.4-18.3 31.1l-28.6 100c-2.4 8.4-.1 17.4 6.1 23.6s15.2 8.5 23.6 6.1l100-28.6c11.8-3.4 22.5-9.7 31.1-18.3L487 138.9c28.1-28.1 28.1-73.7 0-101.8L474.9 25C446.8-3.1 401.2-3.1 373.1 25zM88 64C39.4 64 0 103.4 0 152L0 424c0 48.6 39.4 88 88 88l272 0c48.6 0 88-39.4 88-88l0-112c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 112c0 22.1-17.9 40-40 40L88 464c-22.1 0-40-17.9-40-40l0-272c0-22.1 17.9-40 40-40l112 0c13.3 0 24-10.7 24-24s-10.7-24-24-24L88 64z"/></svg>
                            </button>
                            <button @click="deleteTag(tag.id)"
                                class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 transition">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M135.2 17.7C140.6 6.8 151.7 0 163.8 0L284.2 0c12.1 0 23.2 6.8 28.6 17.7L320 32l96 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 96C14.3 96 0 81.7 0 64S14.3 32 32 32l96 0 7.2-14.3zM32 128l384 0v320c0 35.3-28.7 64-64 64H96c-35.3 0-64-28.7-64-64V128zm96 64c-8.8 0-16 7.2-16 16v224c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16v224c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16v224c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="flex justify-center py-12">
                    <Empty image="/img/empty-box.png" :image-style="{ height: '60px' }" />
                </div>
            </div>
        </div>

        <ModalSmall :show="displayModal" :onClose="closeModal" :icon="'/img/lista.png'">
            <template #title>{{ formTag.id ? 'Editar Tag' : 'Nuevo Tag' }}</template>
            <template #message>Los campos con * son obligatorios</template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Nombre *" />
                        <TextInput type="text" v-model="formTag.name" class="w-full" placeholder="Nombre del tag" />
                        <InputError v-if="formTag.errors.name" />
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton @click="saveTag" type="button" :disabled="formTag.processing">
                    <IconLoader v-if="formTag.processing" class="w-4 h-4 mr-2 animate-spin" />
                    {{ formTag.id ? 'Actualizar' : 'Guardar' }}
                </PrimaryButton>
            </template>
        </ModalSmall>
    </AppLayout>
</template>
