<script setup>
    import { Link } from '@inertiajs/vue3';
    import { Popover, Empty } from 'ant-design-vue';
    import { onMounted, ref } from 'vue';

    const studentsSubscription= ref(null);
    const displayDataList = ref(false);

    const getSubscriptionsExpiredExpiring = () => {
        axios.post(route('aca_subscriptions_expired_expiring')).then((res) => {
            studentsSubscription.value = res.data;
        }).finally(() => {
            displayDataList.value = true;
        });
    }

    onMounted(() => {
        getSubscriptionsExpiredExpiring();
    });

    const simpleImage = Empty.PRESENTED_IMAGE_SIMPLE;
</script>
<template>
    <div class="space-y-5">
        <div class="w-full flex flex-col bg-white border border-gray-200 border-t-4 border-t-blue-600 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-700 dark:border-t-blue-500 dark:shadow-neutral-700/70">
            <div class="p-4 md:p-5">
                <div class="flex items-start justify-between dark:text-white-light mb-5 -mx-5 p-5 pt-0">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white uppercase">
                        Suscripciones vencidas y por vencer
                    </h3>
                </div>
                <div v-if="displayDataList" class="mt-2 text-gray-500 dark:text-neutral-400">
                    <template v-if="studentsSubscription.length > 0">
                        <perfect-scrollbar
                            :options="{
                                swipeEasing: true,
                                wheelPropagation: false,
                            }"
                            class="relative max-h-[380px]"
                        >
                            <ol class="divide-y divide-gray-200 dark:divide-gray-700">
                                <li v-for="expiring in studentsSubscription">

                                    <div class="flex justify-between w-full px-4 md:px-5 py-3">
                                        <Popover title="Información de contacto" placement="right">
                                            <a href="#" class="flex gap-4">
                                                <svg class="w-4 h-5 animate-point-right" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                                    <path fill="currentColor" d="M566.6 342.6C579.1 330.1 579.1 309.8 566.6 297.3L406.6 137.3C394.1 124.8 373.8 124.8 361.3 137.3C348.8 149.8 348.8 170.1 361.3 182.6L466.7 288L96 288C78.3 288 64 302.3 64 320C64 337.7 78.3 352 96 352L466.7 352L361.3 457.4C348.8 469.9 348.8 490.2 361.3 502.7C373.8 515.2 394.1 515.2 406.6 502.7L566.6 342.7z"/>
                                                </svg>
                                                {{ expiring.student.person.full_name }}
                                            </a>
                                            <template #content>
                                                <ul class="space-y-3 font-semibold selectable-content">
                                                    <li>
                                                        <svg class="inline w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                            <path fill="currentColor" d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48L48 64zM0 176L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-208L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/>
                                                        </svg>
                                                        <span class="list-text">{{ expiring.student.person.email }}</span>
                                                    </li>
                                                    <li>
                                                        <svg class="inline w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                            <path fill="currentColor" d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z"/>
                                                        </svg>
                                                        <span class="list-text">{{ expiring.student.person.telephone }}</span>
                                                    </li>
                                                </ul>
                                            </template>
                                        </Popover>
                                        <span
                                            class="inline-flex items-center py-1 px-2 rounded-full text-xs font-medium text-white"
                                            :class="[
                                                {'bg-red-500' : expiring.number_days < 0},
                                                {'bg-yellow-500' : expiring.number_days == 0},
                                                {'bg-blue-500' : expiring.number_days > 0}
                                            ]"
                                        >{{ expiring.expiration_message }}</span>
                                    </div>


                                </li>
                            </ol>
                        </perfect-scrollbar>
                    </template>
                    <div v-else >
                        <Empty
                            :image="simpleImage"
                            description="No existen registros"
                            :image-style="{
                                height: '50px',
                            }"
                        />
                    </div>
                </div>
                <div v-else class="space-y-4">
                    <div  class="flex items-center">
                        <div class="shrink-0">
                            <span class="size-6 block bg-gray-200 rounded-full dark:bg-neutral-700"></span>
                        </div>
                        <div class="flex ms-4 w-full items-center">
                            <div class="flex items-center w-full max-w-[480px]">
                                <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-full"></div>
                                <div class="h-2.5 ms-2 bg-gray-200 rounded-full dark:bg-gray-700 w-80"></div>
                            </div>
                            <div class="h-2.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-24"></div>
                        </div>
                    </div>
                    <div  class="flex items-center">
                        <div class="shrink-0">
                            <span class="size-6 block bg-gray-200 rounded-full dark:bg-neutral-700"></span>
                        </div>
                        <div class="flex ms-4 w-full items-center">
                            <div class="flex items-center w-full max-w-[480px]">
                                <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-full"></div>
                                <div class="h-2.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-full"></div>
                            </div>
                            <div class="h-2.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-24"></div>
                        </div>
                    </div>
                    <!-- <div  class="flex items-center">
                        <div class="shrink-0">
                            <span class="size-6 block bg-gray-200 rounded-full dark:bg-neutral-700"></span>
                        </div>
                        <div class="flex ms-4 w-full items-center">
                            <div class="flex items-center w-full max-w-[480px]">
                                <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-full"></div>
                                <div class="h-2.5 ms-2 bg-gray-200 rounded-full dark:bg-gray-700 w-80"></div>
                                <div class="h-2.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-full"></div>
                            </div>
                            <div class="h-2.5 ms-2 bg-gray-300 rounded-full dark:bg-gray-600 w-24"></div>
                        </div>
                    </div> -->
                </div>
                <div v-can="'aca_reportes_estado_susc_estudiantes'" class="flex justify-end mt-8">
                    <Link :href="route('aca_subscriptions_expired_student')" class="mt-3 inline-flex items-center gap-x-1 text-sm font-semibold rounded-lg border border-transparent text-blue-600 decoration-2 hover:text-blue-700 hover:underline focus:underline focus:outline-hidden focus:text-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-600 dark:focus:text-blue-600" href="#">
                        ver reporte detallado
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
