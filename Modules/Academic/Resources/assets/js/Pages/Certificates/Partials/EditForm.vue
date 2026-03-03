<script setup>
    import { useForm, Link } from '@inertiajs/vue3';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import Swal2 from 'sweetalert2';
    import { ref, watch, onMounted, onUnmounted } from 'vue';
    import iconMenu from '@/Components/vristo/icon/icon-menu.vue';
    import iconCaretsDown from '@/Components/vristo/icon/icon-carets-down.vue';
    import VueCollapsible from 'vue-height-collapsible/vue3';
    import iconCalendarDays from '@/Components/vristo/icon/icon-calendar-days.vue';
    import iconUserStudent from '@/Components/vristo/icon/icon-user-student.vue';
    import iconFont from '@/Components/vristo/icon/icon-font.vue';
    import iconQrcode from '@/Components/vristo/icon/icon-qrcode.vue';
    import iconInfoCircleTwo from '@/Components/vristo/icon/icon-info-circle-two.vue';
    import { Image } from 'ant-design-vue';
    import ImagePng from '@/Components/loader/image-png.vue';
    import switchMobinkakei from '@/Components/switch/switch-mobinkakei.vue';

    const props = defineProps({
        certificate: {
            type: Object,
            default: () => ({}),
        },
    });

    const imagePreviewLoading = ref(false);
    const xasset = assetUrl;

    const getImage = (path) => {
        return xasset + 'storage/' + path;
    };

    const form = useForm({
        id: props.certificate.id,
        action_type: null,
        course_id: null,
        for_module: props.certificate.for_module == 1 ? true : false,
        certificate_img: props.certificate.certificate_img_finished ?? props.certificate.certificate_img,
        certificate_img_preview: null,
        fontfamily_date: props.certificate.fontfamily_date,
        font_align_date: props.certificate.font_align_date,
        font_vertical_align_date: props.certificate.font_vertical_align_date,
        position_date_x: props.certificate.position_date_x,
        position_date_y: props.certificate.position_date_y,
        font_size_date: props.certificate.font_size_date,
        fontfamily_names: props.certificate.fontfamily_names,
        font_align_names: props.certificate.font_align_names,
        font_vertical_align_names: props.certificate.font_vertical_align_names,
        position_names_x: props.certificate.position_names_x,
        position_names_y: props.certificate.position_names_y,
        font_size_names: props.certificate.font_size_names,
        fontfamily_title: props.certificate.fontfamily_title,
        font_align_title: props.certificate.font_align_title,
        font_vertical_align_title: props.certificate.font_vertical_align_title,
        position_title_x: props.certificate.position_title_x,
        position_title_y: props.certificate.position_title_y,
        font_size_title: props.certificate.font_size_title,
        max_width_title: props.certificate.max_width_title,
        position_qr_x: props.certificate.position_qr_x,
        position_qr_y: props.certificate.position_qr_y,
        size_qr: props.certificate.size_qr,
        font_align_qr: props.certificate.font_align_qr,
        fontfamily_description: props.certificate.fontfamily_description,
        font_align_description: props.certificate.font_align_description,
        font_vertical_align_description: props.certificate.font_vertical_align_description,
        position_description_x: props.certificate.position_description_x,
        position_description_y: props.certificate.position_description_y,
        font_size_description: props.certificate.font_size_description,
        max_width_description: props.certificate.max_width_description,
        text_align_description: props.certificate.text_align_description || 'left',
        interspace_description: props.certificate.interspace_description,
        name_certificate: props.certificate.name_certificate,
        state: props.certificate.state == 1 ? true :  false,
        certificate_img_finished: props.certificate.certificate_img_finished,
        visible_image_qr: props.certificate.visible_image_qr == 1 ? true :  false,
        visible_description: props.certificate.visible_description == 1 ? true :  false,
        color_description: props.certificate.color_description,
        visible_title: props.certificate.visible_title == 1 ? true :  false,
        color_title: props.certificate.color_title,
        visible_names: props.certificate.visible_names == 1 ? true :  false,
        color_names: props.certificate.color_names,
        visible_date: props.certificate.visible_date == 1 ? true :  false,
        color_date: props.certificate.color_date,

        // Campos del reverso
        has_back: props.certificate.back_certificate_img ? true : false,
        has_reverse: props.certificate.has_reverse == 1 ? true : false,
        back_certificate_img: props.certificate.back_certificate_img,
        back_certificate_img_finished: props.certificate.back_certificate_img_finished,
        back_certificate_img_preview: null,
        back_description: props.certificate.back_description || '',
        back_content_show_manual: props.certificate.back_content_show_manual != 0 ? true : false,
        back_content_show_course: props.certificate.back_content_show_course != 0 ? true : false,
        back_content_show_module: props.certificate.back_content_show_module != 0 ? true : false,
        back_content_type: props.certificate.back_content_type || 'list',
        back_content_type_module: props.certificate.back_content_type_module || 'list',

        // Configuración del reverso
        back_fontfamily_date: props.certificate.back_fontfamily_date,
        back_font_align_date: props.certificate.back_font_align_date,
        back_font_vertical_align_date: props.certificate.back_font_vertical_align_date,
        back_position_date_x: props.certificate.back_position_date_x,
        back_position_date_y: props.certificate.back_position_date_y,
        back_font_size_date: props.certificate.back_font_size_date,
        back_color_date: props.certificate.back_color_date ?? '#000000',
        back_visible_date: props.certificate.back_visible_date == 1 ? true : false,

        back_fontfamily_names: props.certificate.back_fontfamily_names,
        back_font_align_names: props.certificate.back_font_align_names,
        back_font_vertical_align_names: props.certificate.back_font_vertical_align_names,
        back_position_names_x: props.certificate.back_position_names_x,
        back_position_names_y: props.certificate.back_position_names_y,
        back_font_size_names: props.certificate.back_font_size_names,
        back_color_names: props.certificate.back_color_names ?? '#000000',
        back_visible_names: props.certificate.back_visible_names == 1 ? true : false,

        back_fontfamily_title: props.certificate.back_fontfamily_title,
        back_font_align_title: props.certificate.back_font_align_title,
        back_font_vertical_align_title: props.certificate.back_font_vertical_align_title,
        back_position_title_x: props.certificate.back_position_title_x,
        back_position_title_y: props.certificate.back_position_title_y,
        back_font_size_title: props.certificate.back_font_size_title,
        back_max_width_title: props.certificate.back_max_width_title,
        back_color_title: props.certificate.back_color_title ?? '#000000',
        back_visible_title: props.certificate.back_visible_title == 1 ? true : false,

        back_fontfamily_description: props.certificate.back_fontfamily_description,
        back_font_align_description: props.certificate.back_font_align_description,
        back_font_vertical_align_description: props.certificate.back_font_vertical_align_description,
        back_position_description_x: props.certificate.back_position_description_x,
        back_position_description_y: props.certificate.back_position_description_y,
        back_font_size_description: props.certificate.back_font_size_description,
        back_max_width_description: props.certificate.back_max_width_description,
        back_text_align_description: props.certificate.back_text_align_description || 'left',
        back_color_description: props.certificate.back_color_description ?? '#000000',
        back_visible_description: props.certificate.back_visible_description == 1 ? true : false,

        // Contenido del curso del reverso
        back_fontfamily_course: props.certificate.back_fontfamily_course,
        back_font_align_course: props.certificate.back_font_align_course,
        back_font_vertical_align_course: props.certificate.back_font_vertical_align_course,
        back_position_course_x: props.certificate.back_position_course_x,
        back_position_course_y: props.certificate.back_position_course_y,
        back_font_size_course: props.certificate.back_font_size_course,
        back_max_width_course: props.certificate.back_max_width_course,
        back_color_course: props.certificate.back_color_course ?? '#000000',
        back_visible_course: props.certificate.back_visible_course == 1 ? true : false,

        // Contenido del módulo del reverso
        back_fontfamily_module: props.certificate.back_fontfamily_module,
        back_font_align_module: props.certificate.back_font_align_module,
        back_font_vertical_align_module: props.certificate.back_font_vertical_align_module,
        back_position_module_x: props.certificate.back_position_module_x,
        back_position_module_y: props.certificate.back_position_module_y,
        back_font_size_module: props.certificate.back_font_size_module,
        back_max_width_module: props.certificate.back_max_width_module,
        back_color_module: props.certificate.back_color_module ?? '#000000',
        back_visible_module: props.certificate.back_visible_module == 1 ? true : false,
    });

    // Watcher para validar contenido curso/módulo según tipo de certificado
    watch(() => form.for_module, (newValue) => {
        if (newValue) {
            // Si es certificado por módulo: deshabilitar curso, habilitar módulo
            form.back_visible_course = false;
        } else {
            // Si es certificado por curso: deshabilitar módulo, habilitar curso
            form.back_visible_module = false;
        }
    });

    const isShowChatMenu = ref(false);
    const accordians3 = ref(0);
    const activeTab = ref('front'); // 'front' | 'back' | 'general'

    const getPreviewImage = () => {
        if (activeTab.value === 'back') {
            return form.back_certificate_img_preview;
        }
        return form.certificate_img_preview;
    };

    // Cargar la imagen principal desde la URL
    const loadMainImage = (urlImage) => {
        form.certificate_img_preview = urlImage;
    };

    // Cargar la imagen del reverso desde la URL
    const loadBackImage = (path) => {
        if (path) {
            form.back_certificate_img_preview = getImage(path);
        }
    };

    // Manejar la subida de la imagen del reverso
    const handleBackImageUpload = (event) => {
        const file = event.target.files[0];
        if (file) {
            form.back_certificate_img = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                form.back_certificate_img_preview = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    };

    const removeBackImage = () => {
        form.back_certificate_img = null;
        form.back_certificate_img_preview = null;
    };



    // Cargar la imagen principal al montar el componente
    onMounted(() => {
        loadMainImage(getImage(form.certificate_img));
        // Cargar imagen del reverso si existe
        if (form.back_certificate_img) {
            let imgBack = form.back_certificate_img_finished ?? form.back_certificate_img;
            loadBackImage(imgBack);
        }
    });

    const updateCertificateData = (par) => {
        form.action_type = par;
        imagePreviewLoading.value = true;
        axios({
            method: 'post',
            url: route('aca_certificate_update_info'),
            data: form
        }).then((response) => {
            if (activeTab.value === 'back') {
                // Actualizar vista previa del reverso con la imagen generada
                if (response.data.image) {
                    form.back_certificate_img_preview = response.data.image;
                }
            } else {
                form.certificate_img_preview = response.data.image;
            }
        }).finally(() => {
            imagePreviewLoading.value = false;
        });
    }

    const updateCertificateStatus = () => {
        form.action_type = 99;
        form.processing = true;
        axios({
            method: 'post',
            url: route('aca_certificate_update_info'),
            data: form
        }).then((response) => {
            form.processing = false;
        }).finally(() => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se registró correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        });
    }

