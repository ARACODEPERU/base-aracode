<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Link, router } from '@inertiajs/vue3';
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
    const savingAnswer = ref(false);
    const examFinish = ref(false);
    const isRetrying = ref(false);
    const showReviewScreen = ref(false);
    const questionFilter = ref('all');

    // Computed
    const maxAttempts = computed(() => props.exam?.max_attempts || 1);
    const attemptsUsed = computed(() => props.exam?.attempts_used || 1);
    const canRetry = computed(() => props.exam?.can_retry || false);
    const hasAttemptsLeft = computed(() => attemptsUsed.value < maxAttempts.value);

    // Computed
    const currentQuestion = computed(() => props.exam.questions[currentQuestionIndex.value]);
    const totalQuestions = computed(() => props.exam.questions?.length || 0);
    const answeredCount = computed(() => Object.keys(answers.value).length);
    const savedCount = computed(() => Object.values(answers.value).filter(a => a.saved).length);
    const markedCount = computed(() => Object.values(answers.value).filter(a => a.marked).length);
    const progressPercent = computed(() => totalQuestions.value > 0 ? Math.round((savedCount.value / totalQuestions.value) * 100) : 0);

    const isCurrentQuestionMarked = computed(() => {
        if (!currentQuestion.value) return false;
        return answers.value[currentQuestion.value.id]?.marked || false;
    });

    const hasUnsavedAnswer = computed(() => {
        if (!currentQuestion.value) return false;
        const ans = answers.value[currentQuestion.value.id];
        if (!ans) return false;
        return hasCurrentAnswer.value && !ans.saved;
    });

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
            answers.value[qId] = { answerId: answerId, saved: false, marked: answers.value[qId]?.marked || false };
        }
        else if (type === 'Varias respuestas') {
            if (!answers.value[qId]) {
                answers.value[qId] = { answerIds: [], saved: false, marked: false };
            }

            const currentIds = [...(answers.value[qId].answerIds || [])];
            const index = currentIds.indexOf(answerId);

            if (index === -1) {
                const maxAllowed = currentQuestion.value.answers.filter(a => a.correct == 1 || a.correct == true).length;

                if (currentIds.length < maxAllowed) {
                    currentIds.push(answerId);
                } else {
                    Swal2.fire({
                        title: 'Límite alcanzado',
                        text: `Esta pregunta solo permite seleccionar ${maxAllowed} respuestas.`,
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        padding: '2em',
                        customClass: 'sweet-alerts',
                    });
                }
            } else {
                currentIds.splice(index, 1);
            }
            answers.value[qId].answerIds = currentIds;
            answers.value[qId].saved = false;
        }
    };

    const updateDescription = (value) => {
        const qId = currentQuestion.value.id;
        if (!answers.value[qId]) answers.value[qId] = {};
        answers.value[qId].description = value;
        answers.value[qId].saved = false;
        answers.value[qId].marked = answers.value[qId].marked || false;
    };

    const onFileChange = (event, id) => {
        const qId = currentQuestion.value.id;
        const file = event.target.files[0];
        if (file && file.type === 'application/pdf') {
            answers.value[qId] = { answerId: id, file: file, saved: false, marked: answers.value[qId]?.marked || false };
        }
    };

    const toggleMarkQuestion = () => {
        const qId = currentQuestion.value.id;
        if (!answers.value[qId]) {
            answers.value[qId] = { marked: true, saved: false };
        } else {
            answers.value[qId].marked = !answers.value[qId].marked;
        }
    };

    const getQuestionState = (questionId) => {
        const ans = answers.value[questionId];
        if (!ans) return 'unanswered';

        const hasAnswer = ans.answerId !== undefined || (ans.answerIds?.length > 0) || ans.description?.trim() || ans.file;
        if (!hasAnswer) return 'unanswered';

        if (ans.saved) {
            return ans.marked ? 'marked-saved' : 'saved';
        } else {
            return ans.marked ? 'marked-unsaved' : 'unsaved';
        }
    };

    const getFilteredQuestions = computed(() => {
        if (questionFilter.value === 'all') return props.exam.questions;
        return props.exam.questions.filter((q, index) => {
            const state = getQuestionState(q.id);
            switch (questionFilter.value) {
                case 'answered': return state === 'saved' || state === 'marked-saved';
                case 'unanswered': return state === 'unanswered';
                case 'marked': return state.includes('marked');
                case 'unsaved': return state === 'unsaved' || state === 'marked-unsaved';
                default: return true;
            }
        });
    });

    const getQuestionBadgeClass = (state) => {
        switch (state) {
            case 'saved': case 'marked-saved': return 'bg-green-500 text-white';
            case 'unsaved': case 'marked-unsaved': return 'bg-red-500 text-white animate-pulse';
            default: return 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
        }
    };

    const goToQuestion = async (index) => {
        if (hasUnsavedAnswer.value) {
            const result = await Swal2.fire({
                title: '¡Atención!',
                html: `
                    <div class="text-left">
                        <p class="mb-3">Has respondido esta pregunta pero <strong>no la has guardado</strong>.</p>
                        <p class="text-sm text-gray-600">Si cambias ahora, tu respuesta se perderá y no será tomada en cuenta al calcular tu nota final.</p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save mr-1"></i> Guardar respuesta',
                confirmButtonColor: '#22c55e',
                cancelButtonText: 'Cambiar de todos modos',
                cancelButtonColor: '#f97316',
                denyButtonText: 'Quedarme en la pregunta',
                denyButtonColor: '#6b7280',
                showDenyButton: true,
                padding: '1.5rem',
                customClass: 'sweet-alerts',
            });

            if (result.isConfirmed) {
                await saveAndContinue();
                currentQuestionIndex.value = index;
            } else if (result.isDenied) {
                return;
            } else {
                currentQuestionIndex.value = index;
            }
        } else {
            currentQuestionIndex.value = index;
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

    const saveAndContinue = async () => {
        if (!hasCurrentAnswer.value) {
            Swal2.fire({
                title: 'Atención',
                text: 'Debes responder la pregunta para continuar',
                icon: 'warning',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
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
            answers.value[qId].saved = true;

            if (currentQuestionIndex.value < totalQuestions.value - 1) {
                currentQuestionIndex.value++;
            } else {
                showReviewScreen.value = true;
            }
        } catch (error) {
            Swal2.fire({ title: 'Error', text: 'Error al guardar la respuesta', icon: 'error' });
        } finally {
            savingAnswer.value = false;
        }
    };

    const previousQuestion = () => {
        if (hasUnsavedAnswer.value) {
            Swal2.fire({
                title: '¡Atención!',
                html: `
                    <div class="text-left">
                        <p class="mb-3">Has respondido esta pregunta pero <strong>no la has guardado</strong>.</p>
                        <p class="text-sm text-gray-600">Si vas atrás, tu respuesta se perderá.</p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save mr-1"></i> Guardar y volver',
                confirmButtonColor: '#22c55e',
                cancelButtonText: 'Descartar y volver',
                cancelButtonColor: '#ef4444',
                padding: '1.5rem',
                customClass: 'sweet-alerts',
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await saveAndContinue();
                    if (currentQuestionIndex.value > 0) currentQuestionIndex.value--;
                } else if (result.isDismissed) {
                    if (currentQuestionIndex.value > 0) currentQuestionIndex.value--;
                }
            });
        } else if (currentQuestionIndex.value > 0) {
            currentQuestionIndex.value--;
        }
    };

    const finishExam = () => {
        stopTimer();

        router.post(route('aca_student_exam_module_finish'), {
            student_exam_id: props.examStudent.id,
            time_used: props.exam.duration_minutes * 60 - timeRemaining.value
        }, {
            onFinish: () => {
                examFinish.value = true;
            }
        });
    };

    // Reintentar el examen
    const retryExam = async () => {
        const result = await Swal2.fire({
            title: '¿Reintentar examen?',
            text: `Esto reiniciará todas tus respuestas. Has usado ${attemptsUsed.value} de ${maxAttempts.value} intentos.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, reintentar',
            cancelButtonText: 'Cancelar',
            padding: '2em',
        });

        if (!result.isConfirmed) return;

        isRetrying.value = true;

        try {
            // Llamar a la API para reintentar el examen
            const response = await axios.post(route('aca_student_module_exam_retry', props.examStudent.id));

            if (response.data.success) {
                // Recargar la página después de reintentar exitosamente
                router.visit(route('aca_student_module_exam_solve', props.exam.id), {
                    method: 'get'
                });
            } else {
                Swal2.fire({
                    title: 'Error',
                    text: response.data.message || 'No se pudo reintentar el examen',
                    icon: 'error',
                    padding: '2em',
                });
            }
        } catch (error) {
            console.error('Error al reintentar:', error);
            Swal2.fire({
                title: 'Error',
                text: error.response?.data?.message || 'Ocurrió un error al reintentar el examen',
                icon: 'error',
                padding: '2em',
            });
        } finally {
            isRetrying.value = false;
        }
    };

    // Cargar respuestas guardadas desde el backend
    const loadSavedAnswers = () => {
        if (props.examStudent?.details) {
            const savedDetails = props.examStudent.details;

            Object.keys(savedDetails).forEach(questionId => {
                const savedItem = savedDetails[questionId];

                if (savedItem.type === 'Alternativas') {
                    answers.value[questionId] = {
                        answerId: parseInt(savedItem.answers),
                        saved: true,
                        marked: false
                    };
                }
                else if (savedItem.type === 'Varias respuestas') {
                    const rawIds = Array.isArray(savedItem.answers) ? savedItem.answers : [savedItem.answers];
                    answers.value[questionId] = {
                        answerIds: rawIds.map(id => parseInt(id)),
                        saved: true,
                        marked: false
                    };
                }
                else if (savedItem.type === 'Escribir') {
                    answers.value[questionId] = { description: savedItem.answers, saved: true, marked: false };
                }
                else if (savedItem.type === 'Subir Archivo') {
                    answers.value[questionId] = {
                        file: true,
                        answerId: parseInt(savedItem.answers),
                        fileName: savedItem.answers,
                        saved: true,
                        marked: false
                    };
                }
            });

            const firstUnansweredIndex = props.exam.questions.findIndex(q => !answers.value[q.id]);

            if (firstUnansweredIndex !== -1) {
                currentQuestionIndex.value = firstUnansweredIndex;
            } else {
                currentQuestionIndex.value = props.exam.questions.length - 1;
            }
        }
    };

    const getAnswerSummary = (question) => {
        const ans = answers.value[question.id];
        if (!ans) return 'Sin responder';

        if (question.type_answers === 'Alternativas') {
            const selectedAnswer = question.answers.find(a => a.id === ans.answerId);
            return selectedAnswer ? selectedAnswer.description : 'Sin responder';
        }
        else if (question.type_answers === 'Varias respuestas') {
            if (!ans.answerIds?.length) return 'Sin responder';
            const selectedAnswers = question.answers.filter(a => ans.answerIds.includes(a.id));
            return selectedAnswers.map(a => a.description).join(', ') || 'Sin responder';
        }
        else if (question.type_answers === 'Escribir') {
            return ans.description?.trim() || 'Sin responder';
        }
        else if (question.type_answers === 'Subir Archivo') {
            return ans.file ? 'Archivo adjunto' : 'Sin archivo';
        }
        return 'Sin responder';
    };

    const reviewScreenGoToQuestion = (index) => {
        showReviewScreen.value = false;
        currentQuestionIndex.value = index;
    };

    const submitExamFromReview = () => {
        finishExam();
    };

    // Calcular tiempo restante basado en la hora de inicio
    const calculateTimeRemaining = () => {
        if (!props.examStudent?.started_at) {
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

        loadSavedAnswers();
        timeRemaining.value = calculateTimeRemaining();

        if (timeRemaining.value <= 0) {
            finishExam();
            return;
        }

        startTimer();
    };

    const showStartWarningModal = async () => {
        const result = await Swal2.fire({
            title: '¡Antes de comenzar!',
            html: `
                <div class="text-left">
                    <div class="mb-4 p-4 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 rounded">
                        <p class="font-bold text-amber-800 dark:text-amber-200 mb-2"><i class="fas fa-exclamation-triangle mr-2"></i>Importante</p>
                        <p class="text-sm text-amber-700 dark:text-amber-300">Para que tus respuestas sean guardadas correctamente, debes hacer clic en el botón <strong>"Guardar y Continuar"</strong> después de responder cada pregunta.</p>
                        <p class="text-sm text-amber-700 dark:text-amber-300 mt-2"><strong>Las respuestas no guardadas no serán tomadas en cuenta al calcular tu nota final.</strong></p>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Si cambias de pregunta sin guardar, tu respuesta se perderá.</p>
                </div>
            `,
            icon: 'info',
            confirmButtonText: '¡Entendido, comenzar!',
            confirmButtonColor: '#3b82f6',
            allowOutsideClick: false,
            allowEscapeKey: false,
            padding: '1.5rem',
            customClass: 'sweet-alerts',
        });

        if (result.isConfirmed) {
            initExam();
        }
    };

    onMounted(() => {
        if (props.examStudent.finished_at || props.examStudent.status === 'terminado' || props.examStudent.status === 'revision_pendiente' || props.examStudent.status === 'calificado') {
            examFinish.value = true;
        } else {
            showStartWarningModal();
        }
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
        <template v-else>
            <div v-if="examFinish" class="pt-5 max-w-4xl mx-auto px-4">
                <div class="panel overflow-hidden p-0">

                    <div :class="[
                        'p-6 text-white flex items-center justify-between rounded-t-2xl',
                        props.examStudent.status === 'revision_pendiente' ? 'bg-primary' : (props.examStudent.punctuation >= 11 ? 'bg-success' : 'bg-danger')
                    ]">
                        <div>
                            <h2 class="text-2xl font-bold flex items-center gap-3">
                                <i v-if="props.examStudent.status === 'revision_pendiente'" class="fas fa-hourglass-half"></i>
                                <i v-else-if="props.examStudent.punctuation >= 11" class="fas fa-trophy"></i>
                                <i v-else class="fas fa-redo"></i>
                                {{ props.examStudent.status === 'revision_pendiente' ? 'Examen en Revisión' : 'Examen Finalizado' }}
                            </h2>
                            <p class="text-sm opacity-90 mt-1 font-medium">
                                {{ props.examStudent.status === 'revision_pendiente'
                                    ? 'Tu docente debe calificar manualmente las preguntas abiertas.'
                                    : 'El proceso de evaluación ha concluido satisfactoriamente.'
                                }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs uppercase font-bold tracking-widest opacity-80">Puntaje</span>
                            <div class="text-5xl font-black leading-none">{{ props.examStudent.punctuation }}</div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                            <div class="bg-gray-50 dark:bg-[#0e1726] p-4 rounded-md border border-gray-100 dark:border-[#191e3a] flex flex-col items-center justify-center text-center">
                                <i class="fas fa-stopwatch text-gray-400 dark:text-gray-500 mb-2"></i>
                                <div class="text-gray-500 dark:text-gray-400 text-xs font-bold uppercase">Tiempo Utilizado</div>
                                <div class="text-lg font-semibold text-gray-800 dark:text-white">
                                    {{ Math.floor(props.examStudent.time_spent_seconds / 60) }}m {{ props.examStudent.time_spent_seconds % 60 }}s
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-[#0e1726] p-4 rounded-md border border-gray-100 dark:border-[#191e3a] flex flex-col items-center justify-center text-center">
                                <i class="fas fa-calendar-alt text-gray-400 dark:text-gray-500 mb-2"></i>
                                <div class="text-gray-500 dark:text-gray-400 text-xs font-bold uppercase">Fecha de Envío</div>
                                <div class="text-lg font-semibold text-gray-800 dark:text-white">
                                    {{ props.examStudent.finished_at }}
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-[#0e1726] p-4 rounded-md border border-gray-100 dark:border-[#191e3a] flex flex-col items-center justify-center text-center">
                                <i class="fas fa-check-double text-gray-400 dark:text-gray-500 mb-2"></i>
                                <div class="text-gray-500 dark:text-gray-400 text-xs font-bold uppercase">Estado de Nota</div>
                                <div :class="['text-lg font-bold', props.examStudent.status === 'revision_pendiente' ? 'text-primary' : 'text-success']">
                                    {{ props.examStudent.status === 'revision_pendiente' ? 'Provisional' : 'Finalizada' }}
                                </div>
                            </div>
                        </div>

                        <div v-if="props.examStudent.status === 'revision_pendiente'"
                            class="bg-blue-50 dark:bg-primary/10 border-l-4 border-primary p-4 mb-8">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-primary" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-800 dark:text-blue-300">
                                        <span class="font-bold">Nota en espera:</span> Este examen contiene archivos o textos que el docente debe calificar. Tu puntaje actual de <strong>{{ props.examStudent.punctuation }}</strong> se actualizará pronto.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Información de intentos -->
                        <div v-if="hasAttemptsLeft && props.examStudent.status !== 'revision_pendiente'"
                            class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-yellow-800 dark:text-yellow-200 font-medium">
                                        <i class="fas fa-redo mr-2"></i>
                                        Intentos: {{ attemptsUsed }} / {{ maxAttempts }}
                                    </p>
                                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                                        Te quedan {{ maxAttempts - attemptsUsed }} intento(s) disponible(s)
                                    </p>
                                </div>
                                <button
                                    @click="retryExam"
                                    :disabled="isRetrying"
                                    class="btn btn-warning flex items-center gap-2"
                                >
                                    <i v-if="isRetrying" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-redo"></i>
                                    {{ isRetrying ? 'Reiniciando...' : 'Reintentar Examen' }}
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 justify-center border-t border-gray-100 dark:border-[#191e3a] pt-6">
                            <a :href="route('aca_student_exam_download_pdf', props.examStudent.id)"
                            class="btn btn-outline-primary dark:btn-outline-info flex items-center justify-center gap-2 px-6">
                                <i class="fas fa-file-invoice"></i>
                                Ver mis Respuestas (PDF)
                            </a>

                            <a v-if="props.examStudent.punctuation >= 11 && props.examStudent.status === 'terminado'"
                            :href="props.exam.file_resolved_path"
                            target="_blank"
                            class="btn btn-success flex items-center justify-center gap-2 px-6">
                                <i class="fas fa-book-open"></i>
                                Descargar Solucionario
                            </a>

                            <Link :href="route('aca_mycourses_lesson_themes', exam.module.id)"
                                class="btn btn-dark dark:btn-secondary flex items-center justify-center gap-2 px-6">
                                <i class="fas fa-arrow-left"></i>
                                Ir atras
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="pt-5 px-4 pb-20">
                <!-- Pantalla de Revisión -->
                <div v-if="showReviewScreen" class="max-w-4xl mx-auto">
                    <div class="panel p-0 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white rounded-t-2xl">
                            <h2 class="text-2xl font-bold flex items-center gap-3">
                                <i class="fas fa-clipboard-check"></i>
                                Revisión Final del Examen
                            </h2>
                            <p class="text-sm opacity-90 mt-1">Verifica tus respuestas antes de enviar</p>
                        </div>

                        <div class="p-6">
                            <!-- Resumen de Estadísticas -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg text-center border border-green-200 dark:border-green-800">
                                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ savedCount }}</div>
                                    <div class="text-xs text-green-700 dark:text-green-300 font-medium">Guardadas</div>
                                </div>
                                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg text-center border border-red-200 dark:border-red-800">
                                    <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ totalQuestions - savedCount }}</div>
                                    <div class="text-xs text-red-700 dark:text-red-300 font-medium">Sin Guardar</div>
                                </div>
                                <!-- <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg text-center border border-yellow-200 dark:border-yellow-800">
                                    <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ markedCount }}</div>
                                    <div class="text-xs text-yellow-700 dark:text-yellow-300 font-medium">Marcadas</div>
                                </div> -->
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg text-center border border-blue-200 dark:border-blue-800">
                                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ formattedTime }}</div>
                                    <div class="text-xs text-blue-700 dark:text-blue-300 font-medium">Tiempo Restante</div>
                                </div>
                            </div>

                            <!-- Advertencia si hay respuestas sin guardar -->
                            <div v-if="savedCount < totalQuestions" class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-800 dark:text-red-200 font-medium">
                                            <strong>{{ totalQuestions - savedCount }} pregunta(s) sin guardar</strong>
                                        </p>
                                        <p class="text-xs text-red-600 dark:text-red-300 mt-1">
                                            Las respuestas sin guardar NO serán tomadas en cuenta al calcular tu nota.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Lista de Preguntas -->
                            <div class="space-y-3 max-h-[400px] overflow-y-auto">
                                <div v-for="(question, index) in exam.questions" :key="question.id"
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <div class="flex items-start gap-3">
                                        <button @click="reviewScreenGoToQuestion(index)"
                                            class="flex-shrink-0 w-10 h-10 rounded-lg text-sm font-medium flex items-center justify-center"
                                            :class="getQuestionBadgeClass(getQuestionState(question.id))">
                                            {{ index + 1 }}
                                            <i v-if="getQuestionState(question.id).includes('marked')" class="fas fa-flag ml-1 text-xs"></i>
                                        </button>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs px-2 py-0.5 rounded font-medium"
                                                    :class="{
                                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': question.type_answers === 'Escribir',
                                                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300': question.type_answers === 'Alternativas',
                                                        'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300': question.type_answers === 'Varias respuestas',
                                                        'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300': question.type_answers === 'Subir Archivo',
                                                    }">
                                                    {{ question.type_answers }}
                                                </span>
                                                <span :class="getQuestionState(question.id) === 'saved' || getQuestionState(question.id) === 'marked-saved' ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium">
                                                    <i :class="getQuestionState(question.id) === 'saved' || getQuestionState(question.id) === 'marked-saved' ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                                                    {{ getQuestionState(question.id) === 'saved' || getQuestionState(question.id) === 'marked-saved' ? 'Guardada' : 'Sin guardar' }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-800 dark:text-gray-200 line-clamp-2">{{ question.description }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">
                                                <strong>Tu respuesta:</strong> {{ getAnswerSummary(question) }}
                                            </p>
                                        </div>
                                        <button @click="reviewScreenGoToQuestion(index)"
                                            class="flex-shrink-0 btn btn-sm btn-outline-primary">
                                            <i class="fas fa-pen mr-1"></i> Editar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="flex flex-col sm:flex-row gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <button @click="showReviewScreen = false"
                                    class="btn btn-outline-secondary flex-1">
                                    <i class="fas fa-arrow-left mr-2"></i> Volver al Examen
                                </button>
                                <button @click="submitExamFromReview"
                                    class="btn bg-red-500 hover:bg-red-600 text-white flex-1 border-0">
                                    <i class="fas fa-paper-plane mr-2"></i> Enviar Examen Definitivamente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Examen Normal -->
                <template v-else>
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
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Guardadas</p>
                                    <p class="text-lg font-bold text-green-500">{{ savedCount }}/{{ totalQuestions }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Marcadas</p>
                                    <p class="text-lg font-bold text-orange-500">{{ markedCount }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Progreso</p>
                                    <p class="text-lg font-bold text-blue-500">{{ progressPercent }}%</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full transition-all duration-300"
                                :style="{ width: progressPercent + '%' }"></div>
                        </div>
                    </div>

                    <!-- Pregunta Actual -->
                    <div v-if="currentQuestion" class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4 mb-4">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
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
                                <span v-if="hasUnsavedAnswer" class="bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 text-xs px-2 py-1 rounded font-medium animate-pulse">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Sin guardar
                                </span>
                            </div>
                            <button @click="toggleMarkQuestion"
                                class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                                :class="isCurrentQuestionMarked
                                    ? 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400 hover:bg-orange-200 dark:hover:bg-orange-900/50'
                                    : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                <i class="fas fa-flag"></i>
                                {{ isCurrentQuestionMarked ? 'Marcada' : 'Marcar' }}
                            </button>
                        </div>

                        <div class="mb-4">
                            <p class="text-base font-medium text-gray-900 dark:text-white">{{ currentQuestion.description }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ currentQuestion.score }} puntos</p>
                        </div>

                        <div class="space-y-3">
                            <div v-if="currentQuestion.type_answers === 'Escribir'">
                                <textarea
                                    :value="getCurrentAnswer?.description || ''"
                                    @input="updateDescription($event.target.value)"
                                    class="form-textarea w-full"
                                    rows="4"
                                    placeholder="Escribe tu respuesta aquí..."
                                ></textarea>
                            </div>

                            <div v-else-if="currentQuestion.type_answers === 'Subir Archivo'">
                                <template v-for="(answer, key) in currentQuestion.answers">
                                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">
                                        <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        <p class="text-sm text-gray-500">{{ answer.description }}</p>

                                        <div v-if="getCurrentAnswer?.file && getCurrentAnswer?.fileName" class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                            <div class="flex items-center justify-center gap-2 text-green-600 dark:text-green-400">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                                </svg>
                                                <span class="text-sm font-medium">Archivo ya subido</span>
                                            </div>
                                        </div>

                                        <input @change="onFileChange($event, answer.id)" :id="'file_input'+key" type="file" accept=".pdf" class="mt-2" />
                                    </div>
                                </template>
                            </div>

                            <div v-else-if="currentQuestion.type_answers === 'Alternativas'" class="space-y-2">
                                <label
                                    v-for="answer in currentQuestion.answers"
                                    :key="answer.id"
                                    class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
                                    :class="isAnswerSelected(answer.id)
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'">
                                    <input
                                        type="radio"
                                        :name="'question-' + currentQuestion.id"
                                        :checked="isAnswerSelected(answer.id)"
                                        @change="selectAnswer(answer.id)"
                                        class="w-4 h-4 text-blue-500">
                                    <span class="text-sm text-gray-900 dark:text-white">{{ answer.description }}</span>
                                </label>
                            </div>

                            <div v-else-if="currentQuestion.type_answers === 'Varias respuestas'" class="space-y-2">
                                <p class="text-xs text-gray-500 mb-2">Selecciona todas las respuestas que consideres correctas</p>
                                <label
                                    v-for="answer in currentQuestion.answers"
                                    :key="answer.id"
                                    class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
                                    :class="[
                                        isAnswerSelected(answer.id) ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700',
                                        (!isAnswerSelected(answer.id) && (getCurrentAnswer?.answerIds?.length >= currentQuestion.answers.filter(a => a.correct).length)) ? 'opacity-50 cursor-not-allowed' : ''
                                    ]">
                                    <input
                                        type="checkbox"
                                        :checked="isAnswerSelected(answer.id)"
                                        @change="selectAnswer(answer.id)"
                                        class="w-4 h-4 text-purple-500 rounded"
                                        :disabled="!isAnswerSelected(answer.id) && (getCurrentAnswer?.answerIds?.length >= currentQuestion.answers.filter(a => a.correct).length)">
                                    <span class="text-sm text-gray-900 dark:text-white">{{ answer.description }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button
                                @click="previousQuestion"
                                :disabled="currentQuestionIndex === 0"
                                class="flex items-center gap-1 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                <IconArrowLeft class="w-4 h-4" />
                                Anterior
                            </button>

                            <button
                                v-if="currentQuestionIndex === totalQuestions - 1"
                                @click="saveAndContinue"
                                :disabled="savingAnswer || !hasCurrentAnswer"
                                class="flex items-center gap-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg text-sm font-medium">
                                <span v-if="savingAnswer">Guardando...</span>
                                <span v-else><i class="fas fa-clipboard-check mr-1"></i> Revisar antes de enviar</span>
                            </button>
                            <button
                                v-else
                                @click="saveAndContinue"
                                :disabled="savingAnswer || !hasCurrentAnswer"
                                class="flex items-center gap-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg text-sm font-medium">
                                <span v-if="savingAnswer">Guardando...</span>
                                <span v-else>Guardar y Continuar</span>
                                <IconArrowRight v-if="!savingAnswer" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Navegador de Preguntas Mejorado -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">
                                <i class="fas fa-th-large mr-2"></i>Navegar entre preguntas:
                            </p>
                            <!-- Filtros -->
                            <div class="flex flex-wrap gap-1">
                                <button @click="questionFilter = 'all'"
                                    class="px-2 py-1 text-xs rounded-full font-medium transition-colors"
                                    :class="questionFilter === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                    Todas ({{ totalQuestions }})
                                </button>
                                <button @click="questionFilter = 'answered'"
                                    class="px-2 py-1 text-xs rounded-full font-medium transition-colors"
                                    :class="questionFilter === 'answered' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                    Guardadas ({{ savedCount }})
                                </button>
                                <button @click="questionFilter = 'unsaved'"
                                    class="px-2 py-1 text-xs rounded-full font-medium transition-colors"
                                    :class="questionFilter === 'unsaved' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                    Sin guardar ({{ totalQuestions - savedCount }})
                                </button>
                                <button @click="questionFilter = 'marked'"
                                    class="px-2 py-1 text-xs rounded-full font-medium transition-colors"
                                    :class="questionFilter === 'marked' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                    Marcadas ({{ markedCount }})
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="(question, index) in getFilteredQuestions"
                                :key="question.id"
                                @click="goToQuestion(index)"
                                class="w-10 h-10 rounded-lg text-sm font-medium transition-colors flex items-center justify-center relative"
                                :class="getQuestionBadgeClass(getQuestionState(question.id)).replace('text-white', '').trim() + ' ' + (currentQuestionIndex === index ? 'ring-2 ring-offset-2 ring-blue-500' : '')">
                                <span :class="getQuestionBadgeClass(getQuestionState(question.id)) + ' w-full h-full flex items-center justify-center rounded-lg'">
                                    {{ index + 1 }}
                                    <i v-if="getQuestionState(question.id).includes('marked')" class="fas fa-flag ml-1 text-xs"></i>
                                </span>
                            </button>
                        </div>

                        <!-- Leyenda Actualizada -->
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-xs">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 bg-blue-500 rounded flex items-center justify-center text-white text-[10px] font-bold">1</div>
                                <span class="text-gray-600 dark:text-gray-400">Actual</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 bg-green-500 rounded flex items-center justify-center text-white text-[10px] font-bold">2</div>
                                <span class="text-gray-600 dark:text-gray-400">Guardada</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 bg-red-500 rounded flex items-center justify-center text-white text-[10px] font-bold animate-pulse">3</div>
                                <span class="text-gray-600 dark:text-gray-400">Sin guardar (⚠️ se perderá)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center text-gray-600 dark:text-gray-200 text-[10px] font-bold">4</div>
                                <span class="text-gray-600 dark:text-gray-400">Sin ver</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 bg-orange-500 rounded flex items-center justify-center text-white text-[10px]">
                                    <i class="fas fa-flag text-[8px]"></i>
                                </div>
                                <span class="text-gray-600 dark:text-gray-400">Marcada para revisión</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </AppLayout>
</template>
