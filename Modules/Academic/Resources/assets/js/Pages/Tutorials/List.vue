<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ref, nextTick, onMounted, onUnmounted } from 'vue';
    import iconFolderClassic from '@/Components/vristo/icon/icon-folder-classic.vue';
    import iconFolderClassicOpen from '@/Components/vristo/icon/icon-folder-classic-open.vue';
    import { Empty, Drawer, Avatar } from 'ant-design-vue';
    import iconPencil from "@/Components/vristo/icon/icon-pencil.vue";
    import iconTrash from "@/Components/vristo/icon/icon-trash.vue";
    import InputLabel from "@/Components/InputLabel.vue";
    import TextInput from "@/Components/TextInput.vue";
    import { useForm, router } from "@inertiajs/vue3";
    import Swal2 from 'sweetalert2';
    import InputError from "@/Components/InputError.vue";
    import PrimaryButton from "@/Components/PrimaryButton.vue";
    import DangerButton from "@/Components/DangerButton.vue";
    import iconPlus from "@/Components/vristo/icon/icon-plus.vue";
    import iconX from "@/Components/vristo/icon/icon-x.vue";

    const props = defineProps({
        playLists: {
            type: Object,
            default: () => ({})
        }
    });

    const open = ref(false);
    const formModalList = useForm({
        id: null,
        title: null,
        description: null,
        course_id: null,
        keywords: null,
        status: true
    });

    const showDrawer = (playList = null) => {
        activePlayList.value = null;
        playListVideos.value = null;

        if(playList){
            formModalList.id = playList.id;
            formModalList.title = playList.title;
            formModalList.description = playList.description;
            formModalList.course_id = playList.course_id;
            formModalList.keywords = playList.keywords;
            formModalList.status = playList.status == 1 ? true : false;
        }else{
            formModalList.reset();
        }

        open.value = true;
    };
    const onClose = () => {
        open.value = false;
        activePlayList.value = null;
    };

    const savePlaylist = () => {
        formModalList.post(route('aca_tutorials_playlist_store'), {
            forceFormData: true,
            errorBag: 'savePlaylist',
            preserveScroll: true,
            onSuccess: () => {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se registró correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                formModalList.reset();
                open.value = false;
            },
        });
    }

    const activePlayList = ref(null); // Solo un ID seleccionado
    const playListVideos = ref(null);

    const selectPlayList = (playList) => {
        activePlayList.value = activePlayList.value === playList.id ? null : playList.id;
        playListVideos.value = playList;
    };

    const displayModalVideo = ref(false);

    const openModalVideo = (video = {}) => {

        if(playListVideos.value){
            displayModalVideo.value = true;
            formVideo.list_id = playListVideos.value.id
            if(video){
                formVideo.id = video.id;
                formVideo.title = video.title;
                formVideo.video = video.video;
                formVideo.link = video.link == 1 ? true : false;
                formVideo.duration = video.duration;
                formVideo.stars = video.stars;
                formVideo.keywords = JSON.parse(video.keywords);
                formVideo.status = video.status == 1 ? true : false;
            }

        }else{
            showAlert('Debe seleccionar una lista','error')
        }
    };

    const closeModalVideo = () => {
        displayModalVideo.value = false;
        formVideo.reset();
    };

    const formVideo = useForm({
        id: null,
        list_id: null,
        title: null,
        video: null,
        link: false,
        duration: null,
        stars: null,
        keywords: [],
        status: true
    });

    const displayInputTarget = ref(false);
    const inputRefTarget = ref(null);
    const newTarget = ref(null);

    // Función para mostrar el input y enfocarlo
    const showInputTarget = () => {
        displayInputTarget.value = true;

        // Espera a que el DOM se actualice y luego enfoca el input
        nextTick(() => {
            if (inputRefTarget.value) {
                inputRefTarget.value.focus();
            }
        });
    };

    const hideInputTarget = () => {
        displayInputTarget.value = false;
    }

    const addNewTarget = () => {
        formVideo.keywords.push(newTarget.value);
        displayInputTarget.value = false;
        newTarget.value = null;
    }

    const removeTarget = (key) => {
        formVideo.keywords.splice(key,1);
    }

    const showAlert = async (msg, xicon = 'success') => {
        const toast = Swal2.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        toast.fire({
            icon: xicon,
            title: msg,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }

    const saveVideoByList = async () => {
        try {
            formVideo.processing = true;
            const formData = new FormData()
            Object.keys(formVideo.data()).forEach((key) => {
                const value = formVideo[key]

                if (Array.isArray(value)) {
                    value.forEach((item, index) => {
                        formData.append(`${key}[${index}]`, item)
                    })
                } else {
                    formData.append(key, value ?? '')
                }
            })

            const response = await axios.post(route('aca_tutorials_video_store'), formData)

            // Si llegó aquí, el video fue creado con éxito
            const video = response.data.video

            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se registró correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            })



            // Puedes hacer algo con el video si quieres
            if(formVideo.id){
                const index = playListVideos.value.videos.findIndex(v => v.id === formVideo.id)

                if (index !== -1) {
                    // Actualiza el video existente en la misma posición
                    playListVideos.value.videos[index] = video
                } else {
                    // Si por alguna razón no se encontró, lo agregas como nuevo
                    playListVideos.value.videos.push(video)
                }
            }else{
                playListVideos.value.videos.push(video);
            }

            formVideo.reset()
            displayModalVideo.value = false
            formVideo.processing = false;
            formVideo.setError(null);
        } catch (error) {
            if (error.response?.status === 422) {
                const validationErrors = error.response.data.errors
                formVideo.setError(validationErrors)
            } else {
                showAlert('Error inesperado: '+ error , 'error')
            }
            formVideo.processing = false;
        }
    }

    const newHeight = ref('100%');

    const modifiedContent = (content) => {
        // Copia el contenido original
        if(content){
            let modifiedContent = content;
            // Realiza la sustitución de la altura con un valor dinámico
            modifiedContent = modifiedContent.replace(/height="\d+"/g, `height="${newHeight.value}"`);
            modifiedContent = modifiedContent.replace(/width="\d+"/g, `width="100%"`);
            return modifiedContent;
        }else{
            return null;
        }

    };

    const getIframeSrc = (item) => {
        if(video.type == 1){
            return video.iframe
        }else{
            let iframeHtml = video.iframe;
            const match = iframeHtml.match(/src="([^"]+)"/)
            return match ? match[1] : ''
        }
    }

    const destroyVideo = (video, index) =>{
        Swal2.fire({
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonColor: '#d3021b',
            cancelButtonColor: '#0832b5',
            denyButtonColor: '#39ad1d',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            denyButtonText: `Quitar de la lista`,
            showLoaderOnConfirm: true,
            showLoaderOnDeny: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            backdrop: true,
            preConfirm: () => {
                return axios.post(route('aca_tutorials_video_eliminar_actualizar'),{
                    destroy: true,
                    id: video.id
                }).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message)
                    }
                    return res
                });
            },
            preDeny: () => {
                return axios.post(route('aca_tutorials_video_eliminar_actualizar'), {
                    destroy: false,
                    id: video.id
                }).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message);
                    }
                    return res;
                });
            },
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            console.log(result)
            if (result.isConfirmed) {

                playListVideos.value.videos.splice(index,1);
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: result.value.data.message,
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                    backdrop: true,
                });
            }else if(result.isDenied){
                playListVideos.value.videos.splice(index,1);
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: result.value.data.message,
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                    backdrop: true,
                });
            }
        });
    };

    const destroyList = (playList) => {
        Swal2.fire({
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            backdrop: true,
            preConfirm: () => {
                return axios.delete(route('aca_tutorials_playlist_eliminar', playList.id)).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message)
                    }
                    return res
                });
            },
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: result.value.data.message,
                    icon: 'success',
                    backdrop: true,
                });
                router.visit(route('aca_tutorials_playlist'), {
                    replace: false,
                    method: 'get',
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        });
    }
