<script setup>
    import { ref } from 'vue';
    import { message } from 'ant-design-vue';
    import { useForm, Link } from '@inertiajs/vue3';

    const props = defineProps({
        subscription:{
            type: Object,
            default: () => ({}),
        }
    });

    const baseUrl = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }
</script>
<template>
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <!-- Title -->
        <div class="max-w-2xl mx-auto text-center">
            <h2 class="text-2xl font-bold md:text-4xl md:leading-tight dark:text-white">Formulario de pago</h2>
            <p class="mt-1 text-gray-600 dark:text-neutral-400">pago seguro en aracode.</p>
        </div>
        <!-- End Title -->
        <div class="flex flex-wrap justify-center gap-6">
            <div class="flex flex-col rounded-xl p-8 w-[800px]">
                <ol class="flex items-center w-full text-sm font-medium text-center text-gray-500 dark:text-gray-400 sm:text-base">
                    <li class="flex md:w-full items-center text-yellow-500 dark:text-yellow-600 sm:after:content-[''] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-6 xl:after:mx-10 dark:after:border-gray-700">
                        <span class="flex items-center after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                            </svg>
                            Acceso
                        </span>
                    </li>
                    <li class="flex md:w-full items-center after:content-[''] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-6 xl:after:mx-10 dark:after:border-gray-700">
                        <span class="flex items-center text-blue-600 dark:text-blue-500 after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                <path d="M192 0c-41.8 0-77.4 26.7-90.5 64L64 64C28.7 64 0 92.7 0 128L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64l-37.5 0C269.4 26.7 233.8 0 192 0zm0 64a32 32 0 1 1 0 64 32 32 0 1 1 0-64zM305 273L177 401c-9.4 9.4-24.6 9.4-33.9 0L79 337c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L271 239c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/>
                            </svg>
                            Verificación
                        </span>
                    </li>
                    <li class="flex md:w-full items-center after:content-[''] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-6 xl:after:mx-10 dark:after:border-gray-700">
                        <span class="flex items-center after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                <path d="M64 32C28.7 32 0 60.7 0 96l0 32 576 0 0-32c0-35.3-28.7-64-64-64L64 32zM576 224L0 224 0 416c0 35.3 28.7 64 64 64l448 0c35.3 0 64-28.7 64-64l0-192zM112 352l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm112 16c0-8.8 7.2-16 16-16l128 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16z"/>
                            </svg>
                            Pagar
                        </span>
                    </li>
                    <li class="flex items-center">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM164.1 325.5C182 346.2 212.6 368 256 368s74-21.8 91.9-42.5c5.8-6.7 15.9-7.4 22.6-1.6s7.4 15.9 1.6 22.6C349.8 372.1 311.1 400 256 400s-93.8-27.9-116.1-53.5c-5.8-6.7-5.1-16.8 1.6-22.6s16.8-5.1 22.6 1.6zM144.4 208a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm192-32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/>
                        </svg>
                        Hecho
                    </li>
                </ol>
                <!-- step uno -->
                <div class="flex justify-center p-8">
                    <div class="w-full p-4 text-center bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex flex-col items-center pb-4">
                            <img v-if="$page.props.auth.user.avatar" class="w-24 h-24 mb-3 rounded-full shadow-lg" :src="getImage($page.props.auth.user.avatar)" alt="Bonnie image"/>
                            <img v-else class="w-24 h-24 mb-3 rounded-full shadow-lg" :src="`https://ui-avatars.com/api/?name=${$page.props.auth.user.name}&size=150&rounded=true`" alt="Bonnie image"/>
                        </div> 
                        <h5 class="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $page.props.auth.user.name }}
                        </h5>
                        <p class="mb-5 text-base text-gray-500 sm:text-lg dark:text-gray-400">Por favor, verifique que los datos de su cuenta sean correctos antes de proceder con los siguientes pasos del proceso de suscripción. Asegúrese de que su información esté actualizada para evitar inconvenientes. Si necesita realizar algún cambio, hágalo antes de continuar. ¡Gracias por su confianza en nosotros!</p>
                        <div class="items-center justify-center space-y-4 sm:flex sm:space-y-0 sm:space-x-4 rtl:space-x-reverse">
                            <a href="#" class="btn btn-danger">
                                <svg class="me-3 w-7 h-7" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path d="M288 32c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 224c0 17.7 14.3 32 32 32s32-14.3 32-32l0-224zM143.5 120.6c13.6-11.3 15.4-31.5 4.1-45.1s-31.5-15.4-45.1-4.1C49.7 115.4 16 181.8 16 256c0 132.5 107.5 240 240 240s240-107.5 240-240c0-74.2-33.8-140.6-86.6-184.6c-13.6-11.3-33.8-9.4-45.1 4.1s-9.4 33.8 4.1 45.1c38.9 32.3 63.5 81 63.5 135.4c0 97.2-78.8 176-176 176s-176-78.8-176-176c0-54.4 24.7-103.1 63.5-135.4z"/>
                                </svg>
                                <div class="text-left rtl:text-right">
                                    <div class="mb-1 text-xs">Cerrar sesion</div>
                                    <div class="-mt-1 font-sans text-sm font-semibold">Regresar al login</div>
                                </div>
                            </a>
                            <Link :href="route('academic_step_pay', subscription.id)" class="btn btn-primary">
                                <svg class="me-3 w-7 h-7" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path d="M502.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-128-128c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l370.7 0-73.4 73.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l128-128z"/>
                                </svg>
                                <div class="text-left rtl:text-right">
                                    <div class="mb-1 text-xs">Continuar</div>
                                    <div class="-mt-1 font-sans text-sm font-semibold">Realizar pago</div>
                                </div>
                            </Link>
                        </div>
                    </div>

                </div>
                <!-- finstep uno -->
            </div>
            
        </div>
    </div>
</template>



