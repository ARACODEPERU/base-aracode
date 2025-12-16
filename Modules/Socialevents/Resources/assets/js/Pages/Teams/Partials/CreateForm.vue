<script setup>
    import { useForm, Link } from '@inertiajs/vue3';
    import FormSection from '@/Components/FormSection.vue';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import TextInput from '@/Components/TextInput.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Swal2 from 'sweetalert2';
    import { ref, watch, onMounted } from 'vue';
    import { Select, TreeSelect } from 'ant-design-vue';
    import 'cropperjs/dist/cropper.css';
    import CropperImage from '@/Components/CropperImage.vue';
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
    import SearchClients from './SearchClients.vue';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';

    const props = defineProps({
        ubigeo: {
            type: Object,
            default: () => ({}),
        },
        documentTypes: {
            type: Object,
            default: () => ({}),
        }
    });

    const form = useForm({
        name: null,
        short_name: null,
        ubigeo: null,
        ubigeo_description: null,
        logo_path: null,
        manager_id: null,
        manager_name: null,
    });

    const createNow = () => {
        form.post(route('even_equipos_store'), {
            forceFormData: true,
            errorBag: 'createNow',
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se registró correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                form.reset();
            },
        });
    }

    const cropImageAndSave = (res) => {
        form.logo_path = res;
    }

    const ubigeoSelected = ref(null);

    const selectCity = () => {
        form.ubigeo_description = ubigeoSelected.value.ubigeo_description;
        form.ubigeo = ubigeoSelected.value.district_id;
    }

    const displayModalClientSearch = ref(false);

    const openModalClientSearch = () => {
        displayModalClientSearch.value = true;
    }
    const closeModalClientSearch = () => {
        displayModalClientSearch.value = false;
    }
    const getDataClient = async (data) => {
        form.manager_id = data.id;
        form.manager_name = data.full_name;
        displayModalClientSearch.value = false;

    }


</script>

<template>
    <FormSection @submitted="createNow" class="">
        <template #title>
            Equipo Detalles
        </template>

        <template #description>
            Crear Equipo, Los campos con * son obligatorios
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-3">
                <div class="grid sm:grid-cols-2 gap-6">
                    <div>
                        <InputLabel for="name" value="Nombre *" class="mb-1" />
                        <TextInput v-model="form.name" id="name" />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="short_name" value="Nombre corto *" class="mb-1" />
                        <TextInput v-model="form.short_name" id="short_name" />
                        <InputError :message="form.errors.short_name" class="mt-2" />
                    </div>
                    <div class="col-span-2">
                        <InputLabel for="short_name" value="Ciudad*" class="mb-1" />
                        <multiselect
                            id="industry_id"
                            :model-value="ubigeoSelected"
                            v-model="ubigeoSelected"
                            :options="ubigeo"
                            class="custom-multiselect"
                            :searchable="true"
                            placeholder="Buscar"
                            selected-label="seleccionado"
                            select-label="Elegir"
                            deselect-label="Quitar"
                            label="ubigeo_description"
                            track-by="district_id"
                            @update:model-value="selectCity"
                        ></multiselect>
                        <InputError :message="form.errors.ubigeo_description" class="mt-2" />
                    </div>
                    <div class="col-span-2">
                        <InputLabel for="short_name" value="Delegado / Representante*" class="mb-1" />
                        <div>
                            <input @click="openModalClientSearch" @input="openModalClientSearch"
                                :value="form.manager_name"
                                class="form-input" type="text"
                            />
                            <SearchClients
                                :display="displayModalClientSearch"
                                :closeModalClient="closeModalClientSearch"
                                @clientId="getDataClient"
                                :ubigeo="ubigeo"
                                :documentTypes="documentTypes"
                            />
                            <div>
                                <InputError :message="form.errors.manager_name" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-6 sm:col-span-3">
                <div class="col-span-6">
                    <InputLabel for="file_input" value="Imagen *" />
                    <CropperImage
                        ref="cropper"
                        @onCrop="cropImageAndSave"
                    ></CropperImage>
                    <InputError :message="form.errors.logo_path" class="mt-2" />
                </div>
            </div>

        </template>

        <template #actions>
            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="mr-2">
                {{ form.progress.percentage }}%
            </progress>
            <Keypad>
                <template #botones>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        <icon-loader v-show="form.processing" class="w-4 h-4 animate-spin mr-1" />
                        Guardar
                    </PrimaryButton>
                    <Link :href="route('even_equipos_listado')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
