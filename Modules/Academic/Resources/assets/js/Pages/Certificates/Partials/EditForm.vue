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
    });

    const isShowChatMenu = ref(false);
    const accordians3 = ref(0);
    
    // Cargar la imagen principal desde la URL
    const loadMainImage = (urlImage) => {
        form.certificate_img_preview = urlImage;
    };



   

    // Cargar la imagen principal al montar el componente
    onMounted(() => {
        loadMainImage(getImage(form.certificate_img));
    });

    const updateCertificateData = (par) => {
        form.action_type = par;
        imagePreviewLoading.value = true;
        axios({
            method: 'post',
            url: route('aca_certificate_update_info'),
            data: form
        }).then((response) => {
            form.certificate_img_preview = response.data.image;
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
                        <h4 class="uppercase font-bold">Crear Certificado</h4>
                    </div>
                    <div>
                        <button type="button" class="xl:hidden hover:text-primary" @click="isShowChatMenu = !isShowChatMenu">
                            <icon-carets-down class="m-auto rotate-90" />
                        </button>
                    </div>
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
                            disabled
                        />
                        <InputError :message="form.errors.name_certificate" class="mt-1" />
                    </div>
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
                                        <div class="col-span-4">
                                            <switch-mobinkakei
                                                :title="'Visible'"
                                                :uId="'visible_date'"
                                                v-model="form.visible_date"
                                             />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(1)" class="btn btn-success">vista previa</button>
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
                                        <div class="col-span-4">
                                            <switch-mobinkakei
                                                :title="'Visible'"
                                                :uId="'visible_names'"
                                                v-model="form.visible_names"
                                             />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(2)" class="btn btn-success">vista previa</button>
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
                                        <div class="col-span-4">
                                            <switch-mobinkakei
                                                :title="'Visible'"
                                                :uId="'visible_title'"
                                                v-model="form.visible_title"
                                             />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(3)" class="btn btn-success">vista previa</button>
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
                                        <div class="col-span-4">
                                            <switch-mobinkakei
                                                :title="'Visible'"
                                                :uId="'visible_image_qr'"
                                                v-model="form.visible_image_qr"
                                             />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(4)" class="btn btn-success">vista previa</button>
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
                                            <InputLabel for="interspace_description">Espaciado entre líneas</InputLabel>
                                            <TextInput 
                                                id="interspace_description"
                                                v-model="form.interspace_description"
                                                placeholder="2.5"
                                            />
                                        </div>
                                        <div class="col-span-4">
                                            <InputLabel for="color_description">Color</InputLabel>
                                            <TextInput 
                                                id="color_description"
                                                v-model="form.color_description"
                                                type="color"
                                                placeholder="#000"
                                            />
                                        </div>
                                        <div class="col-span-4">
                                            <switch-mobinkakei
                                                :title="'Visible'"
                                                :uId="'visible_description'"
                                                v-model="form.visible_description"
                                             />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(5)" class="btn btn-success">vista previa</button>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>
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
                            Guardar
                        </button>
                    </div>
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
                        <h4 class="uppercase font-bold">Vista previa del certificado</h4>
                    </div>
                </div>
                <div class="h-px w-full border-b border-[#e0e6ed] dark:border-[#1b2e4b]"></div>
                <div>

                        <image-png v-if="imagePreviewLoading" :alt="'Maximizar'" /> 
                        <Image v-else 
                            :src="form.certificate_img_preview" 
                            style="width: 100%; height: auto;" 
                        />
                    
                </div>
            </div>
        </div>
    </div>
</template>
