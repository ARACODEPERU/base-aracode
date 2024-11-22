<script setup>
import '@Public/themes/personalLanding/assets/css/style.css';
import Header from './Components/Header.vue';
import Jumbotron from './Components/Jumbotron.vue';
import ServicesTI from './Components/ServicesTI.vue';
import About from './Components/About.vue';
import Customers from './Components/Customers.vue';
import Footer from './Components/Footer.vue';
import { Head, Link } from '@inertiajs/vue3';
import FacturacionPrices from './Components/FacturacionPrices.vue';
import Teams from './Components/Teams.vue';

const xassetUrl = assetUrl;

</script>
<template>
    <Head>
        <title>Landing</title>
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
    <Header />

    <!-- hero section -->
    <Jumbotron />

    <!-- service -->
    
    <ServicesTI />
    <!-- about -->
    
    <About />
    <!-- facturacion electronica precios -->
    
    <FacturacionPrices />
    <!-- Portfolio -->
    <!--  -->
    <!-- <Customers /> -->
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
    <section id="contact" class="section-contact 2xl:py-[80px] py-[70px]">
        <div class="banner text-center mb-[30px]" data-aos="fade-up" data-aos-duration="2000" data-aos-delay="300">
            <span class="text-[14px] text-[#777]">FORMULARIO</span>
            <h2
                class="text-center mt-[5px] 2xl:text-[35px] xl:text-[33px] lg:text-[30px] md:text-[26px] sm:text-[24px] text-[22px] font-bold">
                Ponte en contacto con 
                <span class="text-[#7963e0]">NOSOTROS</span>
            </h2>
        </div>
        <form>
            <div
                class="flex flex-wrap justify-between items-center mx-auto mx-auto 2xl:max-w-[1320px] xl:max-w-[1140px] lg:max-w-[960px] md:max-w-[720px] sm:max-w-[540px] max-[320px]:px-[12px] px-6">
                <div class="m-b-minus-24px w-full">
                    <div class="grid lg:grid-cols-2 grid-cols-1 gap-[30px]" data-aos="fade-up" data-aos-duration="2000" data-aos-delay="300">
                        <div class="border rounded-lg">
                            <div class="border-b">
                                <input type="text"
                                    class="appearance-none w-full py-6 px-3 text-gray-700 leading-tight focus:outline-none rounded-t-lg"
                                    id="fname" placeholder="Nombre completo">
                            </div>
                            <div class="border-b">
                                <input type="email"
                                    class="appearance-none w-full py-6 px-3 text-gray-700 leading-tight focus:outline-none"
                                    id="umail" placeholder="Correo electrónico">
                            </div>
                            <div class="border-b">
                                <input type="text"
                                    class="appearance-none w-full py-6 px-3 text-gray-700 leading-tight focus:outline-none"
                                    id="phone" placeholder="Teléfono">
                            </div>
                            <div>
                                <input type="text"
                                    class="appearance-none w-full py-6 px-3 text-gray-700 leading-tight focus:outline-none rounded-b-lg"
                                    id="subject" placeholder="Asunto">
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="mb-4">
                                <textarea class="w-full border rounded-xl pl-4 pt-2 focus:outline-none"
                                    id="exampleFormControlTextarea1" rows="8" placeholder="Mensaje"></textarea>
                            </div>
                            <button type="button"
                                class="text-white bg-[#7963e0] hover:bg-opacity-80 no-underline font-medium rounded-full text-sm px-8 py-2.5 mr-2">
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
    <!-- scroll Top -->
    <div id="scrollup"
        class="fixed bg-[#7963e0] text-white rounded-full flex justify-center text-center items-center p-2 right-6 cursor-pointer bottom-6 h-10 w-10 z-20">
        <i class="fa fa-arrow-up" aria-hidden="true"></i>
    </div>
</template>
