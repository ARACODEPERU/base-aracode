<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import iconUser from '@/Components/vristo/icon/icon-user.vue';
import { useAppStore } from '@/stores/index';

const company = usePage().props.company;
const xassetUrl = assetUrl;

const store = useAppStore();
</script>
<template>
    <!-- Content -->
    <div class="relative h-screen">
        <div class="py-10 lg:py-14">
            <!-- Title -->
            <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto text-center">
                <template v-if="store.theme === 'light'">
                    <Link :href="route('index_main')" class="inline-block mb-4 flex-none focus:outline-none focus:opacity-80" aria-label="Preline">
                        <img v-if="company.logo == '/img/logo176x32.png'" :src="company.logo" class=" h-auto mx-auto" style="width: 250px;">
                        <img v-else :src="`${xassetUrl}storage/${company.logo}` " class="h-auto mx-auto" style="width: 250px;">
                    </Link>
                </template>
                <template v-else>
                    <Link :href="route('index_main')" class="inline-block mb-4 flex-none focus:outline-none focus:opacity-80" aria-label="Preline">
                        <img v-if="company.logo == '/img/logo176x32.png'" :src="company.logo" class=" h-auto mx-auto" style="width: 250px;">
                        <img v-else :src="`${xassetUrl}storage/${company.isotipo_dark}` " class="h-auto mx-auto" style="width: 250px;">
                    </Link>
                </template>
                <h1 class="text-3xl font-bold text-gray-800 sm:text-4xl dark:text-white">
                    Términos y condiciones
                </h1>
                <p class="mt-3 text-gray-600 dark:text-neutral-400">
                    Última actualización: 26 de noviembre de 2024
                </p>
            </div>
            <!-- End Title -->
            <div class="px-4 py-4 sm:px-32 sm:py-8">
                <div class="panel">
                    <div class="mt-16 mx-16">
                        <p class="mb-8 dark:text-white text-lg">
                            Bienvenido a ARACODE S.A.C. ("nosotros", "nuestro" o "la empresa"). Nuestro sitio web (aracodeperu.com) ofrece servicios digitales, incluyendo alquiler de sistemas, facturación electrónica, desarrollo a medida, soporte y mantenimiento de software, marketing digital, diseño gráfico, venta de repuestos de cómputo y desarrollo de aplicaciones móviles. Al acceder o usar nuestro sitio, aceptas estar sujeto a estos términos y condiciones.
                        </p>
                        <p class="mb-8 dark:text-white text-lg">
                            El propósito principal de este sitio es proporcionar información sobre nuestros servicios y productos. También incluye un formulario de contacto donde los usuarios pueden realizar consultas relacionadas con nuestros servicios.
                        </p>
                    </div>

                    <ul class="mt-16 mx-16 space-y-5">
                        <li class="flex gap-x-2 sm:gap-x-4">
                            <svg class="shrink-0 size-[38px] rounded-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM199.4 312.6c-31.2-31.2-31.2-81.9 0-113.1s81.9-31.2 113.1 0c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9c-50-50-131-50-181 0s-50 131 0 181s131 50 181 0c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0c-31.2 31.2-81.9 31.2-113.1 0z"/>
                            </svg>

                            <div class="space-y-3">
                                <h2 class="text-lg text-gray-800 font-bold dark:text-white">
                                    Términos Generales
                                </h2>
                                <div class="space-y-1.5">
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Uso de Nuestros Servicios:
                                    </p>
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Al acceder y realizar consultas a través de nuestro sitio web, confirmas tu aceptación de nuestras políticas. No somos responsables de daños indirectos derivados del uso o la imposibilidad de uso de nuestro sitio. Nos reservamos el derecho de modificar precios y políticas en cualquier momento. En caso de cambios significativos, serás notificado mediante correo electrónico.
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li class="flex gap-x-2 sm:gap-x-4">
                            <svg class="shrink-0 size-[38px] rounded-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                <path fill="currentColor" d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/>
                            </svg>
                            <div class="space-y-3">
                                <h2 class="text-lg text-gray-800 font-bold dark:text-white">
                                    Cuentas de Usuario
                                </h2>
                                <div class="space-y-1.5">
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Responsabilidad de la Cuenta:
                                    </p>
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Para algunos servicios, puede requerirse una cuenta de usuario. Es tu responsabilidad mantener la confidencialidad de tus datos de acceso. Proporcionar información falsa puede resultar en la suspensión o cancelación de tu cuenta.
                                    </p>
                                </div>
                            </div>
                        </li>
                        <!-- End Chat Bubble -->
                        <li class="flex gap-x-2 sm:gap-x-4">
                            <img class="shrink-0 size-[38px]" src="/img/svg/isotipo.svg"/>

                            <div class="space-y-3">
                                <h2 class="text-lg text-gray-800 font-bold dark:text-white">
                                    Productos y Servicios
                                </h2>
                                <div class="space-y-1.5">
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Nuestros productos y servicios se ofrecen de forma digital y/o presencial según sea el caso. En el caso de productos digitales, estos se entregan electrónicamente y estarán disponibles en tu cuenta o mediante comunicación directa con nuestro equipo. No somos responsables por retrasos fuera de nuestro control. En caso de problemas técnicos, puedes comunicarte con nosotros para resolver cualquier inconveniente.
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li class="flex gap-x-2 sm:gap-x-4">
                            <svg class="shrink-0 size-[38px]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path fill="currentColor" d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"/>
                            </svg>

                            <div class="space-y-3">
                                <h2 class="text-lg text-gray-800 font-bold dark:text-white">
                                    Cancelación de Pedidos
                                </h2>
                                <div class="space-y-1.5">
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Nos reservamos el derecho de cancelar pedidos en cualquier momento y por cualquier motivo. Si esto ocurre, te notificaremos y emitiremos un reembolso, si aplica.
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li class="flex gap-x-2 sm:gap-x-4">
                            <svg class="shrink-0 size-[38px]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                <path fill="currentColor" d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"/>
                            </svg>
                            <div class="space-y-3">
                                <h2 class="text-lg text-gray-800 font-bold dark:text-white">
                                    Enlaces a Terceros
                                </h2>
                                <div class="space-y-1.5">
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Sitios Externos:
                                    </p>
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Nuestro sitio web puede contener enlaces a sitios web de terceros. No somos responsables por el contenido, prácticas o políticas de dichos sitios. Recomendamos revisar sus términos y condiciones.
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li class="flex gap-x-2 sm:gap-x-4">
                            <svg class="shrink-0 size-[38px]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path fill="currentColor" d="M256 0c53 0 96 43 96 96l0 3.6c0 15.7-12.7 28.4-28.4 28.4l-135.1 0c-15.7 0-28.4-12.7-28.4-28.4l0-3.6c0-53 43-96 96-96zM41.4 105.4c12.5-12.5 32.8-12.5 45.3 0l64 64c.7 .7 1.3 1.4 1.9 2.1c14.2-7.3 30.4-11.4 47.5-11.4l112 0c17.1 0 33.2 4.1 47.5 11.4c.6-.7 1.2-1.4 1.9-2.1l64-64c12.5-12.5 32.8-12.5 45.3 0s12.5 32.8 0 45.3l-64 64c-.7 .7-1.4 1.3-2.1 1.9c6.2 12 10.1 25.3 11.1 39.5l64.3 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c0 24.6-5.5 47.8-15.4 68.6c2.2 1.3 4.2 2.9 6 4.8l64 64c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0l-63.1-63.1c-24.5 21.8-55.8 36.2-90.3 39.6L272 240c0-8.8-7.2-16-16-16s-16 7.2-16 16l0 239.2c-34.5-3.4-65.8-17.8-90.3-39.6L86.6 502.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l64-64c1.9-1.9 3.9-3.4 6-4.8C101.5 367.8 96 344.6 96 320l-64 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l64.3 0c1.1-14.1 5-27.5 11.1-39.5c-.7-.6-1.4-1.2-2.1-1.9l-64-64c-12.5-12.5-12.5-32.8 0-45.3z"/>
                            </svg>
                            <div class="space-y-3">
                                <h2 class="text-lg text-gray-800 font-bold dark:text-white">
                                    Disponibilidad de Productos y Errores
                                </h2>
                                <div class="space-y-1.5">
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Precisión de la Información:
                                    </p>
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Nos esforzamos por mantener información actualizada sobre nuestros servicios y productos. Sin embargo, pueden ocurrir errores en precios, disponibilidad u otra información. Nos reservamos el derecho de corregir cualquier error sin responsabilidad alguna.
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li class="flex gap-x-2 sm:gap-x-4">
                            <svg class="shrink-0 size-[38px]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                <path fill="currentColor" d="M448 80l0 48c0 44.2-100.3 80-224 80S0 172.2 0 128L0 80C0 35.8 100.3 0 224 0S448 35.8 448 80zM393.2 214.7c20.8-7.4 39.9-16.9 54.8-28.6L448 288c0 44.2-100.3 80-224 80S0 332.2 0 288L0 186.1c14.9 11.8 34 21.2 54.8 28.6C99.7 230.7 159.5 240 224 240s124.3-9.3 169.2-25.3zM0 346.1c14.9 11.8 34 21.2 54.8 28.6C99.7 390.7 159.5 400 224 400s124.3-9.3 169.2-25.3c20.8-7.4 39.9-16.9 54.8-28.6l0 85.9c0 44.2-100.3 80-224 80S0 476.2 0 432l0-85.9z"/>
                            </svg>
                            <div class="space-y-3">
                                <h2 class="text-lg text-gray-800 font-bold dark:text-white">
                                    Derechos sobre los Datos
                                </h2>
                                <div class="space-y-1.5">
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Modificación y Eliminación de Datos:
                                    </p>
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Para más detalles sobre cómo manejamos la modificación, eliminación y protección de tus datos personale, consulta nuestra [Política de Privacidad].
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li class="flex gap-x-2 sm:gap-x-4">
                            <svg class="shrink-0 size-[38px]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path fill="currentColor" d="M362.7 19.3L314.3 67.7 444.3 197.7l48.4-48.4c25-25 25-65.5 0-90.5L453.3 19.3c-25-25-65.5-25-90.5 0zm-71 71L58.6 323.5c-10.4 10.4-18 23.3-22.2 37.4L1 481.2C-1.5 489.7 .8 498.8 7 505s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L421.7 220.3 291.7 90.3z"/>
                            </svg>
                            <div class="space-y-3">
                                <h2 class="text-lg text-gray-800 font-bold dark:text-white">
                                    Cambios en los Términos
                                </h2>
                                <div class="space-y-1.5">
                                    <p class="mb-1.5 text-sm font-bold text-gray-800 dark:text-white">
                                        Actualizaciones:
                                    </p>
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Podemos actualizar estos términos en cualquier momento. Recomendamos revisar esta página periódicamente.
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <p class="mb-1.5 text-sm font-bold text-gray-800 dark:text-white">
                                        Límite de Responsabilidad:
                                    </p>
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        No somos responsables por pérdida de datos, ganancias perdidas o daños derivados del uso de nuestro sitio.
                                    </p>
                                </div>
                            </div>
                        </li>
                        <!-- <li class="flex gap-x-2 sm:gap-x-4">
                            <svg class="shrink-0 size-[38px] rounded-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                <path fill="currentColor" d="M96 128a128 128 0 1 0 256 0A128 128 0 1 0 96 128zm94.5 200.2l18.6 31L175.8 483.1l-36-146.9c-2-8.1-9.8-13.4-17.9-11.3C51.9 342.4 0 405.8 0 481.3c0 17 13.8 30.7 30.7 30.7l131.7 0c0 0 0 0 .1 0l5.5 0 112 0 5.5 0c0 0 0 0 .1 0l131.7 0c17 0 30.7-13.8 30.7-30.7c0-75.5-51.9-138.9-121.9-156.4c-8.1-2-15.9 3.3-17.9 11.3l-36 146.9L238.9 359.2l18.6-31c6.4-10.7-1.3-24.2-13.7-24.2L224 304l-19.7 0c-12.4 0-20.1 13.6-13.7 24.2z"/>
                            </svg>

                            <div class="space-y-3">
                                <h2 class="text-lg text-gray-800 font-bold dark:text-white">
                                    Ley Aplicable y Jurisdicción
                                </h2>
                                <div class="space-y-1.5">
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Marco Legal:
                                    </p>
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Estos términos se rigen por las leyes vigentes en el Perú. Cualquier disputa relacionada con estos términos estará sujeta a la jurisdicción exclusiva de los tribunales competentes según la normativa aplicable.
                                    </p>
                                </div>
                            </div>
                        </li> -->
                        <li class="flex gap-x-2 sm:gap-x-4">
                            <svg class="shrink-0 size-[38px]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path fill="currentColor" d="M64 112c-8.8 0-16 7.2-16 16l0 22.1L220.5 291.7c20.7 17 50.4 17 71.1 0L464 150.1l0-22.1c0-8.8-7.2-16-16-16L64 112zM48 212.2L48 384c0 8.8 7.2 16 16 16l384 0c8.8 0 16-7.2 16-16l0-171.8L322 328.8c-38.4 31.5-93.7 31.5-132 0L48 212.2zM0 128C0 92.7 28.7 64 64 64l384 0c35.3 0 64 28.7 64 64l0 256c0 35.3-28.7 64-64 64L64 448c-35.3 0-64-28.7-64-64L0 128z"/>
                            </svg>
                            <div class="space-y-3">
                                <h2 class="text-lg text-gray-800 font-bold dark:text-white">
                                    Información de Contacto
                                </h2>
                                <div class="space-y-1.5">
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Consultas:
                                    </p>
                                    <p class="mb-1.5 text-lg text-gray-800 dark:text-white">
                                        Si tienes preguntas sobre estos términos, puedes contactarnos a través del formulario de contacto en nuestro sitio web o mediante nuestros canales oficiales.
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <div class="mt-6 sm:mt-16 items-center flex justify-center">
                        <Link :href="route('index_main')" class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">Regresar</Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>