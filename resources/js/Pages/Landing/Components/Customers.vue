<script setup>
const xassetUrl = assetUrl;

import iconEye from '@/Components/vristo/icon/icon-eye.vue';

</script>
<template>
    <section id="portfolio" class="section-Portfolio 2xl:py-[80px] py-[70px] bg-white dark:bg-[#120f1c]">
        <div class="banner text-center mb-[30px]" data-aos="fade-up" data-aos-duration="2000" data-aos-delay="300">
            <span class="text-[14px] text-[#777] dark:text-[#ddd]">PORTAFOLIO</span>
            <h2
                class="text-center mt-[5px] 2xl:text-[35px] xl:text-[33px] lg:text-[30px] md:text-[26px] sm:text-[24px] 
                text-[22px] font-bold dark:text-white">
                ALGUNOS <span class="text-[#0188ee]"> PROYECTOS</span>
            </h2>
        </div>
        <div
            class="flex flex-wrap justify-between items-center mx-auto 2xl:max-w-[1320px] xl:max-w-[1140px] lg:max-w-[960px] md:max-w-[720px] sm:max-w-[540px] max-[320px]:px-[12px] px-6">
            <div class="m-b-minus-24px w-full">
                <div class="portfolio-content" id="MixItUpDA2FB7" data-aos="fade-up" data-aos-duration="2000"
                    data-aos-delay="600">
                    <div class="portfolio-tabs mb-[30px]">
                        <ul
                            class="2xl:flex xl:flex md:flex sm:block place-content-center text-center">
                            <li class="text-[14px] text-[#17181c] dark:text-[#ddd] 2xl:mx-[10px] sm:mx-[0px] px-[10px] leading-[11px] font-semibold hover:text-[#0188ee] cursor-pointer inline-block active"
                                data-filter="all">
                                ALL</li>
                            <li class="text-[14px] text-[#17181c] dark:text-[#ddd] 2xl:mx-[10px] sm:mx-[0px] px-[10px] leading-[11px] font-semibold hover:text-[#0188ee] cursor-pointer inline-block"
                                data-filter=".corporativo">CORPORATIVO</li>
                            <li class="text-[14px] text-[#17181c] dark:text-[#ddd] 2xl:mx-[10px] sm:mx-[0px] px-[10px] leading-[11px] font-semibold hover:text-[#0188ee] cursor-pointer inline-block"
                                data-filter=".elerarning">E-LEARNING</li>
                            <li class="text-[14px] text-[#17181c] dark:text-[#ddd] 2xl:mx-[10px] sm:mx-[0px] px-[10px] leading-[11px] font-semibold hover:text-[#0188ee] cursor-pointer inline-block"
                                data-filter=".ecomerce">E-COMMERCE</li>
                            <li class="text-[14px] text-[#17181c] dark:text-[#ddd] 2xl:mx-[10px] sm:mx-[0px] px-[10px] leading-[11px] font-semibold hover:text-[#0188ee] cursor-pointer inline-block"
                                data-filter=".sistemas">SISTEMAS WEBS</li>
                        </ul>
                    </div>
                    <div class="portfolio-content-items">
                        <div class="grid lg:grid-cols-3 sm:grid-cols-2 grid-cols-1 gap-[30px]">
                            <div class="mix graphics templates">
                                <div class="portfolio-img truncate rounded-2xl relative">
                                    <img :src="`${xassetUrl}img/Landing/customers/Portafolio_Kentha.jpg`" style="width: 400px;"
                                        class="w-full transform hover:bg-blue-600 transition duration-500 hover:-rotate-12 hover:scale-125">
                                    <!-- <h3 class="top-contain absolute top-[15px] right-[15px]">
                                        <span
                                            class="bg-black rounded-full text-white font-normal text-[12px] px-2 py-1">3D
                                            Graphics</span>
                                        <span
                                            class="bg-black rounded-full text-white font-normal text-[12px] px-2 py-1">Templates</span>
                                    </h3> -->
                                    <div class="bottom-contain absolute bottom-4 left-4 right-4">
                                        <div
                                            class="overlay-info px-4 py-2 bg-black bg-opacity-90 rounded-xl grid grid-cols-2 gap-[30px] place-content-between">
                                            <a href="https://kentha.cl/" class="text-white text-sm flex items-center">
                                                https://kentha.cl/
                                            </a>
                                            <a href="https://kentha.cl/" data-fancybox="gallery"
                                                class="text-white text-sm grid justify-items-end">
                                                <p class="hidden"></p>
                                                <span class="bg-[#0188ee] h-8 w-8 flex justify-center
                                                items-center rounded-md">
                                                    <icon-eye class=" w-5  h-5 text-white font-bold" />
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mix design">
                                <div class="portfolio-img truncate rounded-2xl relative">
                                    <img :src="`${xassetUrl}img/Landing/customers/Portafolio_ZoeLife.jpg`" alt="design"
                                        class="w-full transform hover:bg-blue-600 transition duration-500 hover:-rotate-12 hover:scale-125">
                                    <!-- <h3 class="top-contain absolute top-[15px] right-[15px]">
                                        <span
                                            class="bg-black rounded-full font-normal text-white text-[12px] px-2 py-1">Web
                                            Design</span>
                                    </h3> -->
                                    <div class="bottom-contain absolute bottom-4 left-4 right-4">
                                        <div
                                            class="overlay-info px-4 py-2 bg-black bg-opacity-90 rounded-xl grid grid-cols-2 gap-[30px] place-content-between">
                                            <a href="https://zoelifeinternational.com/" class="text-white text-sm flex items-center">
                                                https://zoelifeinternational.com/
                                            </a>
                                            <a href="https://zoelifeinternational.com/" data-fancybox="gallery"
                                                class="text-white text-sm grid justify-items-end">
                                                <p class="hidden"></p>
                                                <span class="bg-[#0188ee] h-8 w-8 flex justify-center
                                                items-center rounded-md">
                                                    <icon-eye class=" w-5  h-5 text-white font-bold" />
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mix design">
                                <div class="portfolio-img truncate rounded-2xl relative">
                                    <img :src="`${xassetUrl}img/Landing/customers/Portafolio_Capperu.jpg`" alt="design"
                                        class="w-full transform hover:bg-blue-600 transition duration-500 hover:-rotate-12 hover:scale-125">
                                    <!-- <h3 class="top-contain absolute top-[15px] right-[15px]">
                                        <span class="bg-black rounded-full font-normal text-white text-[12px] px-2 py-1">Web
                                            Design</span>
                                    </h3> -->
                                    <div class="bottom-contain absolute bottom-4 left-4 right-4">
                                        <div
                                            class="overlay-info px-4 py-2 bg-black bg-opacity-90 rounded-xl grid grid-cols-2 gap-[30px] place-content-between">
                                            <a href="#" class="text-white text-sm flex items-center">
                                                https://capperu.com/
                                            </a>
                                            <a href="assets/img/portfolio/img-3.jpg" data-fancybox="gallery"
                                                class="text-white text-sm grid justify-items-end">
                                                <p class="hidden"></p>
                                                <span class="bg-[#0188ee] h-8 w-8 flex justify-center
                                                items-center rounded-md">
                                                    <icon-eye class=" w-5  h-5 text-white font-bold" />
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mix development">
                                <div class="portfolio-img truncate rounded-2xl relative">
                                    <img :src="`${xassetUrl}img/Landing/customers/Portafolio_CelMovil.jpg`" alt="development"
                                        class="w-full transform hover:bg-blue-600 transition duration-500 hover:-rotate-12 hover:scale-125">
                                    <!-- <h3 class="top-contain absolute top-[15px] right-[15px]">
                                        <span class="bg-black rounded-full font-normal text-white text-[12px] px-2 py-1">Development</span>
                                    </h3> -->
                                    <div class="bottom-contain absolute bottom-4 left-4 right-4">
                                        <div
                                            class="overlay-info px-4 py-2 bg-black bg-opacity-90 rounded-xl grid grid-cols-2 gap-[30px] place-content-between">
                                            <a href="#" class="text-white text-sm flex items-center">
                                                https://celmovil.pe/
                                            </a>
                                            <a href="assets/img/portfolio/img-4.jpg" data-fancybox="gallery"
                                                class="text-white text-sm grid justify-items-end">
                                                <p class="hidden"></p>
                                                <span class="bg-[#0188ee] h-8 w-8 flex justify-center
                                                items-center rounded-md">
                                                    <icon-eye class=" w-5  h-5 text-white font-bold" />
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mix templates design">
                                <div class="portfolio-img truncate rounded-2xl relative">
                                    <img :src="`${xassetUrl}img/Landing/customers/Portafolio_Despega.jpg`" alt="templates"
                                        class="w-full transform hover:bg-blue-600 transition duration-500 hover:-rotate-12 hover:scale-125">
                                    <!-- <h3 class="top-contain absolute top-[15px] right-[15px]">
                                        <span
                                            class="bg-black rounded-full font-normal text-white text-[12px] px-2 py-1">Templates</span>
                                        <span
                                            class="bg-black rounded-full font-normal text-white text-[12px] px-2 py-1">Web
                                            Design</span>
                                    </h3> -->
                                    <div class="bottom-contain absolute bottom-4 left-4 right-4">
                                        <div
                                            class="overlay-info px-4 py-2 bg-black bg-opacity-90 rounded-xl grid grid-cols-2 gap-[30px] place-content-between">
                                            <a href="#" class="text-white text-sm flex items-center">
                                                https://marketingdespega.com/
                                            </a>
                                            <a href="assets/img/portfolio/img-1.jpg" data-fancybox="gallery"
                                                class="text-white text-sm grid justify-items-end">
                                                <p class="hidden"></p>
                                                <span class="bg-[#0188ee] h-8 w-8 flex justify-center
                                                items-center rounded-md">
                                                    <icon-eye class=" w-5  h-5 text-white font-bold" />
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mix development graphics">
                                <div class="portfolio-img truncate rounded-2xl relative">
                                    <img :src="`${xassetUrl}img/Landing/customers/Portafolio_ERP.jpg`" alt="development"
                                        class="w-full transform hover:bg-blue-600 transition duration-500 hover:-rotate-12 hover:scale-125">
                                    <!-- <h3 class="top-contain absolute top-[15px] right-[15px]">
                                        <span
                                            class="bg-black rounded-full font-normal text-white text-[12px] px-2 py-1">Development</span>
                                        <span
                                            class="bg-black rounded-full font-normal text-white text-[12px] px-2 py-1">3D
                                            Graphics</span>
                                    </h3> -->
                                    <div class="bottom-contain absolute bottom-4 left-4 right-4">
                                        <div
                                            class="overlay-info px-4 py-2 bg-black bg-opacity-90 rounded-xl grid grid-cols-2 gap-[30px] place-content-between">
                                            <a href="#" class="text-white text-sm flex items-center">
                                                https://marketingdespega.com/login
                                            </a>
                                            <a href="assets/img/portfolio/img-2.jpg" data-fancybox="gallery"
                                                class="text-white text-sm grid justify-items-end">
                                                <p class="hidden">.</p>
                                                <span class="bg-[#0188ee] h-8 w-8 flex justify-center
                                                items-center rounded-md">
                                                    <icon-eye class=" w-5  h-5 text-white font-bold" />
                                                </span>
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
        <br><br>
        <div class="flex justify-center">
            <div>
                <a href="#contact" type="button"
                        class=" text-white bg-[#0188ee] hover:bg-[#f11600] no-underline font-medium rounded-full text-sm 
                        px-8 py-2.5 mr-2 hidden 2xl:block xl:block lg:block
                        shadow-lg transition duration-500 ease-in-out hover:-translate-y-1 hover:scale-110">
                        Obtenga una cotización
                </a>
            </div>
        </div>
    </section>
</template>