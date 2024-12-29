<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { useAppStore } from '@/stores/index';
import iconSun from '@/Components/vristo/icon/icon-sun.vue';
import iconMoon from '@/Components/vristo/icon/icon-moon.vue';

const user = usePage().props.auth.user;
const company = usePage().props.company;
const xassetUrl = assetUrl;

const props = defineProps({
    dataHeader: {
        type: Object,
        default: () => ({})
    }
});

const store = useAppStore();

</script>
<template>
    <!-- header -->
    <header class="bg-[#f6f8ff] w-full bx-fixed dark:bg-[#1d1a29]">
        <nav class="border-gray-200 py-2">
            <div
                class="flex flex-wrap justify-between items-center px-6 mx-auto 2xl:max-w-[1320px] xl:max-w-[1140px] lg:max-w-[960px] md:max-w-[720px] sm:max-w-[540px] max-[320px]:px-[12px]">
                <Link :href="route('index_main')" class="flex items-center">
                    <img v-if="company.isotipo == '/img/isotipo.png'" :src="company.isotipo" class="w-auto h-[50px]" alt="Logo">
                    <img v-else :src="`${xassetUrl}storage/${dataHeader.items[0].item.content}`" class="w-auto h-[50px]" alt="Logo2">
                </Link>
                <div class="flex items-center lg:order-2">
                    <a href="#contact" type="button"
                        class="text-white bg-[#0188ee] hover:bg-opacity-80 no-underline font-medium rounded-full text-sm px-8 py-2.5 mr-2 hidden 2xl:block xl:block lg:block
                        shadow-lg transition duration-500 ease-in-out hover:-translate-y-1 hover:scale-100">
                        Obtenga una cotización
                    </a>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="btn rounded-full py-2 px-2.5 hover:bg-[#F11600] 
                            shadow-lg transition duration-500 ease-in-out hover:-translate-y-1 hover:scale-100"
                            :class="[store.theme === 'light' ? 'bg-[#0188ee]' : 'btn-outline-[#0188ee]']"
                            @click="store.toggleTheme('light')"
                            >
                            <iconSun class="w-4 h-4 text-white dark:text-[#0188ee] " />
                        </button>
                        <button
                            type="button"
                            class="btn rounded-full py-2 px-2.5 hover:bg-[#F11600] hover:text-white
                            shadow-lg transition duration-500 ease-in-out hover:-translate-y-1 hover:scale-100"
                            :class="[store.theme === 'dark' ? 'bg-[#0188ee]' : 'btn-outline-[#0188ee]']"
                            @click="store.toggleTheme('dark')"
                        >
                            <iconMoon class="w-5 h-5 text-[#0188ee] dark:text-white hover:text-white" />
                        </button>
                    </div>
                    <button type="button" id="dropdown-toggle"
                        class="block ml-2 sm:hidden inline-flex items-center border p-2 text-lg text-gray-500 rounded-lg lg:hidden"
                        aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Abrir el menú principal</span>
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                            <path fill="currentColor" d="M0 96C0 78.3 14.3 64 32 64l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 128C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32L32 448c-17.7 0-32-14.3-32-32s14.3-32 32-32l384 0c17.7 0 32 14.3 32 32z"/>
                        </svg>
                    </button>
                </div>
                <div class="justify-between items-center w-full lg:flex lg:w-auto lg:order-1" id="mobile-menu">
                    <ul class="flex flex-col font-medium lg:flex-row lg:space-x-8 2xl:border-0 lg:border-0 border lg:mt-0 lg-mb-4 lg:p-[0] lg:border-none lg:rounded-[0] lg:text-[15px] mt-4 p-[15px] 2xl:mb-0 xl:mb-0 lg:mb-0 mb-2 border-[#eee] rounded-[30px] text-[13px]"
                        id="top-menu">
                        <li class="nav-item active">
                            <a href="/" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0 dark:text-[#000]">Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a href="#about" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0 dark:text-[#000]">Nosotros</a>
                        </li>
                        <li class="nav-item">
                            <a href="/facturador" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0 dark:text-[#000]">Facturador</a>
                        </li>
                        <!-- <li class="nav-item">
                            <a href="#experience" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0">Experiencia</a>
                        </li>
                        <li class="nav-item">
                            <a href="#portfolio" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0">Cartera</a>
                        </li>
                        <li class="nav-item">
                            <a href="#news" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0">Blog</a>
                        </li> -->
                        <li class="nav-item">
                            <a href="#contact" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0 dark:text-[#000]">Contactanos</a>
                        </li>
                        
                    </ul>
                </div>
            </div>
        </nav>
    </header>
</template>