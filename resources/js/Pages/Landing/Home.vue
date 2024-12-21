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
import iconWhatsapp from '@/Components/vristo/icon/icon-whatsapp.vue';

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
                Haz Crecer Tu Negocio con Sistemas Web que Generan Resultados
            </h1>
            <p class="mb-8 text-lg font-normal text-gray-300 lg:text-xl sm:px-16 lg:px-48">
                Diseñamos sistemas web personalizados que optimizan tus procesos, mejoran la experiencia del cliente y 
                generan resultados para tu negocio.
            </p>
            <div class="flex flex-col space-y-4 sm:flex-row sm:justify-center sm:space-y-0">
                <a href="#contact" class="inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-[#0188ee] hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-900">
                    Ver Demos
                    <svg class="w-3.5 h-3.5 ms-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                    </svg>
                </a>
                <a href="#contact" class="inline-flex justify-center hover:text-gray-900 items-center py-3 px-5 sm:ms-4 text-base font-medium text-center text-white rounded-lg border border-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-400">
                    Contactanos
                </a>  
            </div>
        </div>
    </section>


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
    <section id="contact" class="section-contact 2xl:py-[80px] py-[70px] dark:bg-gray-800">
        <div class="banner text-center mb-[30px]" data-aos="fade-up" data-aos-duration="2000" data-aos-delay="300">
            <span class="text-[14px] text-[#777] dark:text-[#ddd]">CONTACTANOS</span>
            <h2
                class="text-center mt-[5px] 2xl:text-[35px] xl:text-[33px] lg:text-[30px] md:text-[26px] sm:text-[24px] text-[22px] font-bold dark:text-[#fff]">
                QUEREMOS SABER
                <span class="text-[#0188ee]">DE USTED</span>
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
                            <div class="border-b dark:border-[#27213b] pt-1">
                                <input type="email"
                                    class="appearance-none w-full py-6 px-3 text-gray-700 leading-tight focus:outline-none dark:bg-[#161321]"
                                    id="umail" placeholder="Correo electrónico">
                            </div>
                            <div class="border-b dark:border-[#27213b] pt-1">
                                <input type="text"
                                    class="appearance-none w-full py-6 px-3 text-gray-700 leading-tight focus:outline-none dark:bg-[#161321]"
                                    id="phone" placeholder="Teléfono">
                            </div>
                            <div class="dark:border-[#27213b] pt-1">
                                <input type="text"
                                    class="appearance-none w-full py-6 px-3 text-gray-700 leading-tight focus:outline-none rounded-b-lg dark:bg-[#161321]"
                                    id="subject" placeholder="Asunto">
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="mb-3">
                                <textarea class="w-full border dark:border-[#27213b] rounded-xl pl-4 pt-3 focus:outline-none dark:bg-[#161321]"
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

    <!-- footer -->
    
    <Footer />

    <div id="whatsapp">
        <a href="https://wa.link/path6f" target="_blank" class="wtsapp" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <iconWhatsapp class=" w-8 text-white hover:text-white z-10" />
        </a>
    </div>



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


<style type="text/css">
#whatsapp .wtsapp{
    position: fixed;
    transform: all .5s ease;
    background-color: #25D366;
    display: block;
    text-align: center;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
    border-radius: 50px;
    border-right: none;
    color: #fff;
    font-weight: 700;
    font-size: 30px;
    bottom: 80px;
    right: 20px;
    border: 0;
    z-index: 9999;
    width: 50px;
    height: 50px;
    line-height: 50px;
}

#whatsapp .wtsapp:before{
    content: "";
    position: absolute;
    z-index: -1;
    left: 50%;
    top: 50%;
    transform: translateX(-50%) translateY(-50%);
    display: block;
    width: 60px;
    height: 60px;
    background-color: #25D366;
    border-radius: 50%;
    -webkit-animation: pulse-border 1500ms ease-out infinite;
    animation: pulse-border 1500ms ease-out infinite;
}

#whatsapp .wtsapp:focus{
    border: none;
    outline: none;
}

@keyframes pulse-border{
    0%{
        transform: translateX(-50%) translateY(-50%) translateZ(0) scale(1);
        opacity: 1;
    }
    100%{
        transform: translateX(-50%) translateY(-50%) translateZ(0) scale(1.5);
        opacity: 0;
    }
}

</style>
