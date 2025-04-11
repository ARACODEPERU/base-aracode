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
    <AppLayout title="Mis Cursos">
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
        <div class="pt-5 space-y-5 relative">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 relative">
                <div class="max-w-xl mx-auto text-center">
                    <div class="inline-flex px-4 py-1.5 mx-auto rounded-full  ">
                        <p class="text-4xl font-semibold tracking-widest text-g uppercase">MODULOS DEL CURSO</p>
                    </div>
                    <p class="mt-4 text-base leading-relaxed text-gray-600 group-hover:text-white">{{ course.course.description }}</p>
                </div>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 mt-6">
                    <template v-for="(module, index) in course.modules">
                        <Link :href="route('aca_mycourses_lesson_themes', module.id)" class="panel rounded-xl rounded-tl-none transition-all  duration-1000 hover:bg-blue-50  hover:shadow-xl m-2 p-4 relative z-40 group  dark:bg-gray-800">
                            <div class="absolute  bg-blue-500/50 top-0 left-0 w-24 h-1 z-30  transition-all duration-200   group-hover:bg-blue-800 group-hover:w-1/2 dark:group-hover:bg-gray-300 "></div>
                            <div class="py-2 px-9 relative">
                                <svg class="w-10 h-10 fill-gray-400 group-hover:fill-blue-800" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M88.7 223.8L0 375.8 0 96C0 60.7 28.7 32 64 32l117.5 0c17 0 33.3 6.7 45.3 18.7l26.5 26.5c12 12 28.3 18.7 45.3 18.7L416 96c35.3 0 64 28.7 64 64l0 32-336 0c-22.8 0-43.8 12.1-55.3 31.8zm27.6 16.1C122.1 230 132.6 224 144 224l400 0c11.5 0 22 6.1 27.7 16.1s5.7 22.2-.1 32.1l-112 192C453.9 474 443.4 480 432 480L32 480c-11.5 0-22-6.1-27.7-16.1s-5.7-22.2 .1-32.1l112-192z"/></svg>
                                <p class="mt-4 text-base text-gray-600 group-hover:text-blue-800  ">{{ module.description }}</p>
                            </div>
                            <div
                                class="
                                    relative
                                    flex
                                    justify-between
                                    mt-6
                                    pt-4
                                    before:w-[250px] before:h-[1px] before:bg-[#e0e6ed] before:inset-x-0 before:top-0 before:absolute before:mx-auto
                                    dark:before:bg-[#1b2e4b]
                                "
                                >
                                <div class="flex items-center font-semibold">
                                    <div class="w-9 h-9 rounded-full overflow-hidden inline-block ltr:mr-2 rtl:ml-2.5">
                                        <span class="flex w-full h-full items-center justify-center bg-[#515365] text-[#e0e6ed]">{{ module.total_themes }}</span>
                                    </div>
                                    <div class="text-[#515365] dark:text-white-dark">Clases</div>
                                </div>
                                <div class="flex font-semibold">
                                    <div class="text-gray-600 flex items-center ltr:mr-3 rtl:ml-3 ">
                                        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                            <path fill="currentColor" d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 288c0 35.3-28.7 64-64 64L64 512c-35.3 0-64-28.7-64-64L0 64zm384 64l-128 0L256 0 384 128z"/>
                                        </svg>
                                        {{ module.total_files }}
                                    </div>
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                            <path fill="currentColor" d="M256 0L576 0c35.3 0 64 28.7 64 64l0 224c0 35.3-28.7 64-64 64l-320 0c-35.3 0-64-28.7-64-64l0-224c0-35.3 28.7-64 64-64zM476 106.7C471.5 100 464 96 456 96s-15.5 4-20 10.7l-56 84L362.7 169c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6l80 0 48 0 144 0c8.9 0 17-4.9 21.2-12.7s3.7-17.3-1.2-24.6l-96-144zM336 96a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zM64 128l96 0 0 256 0 32c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-32 160 0 0 64c0 35.3-28.7 64-64 64L64 512c-35.3 0-64-28.7-64-64L0 192c0-35.3 28.7-64 64-64zm8 64c-8.8 0-16 7.2-16 16l0 16c0 8.8 7.2 16 16 16l16 0c8.8 0 16-7.2 16-16l0-16c0-8.8-7.2-16-16-16l-16 0zm0 104c-8.8 0-16 7.2-16 16l0 16c0 8.8 7.2 16 16 16l16 0c8.8 0 16-7.2 16-16l0-16c0-8.8-7.2-16-16-16l-16 0zm0 104c-8.8 0-16 7.2-16 16l0 16c0 8.8 7.2 16 16 16l16 0c8.8 0 16-7.2 16-16l0-16c0-8.8-7.2-16-16-16l-16 0zm336 16l0 16c0 8.8 7.2 16 16 16l16 0c8.8 0 16-7.2 16-16l0-16c0-8.8-7.2-16-16-16l-16 0c-8.8 0-16 7.2-16 16z"/>
                                        </svg>
                                        {{ module.total_videos }}
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
