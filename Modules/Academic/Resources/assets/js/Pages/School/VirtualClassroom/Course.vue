<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import ModalLarge from '@/Components/ModalLarge.vue';
import ModalLargeX from '@/Components/ModalLargeX.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { computed, ref } from 'vue';
import {
    faArrowLeft,
    faBookOpen,
    faChevronDown,
    faChevronUp,
    faCircle,
    faCircleCheck,
    faCircleQuestion,
    faClipboardList,
    faCopy,
    faEye,
    faFileLines,
    faFilePdf,
    faLayerGroup,
    faLink,
    faListCheck,
    faPaperclip,
    faPencil,
    faPlus,
    faTrash,
    faXmark,
} from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    course: { type: Object, required: true },
    units: { type: Array, default: () => [] },
    resources: { type: Array, default: () => [] },
    activities: { type: Array, default: () => [] },
    yearIsActive: { type: Boolean, default: false },
    canManage: { type: Boolean, default: false },
    canManageUnits: { type: Boolean, default: false },
    canManageLessons: { type: Boolean, default: false },
    canManageResources: { type: Boolean, default: false },
    canManageActivities: { type: Boolean, default: false },
    canGrade: { type: Boolean, default: false },
    canCopy: { type: Boolean, default: false },
});

/* ------------------------------ Utilidades ----------------------------- */
const showToast = (title, icon = 'success') => {
    const toast = Swal2.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3000,
        customClass: { container: 'toast' },
    });
    toast.fire({ icon, title, padding: '10px 20px' });
};

const confirmAction = (options) =>
    Swal2.fire({
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
        ...options,
    });

