<script setup>
import ModalSmall from '@/Components/ModalSmall.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useAppStore } from '@/stores/index';
const company = usePage().props.company;

const xassetUrl = assetUrl;

const store = useAppStore();

const scrollToContact = () => {
    const element = document.getElementById('contact');
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

const props = defineProps({
    dataWelcome: {
        type: Object,
        default: () => ({})
    }
});

const shape1 = ref(null);
const shape2 = ref(null);
const shape3 = ref(null);
const shape4 = ref(null);
const shape5 = ref(null);

const parallaxElements = [
    { ref: shape1, rate: 0.2 },
    { ref: shape2, rate: 0.2 },
    { ref: shape3, rate: 0.2 },
    { ref: shape4, rate: 0.3 },
    { ref: shape5, rate: 0.2 },
];

const handleMouseMove = (event) => {
    const { innerWidth, innerHeight } = window;
    const centerX = innerWidth / 2;
    const centerY = innerHeight / 2;

    const mouseX = event.clientX - centerX;
    const mouseY = event.clientY - centerY;

    //parallaxElements.forEach(({ ref, rate }) => {
    //    if (ref.value && isElementVisible(ref.value)) {
    //       // Limitar el desplazamiento al ancho y alto de la pantalla
    //        const maxX = innerWidth / 2; // Máximo desplazamiento en X
    //        const maxY = innerHeight / 2; // Máximo desplazamiento en Y

    //       const translateX = Math.min(Math.max(mouseX * rate, -maxX), maxX);
    //        const translateY = Math.min(Math.max(mouseY * rate, -maxY), maxY);

    //        // Aplicar transformación limitada
    //        ref.value.style.transform = `translate(${translateX}px, ${translateY}px)`;
    //    }
    //});
};

onMounted(() => {
    window.addEventListener('mousemove', handleMouseMove);
});

// Eliminar el evento al desmontar el componente
onUnmounted(() => {
    window.removeEventListener('mousemove', handleMouseMove);
});

</script>
<template>
    <section id="home" class="section-hero bg-[#fff] relative dark:bg-[#161321] -z-10 -mt-20">
        <img ref="shape1" class="shape1 absolute w-12 left-72 bottom-36 parallax sm:block hidden" 
            :src="`${xassetUrl}themes/personalLanding/assets/img/shape/shape-1.png`" alt="shape-1">
        <img ref="shape2" class="shape2 absolute w-12 top-72 right-32 parallax top sm:block hidden" 
            :src="`${xassetUrl}themes/personalLanding/assets/img/shape/shape-2.png`" alt="shape-2">
        <img ref="shape3" class="shape3 absolute w-12 top-48	left-96 parallax left top sm:block hidden" 
            :src="`${xassetUrl}themes/personalLanding/assets/img/shape/shape-3.png`"
            alt="shape-3">
        <img ref="shape4" class="shape4 absolute w-6 bottom-72 left-24 parallax left sm:block hidden" :src="`${xassetUrl}themes/personalLanding/assets/img/shape/shape-4.png`"
            alt="shape-4">
        <img ref="shape5" class="shape5 absolute w-12 bottom-48 right-12 parallax bottom sm:block hidden" :src="`${xassetUrl}themes/personalLanding/assets/img/shape/shape-5.png`"
            alt="shape-5">
        <div class="flex flex-wrap justify-between items-center mx-auto 2xl:max-w-[1320px] 
            xl:max-w-[1140px] lg:max-w-[960px] md:max-w-[720px] sm:max-w-[540px] pt-[0px] pb-[80px]
            px-4">
            <div class="w-full 2xl:h-[90vh] lg:h-[80vh] h-[70vh] max-[320px]:h-[50vh] flex items-center 
                    px-2 2xl:max-w-lg xl:max-w-lg lg:max-w-lg lg:w-1/2 lg:mx-0 md:max-w-lg md:w-1/2 md:mx-0 
                    2xl:w-1/2 xl:w-1/2 sm:items-center">
                <div class="text-center 2xl:text-left xl:text-left lg:text-left md:text-left h-72">
                    <span class="text-[#0188ee] text-[18px] font-bold">{{ dataWelcome.items[0].item.content }}</span>
                    <h1
                        class="text-dark-800 dark:text-[#fff] 2xl:text-[60px] xl:text-[55px] lg:text-[50px] md:text-[45px] text-[40px] font-bold">
                        {{ dataWelcome.items[1].item.content }}
                    </h1>
                    <h2 class="py-2 text-dark-800 text-[20px] font-bold dark:text-[#fff]">{{ dataWelcome.items[2].item.content }}</h2>
                    <p class="pt-2 text-gray-500 dark:text-[#ddd] text-base">
                        {{ dataWelcome.items[3].item.content }}
                    </p>
                    <br>
                    <button @click="scrollToContact" type="button"
                        class="text-white mt-4 bg-[#0188ee] hover:bg-[#f11600] no-underline font-medium rounded-full 
                        text-sm px-8 py-2.5 mr-2
                        shadow-lg transition duration-500 ease-in-out hover:-translate-y-1 hover:scale-100">
                        Escribenos
                    </button> 
                </div>
            </div>
            <div class="w-1/2 hidden px-2 2xl:block xl:block lg:block md:block z-10">
                <img :src="`${xassetUrl}themes/vristo/images/app-development-rafiki.svg`" alt="girl" class="max-h-full">
            </div>
        </div>
        <!-- <div class="relative">
            <template v-if="store.theme === 'light'">
                <img :src="`${xassetUrl}themes/personalLanding/assets/img/shape/hero-shape.png`" alt="hero-shape"
                class="absolute bottom-0 left-0 right-0 w-full z-10 bg-center bg-cover"
                >
            </template>
            <template v-else>
                <img :src="`${xassetUrl}themes/personalLanding/assets/img/shape/hero-shape-dark.png`" alt="hero-shape"
                class="absolute bottom-0 left-0 right-0 w-full bg-center z-10 bg-cover">
            </template>
        </div> -->
    </section>

</template>