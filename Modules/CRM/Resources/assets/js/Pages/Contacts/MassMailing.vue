<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import iconSearch from '@/Components/vristo/icon/icon-search.vue';
    import { Cascader, Input, Textarea, Select, SelectOption } from 'ant-design-vue';
    import { ref, computed, onMounted, watch } from 'vue';
    import { useForm, Link } from '@inertiajs/vue3';
    import Keypad from '@/Components/Keypad.vue';
    import { Pagination } from 'flowbite-vue';

    const props = defineProps({
        courses: {
            type: Object,
            default: () => ({})
        },
    });

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
        search: null
    });

    const emailForm = useForm({
        asunto: null,
        mensaje: null,
        correoDefault: null,
        para: []
    });

    const baseUrl = assetUrl;
    const students = ref([]);
    const paginacion = ref([]);
    const selectAll = ref(false); // Checkbox "Marcar/Desmarcar Todos"
    const selectedStudents = ref([])

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    const getContactsPagination = () => {
        
        axios({
            method: 'post',
            url: route('crm_contacts_list_data'),
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
        selectedStudents.value = selectedStudents.value.map(() => selectAll.value);
    };

    onMounted(() => { 
        getContactsPagination();
    });

    // Observa cambios en los checkboxes individuales
    watch(selectedStudents, (newValues) => {
        selectAll.value = newValues.every((isSelected) => isSelected);
    }, { deep: true });
</script>
<template>
    <AppLayout title="Contactos">
        <Navigation :routeModule="route('crm_dashboard')" :titleModule="'CRM'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Contactos</span>
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
                                 />
                            </div>
                            <div>
                                <label for="txtbuscar" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Buscar por descripcion o dni</label>
                                <Input v-model:value="formSearch.search" id="txtbuscar" required />
                            </div>
                            <div class="flex items-center justify-end col-span-2 pr-8">
                                <label class="inline-flex space-x-4">
                                    <span>TODOS</span>
                                    <input 
                                        @change="toggleSelectAll"
                                        type="checkbox" 
                                        class="form-checkbox" 
                                        :id="'checkbox-all'"
                                        v-model="selectAll"
                                    />
                                </label>
                            </div>
                        </div>
                        <div class="p-4 border border-white-dark/20 rounded-lg space-y-4 overflow-x-auto w-full block">
                            <template v-if="students.length > 0">
                                <template v-for="(item, i) in students" :key="i">
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
                                        <div class="flex space-x-4">
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
                                        
                                        <label class="inline-flex">
                                            <input 
                                                type="checkbox" 
                                                class="form-checkbox" 
                                                :id="`student-send-${i}`" 
                                                v-model="selectedStudents[i]"
                                            />
                                        </label>
                                    </div>
                                </template>
                            </template>
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
                            <label for="txtcorreoDefault" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Asunto</label>
                            <Select
                                ref="select"
                                v-model:value="emailForm.correoDefault"
                                style="width: 100%"
                            >
                                <SelectOption value="ccu">Correo con cuenta de usuario</SelectOption>
                                <SelectOption value="cdb">Correo de bienvenida</SelectOption>
                                <SelectOption value="ccc">Correo con certificados</SelectOption>
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
                    </div>
                </div>
                
            </div>
        </div>
        
    </AppLayout>
</template>