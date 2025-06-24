<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import { useForm, router, Link  } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Swal2 from 'sweetalert2';
    import { ConfigProvider, Dropdown, Menu, MenuItem, Button } from 'ant-design-vue';
    import IconBox from '@/Components/vristo/icon/icon-box.vue';
    import IconUserPlus from '@/Components/vristo/icon/icon-user-plus.vue';
    import IconSearch from '@/Components/vristo/icon/icon-search.vue';
    import iconExcel from "@/Components/vristo/icon/icon-excel.vue";
    import iconPencil from '@/Components/vristo/icon/icon-pencil.vue';
    import ModalLarge from "@/Components/ModalLarge.vue";
    import { ref } from 'vue';
    import Navigation from "@/Components/vristo/layout/Navigation.vue";
    import { useAppStore } from '@/stores/index';
    const store = useAppStore();

    const props = defineProps({
        students: {
            type: Object,
            default: () => ({}),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        search: props.filters.search,
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


    const file = ref(null);
    const loading = ref(false);
    const progress = ref(null); // Estado del progreso
    const importKey = ref(null); // Clave única para la importación

    const handleFileUpload = (event) => {
        file.value = event.target.files[0];
    };

    const startImport = async () => {
        loading.value = true; // Inicia el estado de carga

        const formData = new FormData();
        formData.append("file", file.value);

        axios.post(route('aca_student_import_file_excel'), formData)
            .then((response) => {
                importKey.value = response.data.importKey;
                trackProgress(); // Inicia la actualización del progreso
                showAlert("Procesando archivo, por favor espere.", 'success'); // Mostrar mensaje de éxito
            }).catch((error) => {
                // Verifica si hay un mensaje de error en la respuesta
                if (error.response && error.response.data && error.response.data.message) {
                    console.log('Error detectado:', error.response.data.message);
                    showAlert(error.response.data.message, 'error');
                } else {
                    console.log('Error genérico detectado');
                    showAlert("Ocurrió un error al importar el archivo.", 'error');
                }
            })
            .finally(() => {
                // Solo detener el estado de carga si no hay errores
                if (!importKey.value) {
                    loading.value = false;
                }
            });
    };
    const trackProgress = () => {
        const interval = setInterval(async () => {
            try {
                const response = await axios.get(route('aca_student_import_progress', importKey.value));
                progress.value = response.data.progress;

                if (progress.value >= 100) {
                    clearInterval(interval);
                    showAlert("Importación completada.", 'success');
                }
            } catch (error) {
                //console.error(error);
                clearInterval(interval);
            }  finally {
                loading.value = false;
                router.visit(route('aca_students_list'), {
                    method: 'get',
                    replace: true,
                    preserveState: true,
                    preserveScroll: false
                });
            }
        }, 5000); // Actualizar cada 5 segundos
    };

    const showAlert = async (msg, xicon = 'success') => {
        const toast = Swal2.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        toast.fire({
            icon: xicon,
            title: msg,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
</script>

<template>
    <AppLayout title="Estudiantes">
        <Navigation :routeModule="route('crm_dashboard')" :titleModule="'Academico'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Estudiantes</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Estudiantes</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <div>
                            <Link :href="route('aca_students_create')" type="button" class="btn btn-primary">
                                <icon-user-plus class="ltr:mr-2 rtl:ml-2" />
                                Nuevo
                            </Link>
                        </div>
                        <div v-can="'aca_estudiante_importar_excel'">
                            <button v-on:click="showModalImport()" type="button" class="btn btn-success">
                                <icon-excel class="ltr:mr-2 rtl:ml-2 w-4 h-4" />
                                Importar
                            </button>
                        </div>

                    </div>

                    <div class="relative">
                        <input
                            type="text"
                            placeholder="Buscar"
                            class="form-input py-2 ltr:pr-11 rtl:pl-11 peer dark:text-gray-100"
                            v-model="form.search"
                            @keyup.enter="form.get(route('aca_students_list'))"
                        />
                        <div class="absolute ltr:right-[11px] rtl:left-[11px] top-1/2 -translate-y-1/2 peer-focus:text-primary">
                            <icon-search class="mx-auto" />
                        </div>
                    </div>
                </div>
            </div>
            <template v-if="students.data && students.data.length > 0">
                <ConfigProvider>
                    <div class="mt-5 p-0 border-0 overflow-hidden">
                        <div class="grid 2xl:grid-cols-4 xl:grid-cols-3 sm:grid-cols-2 grid-cols-1 gap-6 w-full">
                            <template v-for="(student, index) in students.data">
                                <!-- Badge "Nuevo" en la parte superior izquierda -->
                                <!-- <div v-if="student.new_student"  class="absolute top-6 left-10 transform -translate-x-1/2 -translate-y-1/2 bg-red-500 text-white text-xs font-bold py-1 px-3 rounded ">
                                    Nuevo
                                </div> -->
                                <div class="bg-white dark:bg-[#1c232f] rounded-md overflow-hidden text-center shadow relative">
                                    <div :class="`bg-white/40 rounded-t-md bg-[url('/themes/vristo/images/notification-bg.png')] bg-center bg-cover p-6 pb-0`">
                                        <template v-if="student.people_image">
                                            <img :src="getImage(student.people_image)" class="object-contain w-4/5 max-h-40 mx-auto" :alt="student.full_name"/>
                                        </template>
                                        <template v-else>
                                            <img :src="'https://ui-avatars.com/api/?name='+student.full_name+'&rounded=false'" class="object-contain w-4/5 max-h-40 mx-auto" :alt="student.full_name"/>
                                        </template>
                                    </div>
                                    <div class="px-6 pb-24 -mt-10 relative">
                                        <div class="shadow-md bg-white dark:bg-gray-900 rounded-md px-2 py-4">
                                            <div class="text-xl">{{ student.full_name }}</div>
                                            <div class="text-white-dark">{{ student.role }}</div>
                                            <div class="flex items-center justify-between flex-wrap mt-6 gap-3">
                                                <div class="flex-auto">
                                                    <div class="text-info">{{ student.countCourses ?? 0 }}</div>
                                                    <div>Cursos</div>
                                                </div>
                                                <div class="flex-auto">
                                                    <div class="text-info">{{ student.countSubscriptions ?? 0 }}</div>
                                                    <div>Suscripciones</div>
                                                </div>
                                                <div class="flex-auto">
                                                    <div class="text-info">{{ student.countCertificates ?? 0 }}</div>
                                                    <div>Certificados</div>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <ul class="flex space-x-4 rtl:space-x-reverse items-center justify-center">
                                                    <li>
                                                        <Link :href="route('aca_students_edit', student.id)" v-tippy="{ content: 'Editar', placement: 'bottom'}" class="btn btn-outline-primary p-0 h-7 w-7 rounded-full">
                                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                                <path fill="currentColor" d="M362.7 19.3L314.3 67.7 444.3 197.7l48.4-48.4c25-25 25-65.5 0-90.5L453.3 19.3c-25-25-65.5-25-90.5 0zm-71 71L58.6 323.5c-10.4 10.4-18 23.3-22.2 37.4L1 481.2C-1.5 489.7 .8 498.8 7 505s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L421.7 220.3 291.7 90.3z"/>
                                                            </svg>
                                                        </Link>
                                                    </li>
                                                    <li v-can="'aca_estudiante_cobrar'">
                                                        <Link :href="route('aca_student_invoice', student.id)" v-tippy="{ content: 'Cobrar', placement: 'bottom'}" class="btn btn-outline-primary p-0 h-7 w-7 rounded-full">
                                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                                <path fill="currentColor" d="M64 0C46.3 0 32 14.3 32 32l0 64c0 17.7 14.3 32 32 32l80 0 0 32-57 0c-31.6 0-58.5 23.1-63.3 54.4L1.1 364.1C.4 368.8 0 373.6 0 378.4L0 448c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-69.6c0-4.8-.4-9.6-1.1-14.4L488.2 214.4C483.5 183.1 456.6 160 425 160l-217 0 0-32 80 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32L64 0zM96 48l160 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L96 80c-8.8 0-16-7.2-16-16s7.2-16 16-16zM64 432c0-8.8 7.2-16 16-16l352 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L80 448c-8.8 0-16-7.2-16-16zm48-168a24 24 0 1 1 0-48 24 24 0 1 1 0 48zm120-24a24 24 0 1 1 -48 0 24 24 0 1 1 48 0zM160 344a24 24 0 1 1 0-48 24 24 0 1 1 0 48zM328 240a24 24 0 1 1 -48 0 24 24 0 1 1 48 0zM256 344a24 24 0 1 1 0-48 24 24 0 1 1 0 48zM424 240a24 24 0 1 1 -48 0 24 24 0 1 1 48 0zM352 344a24 24 0 1 1 0-48 24 24 0 1 1 0 48z"/>
                                                            </svg>
                                                        </Link>
                                                    </li>
                                                    <li v-can="'aca_estudiante_listar_comprobantes'">
                                                        <Link :href="route('aca_student_invoice_list', student.id)" v-tippy="{ content: 'Lista de comprobantes', placement: 'bottom'}" class="btn btn-outline-primary p-0 h-7 w-7 rounded-full">
                                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                                                <path fill="currentColor" d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-288-128 0c-17.7 0-32-14.3-32-32L224 0 64 0zM256 0l0 128 128 0L256 0zM64 80c0-8.8 7.2-16 16-16l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L80 96c-8.8 0-16-7.2-16-16zm0 64c0-8.8 7.2-16 16-16l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16zm128 72c8.8 0 16 7.2 16 16l0 17.3c8.5 1.2 16.7 3.1 24.1 5.1c8.5 2.3 13.6 11 11.3 19.6s-11 13.6-19.6 11.3c-11.1-3-22-5.2-32.1-5.3c-8.4-.1-17.4 1.8-23.6 5.5c-5.7 3.4-8.1 7.3-8.1 12.8c0 3.7 1.3 6.5 7.3 10.1c6.9 4.1 16.6 7.1 29.2 10.9l.5 .1s0 0 0 0s0 0 0 0c11.3 3.4 25.3 7.6 36.3 14.6c12.1 7.6 22.4 19.7 22.7 38.2c.3 19.3-9.6 33.3-22.9 41.6c-7.7 4.8-16.4 7.6-25.1 9.1l0 17.1c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-17.8c-11.2-2.1-21.7-5.7-30.9-8.9c0 0 0 0 0 0c-2.1-.7-4.2-1.4-6.2-2.1c-8.4-2.8-12.9-11.9-10.1-20.2s11.9-12.9 20.2-10.1c2.5 .8 4.8 1.6 7.1 2.4c0 0 0 0 0 0s0 0 0 0s0 0 0 0c13.6 4.6 24.6 8.4 36.3 8.7c9.1 .3 17.9-1.7 23.7-5.3c5.1-3.2 7.9-7.3 7.8-14c-.1-4.6-1.8-7.8-7.7-11.6c-6.8-4.3-16.5-7.4-29-11.2l-1.6-.5s0 0 0 0c-11-3.3-24.3-7.3-34.8-13.7c-12-7.2-22.6-18.9-22.7-37.3c-.1-19.4 10.8-32.8 23.8-40.5c7.5-4.4 15.8-7.2 24.1-8.7l0-17.3c0-8.8 7.2-16 16-16z"/>
                                                            </svg>
                                                        </Link>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="mt-6 grid grid-cols-1 gap-4 ltr:text-left rtl:text-right">
                                            <div class="flex items-center">
                                                <div class="flex-none ltr:mr-2 rtl:ml-2">Num. de identificación :</div>
                                                <div class="truncate text-white-dark">{{ student.number }}</div>
                                            </div>
                                            <div class="flex items-center">
                                                <div class="flex-none ltr:mr-2 rtl:ml-2">Email :</div>
                                                <div class="truncate text-white-dark">{{ student.email }}</div>
                                            </div>
                                            <div class="flex items-center">
                                                <div class="flex-none ltr:mr-2 rtl:ml-2">Teléfono :</div>
                                                <div class="text-white-dark">{{ student.telephone }}</div>
                                            </div>
                                            <div class="flex items-center">
                                                <div class="flex-none ltr:mr-2 rtl:ml-2">Dirección :</div>
                                                <div class="text-white-dark">{{ student.address }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-6 flex gap-4 absolute bottom-0 w-full ltr:left-0 rtl:right-0 p-6">
                                        <Link :href="route('aca_students_registrations_create',student.id)" class="btn btn-outline-primary w-1/2">Matriculas</Link>
                                        <Link v-can="'aca_estudiante_certificados_crear'" :href="route('aca_students_certificates_create',student.id)" class="btn btn-outline-danger w-1/2">Certificados</Link>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div>
                            <Pagination :data="students" />
                        </div>
                    </div>
                </ConfigProvider>
            </template>

            <template v-else>
                <div class="mt-5">
                        <div
                            class="
                                h-16
                                relative
                                flex
                                items-center
                                border
                                p-3.5
                                rounded
                                before:inline-block before:absolute before:top-1/2
                                ltr:before:right-0
                                rtl:before:left-0 rtl:before:rotate-180
                                before:-mt-2 before:border-r-8 before:border-t-8 before:border-b-8 before:border-t-transparent before:border-b-transparent before:border-r-inherit
                                text-danger
                                bg-danger-light
                                border-danger
                                ltr:border-r-[64px]
                                rtl:border-l-[64px]
                                dark:bg-danger-dark-light
                            "
                            >
                            <span class="absolute ltr:-right-11 rtl:-left-11 inset-y-0 text-white w-6 h-6 m-auto">
                                <icon-box />
                            </span>
                            <span class="ltr:pr-2 rtl:pl-2">
                                <strong class="ltr:mr-1 rtl:ml-1">Tabla vacía!</strong>No existen registros para mostrar.
                            </span>
                        </div>
                    </div>
            </template>
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

                    @change="handleFileUpload"
                    accept=".xlsx, .xls"
                    >
                </form>

            </template>
            <template #buttons>
                <button
                    :disabled="!file || loading"
                    @click="startImport"
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
    </AppLayout>
</template>
