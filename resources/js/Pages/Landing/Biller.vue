<script setup>
import '@Public/themes/personalLanding/assets/css/style.css';
import Header from './Components/Header.vue';
import Footer from './Components/Footer.vue';
import { Head, Link } from '@inertiajs/vue3';
const xassetUrl = assetUrl;
import { ref, onMounted, onUnmounted } from 'vue';
import iconArrowLeft from '@/Components/vristo/icon/icon-arrow-left.vue';

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
                Simplifica tu Negocio con Facturación Electrónica Segura y Eficiente
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


    <section id="contact" class="section-contact 2xl:py-[80px] py-[70px] dark:bg-gray-800">
        <div class="banner text-center mb-[30px]" data-aos="fade-up" data-aos-duration="2000" data-aos-delay="300">
            <span class="text-[14px] text-[#777] dark:text-[#fff]">FORMULARIO</span>
            <h2
                class="text-center mt-[5px] 2xl:text-[35px] xl:text-[33px] lg:text-[30px] md:text-[26px] sm:text-[24px] text-[22px] font-bold dark:text-[#fff]">
                Ponte en contacto con 
                <span class="text-[#0188ee]">NOSOTROS</span>
            </h2>
        </div>
        <form>
            <div
                class="flex flex-wrap justify-between items-center mx-auto 2xl:max-w-[1320px] xl:max-w-[1140px] lg:max-w-[960px] md:max-w-[720px] sm:max-w-[540px] max-[320px]:px-[12px] px-6">
                <div class="m-b-minus-24px w-full">
                    <div class="grid lg:grid-cols-2 grid-cols-1 gap-[30px]" data-aos="fade-up" data-aos-duration="2000" data-aos-delay="300">
                        <div class="border rounded-lg dark:border-[#27213b]">
                            <div class="border-b dark:border-[#27213b]">
                                <input type="text"
                                    class="appearance-none w-full py-6 px-3 text-gray-700 leading-tight focus:outline-none rounded-t-lg dark:bg-[#161321]"
                                    id="fname" placeholder="Nombre completo">
                            </div>
                            <div class="border-b dark:border-[#27213b]">
                                <input type="email"
                                    class="appearance-none w-full py-6 px-3 text-gray-700 leading-tight focus:outline-none dark:bg-[#161321]"
                                    id="umail" placeholder="Correo electrónico">
                            </div>
                            <div class="border-b dark:border-[#27213b]">
                                <input type="text"
                                    class="appearance-none w-full py-6 px-3 text-gray-700 leading-tight focus:outline-none dark:bg-[#161321]"
                                    id="phone" placeholder="Teléfono">
                            </div>
                            <div class="dark:border-[#27213b]">
                                <input type="text"
                                    class="appearance-none w-full py-6 px-3 text-gray-700 leading-tight focus:outline-none rounded-b-lg dark:bg-[#161321]"
                                    id="subject" placeholder="Asunto">
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="mb-4">
                                <textarea class="w-full border dark:border-[#27213b] rounded-xl pl-4 pt-2 focus:outline-none dark:bg-[#161321]"
                                    id="exampleFormControlTextarea1" rows="8" placeholder="Mensaje"></textarea>
                            </div>
                            <button type="button"
                                class="text-white bg-[#0188ee] hover:bg-[#f11600] no-underline font-medium rounded-full text-sm px-8 py-2.5 mr-2">
                                Enviar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
    
    <Footer />
    <!-- scroll Top -->
    <div id="scrollup"
        @click="scrollToTop" 
        :class="{ show: isVisible }"
        class="fixed bg-[#0188ee] text-white rounded-full flex justify-center 
        text-center items-center p-2 right-6 cursor-pointer bottom-6 h-10 w-10 z-20
        font-bold">
        <icon-arrow-left class="-rotate-90 font-bold" />
    </div>
</template>