<script setup>

    import { ref, onMounted, computed, watch } from 'vue';
    import { Popover } from 'ant-design-vue';

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
                    <ol class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template v-for="(per, hey) in birthdays">
                            <li>
                                <a href="#"
                                    class="items-center block p-3 sm:flex hover:bg-gray-100 dark:hover:bg-gray-700"
                                    :class="{
                                        'animate-blink ': per.status === 'hoy',
                                    }"
                                >
                                    <img v-if="per.image" :src="getImage(per.image)" alt="" class="w-12 h-12 mb-3 me-3 rounded-lg sm:mb-0" />
                                    <img v-else :src="`https://ui-avatars.com/api/?name=${per.name}&size=150`" alt="" class="w-12 h-12 mb-3 me-3 rounded-lg sm:mb-0" />
                                    <div class="text-gray-600 dark:text-gray-400">
                                        <div class="font-normal text-gray-900 dark:text-white">
                                            {{ per.status == 'pasado' ? 'El '+ per.birthdate + ' fue el' :  per.status == 'hoy' ? 'Hoy '+per.birthdate+' es el ' : 'El próximo '+per.birthdate+' es el ' }} cumpleaños de
                                            <span class="font-medium text-gray-900 dark:text-blue-700">{{ per.name }}</span>
                                        </div>
                                        <Popover title="Información de contacto">
                                            <template #content>
                                                <ul class="space-y-3 font-semibold selectable-content">
                                                    <li>
                                                        <svg class="inline w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                            <path fill="currentColor" d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48L48 64zM0 176L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-208L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/>
                                                        </svg>
                                                        <span class="list-text">{{ per.email }}</span>
                                                    </li>
                                                    <li>
                                                        <svg class="inline w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                            <path fill="currentColor" d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z"/>
                                                        </svg>
                                                        <span class="list-text">{{ per.telephone }}</span>
                                                    </li>
                                                </ul>
                                            </template>
                                            <button v-if="per.status == 'hoy'" class="mt-2 w-full inline-flex items-center justify-center font-normal text-[#ec1b2b] bg-[#e6eee4] px-2 py-1">
                                                <img src="/img/24px/pastel-de-cumpleanos.png" />
                                                <img src="/img/24px/globos.png" />
                                                <img src="/img/24px/papel-picado.png" />
                                                <img src="/img/24px/victoria.png" />
                                                <img src="/img/24px/guirnalda.png" />
                                                <img src="/img/24px/champan.png" />
                                                <img src="/img/24px/regalo.png" />
                                            </button>
                                        </Popover>
                                    </div>
                                </a>
                            </li>
                        </template>
                    </ol>

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
    0% { background-color: #f4faf2; } /* Azul oscuro */
    50% { background-color: #e7faf4; } /* Azul normal */
    100% { background-color: #eff5f1; } /* Azul oscuro */
}

.animate-blink {
    /* animation: blink 1.5s infinite alternate ease-in-out; */
}

</style>
