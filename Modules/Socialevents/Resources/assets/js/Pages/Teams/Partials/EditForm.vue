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
        },
        team: {
            type: Object,
            default: () => ({}),
        }
    });

    const form = useForm({
        id: props.team.id,
        name: props.team.name,
        short_name: props.team.short_name,
        ubigeo: props.team.ubigeo,
        ubigeo_description: props.team.ubigeo_description,
        logo_path: null,
        preview_logo_path: props.team.logo_path,
        manager_id: props.team.manager_id,
        manager_name: props.team.manager.full_name,
        champion: props.team.champion == 1 ? true : false,
        status: props.team.status == 1 ? true : false,
    });

    const createNow = () => {
        form.post(route('even_equipos_update'), {
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

    const xhttp =  assetUrl;
</script>

<template>
    <FormSection @submitted="createNow" class="">
        <template #title>
            Equipo Detalles
        </template>

        <template #description>
            Crear equipo, Los campos con * son obligatorios
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
                    <div class="col-span-2">
                        <div class="flex items-center">
                            <input id="checked-checkbox-1" type="checkbox" v-model="form.champion" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="checked-checkbox-1" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Actual campeon</label>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div class="flex items-center">
                            <input id="checked-checkbox-2" type="checkbox" v-model="form.status" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="checked-checkbox-2" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Activo</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-6 sm:col-span-3">
                <div class="col-span-6">
                    <InputLabel for="file_input" value="Imagen *" />
                    <CropperImage
                        :aspectRatio="1024 / 1336"
                        :imgDefault="xhttp + 'storage/' + form.preview_logo_path"
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
                        Actualizar
                    </PrimaryButton>
                    <Link :href="route('even_equipos_listado')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                </template>
            </Keypad>
        </template>
    </FormSection>
</template>
