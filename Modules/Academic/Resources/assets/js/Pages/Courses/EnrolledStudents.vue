<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Keypad from '@/Components/Keypad.vue';
    import Swal from "sweetalert2";
    import { useForm, Link, usePage, router } from '@inertiajs/vue3';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import { ref, watch, onMounted, nextTick } from "vue";
    import iconExcel from "@/Components/vristo/icon/icon-excel.vue";
    import { ConfigProvider, Dropdown, Menu, MenuItem, Button } from 'ant-design-vue';
    import IconSearch from '@/Components/vristo/icon/icon-search.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogOverlay } from '@headlessui/vue';
    import textWriting from '@/Components/loader/text-writing.vue';
    import * as XLSX from "xlsx";
    import Pagination from "@/Components/Pagination.vue";

    const props = defineProps({
        course: {
            type: Object,
            default: () => ({}),
        },
        students:{
            type: Object,
            default: () => ({}),
        }
    });

    const studentsData = ref([]);
    const appCodeUnique = import.meta.env.VITE_APP_CODE ?? 'ARACODE';
    const channelListenOnli = "aca-import-status-" + appCodeUnique + '-' + usePage().props.auth.user.id;
    const channelListenInvoice = "aca-invoice-send-" + appCodeUnique + '-' + usePage().props.auth.user.id;
    const importStatus = ref([]);
    const porsentaje = ref(0);
    const progressSend = ref(0);
    const progressSendInvoice = ref(0);
    const loadingImport = ref(false);
    const scrollContainer = ref(null);
    const scrollContainerInvoice = ref(null);

    onMounted(() => {
        studentsData.value = props.students.data;

        window.socketIo.on(channelListenOnli, (status) => {
            importStatus.value.push(status);
            progressSend.value = parseFloat(progressSend.value) + parseFloat(porsentaje.value)
            nextTick(() => {
                if (scrollContainer.value) {
                    scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight;
                }
            });
        });

        window.socketIo.on(channelListenInvoice, (status) => {
            resultSendEmail.value.push(status);
            progressSendInvoice.value = parseFloat(progressSendInvoice.value) + parseFloat(porsentajeInvoice.value);
            nextTick(() => {
                if (scrollContainerInvoice.value) {
                    scrollContainerInvoice.value.scrollTop = scrollContainerInvoice.value.scrollHeight;
                }
            });
        });
    });

    const chxtodos = ref(false);

    const toggleAllStudent = () => {
        studentsData.value.forEach(student => {
            student.checkbox = chxtodos.value;
        });
    };

    // Para sincronizar si todos los checkboxes individuales están marcados


    const form = useForm({
        search: null
    });

    const baseUrl = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    const displayModalImport = ref(false);

    const showModalImport = () => {
        displayModalImport.value = true;
    }

    const closeModalImport = () => {
        displayModalImport.value = false;
        reloadPage();
    }

    const reloadPage = () => {
        router.visit(route('aca_enrolledstudents_list', props.course.id), {
            method: 'get',
            replace: false,
            preserveState: false,
            preserveScroll: true,
        });
    }

    const leaderCertificate = ref(false);

    const createCertificates = () => {
        // Verifica si al menos un estudiante tiene el checkbox en true
        const algunoMarcado = studentsData.value.some(student => student.checkbox === true);

        if (!algunoMarcado) {
            Swal.fire({
                title: 'Acción no permitida',
                text: 'Debe seleccionar al menos un estudiante.',
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            return;
        }

        leaderCertificate.value = true;

        axios({
            method: 'put',
            url: route('aca_certificate_massive_store', props.course.id),
            data: { students: studentsData.value }
        }).then(() => {
            reloadPage();
        }).finally(() => {
            leaderCertificate.value = false;
        });
    };

    const file = ref(null);
    const displayModalImportDetails = ref(false);
    const loading = ref(false);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const totalRecords = ref(0);

    const handleFile = async (event) => {
        file.value = event.target.files[0];
        const xfile = event.target.files[0];
        // 📌 Leer archivo Excel en el frontend
        const reader = new FileReader();
        reader.onload = (e) => {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: "array" });
            const sheetName = workbook.SheetNames[0];
            const sheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(sheet);

            totalRecords.value = jsonData.length - 1; // Restar 1 para excluir la cabecera
        };
        reader.readAsArrayBuffer(xfile);
    };

    const importFileUploadStart = () => {

        if (!file.value) {
            return showMessage("Selecciona un archivo Excel","error");
        }

        displayModalImportDetails.value = true;
        displayModalImport.value = false;

        loadingImport.value = true;
        importStatus.value = [];
        porsentaje.value = 0;
        progressSend.value = 0;

        const formData = new FormData();

        formData.append('course_id', props.course.id);
        formData.append('modality_id', props.course.modality_id);
        formData.append('file', file.value);
        formData.append('channelListen', channelListenOnli);
        formData.append('csrfToken', csrfToken);
        formData.append('urlBacken', route('aca_import_student_bycourse'));

        let url = import.meta.env.VITE_SOCKET_IO_SERVER + '/api/academic/import-students-excel';
        porsentaje.value = (1 / parseInt(totalRecords.value)) * 100;

        axios.post(url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            timeout: 0,
        }).then(response => {
            console.log(response)
        }).catch(error => {
            console.error('Error al enviar el archivo:', error);
        }).finally(() => {
            loadingImport.value = false;
        });

    }
    const showMessage = (msg = '', type = 'success') => {
        const toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            customClass: { container: 'toast' },
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });

        toast.fire({
            icon: type,
            title: msg,
            padding: '10px 20px',
        });
    };

    const closeModalImportDetails = () => {
        displayModalImportDetails.value = false
        router.visit(route('aca_enrolledstudents_list', props.course.id), {
            method: 'get',
            replace: false,
            preserveState: false,
            preserveScroll: false,
        });
    }

    const leaderDocuments = ref(false);
    const resultSendEmail = ref([]);
    const porsentajeInvoice = ref(0);

    const createDocuments = () => {
        // Verifica si al menos un estudiante tiene el checkbox en true
        const algunoMarcado = studentsData.value.some(student => student.checkbox === true);

        const registrations = studentsData.value.filter(student => student.checkbox === true);
        const marcados = registrations.length;
        if (!algunoMarcado) {
            Swal.fire({
                title: 'Acción no permitida',
                text: 'Debe seleccionar al menos un estudiante.',
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            return;
        }

        leaderDocuments.value = true;

        displayModalSendDetails.value = true;
        const url = import.meta.env.VITE_SOCKET_IO_SERVER + '/api/academic/send-invoice-email'; // Cambia por la URL de tu API
        const contador = 1;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        porsentajeInvoice.value = (contador / parseInt(marcados)) * 100;

        axios.post(url, {
            registrations: registrations,
            csrfToken: csrfToken,
            urlBacken: route('academic_generate_and_send_invoices'),
            channelListen: channelListenInvoice,
            userId: usePage().props.auth.user.id
        },{
            headers: {
                'Content-Type': 'application/json'
            },
            timeout: 0,
        }).then(() => {
            reloadPage();
        })
        .finally(()=>{
            leaderDocuments.value = false;
        });
    }

    const displayModalSendDetails = ref(false);

    const closeModalSendDetails = () => {
        displayModalSendDetails.value = false;
        reloadPage();
    }

</script>

<template>
    <AppLayout title="Cursos">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('aca_courses_list')" class="text-primary hover:underline">Cursos</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('aca_courses_edit', course.id)" class="text-primary hover:underline">{{ course.description }}</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Alumnos</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Alumnos</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <Keypad>
                            <template #botones>
                                <button v-on:click="showModalImport" type="button" class="btn btn-success text-xs px-4 py-2 uppercase">
                                    <icon-excel class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                                    Importar desde Excel
                                </button>
                                <Link :href="route('aca_courses_list')"  class="btn btn-warning text-xs px-4 py-2 uppercase">Ir Cursos</Link>
                            </template>
                        </Keypad>
                    </div>

                    <div class="relative">
                        <input
                            type="text"
                            placeholder="Buscar"
                            class="form-input py-2 ltr:pr-11 rtl:pl-11 peer"
                            v-model="form.search"
                            @keyup.enter="form.get(route('aca_courses_list'))"
                        />
                        <div class="absolute ltr:right-[11px] rtl:left-[11px] top-1/2 -translate-y-1/2 peer-focus:text-primary">
                            <icon-search class="mx-auto" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-6 gap-6 mt-5">
                <div class="col-span-6 sm:col-span-2">
                    <div class="panel">
                        <ul class="space-y-2 font-medium">
                            <li>
                                <button @click="createCertificates" :class="{ 'opacity-25': leaderCertificate }" :disabled="leaderCertificate" type="button" class="flex w-full items-center p-2 text-blue-700 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                                    <svg v-if="leaderCertificate" aria-hidden="true" role="status" class="inline w-5 h-5 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                    </svg>
                                    <svg v-else class="w-5 h-5 transition duration-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" fill="currentColor">
                                        <path fill="currentColor" d="M173.8 5.5c11-7.3 25.4-7.3 36.4 0L228 17.2c6 3.9 13 5.8 20.1 5.4l21.3-1.3c13.2-.8 25.6 6.4 31.5 18.2l9.6 19.1c3.2 6.4 8.4 11.5 14.7 14.7L344.5 83c11.8 5.9 19 18.3 18.2 31.5l-1.3 21.3c-.4 7.1 1.5 14.2 5.4 20.1l11.8 17.8c7.3 11 7.3 25.4 0 36.4L366.8 228c-3.9 6-5.8 13-5.4 20.1l1.3 21.3c.8 13.2-6.4 25.6-18.2 31.5l-19.1 9.6c-6.4 3.2-11.5 8.4-14.7 14.7L301 344.5c-5.9 11.8-18.3 19-31.5 18.2l-21.3-1.3c-7.1-.4-14.2 1.5-20.1 5.4l-17.8 11.8c-11 7.3-25.4 7.3-36.4 0L156 366.8c-6-3.9-13-5.8-20.1-5.4l-21.3 1.3c-13.2 .8-25.6-6.4-31.5-18.2l-9.6-19.1c-3.2-6.4-8.4-11.5-14.7-14.7L39.5 301c-11.8-5.9-19-18.3-18.2-31.5l1.3-21.3c.4-7.1-1.5-14.2-5.4-20.1L5.5 210.2c-7.3-11-7.3-25.4 0-36.4L17.2 156c3.9-6 5.8-13 5.4-20.1l-1.3-21.3c-.8-13.2 6.4-25.6 18.2-31.5l19.1-9.6C65 70.2 70.2 65 73.4 58.6L83 39.5c5.9-11.8 18.3-19 31.5-18.2l21.3 1.3c7.1 .4 14.2-1.5 20.1-5.4L173.8 5.5zM272 192a80 80 0 1 0 -160 0 80 80 0 1 0 160 0zM1.3 441.8L44.4 339.3c.2 .1 .3 .2 .4 .4l9.6 19.1c11.7 23.2 36 37.3 62 35.8l21.3-1.3c.2 0 .5 0 .7 .2l17.8 11.8c5.1 3.3 10.5 5.9 16.1 7.7l-37.6 89.3c-2.3 5.5-7.4 9.2-13.3 9.7s-11.6-2.2-14.8-7.2L74.4 455.5l-56.1 8.3c-5.7 .8-11.4-1.5-15-6s-4.3-10.7-2.1-16zm248 60.4L211.7 413c5.6-1.8 11-4.3 16.1-7.7l17.8-11.8c.2-.1 .4-.2 .7-.2l21.3 1.3c26 1.5 50.3-12.6 62-35.8l9.6-19.1c.1-.2 .2-.3 .4-.4l43.2 102.5c2.2 5.3 1.4 11.4-2.1 16s-9.3 6.9-15 6l-56.1-8.3-32.2 49.2c-3.2 5-8.9 7.7-14.8 7.2s-11-4.3-13.3-9.7z"/>
                                    </svg>
                                    <span class="ms-3">Activar la descarga de certificados</span>
                                </button>
                            </li>
                            <li>
                                <button @click="createDocuments" :class="{ 'opacity-25': leaderDocuments }" :disabled="leaderDocuments" class="flex w-full items-center p-2 text-green-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                                    <svg v-if="leaderDocuments" aria-hidden="true" role="status" class="inline w-5 h-5 text-gray-200 animate-spin dark:text-white" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                    </svg>
                                    <svg v-else class="w-5 h-5 transition duration-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" fill="currentColor">
                                        <path fill="currentColor" d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-288-128 0c-17.7 0-32-14.3-32-32L224 0 64 0zM256 0l0 128 128 0L256 0zM80 64l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L80 96c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm16 96l192 0c17.7 0 32 14.3 32 32l0 64c0 17.7-14.3 32-32 32L96 352c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32zm0 32l0 64 192 0 0-64L96 256zM240 416l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16z"/>
                                    </svg>
                                    <span class="ms-3">Generar y enviar boleta de venta</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <div class="panel p-0 border-0 overflow-hidden">
                        <div class="table-responsive">
                            <ConfigProvider>
                                <table class="table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                <label class="inline-flex">
                                                    <input v-model="chxtodos" @change="toggleAllStudent" id="todos" type="checkbox" class="form-checkbox outline-primary" />
                                                </label>
                                            </th>
                                            <th>
                                                Nombre Alumno
                                            </th>
                                            <th>
                                                BOLETA
                                            </th>
                                            <th>
                                                Certificado
                                            </th>
                                            <th>
                                                Estado
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, index) in studentsData">
                                            <td>
                                                <label class="inline-flex">
                                                    <input v-model="row.checkbox" :id="'chxStudent'+index" type="checkbox" class="form-checkbox" />
                                                </label>
                                            </td>
                                            <td>{{ row.student.person.full_name }}</td>
                                            <td>
                                                <span v-if="row.document_id" class="badge badge-outline-primary">
                                                    {{ row.document.invoice_serie }}-{{ row.document.invoice_correlative }}
                                                </span>
                                                <span v-else class="badge badge-outline-info">Pendiente</span>
                                            </td>
                                            <td>
                                                <div v-if="row.certificate_date" class="flex items-center space-x-4">
                                                    <svg class="w-6 h-6" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                        <path stroke="currentColor" d="M211 7.3C205 1 196-1.4 187.6 .8s-14.9 8.9-17.1 17.3L154.7 80.6l-62-17.5c-8.4-2.4-17.4 0-23.5 6.1s-8.5 15.1-6.1 23.5l17.5 62L18.1 170.6c-8.4 2.1-15 8.7-17.3 17.1S1 205 7.3 211l46.2 45L7.3 301C1 307-1.4 316 .8 324.4s8.9 14.9 17.3 17.1l62.5 15.8-17.5 62c-2.4 8.4 0 17.4 6.1 23.5s15.1 8.5 23.5 6.1l62-17.5 15.8 62.5c2.1 8.4 8.7 15 17.1 17.3s17.3-.2 23.4-6.4l45-46.2 45 46.2c6.1 6.2 15 8.7 23.4 6.4s14.9-8.9 17.1-17.3l15.8-62.5 62 17.5c8.4 2.4 17.4 0 23.5-6.1s8.5-15.1 6.1-23.5l-17.5-62 62.5-15.8c8.4-2.1 15-8.7 17.3-17.1s-.2-17.4-6.4-23.4l-46.2-45 46.2-45c6.2-6.1 8.7-15 6.4-23.4s-8.9-14.9-17.3-17.1l-62.5-15.8 17.5-62c2.4-8.4 0-17.4-6.1-23.5s-15.1-8.5-23.5-6.1l-62 17.5L341.4 18.1c-2.1-8.4-8.7-15-17.1-17.3S307 1 301 7.3L256 53.5 211 7.3z"/>
                                                    </svg>
                                                    <div>
                                                        <strong>Otorgado</strong>
                                                        <p>{{ row.certificate_date }}</p>
                                                    </div>
                                                </div>
                                                <span v-else class="badge bg-info">Pendiente</span>
                                            </td>
                                            <td>
                                                <span v-if="row.status" class="badge bg-success">Activo</span>
                                                <span v-else class="badge bg-danger">Inactivo</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <Pagination :data="students" />
                            </ConfigProvider>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ModalLarge :show="displayModalImport" :onClose="closeModalImport" :icon="'/img/excel.png'">
            <template #title>Importar Alumnos</template>
            <template #message>Puedes registrar datos de forma rápida y sencilla utilizando un archivo Excel. Asegúrate de seguir el formato especificado para garantizar un proceso sin errores.</template>
            <template #content>
                <img src="/img/aca_formato_estudiantes.png" />
                <form enctype="multipart/form-data" class="mt-8">
                    <label for="small-file-input" class="sr-only">Seleccione archivo</label>
                    <input type="file" name="small-file-input"
                        id="small-file-input"
                        class="block w-full border border-gray-200 shadow-sm rounded-lg text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400
                        file:bg-gray-50 file:border-0
                        file:me-4
                        file:py-2 file:px-4
                        dark:file:bg-neutral-700 dark:file:text-neutral-400"

                        @change="handleFile"
                        accept=".xlsx, .xls"
                    >
                </form>

            </template>
            <template #buttons>
                <button
                    :disabled="!file || loading"
                    @click="importFileUploadStart"
                    class="btn btn-primary"
                    >
                    <svg v-show="loading" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                    </svg>
                    {{ loading ? "Cargando..." : "Importar Archivo" }}
                </button>
            </template>
        </ModalLarge>

        <TransitionRoot appear :show="displayModalImportDetails" as="template">
            <Dialog as="div" class="relative z-50">
                <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                    <DialogOverlay class="fixed inset-0 bg-[black]/60" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-start justify-center px-4 py-8">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="relative overflow-hidden w-full max-w-3xl py-8">
                            <button @click="closeModalImportDetails" type="button" class="absolute top-4 ltr:right-4 rtl:left-4 text-gray-400 hover:text-gray-800 dark:hover:text-gray-600 outline-none" >
                                <svg width="24" height="24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                    <path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/>
                                </svg>
                            </button>
                            <div class="p-5">
                                <div
                                    style="background-color: #000; color: #fff; padding: 1rem; border-radius: 8px;"
                                >
                                    <div class="mb-4">
                                        <div class="flex justify-between mb-1">
                                            <span class="text-base font-medium text-gray-100 dark:text-white">Importando alumnos:</span>
                                            <span class="text-sm font-medium text-gray-200 dark:text-white">{{ parseInt(progressSend) == 99 ? 100 : parseInt(progressSend) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                            <div class="bg-blue-600 h-2.5 rounded-full" :style="`width: ${progressSend}%`"></div>
                                        </div>
                                    </div>
                                    <template v-if="importStatus.length > 0">
                                        <div ref="scrollContainer" class="scroll-box-result">
                                            <div>
                                                <template v-for="(resImport, co) in importStatus">
                                                    <div v-if="resImport.success" v-bind:style="{ borderBottom: co !== importStatus.length - 1 ? '1px dotted #a9cdf7' : 'none' }">
                                                        <code style="color: #60a5fa;">
                                                            <span>Alumno: <strong>{{ resImport.dni }} {{ resImport.name }}</strong>&nbsp;</span>
                                                            <span style="color: #a9cdf7;">{{ resImport.message }}</span>
                                                        </code>
                                                    </div>

                                                    <div v-if="!resImport.success" v-bind:style="{ borderBottom: co !== importStatus.length - 1 ? '1px dotted #a9cdf7' : 'none' }">
                                                        <code style="color: #ef4444;">
                                                            <span>Alumno: <strong>{{ resImport.dni }} {{ resImport.name }}</strong>&nbsp;</span>
                                                            <span style="color: #bc150b;">{{ resImport.message }} <span v-if="resImport.error">({{ resImport.error }})</span></span>
                                                        </code>
                                                    </div>
                                                </template>
                                            </div>
                                            <template v-if="loadingImport">
                                                <text-writing :texto="'...............'" />
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
        <TransitionRoot appear :show="displayModalSendDetails" as="template">
            <Dialog as="div" class="relative z-50">
                <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                    <DialogOverlay class="fixed inset-0 bg-[black]/60" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-start justify-center px-4 py-8">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="relative overflow-hidden w-full max-w-3xl py-8">
                            <button @click="closeModalSendDetails" type="button" class="absolute top-4 ltr:right-4 rtl:left-4 text-gray-400 hover:text-gray-800 dark:hover:text-gray-600 outline-none" >
                                <svg width="24" height="24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                    <path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/>
                                </svg>
                            </button>
                            <div class="p-5">
                                <div
                                    style="background-color: #000; color: #fff; padding: 1rem; border-radius: 8px;"
                                >
                                    <div class="mb-4">
                                        <div class="flex justify-between mb-1">
                                            <span class="text-base font-medium text-gray-100 dark:text-white">Resultados del envío de correos:</span>
                                            <span class="text-sm font-medium text-gray-200 dark:text-white">{{ parseInt(progressSendInvoice) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                            <div class="bg-blue-600 h-2.5 rounded-full" :style="`width: ${progressSendInvoice}%`"></div>
                                        </div>
                                    </div>

                                    <div ref="scrollContainerInvoice" class="scroll-box-result">
                                        <div v-if="resultSendEmail.length > 0">
                                            <div v-for="(row, ki) in resultSendEmail">
                                                <code v-if="row.success" style="color: #60a5fa;">
                                                    <span>Alumno: <strong>{{ row.result.procesado.full_name }}</strong>&nbsp;</span>
                                                    <span style="color: #a9cdf7;"> {{ row.result.procesado.razon }}</span>
                                                </code>
                                                <code v-else style="color: #ef4444;">
                                                    <span>Alumno: <strong>{{ row.result.error.full_name }}</strong>&nbsp;</span>
                                                    <span style="color: #bc150b;">{{ row.result.error.razon }}</span>
                                                </code>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>
