<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const reportsData = [

    {
        title: 'Ventas',
        items: [
            { url: route('aca_student_payment_report_bank'), label: 'Reporte de pagos de alumnos por banco'},
        ]
    },
];

</script>

<template>
    <AppLayout title="Reportes">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Academic'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Reportes</span>
            </li>
        </Navigation>
        <div class="mt-5">
                <div class="grid sm:grid-cols-3 justify-items-center gap-6" >
                    <template v-for="(row, key) in reportsData">
                        <div class="panel w-full">
                            <div class="flex flex-col justify-center text-center p-4 md:p-5">
                                <div class="flex justify-center items-center size-12 bg-linear-to-br from-blue-600 to-violet-600 rounded-lg mx-auto">
                                    <svg fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                        <path d="M470.7 9.4c3 3.1 5.3 6.6 6.9 10.3s2.4 7.8 2.4 12.2c0 0 0 .1 0 .1c0 0 0 0 0 0l0 96c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-18.7L310.6 214.6c-11.8 11.8-30.8 12.6-43.5 1.7L176 138.1 84.8 216.3c-13.4 11.5-33.6 9.9-45.1-3.5s-9.9-33.6 3.5-45.1l112-96c12-10.3 29.7-10.3 41.7 0l89.5 76.7L370.7 64 352 64c-17.7 0-32-14.3-32-32s14.3-32 32-32l96 0s0 0 0 0c8.8 0 16.8 3.6 22.6 9.3l.1 .1zM0 304c0-26.5 21.5-48 48-48l416 0c26.5 0 48 21.5 48 48l0 160c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 304zM48 416l0 48 48 0c0-26.5-21.5-48-48-48zM96 304l-48 0 0 48c26.5 0 48-21.5 48-48zM464 416c-26.5 0-48 21.5-48 48l48 0 0-48zM416 304c0 26.5 21.5 48 48 48l0-48-48 0zm-96 80a64 64 0 1 0 -128 0 64 64 0 1 0 128 0z"/>
                                    </svg>
                                </div>
                                <!-- End Icon -->

                                <div class="mt-3">
                                    <h3 class="text-sm sm:text-lg font-semibold text-gray-800 dark:text-neutral-200">
                                    {{ row.title }}
                                    </h3>
                                </div>
                            </div>
                            <ul class="max-w-md space-y-1 text-gray-500 list-inside dark:text-gray-400">
                                <template v-for="(item, co) in row.items">
                                    <li class="hover:underline md:me-6">
                                        <Link :href="item.url" class="flex text-gray-800 underline decoration-gray-800 hover:opacity-80 focus:outline-hidden focus:opacity-80 dark:text-white dark:decoration-white" :target="item.target">
                                            <svg class="w-3.5 h-3.5 me-2 mt-1 text-green-500 dark:text-green-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                                <path fill="currentColor" d="M224 96a160 160 0 1 0 0 320 160 160 0 1 0 0-320zM448 256A224 224 0 1 1 0 256a224 224 0 1 1 448 0z"/>
                                            </svg>
                                            {{ item.label }}
                                        </Link>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </div>

    </AppLayout>
</template>
