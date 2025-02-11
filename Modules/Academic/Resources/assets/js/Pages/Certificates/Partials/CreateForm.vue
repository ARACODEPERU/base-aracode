<script setup>
    import { useForm, Link } from '@inertiajs/vue3';
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import Swal2 from 'sweetalert2';
    import { ref, watch } from 'vue';
    import iconMenu from '@/Components/vristo/icon/icon-menu.vue';
    import iconCaretsDown from '@/Components/vristo/icon/icon-carets-down.vue';
    import Multiselect from "@suadelabs/vue3-multiselect";
    import "@suadelabs/vue3-multiselect/dist/vue3-multiselect.css";


    const props = defineProps({
        courses: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        course_id: null,
        certificate_img: null,
        certificate_img_preview: null,
        fontfamily_date: null,
        font_align_date: null,
        font_vertical_align_date: null,
        position_date_x: null,
        position_date_y: null,
        font_size_date: null,
        fontfamily_names: null,
        font_align_names: null,
        font_vertical_align_names: null,
        position_names_x: null,
        position_names_y: null,
        font_size_names: null,
        fontfamily_title: null,
        font_align_title: null,
        font_vertical_align_title: null,
        position_title_x: null,
        position_title_y: null,
        font_size_title: null,
        max_width_title: null,
        position_qr_x: null,
        position_qr_y: null,
        size_qr: null,
        font_align_qr: null,
        created_at: null,
        updated_at: null,
        fontfamily_description: null,
        font_align_description: null,
        font_vertical_align_description: null,
        position_description_x: null,
        position_description_y: null,
        font_size_description: null,
        max_width_description: null,
        interspace_description: null,
        name_certificate: null,
        state: null
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
            }
        });
    }



    const isShowChatMenu = ref(false);

    const modifyPreview = () => {
        alert('se ejecuta change');
    }

    const canvas = ref(null);

    
    // Manejar la subida de la imagen
    const handleImageUpload = (event) => {
        const file = event.target.files[0];
        if (file) {
            form.certificate_img = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                form.certificate_img_preview = new Image();
                form.certificate_img_preview.src = e.target.result;
                form.certificate_img_preview.onload = () => {
                    // Ajustar el tamaño del canvas al tamaño de la imagen
                    canvas.value.width = form.certificate_img_preview.width;
                    canvas.value.height = form.certificate_img_preview.height;
                    drawCanvas();
                };
            };
            reader.readAsDataURL(file);
        }
    };

    // Dibujar en el canvas
    const drawCanvas = () => {
        const ctx = canvas.value.getContext('2d');
        ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);

        // Dibujar la imagen principal en su tamaño completo
        if (form.certificate_img_preview) {
            ctx.drawImage(
                form.certificate_img_preview,
                0,
                0,
                form.certificate_img_preview.width,
                form.certificate_img_preview.height
            );
        }
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
                        />
                        <InputError :message="form.errors.name_certificate" class="mt-1" />
                    </div>
                    <div class="col-span-2">
                        <InputLabel for="course_id">Curso (Opcional)</InputLabel>
                        <multiselect
                            id="course_id" :model-value="form.course_id"
                            v-model="form.course_id"
                            :options="courses"
                            class="custom-multiselect"
                            :searchable="true"
                            placeholder="Buscar curso"
                            selected-label="seleccionado"
                            select-label="Elegir"
                            deselect-label="Quitar"
                            label="description"
                            track-by="id"
                            @update:modelValue="modifyPreview"
                        ></multiselect>
                    </div>
                    <div class="col-span-2">
                        <div class="flex items-center justify-center w-full">
                            <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                    </svg>
                                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Haga clic para cargar</span> imagen</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">SVG, PNG, JPG or GIF (MAX. 2245x1587px)</p>
                                </div>
                                <input @change="handleImageUpload" id="dropzone-file" type="file" class="hidden" />
                            </label>
                        </div>
                        <InputError :message="form.errors.certificate_img" class="mt-1" />
                    </div>
                    <div class="col-span-2 flex justify-end">
                        <button @click="createCertificate" class="btn btn-primary sm:w-full" type="button">
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
                    <canvas 
                        ref="canvas" 
                        class="canvas-code-block"
                    ></canvas>
                </div>
            </div>
        </div>
    </div>
</template>
<style>
.canvas-code-block {
    width:100%;
    min-height: 400px;
    border: 1px solid #000;
    color: #fff;
    background: #181d28;
}

</style>