</script>
<template>
    <AppLayout title="Tutoriales">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Lista de reproduccion</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <div class="grid grid-cols-6 gap-6 mb-4">
                <div class="panel col-span-6 sm:col-span-2">
                    <div class="flex items-center justify-end mb-6">
                        <button v-can="'aca_tutoriales_lista_nuevo'" @click="showDrawer()" type="button" class="btn btn-primary text-xs">NUEVO</button>
                    </div>
                    <div class="h-[350px]">
                        <perfect-scrollbar
                                :options="{
                                    swipeEasing: true,
                                    wheelPropagation: false,
                                }"
                                class="relative ltr:pr-3.5 rtl:pl-3.5 ltr:-mr-3.5 rtl:-ml-3.5 h-full grow"
                            >
                            <ul v-if="playLists.length > 0" class="w-full flex flex-col divide-y divide-gray-200 dark:divide-neutral-700">
                                <template v-for="(playList, index) in playLists">
                                    <li class="inline-flex items-center gap-x-2 py-2 px-4 text-sm font-medium "
                                        :class="{ 'bg-yellow-100': activePlayList === playList.id }"
                                    >
                                        <div class="flex justify-between w-full space-x-2">
                                            <div class="flex items-center space-x-2">
                                                <div class="text-primary">
                                                    <iconFolderClassicOpen v-if="activePlayList === playList.id" class="w-4.5 h-4.5" />
                                                    <iconFolderClassic v-else class="w-4 h-4" />
                                                </div>
                                                <div
                                                    class="text-blue-700"
                                                    :class="{ 'font-bold': activePlayList === playList.id }"
                                                >
                                                    <a @click="selectPlayList(playList)" href="#">{{ playList.title }}</a>
                                                </div>
                                            </div>
                                            <div >
                                                <div class="dropdown">
                                                    <Popper :placement="'bottom-end'" offsetDistance="0" class="align-middle">
                                                        <button type="button" class="btn p-0 rounded-none border-0 shadow-none dropdown-toggle text-blue-800 dark:text-white-dark hover:text-primary dark:hover:text-primary">
                                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                                <path fill="currentColor" d="M495.9 166.6c3.2 8.7 .5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6c-4.4 11.9-9.7 23.3-15.8 34.3l-4.7 8.1c-6.6 11-14 21.4-22.1 31.2c-5.9 7.2-15.7 9.6-24.5 6.8l-55.7-17.7c-13.4 10.3-28.2 18.9-44 25.4l-12.5 57.1c-2 9.1-9 16.3-18.2 17.8c-13.8 2.3-28 3.5-42.5 3.5s-28.7-1.2-42.5-3.5c-9.2-1.5-16.2-8.7-18.2-17.8l-12.5-57.1c-15.8-6.5-30.6-15.1-44-25.4L83.1 425.9c-8.8 2.8-18.6 .3-24.5-6.8c-8.1-9.8-15.5-20.2-22.1-31.2l-4.7-8.1c-6.1-11-11.4-22.4-15.8-34.3c-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6c4.4-11.9 9.7-23.3 15.8-34.3l4.7-8.1c6.6-11 14-21.4 22.1-31.2c5.9-7.2 15.7-9.6 24.5-6.8l55.7 17.7c13.4-10.3 28.2-18.9 44-25.4l12.5-57.1c2-9.1 9-16.3 18.2-17.8C227.3 1.2 241.5 0 256 0s28.7 1.2 42.5 3.5c9.2 1.5 16.2 8.7 18.2 17.8l12.5 57.1c15.8 6.5 30.6 15.1 44 25.4l55.7-17.7c8.8-2.8 18.6-.3 24.5 6.8c8.1 9.8 15.5 20.2 22.1 31.2l4.7 8.1c6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z"/>
                                                            </svg>
                                                        </button>
                                                        <template #content="{ close }">
                                                            <ul @click="close()" class="whitespace-nowrap">
                                                                <li v-can="'aca_tutoriales_lista_editar'">
                                                                    <button @click="showDrawer(playList)" type="Button" class="dark:text-white">
                                                                        <icon-pencil class="w-4 h-4 mr-2" />
                                                                        Editar
                                                                    </button>
                                                                </li>
                                                                <li v-can="'aca_tutoriales_lista_eliminar'">
                                                                    <button @click="destroyList(playList)" type="Button" class="text-warning">
                                                                        <icon-trash class="w-4 h-4 mr-2" />
                                                                        Eliminar
                                                                    </button>
                                                                </li>
                                                            </ul>
                                                        </template>
                                                    </Popper>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                            <div v-else class="py-16">
                                <Empty
                                    description="No existen registros"
                                />
                            </div>
                        </perfect-scrollbar>
                    </div>
                </div>

                <div class="panel cols-span-6 sm:col-span-4">
                    <div class="h-[420px]">
                        <div class="p-4 h-full border-2 border-gray-200 border-dashed rounded-lg dark:border-gray-700">
                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <button @click="openModalVideo" type="button" class="flex items-center justify-center h-24 bg-gray-50 dark:bg-gray-800">
                                    <p class="text-2xl text-gray-400 dark:text-gray-500">
                                        <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                        </svg>
                                    </p>
                                </button>
                                <template v-if="playListVideos && playListVideos.videos.length > 0">
                                    <template v-for="(item, ixe) in playListVideos.videos">
                                        <template v-if="item.link">
                                            <div>
                                                <div class="flex items-center justify-center h-24 bg-gray-50 dark:bg-gray-800">
                                                    <iframe
                                                        :src="item.video"
                                                        width="100%"
                                                        :height="newHeight"
                                                        frameborder="0"
                                                        allowfullscreen
                                                        class="w-full"
                                                    ></iframe>
                                                </div>
                                                <ul class="flex items-center justify-center gap-2 mt-2">
                                                    <li>
                                                        <a v-on:click="openModalVideo(item)" href="javascript:;"  v-tippy="{ content: 'Editar', placement: 'left'}" >
                                                            <iconPencil />
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a v-on:click="destroyVideo(item, ixe)" href="javascript:;" v-tippy="{ content: 'Eliminar', placement: 'right'}">
                                                            <iconX />
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <div>
                                                <div v-html="modifiedContent(item.video)" class="flex items-center justify-center h-24 bg-gray-50 dark:bg-gray-800"></div>
                                                <ul class="flex items-center justify-center gap-2 mt-2">
                                                    <li>
                                                        <a v-on:click="openModalVideo(item)" href="javascript:;"  v-tippy="{ content: 'Editar', placement: 'left'}" >
                                                            <iconPencil />
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a v-on:click="destroyVideo(item, ixe)" href="javascript:;" v-tippy="{ content: 'Eliminar', placement: 'right'}">
                                                            <iconX />
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </template>
                                    </template>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Drawer placement="right" :closable="false" v-model:open="open" @close="onClose">
            <div class="w-[80%] mx-auto">
                <h5 v-if="formModalList.id" class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Editar lista de reproduccion
                </h5>
                <h5 v-else class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Nueva lista de reproduccion
                </h5>
            </div>
            <form class="w-[80%] mx-auto">
                <div class="mb-5">
                    <InputLabel for="title" value="Titulo" />
                    <textarea v-model="formModalList.title" id="title" class="form-textarea"></textarea>
                    <InputError :message="formModalList.errors.title" class="mt-2" />
                </div>
                <div class="mb-5">
                    <InputLabel value="Descripción" />
                    <textarea v-model="formModalList.description" class="form-textarea" rows="6"></textarea>
                    <InputError :message="formModalList.errors.description" class="mt-2" />
                </div>
                <!-- <div class="mb-5">
                    <InputLabel value="Curso" />
                    <TextInput  />
                </div> -->
                <div class="flex items-start mb-5">
                    <div class="flex items-center h-5">
                        <input v-model="formModalList.status" id="terms" type="checkbox" class="w-4 h-4 border border-gray-300 rounded-sm bg-gray-50 focus:ring-3 focus:ring-blue-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800" />
                    </div>
                    <label for="terms" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Activo</label>
                </div>
                <div class="flex items-center justify-between">
                    <DangerButton @click="onClose" type="button">Cerrar</DangerButton>
                    <PrimaryButton @click="savePlaylist" :class="{ 'opacity-25': formModalList.processing }" :disabled="formModalList.processing" type="button">
                        <svg v-show="formModalList.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                        </svg>
                        Guardar
                    </PrimaryButton>
                </div>
            </form>
        </Drawer>
        <Drawer id="modalAddVideos" width="40%" placement="right" :closable="false" v-model:open="displayModalVideo" @close="closeModalVideo">
            <div v-if="playListVideos" class="w-[80%] mx-auto">
                <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ playListVideos.title }}
                </h5>
                <form class="w-full mx-auto">
                    <div class="mb-5">
                        <InputLabel for="video-title" value="Titulo" />
                        <textarea v-model="formVideo.title" class="form-textarea" id="video-title"></textarea>
                        <InputError :message="formVideo.errors.title" class="mt-2" />
                    </div>
                    <!-- <div class="mb-5">
                        <InputLabel for="video-description" value="Descripción" />
                        <textarea v-model="formVideo.description" class="form-textarea" id="video-description" rows="6"></textarea>
                        <InputError :message="formVideo.errors.description" class="mt-2" />
                    </div> -->
                    <!-- <div class="mb-5">
                        <label class="inline-flex">
                            <input v-model="formVideo.link" type="checkbox" class="form-checkbox" />
                            <span>enlace del video</span>
                        </label>
                    </div> -->
                    <div v-if="formVideo.link" class="mb-5">
                        <InputLabel for="video-linkvideo" value="Enlace" />
                        <TextInput v-model="formVideo.video" id="video-linkvideo" />
                        <InputError :message="formVideo.errors.video" class="mt-2" />
                    </div>
                    <div v-else class="mb-5">
                        <InputLabel for="video-iframevideo" value="Código iframe" />
                        <textarea v-model="formVideo.video" class="form-textarea" id="video-iframevideo" rows="6"></textarea>
                        <InputError :message="formVideo.errors.video" class="mt-2" />
                    </div>
                    <div class="mb-5">
                        <InputLabel for="video-duration" value="Duración" />
                        <TextInput v-model="formVideo.duration" v-mask="'##:##:##'" id="video-duration" />
                        <InputError :message="formVideo.errors.duration" class="mt-2" />
                    </div>
                    <div class="mb-5">
                        <InputLabel for="video-keywords" value="Palabras clave" />
                        <div v-if="formVideo.keywords.length > 0" class="space-y-2 mb-2">
                            <template v-for="(keyword, key) in formVideo.keywords">
                                <div class="relative">
                                    <input
                                        type="text"
                                        :id="`video-target-${key}`"
                                        disabled
                                        class="form-input placeholder:tracking-wider ltr:pr-11 rtl:pl-11"
                                        :value="keyword"
                                    />
                                    <button @click="removeTarget(key)" type="button" class="absolute text-gray-500 ltr:right-1 rtl:left-1 inset-y-0 m-auto w-9 h-9 p-0 flex items-center justify-center">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                            <path fill="currentColor" d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <div v-if="displayInputTarget" class="relative">
                            <input
                                type="text"
                                id="video-keywords"
                                ref="inputRefTarget"
                                class="form-input placeholder:tracking-wider ltr:pr-11 rtl:pl-11"
                                v-model="newTarget"
                                @keyup.enter="addNewTarget"
                            />
                            <button @click="hideInputTarget" type="button" class="absolute text-gray-500 ltr:right-1 rtl:left-1 inset-y-0 m-auto w-9 h-9 p-0 flex items-center justify-center">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                    <path fill="currentColor" d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/>
                                </svg>
                            </button>
                        </div>
                        <div v-else @click="showInputTarget" class="border flex rounded-md p-2 items-center">
                            <icon-plus class="w-4 h-4 mr-2" />
                            Agregar
                        </div>
                        <InputError :message="formVideo.errors.video" class="mt-2" />
                    </div>
                    <div class="flex items-start mb-5">
                        <div class="flex items-center h-5">
                            <input v-model="formVideo.status" id="video-status" type="checkbox" class="w-4 h-4 border border-gray-300 rounded-sm bg-gray-50 focus:ring-3 focus:ring-blue-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800" />
                        </div>
                        <label for="video-status" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Activo</label>
                    </div>
                    <div class="flex items-center justify-between">
                        <DangerButton @click="closeModalVideo" type="button">Cerrar</DangerButton>
                        <PrimaryButton @click="saveVideoByList" :class="{ 'opacity-25': formVideo.processing }" :disabled="formVideo.processing" type="button">
                            <svg v-show="formVideo.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                            </svg>
                            Guardar
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Drawer>
    </AppLayout>
</template>
