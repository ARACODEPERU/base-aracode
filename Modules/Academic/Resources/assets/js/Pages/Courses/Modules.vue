<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import { Link, useForm, router } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ref, onMounted, computed, watch } from 'vue';
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogOverlay } from '@headlessui/vue';
    import flatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import Swal2 from 'sweetalert2';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import SecondaryButton from '@/Components/SecondaryButton.vue';
    import SpinnerLoading from '@/Components/SpinnerLoading.vue';
    import IconClipboardText from '@/Components/vristo/icon/icon-clipboard-text.vue';
    import IconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
    import IconSquareRotated from '@/Components/vristo/icon/icon-square-rotated.vue';
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import IconMenu from '@/Components/vristo/icon/icon-menu.vue';
    import IconUserPlus from '@/Components/vristo/icon/icon-user-plus.vue';
    import IconHorizontalDots from '@/Components/vristo/icon/icon-horizontal-dots.vue';
    import IconPencilPaper from '@/Components/vristo/icon/icon-pencil-paper.vue';
    import IconX from '@/Components/vristo/icon/icon-x.vue';
    import InputError from '@/Components/InputError.vue';
    import ModalLargeXX from "@/Components/ModalLargeXX.vue";


    import ModalLarge from "@/Components/ModalLarge.vue";
    import { reactive } from "vue";

    const props = defineProps({
        course: {
            type: Object,
            default: () => ({}),
        }
    });

    const dataModules = ref([]);
    const dataThemes = ref([]);
    const dataContents = ref([]);

    const selectedTab = ref('');
    const isShowTaskMenu = ref(false);
    const displayThemeModal = ref(false);
    const displayModuleModal = ref(false);
    const viewTaskModal = ref(false);

    const contentForm = useForm({
        theme_key: null,
        theme_name: null,
        theme_id: null,
        id: null,
        position: null,
        description: null,
        content: null,
        is_file: 1
    });

    const themeForm = useForm({
        module_index: null,
        module_name: null,
        module_id: null,
        id: null,
        position: null,
        description: null,
    });

    const moduleForm = useForm({
        course_name: null,
        course_id: null,
        id: null,
        position: null,
        description: null,
    });

    const baseUrl = assetUrl;

    const getPath = (path) => {
        return baseUrl + 'storage/'+ path;
    }

    onMounted(() => {
        dataModules.value = props.course.modules;
        moduleForm.course_name = props.course.description;
        moduleForm.course_id = props.course.id;
    });

    const themesChanged = (module = null, index = null) => {
        themeForm.module_id = module.id;
        themeForm.module_name = module.description;
        themeForm.module_index = index;

        if(module.themes){
            dataThemes.value = module.themes;
        }else{
            dataThemes.value = [];
        }

        isShowTaskMenu.value = false;
        selectedTab.value = module.id;
    };


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

    const newHeight = ref(280);

    const modifiedContent = (content) => {
        // Copia el contenido original
        let modifiedContent = content;

        // Realiza la sustitución de la altura con un valor dinámico
        modifiedContent = modifiedContent.replace(/height="\d+"/g, `height="${newHeight.value}"`);
        modifiedContent = modifiedContent.replace(/width="\d+"/g, `width="100%"`);
        return modifiedContent;
    };
    const btnThemeLoading = ref(false);
    const btnContentLoading = ref(false);
    const btnModuleLoading = ref(false);

    const saveContent = () => {
        btnContentLoading.value = true;
        axios.post(route('aca_courses_module_themes_content_store'), contentForm,{
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        }).then((response) => {
            if(response.data.success){
                let newContent = response.data.content;
                return newContent;
            }else{
                contentForm.setError('content', response.data.errorPdf);
                throw new Error('Error en el contenido PDF');
            }
        }).then((result) => {
            dataContents.value.push(result);

            dataThemes.value[contentForm.theme_key].contents = dataContents.value;

            contentForm.reset(
                'id',
                'position',
                'description',
                'content',
                'is_file'
            );
            setTimeout(() => {
                btnContentLoading.value = false;
            });
        }).catch(error => {
            let validationErrors = error.response.data.errors;
            if (validationErrors && validationErrors.content) {
                const contentErrors = validationErrors.content;

                for (let i = 0; i < contentErrors.length; i++) {
                    contentForm.setError('content', contentErrors[i]);
                }
            }
            if (validationErrors && validationErrors.description) {
                const descriptionErrors = validationErrors.description;

                for (let i = 0; i < descriptionErrors.length; i++) {
                    contentForm.setError('description', descriptionErrors[i]);
                }
            }
            if (validationErrors && validationErrors.position) {
                const positionErrors = validationErrors.position;

                for (let i = 0; i < positionErrors.length; i++) {
                    contentForm.setError('position', positionErrors[i]);
                }
            }
            btnContentLoading.value = false;
        }).finally(() => {
            btnContentLoading.value = false;
        });
    }

    const deleteContent = (id, index) => {
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
            backdrop: true,
            preConfirm: () => {
                return axios.delete(route('aca_courses_module_themes_content_destroy', id)).then((res) => {
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
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                dataContents.value.splice(index,1);
            }
        });

    }

    const closeModalTheme = () => {
        displayThemeModal.value = false;
    }

    const openModalTheme = (data = null) => {
        if(data){
            themeForm.id = data.id;
            themeForm.position = data.position;
            themeForm.description = data.description;

        }
        displayThemeModal.value = true;
    }

    const viewContents = (item = null, key = null) => {
        if(item.contents){
            dataContents.value = item.contents;
        }else{
            dataContents.value = [];
        }

        contentForm.theme_name = item.description;
        contentForm.theme_id = item.id;
        contentForm.theme_key = key;
        setTimeout(() => {
            viewTaskModal.value = true;
        });
    };

    const saveTheme = () => {

        btnThemeLoading.value = true;

        let urrl = route('aca_courses_module_themes_store');
        let metthod = 'POST';

        if(themeForm.id){
            urrl = route('aca_courses_module_themes_update',themeForm.id);
            metthod = 'PUT';
        }

        axios({
            method: metthod,
            url: urrl,
            data: themeForm
        }).then((response) => {
            let newContent = response.data.theme;
            return newContent;
        }).then((result) => {
            if(themeForm.id){
                replaceItemById(themeForm.id,result)
            }else{
                dataThemes.value.push(result);
            }
            themeForm.reset(
                'id',
                'position',
                'description',
            );
            setTimeout(() => {
                btnThemeLoading.value = false;
                displayThemeModal.value = false;
            });
        }).catch(function (error) {
            if (error.response.status === 422) {
                // Obtén los errores del objeto de respuesta JSON
                const errors = error.response.data.errors;

                for (let field in errors) {
                    themeForm.setError(field, errors[field][0]);
                }
            }
            themeForm.progress = false;
            btnThemeLoading.value = false;
        });
    }

    const replaceItemById = (id, newItem = null) => {
        const index = dataThemes.value.findIndex(item => item.id === id);
        if (index !== -1) {
            if(newItem){
                dataThemes.value.splice(index, 1, newItem);
            }else{
                dataThemes.value.splice(index,1);
            }
        }
    }

    const deleteTheme = (id) => {
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
            backdrop: true,
            preConfirm: () => {
                return axios.delete(route('aca_courses_module_themes_destroy', id)).then((res) => {
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
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                replaceItemById(id);
            }
        });
    }

    const closeModalModule = () => {
        displayModuleModal.value = false;
        moduleForm.clearErrors();
    }

    const openModalModule = (data = null) => {
        if(data){
            moduleForm.id = data.id;
            moduleForm.position = data.position;
            moduleForm.description = data.description;

        }
        displayModuleModal.value = true;
    }

    const saveModule = () => {
        btnModuleLoading.value = true;

        let urrl = route('aca_courses_module_store');
        let metthod = 'POST';

        if(moduleForm.id){
            urrl = route('aca_courses_module_update', moduleForm.id);
            metthod = 'PUT';
        }

        axios({
            method: metthod,
            url: urrl,
            data: moduleForm
        }).then((response) => {
            let newContent = response.data.module;

            return newContent;
        }).then((result) => {
            if(moduleForm.id){
                replaceModuleById(moduleForm.id, result)
            }else{
                dataModules.value.push(result);
            }
            moduleForm.reset(
                'id',
                'position',
                'description',
            );
            setTimeout(() => {
                btnModuleLoading.value = false;
            });
        }).catch(function (error) {
            if (error.response.status === 422) {
                // Obtén los errores del objeto de respuesta JSON
                const errors = error.response.data.errors;

                for (let field in errors) {
                    moduleForm.setError(field, errors[field][0]);
                }
            }
            btnModuleLoading.value = false;
            //displayModuleModal.value = false;
        });

    }

    const replaceModuleById = (id, newItem = null) => {
        const index = dataModules.value.findIndex(item => item.id === id);
        if (index !== -1) {
            if(newItem){
                dataModules.value.splice(index, 1, newItem);
            }else{
                dataModules.value.splice(index,1);
            }
        }
    }

    const deleteModule = (id) => {
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
                return axios.delete(route('aca_courses_module_destroy', id)).then((res) => {
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
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                replaceModuleById(id);
            }
        });
    }

    const handleFileChangeContent = (event) => {
        const file = event.target.files[0];
        const allowedTypes = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel'
        ];

        if (file && allowedTypes.includes(file.type)) {
            contentForm.content = file;
            contentForm.clearErrors();
        } else {
            contentForm.setError({
                content: 'Solo se permiten archivos PDF o Excel.',
            });
            event.target.value = null; // Resetea el campo de entrada si el archivo no es válido
        }
    }

    const displayModalDocents = ref(false);
    const dataModalModule = ref({});
    const formModuleTeacher = ref({
        module_id: null,
        teacher_id: null,
        processing: false
    });

    const showModalDocents = (module) => {
        formModuleTeacher.value.teacher_id = module.teacher_id;
        dataModalModule.value = module;
        formModuleTeacher.value.module_id = module.id
        displayModalDocents.value = true;
    }

    const closeModalDocents = () => {
        displayModalDocents.value = false;
    }

    const saveModuleTeacher = () => {
        formModuleTeacher.value.processing = true;

        axios({
            method: 'POST',
            url: route('aca_courses_module_teacher_update'),
            data: formModuleTeacher.value
        }).then(() => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se agrego al docente correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }).catch(function (error) {
            console.log(error);
        }).finally(() => {
            formModuleTeacher.value.processing = false;
        });

    }

    const toggleTeacher = (teacherId, event) => {
        formModuleTeacher.value.teacher_id = event.target.checked ? teacherId : null;
    };


    //////////examen//////
    const formQuestion = useForm({
        id: null,
        exam_id: null,
        description: null,
        scord: 1,
        type: 'Escribir',
    });

    const displayModelConfigExam = ref(false);

    const formExam = useForm({
        id: null,
        content_id: null,
        description: null,
        date_start: null,
        date_end: null,
        status: 1,
    });

    const formAnswer = useForm({
        id: null,
        question_id: null,
        description: null,
        correct: 0,
        score: 1,
        type_answers: null
    });

    const dateTime = ref({
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        locale: Spanish,
    });

    const questions = ref([]);

    const opemModalConfigExam = (conte) => {
        formExam.content_id = conte.id;
        formExam.id = conte.exam ? conte.exam.id : null;
        formExam.status = conte.exam ? conte.exam.status : true;
        formExam.description = conte.exam ? conte.exam.description : conte.description;
        formExam.date_start = conte.exam ? conte.exam.date_start : null;
        formExam.date_end = conte.exam ? conte.exam.date_end : null;
        formQuestion.exam_id = conte.exam ? conte.exam.id : null;

        if(conte.exam && conte.exam.questions){
            questions.value = conte.exam.questions;
        }
        displayModelConfigExam.value = true;
        isOverlayVisible.value = true;
    }

    const closeModalConfigExam = () => {
        displayModelConfigExam.value = false;
    }

    const saveExam = () => {
        formExam.processing = true;
        axios({
            method: 'POST',
            url: route('aca_course_exam_store'),
            data: formExam
        }).then((result)=> {
            Swal2.fire({
                title: result.data.title,
                text: result.data.message,
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            formExam.id = result.data.idExam;
            formQuestion.exam_id = result.data.idExam;
        }).catch(function (error) {
            console.log(error);
        }).finally(() => {
            formExam.processing = false;
        });
    }
    const saveQuestion = () => {
        if(formExam.id){
            formQuestion.processing = true;
            axios({
                method: 'POST',
                url: route('aca_course_exam_question_store'),
                data: formQuestion
            }).then((result)=> {
                Swal2.fire({
                    title: result.data.title,
                    text: result.data.message,
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                if(formQuestion.id){
                    const exam = questions.value.find(e => e.id === formQuestion.id)
                    if (exam) {
                        exam.description = result.data.question.description;
                        exam.score = result.data.question.score;
                        exam.type_answers = result.data.question.type_answers;
                    }
                }else{
                    questions.value.push(result.data.question);
                }

                formQuestion.id = null;
                formQuestion.description = null;
                formQuestion.scord = 1;
                formQuestion.type= 'Escribir';

            }).catch(function (error) {
                console.log(error);
            }).finally(() => {
                formQuestion.processing = false;
            });
        }else{
            showMessage('No existe examen para continuar')
        }
    }

    const canselEditQuestion = () => {
        formQuestion.id = null;
        formQuestion.description = null;
        formQuestion.scord = 1;
        formQuestion.type = 'Escribir';
        isOverlayVisible.value = true
    }

    const editQuestion = (item) => {
        formQuestion.id = item.id;
        formQuestion.description = item.description;
        formQuestion.scord = item.score;
        formQuestion.type = item.type_answers;
    }

    const deleteQuestion = (id) => {
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
                return axios.delete(route('aca_course_exam_question_destroy', id)).then((res) => {
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
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                const index = questions.value.findIndex(e => e.id === id)
                if (index !== -1) {
                    questions.value.splice(index, 1)
                }
            }
        });
    }

    const answersData = ref([]);
    const answersActive = ref(null);
    const isOverlayVisible = ref(true);

    const configAnswer = (item) => {
        console.log(item.id);
        answersData.value = item.answers;
        isOverlayVisible.value = false;
        answersActive.value = item.id;
        formAnswer.question_id = item.id;
        formAnswer.type_answers = item.type_answers;
    }

    const saveAnswer = () => {
        if(formAnswer.question_id){
            formAnswer.processing = true;
            axios({
                method: 'POST',
                url: route('aca_course_exam_answer_store'),
                data: formAnswer
            }).then((result)=> {
                Swal2.fire({
                    title: result.data.title,
                    text: result.data.message,
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                if(formAnswer.id){
                    const xanswer = answersData.value.find(e => e.id === formAnswer.id)
                    if (xanswer) {
                        xanswer.description = result.data.answer.description;
                        xanswer.score = result.data.answer.score;
                        xanswer.correct = result.data.answer.correct;
                    }
                }else{
                    answersData.value.push(result.data.answer);
                }

                formAnswer.id = null;
                formAnswer.description = null;
                formAnswer.score = 1;
                formAnswer.correct = 0;

            }).catch(function (error) {
                console.log(error);
            }).finally(() => {
                formAnswer.processing = false;
            });
        }else{
            showMessage('No existe examen para continuar')
        }
    }

    const editAnswer = (item) => {
        formAnswer.id = item.id;
        formAnswer.description = item.description;
        formAnswer.score = item.score;
        formAnswer.correct = item.correct;
    }

    const deleteAnswer = (id) => {
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
                return axios.delete(route('aca_course_exam_answer_destroy', id)).then((res) => {
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
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                const index = answersData.value.findIndex(e => e.id === id)
                if (index !== -1) {
                    answersData.value.splice(index, 1)
                }
            }
        });
    }

    const canselEditAnswer = () => {
        formAnswer.id = null;
        formAnswer.description = null;
        formAnswer.score = 1;
        formAnswer.correct = 0;
    }
</script>

<template>
    <AppLayout title="Cursos">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <Link :href="route('aca_courses_list')" class="text-primary hover:underline">Cursos</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('aca_courses_edit', course.id)" class="text-primary hover:underline">{{ course.description }}</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Modulos</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div>
                <div class="flex gap-5 relative sm:h-[calc(100vh_-_150px)] h-full">
                    <div
                        class="panel p-4 flex-none w-[340px] max-w-full absolute xl:relative z-10 space-y-4 xl:h-auto h-full xl:block ltr:xl:rounded-r-md ltr:rounded-r-none rtl:xl:rounded-l-md rtl:rounded-l-none hidden"
                        :class="{ '!block': isShowTaskMenu }"
                    >
                        <div class="flex flex-col h-full pb-16">
                            <div class="pb-5">
                                <div class="flex text-center items-center">
                                    <div class="shrink-0">
                                        <icon-clipboard-text />
                                    </div>
                                    <h3 class="text-lg font-semibold ltr:ml-3 rtl:mr-3">Modulos</h3>
                                </div>
                            </div>
                            <div class="h-px w-full border-b border-[#e0e6ed] dark:border-[#1b2e4b] mb-5"></div>
                            <perfect-scrollbar
                                :options="{
                                    swipeEasing: true,
                                    wheelPropagation: false,
                                }"
                                class="relative ltr:pr-3.5 rtl:pl-3.5 ltr:-mr-3.5 rtl:-ml-3.5 h-full grow"
                            >
                                <div class="space-y-1">
                                    <div v-for="(module, index) in dataModules"
                                        class="w-full flex justify-between items-center p-1 hover:bg-white-dark/10 rounded-md dark:hover:bg-[#181F32] font-medium ltr:hover:pl-3 rtl:hover:pr-3 duration-300"
                                        :class="selectedTab === module.id ? 'ltr:pl-3 rtl:pr-3 bg-gray-100 dark:bg-[#181F32] text-primary' : 'text-success'"
                                    >
                                        <div  @click="themesChanged(module, index)" class="flex items-center" style="cursor: pointer;">
                                            <icon-square-rotated class="fill-success shrink-0" />
                                            <div class="text-left ltr:ml-3 rtl:mr-3">{{ module.description }}</div>
                                        </div>
                                        <div class="bg-primary-light dark:bg-[#060818] rounded-md py-0.5 px-2 font-semibold whitespace-nowrap">
                                            <div class="dropdown">
                                                <Popper
                                                    :placement="'bottom-start'"
                                                    offsetDistance="0"
                                                    class="align-middle"
                                                >
                                                    <a href="javascript:;">
                                                        <icon-horizontal-dots class="rotate-90 opacity-70" />
                                                    </a>
                                                    <template #content="{ close }">
                                                        <ul @click="close()" class="whitespace-nowrap">
                                                            <li>
                                                                <a href="javascript:;" @click="openModalModule(module)">
                                                                    <icon-pencil-paper class="w-4.5 h-4.5 ltr:mr-2 rtl:ml-2 shrink-0" />
                                                                    Editar
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:;" @click="showModalDocents(module)">
                                                                    <icon-user-plus class="w-4.5 h-4.5 ltr:mr-2 rtl:ml-2 shrink-0" />
                                                                    <span>
                                                                        Docentes
                                                                    </span>
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:;" @click="deleteModule(module.id)">
                                                                    <icon-trash-lines class="ltr:mr-2 rtl:ml-2 shrink-0" />
                                                                    Eliminar
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </template>
                                                </Popper>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </perfect-scrollbar>
                            <div class="ltr:left-0 rtl:right-0 absolute bottom-0 p-4 w-full">
                                <button class="btn btn-primary w-full" type="button" @click="openModalModule()">
                                    <icon-plus class="ltr:mr-2 rtl:ml-2 shrink-0" />
                                    Nuevo Modulo
                                </button>
                            </div>
                        </div>
                    </div>
                    <div
                        class="overlay bg-black/60 z-[5] w-full h-full rounded-md absolute hidden"
                        :class="{ '!block xl:!hidden': isShowTaskMenu }"
                        @click="isShowTaskMenu = !isShowTaskMenu"
                    ></div>
                    <div class="panel p-0 flex-1 overflow-auto h-full">
                        <div class="flex flex-col h-full">
                            <div class="p-4 flex sm:flex-row flex-col w-full sm:items-center gap-4">
                                <div class="ltr:mr-3 rtl:ml-3 flex items-center">
                                    <button type="button" class="xl:hidden hover:text-primary block ltr:mr-3 rtl:ml-3" @click="isShowTaskMenu = !isShowTaskMenu">
                                        <icon-menu />
                                    </button>
                                    <!-- <div class="relative group flex-1">
                                        <input
                                            type="text"
                                            class="form-input peer ltr:!pr-10 rtl:!pl-10"
                                            placeholder="Search Task..."
                                            v-model="searchTask"
                                            @keyup="searchTasks()"
                                        />
                                        <div class="absolute ltr:right-[11px] rtl:left-[11px] top-1/2 -translate-y-1/2 peer-focus:text-primary">
                                            <icon-search />
                                        </div>
                                    </div> -->
                                </div>
                                <div class="flex items-center justify-center sm:justify-end sm:flex-auto flex-1">
                                    <button @click="openModalTheme" type="button" class="btn btn-outline-primary">Nuevo tema</button>
                                </div>
                            </div>
                            <div class="h-px w-full border-b border-[#e0e6ed] dark:border-[#1b2e4b]"></div>
                            <template v-if="dataThemes.length">
                                <div class="table-responsive grow overflow-y-auto sm:min-h-[300px] min-h-[400px]">
                                    <table class="table-hover">
                                        <tbody>
                                            <template v-for="(theme, key) in dataThemes" :key="theme.id">
                                                <tr class="group cursor-pointer" :class="{ 'bg-white-light/30 dark:bg-[#1a2941]': theme.status === 'complete' }">
                                                    <td class="w-1 text-center text-danger" :class="{ 'text-warning': (theme.contents ? theme.contents.length : 0) > 0 }">
                                                        {{ theme.position }}
                                                    </td>
                                                    <td>
                                                        <div @click="viewContents(theme, key)"
                                                                class="group-hover:text-primary font-semibold text-sm text-danger"
                                                                :class="{ 'text-warning': (theme.contents ? theme.contents.length : 0) > 0 }"
                                                            >
                                                                {{ theme.description }}
                                                        </div>
                                                    </td>

                                                    <td class="w-1">
                                                        <div class="flex items-center justify-between w-max">

                                                            <div class="dropdown">
                                                                <Popper
                                                                    :placement="'bottom-start'"
                                                                    offsetDistance="0"
                                                                    class="align-middle"
                                                                >
                                                                    <a href="javascript:;">
                                                                        <icon-horizontal-dots class="rotate-90 opacity-70" />
                                                                    </a>
                                                                    <template #content="{ close }">
                                                                        <ul @click="close()" class="whitespace-nowrap">
                                                                            <li>
                                                                                <a href="javascript:;" @click="openModalTheme(theme)">
                                                                                    <icon-pencil-paper class="w-4.5 h-4.5 ltr:mr-2 rtl:ml-2 shrink-0" />
                                                                                    Editar
                                                                                </a>
                                                                            </li>
                                                                            <li>
                                                                                <a href="javascript:;" @click="deleteTheme(theme.id)">
                                                                                    <icon-trash-lines class="ltr:mr-2 rtl:ml-2 shrink-0" />
                                                                                    Eliminar
                                                                                </a>
                                                                            </li>
                                                                            <!-- <li>
                                                                                <a href="javascript:;" @click="setImportant(theme)">
                                                                                    <icon-star class="w-4.5 h-4.5 ltr:mr-2 rtl:ml-2 shrink-0" />
                                                                                    <span>
                                                                                        Preguntas
                                                                                    </span>
                                                                                </a>
                                                                            </li> -->
                                                                        </ul>
                                                                    </template>
                                                                </Popper>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                            <template v-else>
                                <div class="flex justify-center items-center sm:min-h-[300px] min-h-[400px] font-semibold text-lg h-full">No data available</div>
                            </template>
                        </div>
                    </div>

                    <TransitionRoot appear :show="displayThemeModal" as="template">
                        <Dialog as="div" class="relative z-[51]">
                            <TransitionChild
                                as="template"
                                enter="duration-300 ease-out"
                                enter-from="opacity-0"
                                enter-to="opacity-100"
                                leave="duration-200 ease-in"
                                leave-from="opacity-100"
                                leave-to="opacity-0"
                            >
                                <DialogOverlay class="fixed inset-0 bg-[black]/60" />
                            </TransitionChild>

                            <div class="fixed inset-0 overflow-y-auto">
                                <div class="flex min-h-full items-center justify-center px-4 py-8">
                                    <TransitionChild
                                        as="template"
                                        enter="duration-300 ease-out"
                                        enter-from="opacity-0 scale-95"
                                        enter-to="opacity-100 scale-100"
                                        leave="duration-200 ease-in"
                                        leave-from="opacity-100 scale-100"
                                        leave-to="opacity-0 scale-95"
                                    >
                                        <DialogPanel class="panel border-0 p-0 rounded-lg overflow-hidden w-full max-w-lg text-black dark:text-white-dark">
                                            <button
                                                type="button"
                                                class="absolute top-4 ltr:right-4 rtl:left-4 text-gray-400 hover:text-gray-800 dark:hover:text-gray-600 outline-none"
                                                @click="closeModalTheme"
                                            >
                                                <icon-x />
                                            </button>
                                            <div class="text-lg font-medium bg-[#fbfbfb] dark:bg-[#121c2c] ltr:pl-5 rtl:pr-5 py-3 ltr:pr-[50px] rtl:pl-[50px]">
                                                {{ themeForm.id ? 'Editar tema' : 'Nuevo tema' }}
                                            </div>
                                            <div class="p-5">
                                                <form @submit.prevent="saveTheme">
                                                    <div class="space-y-4">
                                                        <div class="">
                                                            <label>Posición</label>
                                                            <input v-model="themeForm.position" id="themposition" type="text" class="form-input" placeholder="Posición" />
                                                            <InputError :message="themeForm.errors.position" class="mt-2" />
                                                        </div>
                                                        <div class="">
                                                            <label>Descripción</label>
                                                            <textarea v-model="themeForm.description" id="themdescription" type="text" class="form-input" placeholder="Descripción" rows="4" ></textarea>
                                                            <InputError :message="themeForm.errors.description" class="mt-2" />
                                                        </div>
                                                    </div>
                                                    <div class="ltr:text-right rtl:text-left flex justify-end items-center mt-8 space-x-4">
                                                        <PrimaryButton :class="{ 'opacity-25': btnThemeLoading }" :disabled="btnThemeLoading">
                                                            <SpinnerLoading :display="btnThemeLoading" />
                                                            Cuardar
                                                        </PrimaryButton>
                                                        <SecondaryButton type="button" @click="closeModalTheme">Cerrar</SecondaryButton>
                                                    </div>
                                                </form>
                                            </div>
                                        </DialogPanel>
                                    </TransitionChild>
                                </div>
                            </div>
                        </Dialog>
                    </TransitionRoot>

                    <TransitionRoot appear :show="viewTaskModal" as="template">
                        <Dialog as="div" class="relative z-[51]">
                            <TransitionChild
                                as="template"
                                enter="duration-300 ease-out"
                                enter-from="opacity-0"
                                enter-to="opacity-100"
                                leave="duration-200 ease-in"
                                leave-from="opacity-100"
                                leave-to="opacity-0"
                            >
                                <DialogOverlay class="fixed inset-0 bg-[black]/60" />
                            </TransitionChild>

                            <div class="fixed inset-0 overflow-y-auto">
                                <div class="flex min-h-full items-center justify-center px-4 py-8">
                                    <TransitionChild
                                        as="template"
                                        enter="duration-300 ease-out"
                                        enter-from="opacity-0 scale-95"
                                        enter-to="opacity-100 scale-100"
                                        leave="duration-200 ease-in"
                                        leave-from="opacity-100 scale-100"
                                        leave-to="opacity-0 scale-95"
                                    >
                                        <DialogPanel class="panel border-0 p-0 rounded-lg overflow-hidden w-full max-w-2xl text-black dark:text-white-dark">
                                            <button
                                                type="button"
                                                class="absolute top-4 ltr:right-4 rtl:left-4 text-gray-400 hover:text-gray-800 dark:hover:text-gray-600 outline-none"
                                                @click="viewTaskModal = false"
                                            >
                                                <icon-x />
                                            </button>
                                            <div class="flex items-center flex-wrap gap-2 text-lg font-medium bg-[#fbfbfb] dark:bg-[#121c2c] ltr:pl-5 rtl:pr-5 py-3 ltr:pr-[50px] rtl:pl-[50px]">
                                                <div>{{ contentForm.theme_name }}</div>
                                            </div>
                                            <div class="p-5">
                                                <div class="space-y-5">
                                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                                        <div class="sm:col-span-1">
                                                            <input v-model="contentForm.position" id="conposition" type="text" class="form-input" placeholder="Posición" />
                                                            <div class="text-danger text-sm mt-1" v-if="contentForm.errors.position">{{ contentForm.errors.position }}</div>
                                                        </div>
                                                        <div class="sm:col-span-1">
                                                            <select v-model="contentForm.is_file" id="ctnSelect2" class="form-select text-white-dark" required>
                                                                <option value="1">Link de archivo</option>
                                                                <option value="0">frame de vídeo</option>
                                                                <option value="3">Link videoconferencia</option>
                                                                <option value="2">Subir Archivo</option>
                                                                <option value="4">Examen</option>
                                                            </select>
                                                            <div class="text-danger text-sm mt-1" v-if="contentForm.errors.is_file">{{ contentForm.errors.is_file }}</div>
                                                        </div>
                                                        <div class="sm:col-span-2">
                                                            <input v-model="contentForm.description" id="condescription" type="text" class="form-input" placeholder="Descripción" />
                                                            <div class="text-danger text-sm mt-1" v-if="contentForm.errors.description">{{ contentForm.errors.description }}</div>
                                                        </div>
                                                        <div v-if="contentForm.is_file == 2" class="sm:col-span-4">
                                                            <label for="ctnFile">Archivo</label>
                                                            <input @change="handleFileChangeContent" id="ctnFile" type="file" class="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file:ml-5 file:text-white file:hover:bg-primary" required />
                                                            <div class="text-danger text-sm mt-1" v-if="contentForm.errors.content">{{ contentForm.errors.content }}</div>
                                                        </div>
                                                        <div v-else class="sm:col-span-4">
                                                            <textarea v-model="contentForm.content" id="concontent" rows="3" class="form-textarea" placeholder="Contenido" quired></textarea>
                                                            <div class="text-danger text-sm mt-1" v-if="contentForm.errors.content">{{ contentForm.errors.content }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-if="dataContents.length > 0" class="mt-4 p-4 h-64 overflow-y-auto" >

                                                    <ol class="relative border-s border-gray-200 dark:border-gray-700">
                                                        <li v-for="(conte, hy) in dataContents"class="mb-10 ms-6">
                                                            <span class="absolute flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full -start-4 ring-8 ring-white dark:ring-gray-900 dark:bg-blue-900">
                                                                <svg v-if="conte.is_file == 0" class="w-4 h-4 text-blue-800 dark:text-blue-300" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                                                    <path d="M256 0L576 0c35.3 0 64 28.7 64 64l0 224c0 35.3-28.7 64-64 64l-320 0c-35.3 0-64-28.7-64-64l0-224c0-35.3 28.7-64 64-64zM476 106.7C471.5 100 464 96 456 96s-15.5 4-20 10.7l-56 84L362.7 169c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6l80 0 48 0 144 0c8.9 0 17-4.9 21.2-12.7s3.7-17.3-1.2-24.6l-96-144zM336 96a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zM64 128l96 0 0 256 0 32c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-32 160 0 0 64c0 35.3-28.7 64-64 64L64 512c-35.3 0-64-28.7-64-64L0 192c0-35.3 28.7-64 64-64zm8 64c-8.8 0-16 7.2-16 16l0 16c0 8.8 7.2 16 16 16l16 0c8.8 0 16-7.2 16-16l0-16c0-8.8-7.2-16-16-16l-16 0zm0 104c-8.8 0-16 7.2-16 16l0 16c0 8.8 7.2 16 16 16l16 0c8.8 0 16-7.2 16-16l0-16c0-8.8-7.2-16-16-16l-16 0zm0 104c-8.8 0-16 7.2-16 16l0 16c0 8.8 7.2 16 16 16l16 0c8.8 0 16-7.2 16-16l0-16c0-8.8-7.2-16-16-16l-16 0zm336 16l0 16c0 8.8 7.2 16 16 16l16 0c8.8 0 16-7.2 16-16l0-16c0-8.8-7.2-16-16-16l-16 0c-8.8 0-16 7.2-16 16z"/>
                                                                </svg>
                                                                <svg v-else-if="conte.is_file == 3" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-800 dark:text-blue-300" fill="currentColor" viewBox="0 0 384 512">
                                                                    <path d="M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80L0 432c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z"/>
                                                                </svg>
                                                                <svg v-else-if="conte.is_file == 4" class="w-4 h-4 text-blue-800 dark:text-blue-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                                                        <path fill="currentColor" d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 38.6C310.1 219.5 256 287.4 256 368c0 59.1 29.1 111.3 73.7 143.3c-3.2 .5-6.4 .7-9.7 .7L64 512c-35.3 0-64-28.7-64-64L0 64zm384 64l-128 0L256 0 384 128zm48 96a144 144 0 1 1 0 288 144 144 0 1 1 0-288zm0 240a24 24 0 1 0 0-48 24 24 0 1 0 0 48zM368 321.6l0 6.4c0 8.8 7.2 16 16 16s16-7.2 16-16l0-6.4c0-5.3 4.3-9.6 9.6-9.6l40.5 0c7.7 0 13.9 6.2 13.9 13.9c0 5.2-2.9 9.9-7.4 12.3l-32 16.8c-5.3 2.8-8.6 8.2-8.6 14.2l0 14.8c0 8.8 7.2 16 16 16s16-7.2 16-16l0-5.1 23.5-12.3c15.1-7.9 24.5-23.6 24.5-40.6c0-25.4-20.6-45.9-45.9-45.9l-40.5 0c-23 0-41.6 18.6-41.6 41.6z"/>
                                                                    </svg>
                                                                <svg v-else class="w-4 h-4 text-blue-800 dark:text-blue-300" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                                                    <path d="M320 464c8.8 0 16-7.2 16-16l0-288-80 0c-17.7 0-32-14.3-32-32l0-80L64 48c-8.8 0-16 7.2-16 16l0 384c0 8.8 7.2 16 16 16l256 0zM0 64C0 28.7 28.7 0 64 0L229.5 0c17 0 33.3 6.7 45.3 18.7l90.5 90.5c12 12 18.7 28.3 18.7 45.3L384 448c0 35.3-28.7 64-64 64L64 512c-35.3 0-64-28.7-64-64L0 64z"/>
                                                                </svg>
                                                            </span>
                                                            <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600">
                                                                <div class="items-center justify-between mb-3 sm:flex">
                                                                    <a @click="deleteContent(conte.id,hy)" href="#" class="mb-1 text-xs font-normal text-gray-400 sm:order-last sm:mb-0">Eliminar</a>
                                                                    <div class="text-sm font-normal text-gray-500 lex dark:text-gray-300">{{ conte.description }}</div>
                                                                </div>
                                                                <template v-if="conte.is_file == 0" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-100 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                                                                    <div v-html="modifiedContent(conte.content)" class="m-0"></div>
                                                                </template>
                                                                <a v-else-if="conte.is_file == 1" :href="conte.content" target="_blank" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-100 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                                                                    <svg class="w-3.5 h-3.5 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"/>
                                                                        <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/>
                                                                    </svg> Descargar Archivo
                                                                </a>
                                                                <a v-else-if="conte.is_file == 3" :href="conte.content" target="_blank" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-100 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                                                                    <svg class="w-3.5 h-3.5 me-2.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                                                        <path d="M0 128C0 92.7 28.7 64 64 64l256 0c35.3 0 64 28.7 64 64l0 256c0 35.3-28.7 64-64 64L64 448c-35.3 0-64-28.7-64-64L0 128zM559.1 99.8c10.4 5.6 16.9 16.4 16.9 28.2l0 256c0 11.8-6.5 22.6-16.9 28.2s-23 5-32.9-1.6l-96-64L416 337.1l0-17.1 0-128 0-17.1 14.2-9.5 96-64c9.8-6.5 22.4-7.2 32.9-1.6z"/>
                                                                    </svg> Unirse
                                                                </a>
                                                                <button v-else-if="conte.is_file == 4" @click="opemModalConfigExam(conte)" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-100 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                                                                    <svg class="w-3.5 h-3.5 me-2.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                                        <path d="M495.9 166.6c3.2 8.7 .5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6c-4.4 11.9-9.7 23.3-15.8 34.3l-4.7 8.1c-6.6 11-14 21.4-22.1 31.2c-5.9 7.2-15.7 9.6-24.5 6.8l-55.7-17.7c-13.4 10.3-28.2 18.9-44 25.4l-12.5 57.1c-2 9.1-9 16.3-18.2 17.8c-13.8 2.3-28 3.5-42.5 3.5s-28.7-1.2-42.5-3.5c-9.2-1.5-16.2-8.7-18.2-17.8l-12.5-57.1c-15.8-6.5-30.6-15.1-44-25.4L83.1 425.9c-8.8 2.8-18.6 .3-24.5-6.8c-8.1-9.8-15.5-20.2-22.1-31.2l-4.7-8.1c-6.1-11-11.4-22.4-15.8-34.3c-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6c4.4-11.9 9.7-23.3 15.8-34.3l4.7-8.1c6.6-11 14-21.4 22.1-31.2c5.9-7.2 15.7-9.6 24.5-6.8l55.7 17.7c13.4-10.3 28.2-18.9 44-25.4l12.5-57.1c2-9.1 9-16.3 18.2-17.8C227.3 1.2 241.5 0 256 0s28.7 1.2 42.5 3.5c9.2 1.5 16.2 8.7 18.2 17.8l12.5 57.1c15.8 6.5 30.6 15.1 44 25.4l55.7-17.7c8.8-2.8 18.6-.3 24.5 6.8c8.1 9.8 15.5 20.2 22.1 31.2l4.7 8.1c6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z"/>
                                                                    </svg>
                                                                    Configurar preguntas y respuestas
                                                                </button>
                                                                <a v-else :href="getPath(conte.content)" target="_blank" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-100 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                                                                    <svg class="w-3.5 h-3.5 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"/>
                                                                        <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/>
                                                                    </svg> Descargar Archivo
                                                                </a>
                                                            </div>
                                                        </li>
                                                    </ol>

                                                </div>
                                                <div class="flex justify-end items-center mt-8 space-x-2">
                                                    <PrimaryButton :type="'button'" @click="saveContent" :class="{ 'opacity-25': btnContentLoading }" :disabled="btnContentLoading">
                                                        <SpinnerLoading :display="btnContentLoading" />
                                                        Cuardar Contenido
                                                    </PrimaryButton>
                                                    <SecondaryButton type="button" @click="viewTaskModal = false">Cerrar</SecondaryButton>
                                                </div>
                                            </div>
                                        </DialogPanel>
                                    </TransitionChild>
                                </div>
                            </div>
                        </Dialog>
                    </TransitionRoot>

                    <TransitionRoot appear :show="displayModuleModal" as="template">
                        <Dialog as="div" class="relative z-[51]">
                            <TransitionChild
                                as="template"
                                enter="duration-300 ease-out"
                                enter-from="opacity-0"
                                enter-to="opacity-100"
                                leave="duration-200 ease-in"
                                leave-from="opacity-100"
                                leave-to="opacity-0"
                            >
                                <DialogOverlay class="fixed inset-0 bg-[black]/60" />
                            </TransitionChild>

                            <div class="fixed inset-0 overflow-y-auto">
                                <div class="flex min-h-full items-center justify-center px-4 py-8">
                                    <TransitionChild
                                        as="template"
                                        enter="duration-300 ease-out"
                                        enter-from="opacity-0 scale-95"
                                        enter-to="opacity-100 scale-100"
                                        leave="duration-200 ease-in"
                                        leave-from="opacity-100 scale-100"
                                        leave-to="opacity-0 scale-95"
                                    >
                                        <DialogPanel class="panel border-0 p-0 rounded-lg overflow-hidden w-full max-w-lg text-black dark:text-white-dark">
                                            <button
                                                type="button"
                                                class="absolute top-4 ltr:right-4 rtl:left-4 text-gray-400 hover:text-gray-800 dark:hover:text-gray-600 outline-none"
                                                @click="closeModalModule"
                                            >
                                                <icon-x />
                                            </button>
                                            <div class="text-lg font-medium bg-[#fbfbfb] dark:bg-[#121c2c] ltr:pl-5 rtl:pr-5 py-3 ltr:pr-[50px] rtl:pl-[50px]">
                                                {{ moduleForm.id ? 'Editar Modulo' : 'Nuevo Modulo' }}
                                            </div>
                                            <div class="p-5">
                                                <form>
                                                    <div class="space-y-4">
                                                        <div class="">
                                                            <label>Posición</label>
                                                            <input v-model="moduleForm.position" id="modposition" type="text" class="form-input" placeholder="Posición" />
                                                            <InputError :message="moduleForm.errors.position" class="mt-2" />
                                                        </div>
                                                        <div class="">
                                                            <label>Descripción</label>
                                                            <textarea v-model="moduleForm.description" id="moddescription" type="text" class="form-input" placeholder="Descripción" rows="4" ></textarea>
                                                            <InputError :message="moduleForm.errors.description" class="mt-2" />
                                                        </div>
                                                    </div>
                                                    <div class="ltr:text-right rtl:text-left flex justify-end items-center mt-8 space-x-4">
                                                        <PrimaryButton @click="saveModule" :class="{ 'opacity-25': btnModuleLoading }" :disabled="btnModuleLoading">
                                                            <SpinnerLoading :display="btnModuleLoading" />
                                                            Cuardar
                                                        </PrimaryButton>
                                                        <SecondaryButton type="button" @click="closeModalModule">Cerrar</SecondaryButton>
                                                    </div>
                                                </form>
                                            </div>
                                        </DialogPanel>
                                    </TransitionChild>
                                </div>
                            </div>
                        </Dialog>
                    </TransitionRoot>
                </div>
            </div>
        </div>
        <ModalLarge :show="displayModalDocents" :onClose="closeModalDocents">
            <template #title>{{ dataModalModule.description }}</template>
            <template #message>Docentes</template>
            <template #content>
                <div>
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">
                                    Nombre completo
                                </th>
                                <th scope="col" class="px-6 py-3">

                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, go) in course.teachers" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">

                                <th scope="row" class="flex items-center px-6 py-4 text-gray-900 whitespace-nowrap dark:text-white">
                                    <img v-if="row.teacher.person.image" class="w-10 h-10 rounded-full" :src="getPath(row.teacher.person.image)" alt="Jese image">
                                    <img v-else class="w-10 h-10 rounded-full" :src="`https://ui-avatars.com/api/?name=${row.teacher.person.names}&size=150&rounded=true`" :alt="row.teacher.person.names">
                                    <div class="ps-3">
                                        <div class="text-base font-semibold">{{ row.teacher.person.full_name }}</div>
                                        <div class="font-normal text-gray-500">{{ row.teacher.person.email }}</div>
                                    </div>
                                </th>
                                <td class="w-4 p-4 items-center">
                                    <label v-if="course.teachers.length > 1" :for="`radio-teacher-${go}`" class="inline-flex">
                                        <input
                                            type="radio"
                                            name="square_radio"
                                            class="form-radio rounded-none"
                                            :value="row.teacher_id"
                                            :id="`radio-teacher-${go}`"
                                            :checked="formModuleTeacher.teacher_id === row.teacher_id"
                                            @change="formModuleTeacher.teacher_id = row.teacher_id"
                                        />
                                    </label>
                                    <label v-else :for="`checkbox-teacher-${go}`" class="inline-flex">
                                        <input
                                            type="checkbox"
                                            class="form-checkbox peer"
                                            :value="row.teacher_id"
                                            :id="`checkbox-teacher-${go}`"
                                            :checked="formModuleTeacher.teacher_id === row.teacher_id"
                                            @change="toggleTeacher(row.teacher_id, $event)"
                                        />
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton @click="saveModuleTeacher" :class="{ 'opacity-25': formModuleTeacher.processing }" :disabled="formModuleTeacher.processing">
                    <svg v-show="formModuleTeacher.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                    </svg>
                    Guardar
                </PrimaryButton>
            </template>
        </ModalLarge>
        <ModalLargeXX
            :show="displayModelConfigExam"
            :onClose="closeModalConfigExam"
            :icon="'/img/icons8-examen-50.png'"
            style="z-index: 1000;"
        >
            <template #title>Configurar examen</template>
            <template #content>
                <div class="mt-6">
                    <div class="flex flex-col md:flex-row gap-4 items-center w-[90%] mx-auto">
                        <input v-model="formExam.description" type="text" placeholder="Descripción" class="form-input flex-1" />
                        <div class="flex ">
                            <div class="bg-[#eee] flex justify-center items-center ltr:rounded-l-md rtl:rounded-r-md px-3 font-semibold border ltr:border-r-0 rtl:border-l-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]">
                                <svg class="w-4 h-4" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                    <path d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z"/>
                                </svg>
                            </div>
                            <flat-pickr v-model="formExam.date_start" class="form-input rounded-l-none hover:rounded-l-none" :config="dateTime"></flat-pickr>
                        </div>
                        <div class="flex ">
                            <div class="bg-[#eee] flex justify-center items-center ltr:rounded-l-md rtl:rounded-r-md px-3 font-semibold border ltr:border-r-0 rtl:border-l-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]">
                                <svg class="w-4 h-4" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                    <path d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z"/>
                                </svg>
                            </div>
                            <flat-pickr v-model="formExam.date_end" class="form-input rounded-l-none hover:rounded-l-none" :config="dateTime"></flat-pickr>
                        </div>
                        <div>
                            <select v-model="formExam.status" class="form-select">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <button @click="saveExam" type="button" class="btn btn-primary text-xs">
                            <SpinnerLoading :display="formExam.processing" />
                            Guardar
                        </button>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-6">
                    <div class="border rounded-lg px-6 py-4">
                        <h4 class="font-semibold text-lg dark:text-white-light mb-4">Preguntas</h4>
                        <textarea v-model="formQuestion.description" rows="4" placeholder="Descripción" class="form-textarea mb-4"></textarea>
                        <div class="flex flex-col md:flex-row gap-4 items-center w-full mx-auto">

                            <input v-model="formQuestion.scord" type="number" placeholder="Puntos" class="form-input" />
                            <select v-model="formQuestion.type" class="form-select">
                                <option value="Escribir">Escribir</option>
                                <option value="Alternativas">Alternativas</option>
                                <option value="Varias respuestas">Varias respuestas</option>
                                <option value="Subir Archivo">Subir Archivo</option>
                            </select>
                            <button @click="saveQuestion" type="button" class="btn btn-primary text-xs">
                                <SpinnerLoading :display="formQuestion.processing" />
                                Guardar
                            </button>
                            <button v-if="formQuestion.id" @click="canselEditQuestion" type="button" class="btn btn-danger text-xs">Cancelar</button>
                        </div>
                        <div class="mt-6 flex flex-col rounded-md border border-[#e0e6ed] dark:border-[#1b2e4b]">
                            <div v-for="(item, key) in questions">
                                <div class="border-b border-[#e0e6ed] dark:border-[#1b2e4b] px-4 py-2.5"
                                    :class="answersActive == item.id ? 'bg-primary text-white shadow-[0_1px_15px_1px_rgba(67,97,238,0.15)]' : ''"
                                >
                                    <div class="w-full flex justify-between items-center">
                                        <div>{{ item.description }}</div>
                                        <div>
                                            <div class="dropdown">
                                                <Popper :placement="'bottom-end'" offsetDistance="0" class="align-middle">
                                                    <button type="button" class="btn p-0 rounded-none border-0 shadow-none dropdown-toggle dark:text-white-dark hover:text-primary dark:hover:text-primary">
                                                        <icon-horizontal-dots class="rotate-90 opacity-70" />
                                                    </button>
                                                    <template #content="{ close }">
                                                        <ul @click="close()" class="whitespace-nowrap">
                                                            <li><a @click="editQuestion(item)" href="javascript:;">Editar</a></li>
                                                            <li><a @click="deleteQuestion(item.id)" href="javascript:;">Eliminar</a></li>
                                                            <li><a @click="configAnswer(item)" href="javascript:;">Respuestas</a></li>
                                                        </ul>
                                                    </template>
                                                </Popper>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="relative border rounded-lg px-6 py-4">
                        <!-- Overlay que bloquea el contenido -->
                        <div
                            v-if="isOverlayVisible"
                            class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm z-10 rounded-lg flex items-center justify-center"
                            >
                            <p class="text-white text-center">Elige una pregunta para continuar</p>
                        </div>
                        <h4 class="font-semibold text-lg dark:text-white-light mb-4">Respuestas</h4>
                        <textarea v-model="formAnswer.description" placeholder="Descripción" class="form-textarea mb-4" rows="4"></textarea>
                        <div class="flex flex-col md:flex-row gap-4 items-center w-full mx-auto">
                            <input v-model="formAnswer.score" type="number" placeholder="Puntos" class="form-input" />

                            <select v-model="formAnswer.correct" class="form-select">
                                <option value="1">Correcto</option>
                                <option value="0">Incorrecto</option>
                            </select>

                            <button @click="saveAnswer" class="btn btn-primary text-xs" type="button">
                                <SpinnerLoading :display="formAnswer.processing" />
                                Guardar
                            </button>
                            <button v-if="formAnswer.id" @click="canselEditAnswer" type="button" class="btn btn-danger text-xs">Cancelar</button>
                        </div>
                        <div class="mt-6">
                            <div class="p-4 mb-4 text-sm text-blue-800 border border-blue-300 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400 dark:border-blue-800">
                                <label>Tipo de respuesta: {{ formAnswer.type_answers }}</label>
                                <div v-for="answer in answersData">
                                    <div v-if="formAnswer.type_answers == 'Escribir'">

                                        <div>
                                            <div class="w-full flex justify-between items-center mb-2">
                                                <div class="">{{ answer.description }}</div>
                                                <div class="dropdown">
                                                    <Popper :placement="'bottom-end'" offsetDistance="0" class="align-middle">
                                                        <button type="button" class="btn p-0 rounded-none border-0 shadow-none dropdown-toggle dark:text-white-dark hover:text-primary dark:hover:text-primary">
                                                            <icon-horizontal-dots class="rotate-90 opacity-70" />
                                                        </button>
                                                        <template #content="{ close }">
                                                            <ul @click="close()" class="whitespace-nowrap">
                                                                <li><a @click="editAnswer(answer)" href="javascript:;">Editar</a></li>
                                                                <li><a @click="deleteAnswer(answer.id)" href="javascript:;">Eliminar</a></li>
                                                            </ul>
                                                        </template>
                                                    </Popper>
                                                </div>
                                            </div>
                                            <textarea id="ctnTextareax" rows="3" class="form-textarea" placeholder="Escribir respuesta"></textarea>
                                        </div>
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">NOTA!</span> El docente deberá calificar esta respuesta</p>
                                    </div>
                                    
                                    <div v-else-if="formAnswer.type_answers == 'Alternativas'" class="w-full flex justify-between items-center mb-2">
                                        <label class="inline-flex">
                                            <input :id="'rdbanswer-'+answer.id" type="radio" class="form-radio rounded-none" :checked="answer.correct" />
                                            <span>{{ answer.description }}</span>
                                        </label>
                                        <div class="dropdown">
                                            <Popper :placement="'bottom-end'" offsetDistance="0" class="align-middle">
                                                <button type="button" class="btn p-0 rounded-none border-0 shadow-none dropdown-toggle dark:text-white-dark hover:text-primary dark:hover:text-primary">
                                                    <icon-horizontal-dots class="rotate-90 opacity-70" />
                                                </button>
                                                <template #content="{ close }">
                                                    <ul @click="close()" class="whitespace-nowrap">
                                                        <li><a @click="editAnswer(answer)" href="javascript:;">Editar</a></li>
                                                        <li><a @click="deleteAnswer(answer.id)" href="javascript:;">Eliminar</a></li>
                                                    </ul>
                                                </template>
                                            </Popper>
                                        </div>
                                    </div>
                                    <div v-else-if="formAnswer.type_answers == 'Varias respuestas'" class="w-full flex justify-between items-center mb-2">
                                        <label class="inline-flex">
                                            <input :id="'cbxanswer-'+answer.id" type="checkbox" class="form-checkbox" :checked="answer.correct" />
                                            <span>{{ answer.description }}</span>
                                        </label>
                                        <div class="dropdown">
                                            <Popper :placement="'bottom-end'" offsetDistance="0" class="align-middle">
                                                <button type="button" class="btn p-0 rounded-none border-0 shadow-none dropdown-toggle dark:text-white-dark hover:text-primary dark:hover:text-primary">
                                                    <icon-horizontal-dots class="rotate-90 opacity-70" />
                                                </button>
                                                <template #content="{ close }">
                                                    <ul @click="close()" class="whitespace-nowrap">
                                                        <li><a @click="editAnswer(answer)" href="javascript:;">Editar</a></li>
                                                        <li><a @click="deleteAnswer(answer.id)" href="javascript:;">Eliminar</a></li>
                                                    </ul>
                                                </template>
                                            </Popper>
                                        </div>
                                    </div>
                                    <div v-else-if="formAnswer.type_answers == 'Subir Archivo'">
                                        <div class="w-full flex justify-between items-center mb-2">
                                            <div>
                                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="small_size">{{ answer.description }}</label>
                                                <input class="block w-full mb-5 text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" id="small_size" type="file">
                                            </div>
                                            <div class="dropdown">
                                                <Popper :placement="'bottom-end'" offsetDistance="0" class="align-middle">
                                                    <button type="button" class="btn p-0 rounded-none border-0 shadow-none dropdown-toggle dark:text-white-dark hover:text-primary dark:hover:text-primary">
                                                        <icon-horizontal-dots class="rotate-90 opacity-70" />
                                                    </button>
                                                    <template #content="{ close }">
                                                        <ul @click="close()" class="whitespace-nowrap">
                                                            <li><a @click="editAnswer(answer)" href="javascript:;">Editar</a></li>
                                                            <li><a @click="deleteAnswer(answer.id)" href="javascript:;">Eliminar</a></li>
                                                        </ul>
                                                    </template>
                                                </Popper>
                                            </div>
                                        </div>
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">NOTA!</span> El docente deberá calificar esta respuesta</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </ModalLargeXX>
    </AppLayout>
</template>
