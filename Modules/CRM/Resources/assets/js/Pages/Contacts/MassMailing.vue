<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import iconSearch from '@/Components/vristo/icon/icon-search.vue';
import { Cascader, Input, Textarea } from 'ant-design-vue';
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';


const props = defineProps({
    courses: {
        type: Object,
        default: () => ({})
    }
});



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

const peopleResult = ref([]);

const emailForm = useForm({
    asunto: null,
    mensaje: null,
    para: []
});


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
                                <Link v-can="'crm_envio_correo_masivo'" :href="route('crm_send_mass_mailing')" class="inline-block px-6 py-2.5 bg-red-600 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-red-700 hover:shadow-lg focus:bg-red-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-red-800 active:shadow-lg transition duration-150 ease-in-out">Enviar Correo Masivo</Link>
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
                                <Cascader v-model:value="formSearch.type" :options="options" id="txtbuscarpor" placeholder="Seleccionar" />
                            </div>
                            <div>
                                <label for="txtbuscar" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Buscar por descripcion o dni</label>
                                <Input v-model:value="formSearch.search" id="txtbuscar" required />
                            </div>

                        </div>
                        <div v-if="peopleResult.length > 0" class="p-4 border border-white-dark/20 rounded-lg space-y-4 overflow-x-auto w-full block">
                            <template v-for="(item, i) in peopleResult" :key="i">
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
                                    min-w-[625px]
                                    hover:text-primary
                                    transition-all
                                    duration-300
                                    hover:scale-[1.01]
                                    "
                                >
                                    <div class="user-profile">
                                        <img :src="`/assets/images/${item.thumb}`" alt="" class="w-8 h-8 rounded-md object-cover" />
                                    </div>
                                    <div>{{ item.name }}</div>
                                    <div>{{ item.email }}</div>
                                    <div class="badge border-2 border-dashed" :class="item.statusClass">
                                        {{ item.status }}
                                    </div>
                                    <div class="cursor-pointer">
                                        
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="panel mt-5 md:mt-0 md:col-span-1">
                        <div>
                            <label for="txtasunto" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Asunto</label>
                            <Input v-model:value="emailForm.asunto" id="txtasunto" required />
                        </div>
                        <div class="mt-4">
                            <label for="txtasunto" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mensaje</label>
                            <Textarea
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