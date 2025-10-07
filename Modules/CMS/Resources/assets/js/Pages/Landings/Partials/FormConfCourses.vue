<script setup>
    import { useForm } from '@inertiajs/vue3';
    import { Select, SelectOption } from 'ant-design-vue';
    import Swal from 'sweetalert2';
    import { ref, onMounted, computed, watch } from 'vue';
    import Editor from '@tinymce/tinymce-vue'
    import InputError from '@/Components/InputError.vue';
    import { usePage } from '@inertiajs/vue3';
    import flatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"

    const rangeCalendar = ref({
        dateFormat: 'Y-m-d',
        mode: 'range',
        position: 'auto left',
        locale: Spanish,
        onChange: (selectedDates) => {
            if (selectedDates.length === 2) {
            const [start, end] = selectedDates

            // Validar que el rango sea de al menos 1 día
            if (end <= start) {
                // Si es igual o menor, forzar end = start + 1 día
                const newEnd = new Date(start)
                newEnd.setDate(newEnd.getDate() + 1)
                dateRange.value = [start, newEnd]
                form.date_start = start.toISOString().split('T')[0]
                form.date_end = newEnd.toISOString().split('T')[0]
            } else {
                form.date_start = start.toISOString().split('T')[0]
                form.date_end = end.toISOString().split('T')[0]
            }
            }
        }
    });

    const page = usePage();

    const props = defineProps({
        courses: {
            type: Object,
            default: () => ({})
        },
        tinykey: {
            type: String,
            default: null,
        },
        landingPage: {
            type: Object,
            default: () => ({})
        }
    });

    const baseUrl = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    let ImagePreview = props.landingPage?.main_image;

    const form = useForm({
        landing_id: props.landingPage?.id,
        image: null,
        image_preview: ImagePreview ? getImage(ImagePreview) : '/img/icons8-img-750.png',
        title: props.landingPage?.title,
        slug: props.landingPage?.slug,
        description_short: props.landingPage?.description_short,
        description_long: props.landingPage?.description_long,
        data_related: props.landingPage?.data_related,
        data_related2: props.landingPage?.data_related2,
        additional_field1: props.landingPage?.additional_field1,
        additional_field2: props.landingPage?.additional_field2,
        additional_field3: props.landingPage?.additional_field3,
        date_start: props.landingPage?.date_start,
        date_end: props.landingPage?.date_end,
        status: props.landingPage?.status,
        menu_id: '01'
    });

    const imagePreviewRef = ref(null);
    const fileName = ref(null);

    const loadFile = (event) => {
        const file = event.target.files[0];
        if (!file) {
            // If the user cancels the file selection, clear the fields and exit
            form.image = null;
            form.image_preview = null;
            fileName.value = null;
            return;
        }

        // Assign the file object to the `image` field for form submission
        form.image = file;

        // Guardar nombre + extensión
        fileName.value = file.name;
        // Create a temporary URL for the preview and assign it
        const fileUrl = URL.createObjectURL(file);
        form.image_preview = fileUrl;

        // Use the template ref to get the <img> element
        const imageElement = imagePreviewRef.value;

        if (imageElement) {
            // This is a good practice to free up memory when the image has loaded
            imageElement.onload = () => {
                URL.revokeObjectURL(fileUrl);
            };
        }
    };

    const inputCourses = ref([]);

    onMounted(() => {
        if (props.landingPage?.data_related) {
            inputCourses.value = props.landingPage.data_related.items.map(i => parseInt(i, 10));
        }
    });


    const createLanding = () => {
        let xCourses = {
            table: 'aca_courses',
            items: inputCourses.value.map(Number)
        }
        form.data_related = xCourses;
        form.post(route('cms_landing_course_free_store'), {
            forceFormData: true,
            errorBag: 'createLanding',
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    title: 'Enhorabuena',
                    text: 'Se registró correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                //form.reset()
            },
        });
    }

    const uploadImage = (blobInfo, progress) => {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());

            axios.post(route('blog_article_uploa_image_tiny'), formData, {
                withCredentials: false,
                onUploadProgress: (e) => {
                    progress(e.loaded / e.total * 100);
                }
            }).then(response => {
                if (response.status === 403) {
                    reject({ message: 'HTTP Error: ' + response.status, remove: true });
                    return;
                }

                if (response.status < 200 || response.status >= 300) {
                    reject('HTTP Error: ' + response.status);
                    return;
                }

                const json = response.data;

                if (!json || typeof json.location !== 'string') {
                    reject('Invalid JSON: ' + JSON.stringify(json));
                    return;
                }
                resolve(json.location);
            }).catch(error => {
                reject('Image upload failed due to a XHR Transport error. Error: ' + error.message);
            });
        });
    };

    const generateSlug = (text) => {
        return text
        .toString()
        .normalize("NFD")                     // quita acentos
        .replace(/[\u0300-\u036f]/g, "")      // elimina marcas diacríticas
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')          // reemplaza lo que no sea letras/números
        .replace(/^-+|-+$/g, '')              // quita guiones al inicio/fin
    }

    const onTitleInput = (e) => {
        form.title = e.target.value
        form.slug = generateSlug(form.title)
    }

    const dateRange = ref(null);

    onMounted(() => {
        if (props.landingPage?.date_start && props.landingPage?.date_end) {
            const start = new Date(props.landingPage.date_start)
            const end = new Date(props.landingPage.date_end)

            dateRange.value = [start, end]
        }
    })

