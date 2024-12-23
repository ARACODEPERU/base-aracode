<script setup>
import '@Public/themes/personalLanding/assets/css/style.css';
import Header from './Components/Header.vue';
import Footer from './Components/Footer.vue';
import FacturacionPrices from './Components/FacturacionPrices.vue';
import ContactForm from './Components/ContactForm.vue';
import { Head, Link } from '@inertiajs/vue3';
const xassetUrl = assetUrl;
import { ref, onMounted, onUnmounted } from 'vue';
import iconArrowLeft from '@/Components/vristo/icon/icon-arrow-left.vue';
import WhatsappBiller from './Components/WhatsappBiller.vue';

defineProps({
    dataHome: {
        type: Object,
        default: () => ({})
    }
});

    // Estado para controlar la visibilidad del botón
    const isVisible = ref(false);

    // Función para controlar el scroll
    const handleScroll = () => {
        isVisible.value = window.scrollY > 300;
    };

    // Función para desplazarse al inicio de la página
    const scrollToTop = () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // Configurar el evento de scroll al montar el componente
    onMounted(() => {
        window.addEventListener('scroll', handleScroll);
    });

    // Limpiar el evento al desmontar el componente
    onUnmounted(() => {
        window.removeEventListener('scroll', handleScroll);
    });



</script>


<template>
    <Head>
        <title>Facturador</title>
        <!-- Meta para Facturación Electrónica -->
        <meta name="description" content="Simplifica tu facturación electrónica con nuestra solución. Envía facturas y boletas electrónicas a SUNAT de forma rápida, segura y eficiente.">
        <meta name="keywords" content="facturación electrónica, enviar facturas SUNAT, boletas electrónicas, software SUNAT, sistema de facturación Perú, boletas Perú, SUNAT online">

        <!-- Meta para Desarrollo Web a Medida -->
        <meta name="description" content="Desarrollamos sitios web a medida y aplicaciones personalizadas según las necesidades de tu negocio. Soluciones modernas y adaptadas a tus objetivos.">
        <meta name="keywords" content="desarrollo web a medida, diseño web profesional, creación de sitios web, aplicaciones personalizadas, desarrollo de software, soluciones digitales, páginas web empresariales, desarrollo web Perú">

        <meta name="author" content="Aracode">
        <meta name="robots" content="biller, follow">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </Head>
    <Header :dataHeader="dataHome.header" />
    
    <section style="background-image: url('./img/Landing/bg/tech-bg.png');" class="bg-center bg-no-repeat bg-gray-700 bg-blend-multiply">
        <div class="px-4 mx-auto max-w-screen-xl text-center py-24 lg:py-56">
            <h1 class="mb-4 text-4xl font-extrabold tracking-tight leading-none text-white md:text-5xl lg:text-6xl">
                Simplifica tu Negocio con <span class="text-[#0188ee]">Facturación Electrónica </span>Segura y Eficiente
            </h1>
            <p class="mb-8 text-lg font-normal text-gray-300 lg:text-xl sm:px-16 lg:px-48">
                Implementa nuestra solución de facturación electrónica y lleva el control de tus ventas de forma rápida, 
                segura y cumpliendo con la normativa vigente. Optimiza tu gestión y ahorra tiempo en cada transacción.
            </p>
            <div class="flex flex-col space-y-4 sm:flex-row sm:justify-center sm:space-y-0">
                <a href="#contact" class="inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-[#0188ee] hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-900">
                    Ver Demo
                    <svg class="w-3.5 h-3.5 ms-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                    </svg>
                </a>
                <a href="#" class="inline-flex justify-center hover:text-gray-900 items-center py-3 px-5 sm:ms-4 text-base font-medium text-center text-white rounded-lg border border-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-400">
                    Contactanos
                </a>  
            </div>
        </div>
    </section>

    
    <section class="mx-auto bg-[#f6f8ff] dark:bg-[#161321] pt-[80px] pb-[80px]">
        <div class="max-w-md mx-auto mt-8">
            <div class="bg-white shadow-md rounded-lg">
                <button class="flex items-center justify-between w-full text-left py-2 px-4 font-bold bg-gray-200 hover:bg-gray-300">
                    Pregunta 1
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="answer max-h-0 overflow-hidden">
                    <p class="p-4">Respuesta a la pregunta 1.</p>
                </div>

                <button class="flex items-center justify-between w-full text-left py-2 px-4 font-bold bg-gray-200 hover:bg-gray-300 mt-2">
                    Pregunta 2
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="answer max-h-0 overflow-hidden">
                    <p class="p-4">Respuesta a la pregunta 2.</p>
                </div>

                <button class="flex items-center justify-between w-full text-left py-2 px-4 font-bold bg-gray-200 hover:bg-gray-300 mt-2">
                    Pregunta 3
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="answer max-h-0 overflow-hidden">
                    <p class="p-4">Respuesta a la pregunta 3.</p>
                </div>
            </div>
        </div>
    </section>

    <FacturacionPrices />

    <!-- contact -->
    <ContactForm />

    <Footer />

    <WhatsappBiller />

    <!-- scroll Top -->
    <div id="scrollup"
        @click="scrollToTop" 
        :class="{ show: isVisible }"
        class="fixed bg-[#0188ee] text-white rounded-full flex justify-center 
        text-center items-center p-2 right-6 cursor-pointer bottom-6 h-10 w-10 z-20
        font-bold">
        <iconArrowLeft class="-rotate-90 font-bold" />
    </div>
</template>
