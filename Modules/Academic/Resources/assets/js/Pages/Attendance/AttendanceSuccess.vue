<script setup>
    import AuthLayout from '@/Layouts/Vristo/AuthLayout.vue';
    import { computed } from 'vue';

    const props = defineProps({
        course: {
            type: String,
            default: null
        },
        content: {
            type: String,
            default: null
        },
        registeredAt: {
            type: String,
            default: null
        },
        primaryColor: {
            type: String,
            default: 'blue'
        }
    });

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

    const iconBgClasses = computed(() => {
        const colors = {
            blue: 'bg-blue-500',
            red: 'bg-red-500',
            green: 'bg-green-500',
            orange: 'bg-orange-500',
            purple: 'bg-purple-500'
        };
        return colors[props.primaryColor] || colors.blue;
    });

    const infoBoxClasses = computed(() => {
        const colors = {
            blue: ['from-blue-50', 'to-indigo-50', 'dark:from-blue-900/20', 'dark:to-indigo-900/20', 'border-blue-100', 'dark:border-blue-800'],
            red: ['from-red-50', 'to-rose-50', 'dark:from-red-900/20', 'dark:to-rose-900/20', 'border-red-100', 'dark:border-red-800'],
            green: ['from-green-50', 'to-emerald-50', 'dark:from-green-900/20', 'dark:to-emerald-900/20', 'border-green-100', 'dark:border-green-800'],
            orange: ['from-orange-50', 'to-amber-50', 'dark:from-orange-900/20', 'dark:to-amber-900/20', 'border-orange-100', 'dark:border-orange-800'],
            purple: ['from-purple-50', 'to-violet-50', 'dark:from-purple-900/20', 'dark:to-violet-900/20', 'border-purple-100', 'dark:border-purple-800']
        };
        return colors[props.primaryColor] || colors.blue;
    });
</script>

<template>
    <AuthLayout title="Asistencia Registrada">
        <div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">

            <div class="w-full max-w-md">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

                    <!-- Header con animación de éxito -->
                    <div :class="['bg-gradient-to-r', ...headerGradientClasses, 'p-8', 'text-center', 'relative', 'overflow-hidden']">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent"></div>
                        <div class="relative">
                            <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <h1 class="text-2xl font-bold text-white mb-1">Asistencia Registrada</h1>
                            <p class="text-white/80 text-sm">Tu asistencia ha sido registrada correctamente</p>
                        </div>
                    </div>

                    <!-- Información del registro -->
                    <div class="p-6 space-y-4">
                        <div :class="['bg-gradient-to-r', ...infoBoxClasses, 'rounded-xl', 'p-4', 'border']">
                            <div class="space-y-3">
                                <div v-if="course" class="flex items-center gap-3">
                                    <div :class="['w-8', 'h-8', 'rounded-lg', iconBgClasses, 'flex', 'items-center', 'justify-center', 'flex-shrink-0']">
                                        <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                            <path fill="currentColor" d="M320 216C368.6 216 408 176.6 408 128C408 79.4 368.6 40 320 40C271.4 40 232 79.4 232 128C232 176.6 271.4 216 320 216zM320 514.7L320 365.4C336.3 358.6 352.9 351.7 369.7 344.7C408.7 328.5 450.5 320.1 492.8 320.1L512 320.1L512 480.1L492.8 480.1C433.7 480.1 375.1 491.8 320.5 514.6L320 514.8zM320 296L294.9 285.5C248.1 266 197.9 256 147.2 256L112 256C85.5 256 64 277.5 64 304L64 496C64 522.5 85.5 544 112 544L147.2 544C197.9 544 248.1 554 294.9 573.5L307.7 578.8C315.6 582.1 324.4 582.1 332.3 578.8L345.1 573.5C391.9 554 442.1 544 492.8 544L528 544C554.5 544 576 522.5 576 496L576 304C576 277.5 554.5 256 528 256L492.8 256C442.1 256 391.9 266 345.1 285.5L320 296z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Curso</p>
                                        <p class="font-semibold text-gray-800 dark:text-white truncate">{{ course }}</p>
                                    </div>
                                </div>

                                <div v-if="content" class="flex items-center gap-3">
                                    <div :class="['w-8', 'h-8', 'rounded-lg', iconBgClasses, 'flex', 'items-center', 'justify-center', 'flex-shrink-0']">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Clase</p>
                                        <p class="font-semibold text-gray-800 dark:text-white truncate">{{ content }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div :class="['w-8', 'h-8', 'rounded-lg', iconBgClasses, 'flex', 'items-center', 'justify-center', 'flex-shrink-0']">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Hora de registro</p>
                                        <p class="font-semibold text-gray-800 dark:text-white">{{ registeredAt }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-green-700 dark:text-green-300">
                                    Tu asistencia ha sido registrada exitosamente. No necesitas realizar ninguna acci&oacute;n adicional.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs text-center text-gray-400 dark:text-gray-500">
                            Sistema de Gesti&oacute;n Acad&eacute;mica
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
