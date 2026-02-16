<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Link, useForm, router } from '@inertiajs/vue3';
    import { ref, onMounted, computed, onUnmounted } from 'vue';
    import axios from 'axios';
    import Swal2 from 'sweetalert2';
    import IconCheck from '@/Components/vristo/icon/icon-check.vue';
    import IconX from '@/Components/vristo/icon/icon-x.vue';
    import IconArrowLeft from '@/Components/vristo/icon/icon-arrow-left.vue';
    import IconArrowRight from '@/Components/vristo/icon/icon-arrow-right.vue';

    const props = defineProps({
        exam: { type: Object, default: () => ({}) },
        isSuccess: { type: Boolean, default: false },
        examStudent: { type: Object, default: () => ({}) }
    });

    // Estado del examen
    const currentQuestionIndex = ref(0);
    const answers = ref({});
    const timeRemaining = ref(0);
    const timerInterval = ref(null);
    const isFinished = ref(false);
    const savingAnswer = ref(false);

    // Computed
    const currentQuestion = computed(() => props.exam.questions[currentQuestionIndex.value]);
    const totalQuestions = computed(() => props.exam.questions?.length || 0);
    const answeredCount = computed(() => Object.keys(answers.value).length);
    const progressPercent = computed(() => totalQuestions.value > 0 ? Math.round((answeredCount.value / totalQuestions.value) * 100) : 0);

    const formattedTime = computed(() => {
        const hours = Math.floor(timeRemaining.value / 3600);
        const minutes = Math.floor((timeRemaining.value % 3600) / 60);
        const seconds = timeRemaining.value % 60;
        return hours > 0
            ? `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
            : `${minutes}:${seconds.toString().padStart(2, '0')}`;
    });

    const isTimeWarning = computed(() => timeRemaining.value <= 300 && timeRemaining.value > 60);
    const isTimeCritical = computed(() => timeRemaining.value <= 60);

    // Timer
    const startTimer = () => {
        if (timerInterval.value) clearInterval(timerInterval.value);
        timerInterval.value = setInterval(() => {
            if (timeRemaining.value > 0) {
                timeRemaining.value--;
            } else {
                finishExam();
            }
        }, 1000);
    };

    const stopTimer = () => {
        if (timerInterval.value) {
            clearInterval(timerInterval.value);
            timerInterval.value = null;
        }
    };

    const getCurrentAnswer = computed(() => {
        if (!currentQuestion.value) return null;
        return answers.value[currentQuestion.value.id] || null;
    });

    const hasCurrentAnswer = computed(() => {
        const ans = getCurrentAnswer.value;
        if (!ans) return false;
        const type = currentQuestion.value.type_answers;

        if (type === 'Escribir') return !!ans.description?.trim();
        if (type === 'Subir Archivo') return !!ans.file;
        if (type === 'Varias respuestas') return ans.answerIds?.length > 0;
        return ans.answerId !== undefined && ans.answerId !== null;
    });

    const selectAnswer = (answerId) => {
        const qId = currentQuestion.value.id;
        const type = currentQuestion.value.type_answers;

        if (type === 'Alternativas') {
            answers.value[qId] = { answerId: answerId };
        }
        else if (type === 'Varias respuestas') {
            if (!answers.value[qId]) {
                answers.value[qId] = { answerIds: [] };
            }

            const currentIds = [...(answers.value[qId].answerIds || [])];
            const index = currentIds.indexOf(answerId);

            if (index === -1) {
                // BUSCAR EL LÍMITE: Contamos cuántas respuestas marcadas como 'correct' tiene la pregunta
                const maxAllowed = currentQuestion.value.answers.filter(a => a.correct == 1 || a.correct == true).length;

                if (currentIds.length < maxAllowed) {
                    currentIds.push(answerId);
                } else {
                    // Opcional: Alerta al usuario
                    Swal2.fire({
                        title: 'Límite alcanzado',
                        text: `Esta pregunta solo permite seleccionar ${maxAllowed} respuestas.`,
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            } else {
                currentIds.splice(index, 1);
            }
            answers.value[qId].answerIds = currentIds;
        }
    };

    const isAnswerSelected = (answerId) => {
        const ans = getCurrentAnswer.value;
        if (!ans) return false;
        const type = currentQuestion.value.type_answers;
        if (type === 'Alternativas') return ans.answerId === answerId;
        if (type === 'Varias respuestas') return (ans.answerIds || []).includes(answerId);
        return false;
    };

    const updateDescription = (value) => {
        const qId = currentQuestion.value.id;
        if (!answers.value[qId]) answers.value[qId] = {};
        answers.value[qId].description = value;
    };

    const saveAndContinue = async () => {
        if (!hasCurrentAnswer.value) {
            Swal2.fire({ title: 'Atención', text: 'Debes responder la pregunta para continuar', icon: 'warning' });
            return;
        }

        savingAnswer.value = true;
        const qId = currentQuestion.value.id;
        const type = currentQuestion.value.type_answers;
        const answerData = answers.value[qId];

        const formData = new FormData();
        formData.append('exam_id', props.exam.id);
        formData.append('student_exam_id', props.examStudent.id);
        formData.append('question_id', qId);
        formData.append('question_type', type);

        // Lógica de empaquetado según tipo
        if (type === 'Subir Archivo' && answerData?.file) {
            formData.append('file_answer', answerData.file);
            formData.append('answers', answerData.answerId);
        } else if (type === 'Alternativas') {
            formData.append('answers', answerData.answerId);
        } else if (type === 'Escribir') {
            formData.append('answers', answerData.description);
        } else if (type === 'Varias respuestas') {
            answerData.answerIds.forEach(id => formData.append('answers[]', id));
        }

        try {
            await axios.post(route('aca_student_module_exam_answer_save'), formData);
            if (currentQuestionIndex.value < totalQuestions.value - 1) {
                currentQuestionIndex.value++;
            } else {
                Swal2.fire({
                    title: '¡Última pregunta!',
                    text: 'Has respondido todas las preguntas. ¿Deseas terminar el examen?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Terminar Examen',
                    cancelButtonText: 'Revisar',
                }).then((result) => {
                    if (result.isConfirmed) finishExam();
                });
            }
        } catch (error) {
            Swal2.fire({ title: 'Error', text: 'Error al guardar la respuesta', icon: 'error' });
        } finally {
            savingAnswer.value = false;
        }
    };

    const goToQuestion = (index) => { currentQuestionIndex.value = index; };
    const previousQuestion = () => { if (currentQuestionIndex.value > 0) currentQuestionIndex.value--; };

    const finishExam = () => {
        stopTimer();
        isFinished.value = true;
        router.post(route('aca_student_exam_finish', props.examStudent.id), {
            time_used: props.exam.duration_minutes * 60 - timeRemaining.value
        });
    };

    const onFileChange = (event, id) => {
        const qId = currentQuestion.value.id;
        const file = event.target.files[0];
        if (file && file.type === 'application/pdf') {
            answers.value[qId] = { answerId: id, file: file };
        }
    };

    // Cargar respuestas guardadas desde el backend
    const loadSavedAnswers = () => {
        if (props.examStudent?.details && typeof props.examStudent.details === 'object') {
            const savedDetails = props.examStudent.details;
            
            // Mapear las respuestas guardadas al formato esperado por el componente
            Object.keys(savedDetails).forEach(questionId => {
                const savedAnswer = savedDetails[questionId];
                
                if (savedAnswer.type === 'Alternativas' && savedAnswer.answer_id) {
                    answers.value[questionId] = { answerId: savedAnswer.answer_id };
                } 
                else if (savedAnswer.type === 'Varias respuestas' && savedAnswer.answers_ids) {
                    answers.value[questionId] = { answerIds: savedAnswer.answers_ids };
                } 
                else if (savedAnswer.type === 'Escribir' && savedAnswer.description) {
                    answers.value[questionId] = { description: savedAnswer.description };
                }
                // Para Subir Archivo, no se puede persistir el archivo en sí
            });
            
            // Ir a la última pregunta respondida
            const answeredQuestionIds = Object.keys(answers.value);
            if (answeredQuestionIds.length > 0) {
                const lastAnsweredQuestionId = answeredQuestionIds[answeredQuestionIds.length - 1];
                const questionIndex = props.exam.questions.findIndex(q => q.id == lastAnsweredQuestionId);
                if (questionIndex !== -1) {
                    currentQuestionIndex.value = questionIndex;
                }
            }
        }
    };

    // Calcular tiempo restante basado en la hora de inicio
    const calculateTimeRemaining = () => {
        if (!props.examStudent?.started_at) {
            // Si no hay hora de inicio, usar la duración normal
            return props.exam.duration_minutes * 60;
        }

        const startTime = new Date(props.examStudent.started_at).getTime();
        const durationMs = props.exam.duration_minutes * 60 * 1000;
        const endTime = startTime + durationMs;
        const now = new Date().getTime();
        
        const remaining = Math.floor((endTime - now) / 1000);
        return remaining > 0 ? remaining : 0;
    };

    // Inicializar estado del examen
    const initExam = () => {
        if (!props.isSuccess) return;
        
        // Cargar respuestas guardadas
        loadSavedAnswers();
        
        // Calcular tiempo restante
        timeRemaining.value = calculateTimeRemaining();
        
        // Verificar si el tiempo ya expiró
        if (timeRemaining.value <= 0) {
            finishExam();
            return;
        }
        
        // Iniciar temporizador
        startTimer();
    };

    onMounted(() => {
        initExam();
    });
    onUnmounted(() => stopTimer());
</script>
<template>
    <AppLayout :title="exam.description">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_mycourses'), title: 'Cursos'},
                {route: route('aca_mycourses_lessons',exam.module.course.id), title: exam.module.course.description},
                {title: exam.module.description},
                {title: exam.description}
            ]"
        />

        <!-- Mensaje si el examen no está disponible -->
        <div v-if="!isSuccess" class="pt-5">
            <div class="bg-red-100 text-red-800 p-6 rounded-lg text-center border border-red-200">
                <svg class="w-12 h-12 mx-auto mb-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <h2 class="text-xl font-bold mb-2">Examen no disponible</h2>
                <p class="text-sm">El examen está fuera del horario establecido</p>
                <Link :href="route('aca_mycourses_lessons', exam.module.course.id)" class="inline-block mt-4 bg-white text-red-500 px-4 py-2 rounded-lg font-medium">
                    Volver al curso
                </Link>
            </div>
        </div>

        <!-- Examen -->
        <div v-else class="pt-5 px-4 pb-20">
            <!-- Cronómetro Flotante -->
            <div class="fixed top-20 right-4 z-40 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-3 min-w-[120px]">
                <div class="text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Tiempo restante</p>
                    <p class="text-2xl font-bold"
                       :class="{
                           'text-green-500': !isTimeWarning && !isTimeCritical,
                           'text-yellow-500': isTimeWarning,
                           'text-red-500 animate-pulse': isTimeCritical
                       }">
                        {{ formattedTime }}
                    </p>
                </div>
            </div>

            <!-- Header del Examen -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4 mb-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ exam.description }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Módulo: {{ exam.module.description }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Respondidas</p>
                            <p class="text-lg font-bold text-blue-500">{{ answeredCount }}/{{ totalQuestions }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Progreso</p>
                            <p class="text-lg font-bold text-green-500">{{ progressPercent }}%</p>
                        </div>
                    </div>
                </div>
                <!-- Barra de progreso -->
                <div class="mt-3 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                         :style="{ width: progressPercent + '%' }"></div>
                </div>
            </div>

            <!-- Pregunta Actual -->
            <div v-if="currentQuestion" class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4 mb-4">
                <!-- Número de pregunta -->
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                    <span class="bg-blue-500 text-white text-sm px-3 py-1 rounded-full font-medium">
                        Pregunta {{ currentQuestionIndex + 1 }} de {{ totalQuestions }}
                    </span>
                    <span :class="{
                        'bg-blue-500 text-white': currentQuestion.type_answers === 'Escribir',
                        'bg-green-500 text-white': currentQuestion.type_answers === 'Alternativas',
                        'bg-purple-500 text-white': currentQuestion.type_answers === 'Varias respuestas',
                        'bg-orange-500 text-white': currentQuestion.type_answers === 'Subir Archivo',
                    }" class="text-xs px-2 py-1 rounded font-medium">
                        {{ currentQuestion.type_answers }}
                    </span>
                </div>

                <!-- Descripción de la pregunta -->
                <div class="mb-4">
                    <p class="text-base font-medium text-gray-900 dark:text-white">{{ currentQuestion.description }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ currentQuestion.score }} puntos</p>
                </div>

                <!-- Respuestas según tipo -->
                <div class="space-y-3">
                    <!-- Tipo: Escribir -->
                    <div v-if="currentQuestion.type_answers === 'Escribir'">
                        <textarea
                            :value="getCurrentAnswer?.description || ''"
                            @input="updateDescription($event.target.value)"
                            class="form-textarea w-full"
                            rows="4"
                            placeholder="Escribe tu respuesta aquí..."
                        ></textarea>
                    </div>

                    <!-- Tipo: Subir Archivo -->
                    <div v-else-if="currentQuestion.type_answers === 'Subir Archivo'">
                        <template v-for="(answer, key) in currentQuestion.answers">
                            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">
                                <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="text-sm text-gray-500">{{ answer.description }}</p>
                                <input @change="onFileChange($event, answer.id)" :id="'file_input'+key" type="file" accept=".pdf" class="mt-2" />
                            </div>
                        </template>
                    </div>

                    <!-- Tipo: Alternativas (Radio) -->
                    <div v-else-if="currentQuestion.type_answers === 'Alternativas'" class="space-y-2">
                        <label
                            v-for="answer in currentQuestion.answers"
                            :key="answer.id"
                            class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
                            :class="isAnswerSelected(answer.id)
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        >
                            <input
                                type="radio"
                                :name="'question-' + currentQuestion.id"
                                :checked="isAnswerSelected(answer.id)"
                                @change="selectAnswer(answer.id)"
                                class="w-4 h-4 text-blue-500"
                            >
                            <span class="text-sm text-gray-900 dark:text-white">{{ answer.description }}</span>
                        </label>
                    </div>

                    <!-- Tipo: Varias respuestas (Checkbox) -->
                    <div v-else-if="currentQuestion.type_answers === 'Varias respuestas'" class="space-y-2">
                        <p class="text-xs text-gray-500 mb-2">Selecciona todas las respuestas que consideres correctas</p>
                        <label
                            v-for="answer in currentQuestion.answers"
                            :key="answer.id"
                            class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
                            :class="[
                                isAnswerSelected(answer.id) ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700',
                                // Clase para mostrar que está bloqueado si no está seleccionado y ya llegó al límite
                                (!isAnswerSelected(answer.id) && (getCurrentAnswer?.answerIds?.length >= currentQuestion.answers.filter(a => a.correct).length)) ? 'opacity-50 cursor-not-allowed' : ''
                            ]"

                        >
                            <input
                                type="checkbox"
                                :checked="isAnswerSelected(answer.id)"
                                @change="selectAnswer(answer.id)"
                                class="w-4 h-4 text-purple-500 rounded"
                                :disabled="!isAnswerSelected(answer.id) && (getCurrentAnswer?.answerIds?.length >= currentQuestion.answers.filter(a => a.correct).length)"
                            >
                            <span class="text-sm text-gray-900 dark:text-white">{{ answer.description }}</span>
                        </label>
                    </div>
                </div>

                <!-- Botones de navegación -->
                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                        @click="previousQuestion"
                        :disabled="currentQuestionIndex === 0"
                        class="flex items-center gap-1 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <IconArrowLeft class="w-4 h-4" />
                        Anterior
                    </button>

                    <button
                        @click="saveAndContinue"
                        :disabled="savingAnswer || !hasCurrentAnswer"
                        class="flex items-center gap-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg text-sm font-medium"
                    >
                        <span v-if="savingAnswer">Guardando...</span>
                        <span v-else-if="currentQuestionIndex === totalQuestions - 1">Terminar Examen</span>
                        <span v-else>Guardar y Continuar</span>
                        <IconArrowRight v-if="!savingAnswer && currentQuestionIndex < totalQuestions - 1" class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Paginador de Preguntas -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Navegar entre preguntas:</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="(question, index) in exam.questions"
                        :key="question.id"
                        @click="goToQuestion(index)"
                        class="w-10 h-10 rounded-lg text-sm font-medium transition-colors flex items-center justify-center"
                        :class="{
                            'bg-blue-500 text-white': currentQuestionIndex === index,
                            'bg-green-500 text-white': answers[question.id] && currentQuestionIndex !== index,
                            'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600': !answers[question.id] && currentQuestionIndex !== index
                        }"
                    >
                        {{ index + 1 }}
                    </button>
                </div>
                <div class="flex items-center gap-4 mt-3 text-xs">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-blue-500 rounded"></div>
                        <span class="text-gray-500">Actual</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-green-500 rounded"></div>
                        <span class="text-gray-500">Respondida</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <span class="text-gray-500">Sin responder</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
