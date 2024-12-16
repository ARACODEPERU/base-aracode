<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { 
    faBars, 
 } from "@fortawesome/free-solid-svg-icons";

const user = usePage().props.auth.user;
const company = usePage().props.company;
const xassetUrl = assetUrl;



</script>

<template>

    <header class="bg-white w-full py-0 my-0 dark:bg-gray-800">
        <div class="container">
            <nav class=" flex justify-between lg:justify-start items-center">
                <div class="logo p-0 w-1/6">
                    <img src="img/isotipo.png" width="80" alt="">
                </div>
                <div class="links sm:block hidden w-1/6 md:w-4/6">
                    <ul class="menu flex items-center justify-center gap-5">
                        <li>
                            <a href="" class="text-gray-800 dark:text-white block p-5 font-bold hover:text-blue-500">Inicio</a>
                        </li>
                        <li>
                            <a href="#about" class="text-gray-800 dark:text-white block p-5 font-bold hover:text-blue-500">Nosotros</a>
                        </li>
                        <li>
                            <a href="" class="text-gray-800 dark:text-white block p-5 font-bold hover:text-blue-500">Blog</a>
                        </li>
                        <li>
                            <a href="#contact" class="text-gray-800 dark:text-white border-2 border-blue-500 font-bold py-2 px-4 rounded-full hover:bg-white hover:text-black transition duration-500">Contactanos</a>
                        </li>
                        <li>
                            <a href="" class="text-white rounded-full bg-red-500 font-bold px-4 py-2 hover:text-black hover:bg-white transition duration-500">Campus Online</a>
                        </li>
                    </ul>
                </div>
                <div class="block sm:hidden w-1/6 sm:w-4/6">
                    <div class="dropdown">
                        <Popper :placement="'bottom-end'" offsetDistance="0" class="align-middle">
         
                            <a href="#" class="text-gray-800 dark:text-white p-5 font-bold hover:text-blue-500">
                                <font-awesome-icon :icon="faBars" class="fill-current" />
                            </a>
                            <template #content="{ close }">
                                <ul @click="close()" class="menu flex items-center justify-center gap-5">
                                    <li>
                                        <Link href="#"  class="dark:text-white block p-5 font-bold hover:text-blue-500">
                                            Bog
                                        </Link>
                                    </li>
                                </ul>
                            </template>
                        </Popper>
                    </div>
                </div>
                <!-- <button class="rounded-full bg-blue-500 font-bold px-4 py-2 text-white hover:text-black hover:bg-white transition duration-500">
                    <i class="fas fa-shopping-cart mr-2"></i>Cart
                </button> -->
            </nav>
        </div>
    </header>

    <!-- header -->
    <!-- <header class="bg-[#f6f8ff] w-full bx-static">
        <nav class="border-gray-200 py-2">
            <div
                class="flex flex-wrap justify-between items-center px-6 mx-auto 2xl:max-w-[1320px] xl:max-w-[1140px] lg:max-w-[960px] md:max-w-[720px] sm:max-w-[540px] max-[320px]:px-[12px]">
                <Link :href="route('index_main')" class="flex items-center">
                    <img v-if="company.isotipo == '/img/isotipo.png'" :src="company.isotipo" class="w-[90px]" alt="Logo">
                    <img v-else :src="`${xassetUrl}storage/${company.isotipo}` " class="w-[90px]" alt="Logo2">
                </Link>
                <div class="flex items-center lg:order-2">
                    <a href="#contact" type="button"
                        class="text-white bg-[#7963e0] hover:bg-opacity-80 no-underline font-medium rounded-full text-sm px-8 py-2.5 mr-2 hidden 2xl:block xl:block lg:block">
                        Obtenga una cotización
                    </a>
                    <button data-collapse-toggle="mobile-menu" type="button" id="dropdown-toggle"
                        class="inline-flex items-center border p-2 text-lg text-gray-500 rounded-lg lg:hidden"
                        aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Abrir el menú principal</span>
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>
                <div class="justify-between items-center w-full lg:flex lg:w-auto lg:order-1" id="mobile-menu">
                    <ul class="flex flex-col font-medium lg:flex-row lg:space-x-8 2xl:border-0 lg:border-0 border lg:mt-0 lg-mb-4 lg:p-[0] lg:border-none lg:rounded-[0] lg:text-[15px] mt-4 p-[15px] 2xl:mb-0 xl:mb-0 lg:mb-0 mb-2 border-[#eee] rounded-[30px] text-[13px]"
                        id="top-menu">
                        <li class="nav-item active">
                            <a href="#home" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0">Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a href="#about" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0">Acerca de Nosotros</a>
                        </li>
                        <!-- <li class="nav-item">
                            <a href="#experience" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0">Experiencia</a>
                        </li>
                        <li class="nav-item">
                            <a href="#portfolio" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0">Cartera</a>
                        </li>
                        <li class="nav-item">
                            <Link :href="route('blog_main')" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0">Blog</Link>
                        </li>
                        <li class="nav-item">
                            <a href="#contact" class="block py-2 pr-4 pl-3 text-[#000] lg:p-0">Contacta con nosotros</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header> -->
</template>
    
