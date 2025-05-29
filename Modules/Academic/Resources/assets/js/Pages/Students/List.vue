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
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-for="(student, index) in students.data" class="relative">
                                <!-- Badge "Nuevo" en la parte superior izquierda -->
                                <div v-if="student.new_student"  class="absolute top-6 left-10 transform -translate-x-1/2 -translate-y-1/2 bg-red-500 text-white text-xs font-bold py-1 px-3 rounded ">
                                    Nuevo
                                </div>
                                <div class="w-full max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                                    <div class="flex justify-end px-4 pt-4">
                                        <div class="dropdown">
                                            <Popper :placement="store.rtlClass === 'rtl' ? 'bottom-start' : 'bottom-end'" offsetDistance="0" class="align-middle">
                                                <button type="button" class="btn p-0 rounded-none border-0 shadow-none dropdown-toggle text-black dark:text-white-dark hover:text-primary dark:hover:text-primary">
                                                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 3">
                                                        <path d="M2 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm6.041 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM14 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z"/>
                                                    </svg>
                                                </button>
                                                <template #content="{ close }">
                                                    <ul @click="close()" class="whitespace-nowrap">
                                                        <li>
                                                            <Link :href="route('aca_students_edit', student.id)" type="Button" class="dark:text-white">
                                                                Editar
                                                            </Link>
                                                        </li>
                                                        <li v-can="'aca_estudiante_cobrar'">
                                                            <Link :href="route('aca_student_invoice', student.id)" type="Button" class="text-warning">
                                                                Cobrar
                                                            </Link>
                                                        </li>
                                                        <li v-can="'aca_estudiante_listar_comprobantes'">
                                                            <Link :href="route('aca_student_invoice_list', student.id)" type="Button" class="text-info">
                                                                Lista de Comprobantes
                                                            </Link>
                                                        </li>
                                                    </ul>
                                                </template>
                                            </Popper>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-center pb-10">
                                        <template v-if="student.people_image">
                                            <img :src="getImage(student.people_image)" style="width: 96px; height: 96px;" class="mb-3 rounded-full shadow-lg" :alt="student.full_name"/>
                                        </template>
                                        <template v-else>
                                            <img :src="'https://ui-avatars.com/api/?name='+student.full_name+'&size=96&rounded=true'" class="w-24 h-24 mb-3 rounded-full shadow-lg" :alt="student.full_name"/>
                                        </template>
                                        <h5 class="mb-1 text-xl font-medium text-gray-900 dark:text-white">{{ student.number }}</h5>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 p-2">{{ student.full_name }}</span>
                                        <div class="flex mt-4 space-x-3 mb-2 md:mt-6">
                                            <Link :href="route('aca_students_registrations_create',student.id)" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Matriculas</Link>
                                            <Link v-can="'aca_estudiante_certificados_crear'" :href="route('aca_students_certificates_create',student.id)" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-700 dark:focus:ring-gray-700">Certificados</Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
