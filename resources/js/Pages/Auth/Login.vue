<script setup>
    import { computed, reactive, ref, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import appSetting from '@/app-setting';
    import { useAppStore } from '@/stores/index';
    import AuthLayout from '@/Layouts/Vristo/AuthLayout.vue';
    import IconMail from '@/Components/vristo/icon/icon-mail.vue';
    import IconLockDots from '@/Components/vristo/icon/icon-lock-dots.vue';
    import IconInstagram from '@/Components/vristo/icon/icon-instagram.vue';
    import IconFacebookCircle from '@/Components/vristo/icon/icon-facebook-circle.vue';
    import IconTwitter from '@/Components/vristo/icon/icon-twitter.vue';
    import IconGoogle from '@/Components/vristo/icon/icon-google.vue';
    import IconSun from '@/Components/vristo/icon/icon-sun.vue';
    import IconMoon from '@/Components/vristo/icon/icon-moon.vue';
    import { Link, router, useForm, Head, usePage } from '@inertiajs/vue3';
    import Checkbox from '@/Components/vristo/inputs/Checkbox.vue';
    import InputError from '@/Components/InputError.vue';
    import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

    const store = useAppStore();
    const company = usePage().props.company;
    const socialNetworks = usePage().props.socialNetworks;
    // multi language
    const i18n = reactive(useI18n());
    const changeLanguage = (item) => {
        i18n.locale = item.code;
        appSetting.toggleLanguage(item);
    };

    // dark mode toggle function
    const toggleTheme = (checked) => {
        store.toggleTheme(checked ? 'dark' : 'light');
    };

    const baseUrl = assetUrl;

    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = () => {
        form.post(route('login'), {
            onFinish: () => {
                form.reset('password');
                store.clearSidebar();
            },
        });
    }
</script>
<template>
    <AuthLayout>
        <Head title="Acceso" />
        <div class="flex items-center justify-center min-h-screen px-4 py-8" :style="{ background: store.isDarkMode ? 'linear-gradient(to bottom right, #1e293b, #334155, #0f172a)' : 'linear-gradient(to bottom right, #e0f2fe, #dbeafe, #e8eaf6)', transition: 'background 2s ease' }">
            <div class="w-full max-w-4xl">
                <div class="flex flex-col lg:flex-row bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden">
                    <!-- Left Side - Branding -->
                    <div class="lg:w-1/2 bg-gray-50 dark:bg-gray-800 p-8 flex items-center justify-center">
                        <div class="text-center text-gray-800 dark:text-white">
                            <div class="mb-6">
                                <img v-if="company.logo_negative == '/img/logo176x32_negativo.png'" :src="`${baseUrl}/img/logo176x32_negativo.png`" alt="Logo" class="h-16" />
                                <img v-else :src="`${baseUrl}storage/${company.logo}`" alt="Logo" class="h-16" />
                            </div>
                            <h1 class="text-3xl font-bold mb-4">Bienvenido</h1>
                            <p class="text-blue-700 dark:text-blue-300">Ingresa tus credenciales para acceder a tu cuenta</p>
                        </div>
                    </div>

                    <!-- Right Side - Form -->
                     <div class="lg:w-1/2 p-8 relative">
                        <!-- Dark Mode Toggle -->
                        <div class="absolute top-4 right-4 flex items-center space-x-2">
                            <IconSun class="w-5 h-5 text-yellow-500 opacity-100 dark:opacity-0 transition-opacity duration-300" />
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" :checked="store.isDarkMode" @change="toggleTheme($event.target.checked)" />
                                <div class="w-14 h-7 bg-gradient-to-r from-cyan-200 to-blue-300 dark:from-gray-600 dark:to-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-cyan-300 dark:peer-focus:ring-cyan-800 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-1 after:left-1 after:bg-gradient-to-r after:from-yellow-300 after:to-orange-400 after:rounded-full after:h-5 after:w-5 after:transition-all after:duration-500 after:shadow-lg peer-checked:after:from-indigo-400 peer-checked:after:to-purple-500 hover:shadow-xl hover:scale-105 transition-transform duration-200"></div>
                            </label>
                            <IconMoon class="w-5 h-5 text-gray-400 opacity-0 dark:opacity-100 transition-opacity duration-300" />
                        </div>
                        <div class="max-w-md mx-auto">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">Iniciar Sesión</h2>

                            <form class="space-y-4" @submit.prevent="submit" novalidate>
                                <!-- Email -->
                                <div>
                                    <label for="Email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo Electrónico</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <IconMail class="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            v-model="form.email"
                                            id="Email"
                                            type="email"
                                            placeholder="tu@email.com"
                                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                            tabindex="1"
                                            @input="(e) => form.email = e.target.value.trim()"
                                        />
                                    </div>
                                    <InputError class="mt-1 text-sm" :message="form.errors.email" />
                                </div>

                                <!-- Password -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label for="Password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña</label>
                                        <Link :href="route('password.request')" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition">¿Olvidaste tu contraseña?</Link>
                                    </div>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <IconLockDots class="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            v-model="form.password"
                                            id="Password"
                                            type="password"
                                            placeholder="••••••••"
                                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                            tabindex="2"
                                            @input="(e) => form.password = e.target.value.trim()"
                                        />
                                    </div>
                                    <InputError class="mt-1 text-sm" :message="form.errors.password" />
                                </div>

                                <!-- Remember Me -->
                                <div class="flex items-center">
                                    <Checkbox v-model:checked="form.remember" />
                                    <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Recordarme</label>
                                </div>

                                <!-- Submit -->
                                <button
                                    type="submit"
                                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-2 px-4 rounded-lg font-medium hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 transition transform hover:scale-105 shadow-lg"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="inline-flex items-center">
                                        <IconLoader class="w-4 h-4 mr-3" />
                                        Iniciando...
                                    </span>
                                    <span v-else>Iniciar Sesión</span>
                                </button>
                            </form>

                            <!-- Divider -->
                            <div class="mt-6 mb-4 relative">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400">O continúa con</span>
                                </div>
                            </div>

                            <!-- Social -->
                            <ul class="flex justify-center gap-4 mb-6">
                                <li v-for="network in socialNetworks" :key="network.id">
                                    <a v-if="network.route"
                                        :href="network.route"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                                    >
                                        <template v-if="network.id === 'instagram'">
                                            <IconInstagram class="h-5 w-5" />
                                        </template>
                                        <template v-else-if="network.id === 'facebook'">
                                            <IconFacebookCircle class="h-5 w-5" />
                                        </template>
                                        <template v-else-if="network.id === 'x-twiter'">
                                            <IconTwitter class="h-5 w-5" :fill="true" />
                                        </template>
                                        <template v-else-if="network.id === 'youtube'">
                                            <svg class="h-5 w-5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                                <path d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z"/>
                                            </svg>
                                        </template>
                                        <template v-else-if="network.id === 'linkedin'">
                                            <span class="text-xs font-bold">IN</span>
                                        </template>
                                        <template v-else-if="network.id === 'tiktok'">
                                            <span class="text-xs font-bold">TK</span>
                                        </template>
                                        <template v-else>
                                            <span class="text-xs font-bold">{{ network.id.substring(0, 2).toUpperCase() }}</span>
                                        </template>
                                    </a>
                                </li>
                            </ul>

                            <!-- Footer -->
                            <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                                © {{ new Date().getFullYear() }} ARACODE. Todos los derechos reservados.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
