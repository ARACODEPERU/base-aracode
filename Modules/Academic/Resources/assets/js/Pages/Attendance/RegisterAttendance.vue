<script setup>
    import AuthLayout from '@/Layouts/Vristo/AuthLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import { ref, computed, onMounted, onUnmounted } from 'vue';
    import InputError from '@/Components/InputError.vue';

    const props = defineProps({
        error: {
            type: String,
            default: null
        },
        message: {
            type: String,
            default: null
        },
        link: {
            type: Object,
            default: null
        },
        course: {
            type: Object,
            default: null
        },
        content: {
            type: Object,
            default: null
        },
        requiresVerification: {
            type: Boolean,
            default: false
        },
        primaryColor: {
            type: String,
            default: 'blue'
        },
        company: {
            type: Object,
            default: null
        },
        identityDocumentTypes: {
            type: Array,
            default: () => []
        }
    });

    const baseUrl = assetUrl;

    // ============ LOGO DE LA EMPRESA ============
    const companyLogo = computed(() => {
        if (!props.company) return null;
        const isDark = document.documentElement.classList.contains('dark');
        if (isDark && props.company.logo_dark) {
            return props.company.logo_dark === '/img/logo176x32.png'
                ? baseUrl + props.company.logo_dark
                : baseUrl + 'storage/' + props.company.logo_dark;
        }
        if (props.company.logo) {
            return props.company.logo === '/img/logo176x32.png'
                ? baseUrl + props.company.logo
                : baseUrl + 'storage/' + props.company.logo;
        }
        return null;
    });

    const companyName = computed(() => props.company?.name || '');

    // ============ TIPO DE DOCUMENTO ============
    const maxCharacters = computed(() => {
        if (!form.identity_document_type_id) return 8;
        const selected = props.identityDocumentTypes.find(d => d.id == form.identity_document_type_id);
        if (!selected) return 8;
        return parseInt(selected.number_characters) || 8;
    });

    const form = useForm({
        link_code: props.link?.link_code || '',
        identity_document_type_id: null,
        dni: '',
        verification_code: ''
    });

    // ============ COLORES DINÁMICOS ============

    const headerGradientClasses = computed(() => {
        const gradients = {
            blue: ['from-blue-600', 'via-indigo-600', 'to-purple-600'],
            red: ['from-red-600', 'via-rose-600', 'to-pink-600'],
            green: ['from-green-600', 'via-emerald-600', 'to-teal-600'],
            orange: ['from-orange-600', 'via-amber-600', 'to-yellow-600'],
            purple: ['from-purple-600', 'via-violet-600', 'to-indigo-600']
        };
        return gradients[props.primaryColor] || gradients.blue;
    });

    const buttonGradientClasses = computed(() => {
        const gradients = {
            blue: ['from-blue-600', 'via-indigo-600', 'to-purple-600', 'hover:from-blue-700', 'hover:via-indigo-700', 'hover:to-purple-700'],
            red: ['from-red-600', 'via-rose-600', 'to-pink-600', 'hover:from-red-700', 'hover:via-rose-700', 'hover:to-pink-700'],
            green: ['from-green-600', 'via-emerald-600', 'to-teal-600', 'hover:from-green-700', 'hover:via-emerald-700', 'hover:to-teal-700'],
            orange: ['from-orange-600', 'via-amber-600', 'to-yellow-600', 'hover:from-orange-700', 'hover:via-amber-700', 'hover:to-yellow-700'],
            purple: ['from-purple-600', 'via-violet-600', 'to-indigo-600', 'hover:from-purple-700', 'hover:via-violet-700', 'hover:to-indigo-700']
        };
        return gradients[props.primaryColor] || gradients.blue;
    });

    const iconColorClasses = computed(() => {
        const colors = {
            blue: 'text-blue-500',
            red: 'text-red-500',
            green: 'text-green-500',
            orange: 'text-orange-500',
            purple: 'text-purple-500'
        };
        return colors[props.primaryColor] || colors.blue;
    });

    const courseBoxClasses = computed(() => {
        const colors = {
            blue: ['from-blue-50', 'to-indigo-50', 'dark:from-blue-900/20', 'dark:to-indigo-900/20', 'border-blue-100', 'dark:border-blue-800'],
            red: ['from-red-50', 'to-rose-50', 'dark:from-red-900/20', 'dark:to-rose-900/20', 'border-red-100', 'dark:border-red-800'],
            green: ['from-green-50', 'to-emerald-50', 'dark:from-green-900/20', 'dark:to-emerald-900/20', 'border-green-100', 'dark:border-green-800'],
            orange: ['from-orange-50', 'to-amber-50', 'dark:from-orange-900/20', 'dark:to-amber-900/20', 'border-orange-100', 'dark:border-orange-800'],
            purple: ['from-purple-50', 'to-violet-50', 'dark:from-purple-900/20', 'dark:to-violet-900/20', 'border-purple-100', 'dark:border-purple-800']
        };
        return colors[props.primaryColor] || colors.blue;
    });

    const courseIconBgClasses = computed(() => {
        const colors = {
            blue: 'bg-blue-500',
            red: 'bg-red-500',
            green: 'bg-green-500',
            orange: 'bg-orange-500',
            purple: 'bg-purple-500'
        };
        return colors[props.primaryColor] || colors.blue;
    });

    // ============ CRONÓMETRO REGRESIVO ============

    const remainingTime = ref(0);
    const totalTime = ref(0);
    let countdownInterval = null;

    const calculateTimes = () => {
        if (!props.link?.valid_until || !props.link?.valid_from) return { remaining: 0, total: 0 };

        const now = new Date().getTime();
        const validUntil = new Date(props.link.valid_until).getTime();
        const validFrom = new Date(props.link.valid_from).getTime();

        const remaining = Math.max(0, Math.floor((validUntil - now) / 1000));
        const total = Math.floor((validUntil - validFrom) / 1000);

        return { remaining, total };
    };

    const formattedTime = computed(() => {
        const total = remainingTime.value;
        if (total <= 0) return '00:00';

        const hours = Math.floor(total / 3600);
        const minutes = Math.floor((total % 3600) / 60);
        const seconds = total % 60;

        if (hours > 0) {
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    });

    const isExpiringSoon = computed(() => remainingTime.value < 300 && remainingTime.value > 0);

    const isExpired = computed(() => remainingTime.value <= 0);

    // Calcular porcentaje de tiempo restante
    const progressPercentage = computed(() => {
        if (totalTime.value === 0) return 0;
        return Math.min(100, Math.max(0, (remainingTime.value / totalTime.value) * 100));
    });

    // Color de la barra de progreso según el porcentaje
    const progressBarColor = computed(() => {
        const pct = progressPercentage.value;

        if (pct > 50) {
            // 100% - 50%: Azul/Índigo
            return 'bg-gradient-to-r from-blue-500 to-indigo-500';
        } else if (pct > 20) {
            // 50% - 20%: Amarillo/Naranja
            return 'bg-gradient-to-r from-yellow-500 to-orange-500';
        } else {
            // 20% - 0%: Rojo
            return 'bg-red-500';
        }
    });

    onMounted(() => {
        const times = calculateTimes();
        remainingTime.value = times.remaining;
        totalTime.value = times.total;

        countdownInterval = setInterval(() => {
            const times = calculateTimes();
            remainingTime.value = times.remaining;

            if (remainingTime.value <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    });

    onUnmounted(() => {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    });

    // ============ HELPERS ============

    const errorTitle = computed(() => {
        if (props.error === 'expired') return 'Enlace Expirado';
        if (props.error === 'invalid_link') return 'Enlace No Válido';
        return 'Error';
    });

    const errorIcon = computed(() => {
        if (props.error === 'expired') return 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z';
        return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
    });

    const submitForm = () => {
        form.post(route('aca_asistencia_store'));
    };
</script>

<template>
    <AuthLayout title="Registro de Asistencia">
        <div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">

            <div class="w-full max-w-md">

                <!-- Estado de Error -->
                <div v-if="error || isExpired" class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

                    <div class="bg-gradient-to-r from-red-500 to-orange-500 p-8 text-center">
                        <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="errorIcon"/>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-white">{{ isExpired ? 'Tiempo Agotado' : errorTitle }}</h1>
                    </div>

                    <div class="p-8 text-center">
                        <p class="text-gray-600 dark:text-gray-300 mb-6">
                            {{ isExpired ? 'El tiempo para registrar tu asistencia ha terminado. Solicita un nuevo enlace a tu docente.' : message }}
                        </p>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Si crees que esto es un error, contacta a tu docente o administrador.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Formulario Normal -->
                <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

                    <!-- Header con color dinámico -->
                    <div
                        :class="['bg-gradient-to-r', ...headerGradientClasses, 'p-6', 'text-center', 'relative', 'overflow-hidden']"
                    >
                        <div class="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent"></div>
                        <div class="relative">
                            <!-- Logo de la empresa -->
                            <div v-if="companyLogo" class="mb-3">
                                <img :src="companyLogo" :alt="companyName" class="h-10 mx-auto object-contain" />
                            </div>
                            <div v-else class="w-16 h-16 mx-auto mb-3 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h1 class="text-xl font-bold text-white mb-1">Registro de Asistencia</h1>
                            <p class="text-white/80 text-sm">Ingresa tus datos para confirmar</p>
                        </div>
                    </div>

                    <!-- Información del curso con cronómetro -->
                    <div v-if="course" class="px-6 pt-5">
                        <div :class="['bg-gradient-to-r', ...courseBoxClasses, 'rounded-xl', 'p-4', 'border']">
                            <div class="flex items-start gap-3">
                                <div :class="['w-10', 'h-10', 'rounded-lg', courseIconBgClasses, 'flex', 'items-center', 'justify-center', 'flex-shrink-0']">
                                    <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                        <path fill="currentColor" d="M320 216C368.6 216 408 176.6 408 128C408 79.4 368.6 40 320 40C271.4 40 232 79.4 232 128C232 176.6 271.4 216 320 216zM320 514.7L320 365.4C336.3 358.6 352.9 351.7 369.7 344.7C408.7 328.5 450.5 320.1 492.8 320.1L512 320.1L512 480.1L492.8 480.1C433.7 480.1 375.1 491.8 320.5 514.6L320 514.8zM320 296L294.9 285.5C248.1 266 197.9 256 147.2 256L112 256C85.5 256 64 277.5 64 304L64 496C64 522.5 85.5 544 112 544L147.2 544C197.9 544 248.1 554 294.9 573.5L307.7 578.8C315.6 582.1 324.4 582.1 332.3 578.8L345.1 573.5C391.9 554 442.1 544 492.8 544L528 544C554.5 544 576 522.5 576 496L576 304C576 277.5 554.5 256 528 256L492.8 256C442.1 256 391.9 266 345.1 285.5L320 296z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-800 dark:text-white truncate">{{ course.description }}</p>

                                    <!-- Cronómetro -->
                                    <div
                                        class="flex items-center gap-2 mt-2"
                                        :class="isExpiringSoon ? 'text-red-500 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        <div class="flex items-center gap-2">
                                            <span :class="['font-mono', 'font-bold', 'text-sm', isExpiringSoon ? 'animate-pulse' : '']">
                                                {{ formattedTime }}
                                            </span>
                                            <span class="text-xs">restantes</span>
                                        </div>
                                    </div>

                                    <!-- Barra de progreso -->
                                    <div class="mt-2 h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-1000"
                                            :class="progressBarColor"
                                            :style="{ width: `${progressPercentage}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6 space-y-5">
                        <input type="hidden" v-model="form.link_code">

                        <!-- Campo Tipo de Documento -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <span class="flex items-center gap-2">
                                    <svg :class="['w-4 h-4', iconColorClasses]" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                    Tipo de Documento
                                </span>
                            </label>
                            <select
                                v-model="form.identity_document_type_id"
                                class="w-full px-4 py-3 rounded-xl form-select"
                                required
                            >
                                <option value="" disabled>Seleccionar</option>
                                <option value="1">DNI</option>
                                <option v-for="docType in props.identityDocumentTypes" :key="docType.id" :value="docType.id">
                                    {{ docType.description }}
                                </option>
                            </select>
                            <div class="w-full text-center">
                                <InputError :message="form.errors.identity_document_type_id" class="mt-2" />
                            </div>
                        </div>

                        <!-- Campo Número de Documento -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <span class="flex items-center gap-2">
                                    <svg :class="['w-4 h-4', iconColorClasses]" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                    Número de Documento
                                </span>
                            </label>
                            <input
                                type="text"
                                v-model="form.dni"
                                :maxlength="maxCharacters"
                                :pattern="'[0-9]{' + maxCharacters + '}'"
                                inputmode="numeric"
                                class="w-full px-4 py-3 form-input text-lg text-center"
                                placeholder="Ingresa tu número de documento"
                                required
                            >
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">
                                Debes ingresar {{ maxCharacters }} dígitos
                            </p>
                            <div class="w-full text-center">
                                <InputError :message="form.errors.dni" class="mt-2" />
                            </div>
                        </div>

                        <!-- Campo Código de Verificación -->
                        <div v-if="requiresVerification">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <span class="flex items-center gap-2">
                                    <svg :class="['w-4 h-4', iconColorClasses]" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Código de Verificación
                                </span>
                            </label>
                            <input
                                type="text"
                                v-model="form.verification_code"
                                maxlength="50"
                                class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-lg tracking-wider text-center uppercase"
                                placeholder="ABC123"
                                required
                            >
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">
                                Ingresa el código proporcionado por tu docente
                            </p>
                        </div>

                        <!-- Botón Registrar -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            :class="[
                                'w-full', 'py-4', 'px-6',
                                'bg-gradient-to-r',
                                ...buttonGradientClasses,
                                'text-white', 'font-bold', 'rounded-xl',
                                'transition-all', 'duration-200',
                                'shadow-lg', 'hover:shadow-xl',
                                'disabled:opacity-50', 'disabled:cursor-not-allowed',
                                'flex', 'items-center', 'justify-center', 'gap-2'
                            ]"
                        >
                            <svg v-if="form.processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ form.processing ? 'Registrando...' : 'Registrar Asistencia' }}
                        </button>
                    </form>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs text-center text-gray-400 dark:text-gray-500">
                            Sistema de Gestión Académica
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </AuthLayout>
</template>
