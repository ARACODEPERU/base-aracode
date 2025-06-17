<script setup>
    import { useForm, usePage } from '@inertiajs/vue3';
    import { useAppStore } from '@/stores/index';
    import Swal from 'sweetalert2';
    import { onMounted, ref } from 'vue';

    const store = useAppStore();
    const xasset = assetUrl;
    const company = usePage().props.company;
    const userData = usePage().props.auth.user;

    const getImage = (path) => {
        return xasset + 'storage/'+ path;
    }

    const props = defineProps({
        content: {
            type: Object,
            default: () => ({})
        },
        isSuccess: {
            type: Boolean,
            default: false
        },
        examStudent: {
            type: Object,
            default: () => ({})
        }
    });

    const examStudentRef = ref({});
    const formExam = ref({
        exam_id: props.content.exam.id,
        questions: [],
        processing: false
    });

    const countdown = ref('⏳ Calculando...');
    let interval = null;

    const showCountdown = (targetDateTimeStr) => {
        const [datePart, timePart] = targetDateTimeStr.split(' ');
        const [year, month, day] = datePart.split('-').map(Number);
        const [hour, minute, second] = timePart.split(':').map(Number);

        // Fecha local
        const targetTime = new Date(year, month - 1, day, hour, minute, second).getTime();

        interval = setInterval(() => {
            const now = new Date().getTime();
            const diff = targetTime - now;

            if (diff <= 0) {
                clearInterval(interval);
                countdown.value = '⏰ Tiempo terminado';
            } else {
                const totalSeconds = Math.floor(diff / 1000);
                const days = Math.floor(totalSeconds / (3600 * 24));
                const hours = Math.floor((totalSeconds % (3600 * 24)) / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                let msg = '';
                if (days > 0) {
                    msg = `⏳ Faltan: ${days}d ${hours.toString().padStart(2, '0')}:${minutes
                        .toString()
                        .padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                } else {
                    msg = `⏳ Faltan: ${hours.toString().padStart(2, '0')}:${minutes
                        .toString()
                        .padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                }

                countdown.value = msg;
            }
        }, 1000);
    };

    onMounted(() => {
        // Ejemplo: Fecha y hora de destino
        const target = props.content.exam.date_end;
        showCountdown(target);

        setDataExamStudent();
        examStudentRef.value = props.examStudent;
    });

    const scoreTotal = ref(0);

    const setDataExamStudent = () => {
        let exam = props.content.exam;
        formExam.value.questions = exam.questions.map(q => {
            scoreTotal.value += parseFloat(q.score);
            return {
                id: q.id,
                type: q.type_answers,
                answers: q.type_answers === 'Varias respuestas' ? [] : q.type_answers === 'Subir Archivo' ? null : '' // dependiendo del tipo
            }
        });
    }

    const onFileChange = (event, key) => {
        const file = event.target.files[0];
        if (file && file.type === 'application/pdf') {
            formExam.value.questions[key].answers = file;
        }
    };

    const saveExamStudent = () =>{
        const formData = new FormData();
        formExam.value.processing = true;
        // Agregar el ID del examen
        formData.append('exam_id', formExam.value.exam_id);

        // Agregar cada pregunta y su respuesta
        formExam.value.questions.forEach((question, index) => {
            formData.append(`questions[${index}][id]`, question.id);
            formData.append(`questions[${index}][type]`, question.type);

            // Si es archivo
            if (question.type === 'Subir Archivo') {
                if (question.answers instanceof File) {
                    formData.append(`questions[${index}][answers]`, question.answers);
                }
            }
            // Si es un array (por ejemplo, múltiples respuestas)
            else if (Array.isArray(question.answers)) {
                question.answers.forEach((ans, i) => {
                    formData.append(`questions[${index}][answers][${i}]`, ans);
                });
            }
            // Si es un solo valor (texto o alternativa)
            else {
                formData.append(`questions[${index}][answers]`, question.answers);
            }
        });

        axios.post(route('aca_student_exam_solve_store'), formData).then((result) => {
            showAlert(result.data.message,'success');
            examStudentRef.value = result.data.examStudent;
        }).finally(() => {
            formExam.value.processing = false;
        });
    }

    const showAlert = async (inTitle, inIcon) => {
        const toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        toast.fire({
            icon: inIcon,
            title: inTitle,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
</script>
<template>
    <nav class="bg-white border-gray-200 dark:bg-gray-900">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <div class="flex items-center space-x-3 rtl:space-x-reverse">
                <template v-if="store.theme === 'light' || store.theme === 'system'">
                    <img v-if="company.isotipo == '/img/isotipo.png'" class="h-8" :src="xasset+company.isotipo" alt="" />
                    <img v-else class="h-8" :src="xasset+'storage/'+company.isotipo" alt="" />
                </template>
                <template v-if="store.theme === 'dark'">
                    <img v-if="company.isotipo_negative == '/img/isotipo_negativo.png'" :src="`${xasset}/img/isotipo_negativo.png`" alt="Logo" class="h-8" />
                    <img v-else :src="`${xasset}storage/${company.isotipo_negative}`" alt="Logo" class="h-8" />
                </template>
                <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">{{ company.name }}</span>
            </div>
            <div class="flex items-center md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">
                <button type="button" class="flex text-sm bg-gray-800 rounded-full md:me-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                    <span class="sr-only">Open user {{ userData.name }}</span>
                    <img v-if="userData.avatar"
                        class="w-8 h-8 rounded-full"
                        :src="getImage(userData.avatar)"
                        :alt="userData.name"
                    />
                    <img v-else :src="`https://ui-avatars.com/api/?name=${userData.name}&size=150&rounded=true`" class="w-8 h-8 rounded-full" :alt="userData.name"/>
                </button>
            </div>
        </div>
    </nav>
    <template v-if="examStudentRef.status == 'pendiente'">
        <template v-if="isSuccess">
            <div class="p-9">
                <div class="panel bg-yellow-50 p-0">
                    <div class="p-6">
                        <h4 class="mb-4 text-4xl text-center font-extrabold leading-none tracking-tight text-gray-900 md:text-5xl lg:text-6xl dark:text-white">{{ content.exam.description }}</h4>
                        <ol class="w-full space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400">
                            <template v-for="(question, key) in content.exam.questions">
                                <li >
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ question.description }}</span>
                                    <ul class="ps-5 mt-2 space-y-1 list-none list-inside">
                                    <template v-for="(answer, index) in question.answers">
                                        <template v-if="question.type_answers == 'Escribir'">
                                            <li>
                                                {{ answer.description }}
                                                <template v-if="formExam.questions.length > 0">
                                                    <textarea v-model="formExam.questions[key].answers" id="ctnTextarea" rows="4" class="form-textarea" placeholder="Enter Textarea" required></textarea>
                                                </template>
                                            </li>
                                        </template>
                                        <template v-if="question.type_answers == 'Alternativas'">
                                            <li>
                                                <template v-if="formExam.questions.length > 0">
                                                    <label class="inline-flex">
                                                        <input v-model="formExam.questions[key].answers" type="radio" :value="answer.id" :name="'rbtcolor'+ key" class="form-radio peer" />
                                                        <span class="peer-checked:text-primary">{{ answer.description }}</span>
                                                    </label>
                                                </template>
                                            </li>
                                        </template>
                                        <template v-if="question.type_answers == 'Varias respuestas'">
                                            <li>
                                                <template v-if="formExam.questions.length > 0">
                                                    <label class="inline-flex">
                                                        <input
                                                            v-model="formExam.questions[key].answers"
                                                            :value="answer.id"
                                                            type="checkbox"
                                                            class="form-checkbox"
                                                            :disabled="formExam.questions[key].answers.length >= question.max_correct_answers && !formExam.questions[key].answers.includes(answer.id)"
                                                        />
                                                        <span>{{ answer.description }}</span>
                                                    </label>
                                                </template>
                                            </li>
                                        </template>
                                        <template v-if="question.type_answers == 'Subir Archivo'">
                                            <li>
                                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" :for="'file_input'+key">{{ answer.description }}</label>
                                                <div class="mb-6">
                                                    <label for="small-file-input" class="sr-only">Choose file</label>
                                                    <input
                                                        @change="onFileChange($event, key)"
                                                        accept="application/pdf"
                                                        :id="'file_input'+key"
                                                        type="file"
                                                        :name="'small-file-input'+key"
                                                        class="block w-full border border-gray-200 shadow-sm rounded-lg text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400
                                                        file:bg-gray-50 file:border-0
                                                        file:me-4
                                                        file:py-2 file:px-4
                                                        dark:file:bg-neutral-700 dark:file:text-neutral-400"
                                                    >
                                                </div>
                                            </li>
                                        </template>
                                    </template>
                                    </ul>
                                </li>
                            </template>
                        </ol>
                    </div>
                    <div class="flex items-center px-10 py-3 bg-gray-50 text-right shadow sm:rounded-bl-md sm:rounded-br-md dark:bg-gray-800">
                        <button
                            @click="saveExamStudent"
                            type="button"
                            class="btn btn-warning rounded-full"
                            :class="{ 'opacity-25': formExam.processing }" :disabled="formExam.processing"
                        >
                            Terminar
                            <svg class="w-5 h-5 ltr:ml-1.5 rtl:mr-1.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path d="M313.4 32.9c26 5.2 42.9 30.5 37.7 56.5l-2.3 11.4c-5.3 26.7-15.1 52.1-28.8 75.2l144 0c26.5 0 48 21.5 48 48c0 18.5-10.5 34.6-25.9 42.6C497 275.4 504 288.9 504 304c0 23.4-16.8 42.9-38.9 47.1c4.4 7.3 6.9 15.8 6.9 24.9c0 21.3-13.9 39.4-33.1 45.6c.7 3.3 1.1 6.8 1.1 10.4c0 26.5-21.5 48-48 48l-97.5 0c-19 0-37.5-5.6-53.3-16.1l-38.5-25.7C176 420.4 160 390.4 160 358.3l0-38.3 0-48 0-24.9c0-29.2 13.3-56.7 36-75l7.4-5.9c26.5-21.2 44.6-51 51.2-84.2l2.3-11.4c5.2-26 30.5-42.9 56.5-37.7zM32 192l64 0c17.7 0 32 14.3 32 32l0 224c0 17.7-14.3 32-32 32l-64 0c-17.7 0-32-14.3-32-32L0 224c0-17.7 14.3-32 32-32z"/>
                            </svg>
                        </button>
                    </div>
                </div>

            </div>
            <div class="fixed bottom-0 end-0 z-60 sm:max-w-[250px] w-full mx-auto p-6">
                <!-- Card -->
                <div class="p-4 bg-white/60 backdrop-blur-lg rounded-lg shadow-2xl dark:bg-neutral-900/60 dark:shadow-black/70">
                    <div class="flex justify-between gap-x-5">
                    <div class="grow">
                        <h2 class="font-semibold text-gray-800 dark:text-white">
                            Tiempo restante
                        </h2>
                    </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-800 dark:text-neutral-200" v-html="countdown"> </p>
                </div>
                <!-- End Card -->
            </div>
        </template>
        <template v-else >
            <div class="max-w-3xl flex flex-col mx-auto size-full">
                <!-- ========== HEADER ========== -->
                <header class="mb-auto flex justify-center z-50 w-full py-4">
                    <nav class="px-4 sm:px-6 lg:px-8">
                    <a class="flex-none text-xl font-semibold sm:text-3xl dark:text-white" href="#" aria-label="Brand">📅 Examen no disponible en este momento</a>
                    </nav>
                </header>
                <!-- ========== END HEADER ========== -->

                <!-- ========== MAIN CONTENT ========== -->
                <main id="content">
                    <div class="text-center py-10 px-4 sm:px-6 lg:px-8">
                        <div class="flex justify-center">
                            <img class="w-60 h-60" src="/img/svg/woman-candado.svg" />
                        </div>
                        <p class="mt-3 text-gray-600 dark:text-neutral-400">Este examen no se encuentra habilitado en este momento. Por favor, revisa la fecha y hora programadas para su disponibilidad.</p>
                        <p class="text-gray-600 dark:text-neutral-400">Si tienes alguna duda, comunícate con tu profesor o el área académica.</p>
                        <div class="mt-5 flex flex-col justify-center items-center gap-2 sm:flex-row sm:gap-3">
                            <a class="w-full sm:w-auto py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none" href="../examples.html">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            Back to examples
                            </a>
                        </div>
                    </div>
                </main>
                <!-- ========== END MAIN CONTENT ========== -->

                <!-- ========== FOOTER ========== -->
                <footer class="mt-auto text-center py-5">
                    <div class="max-w-[85rem] mx-auto px-4 sm:px-6 lg:px-8">
                    <p class="text-sm text-gray-500 dark:text-neutral-500">© Reservados todos los derechos. {{ new Date().getFullYear() }}.</p>
                    </div>
                </footer>
                <!-- ========== END FOOTER ========== -->
            </div>
        </template>
    </template>
    <template v-if="examStudentRef.status == 'terminado'">
        <div class="max-w-3xl flex flex-col mx-auto size-full">
            <!-- ========== HEADER ========== -->
            <header class="mb-auto flex justify-center z-50 w-full py-4">
                <nav class="px-4 sm:px-6 lg:px-8">
                <a class="flex-none text-xl font-semibold sm:text-3xl dark:text-white" href="#" aria-label="Brand">🕓 Examen enviado correctamente</a>
                </nav>
            </header>
            <!-- ========== END HEADER ========== -->

            <!-- ========== MAIN CONTENT ========== -->
            <main id="content">
                <div class="text-center py-10 px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-center">
                        <img class="w-60 h-60" src="/img/svg/online-test-bro.svg" />
                    </div>
                    <p class="mt-3 text-gray-600 dark:text-neutral-400">Has terminado tu examen satisfactoriamente.</p>
                    <p class="text-gray-600 dark:text-neutral-400">Tu calificación aún está pendiente de revisión por el docente.</p>
                    <div class="mt-5 flex flex-col justify-center items-center gap-2 sm:flex-row sm:gap-3">
                        <div class="relative flex items-center border p-3.5 rounded text-primary bg-primary-light border-primary dark:bg-primary-dark-light">
                            <span class="ltr:pr-2 rtl:pl-2">
                                <strong class="ltr:mr-1 rtl:ml-1">Puntuación registrada: </strong>
                                {{ examStudentRef.punctuation ?? 0 }}/{{ scoreTotal }} (provisional)
                            </span>
                        </div>
                    </div>
                </div>
            </main>
            <!-- ========== END MAIN CONTENT ========== -->

            <!-- ========== FOOTER ========== -->
            <footer class="mt-auto text-center py-5">
                <div class="max-w-[85rem] mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500 dark:text-neutral-500">© Reservados todos los derechos. {{ new Date().getFullYear() }}.</p>
                </div>
            </footer>
            <!-- ========== END FOOTER ========== -->
        </div>
    </template>
    <template v-else>
        <div v-if="examStudentRef.punctuation > 0"  class="max-w-3xl flex flex-col mx-auto size-full">
            <!-- ========== HEADER ========== -->
            <header class="mb-auto flex justify-center z-50 w-full py-4">
                <nav class="px-4 sm:px-6 lg:px-8">
                <a class="flex-none text-xl font-semibold sm:text-3xl dark:text-white" href="#" aria-label="Brand">📊 Examen calificado</a>
                </nav>
            </header>
            <!-- ========== END HEADER ========== -->

            <!-- ========== MAIN CONTENT ========== -->
            <main id="content">
                <div class="text-center py-10 px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-center">
                        <img v-if="examStudentRef.punctuation > 14" class="w-60 h-60" src="/img/svg/grades-cuate-approved.svg" />
                        <img v-else-if="examStudentRef.punctuation > 10" class="w-60 h-60" src="/img/svg/teacher-student-cuate.svg" />
                        <img v-else class="w-60 h-60" src="/img/svg/student-stress-rafiki.svg" />
                    </div>
                    <p class="mt-3 text-gray-600 dark:text-neutral-400">Has terminado tu examen satisfactoriamente.</p>
                    <p class="text-gray-600 dark:text-neutral-400">Tu examen ha sido revisado y calificado.</p>
                    <div class="mt-5 flex flex-col justify-center items-center gap-2 sm:flex-row sm:gap-3">
                        <div class="relative flex items-center border p-3.5 rounded text-primary bg-primary-light border-primary dark:bg-primary-dark-light">
                            <span class="ltr:pr-2 rtl:pl-2">
                                <strong class="ltr:mr-1 rtl:ml-1">Puntuación final obtenida: </strong>
                                {{ examStudentRef.punctuation ?? 0 }}/{{ scoreTotal }} {{ examStudentRef.punctuation > 14 ? '¡Buen trabajo!' : examStudentRef.punctuation > 10 ? '¡Lo puedes hacer mejor!' : ''}}
                            </span>
                        </div>
                    </div>
                </div>
            </main>
            <!-- ========== END MAIN CONTENT ========== -->

            <!-- ========== FOOTER ========== -->
            <footer class="mt-auto text-center py-5">
                <div class="max-w-[85rem] mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500 dark:text-neutral-500">© Reservados todos los derechos. {{ new Date().getFullYear() }}.</p>
                </div>
            </footer>
            <!-- ========== END FOOTER ========== -->
        </div>
    </template>
</template>
