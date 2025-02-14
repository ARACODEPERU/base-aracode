<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Keypad from '@/Components/Keypad.vue';
    import Pagination from '@/Components/Pagination.vue';
    import Swal2 from "sweetalert2";
    import { Link, router, useForm } from '@inertiajs/vue3';
    import { faXmark, faGears, faTrashAlt, faCheck, faSpellCheck, faDownload, faPlay, faFile, faFilm } from "@fortawesome/free-solid-svg-icons";
    import ModalLarge from '@/Components/ModalLarge.vue';
    import { ref, onMounted } from 'vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import iconExcel from "@/Components/vristo/icon/icon-excel.vue";
    import { ConfigProvider, Dropdown,Menu,MenuItem,Button } from 'ant-design-vue';
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import IconSearch from '@/Components/vristo/icon/icon-search.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

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

    onMounted(() => {
        studentsData.value = props.students;
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
        leaderCertificate.value = true;
        axios({
            method: 'put',
            url: route('aca_certificate_massive_store',props.course.id),
            data: {students: studentsData.value}
        }).then(() => {
            reloadPage();
        }).finally(() => {
            leaderCertificate.value = false;
        });
    };

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
                                <Link :href="route('aca_courses_create')" type="button" class="btn btn-success text-xs px-4 py-2 uppercase">
                                    <icon-excel class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                                    Importar desde Excel
                                </Link>
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
                                <button @click="createCertificates" :class="{ 'opacity-25': leaderCertificate }" :disabled="leaderCertificate" type="button" class="flex items-center p-2 text-blue-700 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                                    <svg v-if="leaderCertificate" aria-hidden="true" role="status" class="inline w-5 h-5 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                    </svg>
                                    <svg v-else class="w-5 h-5 transition duration-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" fill="currentColor">
                                        <path d="M173.8 5.5c11-7.3 25.4-7.3 36.4 0L228 17.2c6 3.9 13 5.8 20.1 5.4l21.3-1.3c13.2-.8 25.6 6.4 31.5 18.2l9.6 19.1c3.2 6.4 8.4 11.5 14.7 14.7L344.5 83c11.8 5.9 19 18.3 18.2 31.5l-1.3 21.3c-.4 7.1 1.5 14.2 5.4 20.1l11.8 17.8c7.3 11 7.3 25.4 0 36.4L366.8 228c-3.9 6-5.8 13-5.4 20.1l1.3 21.3c.8 13.2-6.4 25.6-18.2 31.5l-19.1 9.6c-6.4 3.2-11.5 8.4-14.7 14.7L301 344.5c-5.9 11.8-18.3 19-31.5 18.2l-21.3-1.3c-7.1-.4-14.2 1.5-20.1 5.4l-17.8 11.8c-11 7.3-25.4 7.3-36.4 0L156 366.8c-6-3.9-13-5.8-20.1-5.4l-21.3 1.3c-13.2 .8-25.6-6.4-31.5-18.2l-9.6-19.1c-3.2-6.4-8.4-11.5-14.7-14.7L39.5 301c-11.8-5.9-19-18.3-18.2-31.5l1.3-21.3c.4-7.1-1.5-14.2-5.4-20.1L5.5 210.2c-7.3-11-7.3-25.4 0-36.4L17.2 156c3.9-6 5.8-13 5.4-20.1l-1.3-21.3c-.8-13.2 6.4-25.6 18.2-31.5l19.1-9.6C65 70.2 70.2 65 73.4 58.6L83 39.5c5.9-11.8 18.3-19 31.5-18.2l21.3 1.3c7.1 .4 14.2-1.5 20.1-5.4L173.8 5.5zM272 192a80 80 0 1 0 -160 0 80 80 0 1 0 160 0zM1.3 441.8L44.4 339.3c.2 .1 .3 .2 .4 .4l9.6 19.1c11.7 23.2 36 37.3 62 35.8l21.3-1.3c.2 0 .5 0 .7 .2l17.8 11.8c5.1 3.3 10.5 5.9 16.1 7.7l-37.6 89.3c-2.3 5.5-7.4 9.2-13.3 9.7s-11.6-2.2-14.8-7.2L74.4 455.5l-56.1 8.3c-5.7 .8-11.4-1.5-15-6s-4.3-10.7-2.1-16zm248 60.4L211.7 413c5.6-1.8 11-4.3 16.1-7.7l17.8-11.8c.2-.1 .4-.2 .7-.2l21.3 1.3c26 1.5 50.3-12.6 62-35.8l9.6-19.1c.1-.2 .2-.3 .4-.4l43.2 102.5c2.2 5.3 1.4 11.4-2.1 16s-9.3 6.9-15 6l-56.1-8.3-32.2 49.2c-3.2 5-8.9 7.7-14.8 7.2s-11-4.3-13.3-9.7z"/>
                                    </svg>
                                    <span class="ms-3">Activar la descarga de certificados</span>
                                </button>
                            </li>
                            <li>
                                <button class="flex items-center p-2 text-green-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                                    <svg class="w-5 h-5 transition duration-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" fill="currentColor">
                                        <path d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-288-128 0c-17.7 0-32-14.3-32-32L224 0 64 0zM256 0l0 128 128 0L256 0zM80 64l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L80 96c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm16 96l192 0c17.7 0 32 14.3 32 32l0 64c0 17.7-14.3 32-32 32L96 352c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32zm0 32l0 64 192 0 0-64L96 256zM240 416l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16z"/>
                                    </svg>
                                    <span class="ms-3">Generar y enviar documento de venta</span>
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
                                            <td class="flex justify-center items-center">
                                                <label class="inline-flex">
                                                    <input v-model="row.checkbox" :id="'chxStudent'+index" type="checkbox" class="form-checkbox outline-primary" />
                                                </label>
                                            </td>
                                            <td>{{ row.student.person.full_name }}</td>
                                            <td>
                                                <span v-if="row.document_id" class="badge badge-outline-primary">B001-4354</span>
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

                            </ConfigProvider>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
