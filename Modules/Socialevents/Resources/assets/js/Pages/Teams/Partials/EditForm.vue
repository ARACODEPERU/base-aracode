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
    import { faWandMagicSparkles } from "@fortawesome/free-solid-svg-icons";

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

    const displayModalAI = ref(false);
    const aiGeneratedImage = ref(null);
    const aiProcessing = ref(false);

    const generateShieldWithAI = () => {
        if (!form.name) {
            Swal2.fire('Por favor, escribe el nombre del equipo primero.');
            return;
        }

        aiProcessing.value = true;
        const prompt = `Crea un escudo o logo atractivo y profesional para el equipo de fútbol llamado "${form.name}". Debe ser en formato cuadrado, con colores vibrantes y elementos deportivos.`;

        const url = import.meta.env.VITE_SOCKET_IO_SERVER + '/api/ai/generate-shield';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        axios.post(url, {
            prompt: prompt,
            csrfToken: csrfToken
        }, {
            headers: {
                'Content-Type': 'application/json'
            },
            timeout: 0,
        }).then((result) => {
            aiGeneratedImage.value = result.data.imageBase64;
            displayModalAI.value = true;
        }).catch((error) => {
            console.log(error);
            let errorMessage = 'Error al generar la imagen.';
            if (error.response?.data?.error?.message) {
                const originalMessage = error.response.data.error.message;
                // Traducir mensaje específico al español
                if (originalMessage.includes('You exceeded your current quota')) {
                    errorMessage = 'Has excedido tu cuota actual. Por favor, verifica tu plan y detalles de facturación. Para más información sobre este error, visita: https://ai.google.dev/gemini-api/docs/rate-limits. Para monitorear tu uso actual, visita: https://ai.dev/rate-limit.\n\nDetalles:\n* Cuota excedida para tokens de entrada en el nivel gratuito.\n* Cuota excedida para solicitudes en el nivel gratuito.\nPor favor, reintenta en unos segundos.';
                } else {
                    errorMessage = originalMessage;
                }
            } else if (error.message) {
                errorMessage = error.message;
            }
            Swal2.fire(errorMessage);
        }).finally(() => {
            aiProcessing.value = false;
        });
    }

    const acceptAIGeneratedImage = () => {
        // Asumiendo que CropperImage tiene un prop imgDefault o método para setear
        // Por ahora, setear directamente en form.logo_path como base64
        form.logo_path = aiGeneratedImage.value;
        displayModalAI.value = false;
        aiGeneratedImage.value = null;
    }

    const cancelAIGeneratedImage = () => {
        displayModalAI.value = false;
        aiGeneratedImage.value = null;
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
                    <div class="mb-6">
                        <InputLabel for="file_input" value="Imagen *" />
                        <div class="flex items-center space-x-4">
                            <CropperImage
                                :aspectRatio="1024 / 1336"
                                :imgDefault="xhttp + 'storage/' + form.preview_logo_path"
                                ref="cropper"
                                @onCrop="cropImageAndSave"
                            ></CropperImage>
                        </div>
                        <InputError :message="form.errors.logo_path" class="mt-2" />
                    </div>
                    <button
                        type="button"
                        @click="generateShieldWithAI"
                        :disabled="aiProcessing"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition duration-150 ease-in-out disabled:opacity-50"
                    >
                        <icon-loader v-show="aiProcessing" class="w-4 h-4 animate-spin mr-2" />
                        <font-awesome-icon v-show="!aiProcessing" :icon="faWandMagicSparkles" class="w-4 h-4 mr-2" />
                        Generar Escudo con IA
                    </button>
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

    <!-- Modal para imagen generada con IA -->
    <div v-if="displayModalAI" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="cancelAIGeneratedImage">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900">Escudo Generado con IA</h3>
                <div class="mt-4">
                    <img v-if="aiGeneratedImage" :src="`data:image/png;base64,${aiGeneratedImage}`" class="mx-auto max-w-full h-auto rounded" />
                </div>
                <div class="flex justify-center mt-4 space-x-4">
                    <button @click="acceptAIGeneratedImage" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700">Aceptar</button>
                    <button @click="cancelAIGeneratedImage" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-700">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</template>
