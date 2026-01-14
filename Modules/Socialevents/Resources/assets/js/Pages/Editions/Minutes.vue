<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ref } from "vue";
    import InputError from '@/Components/InputError.vue';
    import { Empty, AvatarGroup, Avatar, Tooltip } from 'ant-design-vue';
    import ModalLargeX from '@/Components/ModalLargeX.vue';
    import PdfContainer from '@/Components/PdfContainer.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue'
import IconPencilPaper from '@/Components/vristo/icon/icon-pencil-paper.vue';


    const props = defineProps({
        edicion: {
            type: Object,
            default: () => ({}),
        },
        sortedMinutes: {
            type: Object,
            default: () => ({}),
        }
    });

    const displayModalPdf = ref(false);
    const pdfUrl = ref(null);
    const loading = ref(false);
    const isImage = ref(false);
    const xhttp =  assetUrl;

    const generatePdf = async (acta) => {
        loading.value = true;
        try {
            const response = await axios.post(route('even_ediciones_actas_pdf'), {
                acta: acta
            });

            // Asignamos la URL que nos dio Laravel al componente
            pdfUrl.value = response.data.url;
        } catch (error) {
            alert("Error al generar PDF", error);
        } finally {
            loading.value = false;
        }
    };

    const openModalPreviewPdf = (acta) => {
        displayModalPdf.value = true;
        loading.value = true;

        // 1. Verificamos si existe un archivo físico subido
        if (acta.minutes_file_path) {
            const path = acta.minutes_file_path;
            const extension = path.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            pdfUrl.value = `/storage/${path}`;
            isImage.value = imageExtensions.includes(extension);
            loading.value = false; // Los archivos estáticos cargan rápido
        } else {
            // 2. Si no hay archivo, es el borrador que generamos por Ajax
            isImage.value = false;
            generatePdf(acta); // Tu método existente que setea pdfUrl y loading
        }
    };

    const closeModalPreviewPdf = () => {
        displayModalPdf.value = false;
        pdfUrl.value = null;
        isImage.value = false;
    };

    const loadingId = ref(null);

    const printPdfDownload = async (acta) => {
        if(acta.has_protest && acta.protest_status == 'pending'){
            Swal.fire({
                title: 'Reclamo Pendiente',
                text: 'No se puede descargar el acta oficial porque existe un reclamo sin resolver.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Registrar Acuerdo / Solución',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#1e3a8a',
                cancelButtonColor: '#6b7280',
                padding: '2em',
                customClass: 'sweet-alerts',
                footer: '<span class="text-gray-500">Regla de negocio: Actas en conflicto</span>'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Llamamos a la función que abre tu modal de registro
                    openModalSolution(acta);
                }
            });

            return; // Detenemos la ejecución aquí
        }else{
            try {
                loadingId.value = acta.minutes_code; // Indicamos qué acta está cargando
                const routeName = acta.minutes_type === 'partido' ? 'even_ediciones_actas_pdf' : 'even_ediciones_accordance_pdf';
                const response = await axios.post(route(routeName), {
                    acta: acta
                });

                let xpdfUrl = response.data.url;
                window.open(xpdfUrl, "_blank");
            } catch (error) {
                let mensajeError = error.response?.data?.message || "No se pudo generar el archivo PDF";
                showAlertToast(mensajeError, "error");
            }finally {
                // Quitamos el loading siempre (éxito o error)
                loadingId.value = null;
            }
        }

    }

    const displayModalSolution = ref(false);
    const formSolution = useForm({
        id: null,
        edition_id: null,
        match_id: null,
        subject: null,
        protest_details: null,
        minutes_subject_local: null,
        minutes_subject_visitor: null,
        resolution_details: null,
        change_score: false,
        new_score_h: null,
        new_score_a: null,
    });

    const openModalSolution = (acta) => {
        formSolution.id = acta.id;
        formSolution.protest_details = acta.protest_details;
        formSolution.subject = acta.minutes_subject;
        formSolution.minutes_subject_local = acta.minutes_subject_local;
        formSolution.minutes_subject_visitor = acta.minutes_subject_visitor;
        formSolution.edition_id = acta.partido.edition_id;
        formSolution.match_id = acta.match_id;
        displayModalSolution.value = true;
    };

    const saveSolution = async () => {

        formSolution.post(route('even_ediciones_actas_solucion_update'), {
            forceFormData: true,
            errorBag: 'saveSolution',
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    title: 'Enhorabuena',
                    text: 'El acuerdo se registró y el acta ya puede ser descargada.',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            },
        });
    };

    const showAlertToast = async (text, icon) => {
        const toast = Swal.mixin({
            toast: true,
            position: 'top-right',
            showConfirmButton: false,
            timer: 3000,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        toast.fire({
            icon: icon,
            title: text,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }

    const displayModalUploadPdf = ref(false);
    const selectedActaForUpload = ref(null);
    const fileToUpload = ref(null);
    const fileName = ref('');
    const isUploading = ref(false);
    const fileType = ref('');
    const formUpload = useForm({
        file: null,
        acta_id: null,
        match_id: null,
        edition_id: null,
        subject: null
    });

    const openUploadModal = (acta) => {
        selectedActaForUpload.value = acta;
        fileToUpload.value = null;
        fileName.value = '';
        formUpload.edition_id = acta.partido.edition_id;
        formUpload.match_id = acta.match_id;
        formUpload.subject = acta.minutes_subject;
        displayModalUploadPdf.value = true;
    };

    const onFileChange = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const allowedImageTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        const allowedPdfType = 'application/pdf';

        if (allowedImageTypes.includes(file.type)) {
            fileType.value = 'image';
            fileToUpload.value = file;
        } else if (file.type === allowedPdfType) {
            fileType.value = 'pdf';
            fileToUpload.value = file;
        } else {
            showAlertToast("Solo se permiten imágenes (JPG, PNG) o archivos PDF", "error");
            e.target.value = null; // Resetear input
            return;
        }
        fileName.value = file.name;
    };

    const uploadSignedActa = () => {
        if (!fileToUpload.value) return;

        // Asignamos los valores al objeto del formulario de Inertia
        formUpload.file = fileToUpload.value;
        formUpload.acta_id = selectedActaForUpload.value.id;

        formUpload.post(route('even_ediciones_actas_file_update'), {
            forceFormData: true, // Obligamos a que use FormData por el archivo
            onBefore: () => {
                // Aquí podrías hacer una última validación si fuera necesario
            },
            onSuccess: () => {
                Swal.fire({
                    title: 'Enhorabuena',
                    text: 'Acta firmada subida correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                displayModalUploadPdf.value = false;
                fileToUpload.value = null; // Limpiamos el estado local
                formUpload.reset(); // Reseteamos el formulario de Inertia
            },
            onError: (errors) => {
                // Si Laravel devuelve errores de validación (ej: archivo muy pesado)
                const firstError = Object.values(errors)[0];
                showAlertToast(firstError || "Error al subir el archivo", "error");
            },
            onFinish: () => {
                // Se ejecuta al final, sea éxito o error
            },
        });
    };
</script>
<template>
    <AppLayout title="Ediciones">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_listado')" class="text-primary hover:underline">Ediciones</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_editar', edicion.id)" class="text-primary hover:underline">{{ edicion.name }}</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Libro de Actas y Acuerdos</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl font-semibold text-gray-800">Libro de Actas y Acuerdos</h2>
                <div class="">
                    <Link v-can="'even_ediciones_actas_nuevo'" :href="route('even_ediciones_actas_crear', edicion.id)" class="btn btn-primary uppercase text-xs">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Nueva Acta
                    </Link>
                </div>
            </div>
            <div class="mt-5">
                <div class="panel p-0">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs font-bold">
                                <th class="px-2 py-2.5 text-center">Acciones</th>
                                <th class="px-2 py-2.5">Código / Título</th>
                                <th class="px-2 py-2.5">Tipo</th>
                                <th class="px-2 py-2.5 text-center">Participantes</th>
                                <th class="px-2 py-2.5">Estado</th>
                                <th class="px-2 py-2.5">Archivo</th>

                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template v-if="sortedMinutes.length > 0">
                                <tr v-for="(item, index) in sortedMinutes" class="hover:bg-gray-50 transition dark:hover:bg-gray-700">
                                    <td class="px-2 py-2.5 text-right space-x-2">
                                        <div class="flex items-center gap-2 justify-center">
                                            <Link
                                                v-can="'even_ediciones_acta_editar'"
                                                :href="item.url_edit"
                                                v-tippy="{content: 'Editar acta', placement: 'bottom'}"
                                                class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <IconPencilPaper class="w-5 h-5" />
                                            </Link>
                                            <button
                                                @click="printPdfDownload(item)"
                                                :disabled="loadingId === item.minutes_code"
                                                v-tippy="{content: 'Descargar PDF', placement: 'bottom'}"
                                                class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <svg v-if="loadingId !== item.minutes_code" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                </svg>

                                                <svg v-else class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </button>
                                            <button
                                                @click="openUploadModal(item)"
                                                title="Subir acta firmada"
                                                class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-full transition"
                                                v-if="item.partido?.status !== 'closed' && !item.minutes_file_path"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                                </svg>
                                            </button>
                                            <button
                                                @click="openModalPreviewPdf(item)"
                                                v-if="item.minutes_file_path"
                                                type="button" class="p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded-full transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2.5">
                                        <div class="text-sm font-bold text-indigo-600">#{{ item.minutes_code }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ item.minutes_subject }}
                                        </div>
                                    </td>
                                    <td class="px-2 py-2.5">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700 font-medium">{{ item.minutes_type }}</span>
                                    </td>
                                    <td class="px-2 py-2.5 text-center">
                                        <AvatarGroup shape="circle" :max-count="2">
                                            <template v-for="person in item.participants">
                                                <Tooltip :title="person.full_name" placement="top">
                                                    <Avatar>
                                                        <template #icon>
                                                            <img :src="`https://ui-avatars.com/api/?name=${person.full_name}`" />
                                                        </template>
                                                    </Avatar>
                                                </Tooltip>
                                            </template>
                                        </AvatarGroup>
                                    </td>
                                    <td class="px-2 py-2.5 text-sm">
                                        <span v-if="item.status == 'pending'" class="flex items-center text-amber-600">
                                            <span class="h-2 w-2 rounded-full bg-amber-600 mr-2 animate-pulse"></span>
                                            Pendiente Firma
                                        </span>
                                        <span v-if="item.status == 'accepted'" class="flex items-center text-blue-600">
                                            <span class="h-2 w-2 rounded-full bg-blue-600 mr-2 animate-pulse"></span>
                                            Firmado
                                        </span>
                                    </td>
                                    <td class="px-2 py-2.5 text-sm">
                                        <span v-if="item.minutes_file_name" class="text-gray-700 italic">{{ item.minutes_file_name }}</span>
                                        <span v-else class="text-gray-400 italic">No cargado</span>
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr>
                                    <td colspan="6" ><Empty :description="'Sin actas registradas'" /></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <ModalLargeX :show="displayModalPdf" :onClose="closeModalPreviewPdf" :icon="isImage ? '/img/documento-imagen.png' : '/img/documento-pdf.png'">
            <template #title>
                {{ isImage ? 'Acta Digitalizada (Imagen)' : 'Acta del Encuentro (PDF)' }}
            </template>

            <template #message>
                {{ isImage ? 'Visualizando fotografía del acta firmada.' : 'Vista previa del documento oficial.' }}
            </template>

            <template #content>
                <div v-if="loading && !pdfUrl" class="flex flex-col items-center justify-center p-20">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
                    <p class="mt-4 text-sm text-gray-500">Cargando archivo...</p>
                </div>

                <div v-else>
                    <div v-if="isImage" class="bg-gray-200 p-4 rounded-lg flex justify-center items-center min-h-[400px]">
                        <div class="relative group">
                            <img
                                :src="pdfUrl"
                                class="max-w-full h-auto rounded shadow-2xl border-4 border-white transition-transform duration-500"
                                alt="Acta firmada"
                            />
                            <div class="absolute top-2 right-2 bg-green-600 text-white px-3 py-1 rounded-full text-[10px] font-bold uppercase shadow-lg">
                                Archivo Original
                            </div>
                        </div>
                    </div>

                    <div v-else>
                        <PdfContainer :pdfUrl="pdfUrl" :isLoading="loading" />
                    </div>
                </div>
            </template>

            <template #buttons>
                <a v-if="pdfUrl" :href="pdfUrl" download class="flex items-center gap-2 text-indigo-600 hover:text-indigo-800 font-bold text-sm px-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Descargar original
                </a>
            </template>
        </ModalLargeX>
        <ModalLargeX
            :show="displayModalSolution"
            :onClose="() => displayModalSolution = false"
            :icon="'/img/examen.png'"
        >
            <template #title>{{ formSolution.subject }}</template>
            <template #message>Registrar Acuerdo de Reclamo</template>
            <template #content>
                <div class="p-4">
                    <div class="mb-4 overflow-hidden rounded-r-md border-l-4 border-red-500 shadow-sm">
                        <div class="bg-red-500 px-3 py-1">
                            <span class="text-white font-bold text-xs uppercase">Reclamo Pendiente de Solución</span>
                        </div>

                        <div class="bg-red-50 p-3 text-sm text-red-800">
                            <div v-if="formSolution?.protest_details?.team_h" class="mb-3 last:mb-0">
                                <span class="font-bold text-red-900 block">
                                    Equipo Local ({{ formSolution?.minutes_subject_local || 'Local' }}):
                                </span>
                                <p class="mt-1 italic text-gray-700 bg-white/50 p-2 rounded border border-red-100">
                                    "{{ formSolution.protest_details.team_h }}"
                                </p>
                            </div>

                            <div v-if="formSolution?.protest_details?.team_a">
                                <span class="font-bold text-red-900 block">
                                    Equipo Visitante ({{ formSolution?.minutes_subject_visitor || 'Visitante' }}):
                                </span>
                                <p class="mt-1 italic text-gray-700 bg-white/50 p-2 rounded border border-red-100">
                                    "{{ formSolution.protest_details.team_a }}"
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-4">
                        <h3 class="text-sm font-bold text-gray-800 uppercase mb-4">Resolución Oficial</h3>

                        <div class="flex items-center mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <input
                                id="change-score"
                                type="checkbox"
                                v-model="formSolution.change_score"
                                class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer"
                            >
                            <label for="change-score" class="ml-3 cursor-pointer">
                                <span class="block text-sm font-medium text-gray-900">¿Modificar resultado del partido?</span>
                                <span class="block text-xs text-gray-500">Active esta opción si el reclamo cambia los goles (ej. W.O. 3-0)</span>
                            </label>
                        </div>

                        <transition name="fade">
                            <div v-if="formSolution.change_score" class="grid grid-cols-2 gap-4 mb-4 animate-fadeIn">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Goles Local</label>
                                    <input
                                        type="number"
                                        v-model="formSolution.new_score_h"
                                        min="0"
                                        class="form-input"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Goles Visitante</label>
                                    <input
                                        type="number"
                                        v-model="formSolution.new_score_a"
                                        min="0"
                                        class="form-input"
                                    >
                                </div>
                            </div>
                        </transition>

                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Detalle del acuerdo / fundamento</label>
                            <textarea
                                v-model="formSolution.resolution_details"
                                rows="3"
                                class="form-textarea"
                                placeholder="Explique brevemente por qué se tomó esta decisión..."
                            ></textarea>
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <div class="flex justify-end gap-2 p-4">
                    <PrimaryButton
                        type="button"
                        @click="saveSolution"
                        :disabled="formSolution.processing"
                    >
                        <div v-if="formSolution.processing" class="mr-2">
                            <svg class="w-5 h-5 animate-bounce-ball" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                <path fill="currentColor" d="M481.3 424.1L409.7 419.3C404.5 419 399.4 420.4 395.2 423.5C391 426.6 388 430.9 386.8 436L369.2 505.6C353.5 509.8 337 512 320 512C303 512 286.5 509.8 270.8 505.6L253.2 436C251.9 431 248.9 426.6 244.8 423.5C240.7 420.4 235.5 419 230.3 419.3L158.7 424.1C141.1 396.9 130.2 364.9 128.3 330.5L189 292.3C193.4 289.5 196.6 285.3 198.2 280.4C199.8 275.5 199.6 270.2 197.7 265.4L171 198.8C192 173.2 219.3 153 250.7 140.9L305.9 186.9C309.9 190.2 314.9 192 320 192C325.1 192 330.2 190.2 334.1 186.9L389.3 140.9C420.6 153 448 173.2 468.9 198.8L442.2 265.4C440.3 270.2 440.1 275.5 441.7 280.4C443.3 285.3 446.6 289.5 450.9 292.3L511.6 330.5C509.7 364.9 498.8 396.9 481.2 424.1zM320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM334.1 250.3C325.7 244.2 314.3 244.2 305.9 250.3L258 285C249.6 291.1 246.1 301.9 249.3 311.8L267.6 368.1C270.8 378 280 384.7 290.4 384.7L349.6 384.7C360 384.7 369.2 378 372.4 368.1L390.7 311.8C393.9 301.9 390.4 291.1 382 285L334.1 250.2z"/>
                            </svg>
                        </div>

                        <span>
                            {{ formSolution.processing ? 'Procesando cambios...' : 'Guardar Acta y finalizar' }}
                        </span>
                    </PrimaryButton>
                </div>
            </template>
        </ModalLargeX>
        <ModalLarge :show="displayModalUploadPdf" :onClose="() => displayModalUploadPdf = false" :icon="'/img/subir-archivo.png'">
            <template #title>{{ formUpload.subject }}</template>
            <template #message>Puede subir el documento escaneado en PDF o una fotografía clara (JPG/PNG) del acta con las firmas.</template>

            <template #content>
                <div class="p-6">
                    <div
                        class="relative border-2 border-dashed rounded-xl p-8 transition-all duration-300 text-center"
                        :class="fileToUpload ? 'border-indigo-500 bg-indigo-50/30' : 'border-gray-300 hover:border-indigo-400 bg-gray-50'"
                    >
                        <input
                            type="file"
                            @change="onFileChange"
                            accept="image/*,application/pdf"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        />

                        <div v-if="!fileToUpload" class="space-y-3">
                            <div class="mx-auto w-14 h-14 bg-white rounded-full shadow-sm flex items-center justify-center text-gray-400">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-700">Toca para tomar foto o seleccionar archivo</p>
                                <p class="text-xs text-gray-500">PDF, JPG o PNG hasta 10MB</p>
                            </div>
                        </div>

                        <div v-else class="flex flex-col items-center animate-fadeIn">
                            <div class="mb-3">
                                <svg v-if="fileType === 'pdf'" class="w-16 h-16 shadow-lg rounded-lg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                    <path fill="currentColor" d="M128 64C92.7 64 64 92.7 64 128L64 512C64 547.3 92.7 576 128 576L208 576L208 464C208 428.7 236.7 400 272 400L448 400L448 234.5C448 217.5 441.3 201.2 429.3 189.2L322.7 82.7C310.7 70.7 294.5 64 277.5 64L128 64zM389.5 240L296 240C282.7 240 272 229.3 272 216L272 122.5L389.5 240zM272 444C261 444 252 453 252 464L252 592C252 603 261 612 272 612C283 612 292 603 292 592L292 564L304 564C337.1 564 364 537.1 364 504C364 470.9 337.1 444 304 444L272 444zM304 524L292 524L292 484L304 484C315 484 324 493 324 504C324 515 315 524 304 524zM400 444C389 444 380 453 380 464L380 592C380 603 389 612 400 612L432 612C460.7 612 484 588.7 484 560L484 496C484 467.3 460.7 444 432 444L400 444zM420 572L420 484L432 484C438.6 484 444 489.4 444 496L444 560C444 566.6 438.6 572 432 572L420 572zM508 464L508 592C508 603 517 612 528 612C539 612 548 603 548 592L548 548L576 548C587 548 596 539 596 528C596 517 587 508 576 508L548 508L548 484L576 484C587 484 596 475 596 464C596 453 587 444 576 444L528 444C517 444 508 453 508 464z"/>
                                </svg>
                                <svg v-if="fileType === 'image'" class="w-16 h-16 shadow-lg rounded-lg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                    <path fill="currentColor" d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM256 320C256 302.3 241.7 288 224 288C206.3 288 192 302.3 192 320C192 337.7 206.3 352 224 352C241.7 352 256 337.7 256 320zM220.6 512L419.4 512C435.2 512 448 499.2 448 483.4C448 476.1 445.2 469 440.1 463.7L343.3 361.9C337.3 355.6 328.9 352 320.1 352L319.8 352C311 352 302.7 355.6 296.6 361.9L199.9 463.7C194.8 469 192 476.1 192 483.4C192 499.2 204.8 512 220.6 512z"/>
                                </svg>
                            </div>

                            <div class="bg-white px-4 py-2 rounded-full border border-indigo-100 shadow-sm flex items-center gap-2">
                                <span class="text-xs font-bold text-indigo-700 truncate max-w-[200px]">{{ fileName }}</span>
                                <button @click.stop="fileToUpload = null" class="text-red-400 hover:text-red-600 transition">
                                   <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                        <path fill="currentColor" d="M342.6 73.4C330.1 60.9 309.8 60.9 297.3 73.4L169.3 201.4C156.8 213.9 156.8 234.2 169.3 246.7C181.8 259.2 202.1 259.2 214.6 246.7L288 173.3L288 384C288 401.7 302.3 416 320 416C337.7 416 352 401.7 352 384L352 173.3L425.4 246.7C437.9 259.2 458.2 259.2 470.7 246.7C483.2 234.2 483.2 213.9 470.7 201.4L342.7 73.4zM160 416C160 398.3 145.7 384 128 384C110.3 384 96 398.3 96 416L96 480C96 533 139 576 192 576L448 576C501 576 544 533 544 480L544 416C544 398.3 529.7 384 512 384C494.3 384 480 398.3 480 416L480 480C480 497.7 465.7 512 448 512L192 512C174.3 512 160 497.7 160 480L160 416z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <InputError :message="formUpload.errors.file" class="mt-2" />
                    </div>
                    <div class="mt-4 p-3 bg-amber-50 rounded-lg flex gap-3 items-center">
                        <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                            <path d="M320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM288 224C288 206.3 302.3 192 320 192C337.7 192 352 206.3 352 224C352 241.7 337.7 256 320 256C302.3 256 288 241.7 288 224zM280 288L328 288C341.3 288 352 298.7 352 312L352 400L360 400C373.3 400 384 410.7 384 424C384 437.3 373.3 448 360 448L280 448C266.7 448 256 437.3 256 424C256 410.7 266.7 400 280 400L304 400L304 336L280 336C266.7 336 256 325.3 256 312C256 298.7 266.7 288 280 288z"/>
                        </svg>
                        <p class="text-xs text-amber-800 italic">
                            Al subir el acta firmada, el resultado del partido se considerará oficial y no podrá ser modificado sin autorización administrativa.
                        </p>
                    </div>
                </div>
            </template>

            <template #buttons>
                <PrimaryButton
                    type="button"
                    @click="uploadSignedActa"
                    :disabled="formUpload.processing"
                >
                    <div v-if="formUpload.processing" class="mr-2">
                        <svg class="w-5 h-5 animate-bounce-ball" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                            <path fill="currentColor" d="M481.3 424.1L409.7 419.3C404.5 419 399.4 420.4 395.2 423.5C391 426.6 388 430.9 386.8 436L369.2 505.6C353.5 509.8 337 512 320 512C303 512 286.5 509.8 270.8 505.6L253.2 436C251.9 431 248.9 426.6 244.8 423.5C240.7 420.4 235.5 419 230.3 419.3L158.7 424.1C141.1 396.9 130.2 364.9 128.3 330.5L189 292.3C193.4 289.5 196.6 285.3 198.2 280.4C199.8 275.5 199.6 270.2 197.7 265.4L171 198.8C192 173.2 219.3 153 250.7 140.9L305.9 186.9C309.9 190.2 314.9 192 320 192C325.1 192 330.2 190.2 334.1 186.9L389.3 140.9C420.6 153 448 173.2 468.9 198.8L442.2 265.4C440.3 270.2 440.1 275.5 441.7 280.4C443.3 285.3 446.6 289.5 450.9 292.3L511.6 330.5C509.7 364.9 498.8 396.9 481.2 424.1zM320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM334.1 250.3C325.7 244.2 314.3 244.2 305.9 250.3L258 285C249.6 291.1 246.1 301.9 249.3 311.8L267.6 368.1C270.8 378 280 384.7 290.4 384.7L349.6 384.7C360 384.7 369.2 378 372.4 368.1L390.7 311.8C393.9 301.9 390.4 291.1 382 285L334.1 250.2z"/>
                        </svg>
                    </div>

                    <span>
                        {{ formUpload.processing ? 'Subida en proceso...' : 'Subir Documento' }}
                    </span>
                </PrimaryButton>
            </template>
        </ModalLarge>
    </AppLayout>
</template>

