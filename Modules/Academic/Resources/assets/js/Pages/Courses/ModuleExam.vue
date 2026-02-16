<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Link, useForm, router } from '@inertiajs/vue3';
    import { ref, onMounted, computed, watch } from 'vue';
    import axios from 'axios';
    import Swal2 from 'sweetalert2';
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import IconPencilPaper from '@/Components/vristo/icon/icon-pencil-paper.vue';
    import IconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
    import IconCheck from '@/Components/vristo/icon/icon-check.vue';
    import IconX from '@/Components/vristo/icon/icon-x.vue';
    import InputError from '@/Components/InputError.vue';
    import ModalSmall from "@/Components/ModalSmall.vue";
    import InputLabel from "@/Components/InputLabel.vue";
    import TextInput from "@/Components/TextInput.vue";
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import SecondaryButton from '@/Components/SecondaryButton.vue';
    import SpinnerLoading from '@/Components/SpinnerLoading.vue';

    const props = defineProps({
        exam: {
            type: Object,
            default: () => ({}),
        }
    });

    const selectedQuestion = ref(null);
    const showQuestionModal = ref(false);
    const showAnswerModal = ref(false);

    const questionForm = useForm({
        id: null,
        exam_id: props.exam.id,
        description: null,
        score: 5,
        type_answers: 'Escribir',
    });

    const answerForm = useForm({
        id: null,
        question_id: null,
        description: null,
        correct: 0,
        score: 0,
        question_description: null
    });

    const typeOptions = [
        { value: 'Escribir', label: 'Escribir' },
        { value: 'Alternativas', label: 'Alternativas' },
        { value: 'Varias respuestas', label: 'Varias respuestas' },
        { value: 'Subir Archivo', label: 'Subir Archivo' },
    ];

    const openModalQuestion = (question = null) => {
        if (question) {
            questionForm.id = question.id;
            questionForm.description = question.description;
            questionForm.score = question.score;
            questionForm.type_answers = question.type_answers;
        } else {
            questionForm.reset();
            questionForm.exam_id = props.exam.id;
            questionForm.score = 5;
            questionForm.type_answers = 'Alternativas';
        }
        showQuestionModal.value = true;
    };

    const closeModalQuestion = () => {
        questionForm.reset();
        showQuestionModal.value = false;
    };

    const saveQuestion = () => {

        const isEditing = !!questionForm.id;

        questionForm.post(route('aca_course_exam_question_form_store'), {
            preserveScroll: true,
            errorBag: 'saveQuestion',
            onSuccess: () => {
                questionForm.reset();
                Swal2.fire({
                    title: '¡Éxito!',
                    text: isEditing ? 'Pregunta actualizada' : 'Pregunta creada',
                    icon: 'success',
                    padding: '2em',
                });
                closeModalQuestion();
            },
        });

    };

    const deleteQuestion = (question) => {
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
                return axios.delete(route('aca_course_exam_question_destroy', question.id)).then((res) => {
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
                refreshDatos();
            }
        });
    };

    const selectQuestion = (question) => {
        selectedQuestion.value = question;
        answerForm.question_id = question.id;
    };

    const openModalAnswer = (answer = null) => {

        if (answer) {
            answerForm.id = answer.id;
            answerForm.description = answer.description;
            answerForm.correct = answer.correct;
            answerForm.score = answer.score;
        } else {
            answerForm.reset();
            answerForm.question_description = selectedQuestion.value.description;
            answerForm.question_id = selectedQuestion.value.id;
            answerForm.score = selectedQuestion.value.score;
            answerForm.correct = selectedQuestion.value.type_answers === 'Escribir' || selectedQuestion.value.type_answers === 'Subir Archivo' ? 0 : 0;
        }
        showAnswerModal.value = true;
    };

    const closeModalAnswer = () => {
        answerForm.reset();
        showAnswerModal.value = false;
    };

    const saveAnswer = () => {
        if (!answerForm.description) {
            answerForm.setError('description','La descripción es requerida');
            return;
        }

        if (!selectedQuestion.value) {
            Swal2.fire({ title: 'Error', text: 'Selecciona una pregunta', icon: 'error', padding: '2em' });
            return;
        }

        if (selectedQuestion.value.type_answers === 'Alternativas') {
            const existingCorrect = selectedQuestion.value.answers?.filter(a => a.correct).length || 0;
            if (answerForm.correct && existingCorrect > 0 && !answerForm.id) {
                Swal2.fire({ title: 'Error', text: 'Ya existe una respuesta correcta en alternativas', icon: 'error', padding: '2em' });
                return;
            }
        }

        if (selectedQuestion.value.type_answers === 'Escribir' || selectedQuestion.value.type_answers === 'Subir Archivo') {
            if (selectedQuestion.value.answers?.length > 0 && !answerForm.id) {
                Swal2.fire({ title: 'Error', text: `Solo puede tener una respuesta para ${selectedQuestion.value.type_answers}`, icon: 'error', padding: '2em' });
                return;
            }
            answerForm.score = selectedQuestion.value.score;
            answerForm.correct = 0;
        } else if (answerForm.correct) {
            answerForm.score = selectedQuestion.value.score;
        } else {
            answerForm.score = 0;
        }

        answerForm.processing = true;
        axios({
            method: 'POST',
            url: route('aca_course_exam_answer_store'),
            data: answerForm
        }).then((result)=> {
            if (!selectedQuestion.value.answers) {
                selectedQuestion.value.answers = [];
            }
            let newAnswer = result.data.answer;
            if (answerForm.id) {

                const index = selectedQuestion.value.answers.findIndex(a => a.id === answerForm.id);
                if (index !== -1) {
                    selectedQuestion.value.answers[index] = newAnswer;
                }
            } else {
                selectedQuestion.value.answers.push(newAnswer);
            }
            Swal2.fire({
                title: result.data.title,
                text: result.data.message,
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }).catch(function (error) {
            //console.log(error);
        }).finally(() => {
            closeModalAnswer();
            answerForm.processing = false;
            refreshDatos();
        });


    };

    const deleteAnswer = (answer) => {
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
                return axios.delete(route('aca_course_exam_answer_destroy', answer.id)).then((res) => {
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
                selectedQuestion.value.answers = selectedQuestion.value.answers.filter(a => a.id !== answer.id);
            }
        });
    };

    const refreshDatos = () => {
        router.visit(route('aca_course_module_exam_view_details', [props.exam.course.id, props.exam.module.id, props.exam.id]), {
            method: 'get',
            replace: true,
            preserveState: true,
            preserveScroll: true,
            only: ['exam']
        });
    };

    const getTypeColor = (type) => {
        const colors = {
            'Escribir': 'bg-blue-500 text-white',
            'Alternativas': 'bg-green-500 text-white',
            'Varias respuestas': 'bg-purple-500 text-white',
            'Subir Archivo': 'bg-orange-500 text-white',
        };
        return colors[type] || 'bg-gray-500 text-white';
    };

</script>

<template>
    <AppLayout title="Gestión de Examen">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {
                    route: route('aca_courses_list'),
                    title: 'Cursos',
                    children: [
                        {route: route('aca_enrolledstudents_list', exam.course.id), title: 'Alumnos'},
                        {route: route('aca_courses_information', exam.course.id), title: 'Información'},
                        {route: route('aca_courses_edit', exam.course.id), title: 'Editar'}
                    ],
                },
                {route: route('aca_courses_edit', exam.course.id), title: exam.course.description},
                {title: 'Módulos'},
                {title: exam.module.description},
                {title: exam.description},
            ]"
        />

        <div class="pt-5 space-y-8 relative">
            <!-- Header del Examen -->
            <div class="panel">
                <div class="flex items-center justify-between py-3 px-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ exam.description }}</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ exam.course.description }} | {{ exam.module.description }}</p>
                    </div>
                    <button @click="openModalQuestion()" class="btn btn-primary text-xs uppercase">
                        <IconPlus class="w-3.5 h-3.5 mr-1" />
                        Nueva Pregunta
                    </button>
                </div>
            </div>

            <!-- Panel Principal -->
            <div class="grid grid-cols-12 gap-3">
                <!-- Lista de Preguntas -->
                <div class="col-span-4">
                    <div class="panel border-0 rounded-lg">
                        <div class="py-2 px-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Preguntas ({{ exam.questions.length }})</h3>
                        </div>
                        <div>
                            <div v-if="exam.questions.length === 0" class="p-3 text-center text-gray-500 text-xs">
                                No hay preguntas aún
                            </div>
                            <div
                                v-for="(question, index) in exam.questions"
                                :key="question.id"
                                @click="selectQuestion(question)"
                                class="p-2.5 border-b border-gray-100 dark:border-gray-700 cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="{'bg-blue-50 dark:bg-blue-900/20': selectedQuestion?.id === question.id}"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start gap-2 flex-1 min-w-0">
                                        <span class="text-xs font-medium text-gray-400 w-4">{{ index + 1 }}.</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs text-gray-900 dark:text-white truncate">{{ question.description }}</p>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span :class="getTypeColor(question.type_answers)" class="text-[10px] px-1.5 py-0.5 rounded">
                                                    {{ question.type_answers }}
                                                </span>
                                                <span class="text-[10px] text-gray-500">{{ question.score }} pts</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-0.5">
                                        <button @click.stop="openModalQuestion(question)" class="p-1 text-blue-500 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded">
                                            <IconPencilPaper class="w-3 h-3" />
                                        </button>
                                        <button @click.stop="deleteQuestion(question)" class="p-1 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/50 rounded">
                                            <IconTrashLines class="w-3 h-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalles de Pregunta y Respuestas -->
                <div class="col-span-8">
                    <div v-if="selectedQuestion" class="space-y-3">
                        <!-- Pregunta Seleccionada -->
                        <div class="panel border-0 rounded-lg">
                            <div class="py-2 px-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span :class="getTypeColor(selectedQuestion.type_answers)" class="text-[10px] px-1.5 py-0.5 rounded font-medium">
                                        {{ selectedQuestion.type_answers }}
                                    </span>
                                    <span class="text-xs font-medium text-gray-900 dark:text-white">{{ selectedQuestion.score }} puntos</span>
                                </div>
                                <button
                                    @click="openModalAnswer()"
                                    class="btn btn-success btn-xs"
                                >
                                    <IconPlus class="w-3 h-3 mr-1" />
                                    Respuesta
                                </button>
                            </div>
                            <div class="p-3">
                                <p class="text-xs font-medium text-gray-900 dark:text-white mb-1">Pregunta:</p>
                                <p class="text-xs text-gray-700 dark:text-gray-300">{{ selectedQuestion.description }}</p>
                            </div>
                        </div>

                        <!-- Lista de Respuestas -->
                        <div class="panel border-0 rounded-lg">
                            <div class="py-2 px-3 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-xs font-semibold text-gray-900 dark:text-white">
                                    Respuestas ({{ selectedQuestion.answers?.length || 0 }})
                                </h3>
                            </div>
                            <div class="">
                                <div v-if="!selectedQuestion.answers?.length" class="p-3 text-center text-gray-500 text-xs">
                                    No hay respuestas aún
                                </div>
                                <div
                                    v-for="(answer, aIndex) in selectedQuestion.answers"
                                    :key="answer.id"
                                    class="p-2.5 border-b border-gray-100 dark:border-gray-700"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start gap-2 flex-1">
                                            <div
                                                v-if="selectedQuestion.type_answers === 'Alternativas' || selectedQuestion.type_answers === 'Varias respuestas'"
                                                class="w-3.5 h-3.5 mt-0.5 rounded flex items-center justify-center flex-shrink-0"
                                                :class="answer.correct ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-600'"
                                            >
                                                <IconCheck v-if="answer.correct" class="w-2 h-2" />
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-xs text-gray-900 dark:text-white">{{ answer.description }}</p>
                                                <p class="text-[10px] text-gray-500 mt-0.5">{{ answer.score }} puntos</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-0.5">
                                            <button @click="openModalAnswer(answer)" class="p-1 text-blue-500 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded">
                                                <IconPencilPaper class="w-3 h-3" />
                                            </button>
                                            <button @click="deleteAnswer(answer)" class="p-1 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/50 rounded">
                                                <IconTrashLines class="w-3 h-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Previsualización -->
                        <div class="panel border-0 rounded-lg">
                            <div class="py-2 px-3 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-xs font-semibold text-gray-900 dark:text-white">Previsualización</h3>
                            </div>
                            <div class="p-3">
                                <div v-if="selectedQuestion.type_answers === 'Escribir'">
                                    <template v-for="(answer, idx) in selectedQuestion.answers">
                                        <p class="text-xs font-medium text-gray-900 dark:text-white mb-2">{{ answer.description }}</p>
                                        <textarea class="form-textarea w-full text-xs" rows="2" placeholder="Escribe tu respuesta aquí..." disabled></textarea>
                                        <p class="text-[10px] text-gray-500 mt-1">Puntaje máximo: {{ selectedQuestion.score }} puntos</p>
                                    </template>
                                </div>

                                <div v-else-if="selectedQuestion.type_answers === 'Alternativas'">
                                    <p class="text-xs font-medium text-gray-900 dark:text-white mb-2">{{ selectedQuestion.description }}</p>
                                    <div class="space-y-1">
                                        <label
                                            v-for="(answer, idx) in selectedQuestion.answers"
                                            :key="idx"
                                            class="flex items-center gap-2 p-1.5 border border-gray-200 dark:border-gray-600 rounded cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                        >
                                            <input type="radio" :name="'preview-' + selectedQuestion.id" class="w-3.5 h-3.5 text-blue-500" disabled>
                                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ answer.description }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div v-else-if="selectedQuestion.type_answers === 'Varias respuestas'">
                                    <p class="text-xs font-medium text-gray-900 dark:text-white mb-2">{{ selectedQuestion.description }}</p>
                                    <div class="space-y-1">
                                        <label
                                            v-for="(answer, idx) in selectedQuestion.answers"
                                            :key="idx"
                                            class="flex items-center gap-2 p-1.5 border border-gray-200 dark:border-gray-600 rounded cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                        >
                                            <input type="checkbox" :name="'preview-' + selectedQuestion.id" class="w-3.5 h-3.5 text-blue-500" disabled>
                                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ answer.description }}</span>
                                        </label>
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-1">Selecciona todas las respuestas correctas</p>
                                </div>

                                <div v-else-if="selectedQuestion.type_answers === 'Subir Archivo'">
                                    <p class="text-xs font-medium text-gray-900 dark:text-white mb-2">{{ selectedQuestion.description }}</p>
                                    <div class="p-3 border border-dashed border-gray-300 dark:border-gray-600 rounded text-center">
                                        <svg class="w-6 h-6 mx-auto text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        <p class="text-[10px] text-gray-500">Subir archivo PDF</p>
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-1">Puntaje máximo: {{ selectedQuestion.score }} puntos</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="panel border-0 rounded-lg p-6 text-center">
                        <svg class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Selecciona una pregunta para ver sus respuestas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Pregunta -->
        <ModalSmall :show="showQuestionModal" :onClose="closeModalQuestion" icon="/img/signo-de-interrogacion.png">
            <template #title>
                {{ questionForm.id ? 'Editar Pregunta' : 'Nueva Pregunta' }}
            </template>
            <template #content>
                <div class="space-y-3">
                    <div>
                        <InputLabel for="type_answers" value="Tipo de Respuesta" />
                        <select v-model="questionForm.type_answers" class="form-select mt-1 block w-full">
                            <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <InputError :message="questionForm.errors.type_answers" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="question_description" value="Descripción de la Pregunta" />
                        <textarea v-model="questionForm.description" rows="2" class="form-textarea mt-1 block w-full" placeholder="Escribe la pregunta..."></textarea>
                        <InputError :message="questionForm.errors.description" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="question_score" value="Puntaje" />
                        <TextInput id="question_score" type="number" v-model="questionForm.score" min="1" class="mt-1 block w-full" />
                        <InputError :message="questionForm.errors.score" class="mt-2" />
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :class="{ 'opacity-25': questionForm.processing }" :disabled="questionForm.processing" type="button" @click="saveQuestion">
                    <SpinnerLoading :display="questionForm.processing" />
                    Guardar
                </PrimaryButton>
            </template>
        </ModalSmall>

        <!-- Modal de Respuesta -->
        <ModalSmall :show="showAnswerModal" :onClose="closeModalAnswer" icon="/img/pregunta-y-respuesta.png">
            <template #title>
                {{ answerForm.id ? 'Editar Respuesta' : 'Nueva Respuesta' }}
            </template>
            <template #content>
                <div class="space-y-3">
                    <div>
                        <InputLabel for="answer_description" value="Descripción de la Respuesta" />
                        <textarea v-model="answerForm.description" rows="2" class="form-textarea mt-1 block w-full" placeholder="Escribe la respuesta..."></textarea>
                        <InputError :message="answerForm.errors.description" class="mt-2" />
                    </div>

                    <div v-if="selectedQuestion?.type_answers === 'Alternativas' || selectedQuestion?.type_answers === 'Varias respuestas'">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="answerForm.correct" :true-value="1" :false-value="0" class="w-4 h-4 text-blue-500 rounded">
                            <span class="text-sm text-gray-700 dark:text-gray-300">¿Es respuesta correcta?</span>
                        </label>

                        <p class="text-xs text-gray-500 mt-1" v-if="selectedQuestion?.type_answers === 'Varias respuestas' && answerForm.correct">
                            Los puntos se dividirán entre {{ (selectedQuestion?.answers?.filter(a => a.correct).length || 0) + (answerForm.correct ? 1 : 0) }} respuestas correctas
                        </p>
                    </div>

                    <div v-else class="bg-gray-50 dark:bg-gray-700 p-2 rounded-lg">
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            <span v-if="selectedQuestion?.type_answers === 'Escribir'">Esta pregunta será calificada manualmente por el docente.</span>
                            <span v-else-if="selectedQuestion?.type_answers === 'Subir Archivo'">El docente revisará el archivo subido y asignará la puntuación.</span>
                        </p>
                    </div>
                    <InputError :message="answerForm.errors.correct" class="mt-2" />
                </div>
            </template>
            <template #buttons>
                <PrimaryButton
                    :class="{ 'opacity-25': answerForm.processing }" :disabled="answerForm.processing"
                    type="button" @click="saveAnswer"
                >
                    <SpinnerLoading :display="answerForm.processing" />
                    Guardar
                </PrimaryButton>
            </template>
        </ModalSmall>
    </AppLayout>
</template>