const resourceLabel = (type) => ({ file: 'Archivo', link: 'Enlace', embed: 'Página' })[type] ?? type;
const activityLabel = (type) => ({ task: 'Tarea', quiz: 'Examen', forum: 'Foro' })[type] ?? type;
const resourceBadge = (type) => ({ file: 'bg-primary', link: 'bg-info', embed: 'bg-dark' })[type] ?? 'bg-secondary';
const activityBadge = (type) => ({ task: 'bg-primary', quiz: 'bg-danger', forum: 'bg-info' })[type] ?? 'bg-secondary';
const resourceIcon = (type) => ({ file: faPaperclip, link: faLink, embed: faFileLines })[type] ?? faPaperclip;
const activityIcon = (type) => ({ task: faClipboardList, quiz: faFileLines, forum: faBookOpen })[type] ?? faClipboardList;
const formatSize = (kb) => (kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${kb} KB`);

const formatDate = (value) => {
    if (! value) {
        return '';
    }

    const [year, month, day] = String(value).split('T')[0].split('-');

    return day && month && year ? `${day}/${month}/${year}` : value;
};

const formatDateTime = (value) => {
    if (! value) {
        return '';
    }

    const [date, time] = String(value).split('T');

    return time ? `${formatDate(date)} ${time}` : formatDate(date);
};

const totalLessons = computed(() => props.units.reduce((total, unit) => total + (unit.lessons?.length ?? 0), 0));

const unitDateRange = (unit) => {
    if (! unit.start_date && ! unit.end_date) {
        return 'Sin fechas';
    }

    if (unit.start_date && unit.end_date) {
        return `${formatDate(unit.start_date)} — ${formatDate(unit.end_date)}`;
    }

    return formatDate(unit.start_date || unit.end_date);
};

/* ----------------------------- Unidades ----------------------------- */
const expandedUnitId = ref(null);

const toggleUnit = (unit) => {
    expandedUnitId.value = expandedUnitId.value === unit.id ? null : unit.id;
};

const unitModal = ref(false);
const editingUnitId = ref(null);

const unitForm = useForm({
    title: '',
    purpose: '',
    significant_situation: '',
    competencies: '',
    capabilities: '',
    performances: '',
    duration_hours: null,
    start_date: '',
    end_date: '',
    status: true,
});

const openUnitCreate = () => {
    editingUnitId.value = null;
    unitForm.reset();
    unitForm.clearErrors();
    unitForm.status = true;
    unitModal.value = true;
};

const openUnitEdit = (unit) => {
    editingUnitId.value = unit.id;
    unitForm.clearErrors();
    unitForm.title = unit.title || '';
    unitForm.purpose = unit.purpose || '';
    unitForm.significant_situation = unit.significant_situation || '';
    unitForm.competencies = unit.competencies || '';
    unitForm.capabilities = unit.capabilities || '';
    unitForm.performances = unit.performances || '';
    unitForm.duration_hours = unit.duration_hours;
    unitForm.start_date = unit.start_date || '';
    unitForm.end_date = unit.end_date || '';
    unitForm.status = !! unit.status;
    unitModal.value = true;
};

const closeUnitModal = () => {
    unitModal.value = false;
    unitForm.clearErrors();
};

const saveUnit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            unitModal.value = false;
            showToast(editingUnitId.value ? 'Unidad actualizada correctamente' : 'Unidad registrada correctamente');
        },
    };

    if (editingUnitId.value) {
        unitForm.put(route('aca_virtual_classroom_unit_update', editingUnitId.value), options);
    } else {
        unitForm.post(route('aca_virtual_classroom_unit_store', props.course.id), options);
    }
};

const destroyUnit = (unit) => {
    confirmAction({
        title: '¿Eliminar unidad didáctica?',
        text: `Se eliminarán la unidad "${unit.title}" y sus sesiones. Los recursos y actividades asociados quedarán sin unidad.`,
        icon: 'warning',
        confirmButtonText: 'Sí, eliminar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('aca_virtual_classroom_unit_destroy', unit.id), {
                preserveScroll: true,
                onSuccess: () => showToast('Unidad eliminada'),
            });
        }
    });
};

/* ------------------------------ Sesiones ----------------------------- */
const lessonModal = ref(false);
const editingLessonId = ref(null);
const currentUnitId = ref(null);
const currentUnitTitle = ref('');

const lessonForm = useForm({
    title: '',
    purpose: '',
    intro_activity: '',
    development: '',
    closure: '',
    metacognition: '',
    evaluation: '',
    duration_minutes: null,
    status: true,
});

const openLessonCreate = (unit) => {
    editingLessonId.value = null;
    currentUnitId.value = unit.id;
    currentUnitTitle.value = unit.title;
    lessonForm.reset();
    lessonForm.clearErrors();
    lessonForm.status = true;
    lessonModal.value = true;
};

const openLessonEdit = (unit, lesson) => {
    editingLessonId.value = lesson.id;
    currentUnitId.value = unit.id;
    currentUnitTitle.value = unit.title;
    lessonForm.clearErrors();
    lessonForm.title = lesson.title || '';
    lessonForm.purpose = lesson.purpose || '';
    lessonForm.intro_activity = lesson.intro_activity || '';
    lessonForm.development = lesson.development || '';
    lessonForm.closure = lesson.closure || '';
    lessonForm.metacognition = lesson.metacognition || '';
    lessonForm.evaluation = lesson.evaluation || '';
    lessonForm.duration_minutes = lesson.duration_minutes;
    lessonForm.status = !! lesson.status;
    lessonModal.value = true;
};

const closeLessonModal = () => {
    lessonModal.value = false;
    lessonForm.clearErrors();
};

const saveLesson = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            lessonModal.value = false;
            showToast(editingLessonId.value ? 'Sesión actualizada correctamente' : 'Sesión registrada correctamente');
        },
    };

    if (editingLessonId.value) {
        lessonForm.put(route('aca_virtual_classroom_lesson_update', editingLessonId.value), options);
    } else {
        lessonForm.post(route('aca_virtual_classroom_lesson_store', currentUnitId.value), options);
    }
};

const destroyLesson = (lesson) => {
    confirmAction({
        title: '¿Eliminar sesión de aprendizaje?',
        text: `Se eliminará la sesión "${lesson.title}".`,
        icon: 'warning',
        confirmButtonText: 'Sí, eliminar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('aca_virtual_classroom_lesson_destroy', lesson.id), {
                preserveScroll: true,
                onSuccess: () => showToast('Sesión eliminada'),
            });
        }
    });
};

/* ------------------------------ Recursos ----------------------------- */
const resourceModal = ref(false);
const editingResourceId = ref(null);
const editingResourceFile = ref(null);
const resourceFileInput = ref(null);

const resourceForm = useForm({
    type: 'file',
    title: '',
    url: '',
    content: '',
    unit_id: null,
    lesson_id: null,
    visibility: 'students',
    status: true,
    file: null,
});

const resourceLessons = computed(() => {
    const unit = props.units.find((item) => item.id === resourceForm.unit_id);

    return unit?.lessons ?? [];
});

const resetResourceForm = () => {
    resourceForm.reset();
    resourceForm.clearErrors();
    resourceForm.type = 'file';
    resourceForm.visibility = 'students';
    resourceForm.status = true;
    resourceForm.file = null;
    editingResourceFile.value = null;

    if (resourceFileInput.value) {
        resourceFileInput.value.value = '';
    }
};

const openResourceCreate = () => {
    editingResourceId.value = null;
    resetResourceForm();
    resourceModal.value = true;
};

const openResourceEdit = (resource) => {
    editingResourceId.value = resource.id;
    resetResourceForm();
    resourceForm.type = resource.type;
    resourceForm.title = resource.title || '';
    resourceForm.url = resource.url || '';
    resourceForm.content = resource.content || '';
    resourceForm.unit_id = resource.unit_id;
    resourceForm.lesson_id = resource.lesson_id;
    resourceForm.visibility = resource.visibility || 'students';
    resourceForm.status = !! resource.status;
    editingResourceFile.value = resource.file_name;
    resourceModal.value = true;
};

const closeResourceModal = () => {
    resourceModal.value = false;
    resourceForm.clearErrors();
};

const saveResource = () => {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            resourceModal.value = false;
            showToast(editingResourceId.value ? 'Recurso actualizado correctamente' : 'Recurso registrado correctamente');
        },
    };

    if (editingResourceId.value) {
        resourceForm.post(route('aca_virtual_classroom_resource_update', editingResourceId.value), options);
    } else {
        resourceForm.post(route('aca_virtual_classroom_resource_store', props.course.id), options);
    }
};

const destroyResource = (resource) => {
    confirmAction({
        title: '¿Eliminar recurso?',
        text: `Se eliminará "${resource.title}". Esta acción no se puede deshacer.`,
        icon: 'warning',
        confirmButtonText: 'Sí, eliminar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('aca_virtual_classroom_resource_destroy', resource.id), {
                preserveScroll: true,
                onSuccess: () => showToast('Recurso eliminado'),
            });
        }
    });
};

const pageModal = ref(false);
const pageResource = ref(null);

const openPage = (resource) => {
    pageResource.value = resource;
    pageModal.value = true;
};

/* ----------------------------- Actividades ---------------------------- */
const activityModal = ref(false);
const editingActivityId = ref(null);

const activityForm = useForm({
    type: 'task',
    title: '',
    instructions: '',
    unit_id: null,
    lesson_id: null,
    open_at: '',
    close_at: '',
    allow_late: false,
    attempts_allowed: 1,
    allow_result_download: false,
    duration_minutes: 30,
    max_score: 20,
    grading_scale: 'numeric',
    status: true,
    files: [],
    remove_file_ids: [],
});

const activityFileInput = ref(null);

/** Archivos ya guardados que el docente no ha marcado para quitar. */
const editingActivityFiles = computed(() => {
    const activity = props.activities.find((item) => item.id === editingActivityId.value);

    return (activity?.files ?? []).filter((file) => ! activityForm.remove_file_ids.includes(file.id));
});

const setActivityFiles = (event) => {
    activityForm.files = Array.from(event.target.files ?? []);
};

const removeActivityFile = (file) => {
    activityForm.remove_file_ids = [...activityForm.remove_file_ids, file.id];
};

const activityLessons = computed(() => {
    const unit = props.units.find((item) => item.id === activityForm.unit_id);

    return unit?.lessons ?? [];
});

const openActivityCreate = () => {
    editingActivityId.value = null;
    activityForm.reset();
    activityForm.clearErrors();
    activityForm.type = 'task';
    activityForm.grading_scale = 'numeric';
    activityForm.max_score = 20;
    activityForm.attempts_allowed = 1;
    activityForm.allow_result_download = false;
    activityForm.duration_minutes = 30;
    activityForm.status = true;
    activityForm.files = [];
    activityForm.remove_file_ids = [];

    if (activityFileInput.value) {
        activityFileInput.value.value = '';
    }

    activityModal.value = true;
};

const openActivityEdit = (activity) => {
    editingActivityId.value = activity.id;
    activityForm.clearErrors();
    activityForm.type = activity.type || 'task';
    activityForm.title = activity.title || '';
    activityForm.instructions = activity.instructions || '';
    activityForm.unit_id = activity.unit_id;
    activityForm.lesson_id = activity.lesson_id;
    activityForm.open_at = activity.open_at || '';
    activityForm.close_at = activity.close_at || '';
    activityForm.allow_late = !! activity.allow_late;
    activityForm.attempts_allowed = Number(activity.attempts_allowed ?? 1);
    activityForm.allow_result_download = !! activity.allow_result_download;
    activityForm.duration_minutes = Number(activity.duration_minutes ?? 30);
    activityForm.max_score = Number(activity.max_score ?? 20);
    activityForm.grading_scale = activity.grading_scale || 'numeric';
    activityForm.status = !! activity.status;
    activityForm.files = [];
    activityForm.remove_file_ids = [];

    if (activityFileInput.value) {
        activityFileInput.value.value = '';
    }

    activityModal.value = true;
};

const closeActivityModal = () => {
    activityModal.value = false;
    activityForm.clearErrors();
};

const saveActivity = () => {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            activityModal.value = false;
            showToast(editingActivityId.value ? 'Actividad actualizada correctamente' : 'Actividad registrada correctamente');
        },
    };

    if (editingActivityId.value) {
        activityForm.post(route('aca_virtual_classroom_activity_update', editingActivityId.value), options);
    } else {
        activityForm.post(route('aca_virtual_classroom_activity_store', props.course.id), options);
    }
};

const destroyActivity = (activity) => {
    confirmAction({
        title: '¿Eliminar actividad?',
        text: `Se eliminarán la actividad "${activity.title}" y sus entregas registradas.`,
        icon: 'warning',
        confirmButtonText: 'Sí, eliminar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('aca_virtual_classroom_activity_destroy', activity.id), {
                preserveScroll: true,
                onSuccess: () => showToast('Actividad eliminada'),
            });
        }
    });
};

const activityDeadline = (activity) => {
    if (activity.close_at) {
        return `Cierra ${formatDateTime(activity.close_at)}`;
    }

    if (activity.open_at) {
        return `Abre ${formatDateTime(activity.open_at)}`;
    }

    return 'Sin límite';
};

/* --------------------- Preguntas de la evaluación --------------------- */
const questionTypeLabels = {
    single_choice: 'Alternativa única',
    multiple_choice: 'Alternativa múltiple',
    true_false: 'Verdadero / Falso',
    short_answer: 'Respuesta abierta',
};

const questionsModal = ref(false);
const questionsActivityId = ref(null);
const questionEditingId = ref(null);
const questionFormOpen = ref(false);

const questionsActivity = computed(() => props.activities.find((item) => item.id === questionsActivityId.value) ?? null);

const questionTotalPoints = computed(() =>
    (questionsActivity.value?.questions ?? []).reduce((total, question) => total + Number(question.points || 0), 0)
);

// Puntos que aún se pueden repartir entre las preguntas. Al editar, la propia
// pregunta libera sus puntos; la nota de la evaluación es el tope.
const questionAvailablePoints = computed(() => {
    const max = Number(questionsActivity.value?.max_score ?? 0);
    const assigned = Number(questionTotalPoints.value || 0);
    const own = questionEditingId.value
        ? Number((questionsActivity.value?.questions ?? []).find((question) => question.id === questionEditingId.value)?.points || 0)
        : 0;

    return Math.max(0, Number((max - assigned + own).toFixed(2)));
});

const canAddQuestion = computed(() => Number(questionsActivity.value?.max_score ?? 0) <= 0 || questionAvailablePoints.value > 0);
const questionPointsExhausted = computed(() => ! canAddQuestion.value);

const defaultOptions = (type) => {
    if (type === 'true_false') {
        return [
            { text: 'Verdadero', is_correct: true },
            { text: 'Falso', is_correct: false },
        ];
    }

    if (type === 'short_answer') {
        return [];
    }

    return [
        { text: '', is_correct: true },
        { text: '', is_correct: false },
        { text: '', is_correct: false },
        { text: '', is_correct: false },
    ];
};

const questionForm = useForm({
    type: 'single_choice',
    question: '',
    points: 1,
    answer_text: '',
    options: defaultOptions('single_choice'),
});

const openQuestions = (activity) => {
    questionsActivityId.value = activity.id;
    questionFormOpen.value = false;
    questionEditingId.value = null;
    questionForm.clearErrors();
    questionsModal.value = true;
};

const closeQuestions = () => {
    questionsModal.value = false;
    questionFormOpen.value = false;
    questionEditingId.value = null;
    questionForm.clearErrors();
};

const changeQuestionType = () => {
    if (questionForm.type === 'true_false' || questionForm.type === 'short_answer') {
        questionForm.options = defaultOptions(questionForm.type);

        return;
    }

    if (questionForm.options.length < 2) {
        questionForm.options = defaultOptions(questionForm.type);
    } else {
        // Al pasar de alternativa múltiple a única solo se conserva la primera correcta.
        let correctSeen = false;
        questionForm.options = questionForm.options.map((option) => {
            if (! option.is_correct) {
                return option;
            }
            if (correctSeen) {
                return { ...option, is_correct: false };
            }
            correctSeen = true;

            return option;
        });
    }
};

const startQuestionCreate = () => {
    questionEditingId.value = null;
    questionForm.reset();
    questionForm.clearErrors();
    questionForm.type = 'single_choice';
    questionForm.question = '';
    questionForm.points = 1;
    questionForm.answer_text = '';
    questionForm.options = defaultOptions('single_choice');
    questionFormOpen.value = true;
};

const startQuestionEdit = (question) => {
    questionEditingId.value = question.id;
    questionForm.clearErrors();
    questionForm.type = question.type;
    questionForm.question = question.question || '';
    questionForm.points = Number(question.points ?? 1);
    questionForm.answer_text = question.answer_text || '';
    questionForm.options = (question.options ?? []).map((option) => ({
        text: option.text,
        is_correct: !! option.is_correct,
    }));

    if (question.type !== 'short_answer' && questionForm.options.length < 2) {
        questionForm.options = defaultOptions(question.type);
    }

    questionFormOpen.value = true;
};

const cancelQuestionForm = () => {
    questionFormOpen.value = false;
    questionEditingId.value = null;
    questionForm.clearErrors();
};

const addQuestionOption = () => {
    if (questionForm.options.length >= 10) {
        return;
    }

    questionForm.options = [...questionForm.options, { text: '', is_correct: false }];
};

const removeQuestionOption = (index) => {
    if (questionForm.options.length <= 2) {
        return;
    }

    const wasCorrect = questionForm.options[index]?.is_correct;
    const options = questionForm.options.filter((_, position) => position !== index);

    if (wasCorrect && ! options.some((option) => option.is_correct)) {
        options[0] = { ...options[0], is_correct: true };
    }

    questionForm.options = options;
};

const markCorrectOption = (index) => {
    if (questionForm.type === 'multiple_choice') {
        questionForm.options = questionForm.options.map((option, position) =>
            position === index ? { ...option, is_correct: ! option.is_correct } : option
        );

        return;
    }

    questionForm.options = questionForm.options.map((option, position) => ({
        ...option,
        is_correct: position === index,
    }));
};

const saveQuestion = () => {
    const wasEditing = !! questionEditingId.value;
    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            questionFormOpen.value = false;
            questionEditingId.value = null;
            showToast(wasEditing ? 'Pregunta actualizada correctamente' : 'Pregunta agregada correctamente');
        },
        onError: (errors) => showToast(Object.values(errors)[0] || 'Revisa la pregunta', 'error'),
    };

    if (wasEditing) {
        questionForm.post(route('aca_virtual_classroom_question_update', questionEditingId.value), options);
    } else {
        questionForm.post(route('aca_virtual_classroom_question_store', questionsActivityId.value), options);
    }
};

const destroyQuestion = (question) => {
    confirmAction({
        title: '¿Eliminar pregunta?',
        text: 'La pregunta y sus alternativas dejarán de contar para la calificación automática.',
        icon: 'warning',
        confirmButtonText: 'Sí, eliminar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('aca_virtual_classroom_question_destroy', question.id), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => showToast('Pregunta eliminada'),
            });
        }
    });
};

const correctOptionsLabel = (question) => {
    if (question.type === 'short_answer') {
        return question.answer_text ? `Respuestas aceptadas: ${question.answer_text}` : 'Sin respuestas configuradas';
    }

    const correct = (question.options ?? []).filter((option) => option.is_correct).map((option) => option.text);

    return correct.length ? correct.join(', ') : 'Sin respuesta correcta';
};

/* ------------------------------- Entregas ----------------------------- */
const submissionsModal = ref(false);
const submissionsActivityId = ref(null);
const gradeDrafts = ref({});
const gradingId = ref(null);
const reviewDrafts = ref({});
const reviewingId = ref(null);
const reviewOpenId = ref(null);

const submissionsActivity = computed(() => props.activities.find((item) => item.id === submissionsActivityId.value) ?? null);

const openSubmissions = (activity) => {
    submissionsActivityId.value = activity.id;
    gradeDrafts.value = {};
    reviewDrafts.value = {};
    reviewOpenId.value = null;

    activity.submissions.forEach((submission) => {
        gradeDrafts.value[submission.id] = {
            score: submission.score,
            grade_text: submission.grade_text || '',
            feedback: submission.feedback || '',
        };
        reviewDrafts.value[submission.id] = Object.fromEntries(
            (submission.open_answers || []).map((answer) => [answer.question_id, answer.points_earned])
        );
    });

    submissionsModal.value = true;
};

const toggleReview = (submission) => {
    reviewOpenId.value = reviewOpenId.value === submission.id ? null : submission.id;
};

// Guarda los puntos que el docente asigna a las respuestas abiertas y refresca
// la nota recalculada para que no se sobrescriba con un valor antiguo.
const saveReview = (submission) => {
    if (! submission.attempt_id) {
        return;
    }

    const draft = reviewDrafts.value[submission.id] || {};
    const answers = Object.entries(draft).map(([questionId, points]) => ({
        question_id: Number(questionId),
        points_earned: Number(points || 0),
    }));

    reviewingId.value = submission.id;

    router.post(
        route('aca_virtual_classroom_attempt_review', submission.attempt_id),
        { answers },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                const activity = (page.props.activities || []).find((item) => item.id === submissionsActivityId.value);
                const updated = (activity?.submissions || []).find((item) => item.id === submission.id);

                if (updated) {
                    gradeDrafts.value[submission.id] = {
                        score: updated.score,
                        grade_text: updated.grade_text || '',
                        feedback: updated.feedback || '',
                    };
                    reviewDrafts.value[submission.id] = Object.fromEntries(
                        (updated.open_answers || []).map((answer) => [answer.question_id, answer.points_earned])
                    );
                }

                showToast('Revisión guardada y nota recalculada');
            },
            onError: (errors) => showToast(Object.values(errors)[0] || 'No se pudo guardar la revisión', 'error'),
            onFinish: () => {
                reviewingId.value = null;
            },
        }
    );
};

const saveGrade = (submission) => {
    gradingId.value = submission.id;

    router.post(
        route('aca_virtual_classroom_submission_grade', submission.id),
        gradeDrafts.value[submission.id],
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => showToast('Calificación guardada'),
            onFinish: () => {
                gradingId.value = null;
            },
        }
    );
};

/* --------------------------- Copia de contenido ----------------------- */
const copyPrevious = () => {
    confirmAction({
        title: '¿Copiar contenido del año anterior?',
        text: 'Se copiarán unidades, sesiones, recursos y actividades del curso equivalente del año anterior. No se copian entregas ni calificaciones.',
        icon: 'warning',
        confirmButtonText: 'Sí, copiar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('aca_virtual_classroom_copy_previous', props.course.id), {}, {
                preserveScroll: true,
                onSuccess: () => showToast('Contenido copiado correctamente'),
            });
        }
    });
};
</script>

<template>
    <AppLayout :title="course.name">
        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Académico'"
            :data="[
                { route: route('aca_virtual_classroom_index'), title: 'Aula Virtual' },
                { title: course.name },
            ]"
        />

        <div class="pt-5">
            <!-- Ficha del curso -->
            <div class="panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 shrink-0 rounded-full bg-primary/10 flex items-center justify-center">
                            <font-awesome-icon :icon="faBookOpen" class="w-5 h-5 text-primary" />
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold">{{ course.name }}</h2>
                            <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-white-dark mt-1">
                                <span v-if="course.level_name">{{ course.level_name }}</span>
                                <span v-if="course.section_label">{{ course.section_label }}</span>
                                <span v-if="course.area_name">{{ course.area_name }}</span>
                                <span v-if="course.year">Año {{ course.year }}</span>
                                <span v-if="course.teacher_name">Docente: {{ course.teacher_name }}</span>
                                <span v-else class="badge bg-warning">Sin docente asignado</span>
                            </div>
                        </div>
                    </div>
                    <Link :href="route('aca_virtual_classroom_index')" class="btn btn-outline-secondary">
                        <font-awesome-icon :icon="faArrowLeft" class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                        Volver al listado
                    </Link>
                </div>

                <div class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="rounded-lg border border-[#e0e6ed] dark:border-[#1b2e4b] p-3">
                        <div class="text-xs text-white-dark uppercase tracking-wide">Unidades</div>
                        <div class="text-lg font-semibold mt-1">{{ units.length }}</div>
                    </div>
                    <div class="rounded-lg border border-[#e0e6ed] dark:border-[#1b2e4b] p-3">
                        <div class="text-xs text-white-dark uppercase tracking-wide">Sesiones</div>
                        <div class="text-lg font-semibold mt-1">{{ totalLessons }}</div>
                    </div>
                    <div class="rounded-lg border border-[#e0e6ed] dark:border-[#1b2e4b] p-3">
                        <div class="text-xs text-white-dark uppercase tracking-wide">Recursos</div>
                        <div class="text-lg font-semibold mt-1">{{ resources.length }}</div>
                    </div>
                    <div class="rounded-lg border border-[#e0e6ed] dark:border-[#1b2e4b] p-3">
                        <div class="text-xs text-white-dark uppercase tracking-wide">Actividades</div>
                        <div class="text-lg font-semibold mt-1">{{ activities.length }}</div>
                    </div>
                </div>

                <div v-if="yearIsActive && canCopy" class="mt-4 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 p-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        <strong>Curso vacío:</strong> puedes copiar unidades, sesiones, recursos y actividades del año anterior.
                    </p>
                    <button type="button" class="btn btn-warning btn-sm" @click="copyPrevious">
                        <font-awesome-icon :icon="faCopy" class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                        Copiar contenido del año anterior
                    </button>
                </div>
            </div>

            <div v-if="! yearIsActive" class="mt-4 rounded-md bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 p-4 text-sm text-blue-700 dark:text-blue-300">
                <strong>Modo historial:</strong> este curso pertenece a un año cerrado. Solo está disponible para consulta.
            </div>

            <!-- Unidades didácticas -->
            <div class="mt-5 panel p-0 border-0 overflow-hidden">
                <div class="p-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold flex items-center gap-2">
                            <font-awesome-icon :icon="faLayerGroup" class="w-4 h-4 text-primary" />
                            Unidades didácticas
                        </h3>
                        <p class="text-xs text-white-dark mt-0.5">Organiza el curso por unidades CNEB y agrega sus sesiones de aprendizaje.</p>
                    </div>
                    <button v-if="canManageUnits" type="button" class="btn btn-primary" @click="openUnitCreate">
                        <font-awesome-icon :icon="faPlus" class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                        Nueva unidad
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="!text-center">Acciones</th>
                                <th class="!text-center w-12">N°</th>
                                <th>Unidad</th>
                                <th>Fechas</th>
                                <th class="!text-center">Sesiones</th>
                                <th class="!text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="units.length === 0">
                                <td colspan="6" class="text-center text-white-dark py-6">
                                    Aún no hay unidades didácticas.
                                    <template v-if="canManageUnits">Usa "Nueva unidad" para empezar.</template>
                                </td>
                            </tr>
                            <template v-for="(unit, index) in units" :key="unit.id">
                                <tr>
                                    <td>
                                        <div class="flex gap-1 items-center justify-center">
                                            <button
                                                v-if="canManageUnits"
                                                type="button"
                                                class="btn btn-sm btn-outline-info"
                                                title="Editar unidad"
                                                @click="openUnitEdit(unit)"
                                            >
                                                <font-awesome-icon :icon="faPencil" />
                                            </button>
                                            <button
                                                v-if="canManageUnits"
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Eliminar unidad"
                                                @click="destroyUnit(unit)"
                                            >
                                                <font-awesome-icon :icon="faTrash" />
                                            </button>
                                        </div>
                                    </td>
                                    <td class="!text-center font-semibold">{{ index + 1 }}</td>
                                    <td>
                                        <div class="font-semibold">{{ unit.title }}</div>
                                        <div v-if="unit.purpose" class="text-xs text-white-dark mt-0.5 max-w-md truncate">{{ unit.purpose }}</div>
                                    </td>
                                    <td class="text-xs">{{ unitDateRange(unit) }}</td>
                                    <td class="!text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary" @click="toggleUnit(unit)">
                                            {{ unit.lessons.length }} sesión(es)
                                            <font-awesome-icon
                                                :icon="expandedUnitId === unit.id ? faChevronUp : faChevronDown"
                                                class="w-3 h-3 ltr:ml-1 rtl:mr-1"
                                            />
                                        </button>
                                    </td>
                                    <td class="!text-center">
                                        <span class="badge" :class="unit.status ? 'bg-success' : 'bg-secondary'">
                                            {{ unit.status ? 'Publicada' : 'Borrador' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="expandedUnitId === unit.id" class="!bg-gray-50 dark:!bg-[#1a2941]">
                                    <td colspan="6">
                                        <div class="p-2 space-y-4">
                                            <dl
                                                v-if="unit.significant_situation || unit.competencies || unit.capabilities || unit.performances || unit.duration_hours"
                                                class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-xs"
                                            >
                                                <div v-if="unit.significant_situation" class="md:col-span-2">
                                                    <dt class="text-white-dark uppercase tracking-wide">Situación significativa</dt>
                                                    <dd class="mt-1 whitespace-pre-line">{{ unit.significant_situation }}</dd>
                                                </div>
                                                <div v-if="unit.competencies">
                                                    <dt class="text-white-dark uppercase tracking-wide">Competencias</dt>
                                                    <dd class="mt-1 whitespace-pre-line">{{ unit.competencies }}</dd>
                                                </div>
                                                <div v-if="unit.capabilities">
                                                    <dt class="text-white-dark uppercase tracking-wide">Capacidades</dt>
                                                    <dd class="mt-1 whitespace-pre-line">{{ unit.capabilities }}</dd>
                                                </div>
                                                <div v-if="unit.performances" class="md:col-span-2">
                                                    <dt class="text-white-dark uppercase tracking-wide">Desempeños esperados</dt>
                                                    <dd class="mt-1 whitespace-pre-line">{{ unit.performances }}</dd>
                                                </div>
                                                <div v-if="unit.duration_hours">
                                                    <dt class="text-white-dark uppercase tracking-wide">Horas</dt>
                                                    <dd class="mt-1">{{ unit.duration_hours }} h</dd>
                                                </div>
                                            </dl>

                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <h4 class="font-semibold text-sm">Sesiones de aprendizaje</h4>
                                                <button v-if="canManageLessons" type="button" class="btn btn-sm btn-outline-primary" @click="openLessonCreate(unit)">
                                                    <font-awesome-icon :icon="faPlus" class="w-3 h-3 ltr:mr-1 rtl:ml-1" />
                                                    Nueva sesión
                                                </button>
                                            </div>

                                            <div v-if="unit.lessons.length === 0" class="text-sm text-white-dark">
                                                Esta unidad aún no tiene sesiones registradas.
                                            </div>
                                            <div v-else class="table-responsive bg-white dark:bg-[#0e1726] rounded-md">
                                                <table class="table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th class="!text-center">Acciones</th>
                                                            <th class="!text-center w-12">N°</th>
                                                            <th>Sesión</th>
                                                            <th class="!text-center">Duración</th>
                                                            <th class="!text-center">Estado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(lesson, lIndex) in unit.lessons" :key="lesson.id">
                                                            <td>
                                                                <div class="flex gap-1 items-center justify-center">
                                                                    <button
                                                                        v-if="canManageLessons"
                                                                        type="button"
                                                                        class="btn btn-sm btn-outline-info"
                                                                        title="Editar sesión"
                                                                        @click="openLessonEdit(unit, lesson)"
                                                                    >
                                                                        <font-awesome-icon :icon="faPencil" />
                                                                    </button>
                                                                    <button
                                                                        v-if="canManageLessons"
                                                                        type="button"
                                                                        class="btn btn-sm btn-outline-danger"
                                                                        title="Eliminar sesión"
                                                                        @click="destroyLesson(lesson)"
                                                                    >
                                                                        <font-awesome-icon :icon="faTrash" />
                                                                    </button>
                                                                </div>
                                                            </td>
                                                            <td class="!text-center">{{ lIndex + 1 }}</td>
                                                            <td>
                                                                <div class="font-semibold">{{ lesson.title }}</div>
                                                                <div v-if="lesson.purpose" class="text-xs text-white-dark mt-0.5 max-w-md truncate">{{ lesson.purpose }}</div>
                                                            </td>
                                                            <td class="!text-center">{{ lesson.duration_minutes ? lesson.duration_minutes + ' min' : '—' }}</td>
                                                            <td class="!text-center">
                                                                <span class="badge" :class="lesson.status ? 'bg-success' : 'bg-secondary'">
                                                                    {{ lesson.status ? 'Publicada' : 'Borrador' }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recursos -->
            <div class="mt-5 panel p-0 border-0 overflow-hidden">
                <div class="p-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold flex items-center gap-2">
                            <font-awesome-icon :icon="faPaperclip" class="w-4 h-4 text-primary" />
                            Recursos y materiales
                        </h3>
                        <p class="text-xs text-white-dark mt-0.5">Archivos, enlaces y páginas de texto que verán los estudiantes.</p>
                    </div>
                    <button v-if="canManageResources" type="button" class="btn btn-primary" @click="openResourceCreate">
                        <font-awesome-icon :icon="faPlus" class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                        Nuevo recurso
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="!text-center">Acciones</th>
                                <th>Tipo</th>
                                <th>Recurso</th>
                                <th>Ubicación</th>
                                <th>Visibilidad</th>
                                <th class="!text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="resources.length === 0">
                                <td colspan="6" class="text-center text-white-dark py-6">
                                    Aún no hay recursos.
                                    <template v-if="canManageResources">Usa "Nuevo recurso" para agregar el primero.</template>
                                </td>
                            </tr>
                            <tr v-for="resource in resources" :key="resource.id">
                                <td>
                                    <div class="flex gap-1 items-center justify-center">
                                        <a
                                            v-if="resource.type === 'file' && resource.file_url"
                                            :href="resource.file_url"
                                            target="_blank"
                                            rel="noopener"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Abrir archivo"
                                        >
                                            <font-awesome-icon :icon="faEye" />
                                        </a>
                                        <a
                                            v-else-if="resource.type === 'link' && resource.url"
                                            :href="resource.url"
                                            target="_blank"
                                            rel="noopener"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Abrir enlace"
                                        >
                                            <font-awesome-icon :icon="faEye" />
                                        </a>
                                        <button
                                            v-else-if="resource.type === 'embed'"
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Ver página"
                                            @click="openPage(resource)"
                                        >
                                            <font-awesome-icon :icon="faEye" />
                                        </button>
                                        <button
                                            v-if="canManageResources"
                                            type="button"
                                            class="btn btn-sm btn-outline-info"
                                            title="Editar recurso"
                                            @click="openResourceEdit(resource)"
                                        >
                                            <font-awesome-icon :icon="faPencil" />
                                        </button>
                                        <button
                                            v-if="canManageResources"
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Eliminar recurso"
                                            @click="destroyResource(resource)"
                                        >
                                            <font-awesome-icon :icon="faTrash" />
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" :class="resourceBadge(resource.type)">
                                        <font-awesome-icon :icon="resourceIcon(resource.type)" class="w-3 h-3 ltr:mr-1 rtl:ml-1" />
                                        {{ resourceLabel(resource.type) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="font-semibold">{{ resource.title }}</div>
                                    <div v-if="resource.size_kb" class="text-xs text-white-dark mt-0.5">{{ formatSize(resource.size_kb) }}</div>
                                    <div v-else-if="resource.url" class="text-xs text-white-dark mt-0.5 max-w-xs truncate">{{ resource.url }}</div>
                                </td>
                                <td class="text-xs">
                                    <span v-if="resource.unit_title">{{ resource.unit_title }}</span>
                                    <span v-if="resource.lesson_title" class="text-white-dark"> · {{ resource.lesson_title }}</span>
                                    <span v-if="! resource.unit_title && ! resource.lesson_title" class="text-white-dark">Todo el curso</span>
                                </td>
                                <td class="text-xs">{{ resource.visibility === 'all' ? 'Todos' : 'Estudiantes' }}</td>
                                <td class="!text-center">
                                    <span class="badge" :class="resource.status ? 'bg-success' : 'bg-secondary'">
                                        {{ resource.status ? 'Visible' : 'Oculto' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Actividades -->
            <div class="mt-5 panel p-0 border-0 overflow-hidden">
                <div class="p-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold flex items-center gap-2">
                            <font-awesome-icon :icon="faClipboardList" class="w-4 h-4 text-primary" />
                            Actividades y evaluaciones
                        </h3>
                        <p class="text-xs text-white-dark mt-0.5">Tareas, exámenes y foros con fechas de entrega y calificación.</p>
                    </div>
                    <button v-if="canManageActivities" type="button" class="btn btn-primary" @click="openActivityCreate">
                        <font-awesome-icon :icon="faPlus" class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                        Nueva actividad
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="!text-center">Acciones</th>
                                <th>Tipo</th>
                                <th>Actividad</th>
                                <th>Ubicación</th>
                                <th>Plazo</th>
                                <th class="!text-center">Nota</th>
                                <th class="!text-center">Contenido</th>
                                <th class="!text-center">Entregas</th>
                                <th class="!text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="activities.length === 0">
                                <td colspan="9" class="text-center text-white-dark py-6">
                                    Aún no hay actividades.
                                    <template v-if="canManageActivities">Usa "Nueva actividad" para crear la primera.</template>
                                </td>
                            </tr>
                            <tr v-for="activity in activities" :key="activity.id">
                                <td>
                                    <div class="flex gap-1 items-center justify-center">
                                        <button
                                            v-if="canGrade"
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Revisar entregas"
                                            @click="openSubmissions(activity)"
                                        >
                                            <font-awesome-icon :icon="faEye" />
                                        </button>
                                        <button
                                            v-if="canManageActivities && activity.type === 'quiz'"
                                            type="button"
                                            class="btn btn-sm btn-outline-success"
                                            title="Configurar preguntas y respuestas"
                                            @click="openQuestions(activity)"
                                        >
                                            <font-awesome-icon :icon="faListCheck" />
                                        </button>
                                        <button
                                            v-if="canManageActivities"
                                            type="button"
                                            class="btn btn-sm btn-outline-info"
                                            title="Editar actividad"
                                            @click="openActivityEdit(activity)"
                                        >
                                            <font-awesome-icon :icon="faPencil" />
                                        </button>
                                        <button
                                            v-if="canManageActivities"
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Eliminar actividad"
                                            @click="destroyActivity(activity)"
                                        >
                                            <font-awesome-icon :icon="faTrash" />
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" :class="activityBadge(activity.type)">
                                        <font-awesome-icon :icon="activityIcon(activity.type)" class="w-3 h-3 ltr:mr-1 rtl:ml-1" />
                                        {{ activityLabel(activity.type) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="font-semibold">{{ activity.title }}</div>
                                    <div v-if="activity.instructions" class="text-xs text-white-dark mt-0.5 max-w-sm truncate">{{ activity.instructions }}</div>
                                </td>
                                <td class="text-xs">
                                    <span v-if="activity.unit_title">{{ activity.unit_title }}</span>
                                    <span v-if="activity.lesson_title" class="text-white-dark"> · {{ activity.lesson_title }}</span>
                                    <span v-if="! activity.unit_title && ! activity.lesson_title" class="text-white-dark">Todo el curso</span>
                                </td>
                                <td class="text-xs">{{ activityDeadline(activity) }}</td>
                                <td class="!text-center text-xs">
                                    {{ activity.grading_scale === 'numeric' ? activity.max_score : 'AD-C' }}
                                </td>
                                <td>
                                    <div class="flex flex-wrap items-center justify-center gap-2 text-xs">
                                        <span
                                            v-if="activity.files_count"
                                            class="inline-flex items-center gap-1 text-info"
                                            :title="`${activity.files_count} archivo(s) de apoyo para descargar`"
                                        >
                                            <font-awesome-icon :icon="faPaperclip" class="w-3 h-3" />
                                            {{ activity.files_count }}
                                        </span>                                        <span v-if="activity.type === 'quiz' && activity.questions_count"
                                            class="inline-flex items-center gap-1 text-success"
                                            :title="`${activity.questions_count} pregunta(s) · ${activity.questions_points} puntos`"
                                        >
                                            <font-awesome-icon :icon="faCircleQuestion" class="w-3 h-3" />
                                            {{ activity.questions_count }}
                                        </span>
                                        <span
                                            v-if="activity.type === 'quiz'"
                                            class="badge bg-info"
                                            :title="`${activity.attempts_allowed} intento(s) por alumno · ${activity.duration_minutes} minuto(s) por intento`"
                                        >
                                            {{ activity.attempts_allowed }} intento(s) · {{ activity.duration_minutes }} min
                                        </span>
                                        <span
                                            v-if="activity.type === 'quiz' && ! activity.questions_count"
                                            class="badge bg-warning"
                                            title="El examen aún no tiene preguntas: ábrelo con el botón de preguntas"
                                        >
                                            Sin configurar
                                        </span>
                                        <span v-if="! activity.files_count && activity.type !== 'quiz'" class="text-white-dark">—</span>
                                    </div>
                                </td>
                                <td class="!text-center">
                                    <span
                                        class="badge"
                                        :class="activity.submissions_count > 0 ? 'bg-primary' : 'bg-secondary'"
                                        title="Entregas registradas (incluye evaluaciones calificadas automáticamente)"
                                    >
                                        {{ activity.submissions_count }}
                                    </span>
                                </td>
                                <td class="!text-center">
                                    <span class="badge" :class="activity.status ? 'bg-success' : 'bg-secondary'">
                                        {{ activity.status ? 'Visible' : 'Oculto' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal unidad didáctica -->
        <ModalLarge :show="unitModal" :onClose="closeUnitModal">
            <template #title>{{ editingUnitId ? 'Editar unidad didáctica' : 'Nueva unidad didáctica' }}</template>
            <template #message>Completa los datos CNEB de la unidad. Las sesiones se agregan desde la lista de unidades.</template>
            <template #content>
                <form id="unit-form" class="space-y-4" @submit.prevent="saveUnit">
                    <div v-if="Object.keys(unitForm.errors).length" class="rounded-md bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900 p-3 text-xs text-red-600 dark:text-red-300">
                        <p v-for="(message, field) in unitForm.errors" :key="field">{{ message }}</p>
                    </div>

                    <div>
                        <label class="form-label">Título *</label>
                        <input v-model="unitForm.title" type="text" maxlength="200" class="form-input" placeholder="Ej. Unidad 1: Nos reconocemos y valoramos" />
                    </div>
                    <div>
                        <label class="form-label">Propósito</label>
                        <textarea v-model="unitForm.purpose" rows="2" maxlength="500" class="form-textarea" placeholder="¿Qué aprenderán los estudiantes en esta unidad?"></textarea>
                    </div>
                    <div>
                        <label class="form-label">Situación significativa (CNEB)</label>
                        <textarea v-model="unitForm.significant_situation" rows="3" class="form-textarea"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Competencias</label>
                            <textarea v-model="unitForm.competencies" rows="3" class="form-textarea"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Capacidades</label>
                            <textarea v-model="unitForm.capabilities" rows="3" class="form-textarea"></textarea>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Desempeños esperados</label>
                        <textarea v-model="unitForm.performances" rows="3" class="form-textarea"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Horas</label>
                            <input v-model="unitForm.duration_hours" type="number" min="0" max="99" class="form-input" />
                        </div>
                        <div>
                            <label class="form-label">Fecha inicio</label>
                            <input v-model="unitForm.start_date" type="date" class="form-input" />
                        </div>
                        <div>
                            <label class="form-label">Fecha fin</label>
                            <input v-model="unitForm.end_date" type="date" class="form-input" />
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input id="unit_status" v-model="unitForm.status" type="checkbox" class="form-checkbox" />
                        <label for="unit_status" class="ml-2 text-sm">Publicada para los estudiantes</label>
                    </div>
                </form>
            </template>
            <template #buttons>
                <PrimaryButton type="submit" form="unit-form" :disabled="unitForm.processing">
                    {{ unitForm.processing ? 'Guardando...' : 'Guardar' }}
                </PrimaryButton>
            </template>
        </ModalLarge>

        <!-- Modal sesión de aprendizaje -->
        <ModalLarge :show="lessonModal" :onClose="closeLessonModal">
            <template #title>{{ editingLessonId ? 'Editar sesión de aprendizaje' : 'Nueva sesión de aprendizaje' }}</template>
            <template #message>Unidad: {{ currentUnitTitle }}</template>
            <template #content>
                <form id="lesson-form" class="space-y-4" @submit.prevent="saveLesson">
                    <div v-if="Object.keys(lessonForm.errors).length" class="rounded-md bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900 p-3 text-xs text-red-600 dark:text-red-300">
                        <p v-for="(message, field) in lessonForm.errors" :key="field">{{ message }}</p>
                    </div>

                    <div>
                        <label class="form-label">Título *</label>
                        <input v-model="lessonForm.title" type="text" maxlength="200" class="form-input" placeholder="Ej. Sesión 1: Identificamos nuestras emociones" />
                    </div>
                    <div>
                        <label class="form-label">Propósito de la sesión</label>
                        <textarea v-model="lessonForm.purpose" rows="2" class="form-textarea"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Inicio (propósito, saberes previos)</label>
                            <textarea v-model="lessonForm.intro_activity" rows="4" class="form-textarea"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Desarrollo (gestión y acompañamiento)</label>
                            <textarea v-model="lessonForm.development" rows="4" class="form-textarea"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Cierre (socialización)</label>
                            <textarea v-model="lessonForm.closure" rows="4" class="form-textarea"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Metacognición</label>
                            <textarea v-model="lessonForm.metacognition" rows="4" class="form-textarea"></textarea>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Evaluación</label>
                        <textarea v-model="lessonForm.evaluation" rows="3" class="form-textarea"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                        <div>
                            <label class="form-label">Duración (minutos)</label>
                            <input v-model="lessonForm.duration_minutes" type="number" min="0" max="600" class="form-input" />
                        </div>
                        <div class="flex items-center">
                            <input id="lesson_status" v-model="lessonForm.status" type="checkbox" class="form-checkbox" />
                            <label for="lesson_status" class="ml-2 text-sm">Publicada para los estudiantes</label>
                        </div>
                    </div>
                </form>
            </template>
            <template #buttons>
                <PrimaryButton type="submit" form="lesson-form" :disabled="lessonForm.processing">
                    {{ lessonForm.processing ? 'Guardando...' : 'Guardar' }}
                </PrimaryButton>
            </template>
        </ModalLarge>

        <!-- Modal recurso -->
        <ModalLarge :show="resourceModal" :onClose="closeResourceModal">
            <template #title>{{ editingResourceId ? 'Editar recurso' : 'Nuevo recurso' }}</template>
            <template #message>Comparte un archivo, un enlace externo o una página de texto, igual que en Moodle o Chamilo.</template>
            <template #content>
                <form id="resource-form" class="space-y-4" @submit.prevent="saveResource">
                    <div v-if="Object.keys(resourceForm.errors).length" class="rounded-md bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900 p-3 text-xs text-red-600 dark:text-red-300">
                        <p v-for="(message, field) in resourceForm.errors" :key="field">{{ message }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Tipo *</label>
                            <select v-model="resourceForm.type" class="form-select">
                                <option value="file">Archivo</option>
                                <option value="link">Enlace / URL</option>
                                <option value="embed">Página de texto</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Título *</label>
                            <input v-model="resourceForm.title" type="text" maxlength="200" class="form-input" placeholder="Ej. Lectura de la semana" />
                        </div>
                    </div>

                    <div v-if="resourceForm.type === 'file'">
                        <label class="form-label">
                            Archivo {{ editingResourceId && editingResourceFile ? '(deja vacío para conservar el actual)' : '*' }}
                        </label>
                        <input
                            ref="resourceFileInput"
                            type="file"
                            class="form-input"
                            accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.png,.jpg,.jpeg,.webp,.mp4"
                            @change="resourceForm.file = $event.target.files[0] || null"
                        />
                        <p class="text-xs text-white-dark mt-1">PDF, Word, PowerPoint, Excel, ZIP, imagen o MP4. Máximo 10 MB.</p>
                        <p v-if="editingResourceFile" class="text-xs text-white-dark mt-1">Archivo actual: {{ editingResourceFile }}</p>
                    </div>

                    <div v-if="resourceForm.type === 'link'">
                        <label class="form-label">Enlace *</label>
                        <input v-model="resourceForm.url" type="text" maxlength="2000" class="form-input" placeholder="https://www.youtube.com/watch?v=..." />
                    </div>

                    <div v-if="resourceForm.type === 'embed'">
                        <label class="form-label">Contenido de la página *</label>
                        <textarea v-model="resourceForm.content" rows="8" class="form-textarea" placeholder="Escribe aquí el contenido que verán los estudiantes."></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Unidad (opcional)</label>
                            <select v-model="resourceForm.unit_id" class="form-select" @change="resourceForm.lesson_id = null">
                                <option :value="null">Todo el curso</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.title }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Sesión (opcional)</label>
                            <select v-model="resourceForm.lesson_id" class="form-select" :disabled="! resourceLessons.length">
                                <option :value="null">Sin sesión específica</option>
                                <option v-for="lesson in resourceLessons" :key="lesson.id" :value="lesson.id">{{ lesson.title }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                        <div>
                            <label class="form-label">Visibilidad</label>
                            <select v-model="resourceForm.visibility" class="form-select">
                                <option value="students">Estudiantes</option>
                                <option value="all">Todos</option>
                            </select>
                        </div>
                        <div class="flex items-center">
                            <input id="resource_status" v-model="resourceForm.status" type="checkbox" class="form-checkbox" />
                            <label for="resource_status" class="ml-2 text-sm">Visible para estudiantes</label>
                        </div>
                    </div>
                </form>
            </template>
            <template #buttons>
                <PrimaryButton type="submit" form="resource-form" :disabled="resourceForm.processing">
                    {{ resourceForm.processing ? 'Guardando...' : 'Guardar' }}
                </PrimaryButton>
            </template>
        </ModalLarge>

        <!-- Modal actividad -->
        <ModalLarge :show="activityModal" :onClose="closeActivityModal">
            <template #title>{{ editingActivityId ? 'Editar actividad' : 'Nueva actividad' }}</template>
            <template #message>Define qué deben entregar los estudiantes y cómo se calificará.</template>
            <template #content>
                <form id="activity-form" class="space-y-4" @submit.prevent="saveActivity">
                    <div v-if="Object.keys(activityForm.errors).length" class="rounded-md bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900 p-3 text-xs text-red-600 dark:text-red-300">
                        <p v-for="(message, field) in activityForm.errors" :key="field">{{ message }}</p>
                    </div>

                    <div v-if="activityForm.type === 'quiz'" class="rounded-md bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 p-3 text-xs text-blue-700 dark:text-blue-300">
                        Guarda la evaluación y luego usa el botón <strong>Configurar preguntas</strong> de la lista para
                        registrar las preguntas, las alternativas y la respuesta correcta. Así el sistema califica sola.
                    </div>

                    <div v-if="activityForm.type === 'quiz'" class="rounded-lg border border-[#e0e6ed] dark:border-[#1b2e4b] p-3">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                            <div>
                                <label class="form-label">Intentos permitidos</label>
                                <input
                                    v-model.number="activityForm.attempts_allowed"
                                    type="number"
                                    min="1"
                                    max="20"
                                    step="1"
                                    class="form-input"
                                />
                                <p class="text-xs text-white-dark mt-1">
                                    La nota del último intento reemplaza a la anterior.
                                </p>
                            </div>
                            <div>
                                <label class="form-label">Tiempo para resolver (minutos)</label>
                                <input
                                    v-model.number="activityForm.duration_minutes"
                                    type="number"
                                    min="1"
                                    max="300"
                                    step="1"
                                    class="form-input"
                                />
                                <p class="text-xs text-white-dark mt-1">
                                    El tiempo empieza cuando el alumno pulsa «Iniciar examen».
                                </p>
                            </div>
                            <div class="flex items-start gap-2 md:pt-7">
                                <input
                                    id="allow_result_download"
                                    v-model="activityForm.allow_result_download"
                                    type="checkbox"
                                    class="form-checkbox mt-0.5"
                                />
                                <label for="allow_result_download" class="text-sm leading-snug">
                                    Permitir que el alumno descargue su examen resuelto con la nota
                                    <span class="block text-xs text-white-dark mt-0.5">
                                        El PDF muestra sus respuestas y la nota, sin las respuestas correctas.
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Tipo *</label>
                            <select v-model="activityForm.type" class="form-select">
                                <option value="task">Tarea</option>
                                <option value="quiz">Examen</option>
                                <option value="forum">Foro</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Título *</label>
                            <input v-model="activityForm.title" type="text" maxlength="200" class="form-input" placeholder="Ej. Resolvemos problemas de fracciones" />
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Instrucciones</label>
                        <textarea v-model="activityForm.instructions" rows="4" class="form-textarea"></textarea>
                    </div>

                    <div class="rounded-lg border border-[#e0e6ed] dark:border-[#1b2e4b] p-3">
                        <label class="form-label flex items-center gap-2">
                            <font-awesome-icon :icon="faFilePdf" class="w-4 h-4 text-info" />
                            Material de apoyo
                        </label>
                        <p class="text-xs text-white-dark mb-2">
                            Sube la ficha, guía o examen en PDF para que el estudiante la descargue y la resuelva.
                        </p>

                        <ul v-if="editingActivityFiles.length" class="mb-2 space-y-1">
                            <li
                                v-for="file in editingActivityFiles"
                                :key="file.id"
                                class="flex items-center justify-between gap-2 rounded-md bg-gray-50 dark:bg-[#1a2941] px-2 py-1 text-xs"
                            >
                                <a
                                    :href="file.file_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex items-center gap-2 text-primary hover:underline min-w-0"
                                >
                                    <font-awesome-icon :icon="faFilePdf" />
                                    <span class="truncate">{{ file.name }}</span>
                                    <span v-if="file.size_kb" class="text-white-dark">{{ formatSize(file.size_kb) }}</span>
                                </a>
                                <button type="button" class="text-danger" title="Quitar archivo" @click="removeActivityFile(file)">
                                    <font-awesome-icon :icon="faXmark" />
                                </button>
                            </li>
                        </ul>

                        <input
                            ref="activityFileInput"
                            type="file"
                            multiple
                            class="form-input"
                            accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.png,.jpg,.jpeg,.webp"
                            @change="setActivityFiles"
                        />
                        <p class="text-xs text-white-dark mt-1">Puedes subir varios archivos; máximo 10 MB por archivo.</p>
                        <p v-if="activityForm.files.length" class="text-xs text-success mt-1">
                            {{ activityForm.files.length }} archivo(s) listo(s) para guardar.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Unidad (opcional)</label>
                            <select v-model="activityForm.unit_id" class="form-select" @change="activityForm.lesson_id = null">
                                <option :value="null">Todo el curso</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.title }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Sesión (opcional)</label>
                            <select v-model="activityForm.lesson_id" class="form-select" :disabled="! activityLessons.length">
                                <option :value="null">Sin sesión específica</option>
                                <option v-for="lesson in activityLessons" :key="lesson.id" :value="lesson.id">{{ lesson.title }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Disponible desde</label>
                            <input v-model="activityForm.open_at" type="datetime-local" class="form-input" />
                        </div>
                        <div>
                            <label class="form-label">Fecha límite</label>
                            <input v-model="activityForm.close_at" type="datetime-local" class="form-input" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div>
                            <label class="form-label">Escala</label>
                            <select v-model="activityForm.grading_scale" class="form-select">
                                <option value="numeric">Numérica (0-20)</option>
                                <option value="qualitative">Literal (AD/A/B/C)</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Nota máxima</label>
                            <input v-model="activityForm.max_score" type="number" min="0" max="100" step="0.01" class="form-input" />
                        </div>
                        <div class="flex items-center">
                            <input id="allow_late" v-model="activityForm.allow_late" type="checkbox" class="form-checkbox" />
                            <label for="allow_late" class="ml-2 text-sm">Permitir entrega tardía</label>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input id="activity_status" v-model="activityForm.status" type="checkbox" class="form-checkbox" />
                        <label for="activity_status" class="ml-2 text-sm">Visible para estudiantes</label>
                    </div>
                </form>
            </template>
            <template #buttons>
                <PrimaryButton type="submit" form="activity-form" :disabled="activityForm.processing">
                    {{ activityForm.processing ? 'Guardando...' : 'Guardar' }}
                </PrimaryButton>
            </template>
        </ModalLarge>

        <!-- Modal preguntas de la evaluación -->
        <ModalLargeX :show="questionsModal" :onClose="closeQuestions">
            <template #title>Preguntas: {{ questionsActivity?.title }}</template>
            <template #message>
                Configura cada pregunta y marca la respuesta correcta. El sistema califica automáticamente cuando el estudiante termina.
            </template>
            <template #content>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="rounded-lg border border-[#e0e6ed] dark:border-[#1b2e4b] p-3">
                        <div class="text-xs text-white-dark uppercase tracking-wide">Preguntas</div>
                        <div class="text-lg font-semibold mt-1">{{ questionsActivity?.questions_count ?? 0 }}</div>
                    </div>
                    <div class="rounded-lg border border-[#e0e6ed] dark:border-[#1b2e4b] p-3">
                        <div class="text-xs text-white-dark uppercase tracking-wide">Puntos en preguntas</div>
                        <div class="text-lg font-semibold mt-1">{{ questionTotalPoints }}</div>
                    </div>
                    <div class="rounded-lg border border-[#e0e6ed] dark:border-[#1b2e4b] p-3">
                        <div class="text-xs text-white-dark uppercase tracking-wide">Nota de la evaluación</div>
                        <div class="text-lg font-semibold mt-1">{{ questionsActivity?.max_score ?? '—' }}</div>
                    </div>
                    <div
                        class="rounded-lg border p-3"
                        :class="questionPointsExhausted ? 'border-warning/60 bg-warning/10' : 'border-[#e0e6ed] dark:border-[#1b2e4b]'"
                    >
                        <div class="text-xs text-white-dark uppercase tracking-wide">Puntos disponibles</div>
                        <div class="text-lg font-semibold mt-1" :class="questionPointsExhausted ? 'text-warning' : ''">
                            {{ questionAvailablePoints }}
                        </div>
                    </div>
                </div>

                <!-- Lista de preguntas -->
                <div v-if="! questionFormOpen" class="mt-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <p class="text-xs text-white-dark max-w-xl">
                            La nota se obtiene así: puntos logrados ÷ puntos totales × nota de la evaluación. Una respuesta
                            abierta se acepta si coincide con cualquiera de las respuestas configuradas.
                        </p>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="! canAddQuestion"
                            :title="questionPointsExhausted
                                ? 'La evaluación ya tiene asignados todos sus puntos. Ajusta los puntos de las preguntas o cambia la nota máxima de la actividad.'
                                : 'Agregar pregunta'"
                            @click="startQuestionCreate"
                        >
                            <font-awesome-icon :icon="faPlus" class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                            Nueva pregunta
                        </button>
                    </div>

                    <div
                        v-if="questionPointsExhausted"
                        class="rounded-md bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 p-3 text-xs text-amber-700 dark:text-amber-300 mb-3"
                    >
                        La evaluación ya tiene asignados sus {{ questionsActivity?.max_score }} puntos, así que no se pueden
                        agregar más preguntas. Ajusta los puntos de las preguntas existentes o cambia la nota máxima de la
                        actividad.
                    </div>

                    <div class="table-responsive">
                        <table class="table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="!text-center">Acciones</th>
                                    <th class="!text-center w-12">N°</th>
                                    <th>Pregunta</th>
                                    <th>Tipo</th>
                                    <th>Respuesta correcta</th>
                                    <th class="!text-center">Puntos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="(questionsActivity?.questions ?? []).length === 0">
                                    <td colspan="6" class="text-center text-white-dark py-6">
                                        Esta evaluación aún no tiene preguntas. Usa "Nueva pregunta" para agregar la primera.
                                    </td>
                                </tr>
                                <tr v-for="(question, index) in questionsActivity?.questions ?? []" :key="question.id">
                                    <td>
                                        <div class="flex gap-1 items-center justify-center">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-info"
                                                title="Editar pregunta"
                                                @click="startQuestionEdit(question)"
                                            >
                                                <font-awesome-icon :icon="faPencil" />
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Eliminar pregunta"
                                                @click="destroyQuestion(question)"
                                            >
                                                <font-awesome-icon :icon="faTrash" />
                                            </button>
                                        </div>
                                    </td>
                                    <td class="!text-center font-semibold">{{ index + 1 }}</td>
                                    <td>
                                        <div class="font-semibold">{{ question.question }}</div>
                                        <ul v-if="question.type !== 'short_answer'" class="mt-1 space-y-0.5 text-xs text-white-dark">
                                            <li v-for="option in question.options" :key="option.id">
                                                <font-awesome-icon
                                                    :icon="option.is_correct ? faCircleCheck : faCircle"
                                                    class="w-3 h-3 ltr:mr-1 rtl:ml-1"
                                                    :class="option.is_correct ? 'text-success' : 'text-white-dark'"
                                                />
                                                {{ option.text }}
                                            </li>
                                        </ul>
                                    </td>
                                    <td class="text-xs">{{ questionTypeLabels[question.type] ?? question.type }}</td>
                                    <td class="text-xs">{{ correctOptionsLabel(question) }}</td>
                                    <td class="!text-center font-semibold">{{ question.points }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Formulario de pregunta -->
                <form v-else id="question-form" class="mt-4 space-y-4" @submit.prevent="saveQuestion">
                    <div v-if="Object.keys(questionForm.errors).length" class="rounded-md bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900 p-3 text-xs text-red-600 dark:text-red-300">
                        <p v-for="(message, field) in questionForm.errors" :key="field">{{ message }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Tipo de pregunta *</label>
                            <select v-model="questionForm.type" class="form-select" @change="changeQuestionType">
                                <option value="single_choice">Alternativa única</option>
                                <option value="multiple_choice">Alternativa múltiple</option>
                                <option value="true_false">Verdadero / Falso</option>
                                <option value="short_answer">Respuesta abierta</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Puntos *</label>
                            <input
                                v-model="questionForm.points"
                                type="number"
                                min="0.01"
                                :max="questionAvailablePoints || null"
                                step="0.01"
                                class="form-input"
                            />
                            <p class="text-xs text-white-dark mt-1">
                                Puedes asignar hasta <strong>{{ questionAvailablePoints }}</strong> puntos.
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Pregunta *</label>
                        <textarea
                            v-model="questionForm.question"
                            rows="3"
                            maxlength="2000"
                            class="form-textarea"
                            placeholder="Ej. ¿Cuál es el resultado de 3/4 + 1/2?"
                        ></textarea>
                    </div>

                    <div v-if="questionForm.type !== 'short_answer'" class="rounded-lg border border-[#e0e6ed] dark:border-[#1b2e4b] p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <label class="form-label mb-0">Alternativas *</label>
                            <span class="text-xs text-white-dark">
                                {{ questionForm.type === 'multiple_choice' ? 'Marca todas las correctas' : 'Marca la respuesta correcta' }}
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div v-for="(option, index) in questionForm.options" :key="index" class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="shrink-0 w-8 h-8 rounded-full border flex items-center justify-center"
                                    :class="option.is_correct ? 'border-success text-success' : 'border-[#e0e6ed] dark:border-[#1b2e4b] text-white-dark'"
                                    :title="questionForm.type === 'multiple_choice' ? 'Marcar como correcta' : 'Elegir como correcta'"
                                    @click="markCorrectOption(index)"
                                >
                                    <font-awesome-icon :icon="option.is_correct ? faCircleCheck : faCircle" />
                                </button>
                                <input
                                    v-model="questionForm.options[index].text"
                                    type="text"
                                    maxlength="500"
                                    class="form-input"
                                    :placeholder="`Alternativa ${index + 1}`"
                                    :disabled="questionForm.type === 'true_false'"
                                />
                                <button
                                    v-if="questionForm.type !== 'true_false' && questionForm.options.length > 2"
                                    type="button"
                                    class="shrink-0 text-danger"
                                    title="Quitar alternativa"
                                    @click="removeQuestionOption(index)"
                                >
                                    <font-awesome-icon :icon="faXmark" />
                                </button>
                            </div>
                        </div>

                        <button
                            v-if="questionForm.type !== 'true_false' && questionForm.options.length < 10"
                            type="button"
                            class="btn btn-sm btn-outline-primary mt-3"
                            @click="addQuestionOption"
                        >
                            <font-awesome-icon :icon="faPlus" class="w-3 h-3 ltr:mr-1 rtl:ml-1" />
                            Agregar alternativa
                        </button>
                    </div>

                    <div v-else>
                        <label class="form-label">Respuestas aceptadas *</label>
                        <input
                            v-model="questionForm.answer_text"
                            type="text"
                            maxlength="2000"
                            class="form-input"
                            placeholder="Ej. 1.25|5/4|uno punto veinticinco"
                        />
                        <p class="text-xs text-white-dark mt-1">
                            Separa con el símbolo <strong>|</strong> las formas equivalentes. No se distinguen mayúsculas, tildes ni espacios extra.
                        </p>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 pt-3 border-t border-[#e0e6ed] dark:border-[#1b2e4b]">
                        <button type="button" class="btn btn-outline-secondary" @click="cancelQuestionForm">
                            Cancelar
                        </button>
                        <PrimaryButton type="submit" :disabled="questionForm.processing">
                            {{ questionForm.processing ? 'Guardando...' : 'Guardar pregunta' }}
                        </PrimaryButton>
                    </div>
                </form>
            </template>
        </ModalLargeX>

        <!-- Modal entregas y calificación -->
        <ModalLargeX :show="submissionsModal" :onClose="submissionsModal = false">
            <template #title>Entregas: {{ submissionsActivity?.title }}</template>
            <template #message>Revisa y califica lo que subieron los estudiantes.</template>
            <template #content>
                <div v-if="! submissionsActivity || submissionsActivity.submissions.length === 0" class="text-center text-white-dark py-6">
                    Aún no hay entregas registradas para esta actividad.
                </div>

                <div v-else class="table-responsive">
                    <table class="table-striped">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Entregado</th>
                                <th class="!text-center">Estado</th>
                                <th>Nota</th>
                                <th>Comentario</th>
                                <th class="!text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="submission in submissionsActivity.submissions" :key="submission.id">
                            <tr>
                                <td>
                                    <div class="font-semibold">{{ submission.student_name || 'Estudiante' }}</div>
                                    <div class="text-xs text-white-dark">{{ submission.student_code }}</div>
                                    <a v-if="submission.file_url" :href="submission.file_url" target="_blank" rel="noopener" class="text-xs text-primary hover:underline">
                                        Ver archivo
                                    </a>
                                </td>
                                <td class="text-xs">{{ submission.submitted_at || '—' }}</td>
                                <td class="!text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="badge" :class="submission.status === 'graded' ? 'bg-success' : 'bg-warning'">
                                            {{ submission.status === 'graded' ? 'Calificada' : 'Pendiente' }}
                                        </span>
                                        <span v-if="submission.auto_graded" class="badge bg-info" title="Nota calculada por el sistema">
                                            Automática
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <input
                                        v-if="submissionsActivity.grading_scale === 'numeric' && gradeDrafts[submission.id]"
                                        v-model="gradeDrafts[submission.id].score"
                                        type="number"
                                        min="0"
                                        :max="submissionsActivity.max_score"
                                        step="0.01"
                                        class="form-input w-24 py-1"
                                    />
                                    <select
                                        v-else-if="gradeDrafts[submission.id]"
                                        v-model="gradeDrafts[submission.id].grade_text"
                                        class="form-select w-24 py-1"
                                    >
                                        <option value="">—</option>
                                        <option value="AD">AD</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                </td>
                                <td>
                                    <input
                                        v-if="gradeDrafts[submission.id]"
                                        v-model="gradeDrafts[submission.id].feedback"
                                        type="text"
                                        maxlength="2000"
                                        class="form-input py-1"
                                        placeholder="Opcional"
                                    />
                                </td>
                                <td class="!text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            v-if="submission.open_answers && submission.open_answers.length"
                                            type="button"
                                            class="btn btn-sm btn-outline-info"
                                            :title="`Revisar ${submission.open_answers.length} respuesta(s) abierta(s)`"
                                            @click="toggleReview(submission)"
                                        >
                                            <font-awesome-icon :icon="reviewOpenId === submission.id ? faChevronUp : faListCheck" />
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            :disabled="gradingId === submission.id"
                                            @click="saveGrade(submission)"
                                        >
                                            {{ gradingId === submission.id ? 'Guardando...' : 'Guardar' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Revisión manual de las respuestas abiertas -->
                            <tr v-if="reviewOpenId === submission.id">
                                <td colspan="6" class="!py-4 bg-blue-50/60 dark:bg-blue-950/20">
                                    <div class="text-xs font-semibold uppercase tracking-wide mb-2">
                                        Respuestas abiertas · asigna los puntos y el sistema recalcula la nota
                                    </div>
                                    <div class="space-y-2">
                                        <div
                                            v-for="answer in submission.open_answers"
                                            :key="answer.question_id"
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <div class="flex-1 min-w-[220px]">
                                                <div class="text-sm font-semibold">{{ answer.question }}</div>
                                                <div class="text-xs text-white-dark">
                                                    Respuesta: {{ answer.answer_text || '(sin respuesta)' }}
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <input
                                                    v-if="reviewDrafts[submission.id]"
                                                    v-model="reviewDrafts[submission.id][answer.question_id]"
                                                    type="number"
                                                    min="0"
                                                    :max="answer.max_points"
                                                    step="0.01"
                                                    class="form-input w-24 py-1"
                                                />
                                                <span class="text-xs text-white-dark">/ {{ answer.max_points }} pts</span>
                                                <span v-if="answer.manually_graded" class="badge bg-success">Revisada</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end mt-3">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            :disabled="reviewingId === submission.id"
                                            @click="saveReview(submission)"
                                        >
                                            {{ reviewingId === submission.id ? 'Guardando...' : 'Guardar revisión y recalcular nota' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </ModalLargeX>

        <!-- Visor de página de texto -->
        <ModalLarge :show="pageModal" :onClose="pageModal = false">
            <template #title>{{ pageResource?.title }}</template>
            <template #message>Página de texto del curso.</template>
            <template #content>
                <div class="whitespace-pre-line text-sm">{{ pageResource?.content }}</div>
            </template>
        </ModalLarge>
    </AppLayout>
</template>
