<script  setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import { ref } from 'vue';
    import VueCollapsible from 'vue-height-collapsible/vue3';
    import { Link, useForm, router } from '@inertiajs/vue3';
    import { faFolderOpen, faNoteSticky, faLink, faVideo, faThumbsUp, faThumbsDown } from "@fortawesome/free-solid-svg-icons";
    import IconSend from '@/Components/vristo/icon/icon-send.vue';

    import iconFile from "@/Components/vristo/icon/icon-file.vue";
    import iconVideo from "@/Components/vristo/icon/icon-video.vue";


    const props = defineProps({
        course: {
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
    <AppLayout :title="course.course.description">
        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="javascript:;" class="text-primary hover:underline">Académico</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('aca_mycourses')" class="text-primary hover:underline">Cursos</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>{{ course.course.description }}</span>
            </li>
        </ul>
        <div class="pt-5 space-y-8 relative">
            <!-- Header Moderno del Curso -->
            <div class="bg-gradient-to-r from-blue-100 via-indigo-100 to-purple-100 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-2xl p-6 text-center shadow-xl border border-gray-100 dark:border-gray-700">
                <div class="max-w-4xl mx-auto">
                    <div class="inline-flex items-center gap-3 px-6 py-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-full shadow-lg mb-4">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385V4.804zM10.5 4c1.255 0 2.443.29 3.5.804v10A7.968 7.968 0 0014.5 14c-1.255 0-2.443-.29-3.5-.804V4.804z"/>
                        </svg>
                        <span class="font-bold text-lg tracking-wide">MÓDULOS DEL CURSO</span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-3">
                        {{ course.course.description }}
                    </h1>
                    <p class="text-base text-gray-600 dark:text-gray-300 max-w-2xl mx-auto mb-4">
                        Explora los módulos disponibles para avanzar en tu aprendizaje
                    </p>

                    <!-- Estadísticas del Curso -->
                    <div class="flex flex-wrap justify-center gap-4">
                        <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-xl p-3 text-center min-w-[100px] shadow-md border border-gray-200 dark:border-gray-600">
                            <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ course.modules?.length || 0 }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 font-medium">Módulos</div>
                        </div>
                        <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-xl p-3 text-center min-w-[100px] shadow-md border border-gray-200 dark:border-gray-600">
                            <div class="text-xl font-bold text-green-600 dark:text-green-400">
                                {{ course.modules?.reduce((sum, m) => sum + (m.total_themes || 0), 0) || 0 }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 font-medium">Clases</div>
                        </div>
                        <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-xl p-3 text-center min-w-[100px] shadow-md border border-gray-200 dark:border-gray-600">
                            <div class="text-xl font-bold text-purple-600 dark:text-purple-400">
                                {{ course.modules?.reduce((sum, m) => sum + (m.total_videos || 0), 0) || 0 }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 font-medium">Videos</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid de Módulos Moderno -->
            <div class="mx-auto max-w-7xl">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                    <template v-for="(module, index) in course.modules" :key="module.id">
                        <Link :href="route('aca_mycourses_lesson_themes', module.id)"
                              class="group relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg transition-all duration-500 hover:shadow-2xl hover:scale-[1.02] overflow-hidden">

                            <!-- Icono del Módulo -->
                            <div class="flex items-start justify-between mb-6">
                                <div class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white p-3 rounded-xl shadow-md group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385V4.804zM10.5 4c1.255 0 2.443.29 3.5.804v10A7.968 7.968 0 0014.5 14c-1.255 0-2.443-.29-3.5-.804V4.804z"/>
                                    </svg>
                                </div>
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Módulo {{ index + 1 }}
                                </div>
                            </div>

                            <!-- Título del Módulo -->
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300">
                                {{ module.description }}
                            </h3>

                            <!-- Separador Visual -->
                            <div class="relative my-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-200 dark:border-gray-600"></div>
                                </div>
                                <div class="relative flex justify-center">
                                    <span class="bg-white dark:bg-gray-800 px-4 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Contenido</span>
                                </div>
                            </div>

                            <!-- Estadísticas del Módulo -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800/30">
                                    <div class="bg-blue-500 text-white text-lg font-bold w-10 h-10 rounded-full mx-auto mb-2 flex items-center justify-center shadow-md">
                                        {{ module.total_themes || 0 }}
                                    </div>
                                    <div class="text-xs font-medium text-blue-700 dark:text-blue-300">Clases</div>
                                </div>

                                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-100 dark:border-green-800/30">
                                    <div class="bg-green-500 text-white text-lg font-bold w-10 h-10 rounded-full mx-auto mb-2 flex items-center justify-center shadow-md">
                                        {{ module.total_files || 0 }}
                                    </div>
                                    <div class="text-xs font-medium text-green-700 dark:text-green-300">Archivos</div>
                                </div>

                                <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-100 dark:border-purple-800/30">
                                    <div class="bg-purple-500 text-white text-lg font-bold w-10 h-10 rounded-full mx-auto mb-2 flex items-center justify-center shadow-md">
                                        {{ module.total_videos || 0 }}
                                    </div>
                                    <div class="text-xs font-medium text-purple-700 dark:text-purple-300">Videos</div>
                                </div>
                            </div>

                            <!-- Indicador de Acceso -->
                            <div class="mt-6 flex items-center justify-center">
                                <span class="text-sm text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300">
                                    Click para explorar →
                                </span>
                            </div>
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
