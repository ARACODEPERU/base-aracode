<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import { Link } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    const props = defineProps({
        empresa: {
            type: Object,
            default: () => ({})
        },
        employee: {
            type: Object,
            default: () => ({}),
        },
        courses: {
            type: Object,
            default: () => ({}),
        }
    });

    const baseUrl = assetUrl;
    const getFileAra = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    const getProgress = (regis) => {
        if (!regis.total_contents || regis.total_contents === 0) return 0;
        const percent = (regis.total_activity / regis.total_contents) * 100;
        return Math.round(percent); // Opcional: redondear el porcentaje
    };
</script>

<template>
    <AppLayout title="Empleados">
        <Navigation :routeModule="route('crm_dashboard')" :titleModule="'CRM'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('crm_companies_list')" class="text-primary hover:underline">Empresas</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('crm_companies_employees',empresa.id)" class="text-primary hover:underline">{{ empresa.full_name }}</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Empleado</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>{{ employee.full_name }}</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <div class="grid sm:grid-cols-3 gap-4">
                <div v-for="(regis, key) in courses" class="">
                    <div class="panel h-full">
                        <div class="">
                            <div class="flex">
                                <div class="flex-1 ltr:mr-4 rtl:ml-4">
                                    <h4 class="font-semibold text-lg mb-2 text-primary">{{ regis.course.description }}</h4>
                                    <p class="media-text">{{ regis.formatted_date }}</p>
                                </div>
                                <div>
                                    <img :src="getFileAra(regis.course.image)" alt="" class="w-16 h-16 rounded" />
                                </div>
                            </div>
                            <div class="mt-6 space-y-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-[#515365] font-semibold">Participacion en los comentarios</p>
                                    <p class="text-base">
                                        <span>#</span>
                                        <span class="font-semibold">{{ regis.total_coments }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-6">
                                <div class="flex justify-between mb-1">
                                    <span class="text-base font-medium text-gray-700 dark:text-white">Avence en contenido</span>
                                    <span class="text-sm font-medium text-gray-700 dark:text-white">{{ getProgress(regis) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                    <div class="bg-yellow-400 h-2.5 rounded-full" :style="{ width: getProgress(regis) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
