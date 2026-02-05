<script  setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import { ref } from 'vue';
    import { Link, useForm, router } from '@inertiajs/vue3';
    import IconSend from '@/Components/vristo/icon/icon-send.vue';
    import IconSquareRotated from '@/Components/vristo/icon/icon-square-rotated.vue';
    import IconTrash from '@/Components/vristo/icon/icon-trash.vue';
    import IconEdit from '@/Components/vristo/icon/icon-edit.vue';
    import IconFilePdf from '@/Components/vristo/icon/icon-file-pdf.vue';
    import IconVideo from '@/Components/vristo/icon/icon-video.vue';
    import IconFile from '@/Components/vristo/icon/icon-file.vue';
    import IconX from '@/Components/vristo/icon/icon-x.vue';
    import InputError from '@/Components/InputError.vue';
    import Swal2 from 'sweetalert2';
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogOverlay } from '@headlessui/vue';

    const props = defineProps({
        course: {
            type: Object,
            default: () => ({}),
        },
        module: {
            type: Object,
            default: () => ({}),
        }
    });

    const treeview1 = ref([]);
    const themeSelected = ref([]);
    const displayModalVideo = ref(false);
    const videoSelected = ref(null);

    const default_theme_id = ref(null);
    const contentsData = ref(null);
    const commentsData = ref(null);

    if(props.module.themes.length > 0){
        default_theme_id.value = props.module.themes[0].id;
        contentsData.value = props.module.themes[0].contents;
        //commentsData = props.module.themes[0].comments;
    }

    const formComment = useForm({
        theme_id:  default_theme_id.value,
        message: null
    });

    const openSelectedVideo = (video) => {
        displayModalVideo.value = true;
        videoSelected.value = modifiedContent(video.content);
        saveStudentHistory(video);
    }

    const closeSelectedVideo = () => {
        displayModalVideo.value = false;
        videoSelected.value = null;
    }

    const newHeight = ref(280);

    const modifiedContent = (content) => {
        // Copia el contenido original
        let modifiedContent = content;

        // Realiza la sustitución de la altura con un valor dinámico
        //modifiedContent = modifiedContent.replace(/height="\d+"/g, `height="${newHeight.value}"`);
        modifiedContent = modifiedContent.replace(/width="\d+"/g, `width="100%"`);
        return modifiedContent;
    };

    const comments = ref([]);
    const commentsLoading = ref(false);

    const getComment = (id) => {
        commentsLoading.value = true;
        axios.get(route('aca_lesson_comments',id)).then((res) => {
            return res.data.comments;
        }).then((data) =>{
            commentsData.value = data
            commentsLoading.value = false
        });
    }

    const createComment = () => {
        formComment.post(route('aca_lesson_comments_store'), {
            errorBag: 'createComment',
            preserveScroll: true,
            onSuccess: () => {
                showMessage('El comentario se registró correctamente.');
                getComment(formComment.theme_id)
                formComment.message = null;
            },
        });
    }

    const activeEditComment = (index) => {
        commentsData.value[index]['edit_status'] = true;
        setTimeout(() => {
            document.getElementById('ctnTextarea' + index).focus();
        }, 0);
    }

    const editComment = (comment, index) => {
        commentsData.value[index]['loading'] = true;
        axios.put(route('aca_lesson_comments_update',comment.id),comment).then((res) => {
            commentsData.value[index]['loading'] = false;
            commentsData.value[index]['edit_status'] = false;
        }).then(() =>{
            showMessage('El comentario se actualizó correctamente.');
        });
    }

    const showMessage = (msg = '', type = 'success') => {
        const toast = Swal2.mixin({
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 3000,
            customClass: { container: 'toast' },
        });
        toast.fire({
            icon: type,
            title: msg,
            padding: '10px 20px',
        });
    };

    const destroyComment = (comment,index) => {
        Swal2.fire({
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            preConfirm: () => {
                return axios.delete(route('aca_lesson_comments_destroy', comment.id)).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message)
                    }
                    return res
                });
            },
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                commentsData.value.splice(index,1);
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                getComment(formComment.theme_id);
            }
        });
    }

    const baseUrl = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    const selectedTab = ref('');

    const selectTheme = (theme) => {
        contentsData.value = theme.contents;
        selectedTab.value = theme.id
        themeSelected.value = theme;
        formComment.theme_id = theme.id
        getComment(theme.id);
    }

    const getPath = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    const saveStudentHistory = (content) => {
        let history = {
            course_id: props.course.id,
            module_id: props.module.id,
            theme_id: content.theme_id,
            content_id: content.id,
            type_content: content.is_file
        }

        axios.post(route('aca_students_history_store'), history);
    }

    const openExamSolve = (content,title = 'Exam', w = 800, h = 600) => {

        const url = route('aca_student_exam_solve',content.id);
        const left = (window.innerWidth / 2) - (w / 2);
        const top = (window.innerHeight / 2) - (h / 2);

        const popup = window.open('', title, `width=${w},height=${h},top=${top},left=${left},scrollbars=yes`);

        if (popup) {
            popup.document.write(`
            <html><head><title>Cargando...</title></head>
            <body style="display:flex;align-items:center;justify-content:center;height:100vh;">
                <div>🔄 Cargando contenido...</div>
            </body></html>
            `);
            popup.document.close();

            // Redirige al contenido real
            popup.location.href = url;
        }
    }

