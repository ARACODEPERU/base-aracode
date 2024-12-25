<script setup>
import '@Public/themes/personalLanding/assets/css/style.css';
import Header from './Components/Header.vue';
import Jumbotron from './Components/Jumbotron.vue';
import ServicesTI from './Components/ServicesTI.vue';
import About from './Components/About.vue';
import Customers from './Components/Customers.vue';
import Footer from './Components/Footer.vue';
import { Head, Link } from '@inertiajs/vue3';
import Teams from './Components/Teams.vue';
const xassetUrl = assetUrl;
import { ref, onMounted, onUnmounted } from 'vue';
import iconArrowLeft from '@/Components/vristo/icon/icon-arrow-left.vue';
import WhatsappHome from './Components/WhatsappHome.vue';
import ContactForm from './Components/ContactForm.vue';
import ContinuousArrow from './Components/ContinuousArrow.vue';

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
        <title>Home</title>
        <!-- Meta para Facturación Electrónica -->
        <meta name="description" content="Simplifica tu facturación electrónica con nuestra solución. Envía facturas y boletas electrónicas a SUNAT de forma rápida, segura y eficiente.">
        <meta name="keywords" content="facturación electrónica, enviar facturas SUNAT, boletas electrónicas, software SUNAT, sistema de facturación Perú, boletas Perú, SUNAT online">

        <!-- Meta para Desarrollo Web a Medida -->
        <meta name="description" content="Desarrollamos sitios web a medida y aplicaciones personalizadas según las necesidades de tu negocio. Soluciones modernas y adaptadas a tus objetivos.">
        <meta name="keywords" content="desarrollo web a medida, diseño web profesional, creación de sitios web, aplicaciones personalizadas, desarrollo de software, soluciones digitales, páginas web empresariales, desarrollo web Perú">

        <meta name="author" content="Aracode">
        <meta name="robots" content="index, follow">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </Head>
    <Header :dataHeader="dataHome.header" />
    

    <section style="background-image: url('./img/Landing/bg/portadaWeb.jpg');" 
            class="bg-center bg-no-repeat bg-gray-700 bg-blend-multiply block">
        <div class="px-4 mx-auto max-w-screen-xl text-center py-24 lg:py-56">
            <h1 class="mb-4 text-4xl font-extrabold tracking-tight leading-none text-white md:text-5xl lg:text-6xl">
                Haz Crecer Tu Negocio con <span class="text-[#0188ee]">Sistemas Web</span> que Generan Resultados
            </h1>
            <p class="mb-8 text-lg font-normal text-gray-300 lg:text-xl sm:px-16 lg:px-48">
                Diseñamos sistemas web personalizados que optimizan tus procesos, mejoran la experiencia del cliente y 
                generan resultados para tu negocio.
            </p>
            <div class="flex flex-col space-y-4 sm:flex-row sm:justify-center sm:space-y-0">
                <a href="#contact" class="inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white 
                    rounded-lg bg-[#0188ee] hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-900
                    shadow-lg transition duration-500 ease-in-out hover:-translate-y-1 hover:scale-100">
                    Ver Demos
                    <svg class="w-3.5 h-3.5 ms-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                    </svg>
                </a>
                <a href="#contact" class="inline-flex justify-center hover:text-gray-900 items-center py-3 px-5 sm:ms-4 text-base font-medium 
                    text-center text-white rounded-lg border border-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-400
                    shadow-lg transition duration-500 ease-in-out hover:-translate-y-1 hover:scale-100">
                    Contactanos
                </a>  
            </div>
        </div>
    </section>

    <ContinuousArrow />


    <!-- hero section -->
    <Jumbotron :dataWelcome="dataHome.welcome" />


    <!-- service -->
    
    <ServicesTI />
    <!-- about -->
    
    <About />
    <!-- Experience and Education -->
    <!-- <section id="experience" class="section-experience bg-[#f6f8ff] relative">
        <div class="relative pt-[60px]">
            <img src="assets/img/shape/bg-shape.png" alt="bg-shape"
                class="absolute top-0 left-0 right-0 w-full bg-center bg-cover">
        </div>
        <div class="2xl:pb-[80px] pb-[70px] 2xl:pt-[80px] md:pt-[70px] pt-[20px]">
            <div class="banner text-center mb-[30px]" data-aos="fade-up" data-aos-duration="2000" data-aos-delay="300">
                <span class="text-[14px] text-[#777]">QUALIFICATION</span>
                <h2
                    class="text-center mt-[5px] 2xl:text-[35px] xl:text-[33px] lg:text-[30px] md:text-[26px] sm:text-[24px] text-[22px] font-bold">
                    My <span class="text-[#7963e0]"> ACHIEVEMENTS</span></h2>
            </div>
            <div
                class="flex flex-wrap justify-between items-center mx-auto mx-auto 2xl:max-w-[1320px] xl:max-w-[1140px] lg:max-w-[960px] md:max-w-[720px] sm:max-w-[540px] max-[320px]:px-[12px] px-6 relative">
                <img src="assets/img/shape/shape-8.png" alt="shape-8" class="absolute w-12 -top-10 right-10">
                <div class="absolute -top-10 left-10 h-12 w-12 bg-[#f1c7a1] rounded-full"></div>
                <div class="grid lg:grid-cols-2 grid-cols-1 gap-[30px]">
                    <div class="transition-all justify-start items-start">
                        <h2 class="text-[20px] font-bold leading-[24px]">Education</h2>
                        <div class="border-l-2 border-gray-300 pl-6">
                            <div class="p-[30px] bg-white rounded-3xl mt-8 relative" data-aos="fade-up"
                                data-aos-duration="2000" data-aos-delay="400">
                                <div class="absolute top-0 bottom-0 -left-6 w-4">
                                    <span
                                        class="w-4 h-4 border-2 border-[#7963e0] rounded-full block bg-[#f6f8ff] absolute top-28 -left-2.5"></span>
                                    <span
                                        class="w-5 border border-[#7963e0] block bg-[#f6f8ff] absolute top-28 my-1.5 left-1.5"></span>
                                </div>
                                <span class="text-[#777] text-[12px] font-medium">June 15, 2013 - 2016</span>
                                <h4 class="text-[16px] leading-[22px] font-semibold mt-[15px] mb-[6px] text-[#7963e0]">
                                    Master in Computer Engineering <span class="ml-[15px] text-[#999] text-[14px]">-
                                        First
                                        Class</span></h4>
                                <p class="text-[13px] text-[#777] mb-0 leading-[28px]">Lorem, ipsum dolor sit amet
                                    consectetur adipisicing elit. Mollitia
                                    sequi doloremque ullam placeat quos provident ex fuga. Ratione ab explicabo
                                    doloribus error odio veritatis tenetur.</p>
                            </div>
                            <div class="p-[30px] bg-white rounded-3xl mt-8 relative" data-aos="fade-up"
                                data-aos-duration="2000" data-aos-delay="600">
                                <div class="absolute top-0 bottom-0 -left-6 w-4">
                                    <span
                                        class="w-4 h-4 border-2 border-[#7963e0] rounded-full block bg-[#f6f8ff] absolute top-28 -left-2.5"></span>
                                    <span
                                        class="w-5 border border-[#7963e0] block bg-[#f6f8ff] absolute top-28 my-1.5 left-1.5"></span>
                                </div>
                                <span class="text-[#777] text-[12px] font-medium">June 12, 2010 - 2013</span>
                                <h4 class="text-[16px] leading-[22px] font-semibold mt-[15px] mb-[6px] text-[#7963e0]">
                                    Bachelor
                                    in Computer Engineering <span class="ml-[15px] text-[#999] text-[14px]">- First
                                        Class</span></h4>
                                <p class="text-[13px] text-[#777] mb-0 leading-[28px]">Lorem, ipsum dolor sit amet
                                    consectetur adipisicing elit.
                                    Mollitia sequi doloremque ullam placeat quos provident ex fuga. Ratione ab explicabo
                                    doloribus error odio veritatis tenetur.</p>
                            </div>
                            <div class="p-[30px] bg-white rounded-3xl mt-8 relative" data-aos="fade-up"
                                data-aos-duration="2000" data-aos-delay="800">
                                <div class="absolute top-0 bottom-0 -left-6 w-4">
                                    <span
                                        class="w-4 h-4 border-2 border-[#7963e0] rounded-full block bg-[#f6f8ff] absolute top-28 -left-2.5"></span>
                                    <span
                                        class="w-5 border border-[#7963e0] block bg-[#f6f8ff] absolute top-28 my-1.5 left-1.5"></span>
                                </div>
                                <span class="text-[#777] text-[12px] font-medium">June 1, 2009 - 2010</span>
                                <h4 class="text-[16px] leading-[22px] font-semibold mt-[15px] mb-[6px] text-[#7963e0]">
                                    Higher Secondary <span class="ml-[15px] text-[#999] text-[14px]">- (A+)</span></h4>
                                <p class="text-[13px] text-[#777] mb-0 leading-[28px]">Lorem, ipsum dolor sit amet
                                    consectetur adipisicing elit.
                                    Mollitia sequi doloremque ullam placeat quos provident ex fuga. Ratione ab explicabo
                                    doloribus error odio veritatis tenetur.</p>
                            </div>
                        </div>
                    </div>
                    <div class="transition-all 2xl:mt-0 lg:mt-0 xl:mt-0 mt-[15px] justify-start items-start" data-aos="fade-up" data-aos-duration="2000"
                        data-aos-delay="300">
                        <h2 class="text-[20px] font-bold leading-[24px]">Experience</h2>
                        <div class="border-l-2 border-gray-300 pl-6">
                            <div class="p-[30px] bg-white rounded-3xl mt-8 relative" data-aos="fade-up"
                                data-aos-duration="2000" data-aos-delay="400">
                                <div class="absolute top-0 bottom-0 -left-6 w-4">
                                    <span
                                        class="w-4 h-4 border-2 border-[#7963e0] rounded-full block bg-[#f6f8ff] absolute top-28 -left-2.5"></span>
                                    <span
                                        class="w-5 border border-[#7963e0] block bg-[#f6f8ff] absolute top-28 my-1.5 left-1.5"></span>
                                </div>
                                <span class="text-[#777] text-[12px] font-medium">March 12, 2020</span>
                                <h4 class="text-[16px] leading-[22px] font-semibold mt-[15px] mb-[6px] text-[#7963e0]">
                                    Envato
                                    <span class="ml-[15px] text-[#999] text-[14px]">- Team Leader</span>
                                </h4>
                                <p class="text-[13px] text-[#777] mb-0 leading-[28px]">Lorem, ipsum dolor sit amet
                                    consectetur adipisicing elit.
                                    Mollitia
                                    sequi doloremque ullam placeat quos provident ex fuga. Ratione ab explicabo
                                    doloribus
                                    error
                                    odio veritatis tenetur.</p>
                            </div>
                            <div class="p-[30px] bg-white rounded-3xl mt-8 relative" data-aos="fade-up"
                                data-aos-duration="2000" data-aos-delay="600">
                                <div class="absolute top-0 bottom-0 -left-6 w-4">
                                    <span
                                        class="w-4 h-4 border-2 border-[#7963e0] rounded-full block bg-[#f6f8ff] absolute top-28 -left-2.5"></span>
                                    <span
                                        class="w-5 border border-[#7963e0] block bg-[#f6f8ff] absolute top-28 my-1.5 left-1.5"></span>
                                </div>
                                <span class="text-[#777] text-[12px] font-medium">January 23, 2018 - 2020</span>
                                <h4 class="text-[16px] leading-[22px] font-semibold mt-[15px] mb-[6px] text-[#7963e0]">
                                    Facebook Company <span class="ml-[15px] text-[#999] text-[14px]">- Sr.
                                        Developer</span></h4>
                                <p class="text-[13px] text-[#777] mb-0 leading-[28px]">Lorem, ipsum dolor sit amet
                                    consectetur adipisicing elit.
                                    Mollitia sequi doloremque ullam placeat quos provident ex fuga. Ratione ab explicabo
                                    doloribus error odio veritatis tenetur.</p>
                            </div>
                            <div class="p-[30px] bg-white rounded-3xl mt-8 relative" data-aos="fade-up"
                                data-aos-duration="2000" data-aos-delay="800">
                                <div class="absolute top-0 bottom-0 -left-6 w-4">
                                    <span
                                        class="w-4 h-4 border-2 border-[#7963e0] rounded-full block bg-[#f6f8ff] absolute top-28 -left-2.5"></span>
                                    <span
                                        class="w-5 border border-[#7963e0] block bg-[#f6f8ff] absolute top-28 my-1.5 left-1.5"></span>
                                </div>
                                <span class="text-gray-500 text-sm">July 23, 2016 - 2018 </span>
                                <h4 class="text-[16px] leading-[22px] font-semibold mt-[15px] mb-[6px] text-[#7963e0]">
                                    Twitter Company <span class="ml-[15px] text-[#999] text-[14px]">- Jr.
                                        Developer</span></h4>
                                <p class="text-[13px] text-[#777] mb-0 leading-[28px]">Lorem, ipsum dolor sit amet
                                    consectetur adipisicing elit.
                                    Mollitia sequi doloremque ullam placeat quos provident ex fuga. Ratione ab explicabo
                                    doloribus error odio veritatis tenetur.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <Customers />
    <!-- News -->
    <!-- <section id="news" class="section-news bg-[#f6f8ff]">
        <div class="relative pt-[60px]">
            <img :src="`${pathImage}themes/personalLanding/assets/img/shape/bg-shape.png`" alt="bg-shape"
                class="absolute top-0 left-0 right-0 w-full bg-center bg-cover">
        </div>
        <div class="2xl:pb-[80px] pb-[70px] 2xl:pt-[80px] lg:pt-[80px] pt-[20px]">
            <div class="banner text-center mb-[30px]" data-aos="fade-up" data-aos-duration="2000" data-aos-delay="300">
                <span class="text-[14px] text-[#777]">BLOGS</span>
                <h2
                    class="text-center mt-[5px] 2xl:text-[35px] xl:text-[33px] lg:text-[30px] md:text-[26px] sm:text-[24px] text-[22px] font-bold">
                    Latest <span class="text-[#7963e0]"> NEWS</span></h2>
            </div>
            <div class="flex flex-wrap justify-between items-center mx-auto mx-auto 2xl:max-w-[1320px] xl:max-w-[1140px] lg:max-w-[960px] md:max-w-[720px] sm:max-w-[540px] max-[320px]:px-[12px] relative px-6"
                data-aos="fade-up" data-aos-duration="2000" data-aos-delay="300">
                <div class="relative"></div>
                <div class="2xl:absolute 2xl:max-[1140px] xl:absolute xl:max-w-[720px] lg:absolute lg:max-w-[620px] max-w-[100%] relative">
                    <div class="transition-all justify-start items-start">
                        <div class="news-slider">
                            <div class="carousel-wrap">
                                <div class="owl-carousel news-carousel owl-loaded owl-drag">
                                    <div class="owl-stage-outer">
                                        <div class="owl-stage">
                                            <div class="owl-item">
                                                <div class="card bg-white border rounded-2xl p-6">
                                                    <img src="assets/img/news/img-2.jpg" alt="news-3">
                                                    <div class="news-card-details mt-[16px]">
                                                        <p class="text-[13px] leading-[30px] mb-[5px] text-[#7963e0]">By
                                                            Admin <span class="text-gray-400">- 04 Comments</span></p>
                                                        <h5
                                                            class="pb-[15px] text-[17px] font-bold border-b border-[#dbdada]">
                                                            Lorem ipsum dolor sit amet.</h5>
                                                        <div class="pt-[15px] text-sm text-[#7963e0]">Read More <a
                                                                class="hover:text-[#7963e0]">
                                                                <i class="fa-solid fa-arrow-right"></i></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="owl-item">
                                                <div class="card bg-white border rounded-2xl p-6">
                                                    <img src="assets/img/news/img-3.jpg" alt="news-3">
                                                    <div class="news-card-details mt-[16px]">
                                                        <p class="text-[13px] leading-[30px] mb-[5px] text-[#7963e0]">By
                                                            Admin <span class="text-gray-400">- 04 Comments</span></p>
                                                        <h5
                                                            class="pb-[15px] text-[17px] font-bold border-b border-[#dbdada]">
                                                            Lorem ipsum dolor sit amet.</h5>
                                                        <div class="pt-[15px] text-sm text-[#7963e0]">Read More <a
                                                                class="hover:text-[#7963e0]">
                                                                <i class="fa-solid fa-arrow-right"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="transition-all flex-row 2xl:block xl:block lg:block hidden">
                    <img src="assets/img/news/img-1.jpg" alt="news" class="w-auto rounded-[15px]">
                </div>
            </div>
        </div>
    </section> -->
    
    <Teams /> 

    <!-- contact -->
     <ContactForm />

    <!-- footer -->
    
    <Footer />

    <WhatsappHome />

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