</script>
<template>
    <div>
        <div class="flex gap-5 relative " >
            <div
                class="panel p-4 flex-none max-w-xs w-full absolute xl:relative z-10 space-y-4 h-full hidden xl:block overflow-hidden dark:bg-gray-800"
                :class="isShowChatMenu && '!block !overflow-y-auto'"
            >
                <div class="flex justify-between items-center w-full">
                    <div class="flex items-center">
                        <h4 class="uppercase font-bold">Editar Certificado</h4>
                    </div>
                    <div>
                        <button type="button" class="xl:hidden hover:text-primary" @click="isShowChatMenu = !isShowChatMenu">
                            <icon-carets-down class="m-auto rotate-90" />
                        </button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'front' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray300'"
                        @click="activeTab = 'front'"
                    >
                        Anverso
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'back' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray300'"
                        @click="activeTab = 'back'"
                    >
                        Reverso
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'general' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray300'"
                        @click="activeTab = 'general'"
                    >
                        General
                    </button>
                </div>

                <div class="h-px w-full border-b border-[#e0e6ed] dark:border-[#1b2e4b]"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <InputLabel for="name_certificate">Nombre</InputLabel>
                        <TextInput
                            id="name_certificate"
                            v-model="form.name_certificate"
                            placeholder="Ejemplo: Modelo 1"
                            class="bg-gray-100"
                            :disabled="activeTab !== 'general'"
                        />
                        <InputError :message="form.errors.name_certificate" class="mt-1" />
                    </div>

                    <!-- Tab ANVERSO -->
                    <template v-if="activeTab === 'front'">
                    <div class="col-span-2 space-y-2 font-semibold">
                        <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                            <button
                                type="button"
                                class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                :class="{ '!text-primary': accordians3 === 1 }"
                                @click="accordians3 === 1 ? (accordians3 = null) : (accordians3 = 1)"
                            >
                                <icon-calendar-days class="mr-2 w-4 h-4" />
                                    Fecha
                                <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 1 }">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </div>
                            </button>
                            <vue-collapsible :isOpen="accordians3 === 1">
                                <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                    <div class="grid grid-cols-4 gap-4">
                                        <div class="col-span-2">
                                            <InputLabel for="fontfamily_date">Fuente utilizada</InputLabel>
                                            <select v-model="form.fontfamily_date" id="fontfamily_date" class="form-select text-white-dark">
                                                <option value="Pacifico-Regular.ttf">Pacifico-Regular</option>
                                                <option value="PlaywriteIN-Regular.ttf">PlaywriteIN-Regular</option>
                                                <option value="OLDENGL.TTF">OLDENGL</option>
                                                <option value="Poppins-Light.ttf">Poppins-Light.ttf</option>
                                                <option value="Intro-Headr.ttf">Intro-Headr.ttf</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_size_date">Tamaño de fuente</InputLabel>
                                            <TextInput
                                                id="font_size_date"
                                                v-model="form.font_size_date"
                                                placeholder="22, 23, etc..."
                                            />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_align_date">A. horizontal</InputLabel>
                                            <select v-model="form.font_align_date" id="font_align_date" class="form-select text-white-dark">
                                                <option value="left">left</option>
                                                <option value="center">center</option>
                                                <option value="right">right</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_vertical_align_date">A. vertical</InputLabel>
                                            <select v-model="form.font_vertical_align_date" id="font_vertical_align_date" class="form-select text-white-dark">
                                                <option value="top">top</option>
                                                <option value="center">center</option>
                                                <option value="bottom">bottom</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_date_x">Posición X</InputLabel>
                                            <TextInput
                                                id="position_date_x"
                                                v-model="form.position_date_x"
                                                placeholder="1, 2, etc.."
                                            />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_date_y">Posición Y</InputLabel>
                                            <TextInput
                                                id="position_date_y"
                                                v-model="form.position_date_y"
                                                placeholder="1, 2, etc.."
                                            />
                                        </div>
                                        <div class="col-span-4">
                                            <InputLabel for="color_date">Color</InputLabel>
                                            <TextInput
                                                id="color_date"
                                                v-model="form.color_date"
                                                type="color"
                                                placeholder="#000"
                                            />
                                        </div>
                                        <div class="col-span-4 flex items-center justify-between">
                                            <InputLabel for="visible_description" class="text-lg" value="Visible" />
                                            <input type="checkbox" id="visible_date" class="form-checkbox" v-model="form.visible_date"  />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(1)" class="btn btn-success">Guardar cambios y ver resultado</button>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>

                        <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                            <button
                                type="button"
                                class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                :class="{ '!text-primary': accordians3 === 2 }"
                                @click="accordians3 === 2 ? (accordians3 = null) : (accordians3 = 2)"
                            >
                                <icon-user-student class="w-4 h-4 mr-2" />
                                    Nombre del estudiante
                                <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 2 }">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </div>
                            </button>
                            <vue-collapsible :isOpen="accordians3 === 2">
                                <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                    <div class="grid grid-cols-4 gap-4">
                                        <div class="col-span-2">
                                            <InputLabel for="fontfamily_names">Fuente utilizada</InputLabel>
                                            <select v-model="form.fontfamily_names" id="fontfamily_names" class="form-select text-white-dark">
                                                <option value="Pacifico-Regular.ttf">Pacifico-Regular</option>
                                                <option value="PlaywriteIN-Regular.ttf">PlaywriteIN-Regular</option>
                                                <option value="OLDENGL.TTF">OLDENGL</option>
                                                <option value="Poppins-Light.ttf">Poppins-Light.ttf</option>
                                                <option value="Intro-Headr.ttf">Intro-Headr.ttf</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_size_names">Tamaño de fuente</InputLabel>
                                            <TextInput
                                                id="font_size_names"
                                                v-model="form.font_size_names"
                                                placeholder="22, 23, etc..."
                                            />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_align_names">A. horizontal</InputLabel>
                                            <select v-model="form.font_align_names" id="font_align_names" class="form-select text-white-dark">
                                                <option value="left">left</option>
                                                <option value="center">center</option>
                                                <option value="right">right</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_vertical_align_names">A. vertical</InputLabel>
                                            <select v-model="form.font_vertical_align_names" id="font_vertical_align_names" class="form-select text-white-dark">
                                                <option value="top">top</option>
                                                <option value="center">center</option>
                                                <option value="bottom">bottom</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_names_x">Posición X</InputLabel>
                                            <TextInput
                                                id="position_names_x"
                                                v-model="form.position_names_x"
                                                placeholder="1, 2, etc.."
                                            />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_names_y">Posición Y</InputLabel>
                                            <TextInput
                                                id="position_names_y"
                                                v-model="form.position_names_y"
                                                placeholder="1, 2, etc.."
                                            />
                                        </div>
                                        <div class="col-span-4">
                                            <InputLabel for="color_names">Color</InputLabel>
                                            <TextInput
                                                id="color_names"
                                                v-model="form.color_names"
                                                type="color"
                                                placeholder="#000"
                                            />
                                        </div>
                                        <div class="col-span-4 flex items-center justify-between">
                                            <InputLabel for="visible_description" class="text-lg" value="Visible" />
                                            <input type="checkbox" id="visible_names" class="form-checkbox" v-model="form.visible_names"  />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(2)" class="btn btn-success">Guardar cambios y ver resultado</button>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>

                        <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                            <button
                                type="button"
                                class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                :class="{ '!text-primary': accordians3 === 3 }"
                                @click="accordians3 === 3 ? (accordians3 = null) : (accordians3 = 3)"
                            >
                                <icon-font class="w-4 h-4 mr-2" />
                                Título
                                <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 3 }">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </div>
                            </button>
                            <vue-collapsible :isOpen="accordians3 === 3">
                                <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                    <div class="grid grid-cols-4 gap-4">
                                        <div class="col-span-2">
                                            <InputLabel for="fontfamily_title">Fuente utilizada</InputLabel>
                                            <select v-model="form.fontfamily_title" id="fontfamily_title" class="form-select text-white-dark">
                                                <option value="Pacifico-Regular.ttf">Pacifico-Regular</option>
                                                <option value="PlaywriteIN-Regular.ttf">PlaywriteIN-Regular</option>
                                                <option value="OLDENGL.TTF">OLDENGL</option>
                                                <option value="Poppins-Light.ttf">Poppins-Light.ttf</option>
                                                <option value="Intro-Headr.ttf">Intro-Headr.ttf</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_size_title">Tamaño de fuente</InputLabel>
                                            <TextInput
                                                id="font_size_title"
                                                v-model="form.font_size_title"
                                                placeholder="22, 23, etc..."
                                            />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_align_title">A. horizontal</InputLabel>
                                            <select v-model="form.font_align_title" id="font_align_title" class="form-select text-white-dark">
                                                <option value="left">left</option>
                                                <option value="center">center</option>
                                                <option value="right">right</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_vertical_align_title">A. vertical</InputLabel>
                                            <select v-model="form.font_vertical_align_title" id="font_vertical_align_title" class="form-select text-white-dark">
                                                <option value="top">top</option>
                                                <option value="center">center</option>
                                                <option value="bottom">bottom</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_title_x">Posición X</InputLabel>
                                            <TextInput
                                                id="position_title_x"
                                                v-model="form.position_title_x"
                                                placeholder="1, 2, etc.."
                                            />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_title_y">Posición Y</InputLabel>
                                            <TextInput
                                                id="position_title_y"
                                                v-model="form.position_title_y"
                                                placeholder="1, 2, etc.."
                                            />
                                        </div>
                                        <div class="col-span-4">
                                            <InputLabel for="max_width_title">Ancho máximo en píxeles</InputLabel>
                                            <TextInput
                                                id="max_width_title"
                                                v-model="form.max_width_title"
                                                placeholder="900"
                                            />
                                        </div>
                                        <div class="col-span-4">
                                            <InputLabel for="color_title">Color</InputLabel>
                                            <TextInput
                                                id="color_title"
                                                v-model="form.color_title"
                                                type="color"
                                                placeholder="#000"
                                            />
                                        </div>
                                        <div class="col-span-4 flex items-center justify-between">
                                            <InputLabel for="visible_description" class="text-lg" value="Visible" />
                                            <input type="checkbox" id="visible_title" class="form-checkbox" v-model="form.visible_title"  />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(3)" class="btn btn-success">Guardar cambios y ver resultado</button>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>
                        <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                            <button
                                type="button"
                                class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                :class="{ '!text-primary': accordians3 === 4 }"
                                @click="accordians3 === 4 ? (accordians3 = null) : (accordians3 = 4)"
                            >
                                <icon-qrcode class="w-4 h-4 mr-2" />
                                Imagen QR
                                <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 4 }">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </div>
                            </button>
                            <vue-collapsible :isOpen="accordians3 === 4">
                                <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                    <div class="grid grid-cols-4 gap-4">
                                        <div class="col-span-2">
                                            <InputLabel for="size_qr">Tamaño</InputLabel>
                                            <TextInput
                                                id="size_qr"
                                                v-model="form.size_qr"
                                                placeholder="22, 23, etc..."
                                            />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_align_qr">A. horizontal</InputLabel>
                                            <select v-model="form.font_align_qr" id="font_align_qr" class="form-select text-white-dark">
                                                <option value="top-left">top-left</option>
                                                <option value="top-center">top-center</option>
                                                <option value="top-right">top-right</option>
                                                <option value="bottom-left">bottom-left</option>
                                                <option value="bottom-center">bottom-center</option>
                                                <option value="bottom-right">bottom-right</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_qr_x">Posición X</InputLabel>
                                            <TextInput
                                                id="position_qr_x"
                                                v-model="form.position_qr_x"
                                                placeholder="1, 2, etc.."
                                            />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_qr_y">Posición Y</InputLabel>
                                            <TextInput
                                                id="position_qr_y"
                                                v-model="form.position_qr_y"
                                                placeholder="1, 2, etc.."
                                            />
                                        </div>
                                        <div class="col-span-4 flex items-center justify-between">
                                            <InputLabel for="visible_description" class="text-lg" value="Visible" />
                                            <input type="checkbox" id="visible_image_qr" class="form-checkbox" v-model="form.visible_image_qr"  />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(4)" class="btn btn-success">Guardar cambios y ver resultado</button>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>
                        <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                            <button
                                type="button"
                                class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                :class="{ '!text-primary': accordians3 === 5 }"
                                @click="accordians3 === 5 ? (accordians3 = null) : (accordians3 = 5)"
                            >
                                <icon-info-circle-two class="w-4 h-4 mr-2" />
                                Descripción
                                <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 5 }">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </div>
                            </button>
                            <vue-collapsible :isOpen="accordians3 === 5">
                                <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                    <div class="grid grid-cols-4 gap-4">
                                        <div class="col-span-2">
                                            <InputLabel for="fontfamily_description">Fuente utilizada</InputLabel>
                                            <select v-model="form.fontfamily_description" id="fontfamily_description" class="form-select text-white-dark">
                                                <option value="Pacifico-Regular.ttf">Pacifico-Regular</option>
                                                <option value="PlaywriteIN-Regular.ttf">PlaywriteIN-Regular</option>
                                                <option value="OLDENGL.TTF">OLDENGL</option>
                                                <option value="Poppins-Light.ttf">Poppins-Light.ttf</option>
                                                <option value="Intro-Headr.ttf">Intro-Headr.ttf</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_size_description">Tamaño de fuente</InputLabel>
                                            <TextInput
                                                id="font_size_description"
                                                v-model="form.font_size_description"
                                                placeholder="22, 23, etc..."
                                            />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_align_description">A. horizontal</InputLabel>
                                            <select v-model="form.font_align_description" id="font_align_description" class="form-select text-white-dark">
                                                <option value="left">left</option>
                                                <option value="center">center</option>
                                                <option value="right">right</option>
                                                <option value="justify">justify</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_vertical_align_description">A. vertical</InputLabel>
                                            <select v-model="form.font_vertical_align_description" id="font_vertical_align_description" class="form-select text-white-dark">
                                                <option value="top">top</option>
                                                <option value="center">center</option>
                                                <option value="bottom">bottom</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_description_x">Posición X</InputLabel>
                                            <TextInput
                                                id="position_description_x"
                                                v-model="form.position_description_x"
                                                placeholder="1, 2, etc.."
                                            />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_description_y">Posición Y</InputLabel>
                                            <TextInput
                                                id="position_description_y"
                                                v-model="form.position_description_y"
                                                placeholder="1, 2, etc.."
                                            />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="max_width_description">Ancho máximo en píxeles</InputLabel>
                                            <TextInput
                                                id="max_width_description"
                                                v-model="form.max_width_description"
                                                placeholder="800"
                                            />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="text_align_description">Alineación Texto</InputLabel>
                                            <select v-model="form.text_align_description" id="text_align_description" class="form-select text-white-dark">
                                                <option value="left">Izquierda</option>
                                                <option value="center">Centrado</option>
                                                <option value="right">Derecha</option>
                                                <option value="justify">Justificado</option>
                                            </select>
                                        </div>
                                        <!-- <div class="col-span-2">
                                            <InputLabel for="interspace_description">Espaciado entre líneas</InputLabel>
                                            <TextInput
                                                id="interspace_description"
                                                v-model="form.interspace_description"
                                                placeholder="2.5"
                                            />
                                        </div> -->
                                        <div class="col-span-4">
                                            <InputLabel for="color_description">Color</InputLabel>
                                            <TextInput
                                                id="color_description"
                                                v-model="form.color_description"
                                                type="color"
                                                placeholder="#000"
                                            />
                                        </div>
                                        <div class="col-span-4 flex items-center justify-between">
                                            <InputLabel for="visible_description" class="text-lg" value="Visible" />
                                            <input type="checkbox" id="visible_description" class="form-checkbox" v-model="form.visible_description"  />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(5)" class="btn btn-success">Guardar cambios y ver resultado</button>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>
                    </div>
                    </template>

                    <!-- Tab REVERSO -->
                    <template v-if="activeTab === 'back'">
                        <!-- Toggle Habilitar Reverso -->
                        <div class="col-span-2 mb-4">
                            <label class="flex items-center cursor-pointer">
                                <input v-model="form.has_back" type="checkbox" class="form-checkbox text-primary" />
                                <span class="ltr:ml-2 rtl:mr-2 text-white-dark font-medium">Habilitar certificado con reverso</span>
                            </label>
                        </div>

                        <!-- Toggle Incluir Reverso en Descarga -->
                        <div class="col-span-2 mb-4" v-if="form.has_back">
                            <label class="flex items-center cursor-pointer">
                                <input v-model="form.has_reverse" type="checkbox" class="form-checkbox text-primary" />
                                <span class="ltr:ml-2 rtl:mr-2 text-white-dark font-medium">Incluir reverso al descargar (ZIP)</span>
                            </label>
                        </div>

                        <template v-if="form.has_back">
                            <!-- Imagen Reverso -->
                            <!-- <div class="col-span-2 mb-4">
                                <label class="text-sm font-medium text-white-dark mb-2 block">Imagen Reverso</label>
                                <div class="flex items-center justify-center w-full">
                                    <label for="dropzone-back-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-800 hover:bg-gray-700 dark:border-gray-600">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg class="w-8 h-8 mb-4 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                            </svg>
                                            <p class="mb-2 text-sm text-gray-400"><span class="font-semibold">Subir imagen</span> del reverso</p>
                                        </div>
                                        <input @change="handleBackImageUpload" id="dropzone-back-file" type="file" class="hidden" />
                                    </label>
                                </div>
                            </div> -->

                            <!-- Preview imagen reverso -->
                            <!-- <div class="col-span-2 mb-4" v-if="form.back_certificate_img">
                                <div class="relative inline-block">
                                    <img :src="getImage(form.back_certificate_img)" class="h-20 rounded border border-gray-600" />
                                    <button
                                        type="button"
                                        @click="removeBackImage"
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                                    >
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            </div> -->

                            <!-- Contenido del Reverso (configurado en cada sección con opción Visible) -->
                            <div class="col-span-2 mb-4">
                                <InputLabel class="mb-2 text-white-dark">Contenido del Reverso</InputLabel>
                                <p class="text-xs text-gray-400">Cada sección de configuración abajo tiene la opción "Visible" para mostrar u ocultar el contenido.</p>
                            </div>

                            <!-- Descripción manual editable -->
                            <div class="col-span-2 mb-4" v-if="form.back_content_show_manual">
                                <InputLabel for="back_description" class="text-white-dark">Descripción del Reverso</InputLabel>
                                <textarea
                                    id="back_description"
                                    v-model="form.back_description"
                                    class="form-textarea"
                                    rows="4"
                                    placeholder="Escriba el contenido que desea mostrar en el reverso..."
                                ></textarea>
                            </div>

                            <!-- CONFIGURACIÓN DEL REVERSO -->
                            <div class="col-span-2 space-y-2 font-semibold mt-6">
                                <h5 class="text-white-dark text-sm uppercase mb-2">Configuración del Reverso</h5>

                                <!-- Fecha del Reverso -->
                                <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                    <button
                                        type="button"
                                        class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                        :class="{ '!text-primary': accordians3 === 21 }"
                                        @click="accordians3 === 21 ? (accordians3 = null) : (accordians3 = 21)"
                                    >
                                        <icon-calendar-days class="mr-2 w-4 h-4" />
                                        Fecha
                                        <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 21 }">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        </div>
                                    </button>
                                    <vue-collapsible :isOpen="accordians3 === 21">
                                        <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                            <div class="grid grid-cols-4 gap-4">
                                                <div class="col-span-2">
                                                    <InputLabel for="back_fontfamily_date">Fuente utilizada</InputLabel>
                                                    <select v-model="form.back_fontfamily_date" id="back_fontfamily_date" class="form-select text-white-dark">
                                                        <option value="Pacifico-Regular.ttf">Pacifico-Regular</option>
                                                        <option value="PlaywriteIN-Regular.ttf">PlaywriteIN-Regular</option>
                                                        <option value="OLDENGL.TTF">OLDENGL</option>
                                                        <option value="Poppins-Light.ttf">Poppins-Light.ttf</option>
                                                        <option value="Intro-Headr.ttf">Intro-Headr.ttf</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_size_date">Tamaño de fuente</InputLabel>
                                                    <TextInput id="back_font_size_date" v-model="form.back_font_size_date" placeholder="22, 23, etc..." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_align_date">A. horizontal</InputLabel>
                                                    <select v-model="form.back_font_align_date" id="back_font_align_date" class="form-select text-white-dark">
                                                        <option value="left">left</option>
                                                        <option value="center">center</option>
                                                        <option value="right">right</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_vertical_align_date">A. vertical</InputLabel>
                                                    <select v-model="form.back_font_vertical_align_date" id="back_font_vertical_align_date" class="form-select text-white-dark">
                                                        <option value="top">top</option>
                                                        <option value="center">center</option>
                                                        <option value="bottom">bottom</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_position_date_x">Posición X</InputLabel>
                                                    <TextInput id="back_position_date_x" v-model="form.back_position_date_x" placeholder="1, 2, etc.." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_position_date_y">Posición Y</InputLabel>
                                                    <TextInput id="back_position_date_y" v-model="form.back_position_date_y" placeholder="1, 2, etc.." />
                                                </div>
                                                <div class="col-span-4">
                                                    <InputLabel for="back_color_date">Color</InputLabel>
                                                    <TextInput id="back_color_date" v-model="form.back_color_date" type="color" placeholder="#000" />
                                                </div>
                                                <div class="col-span-4 flex items-center justify-between">
                                                    <InputLabel for="back_visible_date" class="text-lg" value="Visible" />
                                                    <input type="checkbox" id="back_visible_date" class="form-checkbox" v-model="form.back_visible_date" />
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-end mt-4">
                                                <button @click="updateCertificateData(6)" class="btn btn-success">Guardar cambios y ver resultado</button>
                                            </div>
                                        </div>
                                    </vue-collapsible>
                                </div>

                                <!-- Nombre del estudiante del Reverso -->
                                <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                    <button
                                        type="button"
                                        class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                        :class="{ '!text-primary': accordians3 === 22 }"
                                        @click="accordians3 === 22 ? (accordians3 = null) : (accordians3 = 22)"
                                    >
                                        <icon-user-student class="w-4 h-4 mr-2" />
                                        Nombre del estudiante
                                        <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 22 }">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        </div>
                                    </button>
                                    <vue-collapsible :isOpen="accordians3 === 22">
                                        <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                            <div class="grid grid-cols-4 gap-4">
                                                <div class="col-span-2">
                                                    <InputLabel for="back_fontfamily_names">Fuente utilizada</InputLabel>
                                                    <select v-model="form.back_fontfamily_names" id="back_fontfamily_names" class="form-select text-white-dark">
                                                        <option value="Pacifico-Regular.ttf">Pacifico-Regular</option>
                                                        <option value="PlaywriteIN-Regular.ttf">PlaywriteIN-Regular</option>
                                                        <option value="OLDENGL.TTF">OLDENGL</option>
                                                        <option value="Poppins-Light.ttf">Poppins-Light.ttf</option>
                                                        <option value="Intro-Headr.ttf">Intro-Headr.ttf</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_size_names">Tamaño de fuente</InputLabel>
                                                    <TextInput id="back_font_size_names" v-model="form.back_font_size_names" placeholder="22, 23, etc..." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_align_names">A. horizontal</InputLabel>
                                                    <select v-model="form.back_font_align_names" id="back_font_align_names" class="form-select text-white-dark">
                                                        <option value="left">left</option>
                                                        <option value="center">center</option>
                                                        <option value="right">right</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_vertical_align_names">A. vertical</InputLabel>
                                                    <select v-model="form.back_font_vertical_align_names" id="back_font_vertical_align_names" class="form-select text-white-dark">
                                                        <option value="top">top</option>
                                                        <option value="center">center</option>
                                                        <option value="bottom">bottom</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_position_names_x">Posición X</InputLabel>
                                                    <TextInput id="back_position_names_x" v-model="form.back_position_names_x" placeholder="1, 2, etc.." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_position_names_y">Posición Y</InputLabel>
                                                    <TextInput id="back_position_names_y" v-model="form.back_position_names_y" placeholder="1, 2, etc.." />
                                                </div>
                                                <div class="col-span-4">
                                                    <InputLabel for="back_color_names">Color</InputLabel>
                                                    <TextInput id="back_color_names" v-model="form.back_color_names" type="color" placeholder="#000" />
                                                </div>
                                                <div class="col-span-4 flex items-center justify-between">
                                                    <InputLabel for="back_visible_names" class="text-lg" value="Visible" />
                                                    <input type="checkbox" id="back_visible_names" class="form-checkbox" v-model="form.back_visible_names" />
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-end mt-4">
                                                <button @click="updateCertificateData(7)" class="btn btn-success">Guardar cambios y ver resultado</button>
                                            </div>
                                        </div>
                                    </vue-collapsible>
                                </div>

                                <!-- Título del Reverso -->
                                <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                    <button
                                        type="button"
                                        class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                        :class="{ '!text-primary': accordians3 === 23 }"
                                        @click="accordians3 === 23 ? (accordians3 = null) : (accordians3 = 23)"
                                    >
                                        <icon-font class="w-4 h-4 mr-2" />
                                        Título
                                        <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 23 }">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        </div>
                                    </button>
                                    <vue-collapsible :isOpen="accordians3 === 23">
                                        <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                            <div class="grid grid-cols-4 gap-4">
                                                <div class="col-span-2">
                                                    <InputLabel for="back_fontfamily_title">Fuente utilizada</InputLabel>
                                                    <select v-model="form.back_fontfamily_title" id="back_fontfamily_title" class="form-select text-white-dark">
                                                        <option value="Pacifico-Regular.ttf">Pacifico-Regular</option>
                                                        <option value="PlaywriteIN-Regular.ttf">PlaywriteIN-Regular</option>
                                                        <option value="OLDENGL.TTF">OLDENGL</option>
                                                        <option value="Poppins-Light.ttf">Poppins-Light.ttf</option>
                                                        <option value="Intro-Headr.ttf">Intro-Headr.ttf</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_size_title">Tamaño de fuente</InputLabel>
                                                    <TextInput id="back_font_size_title" v-model="form.back_font_size_title" placeholder="22, 23, etc..." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_align_title">A. horizontal</InputLabel>
                                                    <select v-model="form.back_font_align_title" id="back_font_align_title" class="form-select text-white-dark">
                                                        <option value="left">left</option>
                                                        <option value="center">center</option>
                                                        <option value="right">right</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_vertical_align_title">A. vertical</InputLabel>
                                                    <select v-model="form.back_font_vertical_align_title" id="back_font_vertical_align_title" class="form-select text-white-dark">
                                                        <option value="top">top</option>
                                                        <option value="center">center</option>
                                                        <option value="bottom">bottom</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_position_title_x">Posición X</InputLabel>
                                                    <TextInput id="back_position_title_x" v-model="form.back_position_title_x" placeholder="1, 2, etc.." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_position_title_y">Posición Y</InputLabel>
                                                    <TextInput id="back_position_title_y" v-model="form.back_position_title_y" placeholder="1, 2, etc.." />
                                                </div>
                                                <div class="col-span-4">
                                                    <InputLabel for="back_max_width_title">Ancho máximo en píxeles</InputLabel>
                                                    <TextInput id="back_max_width_title" v-model="form.back_max_width_title" placeholder="900" />
                                                </div>
                                                <div class="col-span-4">
                                                    <InputLabel for="back_color_title">Color</InputLabel>
                                                    <TextInput id="back_color_title" v-model="form.back_color_title" type="color" placeholder="#000" />
                                                </div>
                                                <div class="col-span-4 flex items-center justify-between">
                                                    <InputLabel for="back_visible_title" class="text-lg" value="Visible" />
                                                    <input type="checkbox" id="back_visible_title" class="form-checkbox" v-model="form.back_visible_title" />
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-end mt-4">
                                                <button @click="updateCertificateData(8)" class="btn btn-success">Guardar cambios y ver resultado</button>
                                            </div>
                                        </div>
                                    </vue-collapsible>
                                </div>

                                <!-- Descripción Manual del Reverso -->
                                <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                    <button
                                        type="button"
                                        class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                        :class="{ '!text-primary': accordians3 === 24 }"
                                        @click="accordians3 === 24 ? (accordians3 = null) : (accordians3 = 24)"
                                    >
                                        <icon-info-circle-two class="w-4 h-4 mr-2" />
                                        Descripción Manual
                                        <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 24 }">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        </div>
                                    </button>
                                    <vue-collapsible :isOpen="accordians3 === 24">
                                        <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                            <div class="grid grid-cols-4 gap-4">
                                                <div class="col-span-2">
                                                    <InputLabel for="back_fontfamily_description">Fuente utilizada</InputLabel>
                                                    <select v-model="form.back_fontfamily_description" id="back_fontfamily_description" class="form-select text-white-dark">
                                                        <option value="Pacifico-Regular.ttf">Pacifico-Regular</option>
                                                        <option value="PlaywriteIN-Regular.ttf">PlaywriteIN-Regular</option>
                                                        <option value="OLDENGL.TTF">OLDENGL</option>
                                                        <option value="Poppins-Light.ttf">Poppins-Light.ttf</option>
                                                        <option value="Intro-Headr.ttf">Intro-Headr.ttf</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_size_description">Tamaño de fuente</InputLabel>
                                                    <TextInput id="back_font_size_description" v-model="form.back_font_size_description" placeholder="22, 23, etc..." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_align_description">A. horizontal</InputLabel>
                                                    <select v-model="form.back_font_align_description" id="back_font_align_description" class="form-select text-white-dark">
                                                        <option value="top">top</option>
                                                        <option value="center">center</option>
                                                        <option value="bottom">bottom</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_vertical_align_description">A. vertical</InputLabel>
                                                    <select v-model="form.back_font_vertical_align_description" id="back_font_vertical_align_description" class="form-select text-white-dark">
                                                        <option value="top">top</option>
                                                        <option value="center">center</option>
                                                        <option value="bottom">bottom</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_position_description_x">Posición X</InputLabel>
                                                    <TextInput id="back_position_description_x" v-model="form.back_position_description_x" placeholder="1, 2, etc.." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_position_description_y">Posición Y</InputLabel>
                                                    <TextInput id="back_position_description_y" v-model="form.back_position_description_y" placeholder="1, 2, etc.." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_max_width_description">Ancho máximo</InputLabel>
                                                    <TextInput id="back_max_width_description" v-model="form.back_max_width_description" placeholder="800" />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_text_align_description">Alineación Texto</InputLabel>
                                                    <select v-model="form.back_text_align_description" id="back_text_align_description" class="form-select text-white-dark">
                                                        <option value="left">Izquierda</option>
                                                        <option value="center">Centrado</option>
                                                        <option value="right">Derecha</option>
                                                        <option value="justify">Justificado</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-4">
                                                    <InputLabel for="back_color_description">Color</InputLabel>
                                                    <TextInput id="back_color_description" v-model="form.back_color_description" type="color" placeholder="#000" />
                                                </div>
                                                <div class="col-span-4 flex items-center justify-between">
                                                    <InputLabel for="back_visible_description" class="text-lg" value="Visible" />
                                                    <input type="checkbox" id="back_visible_description" class="form-checkbox" v-model="form.back_visible_description" />
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-end mt-4">
                                                <button @click="updateCertificateData(9)" class="btn btn-success">Guardar cambios y ver resultado</button>
                                            </div>
                                        </div>
                                    </vue-collapsible>
                                </div>

                                <!-- Contenido del Curso del Reverso -->
                                <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                    <button
                                        type="button"
                                        class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                        :class="{ '!text-primary': accordians3 === 25 }"
                                        @click="accordians3 === 25 ? (accordians3 = null) : (accordians3 = 25)"
                                    >
                                        <icon-info-circle-two class="w-4 h-4 mr-2" />
                                        Contenido del Curso
                                        <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 25 }">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        </div>
                                    </button>
                                    <vue-collapsible :isOpen="accordians3 === 25">
                                        <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                            <div class="grid grid-cols-4 gap-4">
                                                <div class="col-span-2">
                                                    <InputLabel for="back_fontfamily_course">Fuente utilizada</InputLabel>
                                                    <select v-model="form.back_fontfamily_course" id="back_fontfamily_course" class="form-select text-white-dark">
                                                        <option value="Pacifico-Regular.ttf">Pacifico-Regular</option>
                                                        <option value="PlaywriteIN-Regular.ttf">PlaywriteIN-Regular</option>
                                                        <option value="OLDENGL.TTF">OLDENGL</option>
                                                        <option value="Poppins-Light.ttf">Poppins-Light.ttf</option>
                                                        <option value="Intro-Headr.ttf">Intro-Headr.ttf</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_size_course">Tamaño de fuente</InputLabel>
                                                    <TextInput id="back_font_size_course" v-model="form.back_font_size_course" placeholder="22, 23, etc..." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_align_course">A. horizontal</InputLabel>
                                                    <select v-model="form.back_font_align_course" id="back_font_align_course" class="form-select text-white-dark">
                                                        <option value="left">left</option>
                                                        <option value="center">center</option>
                                                        <option value="right">right</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_vertical_align_course">A. vertical</InputLabel>
                                                    <select v-model="form.back_font_vertical_align_course" id="back_font_vertical_align_course" class="form-select text-white-dark">
                                                        <option value="top">top</option>
                                                        <option value="center">center</option>
                                                        <option value="bottom">bottom</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_position_course_x">Posición X</InputLabel>
                                                    <TextInput id="back_position_course_x" v-model="form.back_position_course_x" placeholder="1, 2, etc.." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_position_course_y">Posición Y</InputLabel>
                                                    <TextInput id="back_position_course_y" v-model="form.back_position_course_y" placeholder="1, 2, etc.." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_max_width_course">Ancho máximo</InputLabel>
                                                    <TextInput id="back_max_width_course" v-model="form.back_max_width_course" placeholder="800" />
                                                </div>
                                                <div class="col-span-4">
                                                    <InputLabel for="back_color_course">Color</InputLabel>
                                                    <TextInput id="back_color_course" v-model="form.back_color_course" type="color" placeholder="#000" />
                                                </div>
                                                <div class="col-span-4">
                                                    <InputLabel for="back_content_type">Tipo de contenido</InputLabel>
                                                    <select v-model="form.back_content_type" id="back_content_type" class="form-select text-white-dark" :disabled="form.for_module">
                                                        <option value="list">Listado</option>
                                                        <option value="table">Tabla de contenido</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-4 flex items-center justify-between">
                                                    <InputLabel for="back_visible_course" class="text-lg" value="Visible" />
                                                    <input type="checkbox" id="back_visible_course" class="form-checkbox" v-model="form.back_visible_course" :disabled="form.for_module" />
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-end mt-4">
                                                <button @click="updateCertificateData(12)" class="btn btn-success">Guardar cambios y ver resultado</button>
                                            </div>
                                        </div>
                                    </vue-collapsible>
                                </div>

                                <!-- Contenido del Módulo del Reverso -->
                                <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                    <button
                                        type="button"
                                        class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                        :class="{ '!text-primary': accordians3 === 26 }"
                                        @click="accordians3 === 26 ? (accordians3 = null) : (accordians3 = 26)"
                                    >
                                        <icon-info-circle-two class="w-4 h-4 mr-2" />
                                        Contenido del Módulo
                                        <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 26 }">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        </div>
                                    </button>
                                    <vue-collapsible :isOpen="accordians3 === 26">
                                        <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                            <div class="grid grid-cols-4 gap-4">
                                                <div class="col-span-2">
                                                    <InputLabel for="back_fontfamily_module">Fuente utilizada</InputLabel>
                                                    <select v-model="form.back_fontfamily_module" id="back_fontfamily_module" class="form-select text-white-dark">
                                                        <option value="Pacifico-Regular.ttf">Pacifico-Regular</option>
                                                        <option value="PlaywriteIN-Regular.ttf">PlaywriteIN-Regular</option>
                                                        <option value="OLDENGL.TTF">OLDENGL</option>
                                                        <option value="Poppins-Light.ttf">Poppins-Light.ttf</option>
                                                        <option value="Intro-Headr.ttf">Intro-Headr.ttf</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_size_module">Tamaño de fuente</InputLabel>
                                                    <TextInput id="back_font_size_module" v-model="form.back_font_size_module" placeholder="22, 23, etc..." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_align_module">A. horizontal</InputLabel>
                                                    <select v-model="form.back_font_align_module" id="back_font_align_module" class="form-select text-white-dark">
                                                        <option value="left">left</option>
                                                        <option value="center">center</option>
                                                        <option value="right">right</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_font_vertical_align_module">A. vertical</InputLabel>
                                                    <select v-model="form.back_font_vertical_align_module" id="back_font_vertical_align_module" class="form-select text-white-dark">
                                                        <option value="top">top</option>
                                                        <option value="center">center</option>
                                                        <option value="bottom">bottom</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_position_module_x">Posición X</InputLabel>
                                                    <TextInput id="back_position_module_x" v-model="form.back_position_module_x" placeholder="1, 2, etc.." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_position_module_y">Posición Y</InputLabel>
                                                    <TextInput id="back_position_module_y" v-model="form.back_position_module_y" placeholder="1, 2, etc.." />
                                                </div>
                                                <div class="col-span-2">
                                                    <InputLabel for="back_max_width_module">Ancho máximo</InputLabel>
                                                    <TextInput id="back_max_width_module" v-model="form.back_max_width_module" placeholder="800" />
                                                </div>
                                                <div class="col-span-4">
                                                    <InputLabel for="back_color_module">Color</InputLabel>
                                                    <TextInput id="back_color_module" v-model="form.back_color_module" type="color" placeholder="#000" />
                                                </div>
                                                <div class="col-span-4">
                                                    <InputLabel for="back_content_type_module">Tipo de contenido</InputLabel>
                                                    <select v-model="form.back_content_type_module" id="back_content_type_module" class="form-select text-white-dark" :disabled="!form.for_module">
                                                        <option value="list">Listado</option>
                                                        <option value="table">Tabla de contenido</option>
                                                    </select>
                                                </div>
                                                <div class="col-span-4 flex items-center justify-between">
                                                    <InputLabel for="back_visible_module" class="text-lg" value="Visible" />
                                                    <input type="checkbox" id="back_visible_module" class="form-checkbox" v-model="form.back_visible_module" :disabled="!form.for_module" />
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-end mt-4">
                                                <button @click="updateCertificateData(13)" class="btn btn-success">Guardar cambios y ver resultado</button>
                                            </div>
                                        </div>
                                    </vue-collapsible>
                                </div>
                            </div>
                        </template>
                    </template>

                    <!-- Tab GENERAL -->
                    <template v-if="activeTab === 'general'">
                    <!-- Certificado para Módulos -->
                    <div class="col-span-2 mb-4">
                        <label class="flex items-center cursor-pointer">
                            <input v-model="form.for_module" type="checkbox" class="form-checkbox text-primary" />
                            <span class="ltr:ml-2 rtl:mr-2 text-white-dark font-medium">Certificado para módulos</span>
                        </label>
                        <p class="text-xs text-gray-400 mt-1">Al descargar desde un módulo, mostrará el nombre del módulo</p>
                    </div>
                    <div class="col-span-2">
                        <label class="flex items-center cursor-pointer">
                            <input v-model="form.state" type="checkbox" class="form-checkbox" />
                            <span class="text-white-dark">Activo</span>
                        </label>
                    </div>
                    <div class="col-span-2">
                        <button @click="updateCertificateStatus" class="btn btn-primary w-full" type="button">
                            <svg v-show="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                            </svg>
                            Guardar datos principales
                        </button>
                    </div>
                    </template>
                </div>
            </div>
            <div
                class="bg-black/60 z-[5] w-full h-full absolute rounded-md hidden"
                :class="isShowChatMenu && '!block xl:!hidden'"
                @click="isShowChatMenu = !isShowChatMenu"
            ></div>
            <div class="panel flex-1 space-y-4 p-4">
                <div class="flex justify-between items-center w-full">
                    <div>
                        <button type="button" class="xl:hidden hover:text-primary" @click="isShowChatMenu = !isShowChatMenu">
                            <icon-menu />
                        </button>
                    </div>
                    <div class="flex items-center">
                        <h4 class="uppercase font-bold">
                            <span v-if="activeTab === 'front'">Vista previa del anverso</span>
                            <span v-else-if="activeTab === 'back'">Vista previa del reverso</span>
                            <span v-else>Vista previa</span>
                        </h4>
                    </div>
                </div>
                <div class="h-px w-full border-b border-[#e0e6ed] dark:border-[#1b2e4b]"></div>
                <div>
                    <template v-if="activeTab === 'back' && !form.back_certificate_img_preview">
                        <div class="flex flex-col items-center justify-center h-64 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 text-center px-4">
                                Suba una imagen para el reverso del certificado<br>
                                <span class="text-sm">en la sección "Reverso" del formulario</span>
                            </p>
                        </div>
                    </template>
                    <template v-else>
                        <image-png v-if="imagePreviewLoading" :alt="'Maximizar'" />
                        <Image v-else
                            :src="getPreviewImage()"
                            style="width: 100%; height: auto;"
                        />
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
