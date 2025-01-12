<script setup>
    import { ref } from 'vue';
    import { message } from 'ant-design-vue';
    import { useForm } from '@inertiajs/vue3';

    const props = defineProps({
        subscription:{
            type: Object,
            default: () => ({}),
        }
    });

    const formLoginRegister = ref(false);

    const changeFormLoginRegister = (showForm) => {
        formLoginRegister.value = showForm;
    }

    const loginForm = useForm({
        email: null,
        password: null
    });

    const loginRegister = useForm({
        names: null,
        numberdni: null,
        apms: null,
        sexo: 'M',
        apps: null,
        email: null,
        password: null
    });

    const loginAction = () => {
        loginForm.put(route('academic_step_account_login',props.subscription.id), {
            preserveScroll: true,
            onSuccess: () => loginForm.reset(),
        });
    }

    const createAction = () => {
        loginRegister.put(route('academic_step_account_create',props.subscription.id), {
            preserveScroll: true,
            onSuccess: () => loginRegister.reset(),
        });
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
                    <li class="flex md:w-full items-center text-blue-600 dark:text-blue-500 sm:after:content-[''] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-6 xl:after:mx-10 dark:after:border-gray-700">
                        <span class="flex items-center after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
                            <svg class="hidden w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                            </svg>
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                <path d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l448 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zm80 256l64 0c44.2 0 80 35.8 80 80c0 8.8-7.2 16-16 16L80 384c-8.8 0-16-7.2-16-16c0-44.2 35.8-80 80-80zm-32-96a64 64 0 1 1 128 0 64 64 0 1 1 -128 0zm256-32l128 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64l128 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64l128 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16s7.2-16 16-16z"/>
                            </svg>
                            Acceso
                        </span>
                    </li>
                    <li class="flex md:w-full items-center after:content-[''] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-6 xl:after:mx-10 dark:after:border-gray-700">
                        <span class="flex items-center after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
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
                    <form v-if="formLoginRegister" @submit.prevent="loginAction"  class="w-[500px] bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                        <h3 class="mb-4 text-lg font-medium leading-none text-gray-900 dark:text-gray-100">Iniciar sesión</h3>
                        <div class="space-y-6">
                            <div>
                                <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-100">Correo electrónico</label>
                                <input v-model="loginForm.email" type="text" name="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" placeholder="username.example" required="">
                                <InputError :message="loginForm.errors.email" class="mt-2" />
                            </div>
                            <div>
                                <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-100">Contraseña</label>
                                <input v-model="loginForm.password" type="password" name="password" id="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5" placeholder="name@company.com" required="">
                                <InputError :message="loginForm.errors.password" class="mt-2" />
                            </div>
                        </div>
                        <div class="flex justify-between mt-6">
                            <button v-on:click="changeFormLoginRegister(false)" type="submit" class="btn btn-danger mr-4">
                                Crear cuenta
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Ingresar
                            </button>
                        </div>
                    </form>
                    <form v-else @submit.prevent="createAction" class="w-[800px] bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
                        <h3 class="mb-4 text-lg font-medium leading-none text-gray-900 dark:text-gray-100">Crear cuenta</h3>
                        <div class="grid gap-4 mb-4 sm:grid-cols-2">
                            <div>
                                <label for="username" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-100">Nombre</label>
                                <input v-model="loginRegister.names" type="text" name="username" id="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required="">
                                <InputError :message="loginRegister.errors.names" class="mt-2" />
                            </div>
                            <div>
                                <label for="apps" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Apellido Paterno</label>
                                <input v-model="loginRegister.apps" type="text" name="apps" id="apps" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required="">
                                <InputError :message="loginRegister.errors.apps" class="mt-2" />
                            </div>
                            <div>
                                <label for="apms" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Apellido Materno</label>
                                <input v-model="loginRegister.apms" type="text" name="apms" id="apms" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required="">
                                <InputError :message="loginRegister.errors.apms" class="mt-2" />
                            </div>
                            <div>
                                <label for="numberdni" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Número de identificación</label>
                                <input v-model="loginRegister.numberdni" type="text" name="numberdni" id="numberdni" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required="">
                                <InputError :message="loginRegister.errors.numberdni" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2 flex  space-x-4">
                                <div class="flex items-center px-4 border border-gray-200 rounded dark:border-gray-700">
                                    <input  v-model="loginRegister.sexo" id="bordered-radio-1" type="radio" value="M" name="bordered-radio" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="bordered-radio-1" class="w-full py-4 ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Hombre</label>
                                </div>
                                <div class="flex items-center px-4 border border-gray-200 rounded dark:border-gray-700">
                                    <input v-model="loginRegister.sexo" id="bordered-radio-2" type="radio" value="F" name="bordered-radio" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="bordered-radio-2" class="w-full py-4 ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Mujer</label>
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-4 mb-4 sm:grid-cols-2">
                            <div>
                                <label for="remail" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-100">Correo electrónico</label>
                                <input v-model="loginRegister.email" type="text" name="remail" id="remail" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required="">
                                <InputError :message="loginRegister.errors.email" class="mt-2" />
                            </div>
                            <div>
                                <label for="rpassword" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-100">Contraseña</label>
                                <input v-model="loginRegister.password" type="password" name="rpassword" id="rpassword" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required="">
                                <InputError :message="loginRegister.errors.password" class="mt-2" />
                            </div>
                        </div>
                        <div class="flex justify-between mt-6">
                            <button v-on:click="changeFormLoginRegister(true)" type="button" class="btn btn-danger mr-4">
                                Iniciar sesión
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Crear
                            </button>
                        </div>
                    </form>
                </div>
                <!-- finstep uno -->
            </div>
            
        </div>
    </div>
</template>