</script>
<template>
    <form class="space-y-5" @submit.prevent="createLanding">
        <div class="flex sm:flex-row flex-col">
            <label for="txttitle" class="mb-0 sm:w-1/4 sm:ltr:mr-2 rtl:ml-2">Título*</label>
            <div class="flex-1">
                <input v-model="form.title" @input="onTitleInput" id="txttitle" type="text" placeholder="Título principal" class="form-input" />
                <InputError :message="form.errors.title" class="mt-2" />
            </div>
        </div>
        <div class="flex sm:flex-row flex-col">
            <label for="txtslug" class="mb-0 sm:w-1/4 sm:ltr:mr-2 rtl:ml-2">url amigable (slug)</label>
            <div class="flex-1">
                <input v-model="form.slug" id="txtslug" type="text" placeholder="automáticamente se generara el slug"
                    class="
                        form-input disabled:pointer-events-none disabled:bg-[#eee] dark:disabled:bg-[#1b2e4b] cursor-not-allowed
                    "
                    disabled
                />
                <InputError :message="form.errors.slug" class="mt-2" />
            </div>
        </div>
        <div class="flex sm:flex-row flex-col">
            <label for="txtmeta" class="mb-0 sm:w-1/4 sm:ltr:mr-2 rtl:ml-2">Descripción * (&lt;meta&gt;)</label>
            <div class="flex-1">
                <input v-model="form.description_short" id="txtmeta" type="text" placeholder="Descripción corta" class="form-input" />
                <InputError :message="form.errors.description_short" class="mt-2" />
            </div>
        </div>
        <div class="flex sm:flex-row flex-col">
            <label for="txtdescription" class="mb-0 sm:w-1/4 sm:ltr:mr-2 rtl:ml-2">Descripción * (Contenido)</label>
            <div class="flex-1">
                <Editor
                    id="txtdescription"
                    :api-key="tinykey"
                    v-model="form.description_long"
                    :init="{
                        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                        images_upload_handler: uploadImage,
                        language: 'es',
                    }"
                    :images_file_types="'jpg,svg,webp'"
                    :images_upload_url="route('blog_article_uploa_image_tiny')"
                />
                <InputError :message="form.errors.description_long" class="mt-2" />
            </div>
        </div>
        <div class="flex sm:flex-row flex-col">
            <label for="fileImage" class="mb-0 sm:w-1/4 sm:ltr:mr-2 rtl:ml-2">Imagen Principal *</label>
            <div class="flex-1">
                <!-- From Uiverse.io by ParasSalunke -->
                <div class="max-w-md rounded-lg overflow-hidden md:max-w-xl">
                    <div class="md:flex">
                        <div class="w-full">
                            <div
                                class="relative h-48 rounded-lg border-2 border-blue-200 bg-gray-50 flex justify-center items-center shadow-lg hover:shadow-xl transition-shadow duration-300 ease-in-out dark:bg-gray-800 dark:border-gray-700"
                            >
                                <div class="absolute flex flex-col items-center">
                                    <img
                                        alt="File Icon"
                                        class="mb-3 w-24 h-auto"
                                        :src="form.image_preview"
                                        ref="imagePreviewRef"
                                    />
                                    <span class="block text-gray-500 font-semibold" v-if="fileName">
                                        {{ fileName }}
                                    </span >
                                    <span class="block text-gray-400 font-normal mt-1">
                                        Haga clic para cargar imagen
                                    </span>
                                </div>

                                <input
                                    @change="loadFile"
                                    name="file"
                                    class="h-full w-full opacity-0 cursor-pointer"
                                    type="file"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <InputError :message="form.errors.image" class="mt-2" />
            </div>
        </div>
        <div class="flex sm:flex-row flex-col">
            <label for="txtaditional1" class="mb-0 sm:w-1/4 sm:ltr:mr-2 rtl:ml-2">Título del formulario paso 1</label>
            <div class="flex-1">
                <input v-model="form.additional_field1" id="txtaditional1" type="text" placeholder="Título del formulario" class="form-input flex-1" />
                <InputError :message="form.errors.additional_field1" class="mt-2" />
            </div>
        </div>
        <div class="flex sm:flex-row flex-col">
            <label for="txtaditional2" class="mb-0 sm:w-1/4 sm:ltr:mr-2 rtl:ml-2">Título del formulario paso 2</label>
            <div class="flex-1">
                <input v-model="form.additional_field2" id="txtaditional2" type="text" placeholder="adicional 2" class="form-input flex-1" />
                <InputError :message="form.errors.additional_field2" class="mt-2" />
            </div>
        </div>
        <div class="flex sm:flex-row flex-col">
            <label for="horizontalEmail" class="mb-0 sm:w-1/4 sm:ltr:mr-2 rtl:ml-2">Cursos disponibles que se visualizan en la landing (*)</label>
            <div class="flex-1">
                <Select
                    v-model:value="inputCourses"
                    mode="multiple"
                    style="width: 100%"
                    placeholder="Seleccionar Cursos"
                    option-label-prop="children"
                    class="ara-ant-select"
                >
                    <template v-for="course in courses">
                        <SelectOption :value="course.id" :label="course.description">
                            {{ course.description }}
                        </SelectOption>
                    </template>
                </Select>
                <InputError :message="form.errors.data_related" class="mt-2" />
            </div>
        </div>

        <div class="flex sm:flex-row flex-col">
            <label for="txtaditional2" class="mb-0 sm:w-1/4 sm:ltr:mr-2 rtl:ml-2">Duración de la promocion</label>
            <div class="flex-1">
                <flat-pickr v-model="dateRange" class="form-input" :config="rangeCalendar"></flat-pickr>
                <InputError :message="form.errors.additional_field2" class="mt-2" />
            </div>
        </div>

        <div class="flex sm:flex-row flex-col">
            <label for="txtaditional3" class="mb-0 sm:w-1/4 sm:ltr:mr-2 rtl:ml-2">Campo adicional 3</label>
            <div class="flex-1">
                <input v-model="form.additional_field3" id="txtaditional3" type="text" placeholder="adicional 3" class="form-input flex-1" />
                <InputError :message="form.errors.additional_field3" class="mt-2" />
            </div>
        </div>
        <div class="flex sm:flex-row flex-col items-center">
            <div class="sm:w-1/4 sm:ltr:mr-2 rtl:ml-2"></div>
            <div class="flex-1">
                <div v-if="page.props.flash.success" class="bg-green-500 text-white p-2 rounded mb-2">
                    {{ page.props.flash.success }}
                </div>
                <div v-if="page.props.flash.error" class="bg-red-500 text-white p-2 rounded mb-2">
                    {{ page.props.flash.error }}
                </div>
                <button type="submit" class="btn btn-primary" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    <svg v-show="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                    </svg>
                    Guardar cambios
                </button>
            </div>
        </div>
    </form>
</template>
