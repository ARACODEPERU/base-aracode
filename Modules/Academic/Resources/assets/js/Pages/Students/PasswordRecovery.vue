<script setup>
import GuestLayout from '@/Layouts/Vristo/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import IconMail from '@/Components/vristo/icon/icon-mail.vue';
import IconLockDots from '@/Components/vristo/icon/icon-lock-dots.vue';
import { useAppStore } from '@/stores/index';

const props = defineProps({
    personId: {
        type: Number,
        required: true,
    },
    resetUrl: {
        type: String,
        required: true,
    },
});

const form = useForm({
    email: '',
    number: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(props.resetUrl, {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const baseUrl = assetUrl;
const company = usePage().props.company;
const store = useAppStore();
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar contraseña" />

        <div>
            <div class="absolute inset-0">
                <img :src="`${baseUrl}/themes/vristo/images/auth/bg-gradient.png`" alt="image" class="h-full w-full object-cover" />
            </div>
            <div
                class="relative flex min-h-screen items-center justify-center bg-cover bg-center bg-no-repeat px-6 py-10 dark:bg-[#060818] sm:px-16"
                :style="`background-image:url('${baseUrl}/themes/vristo/images/auth/map.png');`"
            >
                <img :src="`${baseUrl}/themes/vristo/images/auth/coming-soon-object1.png`" alt="image" class="absolute left-0 top-1/2 h-full max-h-[893px] -translate-y-1/2" />
                <img :src="`${baseUrl}/themes/vristo/images/auth/coming-soon-object2.png`" alt="image" class="absolute left-24 top-0 h-40 md:left-[30%]" />
                <img :src="`${baseUrl}/themes/vristo/images/auth/coming-soon-object3.png`" alt="image" class="absolute right-0 top-0 h-[300px]" />
                <img :src="`${baseUrl}/themes/vristo/images/auth/polygon-object.svg`" alt="image" class="absolute bottom-0 end-[28%]" />
                <div
                    class="relative flex w-full max-w-[1502px] flex-col justify-between overflow-hidden rounded-md bg-white/60 backdrop-blur-lg dark:bg-black/50 lg:min-h-[758px] lg:flex-row lg:gap-10 xl:gap-0"
                >
                    <div
                        class="relative hidden w-full items-center justify-center bg-[linear-gradient(225deg,rgba(153,4,12,1)_0%,rgba(227,6,19,1)_100%)] p-5 lg:inline-flex lg:max-w-[835px] xl:-ms-28 ltr:xl:skew-x-[14deg] rtl:xl:skew-x-[-14deg]"
                    >
                        <div
                            class="absolute inset-y-0 w-8 from-primary/10 via-transparent to-transparent ltr:-right-10 ltr:bg-gradient-to-r rtl:-left-10 rtl:bg-gradient-to-l xl:w-16 ltr:xl:-right-20 rtl:xl:-left-20"
                        ></div>
                        <div class="ltr:xl:-skew-x-[14deg] rtl:xl:skew-x-[14deg]">
                            <Link :href="route('index_main')" class="w-48 block lg:w-72 ms-10">
                                <img v-if="company.logo_negative == '/img/logo176x32_negativo.png'" :src="company.logo_negative" alt="Logo" class="w-full" />
                                <img v-else :src="`${baseUrl}storage/${company.logo_negative}`" alt="Logo" class="w-full" />
                            </Link>
                            <div class="mt-24 hidden w-full max-w-[430px] lg:block">
                                <img :src="`${baseUrl}/themes/vristo/images/auth/reset-password.svg`" alt="Cover Image" class="w-full" />
                            </div>
                        </div>
                    </div>
                    <div class="relative flex w-full flex-col items-center justify-center gap-6 px-4 pb-16 pt-6 sm:px-6 lg:max-w-[667px]">
                        <div class="flex w-full max-w-[440px] items-center gap-2 lg:absolute lg:end-6 lg:top-6 lg:max-w-full">
                            <Link :href="route('index_main')" class="w-8 block lg:hidden">
                                <template v-if="store.theme === 'light'">
                                    <img v-if="company.isotipo == '/img/isotipo.png'" :src="`${baseUrl}/img/isotipo.png`" alt="Logo" class="mx-auto w-10" />
                                    <img v-else :src="`${baseUrl}storage/${company.isotipo}`" alt="Logo" class="mx-auto w-10" />
                                </template>
                                <template v-if="store.theme === 'dark'">
                                    <img v-if="company.isotipo_negative == '/img/isotipo_negativo.png'" :src="`${baseUrl}/img/isotipo_negativo.png`" alt="Logo" class="mx-auto w-10" />
                                    <img v-else :src="`${baseUrl}storage/${company.isotipo_negative}`" alt="Logo" class="mx-auto w-10" />
                                </template>
                            </Link>
                        </div>
                        <div class="w-full max-w-[440px] lg:mt-16">
                            <div class="mb-7">
                                <h1 class="mb-3 text-2xl font-bold !leading-snug dark:text-white">Recuperar contraseña</h1>
                                <p>Confirma tu correo y numero de identificacion para crear una nueva contraseña.</p>
                            </div>
                            <form class="space-y-5" @submit.prevent="submit" novalidate>
                                <div>
                                    <label for="email">Email</label>
                                    <div class="relative text-white-dark">
                                        <TextInput
                                            id="email"
                                            type="email"
                                            class="form-input pl-10 placeholder:text-white-dark"
                                            v-model="form.email"
                                            required
                                            autofocus
                                            placeholder="Introduce tu correo"
                                        />
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2">
                                            <icon-mail :fill="true" />
                                        </span>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.email" />
                                </div>
                                <div>
                                    <label for="number">Num. de identificacion</label>
                                    <div class="relative text-white-dark">
                                        <TextInput
                                            id="number"
                                            type="text"
                                            class="form-input pl-10 placeholder:text-white-dark"
                                            v-model="form.number"
                                            required
                                            placeholder="Introduce tu num. de identificacion"
                                        />
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L7 17H4v-3L1.257 11.257A6 6 0 1118 8zm-6-2a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.number" />
                                </div>
                                <div>
                                    <label for="password">Nueva contraseña</label>
                                    <div class="relative text-white-dark">
                                        <TextInput
                                            id="password"
                                            type="password"
                                            class="ps-10 placeholder:text-white-dark"
                                            v-model="form.password"
                                            required
                                            placeholder="Introduce tu nueva contraseña"
                                        />
                                        <span class="absolute start-4 top-1/2 -translate-y-1/2">
                                            <icon-lock-dots :fill="true" />
                                        </span>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.password" />
                                </div>
                                <div>
                                    <label for="password_confirmation">Repetir contraseña</label>
                                    <div class="relative text-white-dark">
                                        <TextInput
                                            id="password_confirmation"
                                            type="password"
                                            class="ps-10 placeholder:text-white-dark"
                                            v-model="form.password_confirmation"
                                            required
                                            placeholder="Repite tu nueva contraseña"
                                        />
                                        <span class="absolute start-4 top-1/2 -translate-y-1/2">
                                            <icon-lock-dots :fill="true" />
                                        </span>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                                </div>
                                <button type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="btn btn-gradient !mt-6 w-full border-0 uppercase shadow-[0_10px_20px_-10px_rgba(67,97,238,0.44)]">
                                    Actualizar contraseña
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
