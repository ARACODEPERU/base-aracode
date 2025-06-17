<script setup>
    import { ref, onMounted } from "vue";
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import DataTable from 'datatables.net-vue3';
    import DataTablesCore from 'datatables.net';
    import 'datatables.net-responsive';
    import '@/Components/vristo/datatables/datatables.css'
    import '@/Components/vristo/datatables/style.css'
    import 'datatables.net-buttons'; // Importa el plugin de botones
    import 'datatables.net-buttons-dt'; // Importa los estilos de los botones
    import es_PE from '@/Components/vristo/datatables/datatables-es.js'
    import Multiselect from "@suadelabs/vue3-multiselect";
    import "@suadelabs/vue3-multiselect/dist/vue3-multiselect.css";
    import ModalLargeX from "@/Components/ModalLargeX.vue";
    import { useForm } from "@inertiajs/vue3";
    import iconFilePdf from "@/Components/vristo/icon/icon-file-pdf.vue";
    import Swal from "sweetalert2";

    const props = defineProps({
        courses: {
            type: Object,
            default: () => ({})
        }
    });

    DataTable.use(DataTablesCore);
        const columns = [
        {
            data: null,
            render: '#action',
            title: 'Acciones'
        },
        { data: null, render: '#course', title: 'Curso' },
        { data: null, render: '#student', title: 'Estudiante' },
        { data: null, render: '#created',title: 'Fecha Registrado' },
        { data: 'elapsed_time', title: 'Duración' },
        { data: null, render: '#punctuation', title: 'Calificación' },
        { data: null, render: '#status', title: 'Estado' },
    ];

    const options = {
        responsive: true,
        language: es_PE,
        order: [[2, 'desc']],
        ajax: {
            url: route('aca_student_exam_review_exams_table'),
            data: function (d) {
                d.course_id = selectedCourse.value;
            }
        }
    }

    const examStudentTable = ref(null);
    let instance = null;

    onMounted(() => {
        instance = examStudentTable.value?.dt;
    });

    const refreshTable = () => {
        if (instance) {
            instance.ajax.params = function (d) {
                d.course_id = selectedCourse.value;
            };
            instance.ajax.url(route('aca_student_exam_review_exams_table')).load();
        }
    };


    const formatDate = (dateString) => {
        const date = new Date(dateString)
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}:${String(date.getSeconds()).padStart(2, '0')}`
    }

    const courseSelected = ref(null);
    const selectedCourse = ref(null);

    const updateSelected = (query) => {

        selectedCourse.value = query.id;
        examStudentTable.value.dt.ajax.reload();
    }

    const examForm = useForm({
        exam_id: null,
        student_name: null,
        exam_name: null,
        answers: [],
        qualifies: null
    });
    const displayModalQualify = ref(false);
    const contentExam = ref([]);
    const opemModalQualify = (content) => {
        examForm.exam_id = content.id;
        examForm.student_name = content.student.person.full_name;
        examForm.exam_name = content.exam.description;
        examForm.answers = content.details;
        contentExam.value = content.exam;
        displayModalQualify.value = true;
    }
    const closeModalQualify = () => {
        displayModalQualify.value = false;
    }

    const  getStudentAnswer = (questionId) => {
        return examForm.answers.find(ans => ans.id == questionId);
    }

    const isCorrectAnswer = (question, studentAnswer) => {
        if (!studentAnswer || !studentAnswer.answers) return false

        if (question.type_answers === 'Alternativas') {
            const correctAnswer = question.answers.find((a) => a.correct)?.id
            return String(correctAnswer) === String(studentAnswer.answers)
        }

        if (question.type_answers === 'Varias respuestas') {
            const correctAnswers = question.answers
            .filter((a) => a.correct)
            .map((a) => String(a.id))
            .sort()
            const studentAnswers = [...studentAnswer.answers].map(String).sort()
            return JSON.stringify(correctAnswers) === JSON.stringify(studentAnswers)
        }

        return false
    }

    // Calcula puntaje parcial solo para "Varias respuestas"
    const getPartialScore = (question, studentAnswer) => {
        if (!studentAnswer || !Array.isArray(studentAnswer.answers)) return 0

        if (question.type_answers === 'Varias respuestas') {
            const selectedIds = studentAnswer.answers.map(String)
            return question.answers.reduce((sum, answer) => {
                if (answer.correct && selectedIds.includes(String(answer.id))) {
                    return sum + parseFloat(answer.score || 0)
                }
                return sum
            }, 0)
        }

        return isCorrectAnswer(question, studentAnswer) ? question.score : 0
    }

    const inputsQualify = ref([]);

    const saveQualifyAnswer = (question, key) => {
        let calificacion = inputsQualify.value[key];
        examForm.qualifies = {
            question_id: question.id,
            punctuation: calificacion
        }

        axios.post(route('aca_student_grade_exam_response_store'), examForm).then(response => {
            const answer = examForm.answers.find(ans => ans.id == question.id);
            if (answer) {
                answer.punctuation = calificacion;
            }
            refreshTable();
            Swal.fire({
                icon: 'success',
                title: 'Actualizado correctamente',
                text: 'La nota del examen se actualizo a: ' + response.data.punctuation,
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }).catch(error => {
            console.error('Error al calificar:', error);
        });
    }

    const hasValidPunctuation = (questionId) => {
        const answer = examForm.answers.find(ans => ans.id == questionId);
        return answer && !isNaN(answer.punctuation);
    }

    const getAnswerPunctuation = (questionId) => {
        const answer = examForm.answers.find(ans => ans.id == questionId);
        return answer.punctuation;
    }
</script>
<template>
    <AppLayout title="Examenes">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Lista de Alumnos </span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl" style="width: 30%;">Lista de Alumnos </h2>
                <!-- <button @click="refreshTable" type="button" >Refrescar</button> -->
                <div class="flex-1">
                    <div class="w-full">
                        <multiselect
                            id="course_id"
                            :model-value="courseSelected"
                            v-model="courseSelected"
                            :options="courses"
                            class="custom-multiselect"
                            :searchable="true"
                            placeholder="Buscar curso"
                            selected-label="seleccionado"
                            select-label="Elegir"
                            deselect-label="Quitar"
                            label="description"
                            track-by="id"
                            @update:modelValue="updateSelected"
                        ></multiselect>
                    </div>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">

                <DataTable ref="examStudentTable" :options="options" :columns="columns">
                    <template #action="props">
                        <div class="flex gap-4 items-center justify-center">
                            <button @click="opemModalQualify(props.rowData)" type="button" class="btn btn-outline-primary px-2">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path fill="currentColor" d="M362.7 19.3L314.3 67.7 444.3 197.7l48.4-48.4c25-25 25-65.5 0-90.5L453.3 19.3c-25-25-65.5-25-90.5 0zm-71 71L58.6 323.5c-10.4 10.4-18 23.3-22.2 37.4L1 481.2C-1.5 489.7 .8 498.8 7 505s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L421.7 220.3 291.7 90.3z"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                    <template #course="props">
                        <div>
                            {{ props.rowData.exam.course.description }}
                        </div>
                    </template>
                    <template #student="props">
                        <div>
                            <h6 class="font-semibold">
                                {{ props.rowData.student.person.number }}
                            </h6>
                            <span class="block text-xs">
                                {{ props.rowData.student.person.full_name }}
                            </span>
                        </div>
                    </template>
                    <template #created="props">
                        {{ formatDate(props.rowData.created_at) }}
                    </template>
                    <template #punctuation="props">
                        {{ props.rowData.punctuation }}
                    </template>
                    <template #status="props">
                        <div>
                            <span v-if="props.rowData.status == 'terminado'" v-tippy="{ content: 'El alumno termino su examen satisfactoriamente pero falta calificar algunas preguntas.', placement: 'bottom'}" class="badge bg-warning">Terminado</span>
                            <span v-else-if="props.rowData.status == 'pendiente'" v-tippy="{ content: 'Le falta terminar de resolver su examen.', placement: 'bottom'}" class="badge bg-primary">Pendiente</span>
                            <span v-else-if="props.rowData.status == 'calificado'" v-tippy="{ content: 'El examen ya fue calificado correctamente.', placement: 'bottom'}" class="badge bg-success">Calificado</span>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
        <ModalLargeX :show="displayModalQualify" :onClose="closeModalQualify" :icon="'/img/examen.png'">
            <template #title>
                {{ examForm.student_name }}
            </template>
            <template #message>{{ examForm.exam_name }}</template>
            <template #content>
                <div class="p-6">
                    <ol class="w-full space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400">
                        <template v-for="(question, key) in contentExam.questions">
                            <li >
                                <span class="font-semibold text-gray-900 dark:text-white">{{ question.description }}</span>
                                <ul class="ps-5 mt-2 space-y-1 list-none list-inside">
                                <template v-for="(answer, index) in question.answers">
                                    <template v-if="question.type_answers == 'Escribir'">
                                        <li>
                                            {{ answer.description }}
                                            <textarea
                                                id="ctnTextarea"
                                                rows="4"
                                                class="form-textarea"
                                                placeholder="Enter Textarea"
                                                :value="getStudentAnswer(question.id)?.answers || ''"
                                                :disabled="true"
                                            >
                                            </textarea>
                                        </li>
                                    </template>
                                    <template v-if="question.type_answers == 'Alternativas'">
                                        <li>
                                            <label class="inline-flex">
                                                <input
                                                    type="radio"
                                                    :value="answer.id"
                                                    :name="'rbtcolor'+ key"
                                                    class="form-radio peer"
                                                    :disabled="true"
                                                    :checked="getStudentAnswer(question.id)?.answers == answer.id"
                                                />
                                                <span class="peer-checked:text-primary">{{ answer.description }}</span>
                                            </label>
                                        </li>
                                    </template>
                                    <template v-if="question.type_answers == 'Varias respuestas'">
                                        <li>

                                            <label class="inline-flex">
                                                <input
                                                    :checked="getStudentAnswer(question.id)?.answers?.includes(String(answer.id))"
                                                    :value="answer.id"
                                                    type="checkbox"
                                                    class="form-checkbox peer"
                                                    :disabled="true"
                                                />
                                                <span class="peer-checked:text-primary">{{ answer.description }}</span>
                                            </label>

                                        </li>
                                    </template>
                                    <template v-if="question.type_answers == 'Subir Archivo'">
                                        <li v-if="getStudentAnswer(question.id)?.answers">
                                            <a
                                                :href="'/storage/' + getStudentAnswer(question.id).answers"
                                                target="_blank"
                                                class="border border-blue-500 text-blue-700 bg-gray-50 dark:bg-gray-600 flex items-center justify-center rounded-md w-40 px-2 py-1.5 gap-6"
                                                type="button"
                                            >
                                                <icon-file-pdf class="w-4 h-4" />
                                                Ver archivo
                                            </a>
                                        </li>
                                    </template>
                                </template>
                                </ul>
                                <template v-if="['Alternativas', 'Varias respuestas'].includes(question.type_answers)">
                                    <div class="mt-2 flex items-center space-x-2 ps-5">
                                        <template v-if="isCorrectAnswer(question, getStudentAnswer(question.id))">
                                            <!-- ✅ Totalmente correcto -->
                                            <div class="inline-flex flex-wrap gap-2">
                                                <div>
                                                    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium badge badge-outline-primary">
                                                        <svg class="shrink-0 size-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L9 11.586 6.707 9.293a1 1 0 00-1.414 1.414L9 14.414l7.121-7.121a1 1 0 000-1.414z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span>Correcto</span>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium badge badge-outline-primary">
                                                        <span>+{{ question.score }} punto(s)</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </template>

                                        <template v-else-if="question.type_answers === 'Varias respuestas' && getPartialScore(question, getStudentAnswer(question.id)) > 0">
                                            <!-- ⚠️ Parcialmente correcto -->
                                            <div class="inline-flex flex-wrap gap-2">
                                                <div>
                                                    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium badge badge-outline-info">
                                                        <svg class="shrink-0 size-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11H9v4h2V7zm0 6H9v2h2v-2z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <span>Parcialmente correcto</span>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium badge badge-outline-info">
                                                        <span>+{{ getPartialScore(question, getStudentAnswer(question.id)) }} punto(s)</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </template>

                                        <template v-else>
                                            <!-- ❌ Incorrecto -->
                                            <div class="inline-flex flex-wrap gap-2">
                                                <div>
                                                    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium badge badge-outline-danger">
                                                        <svg class="shrink-0 size-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 8.586L5.707 4.293A1 1 0 104.293 5.707L8.586 10l-4.293 4.293a1 1 0 101.414 1.414L10 11.414l4.293 4.293a1 1 0 001.414-1.414L11.414 10l4.293-4.293a1 1 0 00-1.414-1.414L10 8.586z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span>Incorrecto</span>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium badge badge-outline-danger">
                                                        <span>0 puntos</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template v-else-if="hasValidPunctuation(question.id)">
                                    <!-- ✅ Totalmente correcto -->
                                    <div class="inline-flex flex-wrap gap-2 ps-5 mt-4">
                                        <div>
                                            <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium badge badge-outline-primary">
                                                <svg class="shrink-0 size-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L9 11.586 6.707 9.293a1 1 0 00-1.414 1.414L9 14.414l7.121-7.121a1 1 0 000-1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <span>Calificado</span>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium badge badge-outline-primary">
                                                <span>+{{ getAnswerPunctuation(question.id) }} punto(s)</span>
                                            </span>
                                        </div>
                                    </div>
                                </template>
                                <template v-if="!hasValidPunctuation(question.id)">
                                    <div class="mt-6 ps-5" style="width: 40%;">
                                        <label for="addonsRightoutline">Calificar</label>
                                        <div class="flex">
                                            <div
                                                class="bg-[#eee] flex justify-center items-center ltr:rounded-l-md rtl:rounded-r-md px-3 font-semibold border ltr:border-r-0 rtl:border-l-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                                            >
                                                puntos
                                            </div>
                                            <input v-model="inputsQualify[key]" id="addonsRightoutline" type="number" :max="question.score" class="form-input rounded-none" />
                                            <button @click="saveQualifyAnswer(question, key)" type="button" class="btn btn-outline-secondary ltr:rounded-l-none rtl:rounded-r-none">Guardar</button>
                                        </div>
                                    </div>
                                </template>

                            </li>
                        </template>
                    </ol>
                </div>
            </template>
        </ModalLargeX>
    </AppLayout>
</template>
