<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { Link, useForm } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import iconSearch from '@/Components/vristo/icon/icon-search.vue';
    import { Empty } from 'ant-design-vue';
    import { ref } from 'vue';
    import DropdownExports from '@/Components/DropdownExports.vue';
    import flatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import { useAppStore } from '@/stores/index';
    const store = useAppStore();


</script>
<template>
    <!-- <AppLayout title="Reportes">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Academic'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <Link :href="route('aca_reports_dashboard')" class="text-primary hover:underline">Reportes</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Reporte Estado de Suscripciones Académicas</span>
            </li>
        </Navigation>
        <div class="mt-5"> -->
            <div class="relative flex min-h-screen items-center justify-center overflow-hidden">
                <div
                    class="px-6 py-16 text-center font-semibold before:container before:absolute before:left-1/2 before:-translate-x-1/2 before:rounded-full before:bg-[linear-gradient(180deg,#4361EE_0%,rgba(67,97,238,0)_50.73%)] before:aspect-square before:opacity-10 md:py-20"
                >
                    <img
                        :src="store.theme === 'dark' || store.isDarkMode ? '/themes/vristo/images/error/maintenence-dark.svg' : '/themes/vristo/images/error/maintenence-light.svg'"
                        alt="maintenence"
                        class="mx-auto -mt-10 w-full max-w-xs object-cover md:-mt-20 md:max-w-lg"
                    />
                    <div class="-mt-8 font-semibold dark:text-white">
                        <h2 class="mb-5 text-3xl font-bold text-primary md:text-5xl">En mantenimiento</h2>
                        <h4 class="mb-7 text-xl sm:text-2xl">Gracias por visitarnos.</h4>
                        <p class="text-base">
                            Actualmente estamos trabajando en realizar algunas mejoras.
                            <br class="hidden sm:block" />Para brindarle una mejor experiencia de usuario. <br />
                            <br />Por favor, vuelva a visitarnos en breve.
                        </p>
                    </div>
                    <Link :href="route('dashboard')" class="btn btn-gradient mx-auto !mt-7 w-max border-0 uppercase shadow-none">ir al inicio</Link>
                </div>
            </div>
        <!-- </div>
    </AppLayout> -->
</template>