</script>
<template>
    <AppLayout :title="course.description">
        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="javascript:;" class="text-primary hover:underline">Académico</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('aca_mycourses')" class="text-primary hover:underline">Cursos</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('aca_mycourses_lessons',course.id)" class="text-primary hover:underline">{{ course.description }}</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>{{ module.description }}</span>
            </li>
        </ul>
        <div class="pt-5 space-y-8 relative">
            <!-- Header Premium del Módulo -->
            <div class="bg-gradient-to-r from-blue-100 via-indigo-100 to-purple-100 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-2xl p-6 text-center shadow-xl border border-gray-100 dark:border-gray-700">
                <div class="max-w-4xl mx-auto">
                    <!-- Badge y Título -->
                    <div class="inline-flex items-center gap-3 px-6 py-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-full shadow-lg mb-4">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385V4.804zM10.5 4c1.255 0 2.443.29 3.5.804v10A7.968 7.968 0 0014.5 14c-1.255 0-2.443-.29-3.5-.804V4.804z"/>
                        </svg>
                        <span class="font-bold text-lg tracking-wide">TEMAS DEL MÓDULO</span>
                    </div>

                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-3">
                        {{ course.description }}
                    </h1>
                    <p class="text-xl font-semibold text-blue-600 dark:text-blue-400 mb-4">
                        {{ module.description }}
                    </p>


                    <!-- Perfil del Instructor -->
                    <div class="flex justify-center w-full">
                        <template v-if="module.teacher_id">
                            <div class="flex items-center gap-4 bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-xl py-2.5 px-4 shadow-md border border-gray-200 dark:border-gray-600">
                                <div class="shrink-0">
                                    <img v-if="module.teacher.person.image"
                                        class="w-12 h-12 rounded-full border-4 border-white dark:border-gray-800 shadow-lg object-cover"
                                        :src="getImage(module.teacher.person.image)"
                                        alt="Avatar">
                                    <img v-else
                                        :src="`https://ui-avatars.com/api/?name=${module.teacher.person.names}&size=150&rounded=true`"
                                        class="w-12 h-12 rounded-full border-4 border-white dark:border-gray-800 shadow-lg object-cover"
                                        alt="avatar"/>
                                </div>
                                <div class="text-left">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ module.teacher.person.full_name }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ module.teacher.person.presentacion }}
                                    </p>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div v-if="course.teacher" class="flex items-center gap-4 bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-xl py-2 px-4 shadow-md border border-gray-200 dark:border-gray-600">
                                <div class="shrink-0">
                                    <img v-if="course.teacher.person.image"
                                        class="w-12 h-12 rounded-full border-4 border-white dark:border-gray-800 shadow-lg object-cover"
                                        :src="getImage(course.teacher.person.image)"
                                        alt="Avatar">
                                    <img v-else
                                        :src="`https://ui-avatars.com/api/?name=${course.teacher.person.names}&size=150&rounded=true`"
                                        class="w-12 h-12 rounded-full border-4 border-white dark:border-gray-800 shadow-lg object-cover"
                                        alt="avatar"/>
                                </div>
                                <div class="text-left">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ course.teacher.person.full_name }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ course.teacher.person.presentacion }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Estadísticas del Módulo -->
                    <div class="flex flex-wrap justify-center gap-4 mt-4">
                        <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-xl p-3 text-center min-w-[100px] shadow-md border border-gray-200 dark:border-gray-600">
                            <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ module.themes?.length || 0 }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 font-medium">Temas</div>
                        </div>
                        <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-xl p-3 text-center min-w-[100px] shadow-md border border-gray-200 dark:border-gray-600">
                            <div class="text-xl font-bold text-green-600 dark:text-green-400">
                                {{ module.themes?.reduce((sum, t) => sum + (t.contents?.length || 0), 0) || 0 }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 font-medium">Contenidos</div>
                        </div>
                        <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-xl p-3 text-center min-w-[100px] shadow-md border border-gray-200 dark:border-gray-600">
                            <div class="text-xl font-bold text-purple-600 dark:text-purple-400">
                                {{ module.themes?.reduce((sum, t) => sum + (t.contents?.filter(c => c.is_file == 0 || c.is_file == 3).length || 0), 0) || 0 }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 font-medium">Videos</div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="grid grid-cols-6 gap-6">
                <!-- Sidebar de Temas Moderno -->
                <div class="col-span-6 sm:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Header del Sidebar -->
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-500 p-4 text-white">
                            <h2 class="text-lg font-bold tracking-wide flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385V4.804zM10.5 4c1.255 0 2.443.29 3.5.804v10A7.968 7.968 0 0014.5 14c-1.255 0-2.443-.29-3.5-.804V4.804z"/>
                                </svg>
                                Temas del Módulo
                            </h2>
                        </div>

                        <!-- Lista de Temas -->
                        <div class="p-4 space-y-3 max-h-[600px] overflow-y-auto">
                            <template v-for="(theme, index) in module.themes" :key="theme.id">
                                <div @click="selectTheme(theme)"
                                     class="group cursor-pointer bg-gray-50 dark:bg-gray-700 rounded-xl p-4 transition-all duration-300 hover:shadow-md hover:scale-[1.02] border border-transparent hover:border-blue-300 dark:hover:border-blue-500"
                                     :class="selectedTab === theme.id
                                         ? 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-600 dark:to-blue-900 border-blue-500 dark:border-blue-400 shadow-lg scale-[1.02]'
                                         : 'hover:bg-gray-100 dark:hover:bg-gray-600'">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <!-- Icono del Tema -->
                                            <div :class="selectedTab === theme.id
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-green-100 text-green-600 dark:bg-green-900/50 dark:text-green-300'"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <!-- Nombre del Tema -->
                                            <div class="flex-1 min-w-0">
                                                <h3 :class="selectedTab === theme.id
                                                    ? 'text-blue-700 dark:text-blue-300 font-bold'
                                                    : 'text-gray-700 dark:text-gray-300 font-semibold group-hover:text-blue-600 dark:group-hover:text-blue-400'"
                                                    class="text-sm truncate">
                                                    {{ theme.description }}
                                                </h3>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    Tema {{ index + 1 }} del módulo
                                                </p>
                                            </div>
                                        </div>
                                        <!-- Badge de Contenidos -->
                                        <div class="flex flex-col items-end gap-1">
                                            <div :class="selectedTab === theme.id
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300'"
                                                class="px-2 py-1 rounded-full text-xs font-bold min-w-[20px] text-center">
                                                {{ theme.contents?.length || 0 }}
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                contenidos
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Barra de Progreso Visual (si hay contenidos) -->
                                    <div v-if="theme.contents && theme.contents.length > 0" class="mt-3">
                                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                                            <div class="bg-gradient-to-r from-blue-400 to-indigo-500 h-1.5 rounded-full transition-all duration-500"
                                                 style="width: 30%"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Estado Vacío -->
                            <div v-if="!module.themes || module.themes.length === 0" class="text-center py-12">
                                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3h4v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No hay temas disponibles</h3>
                                <p class="text-gray-500 dark:text-gray-400">Este módulo aún no tiene temas asignados</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Panel de Contenidos Moderno -->
                <div class="col-span-6 sm:col-span-4">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Header del Panel -->
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-500 p-4 text-white">
                            <h2 class="text-lg font-bold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385V4.804zM10.5 4c1.255 0 2.443.29 3.5.804v10A7.968 7.968 0 0014.5 14c-1.255 0-2.443-.29-3.5-.804V4.804z"/>
                                </svg>
                                Contenido del Tema
                            </h2>
                            <p class="text-sm text-indigo-100 mt-1">
                                {{ selectedTab ? `Tema seleccionado` : 'Selecciona un tema para ver su contenido' }}
                            </p>
                        </div>

                        <!-- Lista de Contenidos -->
                        <div class="p-6 space-y-4 max-h-[600px] overflow-y-auto">
                            <template v-if="contentsData && contentsData.length > 0">
                                <template v-for="(content, key) in contentsData" :key="content.id">

                                    <!-- Link de Archivo -->
                                    <template v-if="content.is_file == 1">
                                        <div class="group bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl p-5 border border-blue-200 dark:border-blue-800/30 transition-all duration-300 hover:shadow-md hover:scale-[1.01]">
                                            <div class="flex items-center gap-4">
                                                <!-- Icono -->
                                                <div class="bg-blue-500 text-white p-3 rounded-xl group-hover:scale-110 transition-transform shadow-md">
                                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                                    </svg>
                                                </div>

                                                <!-- Información -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <span class="bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 px-2 py-1 rounded-full text-xs font-semibold">
                                                            LINK DE ARCHIVO
                                                        </span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">Contenido externo</span>
                                                    </div>
                                                    <h3 class="text-base font-bold text-gray-900 dark:text-white truncate">
                                                        {{ content.description }}
                                                    </h3>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                        Accede a este recurso externo haciendo clic en el botón
                                                    </p>
                                                </div>

                                                <!-- Botón de Acción -->
                                                <div>
                                                    <a
                                                        :href="content.content"
                                                        @click="saveStudentHistory(content)"
                                                        target="_blank"
                                                        class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white px-4 py-2 rounded-lg font-medium text-sm hover:from-blue-600 hover:to-cyan-600 transition-all duration-300 shadow-md hover:shadow-lg flex items-center gap-2"
                                                    >
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 512 512">
                                                            <path fill="currentColor" d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2l0 82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9l0-107.2c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/>
                                                        </svg>
                                                        Ir al sitio
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <!-- Video Embebido -->
                                    <template v-else-if="content.is_file == 0">
                                        <div class="group bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-5 border border-green-200 dark:border-green-800/30 transition-all duration-300 hover:shadow-md hover:scale-[1.01]">
                                            <div class="flex items-center gap-4">
                                                <!-- Icono -->
                                                <div class="bg-green-500 text-white p-3 rounded-xl group-hover:scale-110 transition-transform shadow-md">
                                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                                    </svg>
                                                </div>

                                                <!-- Información -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <span class="bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 px-2 py-1 rounded-full text-xs font-semibold">
                                                            VIDEO
                                                        </span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">Contenido multimedia</span>
                                                    </div>
                                                    <h3 class="text-base font-bold text-gray-900 dark:text-white truncate">
                                                        {{ content.description }}
                                                    </h3>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                        Reproduce este video directamente en el reproductor
                                                    </p>
                                                </div>

                                                <!-- Botón de Reproducción -->
                                                <div>
                                                    <button @click="openSelectedVideo(content)"
                                                        type="button"
                                                        class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-4 py-2 rounded-lg font-medium text-sm hover:from-green-600 hover:to-emerald-600 transition-all duration-300 shadow-md hover:shadow-lg flex items-center gap-2"
                                                    >
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                                        </svg>
                                                        Reproducir
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <!-- Archivo Descargable -->
                                    <template v-else-if="content.is_file == 2">
                                        <div class="group bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl p-5 border border-yellow-200 dark:border-yellow-800/30 transition-all duration-300 hover:shadow-md hover:scale-[1.01]">
                                            <div class="flex items-center gap-4">
                                                <!-- Icono -->
                                                <div class="bg-yellow-500 text-white p-3 rounded-xl group-hover:scale-110 transition-transform shadow-md">
                                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                                    </svg>
                                                </div>

                                                <!-- Información -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <span class="bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300 px-2 py-1 rounded-full text-xs font-semibold">
                                                            ARCHIVO
                                                        </span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">Documento descargable</span>
                                                    </div>
                                                    <h3 class="text-base font-bold text-gray-900 dark:text-white truncate">
                                                        {{ content.description }}
                                                    </h3>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                        Descarga este archivo para acceder al contenido completo
                                                    </p>
                                                </div>

                                                <!-- Botón de Descarga -->
                                                <div>
                                                    <a
                                                        :href="getPath(content.content)"
                                                        @click="saveStudentHistory(content)"
                                                        target="_blank"
                                                        type="button"
                                                        class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white px-4 py-2 rounded-lg font-medium text-sm hover:from-yellow-600 hover:to-orange-600 transition-all duration-300 shadow-md hover:shadow-lg flex items-center gap-2"
                                                    >
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                        </svg>
                                                        Descargar
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                <!-- Videoconferencia -->
                                    <template v-else-if="content.is_file == 3">
                                        <div class="group bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-5 border border-purple-200 dark:border-purple-800/30 transition-all duration-300 hover:shadow-md hover:scale-[1.01]">
                                            <div class="flex items-center gap-4">
                                                <!-- Icono -->
                                                <div class="bg-purple-500 text-white p-3 rounded-xl group-hover:scale-110 transition-transform shadow-md">
                                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                                    </svg>
                                                </div>

                                                <!-- Información -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <span class="bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 px-2 py-1 rounded-full text-xs font-semibold">
                                                            VIDEOCONFERENCIA
                                                        </span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">Sesión en vivo</span>
                                                    </div>
                                                    <h3 class="text-base font-bold text-gray-900 dark:text-white truncate">
                                                        {{ content.description }}
                                                    </h3>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                        Únete a la videoconferencia en el horario programado
                                                    </p>
                                                </div>

                                                <!-- Botón de Unirse -->
                                                <div>
                                                    <a
                                                        :href="content.content"
                                                        @click="saveStudentHistory(content)"
                                                        target="_blank"
                                                        type="button"
                                                        class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-4 py-2 rounded-lg font-medium text-sm hover:from-purple-600 hover:to-pink-600 transition-all duration-300 shadow-md hover:shadow-lg flex items-center gap-2"
                                                    >
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                                        </svg>
                                                        Unirse
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <!-- Examen -->
                                    <template v-else-if="content.is_file == 4">
                                        <div class="group bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 rounded-xl p-5 border border-red-200 dark:border-red-800/30 transition-all duration-300 hover:shadow-md hover:scale-[1.01]">
                                            <div class="flex items-center gap-4">
                                                <!-- Icono -->
                                                <div class="bg-red-500 text-white p-3 rounded-xl group-hover:scale-110 transition-transform shadow-md">
                                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
                                                    </svg>
                                                </div>

                                                <!-- Información -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <span class="bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 px-2 py-1 rounded-full text-xs font-semibold">
                                                            EXAMEN
                                                        </span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">Evaluación</span>
                                                    </div>
                                                    <h3 class="text-base font-bold text-gray-900 dark:text-white truncate">
                                                        {{ content.description }}
                                                    </h3>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                        Resuelve esta evaluación para poner a prueba tus conocimientos
                                                    </p>
                                                </div>

                                                <!-- Botón de Resolver -->
                                                <div>
                                                    <button
                                                        @click="openExamSolve(content)"
                                                        type="button"
                                                        class="bg-gradient-to-r from-red-500 to-rose-500 text-white px-4 py-2 rounded-lg font-medium text-sm hover:from-red-600 hover:to-rose-600 transition-all duration-300 shadow-md hover:shadow-lg flex items-center gap-2"
                                                    >
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
                                                        </svg>
                                                        Resolver
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                            </template>
                            <!-- Estado Vacío -->
                            <div v-if="!contentsData || contentsData.length === 0" class="text-center py-16">
                                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">No hay contenidos disponibles</h3>
                                <p class="text-gray-500 dark:text-gray-400">Este tema aún no tiene contenidos asignados</p>
                            </div>
                        </div>

                        <!-- Sección de Comentarios Moderna -->
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 border-t border-gray-200 dark:border-gray-600">
                            <div class="p-6">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white p-2 rounded-lg">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">COMENTARIOS</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Discute sobre el contenido del tema</p>
                                    </div>
                                </div>
                                <template v-if="commentsLoading">
                                    <div class="flex items-center mt-4">
                                        <svg class="w-10 h-10 me-3 text-gray-200 dark:text-gray-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                                        </svg>
                                        <div>
                                            <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-32 mb-2"></div>
                                            <div class="w-48 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                                        </div>
                                    </div>
                                </template>
                                <template v-else>
                                    <template v-if="commentsData && commentsData.length > 0">
                                        <div v-for="(comment, ibex) in commentsData" class="mt-8">
                                            <div class="flex align-top">
                                                <div class="shrink-0">
                                                    <img v-if="comment.user.avatar" class="p-1 rounded-full w-14 h-14 ring-2 ring-gray-100/20" :src="getImage(comment.user.avatar)" alt="img">
                                                    <img v-else :src="'https://ui-avatars.com/api/?name='+comment.user.name+'&size=150&rounded=true'" class="p-1 rounded-full w-14 h-14 ring-2 ring-gray-100/20" :alt="comment.user.name"/>
                                                </div>
                                                <div class="ltr:ml-3 rtl:mr-3 grow">
                                                    <small class="text-xs text-gray-500 ltr:float-right rtl:float-left dark:text-gray-300"><i class="uil uil-clock"></i> {{ comment.time_elapsed }}</small>
                                                    <a href="javascript:(0)" class="text-gray-900 transition-all duration-500 ease-in-out hover:bg-violet-500 dark:text-gray-50"><h6 class="mb-0 text-16 mt-sm-0">{{ comment.user.name }}</h6></a>
                                                    <p class="mb-0 text-sm text-gray-500 dark:text-gray-300">{{ comment.created_atx }}</p>

                                                    <div v-show="comment.edit_status">
                                                        <form @submit.prevent="editComment(comment,ibex)" class="mt-2 contact-form">
                                                            <div>
                                                                <textarea v-model="comment.description" :id="'ctnTextarea'+ibex" :ref="'ctnTextarea' + ibex" rows="3" class="form-textarea" placeholder="Escribe aqui..." required></textarea>
                                                            </div>

                                                            <div class="flex justify-end mt-4">
                                                                <button name="submit" type="submit" class="btn btn-danger hover:-translate-y-1" :class="{ 'opacity-25': comment.loading }" :disabled="comment.loading">
                                                                    Editar mensaje
                                                                    <svg v-if="comment.loading" aria-hidden="true" role="status" class="inline w-4 h-4 ml-2 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                                                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                                                    </svg>
                                                                    <icon-send v-else class="ml-2" />
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <p v-if="!comment.edit_status" class="mb-0 italic text-gray-500 dark:text-gray-300">{{ comment.description }}</p>

                                                    <div class="mt-4">
                                                        <ul class="flex space-x-4 rtl:space-x-reverse font-bold">
                                                            <!-- <li>
                                                                <a href="javascript:;" class="flex items-center hover:text-primary">
                                                                <icon-message class="mr-1 w-4 h-4" />
                                                                Responder
                                                                </a>
                                                            </li> -->
                                                            <!-- megusta y no me gusta  -->
                                                            <!-- <li>
                                                                <a href="javascript:;" class="flex items-center hover:text-primary">
                                                                    <font-awesome-icon :icon="faThumbsUp" class="mr-1" />
                                                                    {{ comment.i_like == null ? 0 : comment.i_like }}
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:;" class="flex items-center hover:text-primary">
                                                                    <font-awesome-icon :icon="faThumbsDown" class="mr-1" />
                                                                    {{ comment.not_like == null ? 0 : comment.not_like }}
                                                                </a>
                                                            </li> -->
                                                            <li v-if="$page.props.auth.user.id == comment.user.id">
                                                                <a @click="activeEditComment(ibex)" href="javascript:;" class="flex items-center hover:text-primary">
                                                                    <icon-edit class="mr-1 w-4 h-4" />
                                                                    Editar
                                                                </a>
                                                            </li>
                                                            <li v-if="$page.props.auth.user.id == comment.user.id">
                                                                <a @click="destroyComment(comment,ibex)" href="javascript:;" class="flex items-center hover:text-primary">
                                                                    <icon-trash class="mr-1 w-4 h-4" />
                                                                    Eliminar
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                                <form @submit.prevent="createComment" class="mt-8 contact-form">
                                    <div>
                                        <label for="ctnTextarea">Dejar un comentario</label>
                                        <textarea v-model="formComment.message" id="ctnTextarea" rows="3" class="form-textarea" placeholder="Escribe aqui..." required></textarea>
                                        <InputError :message="formComment.errors.message" class="mt-2" />
                                    </div>

                                    <div class="flex justify-end mt-6">
                                        <button name="submit" type="submit" id="submit" :class="{ 'opacity-25': formComment.processing }" :disabled="formComment.processing" class="btn btn-primary hover:-translate-y-1">
                                            Enviar mensaje
                                            <svg v-if="formComment.processing" aria-hidden="true" role="status" class="inline w-4 h-4 ml-2 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                            </svg>
                                            <icon-send v-else class="ml-2" />
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
         <!-- Modal -->
        <TransitionRoot appear :show="displayModalVideo" as="template">
            <Dialog as="div" @close="closeSelectedVideo" class="relative z-50">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <DialogOverlay class="fixed inset-0 bg-[black]/60" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-start justify-center px-4 py-8">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="relative overflow-hidden w-full max-w-3xl py-8">
                            <button @click="closeSelectedVideo" type="button" class="absolute top-4 ltr:right-4 rtl:left-4 text-gray-400 hover:text-gray-800 dark:hover:text-gray-600 outline-none">
                                <icon-x />
                            </button>
                            <div class="p-5" v-html="videoSelected"></div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
            </Dialog>
        </TransitionRoot>

    </AppLayout>
</template>

