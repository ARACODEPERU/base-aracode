<script setup>
import '@Public/themes/personalLanding/assets/css/style.css';
import Header from './Components/Header.vue';
import Footer from './Components/Footer.vue';
import FacturacionPrices from './Components/FacturacionPrices.vue';
import ContactForm from './Components/ContactForm.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import iconArrowLeft from '@/Components/vristo/icon/icon-arrow-left.vue';
import WhatsappBiller from './Components/WhatsappBiller.vue';
import { initFlowbite } from 'flowbite'; // Importar desde Flowbite, previa instalación


// Inicializando Flowbite
onMounted(() => {
    initFlowbite();
}) 

const xassetUrl = assetUrl;
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
                <a href="#contact" class="inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center
                 text-white rounded-lg bg-[#0188ee] hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-900
                 shadow-lg transition duration-500 ease-in-out hover:-translate-y-1 hover:scale-110">
                    Ver Demo
                    <svg class="w-3.5 h-3.5 ms-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                    </svg>
                </a>
                <a href="#" class="inline-flex justify-center hover:text-gray-900 items-center py-3 px-5 sm:ms-4 text-base font-medium 
                text-center text-white rounded-lg border border-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-400
                shadow-lg transition duration-500 ease-in-out hover:-translate-y-1 hover:scale-110">
                    Contactanos
                </a>  
            </div>
        </div>
    </section>

    
    <section class="mx-auto dark:bg-[#161321] pt-[80px] pb-[80px]">
        <!-- Title -->
        <div class="max-w-2xl mx-auto text-center mb-10 lg:mb-14">
            <h2 class="text-2xl font-bold md:text-4xl md:leading-tight dark:text-white">
                PREGUNTAS <span class="text-[#0188ee]">FRECUENTES</span>
            </h2>
            <!-- <p class="mt-1 text-gray-600 dark:text-neutral-400">
                planes por mes o al año, envía tus facturas y boletas electrónicas a SUNAT fácilmente
            </p> -->
        </div>
        <div class="max-w-2xl mx-auto text-center mb-10 lg:mb-14">
            <div id="accordion-collapse" data-accordion="collapse">
            <h2 id="accordion-collapse-heading-1">
                <button type="button" class="flex items-center justify-between w-full p-5 font-medium rtl:text-right text-gray-500 border 
                border-b-0 border-gray-200 rounded-t-xl focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-800 dark:border-gray-700
                 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 gap-3" 
                 data-accordion-target="#accordion-collapse-body-1" aria-expanded="true" 
                 aria-controls="accordion-collapse-body-1">
                    <span>¿Qué es una factura electrónica?</span>
                    <svg data-accordion-icon class="w-3 h-3 rotate-180 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                    </svg>
                </button>
            </h2>
            <div id="accordion-collapse-body-1" class="hidden" aria-labelledby="accordion-collapse-heading-1">
                <div class="p-5 border border-b-0 border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                    <p class="mb-2 text-gray-500 dark:text-gray-400">
                        Una factura electrónica es un comprobante de pago en formato digital que sirve para sustentar la compraventa de bienes 
                        o servicios. Si bien este tipo facturación tiene la misma validez legal que la facturación tradicional (en papel), 
                        termina siendo más atractiva para pequeñas, medianas y grandes empresas debido a los múltiples beneficios que ofrece.
                    </p>
                </div>
            </div>
            <h2 id="accordion-collapse-heading-2">
                <button type="button" class="flex items-center justify-between w-full p-5 font-medium rtl:text-right text-gray-500 border 
                border-b-0 border-gray-200 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-800 dark:border-gray-700
                 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 gap-3" 
                 data-accordion-target="#accordion-collapse-body-2" aria-expanded="false" 
                 aria-controls="accordion-collapse-body-2">
                <span>¿Para qué sirve la facturación electrónica?</span>
                <svg data-accordion-icon class="w-3 h-3 rotate-180 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                </svg>
                </button>
            </h2>
            <div id="accordion-collapse-body-2" class="hidden" aria-labelledby="accordion-collapse-heading-2">
                <div class="p-5 border border-b-0 border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                    <p class="mb-2 text-gray-500 dark:text-gray-400">
                        La facturación electrónica sirve para sustentar y validar las transacciones comerciales que se generan entre 
                        empresas y clientes. Además, gracias a la digitalización de comprobantes, con este tipo de facturación puedes 
                        simplificar procesos y reducir costos asociados a la emisión y almacenamiento de facturas en papel.
                    </p>
                </div>
            </div>
            <h2 id="accordion-collapse-heading-3">
                <button type="button" class="flex items-center justify-between w-full p-5 font-medium rtl:text-right text-gray-500 border
                 border-gray-200 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-800 dark:border-gray-700 dark:text-gray-400
                  hover:bg-gray-100 dark:hover:bg-gray-800 gap-3" 
                  data-accordion-target="#accordion-collapse-body-3" aria-expanded="false" 
                  aria-controls="accordion-collapse-body-3">
                <span>¿Qué beneficios tengo al emitir facturas electrónicas con Alegra?</span>
                <svg data-accordion-icon class="w-3 h-3 rotate-180 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                </svg>
                </button>
            </h2>
            <div id="accordion-collapse-body-3" class="hidden" aria-labelledby="accordion-collapse-heading-3">
                <div class="p-5 border border-t-0 border-gray-200 dark:border-gray-700">
                    <p class="mb-2 text-gray-500 dark:text-gray-400">
                        Entre los beneficios de emitir facturas electrónicas con Alegra tenemos:
                    </p>
                    <ul style="list-style:none;margin-top:10px;margin-bottom:10px">
                        <li style="display:flex;gap:5px;align-items:center">
                        <img src="https://cdn1.alegra.com/websites/check-icon-low-check.webp" loading="lazy" style="width:15px;margin-right:10px"> 
                        Ahorro tiempo tus facturas de forma automática
                        </li>
                        <li style="display:flex;gap:5px;align-items:center">
                            <img src="https://cdn1.alegra.com/websites/check-icon-low-check.webp" loading="lazy" style="width:15px;margin-right:10px"> 
                            Mayor tranquilidad al cumplir fácil con la SUNAT
                        </li>
                        <li style="display:flex;gap:5px;align-items:center">
                            <img src="https://cdn1.alegra.com/websites/check-icon-low-check.webp" loading="lazy" style="width:15px;margin-right:10px"> 
                            Eficiencia en procesos al reducir errores humanos
                        </li>
                        <li style="display:flex;gap:5px;align-items:center">
                            <img src="https://cdn1.alegra.com/websites/check-icon-low-check.webp" loading="lazy" style="width:15px;margin-right:10px"> 
                            Ahorro de dinero al reducir costos de impresión y almacenamiento
                        </li>
                    </ul>
                    <p class="mb-2 text-gray-500 dark:text-gray-400">
                        Y lo mejor es que también puedes emitir boletas y guías de remisión electrónicas de forma ilimitada y desde cualquier lugar. 
                        Conoce más en nuestra 
                        <a href="https://ayuda.alegra.com/es/facturaci%C3%B3n-electr%C3%B3nica-en-alegra-per%C3%BA" class="font-weight-500" target="_blank" rel="noreferrer">
                            guía sobre cómo llevar tu Facturación Electrónica en Alegra. [Paso a paso]
                        </a>
                    </p>
                </div>
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
