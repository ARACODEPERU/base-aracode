<script setup>

    import { ref, onMounted, computed, watch } from 'vue';

    const displayBirthdays = ref(false);
    const birthdays = ref([]);


    const getDataBirthdays = () => {
        displayBirthdays.value = true;

        axios.post(route('person-birthdays')).then((response) => {
            return response.data;
        }).then((result) => {
            birthdays.value = result;
            return 1;
        //displayDiskBusy.value = false;
        }).then(() =>{
            displayBirthdays.value = false;
        }).catch((error) => {
            console.error('Error al obtener los datos:', error);
            displayBirthdays.value = false;
        });
    };

    onMounted(() => {
        getDataBirthdays();
    });

    const baseUrl = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

</script>
<template>
    <div class="panel">
        <template v-if="displayBirthdays">
            <div role="status" class="">
                <div class="flex items-center justify-between">
                    <div class="flex items-center mt-4">
                        <svg class="w-10 h-10 me-3 text-gray-200 dark:text-gray-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                        </svg>
                        <div>
                            <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-32 mb-2"></div>
                            <div class="w-48 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center mt-4">
                        <svg class="w-10 h-10 me-3 text-gray-200 dark:text-gray-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                        </svg>
                        <div>
                            <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-32 mb-2"></div>
                            <div class="w-48 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center mt-4">
                        <svg class="w-10 h-10 me-3 text-gray-200 dark:text-gray-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                        </svg>
                        <div>
                            <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-32 mb-2"></div>
                            <div class="w-48 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template v-else>
            <div class="flex items-start justify-between dark:text-white-light mb-5 -mx-5 p-5 pt-0">
                <h5 class="font-semibold text-lg">CUMPLEAÑOS</h5>
                <!-- <div class="dropdown">
                    <Popper :placement="'bottom-start'" offsetDistance="0" class="align-middle">
                        <button type="button">
                            <icon-horizontal-dots class="text-black/70 dark:text-white/70 hover:!text-primary" />
                        </button>
                        <template #content="{ close }">
                            <ul @click="close()">
                                <li>
                                    <a href="javascript:;">View All</a>
                                </li>
                                <li>
                                    <a href="javascript:;">Mark as Read</a>
                                </li>
                            </ul>
                        </template>
                    </Popper>
                </div> -->
            </div>
            <template v-if="birthdays.length > 0">

                <perfect-scrollbar
                    :options="{
                        swipeEasing: true,
                        wheelPropagation: false,
                    }"
                    class="relative max-h-[380px]"
                >
                    <div class="space-y-4">
                        <template v-for="(per, hey) in birthdays">
                                <div class="sm:flex pl-4">
                                    <div class="
                                        relative
                                        mx-auto
                                        mb-5
                                        sm:mb-0
                                        ltr:sm:mr-4
                                        rtl:sm:ml-4
                                        z-[2]
                                        before:absolute before:top-12 before:left-1/2 before:-bottom-[15px] before:-translate-x-1/2 before:border-l-2 before:border-[#ebedf2] before:w-0 before:h-auto before:-z-[1]
                                        dark:before:border-[#191e3a]
                                        before:hidden
                                        sm:before:block
                                    "
                                    >
                                        <img v-if="per.image" :src="getImage(per.image)" alt="" class="w-12 h-12 mx-auto rounded-full shadow-[0_4px_9px_0_rgba(31,45,61,0.31)]" />
                                        <img v-else :src="`https://ui-avatars.com/api/?name=${per.name}&size=150&rounded=true`" alt="" class="w-12 h-12 mx-auto rounded-full shadow-[0_4px_9px_0_rgba(31,45,61,0.31)]" />
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center p-3.5 rounded-tl-none rounded-xl text-primary bg-primary-light dark:bg-primary-dark-light"
                                        :class="{
                                            'animate-blink text-white': per.status === 'hoy',
                                        }"
                                        >
                                            <span class="ltr:pr-2 rtl:pl-2">
                                                {{ per.status == 'pasado' ? 'El '+ per.birthdate + ' fue el' :  per.status == 'hoy' ? 'Hoy '+per.birthdate+' es el' : 'El próximo '+per.birthdate+' es el'}} cumpleaños de
                                                <strong class="ltr:mr-1 rtl:ml-1">{{ per.name }}</strong>

                                            </span>
                                            <button v-if="per.status == 'hoy'" type="button" class="ltr:ml-auto rtl:mr-auto hover:opacity-80">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                                    <path fill="currentColor" d="M86.4 5.5L61.8 47.6C58 54.1 56 61.6 56 69.2L56 72c0 22.1 17.9 40 40 40s40-17.9 40-40l0-2.8c0-7.6-2-15-5.8-21.6L105.6 5.5C103.6 2.1 100 0 96 0s-7.6 2.1-9.6 5.5zm128 0L189.8 47.6c-3.8 6.5-5.8 14-5.8 21.6l0 2.8c0 22.1 17.9 40 40 40s40-17.9 40-40l0-2.8c0-7.6-2-15-5.8-21.6L233.6 5.5C231.6 2.1 228 0 224 0s-7.6 2.1-9.6 5.5zM317.8 47.6c-3.8 6.5-5.8 14-5.8 21.6l0 2.8c0 22.1 17.9 40 40 40s40-17.9 40-40l0-2.8c0-7.6-2-15-5.8-21.6L361.6 5.5C359.6 2.1 356 0 352 0s-7.6 2.1-9.6 5.5L317.8 47.6zM128 176c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 48c-35.3 0-64 28.7-64 64l0 71c8.3 5.2 18.1 9 28.8 9c13.5 0 27.2-6.1 38.4-13.4c5.4-3.5 9.9-7.1 13-9.7c1.5-1.3 2.7-2.4 3.5-3.1c.4-.4 .7-.6 .8-.8l.1-.1s0 0 0 0s0 0 0 0s0 0 0 0s0 0 0 0c3.1-3.2 7.4-4.9 11.9-4.8s8.6 2.1 11.6 5.4c0 0 0 0 0 0s0 0 0 0l.1 .1c.1 .1 .4 .4 .7 .7c.7 .7 1.7 1.7 3.1 3c2.8 2.6 6.8 6.1 11.8 9.5c10.2 7.1 23 13.1 36.3 13.1s26.1-6 36.3-13.1c5-3.5 9-6.9 11.8-9.5c1.4-1.3 2.4-2.3 3.1-3c.3-.3 .6-.6 .7-.7l.1-.1c3-3.5 7.4-5.4 12-5.4s9 2 12 5.4l.1 .1c.1 .1 .4 .4 .7 .7c.7 .7 1.7 1.7 3.1 3c2.8 2.6 6.8 6.1 11.8 9.5c10.2 7.1 23 13.1 36.3 13.1s26.1-6 36.3-13.1c5-3.5 9-6.9 11.8-9.5c1.4-1.3 2.4-2.3 3.1-3c.3-.3 .6-.6 .7-.7l.1-.1c2.9-3.4 7.1-5.3 11.6-5.4s8.7 1.6 11.9 4.8c0 0 0 0 0 0s0 0 0 0s0 0 0 0l.1 .1c.2 .2 .4 .4 .8 .8c.8 .7 1.9 1.8 3.5 3.1c3.1 2.6 7.5 6.2 13 9.7c11.2 7.3 24.9 13.4 38.4 13.4c10.7 0 20.5-3.9 28.8-9l0-71c0-35.3-28.7-64-64-64l0-48c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 48-64 0 0-48c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 48-64 0 0-48zM448 394.6c-8.5 3.3-18.2 5.4-28.8 5.4c-22.5 0-42.4-9.9-55.8-18.6c-4.1-2.7-7.8-5.4-10.9-7.8c-2.8 2.4-6.1 5-9.8 7.5C329.8 390 310.6 400 288 400s-41.8-10-54.6-18.9c-3.5-2.4-6.7-4.9-9.4-7.2c-2.7 2.3-5.9 4.7-9.4 7.2C201.8 390 182.6 400 160 400s-41.8-10-54.6-18.9c-3.7-2.6-7-5.2-9.8-7.5c-3.1 2.4-6.8 5.1-10.9 7.8C71.2 390.1 51.3 400 28.8 400c-10.6 0-20.3-2.2-28.8-5.4L0 480c0 17.7 14.3 32 32 32l384 0c17.7 0 32-14.3 32-32l0-85.4z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>


                        </template>
                    </div>
                </perfect-scrollbar>
            </template>
            <template>
                <div class="flex items-center p-3.5 rounded text-secondary bg-secondary-light dark:bg-secondary-dark-light">
                    <span class="ltr:pr-2 rtl:pl-2"><strong class="ltr:mr-1 rtl:ml-1">Aun no </strong>existen registro.</span>
                </div>
            </template>
        </template>
    </div>
</template>
<style scoped>
@keyframes blink {
    0% { background-color: #208607; } /* Azul oscuro */
    50% { background-color: #0f9b16; } /* Azul normal */
    100% { background-color: #135c05; } /* Azul oscuro */
}

.animate-blink {
    animation: blink 1.5s infinite alternate ease-in-out;
}
</style>
