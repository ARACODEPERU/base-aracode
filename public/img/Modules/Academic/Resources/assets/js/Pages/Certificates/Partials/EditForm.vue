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

    const props = defineProps({
        certificate: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        course_id: null,
        certificate_img: null,
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
    });

    const createCertificate = () => {
        form.post(route('aca_certificate_store'), {
            forceFormData: true,
            errorBag: 'createCertificate',
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
    };

    const xasset = assetUrl;

    const getImage = (path) => {
        return xasset + 'storage/' + path;
    };

    const isShowChatMenu = ref(false);
    const accordians3 = ref(0);
    const canvas = ref(null);
    const certificateImgPreview = ref(null);
    // Cargar la imagen principal desde la URL
    const loadMainImage = () => {
        form.certificate_img_preview = new Image();
        form.certificate_img_preview.src = getImage(props.certificate.certificate_img);
        form.certificate_img_preview.onload = () => {
            // Ajustar el tamaño del canvas al tamaño de la imagen
            canvas.value.width = form.certificate_img_preview.width;
            canvas.value.height = form.certificate_img_preview.height;
            drawCanvas();
        };
    };

    // Dibujar en el canvas
    const drawCanvas = () => {
        const ctx = canvas.value.getContext('2d');
        ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);

        // Dibujar la imagen principal en su tamaño original
        if (form.certificate_img_preview) {
            ctx.drawImage(
                form.certificate_img_preview,
                0,
                0,
                canvas.value.width, // Usar el ancho del canvas
                canvas.value.height // Usar el alto del canvas
            );
        }

        // Dibujar la fecha
        drawText(
            ctx,
            textDate,
            form.position_date_x,
            form.position_date_y,
            form.fontfamily_date,
            form.font_size_date,
            form.font_align_date,
            form.font_vertical_align_date
        );

        // Dibujar el nombre del estudiante
        drawText(
            ctx,
            textStudentName,
            form.position_names_x,
            form.position_names_y,
            form.fontfamily_names,
            form.font_size_names,
            form.font_align_names,
            form.font_vertical_align_names
        );

        // Dibujar el título
        //form.font_size_title,
        drawText(
            ctx,
            textTitle,
            form.position_title_x,
            form.position_title_y,
            form.fontfamily_title,
            16,
            form.font_align_title,
            form.font_vertical_align_title,
            form.max_width_title
        );

        // Dibujar la descripción
        drawText(
            ctx,
            textDescription,
            form.position_description_x,
            form.position_description_y,
            form.fontfamily_description,
            form.font_size_description,
            form.font_align_description,
            form.font_vertical_align_description,
            form.max_width_description
        );

        // Dibujar la imagen QR
        if (imageQrDefault) {
            const imgQr = new Image();
            imgQr.src = imageQrDefault;
            imgQr.onload = () => {
                ctx.drawImage(
                    imgQr,
                    form.position_qr_x,
                    form.position_qr_y,
                    form.size_qr,
                    form.size_qr
                );
            };
        }
    };

    const drawText = (ctx, text, x, y, font, fontSize, align, verticalAlign, maxWidth) => {
        ctx.font = `${fontSize}px ${font}`;
        ctx.textAlign = align;
        ctx.textBaseline = verticalAlign;
        ctx.fillStyle = '#000000'; // Color del texto

        if (maxWidth) {
            // Si hay un ancho máximo, ajusta el texto para que no lo exceda
            let lines = [];
            let currentLine = '';
            text.split(' ').forEach(word => {
                let testLine = currentLine + word + ' ';
                let metrics = ctx.measureText(testLine);
                if (metrics.width > maxWidth && currentLine !== '') {
                    lines.push(currentLine);
                    currentLine = word + ' ';
                } else {
                    currentLine = testLine;
                }
            });
            lines.push(currentLine);
            lines.forEach((line, index) => {
                ctx.fillText(line, x, y + (index * (fontSize + 5))); // Ajusta el espaciado entre líneas
            });
        } else {
            ctx.fillText(text, x, y);
        }
    };

    watch(() => [
            form.fontfamily_date,
            form.font_size_date,
            form.font_align_date,
            form.font_vertical_align_date,
            form.position_date_x,
            form.position_date_y,
            form.fontfamily_names,
            form.font_size_names,
            form.font_align_names,
            form.font_vertical_align_names,
            form.position_names_x,
            form.position_names_y,
            form.fontfamily_title,
            form.font_size_title,
            form.font_align_title,
            form.font_vertical_align_title,
            form.position_title_x,
            form.position_title_y,
            form.max_width_title,
            form.position_qr_x,
            form.position_qr_y,
            form.size_qr,
            form.font_align_qr,
            form.fontfamily_description,
            form.font_size_description,
            form.font_align_description,
            form.font_vertical_align_description,
            form.position_description_x,
            form.position_description_y,
            form.max_width_description,
            form.interspace_description,
        ],() => {
            drawCanvas();
        },{ deep: true }
    );

    // Cargar la imagen principal al montar el componente
    onMounted(() => {
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas(); // Ajustar el tamaño inicial
        loadMainImage();
    });

    onUnmounted(() => {
        window.removeEventListener('resize', resizeCanvas);
    });

    const textDate = '01/10/2024'; //////fontfamily_date
    const textStudentName = 'Elmer Rodriguez Bobadilla'; ///fontfamily_names
    const textTitle = 'Certificado'; ///////////fontfamily_title
    const textDescription = 'Otorgado al alumno: Elmer Rodriguez Bobadilla por el cumplimiento de aprendisaje al curso virtual, por cumplimiento de las 22 horas estudiadas satisfactorias ';
    const imageQrDefault = '/img/qrcode.png'; ///size_qr

    const resizeCanvas = () => {
        const canvasElement = canvas.value;
        const container = canvasElement.parentElement;

        // Obtener el ancho y alto del contenedor
        const width = container.clientWidth;
        const height = container.clientHeight;

        // Ajustar el tamaño del canvas en píxeles
        canvasElement.width = width;
        canvasElement.height = height;

        // Redibujar el contenido del canvas
        drawCanvas();
    };
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
                                                <option value="1">Pacifico-Regular</option>
                                                <option value="2">PlaywriteIN-Regular</option>
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
                                                <option value="1">Pacifico-Regular</option>
                                                <option value="2">PlaywriteIN-Regular</option>
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
                                                <option value="1">Pacifico-Regular</option>
                                                <option value="2">PlaywriteIN-Regular</option>
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
                                                <option value="left">left</option>
                                                <option value="center">center</option>
                                                <option value="right">right</option>
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
                                                <option value="1">Pacifico-Regular</option>
                                                <option value="2">PlaywriteIN-Regular</option>
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
                                        <div class="col-span-4">
                                            <InputLabel for="max_width_description">Ancho máximo en píxeles</InputLabel>
                                            <TextInput 
                                                id="max_width_description"
                                                v-model="form.max_width_description"
                                                placeholder="800"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <InputLabel for="interspace_description">Espaciado entre líneas</InputLabel>
                        <TextInput 
                            id="interspace_description"
                            v-model="form.interspace_description"
                            placeholder="2.5"
                        />
                    </div>
                    <div class="col-span-2">
                        <label class="flex items-center cursor-pointer">
                            <input v-model="form.state" type="checkbox" class="form-checkbox" />
                            <span class="text-white-dark">Activo</span>
                        </label>
                    </div>
                    <div class="col-span-2">
                        <button @click="createCertificate" class="btn btn-primary w-full" type="button">
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
                <div class="canvas-container">
                    <canvas ref="canvas" class="canvas-code-block"></canvas>
                </div>
            </div>
        </div>
    </div>
</template>
<style>
.canvas-container {
    width: 100%; /* Ocupa el 100% del ancho del contenedor */
    max-width: 100%; /* Limita el ancho máximo */
    aspect-ratio: 2245 / 1587; /* Relación de aspecto de la imagen */
    position: relative;
    overflow: hidden;
}

.canvas-code-block {
    width: 100%; /* Ocupa el 100% del contenedor */
    height: 100%; /* Ocupa el 100% del contenedor */
    display: block;
}
</style>
