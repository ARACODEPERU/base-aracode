<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { useForm, Link, router } from "@inertiajs/vue3";
    import iconX from "@/Components/vristo/icon/icon-x.vue";
    import iconPencil from "@/Components/vristo/icon/icon-pencil.vue";
    import Swal2 from 'sweetalert2';
    import { Empty, Drawer } from 'ant-design-vue';
    import { ref, nextTick } from 'vue';
    import InputLabel from "@/Components/InputLabel.vue";
    import TextInput from "@/Components/TextInput.vue";
    import InputError from "@/Components/InputError.vue";
    import PrimaryButton from "@/Components/PrimaryButton.vue";
    import DangerButton from "@/Components/DangerButton.vue";
    import iconPlus from "@/Components/vristo/icon/icon-plus.vue";

    const props = defineProps({
        videos: {
            type: Object,
            default: () => ({}),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        search: props.filters.search,
    });

    const modifiedContent = (content) => {
        // Copia el contenido original
        if(content){
            let modifiedContent = content;
            // Realiza la sustitución de la altura con un valor dinámico
            modifiedContent = modifiedContent.replace(/height="\d+"/g, `height="100%"`);
            modifiedContent = modifiedContent.replace(/width="\d+"/g, `width="100%"`);
            return modifiedContent;
        }else{
            return null;
        }

    };

    const destroyVideo = (video) => {
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
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: result.value.data.message,
                    icon: 'success',
                    backdrop: true,
                });
                router.visit(route('aca_tutorials_videos_list'), {
                    replace: false,
                    method: 'get',
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        });
    }

    const displayModalVideo = ref(false);

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

    const openModalVideo = (video = {}) => {
        if(video){
            console.log('aca llega')
            formVideo.list_id = video.list_id
            formVideo.id = video.id;
            formVideo.title = video.title;
            formVideo.video = video.video;
            formVideo.link = video.link == 1 ? true : false;
            formVideo.duration = video.duration;
            formVideo.stars = video.stars;
            formVideo.keywords = JSON.parse(video.keywords);
            formVideo.status = video.status == 1 ? true : false;
        }
        displayModalVideo.value = true;
    };

    const closeModalVideo = () => {
        displayModalVideo.value = false;
        formVideo.reset();
    };

    const saveVideo = async () => {
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

            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se registró correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            })

            router.visit(route('aca_tutorials_videos_list'), {
                replace: false,
                method: 'get',
                preserveState: true,
                preserveScroll: true,
                onSuccess: page => {
                    formVideo.reset();
                    formVideo.setError(null);
                    displayModalVideo.value = false;
                },
            });

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

    const displayInputTarget = ref(false);
    const inputRefTarget = ref(null);
    const newTarget = ref(null);

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
</script>
<template>
    <AppLayout title="Tutoriales">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Lista de videos</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Videos</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <div>
                            <button @click="openModalVideo(null)" type="button" class="btn btn-primary">
                                <icon-plus class="ltr:mr-2 rtl:ml-2" />
                                Nuevo
                            </button>
                        </div>
                    </div>

                    <div class="relative">
                        <input
                            type="text"
                            placeholder="Buscar"
                            class="form-input py-2 ltr:pr-11 rtl:pl-11 peer"
                            v-model="form.search"
                            @keyup.enter="form.get(route('aca_courses_list'))"
                        />
                        <div class="absolute ltr:right-[11px] rtl:left-[11px] top-1/2 -translate-y-1/2 peer-focus:text-primary">
                            <icon-search class="mx-auto" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-5">
                <div class="grid grid-cols-3 gap-4">
                    <template v-for="(item, index) in videos.data">
                        <div class="w-full bg-white shadow-[4px_6px_10px_-3px_#bfc9d4] border border-[#e0e6ed] dark:border-[#1b2e4b] dark:bg-[#191e3a] dark:shadow-none">
                            <div class="py-7 px-6">
                                <div class="-mt-7 mb-2 -mx-6">
                                    <div class="h-auto max-w-full rounded-lg">
                                        <template v-if="item.link">
                                            <iframe
                                                :src="item.video"
                                                width="100%"
                                                height="100%"
                                                frameborder="0"
                                                allowfullscreen
                                                class="w-full"
                                            ></iframe>
                                        </template>
                                        <template v-else>
                                            <div v-html="modifiedContent(item.video)"></div>
                                        </template>
                                    </div>
                                </div>
                                <h5 class="text-[#3b3f5c] text-[15px] font-bold mb-4 dark:text-white-light">{{ item.title }}</h5>
                                <div class="
                                        relative
                                        flex
                                        justify-between
                                        mt-6
                                        pt-4
                                        before:w-full before:h-[1px] before:bg-[#e0e6ed] before:inset-x-0 before:top-0 before:absolute before:mx-auto
                                        dark:before:bg-[#1b2e4b]
                                    "
                                >
                                    <div class="flex items-center font-semibold">
                                        <div class="w-5 h-5 rounded-full overflow-hidden inline-block ltr:mr-2 rtl:ml-2.5">
                                            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                <path fill="currentColor" d="M256 0a256 256 0 1 1 0 512A256 256 0 1 1 256 0zM232 120l0 136c0 8 4 15.5 10.7 20l96 64c11 7.4 25.9 4.4 33.3-6.7s4.4-25.9-6.7-33.3L280 243.2 280 120c0-13.3-10.7-24-24-24s-24 10.7-24 24z"/>
                                            </svg>
                                        </div>
                                        <div class="text-[#515365] dark:text-white-dark">DURACIÓN: {{ item.duration }}</div>
                                    </div>
                                    <div class="flex font-semibold">
                                        <div class="text-primary flex items-center ltr:mr-3 rtl:ml-3">
                                            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                                <path fill="currentColor" d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64c-7.1 0-13.9-1.2-20.3-3.3c-5.5-1.8-11.9 1.6-11.7 7.4c.3 6.9 1.3 13.8 3.2 20.7c13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3z"/>
                                            </svg>
                                            {{ item.number_views }}
                                        </div>
                                        <div class="text-primary flex items-center">
                                            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                                <path fill="currentColor" d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/>
                                            </svg>
                                            {{ item.stars }}
                                        </div>
                                    </div>
                                </div>
                                <ul class="flex items-center justify-center gap-2 mt-4">
                                    <li>
                                        <button @click="openModalVideo(item)" class="btn btn-info btn-sm text-xs" type="button" >
                                            <iconPencil class="w-4 h-4 mr-2" /> Editar
                                        </button>
                                    </li>
                                    <li>
                                        <button v-on:click="destroyVideo(item)" class="btn btn-danger btn-sm text-xs" type="button" >
                                            <iconX class="w-4 h-4 mr-2" /> Eliminar
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        <Drawer id="modalAddVideos" width="40%" placement="right" :closable="false" v-model:open="displayModalVideo" @close="closeModalVideo">
            <div class="w-[80%] mx-auto">
                <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ formVideo.id ? 'Editar video' : 'Nuevo video' }}
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
                        <PrimaryButton @click="saveVideo" :class="{ 'opacity-25': formVideo.processing }" :disabled="formVideo.processing" type="button">
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
