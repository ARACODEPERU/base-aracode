<script setup>
    import { Link } from '@inertiajs/vue3';
    import IconStarFiller from '@/Components/vristo/icon/icon-star-filler.vue';
    import IconUserGraduate from '@/Components/vristo/icon/icon-user-graduate.vue';
import IconArrowRight from '@/Components/vristo/icon/icon-arrow-right.vue';

    defineProps({
        lastCourse: {
            type: Object,
            default: () => ({}),
        },
        urlBasek: {
            type: String,
            default: null
        },
        isBirthday: {
            type: Boolean,
            default: false
        }
    });

    const getProgress = (regis) => {
        if (!regis.total_contents || regis.total_contents === 0) return 0;
        const percent = (regis.total_activity / regis.total_contents) * 100;
        return Math.round(percent);
    };

    const getProgressColor = (percent) => {
        if (percent >= 75) return 'bg-green-500';
        if (percent >= 50) return 'bg-yellow-500';
        return 'bg-primary';
    };
</script>

<template>
    <div
        v-if="lastCourse"
        :class="isBirthday ? 'grid grid-cols-1 md:grid-cols-6 gap-6': ''"
    >
        <!-- Birthday Panel -->
        <div v-if="isBirthday" class="col-span-1 md:col-span-2">
            <div class="panel dark:bg-[#1b2e4b] p-6 rounded-2xl text-center border-2 border-yellow-400 dark:border-yellow-500">
                <div class="flex justify-center mb-4">
                    <div class="relative">
                        <div class="w-20 h-20 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                            <span class="text-4xl">🎂</span>
                        </div>
                        <div class="absolute -top-2 -right-2 animate-bounce text-2xl">🎉</div>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-yellow-600 dark:text-yellow-400 mb-2">¡Feliz Cumpleaños!</h3>
                <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                    Te desean un día lleno de alegría y un nuevo año de vida lleno de éxitos.
                </p>
                <div class="flex justify-center gap-3 text-2xl">
                    <span>🎈</span>
                    <span>🎊</span>
                    <span>🏆</span>
                    <span>🥳</span>
                    <span>🎁</span>
                </div>
            </div>
        </div>

        <!-- Course Card -->
        <div :class="isBirthday ? 'col-span-1 md:col-span-4': 'w-full'">
            <div class="panel dark:bg-[#1b2e4b] rounded-2xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-300 border border-gray-100 dark:border-gray-700">
                <div class="flex flex-col md:flex-row">
                    <!-- Course Image Section -->
                    <div class="relative w-full md:w-56 h-48 md:h-auto flex-shrink-0 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900">
                        <img
                            v-if="lastCourse.course.image"
                            :src="`${urlBasek}/storage/${lastCourse.course.image}`"
                            :alt="lastCourse.course.description"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                            <IconUserGraduate class="w-16 h-16" />
                        </div>

                        <!-- Popular Badge -->
                        <div
                            v-if="lastCourse.is_popular"
                            class="absolute top-3 left-3 bg-orange-500 text-white text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1 shadow-md"
                        >
                            <IconStarFiller class="w-3 h-3 text-yellow-200" />
                            <span>Más Popular</span>
                        </div>

                        <!-- Modality Badge -->
                        <div
                            v-if="lastCourse.course.modality?.description"
                            class="absolute bottom-3 right-3 bg-black/60 text-white text-xs px-2 py-1 rounded backdrop-blur-sm"
                        >
                            {{ lastCourse.course.modality.description }}
                        </div>
                    </div>

                    <!-- Course Info Section -->
                    <div class="flex-1 p-5 md:p-6">
                        <!-- Course Type & Category -->
                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ lastCourse.course.type_description || 'Curso' }}
                            </span>
                            <span class="text-gray-300 dark:text-gray-600">•</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ lastCourse.course.sector_description }}
                            </span>
                        </div>

                        <!-- Course Title -->
                        <h2 class="text-xl md:text-2xl font-semibold text-gray-800 dark:text-white mb-4 line-clamp-2">
                            {{ lastCourse.course.description }}
                        </h2>

                        <!-- Progress Section -->
                        <div class="mb-5">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Progreso</span>
                                <span
                                    class="text-sm font-bold"
                                    :class="getProgress(lastCourse) >= 100 ? 'text-green-500' : 'text-gray-700 dark:text-gray-300'"
                                >
                                    {{ getProgress(lastCourse) }}%
                                </span>
                            </div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500 ease-out"
                                    :class="getProgressColor(getProgress(lastCourse))"
                                    :style="{ width: getProgress(lastCourse) + '%' }"
                                ></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                {{ lastCourse.total_activity || 0 }} de {{ lastCourse.total_contents || 0 }} actividades completadas
                            </p>
                        </div>

                        <!-- Action Button -->
                        <div class="flex justify-end">
                            <Link
                                :href="route('aca_mycourses_lessons', lastCourse.course.id)"
                                class="group flex items-center gap-2 bg-primary hover:bg-primary/90 text-white px-5 py-2.5 rounded-xl font-medium transition-all duration-200 hover:shadow-lg hover:shadow-primary/30"
                            >
                                <span>{{ getProgress(lastCourse) >= 100 ? 'Revisar' : 'Continuar' }}</span>
                                <span class="group-hover:translate-x-1 transition-transform text-lg">
                                    <IconArrowRight class="w-4 h-4" />
                                </span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
