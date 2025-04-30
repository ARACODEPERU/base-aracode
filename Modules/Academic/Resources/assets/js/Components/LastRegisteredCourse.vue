<script setup>
    import { Link } from '@inertiajs/vue3';

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
        return Math.round(percent); // Opcional: redondear el porcentaje
    };
</script>
<template>
    <div
        v-if="lastCourse"
        :class="isBirthday ? 'grid grid-cols-6 gap-4': ''"
    >
        <div v-if="isBirthday" class="panel col-span-6 sm:col-span-2">
            <div class="flex items-center justify-center mb-4">
               <div class="rounded-full bg-gray-100 border p-4">
                    <img src="/img/24px/pastel-de-cumpleanos.png" >
               </div>
            </div>
            <div>
                <h5 class="mt-2 mb-2 text-2xl text-center font-bold tracking-tight text-gray-900 dark:text-white">¡Feliz cumpleaños!</h5>
                <p class="mb-3 text-center font-normal text-gray-700 dark:text-gray-400">Te deseamos un día lleno de alegría y un nuevo año de vida lleno de éxitos. ¡Gracias por ser parte de nuestra comunidad!</p>
                <button class="w-full inline-flex items-center justify-center font-normal text-[#ec1b2b] bg-[#e6eee4] px-2 py-1">
                    <img src="/img/24px/globos.png" />
                    <img src="/img/24px/papel-picado.png" />
                    <img src="/img/24px/victoria.png" />
                    <img src="/img/24px/guirnalda.png" />
                    <img src="/img/24px/champan.png" />
                    <img src="/img/24px/regalo.png" />
                </button>
            </div>
        </div>
        <div :class="isBirthday ? 'col-span-6 sm:col-span-4': ''">
            <div class="panel p-0">
                <div class="flex items-center justify-between">
                    <div class="h-full w-[250px] py-10 px-4 dark:text-[#cccccc] rounded-l-lg">
                        <p class="text-[11px] tracking-widest ">{{ lastCourse.course.modality.description }}</p>
                        <img :src="`${urlBasek}/storage/${lastCourse.course.image}`" class="mt-6" />
                        <h4 class="text-[12px] pt-[30px] cursor-pointer">
                            {{ lastCourse.course.sector_description }}
                        </h4>
                    </div>
                    <div class="p-4 w-full rounded-r-xl">
                        <div>
                            <div class="flex justify-between">
                                <h1 class="text-[#949494] text-[13px] tracking-[.5px]">
                                    {{ lastCourse.course.type_description }}
                                </h1>
                                <div class="relative">
                                    <div class="h-1.5 w-[200px] bg-slate-200 rounded-xl">
                                        <div class="h-1.5 bg-[#261a6b] rounded-xl" :style="{ width: getProgress(lastCourse) + '%' }" ></div>
                                    </div>
                                    <p
                                        class="text-[#a8a8a8] text-[12px] tracking-[.5px] absolute right-0"
                                    >
                                    {{ lastCourse.total_activity }}/{{ lastCourse.total_contents }} Desafíos
                                    </p>
                                </div>
                            </div>
                            <h1 class="text-[28px] pt-2 font-[500] tracking-wide break-words whitespace-normal leading-relaxed">
                                {{ lastCourse.course.description }}
                            </h1>
                        </div>
                        <div class="flex items-center justify-end">
                            <Link :href="route('aca_mycourses_lessons', lastCourse.course.id)"
                                class="btn btn-primary w-[120px] rounded-3xl"
                            >
                                Continuar
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
