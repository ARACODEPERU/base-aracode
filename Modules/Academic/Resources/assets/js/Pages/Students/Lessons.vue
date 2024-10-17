<script  setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import { ref } from 'vue';
    import VueCollapsible from 'vue-height-collapsible/vue3';
    import { Link, useForm, router } from '@inertiajs/vue3';
    import { faFolderOpen, faNoteSticky, faLink, faVideo, faThumbsUp, faThumbsDown } from "@fortawesome/free-solid-svg-icons";
    import IconSend from '@/Components/vristo/icon/icon-send.vue';

    import IconEdit from '@/Components/vristo/icon/icon-edit.vue';
    import IconTrash from '@/Components/vristo/icon/icon-trash.vue';
    import IconMessage from '@/Components/vristo/icon/icon-message.vue';
    import InputError from '@/Components/InputError.vue';
    import Swal2 from 'sweetalert2';
    import DraggableDiv from '@/Components/DraggableDiv.vue';

    const props = defineProps({
        course: {
            type: Object,
            default: () => ({}),
        }
    });

    const treeview1 = ref([]);
    const themeSelected = ref([]);
    const displayModalVideo = ref(false);
    const videoSelected = ref(null);

    const formComment = useForm({
        theme_id: null,
        message: null
    });

    const toggleTreeview1 = (name, pre, theme = null) => {
        if (pre) {
            themeSelected.value = theme;
            formComment.theme_id = theme.id
            getComment(theme.id);
        }else{
            formComment.theme_id = null
            themeSelected.value = [];
        }

        if (treeview1.value.includes(name)) {
            treeview1.value = treeview1.value.filter((d) => d !== name);
        } else {
            treeview1.value.push(name);
        }
    };

    const openSelectedVideo = (video) => {
        displayModalVideo.value = true;
        videoSelected.value = modifiedContent(video);
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
        modifiedContent = modifiedContent.replace(/height="\d+"/g, `height="${newHeight.value}"`);
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
            comments.value = data
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
        comments.value[index]['edit_status'] = true;
        setTimeout(() => {
            document.getElementById('ctnTextarea' + index).focus();
        }, 0);
    }

    const editComment = (comment, index) => {
        comments.value[index]['loading'] = true;
        axios.put(route('aca_lesson_comments_update',comment.id),comment).then((res) => {
            comments.value[index]['loading'] = false;
            comments.value[index]['edit_status'] = false;
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
                comments.value.splice(index,1);
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
                <span>{{ course.description }}</span>
            </li>
        </ul>
        <div class="pt-5 space-y-5 relative">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 relative">
                <div class="max-w-xl mx-auto text-center">
                    <div class="inline-flex px-4 py-1.5 mx-auto rounded-full  ">
                        <p class="text-4xl font-semibold tracking-widest text-g uppercase">MODULOS DEL CURSO</p>
                    </div>
                    <p class="mt-4 text-base leading-relaxed text-gray-600 group-hover:text-white">{{ course.description }}</p>
                </div>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 mt-6">
                    <template v-for="(module, index) in course.modules">
                        <Link :href="route('aca_mycourses_lesson_themes', module.id)" class="transition-all  duration-1000 bg-white hover:bg-blue-500  hover:shadow-xl m-2 p-4 relative z-40 group  ">
                            <div class=" absolute  bg-blue-500/50 top-0 left-0 w-24 h-1 z-30  transition-all duration-200   group-hover:bg-white group-hover:w-1/2  "></div>
                            <div class="py-2 px-9 relative  ">
                                <svg class="w-10 h-10 fill-gray-400 group-hover:fill-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M88.7 223.8L0 375.8 0 96C0 60.7 28.7 32 64 32l117.5 0c17 0 33.3 6.7 45.3 18.7l26.5 26.5c12 12 28.3 18.7 45.3 18.7L416 96c35.3 0 64 28.7 64 64l0 32-336 0c-22.8 0-43.8 12.1-55.3 31.8zm27.6 16.1C122.1 230 132.6 224 144 224l400 0c11.5 0 22 6.1 27.7 16.1s5.7 22.2-.1 32.1l-112 192C453.9 474 443.4 480 432 480L32 480c-11.5 0-22-6.1-27.7-16.1s-5.7-22.2 .1-32.1l112-192z"/></svg>
                                <p class="mt-4 text-base text-gray-600 group-hover:text-white  ">{{ module.description }}</p>
                            </div>
                        </Link>
                    </template>
                </div>
            </div>
        </div> 
    </AppLayout>
</template>