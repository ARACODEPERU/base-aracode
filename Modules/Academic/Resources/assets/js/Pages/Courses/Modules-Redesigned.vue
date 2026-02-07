
<script setup>
    // Importaciones existentes sin cambios
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
    import ModalLargeX from "@/Components/ModalLargeX.vue";
    import ModalLarge from "@/Components/ModalLarge.vue";


    // Props y variables existentes
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

    // Variables computadas para estadísticas
    const selectedModule = computed(() => {
        return dataModules.value.find(m => m.id === selectedTab.value);
    });

    const totalThemes = computed(() => {
        return dataModules.value.reduce((total, module) => {
            return total + (module.themes?.length || 0);
        }, 0);
    });

    const totalContents = computed(() => {
        return dataModules.value.reduce((total, module) => {
            return total + module.themes?.reduce((themeTotal, theme) => {
                return themeTotal + (theme.contents?.length || 0);
            }, 0) || 0;
        }, 0);
    });


    const getContentTypeColor = (type) => {
        const colors = {
            '0': 'text-blue-500',
            '1': 'text-green-500',
            '2': 'text-purple-500',
            '3': 'text-orange-500',
            '4': 'text-red-500'
        };
        return colors[type] || 'text-gray-500';
    };

    // Forms con toda la funcionalidad original
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

    watch(
        () => props.course.modules,
        (newModules) => {
            dataModules.value = newModules;
        },
        { deep: true } // Importante para detectar cambios dentro del array
    );

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

    // Funciones para la gestión de módulos


    const openModalModule = (module = null) => {
        if (module) {
            moduleForm.id = module.id;
            moduleForm.description = module.description;
            moduleForm.position = module.position;
        }

        moduleForm.course_name = props.course.description;
        moduleForm.course_id = props.course.id;

        displayModuleModal.value = true;
    };



    const closeModalModule = () => {
        displayModuleModal.value = false;
        moduleForm.reset();
    };

    const replaceModuleById = (id, newItem = null) => {
        const index = dataModules.value.findIndex(item => item.id === id);
        console.log(index);
        if (index !== -1) {
            if(newItem){
                dataModules.value.splice(index, 1, newItem);
            }else{
                dataModules.value.splice(index,1);
            }
        }
    }

    const saveModule = () => {
        btnModuleLoading.value = true;

        let urrl = route('aca_courses_module_store');
        let metthod = 'POST';

        if(moduleForm.id){
            urrl = route('aca_courses_module_update', moduleForm.id);
            metthod = 'PUT';
        }

        axios({method: metthod, url: urrl, data: moduleForm}).then((response) => {
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
    };

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
                // Swal2.fire({
                //     title: 'Enhorabuena',
                //     text: 'Se Eliminó correctamente d',
                //     icon: 'success',
                //     padding: '2em',
                //     customClass: 'sweet-alerts',
                // });
                router.visit(route('aca_courses_module_panel', props.course.id), {
                    method: 'get',
                    replace: true, // Usa true para no llenar el historial si solo refrescas datos
                    preserveState: true, // ¡CRUCIAL! Mantiene el estado de tus inputs y componentes
                    preserveScroll: true, // Evita que la página salte arriba
                    only: ['course'], // Solo pide al servidor el objeto 'course'
                });
            }
        });
    }

    const openModalTheme = (data = null) => {

        if (!selectedModule.value) {
            Swal2.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'Debes seleccionar un módulo primero'
            });
            return;
        }

        themeForm.reset();
        themeForm.module_id = selectedModule.value.id;
        themeForm.module_name = selectedModule.value.description;

        if(data){
            themeForm.id = data.id;
            themeForm.position = data.position;
            themeForm.description = data.description;

        }
        displayThemeModal.value = true;
    };

    const closeModalTheme = () => {
        displayThemeModal.value = false;
    }



    const viewContents = (theme = null, key = null) => {
        contentForm.theme_key = key;
        contentForm.theme_name = theme.description;
        contentForm.theme_id = theme.id;

        if(theme.contents){
            dataContents.value = theme.contents;
        }else{
            dataContents.value = [];
        }

        setTimeout(() => {
            viewTaskModal.value = true;
        });

    };

    const closeModalContents = () => {
        contentForm.reset()
        viewTaskModal.value = false;
    };
    ////modulos crud
    const displayModalDocents = ref(false);
    const dataModalModule = ref({});
    const formModuleTeacher = ref({
        module_id: null,
        teacher_id: null,
        processing: false
    });
    const showModalDocents = (module) => {
        console.log(module);
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

    /////temas

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
    const saveTheme = () => {

        btnThemeLoading.value = true;

        let urrl = route('aca_courses_module_themes_store');
        let metthod = 'POST';

        if(themeForm.id){
            urrl = route('aca_courses_module_themes_update',themeForm.id);
            metthod = 'PUT';
        }

        axios({method: metthod, url: urrl, data: themeForm}).then((response) => {
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
            if (error.response?.status === 422) {
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

    ////contenido

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

            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se agrego el contenido correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
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
        score: 0,
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
            refreshDatos();
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
                //console.log(error);
            }).finally(() => {
                formQuestion.processing = false;
                refreshDatos();
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
        answersData.value = item.answers ?? [];
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
                //console.log(error);
            }).finally(() => {
                formAnswer.processing = false;
                refreshDatos();
            });
        }else{
            showMessage('No existe examen para continuar', 'error')
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

    const refreshDatos = () => {
        router.visit(route('aca_courses_module_panel', props.course.id), {
            method: 'get',
            replace: true,
            preserveState: true,
            preserveScroll: true,
            only: ['course'],
        });
    }
</script>

<template>
    <AppLayout title="Gestión de Módulos">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {
                    route: route('aca_courses_list'),
                    title: 'Cursos',
                    children: [
                        {route: route('aca_enrolledstudents_list', course.id), title: 'Alumnos', permissions: 'aca_cursos_listado_estudiantes'},
                        {route: route('aca_courses_information', course.id), title: 'Información'},
                        {route: route('aca_courses_edit', course.id), title: 'Editar'}
                    ],
                },
                {route: route('aca_courses_edit', course.id), title: course.description},
                {title: 'Modulos'}
            ]"
        />
        <!-- Header Moderno del Curso -->
        <div class="mt-6 bg-gradient-to-r from-blue-100 to-blue-200 dark:from-gray-800 dark:to-gray-700 rounded-lg text-blue-900 dark:text-blue-100 shadow-md">
            <div class="container mx-auto px-6 py-8">
                <!-- Información Principal del Curso -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold mb-2 dark:text-white">{{ course.description }}</h1>
                        <p class="text-blue-600 dark:text-blue-300 text-lg">Organiza y gestiona el contenido educativo del curso</p>
                    </div>

                    <!-- Estadísticas del Curso -->
                    <div class="flex gap-4">
                        <div class="bg-white/20 dark:bg-gray-900/30 backdrop-blur-sm rounded-xl p-4 text-center border dark:border-gray-600">
                            <div class="text-2xl font-bold dark:text-white">{{ dataModules.length }}</div>
                            <div class="text-sm text-blue-600 dark:text-blue-300">Módulos</div>
                        </div>
                        <div class="bg-white/20 dark:bg-gray-900/30 backdrop-blur-sm rounded-xl p-4 text-center border dark:border-gray-600">
                            <div class="text-2xl font-bold dark:text-white">{{ totalThemes }}</div>
                            <div class="text-sm text-blue-600 dark:text-blue-300">Temas</div>
                        </div>
                        <div class="bg-white/20 dark:bg-gray-900/30 backdrop-blur-sm rounded-xl p-4 text-center border dark:border-gray-600">
                            <div class="text-2xl font-bold dark:text-white">{{ totalContents }}</div>
                            <div class="text-sm text-blue-600 dark:text-blue-300">Contenidos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="mt-6 min-h-screen">

            <div class="grid grid-cols-6 gap-8">

                <!-- Sidebar Izquierdo - Lista de Módulos -->
                <div class="col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 h-full flex flex-col">
                        <!-- Header del Sidebar -->
                        <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-100 to-blue-100 dark:from-gray-700 dark:to-blue-900 rounded-t-xl">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-500 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385V4.804zM10.5 4c1.255 0 2.443.29 3.5.804v10A7.968 7.968 0 0014.5 14c-1.255 0-2.443-.29-3.5-.804V4.804z"/>
                                    </svg>
                                    Módulos
                                </h2>
                                <button
                                    @click="openModalModule()"
                                    class="p-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 transition-colors shadow-sm"
                                    title="Crear nuevo módulo"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Lista de Módulos con estilos Tailwind -->
                        <div class="p-4 max-h-[480px] overflow-y-auto space-y-3">
                            <div
                                v-for="(module, index) in dataModules"
                                :key="module.id"
                                class="bg-white border border-gray-200 dark:bg-gray-700 dark:border-gray-600 rounded-xl p-4 cursor-pointer transition-all duration-300 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-400 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-gray-700 dark:hover:to-blue-900"
                                :class="{ 'border-blue-500 dark:border-blue-400 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-blue-900 shadow-md': selectedTab === module.id }"
                                @click="themesChanged(module, index)"
                            >
                                <!-- Icono y Título -->
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start gap-3 flex-1 min-w-0">
                                        <div class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white p-2 rounded-lg flex-shrink-0">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385V4.804zM10.5 4c1.255 0 2.443.29 3.5.804v10A7.968 7.968 0 0014.5 14c-1.255 0-2.443-.29-3.5-.804V4.804z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-semibold text-gray-900 dark:text-white mb-1 truncate text-sm">{{ module.description }}</h3>
                                            <div class="flex items-center gap-2 text-xs">
                                                <span class="bg-blue-100 dark:bg-gray-800 text-blue-700 dark:text-blue-600 px-2 py-1 rounded-full font-medium">
                                                    {{ module.position || 0 }} Posición
                                                </span>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Menú de Acciones -->
                                    <div class="flex flex-col gap-1">
                                        <button
                                            @click.stop="openModalModule(module)"
                                            class="p-1.5 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300 rounded-lg transition-colors hover:bg-blue-200 dark:hover:bg-blue-800/70 group"
                                            v-tippy="{ content: 'Editar módulo' , placement: 'bottom' }"
                                        >
                                            <svg class="w-3 h-3 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                            </svg>
                                        </button>
                                        <button
                                            @click.stop="deleteModule(module)"
                                            class="p-1.5 bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-300 rounded-lg transition-colors hover:bg-red-200 dark:hover:bg-red-800/70 group"
                                            v-tippy="{ content: 'Eliminar módulo' , placement: 'bottom' }"
                                        >
                                            <svg class="w-3 h-3 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenido Central - Gestión de Temas y Contenidos -->
                <div class="col-span-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <!-- Header del Contenido Central -->
                        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-1">
                                        {{ selectedModule?.description || 'Selecciona un módulo' }}
                                    </h2>
                                    <p class="text-gray-500 dark:text-gray-400">
                                        {{ dataThemes.length }} temas • {{ totalContents }} contenidos
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button
                                        v-if="selectedModule"
                                        @click="showModalDocents(selectedModule)"
                                        class="btn btn-success text-xs uppercase"
                                    >
                                        <IconUserPlus class="w-4 h-4 mr-2"/>
                                        Docentes
                                    </button>
                                    <button
                                        v-if="selectedModule"
                                        @click="openModalTheme"
                                        class="btn btn-primary text-xs uppercase"
                                    >
                                        <IconPlus class="w-4 h-4 mr-2"/>
                                        Nuevo Tema
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Temas -->
                        <div class="p-6">
                            <div v-if="dataThemes.length > 0" class="space-y-4">
                                <div
                                    v-for="(theme, key) in dataThemes"
                                    :key="theme.id"
                                    class="bg-white dark:bg-gray-750 border border-gray-200 dark:border-gray-600 rounded-xl p-5 cursor-pointer transition-all duration-300 hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-400 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 dark:hover:from-gray-700 dark:hover:to-purple-900 group"

                                >
                                    <!-- Cabecera del Tema -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-start gap-3 flex-1">
                                            <div class="bg-gradient-to-r from-indigo-500 to-purple-500 text-white p-2 rounded-lg flex-shrink-0 group-hover:scale-105 transition-transform">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2 text-base group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors">{{ theme.description }}</h3>
                                                <div class="flex items-center gap-4 text-sm">
                                                    <span class="flex items-center gap-1.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-full font-medium">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3h4v1a1 1 0 102 0V3a2 2 0 012-2v5a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
                                                        </svg>
                                                        {{ theme.contents?.length || 0 }} contenidos
                                                    </span>
                                                    <span class="flex items-center gap-1.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 px-2.5 py-1 rounded-full font-medium">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                                        </svg>
                                                        Posición {{ theme.position }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Estado del Tema -->
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center justify-end gap-2">
                                                <span
                                                    class="px-3 py-1 rounded-full text-xs font-medium transition-all duration-300"
                                                    :class="theme.contents?.length > 0
                                                        ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800'
                                                        : 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800'"
                                                >
                                                    {{ theme.contents?.length > 0 ? '✓ Contenido agregado' : '⚠ Sin contenido' }}
                                                </span>
                                            </div>
                                            <div class="flex flex-rows items-center justify-end gap-2">
                                                <button @click="openModalTheme(theme)"
                                                    type="button"
                                                    v-tippy="{content: 'Editar', placement: 'bottom'}"
                                                    class="p-1.5 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300 rounded-lg transition-colors hover:bg-blue-200 dark:hover:bg-blue-800/70 group">
                                                    <icon-pencil-paper class="w-4 h-4" />
                                                </button>
                                                <button @click="viewContents(theme, key)"
                                                    type="button"
                                                    v-tippy="{content: 'Contenido', placement: 'bottom'}"
                                                    class="p-1.5 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-600 dark:text-yellow-300 rounded-lg transition-colors hover:bg-yellow-200 dark:hover:bg-yellow-800/70 group">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                                        <path fill="currentColor" d="M320 205.3L320 514.6L320.5 514.4C375.1 491.7 433.7 480 492.8 480L512 480L512 160L492.8 160C450.6 160 408.7 168.4 369.7 184.6C352.9 191.6 336.3 198.5 320 205.3zM294.9 125.5L320 136L345.1 125.5C391.9 106 442.1 96 492.8 96L528 96C554.5 96 576 117.5 576 144L576 496C576 522.5 554.5 544 528 544L492.8 544C442.1 544 391.9 554 345.1 573.5L332.3 578.8C324.4 582.1 315.6 582.1 307.7 578.8L294.9 573.5C248.1 554 197.9 544 147.2 544L112 544C85.5 544 64 522.5 64 496L64 144C64 117.5 85.5 96 112 96L147.2 96C197.9 96 248.1 106 294.9 125.5z"/>
                                                    </svg>
                                                </button>
                                                <button @click="deleteTheme(theme.id)"
                                                    v-tippy="{content: 'Eliminar', placement: 'bottom'}"
                                                    type="button"
                                                    class="p-1.5 bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-300 rounded-lg transition-colors hover:bg-red-200 dark:hover:bg-red-800/70 group">
                                                    <icon-trash-lines class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Preview de Contenidos -->
                                    <div v-if="theme.contents?.length > 0" class="mt-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                        <div class="flex gap-2 flex-wrap items-center">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-1 w-full">Tipos de contenido:</span>
                                            <div
                                                v-for="content in theme.contents.slice(0, 5)"
                                                :key="content.id"
                                                class="w-8 h-8 bg-white dark:bg-gray-600 rounded-lg border border-gray-200 dark:border-gray-500 flex items-center justify-center shadow-sm hover:shadow-md transition-shadow"
                                                v-tippy="{ content: content.description , placement: 'bottom' }"
                                            >
                                                <svg v-if="content.is_file == '1'" class="w-4 h-4" :class="getContentTypeColor(content.is_file)" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                                    <path d="M451.5 160C434.9 160 418.8 164.5 404.7 172.7C388.9 156.7 370.5 143.3 350.2 133.2C378.4 109.2 414.3 96 451.5 96C537.9 96 608 166 608 252.5C608 294 591.5 333.8 562.2 363.1L491.1 434.2C461.8 463.5 422 480 380.5 480C294.1 480 224 410 224 323.5C224 322 224 320.5 224.1 319C224.6 301.3 239.3 287.4 257 287.9C274.7 288.4 288.6 303.1 288.1 320.8C288.1 321.7 288.1 322.6 288.1 323.4C288.1 374.5 329.5 415.9 380.6 415.9C405.1 415.9 428.6 406.2 446 388.8L517.1 317.7C534.4 300.4 544.2 276.8 544.2 252.3C544.2 201.2 502.8 159.8 451.7 159.8zM307.2 237.3C305.3 236.5 303.4 235.4 301.7 234.2C289.1 227.7 274.7 224 259.6 224C235.1 224 211.6 233.7 194.2 251.1L123.1 322.2C105.8 339.5 96 363.1 96 387.6C96 438.7 137.4 480.1 188.5 480.1C205 480.1 221.1 475.7 235.2 467.5C251 483.5 269.4 496.9 289.8 507C261.6 530.9 225.8 544.2 188.5 544.2C102.1 544.2 32 474.2 32 387.7C32 346.2 48.5 306.4 77.8 277.1L148.9 206C178.2 176.7 218 160.2 259.5 160.2C346.1 160.2 416 230.8 416 317.1C416 318.4 416 319.7 416 321C415.6 338.7 400.9 352.6 383.2 352.2C365.5 351.8 351.6 337.1 352 319.4C352 318.6 352 317.9 352 317.1C352 283.4 334 253.8 307.2 237.5z"/>
                                                </svg>
                                                <svg v-else-if="content.is_file == '0'" class="w-4 h-4" :class="getContentTypeColor(content.is_file)" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                                    <path d="M96 160C96 124.7 124.7 96 160 96L480 96C515.3 96 544 124.7 544 160L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 160zM144 432L144 464C144 472.8 151.2 480 160 480L192 480C200.8 480 208 472.8 208 464L208 432C208 423.2 200.8 416 192 416L160 416C151.2 416 144 423.2 144 432zM448 416C439.2 416 432 423.2 432 432L432 464C432 472.8 439.2 480 448 480L480 480C488.8 480 496 472.8 496 464L496 432C496 423.2 488.8 416 480 416L448 416zM144 304L144 336C144 344.8 151.2 352 160 352L192 352C200.8 352 208 344.8 208 336L208 304C208 295.2 200.8 288 192 288L160 288C151.2 288 144 295.2 144 304zM448 288C439.2 288 432 295.2 432 304L432 336C432 344.8 439.2 352 448 352L480 352C488.8 352 496 344.8 496 336L496 304C496 295.2 488.8 288 480 288L448 288zM144 176L144 208C144 216.8 151.2 224 160 224L192 224C200.8 224 208 216.8 208 208L208 176C208 167.2 200.8 160 192 160L160 160C151.2 160 144 167.2 144 176zM448 160C439.2 160 432 167.2 432 176L432 208C432 216.8 439.2 224 448 224L480 224C488.8 224 496 216.8 496 208L496 176C496 167.2 488.8 160 480 160L448 160z"/>
                                                </svg>
                                                <svg v-else-if="content.is_file == '3'" class="w-4 h-4" :class="getContentTypeColor(content.is_file)" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                                    <path d="M320 128C241 128 175.3 185.3 162.3 260.7C171.6 257.7 181.6 256 192 256L208 256C234.5 256 256 277.5 256 304L256 400C256 426.5 234.5 448 208 448L192 448C139 448 96 405 96 352L96 288C96 164.3 196.3 64 320 64C443.7 64 544 164.3 544 288L544 456.1C544 522.4 490.2 576.1 423.9 576.1L336 576L304 576C277.5 576 256 554.5 256 528C256 501.5 277.5 480 304 480L336 480C362.5 480 384 501.5 384 528L384 528L424 528C463.8 528 496 495.8 496 456L496 435.1C481.9 443.3 465.5 447.9 448 447.9L432 447.9C405.5 447.9 384 426.4 384 399.9L384 303.9C384 277.4 405.5 255.9 432 255.9L448 255.9C458.4 255.9 468.3 257.5 477.7 260.6C464.7 185.3 399.1 127.9 320 127.9z"/>
                                                </svg>
                                                <svg v-else-if="content.is_file == '2'" class="w-4 h-4" :class="getContentTypeColor(content.is_file)" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                                    <path d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM303 505C312.4 514.4 327.6 514.4 336.9 505L400.9 441C410.3 431.6 410.3 416.4 400.9 407.1C391.5 397.8 376.3 397.7 367 407.1L344 430.1L344 344C344 330.7 333.3 320 320 320C306.7 320 296 330.7 296 344L296 430.1L273 407.1C263.6 397.7 248.4 397.7 239.1 407.1C229.8 416.5 229.7 431.7 239.1 441L303.1 505z"/>
                                                </svg>
                                                <svg v-else class="w-4 h-4" :class="getContentTypeColor(content.is_file)" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                                    <path d="M128.1 64C92.8 64 64.1 92.7 64.1 128L64.1 512C64.1 547.3 92.8 576 128.1 576L274.3 576L285.2 521.5C289.5 499.8 300.2 479.9 315.8 464.3L448 332.1L448 234.6C448 217.6 441.3 201.3 429.3 189.3L322.8 82.7C310.8 70.7 294.5 64 277.6 64L128.1 64zM389.6 240L296.1 240C282.8 240 272.1 229.3 272.1 216L272.1 122.5L389.6 240zM332.3 530.9L320.4 590.5C320.2 591.4 320.1 592.4 320.1 593.4C320.1 601.4 326.6 608 334.7 608C335.7 608 336.6 607.9 337.6 607.7L397.2 595.8C409.6 593.3 421 587.2 429.9 578.3L548.8 459.4L468.8 379.4L349.9 498.3C341 507.2 334.9 518.6 332.4 531zM600.1 407.9C622.2 385.8 622.2 350 600.1 327.9C578 305.8 542.2 305.8 520.1 327.9L491.3 356.7L571.3 436.7L600.1 407.9z"/>
                                                </svg>
                                            </div>
                                            <div
                                                v-if="theme.contents.length > 5"
                                                class="w-8 h-8 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-600 dark:to-gray-700 rounded-lg border border-gray-300 dark:border-gray-500 flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-300"
                                            >
                                                +{{ theme.contents.length - 5 }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="empty-state">
                                <div class="text-center py-16 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-xl">
                                    <div class="w-20 h-20 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                        <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3h4v1a1 1 0 102 0V3a2 2 0 012-2v5a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">No hay temas disponibles</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">Comienza agregando tu primer tema para organizar el contenido del módulo de manera estructurada.</p>
                                    <button v-if="selectedModule" @click="openModalTheme()" class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white px-6 py-3 rounded-lg font-medium hover:from-blue-600 hover:to-indigo-600 transition-all duration-300 shadow-md hover:shadow-lg transform hover:scale-105 inline-flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                                        </svg>
                                        Crear Primer Tema
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<!-- Todos los modales existentes (sin cambios) -->
<!-- Modal para Temas -->
        <ModalLarge :show="displayThemeModal" :onClose="closeModalTheme" :icon="'/img/temas-importantes.png'">
            <template #title>{{ themeForm.module_name }}</template>
            <template #message>{{ themeForm.id ? 'Editar tema' : 'Nuevo tema' }}</template>
            <template #content>
                <div class="p-5">
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
                </div>
            </template>
            <template #buttons>
                <PrimaryButton @click="saveTheme" :class="{ 'opacity-25': btnThemeLoading }" :disabled="btnThemeLoading">
                    <svg v-show="btnThemeLoading" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                    </svg>
                    Guardar
                </PrimaryButton>
            </template>
        </ModalLarge>
<!-- Modal para Contenidos -->
         <ModalLargeX :show="viewTaskModal" :onClose="closeModalContents" :icon="'/img/material.png'">
            <template #title>{{ contentForm.theme_name }}</template>
            <template #message>Contenido del tema seleccionado</template>
            <template #content>
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
                            <div class="sm:col-span-4 flex justify-end">
                                <PrimaryButton :type="'button'" @click="saveContent" :class="{ 'opacity-25': btnContentLoading }" :disabled="btnContentLoading">
                                    <svg v-show="btnContentLoading" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                    </svg>
                                    Cuardar Contenido
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                    <div v-if="dataContents.length > 0" class="mt-6 p-4 h-64 overflow-y-auto rounded-lg bg-white border border-blue-50" >
                        <div class="px-6 py-4">
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
                                            <button @click="deleteContent(conte.id,hy)" type="button" class="mb-1 text-xs font-normal text-gray-400 sm:order-last sm:mb-0 uppercase">Eliminar</button>
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
                    </div>
                </div>
            </template>
        </ModalLargeX>
<!-- Modal para Módulos -->
        <ModalLarge :show="displayModuleModal" :onClose="closeModalModule" :icon="'/img/temas-importantes.png'">
            <template #title>Mantenimiento</template>
            <template #message>{{ moduleForm.id ? 'Editar Modulo' : 'Nuevo Modulo' }}</template>
            <template #content>
                <div class="p-5">
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
                </div>
            </template>
            <template #buttons>
                <PrimaryButton @click="saveModule" :class="{ 'opacity-25': btnModuleLoading }" :disabled="btnModuleLoading">
                    <svg v-show="btnModuleLoading" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                    </svg>
                    Guardar
                </PrimaryButton>
            </template>
        </ModalLarge>
<!-- Modal para Docentes -->
         <ModalLarge :show="displayModalDocents" :onClose="closeModalDocents">
            <template #title>{{ dataModalModule.description }}</template>
            <template #message>Docentes</template>
            <template #content>
                <div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center px-4 py-2">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Docentes
                            </h3>
                            <span class="text-xs font-medium text-gray-400">Seleccionar</span>
                        </div>

                        <div v-for="(row, go) in course.teachers" :key="go"
                            @click="course.teachers.length > 1 ? formModuleTeacher.teacher_id = row.teacher_id : null"
                            :class="[
                                'group flex items-center justify-between p-4 rounded-xl border transition-all duration-200 cursor-pointer',
                                formModuleTeacher.teacher_id === row.teacher_id
                                    ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20 shadow-sm'
                                    : 'border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-500 hover:shadow-md'
                            ]"
                        >
                            <div class="flex items-center space-x-4">
                                <div class="relative">
                                    <img v-if="row.teacher.person.image"
                                        class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm"
                                        :src="getPath(row.teacher.person.image)"
                                        :alt="row.teacher.person.names">
                                    <img v-else
                                        class="w-12 h-12 rounded-full border-2 border-white shadow-sm"
                                        :src="`https://ui-avatars.com/api/?name=${row.teacher.person.names}&background=random&color=fff`"
                                        :alt="row.teacher.person.names">

                                    <div v-if="formModuleTeacher.teacher_id === row.teacher_id"
                                        class="absolute -top-1 -right-1 w-4 h-4 bg-indigo-600 rounded-full border-2 border-white flex items-center justify-center">
                                        <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 transition-colors">
                                        {{ row.teacher.person.full_name }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ row.teacher.person.email }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input v-if="course.teachers.length > 1"
                                    type="radio"
                                    :id="`radio-teacher-${go}`"
                                    :value="row.teacher_id"
                                    v-model="formModuleTeacher.teacher_id"
                                    class="w-5 h-5 text-indigo-600 bg-gray-100 border-gray-300 focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                />

                                <input v-else
                                    type="checkbox"
                                    :id="`checkbox-teacher-${go}`"
                                    :checked="formModuleTeacher.teacher_id === row.teacher_id"
                                    @change="toggleTeacher(row.teacher_id, $event)"
                                    class="w-5 h-5 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                />
                            </div>
                        </div>
                    </div>
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

<!-- Modal para Exámenes -->
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
                        <button @click="saveExam" v-can="'aca_cursos_examen_configuracion'" type="button" class="btn btn-primary text-xs">
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
