<script setup>

</script>

<template>
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
                                class="text-white bg-[#0188ee] hover:bg-[#f11600] no-underline font-medium rounded-full text-sm px-8 py-2.5 mr-2 
                                shadow-lg transition duration-500 ease-in-out hover:-translate-y-1 hover:scale-110">
                                Enviar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</template>