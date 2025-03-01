<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogOverlay } from '@headlessui/vue';
    import { Cascader, Input, Textarea, Select, SelectOption, notification } from 'ant-design-vue';
    import { ref, computed, onMounted, watch, nextTick  } from 'vue';
    import { useForm, Link, usePage } from '@inertiajs/vue3';
    import Keypad from '@/Components/Keypad.vue';
    import { Pagination } from 'flowbite-vue';
    import Swal from 'sweetalert2';
    import textWriting from '@/Components/loader/text-writing.vue';

    const props = defineProps({
        courses: {
            type: Object,
            default: () => ({})
        },
    });

    const appCodeUnique = import.meta.env.VITE_APP_CODE ?? 'ARACODE';
    const channelListen = "email-status-" + appCodeUnique + '-' + usePage().props.auth.user.id;
    const loadingPrev = ref(false);
    const loadingNext = ref(false);

    const options = computed(() => [
        {
            value: 'new',
            label: 'Nuevos Estudiantes',
        },
        {
            value: 'cur',
            label: 'Cursos',
            children: props.courses.map((obj) => ({value:obj.id,label:obj.description})),
        },
        {
            value: 'all',
            label: 'Todos',
        },
    ]);

    const formSearch = ref({
        type: null,
        search: null,
        number_records: 10
    });

    const emailForm = useForm({
        asunto: null,
        mensaje: null,
        correoDefault: null,
        csrfToken: null,
        urlBacken: route('crm_contacts_send_mail_post'),
        channelListen: channelListen,
        para: []
    });

    const baseUrl = assetUrl;
    const students = ref([]);
    const paginacion = ref([]);
    const selectAll = ref(false); // Checkbox "Marcar/Desmarcar Todos"
    const selectedStudents = ref([])
    const scrollContainer = ref(null);

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    const getContactsPagination = () => {

        axios({
            method: 'post',
            url: route('crm_contacts_list_pagination'),
            data: { search: formSearch.value }
        }).then((response) => {
            students.value = response.data.data;
            paginacion.value = response.data;
            selectedStudents.value = students.value.map(() => false);
        });
    }

    const getDoSomething = (url, act) => {
        if(act == 'prev'){
            loadingPrev.value = !loadingPrev.value
        }else{
            loadingNext.value = !loadingNext.value
        }

        axios({
            method: 'post',
            url: url,
            data: { search: formSearch.value }
        }).then((response) => {
            students.value = response.data.data;
            paginacion.value = response.data;
            selectedStudents.value = students.value.map(() => false);
        }).finally(() => {
            if(act == 'prev'){
                loadingPrev.value = !loadingPrev.value
            }else{
                loadingNext.value = !loadingNext.value
            }
        });
    }

    const toggleSelectAll = () => {

        selectedStudents.value = selectedStudents.value.map(() => true);

        students.value.forEach((student) => {
            if (!emailForm.para.some((item) => item.id === student.id)) {
                emailForm.para.push(student);
            }
        });

    };

    const toggleDeSelectAll = () => {
        emailForm.para = [];
    };

    onMounted(() => {
        getContactsPagination();
        window.socketIo.on(channelListen, (status) => {
            emailStatus.value.push(status);
            progressSend.value = parseFloat(progressSend.value) + parseFloat(porsentaje.value)
            nextTick(() => {
                if (scrollContainer.value) {
                    scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight;
                }
            });
        });
    });

    // Observa cambios en los checkboxes individuales
    watch(selectedStudents, (newValues) => {
        selectAll.value = newValues.every((isSelected) => isSelected);
    }, { deep: true });


    const selectEmailDeafult = () => {
        let df = emailForm.correoDefault;

        if(df == 'ccu'){
            emailForm.asunto = 'Cuenta de usuario';
        } else if(df == 'cdb'){
            emailForm.asunto = 'Correo de bienvenida';
        } else if(df == 'ccc'){
            emailForm.asunto = 'Certificados de sus cursos';
        } else {
            emailForm.asunto = null;
        }
    }

    const updateSelectedItem = (item) => {
        // Verifica si el elemento ya existe en el array antes de agregarlo
        if (!emailForm.para.some(selectedItem => selectedItem.id === item.id)) {
            emailForm.para.push(item);
        }else{
            console.log(emailForm.para)
        }
    };

    const removeSelectedItem = (item) => {
        // Elimina el elemento del array si existe
        emailForm.para = emailForm.para.filter(selectedItem => selectedItem.id !== item.id);
    };

    const loadingSend = ref(false);
    const displayModalDetails = ref(false);
    const progressSend = ref(0);
    const porsentaje = ref(0);

    const sendEmails = async () => {
        emailStatus.value = [];
        porsentaje.value = 0;
        progressSend.value = 0;
        // Validar si el asunto está vacío
        if (!emailForm.asunto) {
            showMessage("El campo 'Asunto' es obligatorio.",'info');
            return; // Detiene la ejecución si está vacío
        }

        // Validar si el correo predeterminado está vacío
        if (!emailForm.correoDefault) {
            showMessage("El campo 'Correo predeterminado' es obligatorio.",'info');
            return; // Detiene la ejecución si está vacío
        }

        // Validar si el array 'para' está vacío
        if (emailForm.para.length === 0) {
            showMessage("Debe agregar al menos un destinatario.",'info');
            return; // Detiene la ejecución si no hay destinatarios
        }
        loadingSend.value = true;

        displayModalDetails.value = true;
        const url = import.meta.env.VITE_SOCKET_IO_SERVER + '/api/academic/send-mails'; // Cambia por la URL de tu API
        let emailstotal = emailForm.para.length;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        emailForm.csrfToken = csrfToken;
        porsentaje.value = (1 / parseInt(emailstotal)) * 100;

         axios.post(url,emailForm,{
            headers: {
                'Content-Type': 'application/json'
            },
            timeout: 0,
        }).finally(()=>{
            loadingSend.value = false;
        });

    }

    const closeModalDetails = () => {
        displayModalDetails.value = false
    }

    const emailStatus = ref([])

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
</script>
<template>
    <AppLayout title="Contactos">
        <Navigation :routeModule="route('crm_dashboard')" :titleModule="'CRM'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <Link :href="route('crm_contacts_list')" >Contactos</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Enviar Correos</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Lista de contactos </h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <Keypad>
                            <template #botones>
                                <Link v-can="'crm_contactos_listado'" :href="route('crm_contacts_list')" class="inline-block px-6 py-2.5 bg-yellow-600 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-yellow-700 hover:shadow-lg focus:bg-yellow-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-yellow-800 active:shadow-lg transition duration-150 ease-in-out">ir al listado</Link>
                            </template>
                        </Keypad>

                    </div>
                </div>
            </div>
            <div class="mt-6">
                <div class="md:grid md:grid-cols-2 md:gap-4 mb-4">
                    <div class="panel mt-5 md:mt-0 md:col-span-1">
                        <div class="grid gap-6 mb-6 md:grid-cols-2">
                            <div>
                                <label for="txtbuscarpor" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tipo de busqueda</label>
                                <Cascader
                                    v-model:value="formSearch.type"
                                    :options="options"
                                    id="txtbuscarpor"
                                    placeholder="Seleccionar"
                                    @change="getContactsPagination"
                                    style="width: 100%"
                                 />
                            </div>
                            <div>
                                <label for="txtbuscar" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Buscar por descripcion o dni</label>
                                <Input v-model:value="formSearch.search" @keyup.enter="getContactsPagination"  id="txtbuscar" />
                            </div>
                            <div class="flex items-center justify-between col-span-2 pr-6">
                                <div class="flex items-center space-x-2">
                                    <Select v-if="paginacion.total > 10"
                                        v-model:value="formSearch.number_records"
                                        style="width: 80px"
                                        @change="getContactsPagination"
                                    >
                                        <SelectOption value="10">10</SelectOption>
                                        <SelectOption value="50">50</SelectOption>
                                        <SelectOption value="100">100</SelectOption>
                                        <SelectOption value="todos">Todos</SelectOption>
                                    </Select>
                                    <span class="dark:text-info" v-if="paginacion.total">{{ paginacion.total }} Total</span>
                                </div>
                                <button @click="toggleSelectAll" type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm p-2 text-center inline-flex items-center me-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                        <path stroke="currentColor" d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z"/>
                                    </svg>
                                    Agregar todo
                                </button>
                            </div>
                        </div>
                        <div class="border border-white-dark/20 rounded-lg overflow-x-auto w-full block">
                            <div v-if="students.length > 0" class="space-y-4 max-h-[850px] overflow-y-auto p-4">
                                <template v-for="(item, ixx) in students" :key="ixx">
                                    <div
                                        class="
                                        bg-white
                                        dark:bg-[#1b2e4b]
                                        rounded-xl
                                        shadow-[0_0_4px_2px_rgb(31_45_61_/_10%)]
                                        p-3
                                        flex
                                        items-center
                                        justify-between
                                        text-gray-500
                                        font-semibold
                                        hover:text-primary
                                        transition-all
                                        duration-300
                                        hover:scale-[1.01]
                                        "
                                    >
                                        <div class="flex space-x-4 items-center">
                                            <div class="user-profile">
                                                <template v-if="item.image">
                                                    <img :src="getImage(item.image)" class="w-8 h-8 rounded-md object-cover" :alt="item.full_name"/>
                                                </template>
                                                <template v-else>
                                                    <img :src="'https://ui-avatars.com/api/?name='+item.full_name+'&size=96&rounded=false'" class="w-8 h-8 rounded-md object-cover" :alt="item.full_name"/>
                                                </template>
                                            </div>
                                            <div>
                                                <h6 class="font-semibold dark:text-white-light">{{ item.full_name }}</h6>
                                                <p>{{ item.email }}</p>
                                            </div>
                                            <!-- <div v-if="item.new_student" class="badge bg-success h-6">
                                                Nuevo
                                            </div> -->
                                        </div>

                                        <button
                                            :id="`student-send-${ixx}`"
                                            @click="updateSelectedItem(item,ixx)"
                                            type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm p-2 text-center inline-flex items-center me-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                            <svg class="w-5 h-5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                                <path stroke="currentColor" d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z"/>
                                            </svg>
                                            <span class="sr-only">Agregar</span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <div class="flex items-center justify-center text-center">
                                <Pagination
                                    v-model="paginacion.current_page"
                                    :layout="'table'"
                                    :per-page="paginacion.per_page"
                                    :total-items="paginacion.total"
                                    :previousLabel="'Atras'"
                                    :nextLabel="'Siguiente'"
                                    class="mb-2"
                                >
                                    <template #prev-button>
                                        <button @click="getDoSomething(paginacion.prev_page_url,'prev')" :disabled="paginacion.to == 1" class="btn btn-outline-warning btn-sm mr-1">
                                            <svg v-show="loadingPrev" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                            </svg>
                                            Atras
                                        </button>
                                    </template>
                                    <template #next-button>
                                        <button @click="getDoSomething(paginacion.next_page_url,'next')" class="btn btn-outline-warning btn-sm ml-1">
                                            <svg v-show="loadingNext" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                            </svg>
                                            Siguiente
                                        </button>
                                    </template>
                                </Pagination>
                            </div>
                        </div>
                    </div>
                    <div class="panel mt-5 md:mt-0 md:col-span-1">
                        <div>
                            <label for="txtcorreoDefault" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Plantilla de correo</label>
                            <Select
                                ref="select"
                                v-model:value="emailForm.correoDefault"
                                style="width: 100%"
                                @change="selectEmailDeafult"
                            >
                                <SelectOption value="ccu">Correo con cuenta de usuario</SelectOption>
                                <!-- <SelectOption value="cdb">Correo de bienvenida</SelectOption>
                                <SelectOption value="ccc">Correo con certificados</SelectOption> -->
                                <SelectOption value="cmp">Correo con mensaje personalizado</SelectOption>
                            </Select>
                        </div>
                        <div>
                            <label for="txtasunto" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Asunto</label>
                            <Input
                                :disabled="emailForm.correoDefault != 'cmp'"
                                v-model:value="emailForm.asunto"
                                id="txtasunto"
                                required />
                        </div>
                        <div class="mt-4">
                            <label for="txtasunto" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mensaje</label>
                            <Textarea
                                :disabled="emailForm.correoDefault != 'cmp'"
                                v-model:value="emailForm.mensaje"
                                placeholder="Escriba mensaje"
                                :auto-size="{ minRows: 8, maxRows: 12 }"
                            />

                        </div>


                        <div v-if="emailForm.para.length > 0" class="relative overflow-x-auto mt-4 shadow-md sm:rounded-lg max-h-[600px] overflow-y-auto">
                            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">
                                            Accion
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            Nombre
                                        </th>

                                    </tr>
                                </thead>
                                <tbody >
                                    <template v-for="(contact, key) in emailForm.para">
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                            <td class="px-6 py-2">
                                                <button
                                                    :id="`student-send-${key}`"
                                                    @click="removeSelectedItem(contact,key)"
                                                    type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm p-2 text-center inline-flex items-center me-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                                    <svg  class="w-5 h-5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                                        <path stroke="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.2 288 416 288c17.7 0 32-14.3 32-32s-14.3-32-32-32l-306.7 0L214.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"/>
                                                    </svg>
                                                    <span class="sr-only">Quitar</span>
                                                </button>
                                            </td>
                                            <th scope="row" class="px-3 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                <div class="flex space-x-4 items-center">
                                                    <div class="user-profile">
                                                        <template v-if="contact.image">
                                                            <img :src="getImage(contact.image)" class="w-8 h-8 rounded-md object-cover" :alt="contact.full_name"/>
                                                        </template>
                                                        <template v-else>
                                                            <img :src="'https://ui-avatars.com/api/?name='+contact.full_name+'&size=96&rounded=false'" class="w-8 h-8 rounded-md object-cover" :alt="contact.full_name"/>
                                                        </template>
                                                    </div>
                                                    <div>
                                                        <h6 class="font-semibold dark:text-white-light">{{ contact.full_name }}</h6>
                                                        <p>{{ contact.email }}</p>
                                                    </div>
                                                </div>
                                            </th>

                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="md:grid md:grid-cols-3 md:gap-4 mt-4 items-center">
                            <div class="col-span-2">
                                <svg v-show="loadingSend" aria-hidden="true" role="status" class="inline w-8 h-8 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                </svg>
                                <button v-show="!loadingSend" @click="toggleDeSelectAll" type="button" class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm p-2 text-center inline-flex items-center me-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">
                                    <svg  class="w-5 h-5 mr-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                        <path stroke="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.2 288 416 288c17.7 0 32-14.3 32-32s-14.3-32-32-32l-306.7 0L214.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"/>
                                    </svg>
                                    Quitar todo
                                </button>
                            </div>
                            <div class="flex justify-end">
                                <button @click="sendEmails" type="button" :class="{ 'opacity-25': loadingSend }" :disabled="loadingSend" class="btn btn-primary w-[120px]">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                        <path d="M498.1 5.6c10.1 7 15.4 19.1 13.5 31.2l-64 416c-1.5 9.7-7.4 18.2-16 23s-18.9 5.4-28 1.6L284 427.7l-68.5 74.1c-8.9 9.7-22.9 12.9-35.2 8.1S160 493.2 160 480l0-83.6c0-4 1.5-7.8 4.2-10.8L331.8 202.8c5.8-6.3 5.6-16-.4-22s-15.7-6.4-22-.7L106 360.8 17.7 316.6C7.1 311.3 .3 300.7 0 288.9s5.9-22.8 16.1-28.7l448-256c10.7-6.1 23.9-5.5 34 1.4z"/>
                                    </svg>
                                    Enviar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <TransitionRoot appear :show="displayModalDetails" as="template">
            <Dialog as="div" @close="closeModalDetails" class="relative z-50">
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
                            <button @click="closeModalDetails" type="button" class="absolute top-4 ltr:right-4 rtl:left-4 text-gray-400 hover:text-gray-800 dark:hover:text-gray-600 outline-none" >
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
                                            <span class="text-sm font-medium text-gray-200 dark:text-white">{{ parseInt(progressSend) == 99 ? 100 : parseInt(progressSend) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                            <div class="bg-blue-600 h-2.5 rounded-full" :style="`width: ${progressSend}%`"></div>
                                        </div>
                                    </div>
                                    <template v-if="emailStatus.length > 0">
                                        <div ref="scrollContainer" class="scroll-box-result">
                                            <template v-for="(resEmail, co) in emailStatus">

                                                <div v-bind:style="{ borderBottom: co !== emailStatus.length - 1 ? '1px dotted #a9cdf7' : 'none' }">
                                                    <div v-if="resEmail.success" >
                                                        <code style="color: #60a5fa;">
                                                            <span>{{ resEmail.email }} <span style="color: #a9cdf7;">{{ resEmail.status }}</span></span>
                                                        </code>
                                                    </div>

                                                    <div v-if="!resEmail.success" >
                                                        <code style="color: #ef4444;">
                                                            {{ resEmail.email ?? 'Nulo o vacio' }} {{ resEmail.status }} {{ resEmail.error }}
                                                        </code>
                                                    </div>
                                                </div>

                                            </template>
                                            <template v-if="loadingSend">
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
    </AppLayout>
</template>
<style>
.scroll-box-result {
  height: 350px;
  overflow-y: auto;
  border: 1px solid #757575;
  padding: 10px;
}
</style>
