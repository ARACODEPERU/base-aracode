<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import Multiselect from '@suadelabs/vue3-multiselect';
import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
import Swal from 'sweetalert2';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Keypad from '@/Components/Keypad.vue';
import iconSearch from '@/Components/vristo/icon/icon-search.vue';
import iconProcessing from '@/Components/vristo/icon/icon-processing.vue';
import iconPlus from '@/Components/vristo/icon/icon-plus.vue';
import iconTrash from '@/Components/vristo/icon/icon-trash.vue';
import EndodonticTreatmentFields from '@/Components/Health/EndodonticTreatmentFields.vue';
import IconAllergySvgrepo from '@/Components/Health/IconAllergySvgrepo.vue';
import { isDentalServiceType, normalizeHealthServiceType, serviceTypesByGroup } from '@/Components/Health/healthOptions.js';
import Odontogram from './Odontogram.vue';

const props = defineProps({
    doctors: {
        type: Array,
        default: () => [],
    },
    allergyTypes: {
        type: Array,
        default: () => [],
    },
    attention: {
        type: Object,
        default: null,
    },
    patientSummary: {
        type: Object,
        default: null,
    },
    currentDoctor: {
        type: Object,
        default: null,
    },
    canChooseDoctor: {
        type: Boolean,
        default: true,
    },
    attentionDefaults: {
        type: Object,
        default: null,
    },
});

const isEdit = computed(() => Boolean(props.attention?.id));
const isSigned = computed(() => Boolean(props.attention?.signed_at));
const canEdit = computed(() => !isSigned.value);

const doctorOption = computed(() => {
    if (!props.attention?.doctor) {
        return null;
    }

    return {
        code: props.attention.doctor.id,
        name: props.attention.doctor.person?.full_name,
        email: props.attention.doctor.person?.email,
        telephone: props.attention.doctor.person?.telephone,
        profession: props.attention.doctor.profession,
        specialty: props.attention.doctor.specialty,
        service_type: normalizeHealthServiceType(props.attention.doctor.attention_service_type || 'general'),
    };
});

const currentDoctorOption = computed(() => {
    if (!props.currentDoctor) {
        return null;
    }

    const doctor = props.doctors.find((item) => item.code === props.currentDoctor.code) || props.currentDoctor;

    return {
        ...doctor,
        service_type: normalizeHealthServiceType(doctor.service_type || 'general'),
    };
});

const cie10Option = (item) => {
    if (!item) {
        return null;
    }

    return {
        id: item.id,
        cie10x: item.cie10x,
        cie10: item.cie10,
        description: item.description,
        code_label: item.cie10x,
        description_label: item.description || item.cie10,
    };
};

const formatDateTime = (value = null) => {
    const date = value ? new Date(value) : new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day} ${hours}:${minutes}`;
};

const splitDateTime = (value = null) => {
    const formatted = formatDateTime(value);

    return {
        date: formatted.slice(0, 10),
        time: formatted.slice(11, 16),
    };
};

const localDateTimeInputValue = (value = null) => formatDateTime(value).replace(' ', 'T');

const attentionWindowLimits = computed(() => {
    const now = new Date();
    const minimum = new Date(now.getTime() - (24 * 60 * 60 * 1000));
    const minMoment = splitDateTime(minimum);
    const maxMoment = splitDateTime(now);
    const selectedDate = form.attention_date;

    let minTime = '00:00';
    let maxTime = '23:59';

    if (selectedDate === minMoment.date) {
        minTime = minMoment.time;
    }

    if (selectedDate === maxMoment.date) {
        maxTime = maxMoment.time;
    }

    return {
        minDate: minMoment.date,
        maxDate: maxMoment.date,
        minTime,
        maxTime,
    };
});

const defaultEndodonticCanal = () => ({
    name: null,
    length: null,
    supported_on: null,
    initial_file: null,
    working_file: null,
    master_file: null,
});

const defaultEndodonticData = () => ({
    tooth: null,
    diagnosis: null,
    session_number: null,
    status: 'proceso',
    is_final_session: false,
    ldr: null,
    lt: null,
    canals: [defaultEndodonticCanal()],
});

const normalizeEndodonticData = (value = null) => {
    const data = {
        ...defaultEndodonticData(),
        ...(value || {}),
    };

    data.session_number = data.session_number ? Number.parseInt(data.session_number, 10) : null;
    data.status = data.is_final_session || data.status === 'finalizado' ? 'finalizado' : 'proceso';
    data.is_final_session = data.status === 'finalizado';
    data.canals = Array.isArray(value?.canals) && value.canals.length
        ? value.canals.map((canal) => ({ ...defaultEndodonticCanal(), ...canal }))
        : [defaultEndodonticCanal()];

    return data;
};

const normalizeTreatment = (treatment = {}) => {
    const treatmentType = treatment.treatment_type || 'farmacologica';

    return {
        treatment_type: treatmentType,
        name: treatment.name || null,
        description: treatment.description || null,
        indications: treatment.indications || null,
        endodontic_data: treatmentType === 'endodontico'
            ? normalizeEndodonticData(treatment.endodontic_data)
            : null,
    };
};

const normalizePendingEndodonticTreatment = (pendingTreatment) => normalizeTreatment({
    treatment_type: 'endodontico',
    name: pendingTreatment.name || 'Tratamiento endodóntico',
    description: pendingTreatment.description || null,
    indications: pendingTreatment.indications || null,
    endodontic_data: {
        ...(pendingTreatment.endodontic_data || {}),
        session_number: pendingTreatment.next_session_number || 1,
        status: 'proceso',
        is_final_session: false,
    },
});

const normalizeProcedureCharge = (charge = {}) => ({
    procedure_id: charge.procedure_id || charge.procedure?.id || null,
    name: charge.name_snapshot || charge.procedure?.name || null,
    price: Number(charge.price ?? charge.procedure?.default_price ?? 0),
    quantity: Number(charge.quantity ?? 1),
    notes: charge.notes || '',
    suggested: false,
});

const initialAttentionMoment = splitDateTime(props.attention?.attention_at);
const defaultDoctorOption = computed(() => {
    if (props.attentionDefaults?.doctor_id) {
        return {
            ...props.attentionDefaults.doctor_id,
            service_type: normalizeHealthServiceType(props.attentionDefaults.doctor_id.service_type || 'general'),
        };
    }

    return props.canChooseDoctor ? doctorOption.value : currentDoctorOption.value;
});

const form = useForm({
    attention_at: `${initialAttentionMoment.date} ${initialAttentionMoment.time}`,
    attention_date: initialAttentionMoment.date,
    attention_time: initialAttentionMoment.time,
    service_type: normalizeHealthServiceType(props.attention?.service_type || props.attentionDefaults?.service_type || defaultDoctorOption.value?.service_type || 'general'),
    patient_id: props.attention?.patient_id || props.attentionDefaults?.patient_id || props.patientSummary?.patient?.id || null,
    doctor_id: defaultDoctorOption.value,
    appointment_id: props.attention?.appointment_id || props.attentionDefaults?.appointment_id || null,
    patient_story: props.attention?.patient_story || props.attentionDefaults?.patient_story || null,
    doctor_observation: props.attention?.doctor_observation || props.attentionDefaults?.doctor_observation || null,
    clinical_findings: props.attention?.clinical_findings || null,
    cie10_id: props.attention?.cie10_id || null,
    diagnosis: props.attention?.diagnosis || null,
    treatment_plan: props.attention?.treatment_plan || null,
    observations: props.attention?.observations || null,
    blood_pressure_systolic: props.attention?.blood_pressure_systolic || null,
    blood_pressure_diastolic: props.attention?.blood_pressure_diastolic || null,
    heart_rate: props.attention?.heart_rate || null,
    respiratory_rate: props.attention?.respiratory_rate || null,
    height: props.attention?.height || null,
    weight: props.attention?.weight || null,
    body_mass_index: props.attention?.body_mass_index || null,
    exams: props.attention?.exams?.length ? props.attention.exams.map((exam) => ({
        exam_type: exam.exam_type,
        name: exam.name,
        description: exam.description,
        result: exam.result,
    })) : [
        { exam_type: 'radiografia', name: null, description: null, result: null },
    ],
    treatments: props.attention?.treatments?.length ? props.attention.treatments.map(normalizeTreatment) : [
        normalizeTreatment(),
    ],
    procedure_charges: props.attention?.charges?.length ? props.attention.charges.map(normalizeProcedureCharge) : [],
    stay_on_attention: false,
    odontogram: props.attention?.odontogram || { teeth: {}, notes: null, condition: 'caries' },
});

const selectedCie10 = ref(cie10Option(props.attention?.cie10));
const cie10CodeOptions = ref(selectedCie10.value ? [selectedCie10.value] : []);
const cie10DescriptionOptions = ref(selectedCie10.value ? [selectedCie10.value] : []);
const cie10SearchTerms = reactive({
    code: '',
    description: '',
});
const showVitalSigns = ref(Boolean(
    props.attention?.blood_pressure_systolic
    || props.attention?.blood_pressure_diastolic
    || props.attention?.heart_rate
    || props.attention?.respiratory_rate
    || props.attention?.height
    || props.attention?.weight
    || props.attention?.body_mass_index
));

const treatmentTypes = [
    { value: 'farmacologica', label: 'Terapia farmacológica' },
    { value: 'endodontico', label: 'Tratamiento endodóntico' },
    { value: 'extraccion_dental', label: 'Extracción dental' },
    { value: 'ortodoncia', label: 'Ortodoncia' },
    { value: 'restauracion_dental', label: 'Restauracion dental' },
    { value: 'protesis_dental', label: 'Protesis dental' },
    { value: 'preventivo', label: 'Tratamientos preventivos' },
    { value: 'otros', label: 'Otros' },
];

const seeker = reactive({
    search: props.attention?.patient?.person?.full_name || props.patientSummary?.patient?.person?.full_name || null,
    loading: false,
    several: false,
    patients: [],
    patient: props.attention?.patient || props.patientSummary?.patient || null,
});

const summary = ref(props.patientSummary);
const failedImages = ref({});
const odontogramLoadedFromHistory = ref(false);
const loadedOdontogramPatientId = ref(null);

const baseUrl = assetUrl;

const blankOdontogram = (condition = form.odontogram?.condition || 'caries') => ({
    teeth: {},
    notes: null,
    condition,
});

const cloneOdontogramTeeth = (teeth = {}) => JSON.parse(JSON.stringify(teeth || {}));

const avatarUrl = (name, size = 300) => `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'Paciente')}&size=${size}&rounded=true`;

const normalizeImagePath = (path) => {
    if (!path) {
        return null;
    }

    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    const cleanBase = String(baseUrl).replace(/\/+$/, '');
    const cleanPath = String(path)
        .replace(/\\/g, '/')
        .replace(/^\/+/, '')
        .replace(/^storage\//, '');

    return `${cleanBase}/storage/${cleanPath.split('/').map(encodeURIComponent).join('/')}`;
};

const patientImageUrl = (patient) => normalizeImagePath(patient?.person?.image);

const imageKey = (patient) => patient?.id ? `patient-${patient.id}` : `person-${patient?.person?.id || 'selected'}`;

const hasPatientImage = (patient) => {
    const key = imageKey(patient);
    return Boolean(patientImageUrl(patient)) && !failedImages.value[key];
};

const markImageFailed = (patient) => {
    failedImages.value = {
        ...failedImages.value,
        [imageKey(patient)]: true,
    };
};

const searchPatients = () => {
    if (!seeker.search) {
        return;
    }

    seeker.loading = true;
    axios.post(route('heal_patients_search'), { search: seeker.search })
        .then((response) => {
            seeker.patients = response.data.success ? response.data.patients : [];
            seeker.several = seeker.patients.length > 0;

            if (!seeker.several) {
                Swal.fire({
                    icon: 'error',
                    title: 'Sin resultados',
                    text: 'No se encontraron pacientes para la busqueda.',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        })
        .finally(() => {
            seeker.loading = false;
        });
};

const selectPatient = (patient) => {
    const patientChanged = form.patient_id !== patient.id;

    form.patient_id = patient.id;
    seeker.patient = patient;
    seeker.search = patient.person.full_name;
    seeker.several = false;

    if (!isEdit.value && patientChanged) {
        form.odontogram = blankOdontogram();
        form.procedure_charges = [];
        odontogramLoadedFromHistory.value = false;
        loadedOdontogramPatientId.value = null;
    }

    loadPatientSummary(patient.id);
};

const addPatientAllergy = () => {
    if (!seeker.patient) {
        return;
    }

    if (!props.allergyTypes.length) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin tipos de alergia',
            text: 'No hay tipos de alergia configurados para registrar.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    Swal.fire({
        title: 'Agregar alergia',
        width: 560,
        html: `
            <div class="text-left">
                <div class="mb-4 rounded-md border border-danger/20 bg-danger/10 px-4 py-3 text-danger">
                    <div class="text-sm font-semibold">${seeker.patient.person?.full_name || 'Paciente seleccionado'}</div>
                    <div class="mt-1 text-xs opacity-80">Registra una alergia relevante para mostrarla en el resumen clínico.</div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="allergy_id" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                            Tipo de alergia
                        </label>
                        <select
                            id="allergy_id"
                            class="form-select w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                        >
                            ${props.allergyTypes.map((allergy) => `<option value="${allergy.id}">${allergy.title}</option>`).join('')}
                        </select>
                    </div>

                    <div>
                        <label for="allergy_description" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                            Descripción
                        </label>
                        <textarea
                            id="allergy_description"
                            class="form-textarea min-h-[120px] w-full resize-none rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                            maxlength="500"
                            placeholder="Ejemplo: reacción a penicilina, urticaria, dificultad respiratoria..."
                        ></textarea>
                        <div class="mt-1 text-xs text-white-dark">Maximo 500 caracteres.</div>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Registrar',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        customClass: {
            popup: 'sweet-alerts text-left',
            title: 'text-xl font-semibold',
            htmlContainer: 'mt-4',
            actions: 'mt-6 flex-row-reverse gap-3',
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-outline-danger',
        },
        buttonsStyling: false,
        preConfirm: () => {
            const allergy = document.getElementById('allergy_id')?.value;
            const description = document.getElementById('allergy_description')?.value?.trim();

            if (!allergy || !description) {
                Swal.showValidationMessage('Selecciona un tipo y escribe la descripción.');
                return false;
            }

            return axios.post(route('heal_allergies_store'), {
                patient: seeker.patient.id,
                allergy,
                description,
            }).then((response) => response.data).catch((error) => {
                Swal.showValidationMessage(error.response?.data?.message || 'No se pudo registrar la alergia.');
            });
        },
    }).then((result) => {
        if (!result.isConfirmed || !result.value?.success) {
            return;
        }

        const allergy = result.value.allergy;

        summary.value = {
            ...(summary.value || {}),
            allergies: [
                allergy,
                ...((summary.value?.allergies || []).filter((item) => item.id !== allergy.id)),
            ].slice(0, 5),
        };

        Swal.fire({
            icon: 'success',
            title: 'Alergia registrada',
            text: 'La alergia se agrego al resumen del paciente.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    });
};

const loadPatientSummary = (patientId) => {
    summary.value = null;

    axios.get(route('heal_attentions_patient_summary', patientId))
        .then((response) => {
            summary.value = response.data;
            loadLatestOdontogram();
            applyPendingEndodonticSessions();
            applyBillingSuggestions();
        });
};

const hasOdontogramRecords = () => Boolean(Object.keys(form.odontogram?.teeth || {}).length);

const loadLatestOdontogram = () => {
    const summaryPatientId = summary.value?.patient?.id || form.patient_id;

    if (
        isEdit.value
        || !summaryPatientId
        || !isDentalServiceType(form.service_type)
        || loadedOdontogramPatientId.value === summaryPatientId
        || hasOdontogramRecords()
        || !summary.value?.latest_odontogram
    ) {
        return;
    }

    form.odontogram = {
        teeth: cloneOdontogramTeeth(summary.value.latest_odontogram.teeth),
        notes: summary.value.latest_odontogram.notes || null,
        condition: form.odontogram?.condition || 'caries',
    };
    odontogramLoadedFromHistory.value = true;
    loadedOdontogramPatientId.value = summaryPatientId;
};

const treatmentToothKey = (treatment) => String(treatment?.endodontic_data?.tooth || '').trim().toLowerCase();

const applyPendingEndodonticSessions = () => {
    if (isEdit.value || !canEdit.value || !summary.value?.active_endodontic_treatments?.length) {
        return;
    }

    const currentKeys = new Set(
        form.treatments
            .filter((treatment) => treatment.treatment_type === 'endodontico')
            .map(treatmentToothKey)
    );

    summary.value.active_endodontic_treatments.forEach((pendingTreatment) => {
        const key = treatmentToothKey(pendingTreatment);

        if (key && currentKeys.has(key)) {
            return;
        }

        form.treatments.push(normalizePendingEndodonticTreatment(pendingTreatment));
        currentKeys.add(key);
    });
};

const billingProcedures = computed(() => summary.value?.billing?.procedures || []);

const selectedChargeProcedureIds = computed(() => new Set(
    form.procedure_charges.map((charge) => Number(charge.procedure_id))
));

const chargeProcedureById = (procedureId) => billingProcedures.value.find((procedure) => Number(procedure.id) === Number(procedureId));
const showProcedureChargeModal = ref(false);
const procedureChargeModalContext = ref('save');
const procedureChargePromptAnswered = ref(false);
const canOfferProcedureCharges = computed(() => canEdit.value && seeker.patient && billingProcedures.value.length > 0);
const procedureChargeModalSubmitText = computed(() => (
    procedureChargeModalContext.value === 'sign' ? 'Guardar y firmar' : 'Guardar atención'
));

const trashConfirmHtml = (message) => `
    <div class="flex flex-col items-center gap-3">
        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-danger/10 text-danger">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20.5 6H3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                <path d="M18.833 8.5L18.373 15.399C18.197 18.054 18.108 19.382 17.243 20.191C16.378 21 15.048 21 12.387 21H11.613C8.953 21 7.622 21 6.757 20.191C5.892 19.382 5.804 18.054 5.627 15.399L5.167 8.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                <path opacity="0.5" d="M6.5 6C7.432 5.978 8.159 5.455 8.439 4.68L8.571 4.286C8.654 4.037 8.696 3.913 8.751 3.807C8.97 3.386 9.376 3.094 9.845 3.019C9.962 3 10.093 3 10.355 3H13.645C13.907 3 14.038 3 14.155 3.019C14.624 3.094 15.03 3.386 15.249 3.807C15.304 3.913 15.346 4.037 15.429 4.286L15.561 4.68C15.841 5.455 16.568 5.978 17.5 6" stroke="currentColor" stroke-width="1.8" />
            </svg>
        </div>
        <p class="m-0 text-sm text-white-dark">${message}</p>
    </div>
`;

const confirmTrashAction = (message) => Swal.fire({
    title: 'Confirmar eliminacion',
    html: trashConfirmHtml(message),
    showCancelButton: true,
    confirmButtonText: 'Eliminar',
    cancelButtonText: 'Cancelar',
    customClass: {
        popup: 'sweet-alerts',
        confirmButton: 'btn btn-danger',
        cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
    },
    buttonsStyling: false,
    reverseButtons: true,
    padding: '2em',
});

const addProcedureCharge = (procedure, options = {}) => {
    if (!procedure || selectedChargeProcedureIds.value.has(Number(procedure.id))) {
        return;
    }

    form.procedure_charges.push({
        procedure_id: procedure.id,
        name: procedure.name,
        price: Number(options.price ?? procedure.default_price ?? 0),
        quantity: Number(options.quantity ?? 1),
        notes: options.notes || '',
        suggested: Boolean(options.suggested),
    });
};

const removeProcedureCharge = (index) => {
    confirmTrashAction('Se quitará este procedimiento/cobro de la atención.').then((result) => {
        if (result.isConfirmed) {
            form.procedure_charges.splice(index, 1);
        }
    });
};

const addManualProcedureCharge = () => {
    const procedure = billingProcedures.value.find((item) => !selectedChargeProcedureIds.value.has(Number(item.id)));
    addProcedureCharge(procedure);
};

const openProcedureChargeModal = (context = 'save') => {
    procedureChargeModalContext.value = context;
    procedureChargePromptAnswered.value = true;

    if (!form.procedure_charges.length) {
        addManualProcedureCharge();
    }

    showProcedureChargeModal.value = true;
};

const closeProcedureChargeModal = () => {
    showProcedureChargeModal.value = false;
};

const applyBillingSuggestions = () => {
    if (isEdit.value || !canEdit.value || !summary.value?.billing) {
        return;
    }

    const consultation = summary.value.billing.consultation_procedure;

    if (summary.value.billing.should_charge_consultation && consultation) {
        addProcedureCharge(consultation, {
            suggested: true,
            notes: 'Consulta sugerida por regla de primera atención o más de 2 meses desde la última atención.',
        });
    }
};

const formatSummaryDate = (value) => {
    if (!value) {
        return 'Sin registro';
    }

    return new Intl.DateTimeFormat('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const toggleVitalSigns = () => {
    showVitalSigns.value = !showVitalSigns.value;
};

const updateBodyMassIndex = () => {
    const height = Number.parseFloat(form.height);
    const weight = Number.parseFloat(form.weight);

    if (!height || !weight) {
        form.body_mass_index = null;
        return;
    }

    const heightInMeters = height > 3 ? height / 100 : height;
    form.body_mass_index = Number(weight / (heightInMeters * heightInMeters)).toFixed(2);
};

const addExam = () => {
    form.exams.push({
        exam_type: 'laboratorio',
        name: null,
        description: null,
        result: null,
    });
};

const removeExam = (index) => {
    confirmTrashAction('Se eliminara este examen complementario.').then((result) => {
        if (result.isConfirmed) {
            form.exams.splice(index, 1);
        }
    });
};

const addTreatment = () => {
    form.treatments.push(normalizeTreatment());
};

const removeTreatment = (index) => {
    confirmTrashAction('Se eliminará este tratamiento de la atención.').then((result) => {
        if (result.isConfirmed) {
            form.treatments.splice(index, 1);
        }
    });
};

const syncEndodonticData = (treatment) => {
    if (treatment.treatment_type === 'endodontico') {
        treatment.endodontic_data = normalizeEndodonticData({
            ...treatment.endodontic_data,
            session_number: treatment.endodontic_data?.session_number || 1,
            status: treatment.endodontic_data?.status || 'proceso',
        });
        return;
    }

    treatment.endodontic_data = null;
};

const queueCie10Search = (search, mode) => {
    cie10SearchTerms[mode] = search || '';
};

const searchCie10 = (mode) => {
    const search = cie10SearchTerms[mode]?.trim();

    if (!search) {
        return;
    }

    axios.get(route('heal_attentions_cie10_search'), {
        params: { search, mode },
    }).then((response) => {
        const options = response.data.map(cie10Option);

        if (mode === 'code') {
            cie10CodeOptions.value = options;
            return;
        }

        cie10DescriptionOptions.value = options;
    });
};

const changeDoctorPin = () => {
    if (!props.currentDoctor) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin doctor vinculado',
            text: 'Tu cuenta no está vinculada a un doctor para modificar el PIN.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    Swal.fire({
        title: 'Cambiar PIN de firma',
        html: `
            <div class="text-left space-y-3">
                <label class="block text-sm font-semibold">PIN actual</label>
                <input id="current_pin" type="password" maxlength="4" inputmode="numeric" class="swal2-input" placeholder="1234">
                <label class="block text-sm font-semibold">Nuevo PIN</label>
                <input id="new_pin" type="password" maxlength="4" inputmode="numeric" class="swal2-input" placeholder="4 digitos">
                <label class="block text-sm font-semibold">Confirmar PIN</label>
                <input id="new_pin_confirmation" type="password" maxlength="4" inputmode="numeric" class="swal2-input" placeholder="4 digitos">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Actualizar PIN',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'sweet-alerts',
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
        },
        buttonsStyling: false,
        reverseButtons: true,
        preConfirm: () => {
            const currentPin = document.getElementById('current_pin')?.value;
            const newPin = document.getElementById('new_pin')?.value;
            const confirmation = document.getElementById('new_pin_confirmation')?.value;

            if (!/^\d{4}$/.test(currentPin || '') || !/^\d{4}$/.test(newPin || '') || newPin !== confirmation) {
                Swal.showValidationMessage('Ingresa PINes de 4 números y confirma correctamente.');
                return false;
            }

            return axios.post(route('heal_doctors_pin_update'), {
                current_pin: currentPin,
                new_pin: newPin,
                new_pin_confirmation: confirmation,
            }).catch((error) => {
                Swal.showValidationMessage(error.response?.data?.message || 'No se pudo actualizar el PIN.');
                return false;
            });
        },
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'PIN actualizado',
                text: 'El nuevo PIN de firma ya está activo.',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    });
};

const sendDoctorAccessReset = () => {
    if (!props.canChooseDoctor || !form.doctor_id?.code) {
        return;
    }

    Swal.fire({
        icon: 'warning',
        title: 'Restablecer acceso del doctor',
        text: 'Se enviará un correo para restablecer su contraseña y su PIN volverá temporalmente a 1234.',
        showCancelButton: true,
        confirmButtonText: 'Enviar correo',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'sweet-alerts',
            confirmButton: 'btn btn-warning',
            cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
        },
        buttonsStyling: false,
        reverseButtons: true,
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        axios.post(route('heal_doctors_access_reset', form.doctor_id.code)).then((response) => {
            Swal.fire({
                icon: 'success',
                title: 'Correo enviado',
                text: response.data.message,
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        });
    });
};

const continueSignAttention = () => {
    Swal.fire({
        icon: 'warning',
        title: 'Firma médico legal',
        html: '<strong>Después de firmar esta atención ya no podrá ser modificada ni eliminada.</strong><br><br>Revisa cuidadosamente relato, hallazgos, diagnóstico, tratamientos, exámenes y odontograma antes de continuar.',
        showCancelButton: true,
        confirmButtonText: 'Entiendo, continuar',
        cancelButtonText: 'Revisar antes',
        customClass: {
            popup: 'sweet-alerts border-4 border-danger',
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
        },
        buttonsStyling: false,
        reverseButtons: true,
        padding: '2em',
    }).then((warning) => {
        if (!warning.isConfirmed) {
            return;
        }

        Swal.fire({
            title: 'Firmar atención',
            html: `
                <div class="text-left space-y-3">
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Hora de firma</label>
                        <input id="signed_at" type="datetime-local" class="swal2-input" value="${localDateTimeInputValue()}">
                        <p class="mt-1 text-xs text-gray-500">Debe ser posterior al inicio de la atención.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold">PIN del doctor</label>
                        <input id="signature_pin" type="password" maxlength="4" inputmode="numeric" class="swal2-input" placeholder="4 números">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Firmar atención',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'sweet-alerts',
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
            },
            buttonsStyling: false,
            reverseButtons: true,
            preConfirm: () => {
                const signedAt = document.getElementById('signed_at')?.value;
                const pin = document.getElementById('signature_pin')?.value;

                if (!signedAt) {
                    Swal.showValidationMessage('Ingresa la hora de firma.');
                    return false;
                }

                if (!/^\d{4}$/.test(pin || '')) {
                    Swal.showValidationMessage('El PIN debe tener 4 números.');
                    return false;
                }

                return axios.post(route('heal_attentions_sign', props.attention.id), {
                    pin,
                    signed_at: signedAt,
                })
                    .catch((error) => {
                        const errors = error.response?.data?.errors || {};
                        const message = Object.values(errors).flat().join(' ');

                        Swal.showValidationMessage(message || error.response?.data?.message || 'No se pudo firmar la atención.');
                        return false;
                    });
            },
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Atención firmada',
                    text: 'El documento quedó bloqueado para modificaciones.',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                }).then(() => {
                    router.reload();
                });
            }
        });
    });
};

const signAttention = () => {
    if (!isEdit.value || isSigned.value) {
        return;
    }

    if (
        canOfferProcedureCharges.value
        && !procedureChargePromptAnswered.value
        && !form.procedure_charges.length
    ) {
        procedureChargePromptAnswered.value = true;

        Swal.fire({
            icon: 'question',
            title: 'Procedimientos/cobros',
            text: '¿Deseas agregar procedimientos y precios antes de firmar esta atención?',
            showCancelButton: true,
            confirmButtonText: 'Si, agregar',
            cancelButtonText: 'Firmar sin cobros',
            customClass: {
                popup: 'sweet-alerts',
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
            },
            buttonsStyling: false,
            reverseButtons: true,
            padding: '2em',
        }).then((result) => {
            if (result.isConfirmed) {
                openProcedureChargeModal('sign');
                return;
            }

            continueSignAttention();
        });

        return;
    }

    continueSignAttention();
};

const submitAttention = ({ afterSuccess = null, showSuccess = true, stayOnAttention = false } = {}) => {
    form.attention_at = `${form.attention_date || ''} ${form.attention_time || ''}`.trim();
    form.stay_on_attention = stayOnAttention;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeProcedureChargeModal();

            if (afterSuccess) {
                afterSuccess();
                return;
            }

            if (!showSuccess) {
                return;
            }

            Swal.fire({
                icon: 'success',
                title: 'Enhorabuena',
                text: 'La atención se guardó correctamente.',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
        onFinish: () => {
            form.stay_on_attention = false;
        },
        onError: (errors) => {
            const message = Object.values(errors || {}).flat().filter(Boolean).join(' ');

            Swal.fire({
                icon: 'error',
                title: 'No se pudo guardar',
                text: message || 'Revisa los campos obligatorios de la atención.',
                customClass: {
                    popup: 'sweet-alerts',
                    confirmButton: 'btn btn-primary',
                },
                buttonsStyling: false,
                padding: '2em',
            });
        },
    };

    if (isEdit.value) {
        form.put(route('heal_attentions_update', props.attention.id), options);
        return;
    }

    form.post(route('heal_attentions_store'), options);
};

const submitProcedureChargeModal = () => {
    if (procedureChargeModalContext.value === 'sign') {
        submitAttention({
            showSuccess: false,
            stayOnAttention: true,
            afterSuccess: continueSignAttention,
        });
        return;
    }

    submitAttention();
};

const saveAttention = () => {
    if (
        canOfferProcedureCharges.value
        && !procedureChargePromptAnswered.value
        && !form.procedure_charges.length
    ) {
        procedureChargePromptAnswered.value = true;

        Swal.fire({
            icon: 'question',
            title: 'Procedimientos/cobros',
            text: '¿Deseas agregar procedimientos y precios antes de guardar esta atención?',
            showCancelButton: true,
            confirmButtonText: 'Si, agregar',
            cancelButtonText: 'Guardar sin cobros',
            customClass: {
                popup: 'sweet-alerts',
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-dark ltr:mr-3 rtl:ml-3',
            },
            buttonsStyling: false,
            reverseButtons: true,
            padding: '2em',
        }).then((result) => {
            if (result.isConfirmed) {
                openProcedureChargeModal();
                return;
            }

            submitAttention();
        });

        return;
    }

    submitAttention();
};

onMounted(() => {
    if (!form.odontogram?.teeth) {
        form.odontogram = blankOdontogram('caries');
    }

    loadLatestOdontogram();
});

watch(() => form.service_type, () => {
    loadLatestOdontogram();
});

watch(() => form.doctor_id, (doctor) => {
    if (!canEdit.value || !doctor?.service_type) {
        return;
    }

    form.service_type = doctor.service_type;
}, { deep: true });

watch(() => [form.height, form.weight], updateBodyMassIndex);

watch(selectedCie10, (value) => {
    form.cie10_id = value?.id || null;
    form.diagnosis = value?.description || value?.cie10 || form.diagnosis;

    if (value) {
        if (!cie10CodeOptions.value.some((item) => item.id === value.id)) {
            cie10CodeOptions.value = [value, ...cie10CodeOptions.value];
        }

        if (!cie10DescriptionOptions.value.some((item) => item.id === value.id)) {
            cie10DescriptionOptions.value = [value, ...cie10DescriptionOptions.value];
        }
    }
});
</script>

<template>
    <form @submit.prevent="saveAttention" class="space-y-5">
        <div v-if="isSigned" class="panel border-danger/40 bg-danger/10 text-danger">
            <div class="font-semibold">Atención firmada</div>
            <div class="text-sm">Este documento médico legal fue firmado el {{ formatSummaryDate(props.attention.signed_at) }} y ya no puede modificarse.</div>
        </div>
        <div v-if="form.errors.appointment_id" class="panel border-danger/40 bg-danger/10 text-danger">
            {{ form.errors.appointment_id }}
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-5">
            <div class="space-y-5 xl:order-2">
                <div class="panel">
                    <div class="flex flex-col items-center">
                        <template v-if="seeker.patient">
                            <div class="flex items-center justify-center gap-3">
                                <img
                                    v-if="hasPatientImage(seeker.patient)"
                                    :src="patientImageUrl(seeker.patient)"
                                    alt=""
                                    class="w-24 h-24 rounded-full object-cover"
                                    @error="markImageFailed(seeker.patient)"
                                />
                                <img
                                    v-else
                                    :src="avatarUrl(seeker.patient.person?.full_name, 300)"
                                    alt=""
                                    class="w-24 h-24 rounded-full object-cover"
                                />
                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm whitespace-nowrap"
                                    @click="addPatientAllergy"
                                >
                                    <IconAllergySvgrepo class="w-4 h-4 mr-1" />
                                    +Alergias
                                </button>
                            </div>
                            <div class="mt-3 text-center">
                                <div class="font-semibold text-primary">{{ seeker.patient.person?.full_name }}</div>
                                <div class="text-xs text-white-dark">{{ seeker.patient.person?.number }}</div>
                            </div>
                        </template>
                        <template v-else>
                            <img src="/img/svg/questions-pana.svg" alt="" class="w-24 h-24 rounded-full object-cover" />
                            <div class="mt-3 text-sm text-white-dark">Busca y selecciona un paciente</div>
                        </template>
                    </div>
                </div>

                <div class="panel">
                    <h3 class="font-semibold text-base dark:text-white mb-4">Resumen del paciente</h3>
                    <template v-if="seeker.patient">
                        <div class="space-y-4 text-sm">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded border border-slate-200 dark:border-slate-700 p-3">
                                    <div class="text-white-dark text-xs">Edad</div>
                                    <div class="font-semibold text-primary">{{ summary?.patient?.age ?? 'Sin dato' }}</div>
                                </div>
                                <div class="rounded border border-slate-200 dark:border-slate-700 p-3">
                                    <div class="text-white-dark text-xs">Última atención</div>
                                    <div class="font-semibold">{{ formatSummaryDate(summary?.last_attention?.attention_at) }}</div>
                                </div>
                            </div>

                            <div>
                                <div class="font-semibold mb-2">Últimos diagnósticos</div>
                                <ul v-if="summary?.diagnoses?.length" class="space-y-2">
                                    <li v-for="item in summary.diagnoses" :key="item.id" class="rounded bg-slate-50 dark:bg-slate-800 p-2">
                                        <div class="text-xs text-white-dark">{{ formatSummaryDate(item.attention_at) }}</div>
                                        <div class="line-clamp-2">{{ item.diagnosis }}</div>
                                    </li>
                                </ul>
                                <div v-else class="text-white-dark text-xs">Sin diagnósticos previos.</div>
                            </div>

                            <div>
                                <div class="font-semibold mb-2">Alergias</div>
                                <ul v-if="summary?.allergies?.length" class="space-y-2">
                                    <li v-for="item in summary.allergies" :key="item.id" class="rounded bg-danger/10 text-danger p-2">
                                        <div class="font-semibold">{{ item.title }}</div>
                                        <div class="text-xs">{{ item.description }}</div>
                                    </li>
                                </ul>
                                <div v-else class="text-white-dark text-xs">Sin alergias registradas.</div>
                            </div>

                            <div>
                                <div class="font-semibold mb-2">Últimos tratamientos</div>
                                <ul v-if="summary?.treatments?.length" class="space-y-2">
                                    <li v-for="item in summary.treatments" :key="item.id" class="rounded bg-slate-50 dark:bg-slate-800 p-2">
                                        <div class="text-xs text-white-dark">{{ formatSummaryDate(item.attention_at) }}</div>
                                        <div class="line-clamp-2">{{ item.treatment_plan }}</div>
                                    </li>
                                </ul>
                                <div v-else class="text-white-dark text-xs">Sin tratamientos previos.</div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="text-sm text-white-dark">Selecciona un paciente para ver su resumen clínico.</div>
                    </template>
                </div>

                <div class="panel">
                    <h3 class="font-semibold text-base dark:text-white mb-4">Firma medica</h3>
                    <template v-if="isSigned">
                        <div class="rounded border border-success/30 bg-success/10 text-success p-3 text-sm">
                            <div class="font-semibold">Documento firmado</div>
                            <div>{{ formatSummaryDate(props.attention.signed_at) }}</div>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-sm text-white-dark mb-4">
                            La firma bloquea la atención para nuevas modificaciones.
                        </p>
                        <button v-if="isEdit" type="button" class="btn btn-danger w-full" @click="signAttention">
                            Firmar atención
                        </button>
                        <div v-else class="text-xs text-white-dark">
                            Guarda la atención para habilitar la firma.
                        </div>
                    </template>

                    <button
                        v-if="props.currentDoctor"
                        type="button"
                        class="btn btn-outline-primary btn-sm w-full mt-4"
                        @click="changeDoctorPin"
                    >
                        Cambiar mi PIN
                    </button>
                </div>
            </div>

            <div class="xl:col-span-3 xl:order-1 space-y-5">
                <div class="panel grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div class="md:col-span-3">
                        <InputLabel value="Paciente" />
                        <div class="relative">
                            <input
                                v-model="seeker.search"
                                type="text"
                                class="form-input pr-10"
                                placeholder="Buscar por nombre o documento"
                                :disabled="!canEdit"
                                @keydown.enter.prevent="searchPatients"
                            />
                            <button type="button" class="btn btn-primary absolute right-1 inset-y-0 my-auto rounded-md w-7 h-7 p-0" :disabled="!canEdit" @click="searchPatients">
                                <icon-processing v-if="seeker.loading" class="w-4 h-4" />
                                <icon-search v-else class="w-4 h-4" />
                            </button>

                            <div v-if="seeker.several" class="absolute z-20 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md mt-1 shadow-lg max-h-64 overflow-auto">
                                <button
                                    v-for="patient in seeker.patients"
                                    :key="patient.id"
                                    type="button"
                                    class="w-full flex items-center gap-3 px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-left"
                                    :disabled="!canEdit"
                                    @click="selectPatient(patient)"
                                >
                                    <img
                                        v-if="hasPatientImage(patient)"
                                        :src="patientImageUrl(patient)"
                                        class="w-10 h-10 rounded-full object-cover"
                                        alt=""
                                        @error="markImageFailed(patient)"
                                    />
                                    <img
                                        v-else
                                        :src="avatarUrl(patient.person.full_name, 80)"
                                        class="w-10 h-10 rounded-full object-cover"
                                        alt=""
                                    />
                                    <span>
                                        <span class="block font-semibold">{{ patient.person.full_name }}</span>
                                        <span class="block text-xs text-white-dark">{{ patient.person.number }}</span>
                                    </span>
                                </button>
                            </div>
                        </div>
                        <InputError :message="form.errors.patient_id" class="mt-1" />
                    </div>

                    <div class="md:col-span-3">
                        <InputLabel value="Doctor que atendió" />
                        <multiselect
                            v-model="form.doctor_id"
                            :options="doctors"
                            class="custom-multiselect"
                            :searchable="true"
                            :disabled="!canEdit || !props.canChooseDoctor"
                            placeholder="Buscar doctor"
                            selected-label="seleccionado"
                            select-label="Elegir"
                            deselect-label="Quitar"
                            label="name"
                            track-by="code"
                        />
                        <div v-if="!props.canChooseDoctor && props.currentDoctor" class="mt-1 text-xs text-white-dark">
                            El doctor se asigna automáticamente a tu usuario.
                        </div>
                        <InputError :message="form.errors.doctor_id_value || form.errors.doctor_id" class="mt-1" />
                        <button
                            v-if="props.currentDoctor"
                            type="button"
                            class="btn btn-outline-primary btn-sm mt-2"
                            @click="changeDoctorPin"
                        >
                            Cambiar mi PIN de firma
                        </button>
                        <button
                            v-if="props.canChooseDoctor && form.doctor_id?.code"
                            type="button"
                            class="btn btn-outline-warning btn-sm mt-2 ml-2"
                            @click="sendDoctorAccessReset"
                        >
                            Restablecer acceso doctor
                        </button>
                    </div>

                    <div>
                        <InputLabel value="Fecha de atención" />
                        <TextInput
                            v-model="form.attention_date"
                            type="date"
                            class="form-input"
                            :min="attentionWindowLimits.minDate"
                            :max="attentionWindowLimits.maxDate"
                            :disabled="!canEdit"
                        />
                        <InputError :message="form.errors.attention_date || form.errors.attention_at" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Hora de atención" />
                        <TextInput
                            v-model="form.attention_time"
                            type="time"
                            class="form-input"
                            :min="attentionWindowLimits.minTime"
                            :max="attentionWindowLimits.maxTime"
                            :disabled="!canEdit"
                        />
                        <InputError :message="form.errors.attention_time" class="mt-1" />
                        <div class="mt-1 text-xs text-white-dark">Solo se permite el momento actual o hasta 24 horas hacia atrás.</div>
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel value="Tipo de atención" />
                        <select v-model="form.service_type" class="form-select" :disabled="!canEdit">
                            <optgroup label="Medicas">
                                <option v-for="type in serviceTypesByGroup('Medica')" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </optgroup>
                            <optgroup label="Odontologicas">
                                <option v-for="type in serviceTypesByGroup('Odontologica')" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </optgroup>
                        </select>
                        <div v-if="form.doctor_id?.specialty" class="text-xs text-white-dark mt-1">
                            Especialidad del doctor: {{ form.doctor_id.specialty }}
                        </div>
                    </div>
                </div>

                <div class="panel">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div>
                    <InputLabel value="Relato del paciente" />
                    <textarea v-model="form.patient_story" class="form-textarea" rows="5" :disabled="!canEdit"></textarea>
                    <InputError :message="form.errors.patient_story" class="mt-1" />
                </div>
                <div>
                    <InputLabel value="Hallazgos por observación del doctor" />
                    <textarea v-model="form.doctor_observation" class="form-textarea" rows="5" :disabled="!canEdit"></textarea>
                    <InputError :message="form.errors.doctor_observation" class="mt-1" />
                </div>
                <div>
                    <InputLabel value="Hallazgos clínicos" />
                    <textarea v-model="form.clinical_findings" class="form-textarea" rows="5" :disabled="!canEdit"></textarea>
                    <InputError :message="form.errors.clinical_findings" class="mt-1" />
                </div>
                <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Código CIE10" />
                        <multiselect
                            v-model="selectedCie10"
                            :options="cie10CodeOptions"
                            class="custom-multiselect"
                            :searchable="true"
                            :internal-search="false"
                            :disabled="!canEdit"
                            placeholder="Buscar código"
                            selected-label="seleccionado"
                            select-label="Elegir"
                            deselect-label="Quitar"
                            label="code_label"
                            track-by="id"
                            @search-change="queueCie10Search($event, 'code')"
                            @keydown.enter.stop.prevent="searchCie10('code')"
                        />
                    </div>
                    <div>
                        <InputLabel value="Descripción CIE10" />
                        <multiselect
                            v-model="selectedCie10"
                            :options="cie10DescriptionOptions"
                            class="custom-multiselect"
                            :searchable="true"
                            :internal-search="false"
                            :disabled="!canEdit"
                            placeholder="Buscar descripción"
                            selected-label="seleccionado"
                            select-label="Elegir"
                            deselect-label="Quitar"
                            label="description_label"
                            track-by="id"
                            @search-change="queueCie10Search($event, 'description')"
                            @keydown.enter.stop.prevent="searchCie10('description')"
                        />
                    </div>
                    <InputError :message="form.errors.cie10_id" class="md:col-span-2 mt-1" />
                </div>
                <div>
                    <InputLabel value="Diagnóstico / comentario clínico" />
                    <textarea v-model="form.diagnosis" class="form-textarea" rows="5" :disabled="!canEdit"></textarea>
                </div>
                <div>
                    <InputLabel value="Plan de tratamiento" />
                    <textarea v-model="form.treatment_plan" class="form-textarea" rows="5" :disabled="!canEdit"></textarea>
                </div>
                <div>
                    <InputLabel value="Observaciones" />
                    <textarea v-model="form.observations" class="form-textarea" rows="5" :disabled="!canEdit"></textarea>
                </div>
                <div class="lg:col-span-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="toggleVitalSigns">
                        <icon-plus class="w-4 h-4 mr-1" />
                        {{ showVitalSigns ? 'Ocultar signos vitales' : 'Agregar signos vitales' }}
                    </button>
                </div>
                <div v-if="showVitalSigns" class="lg:col-span-2 rounded border border-slate-200 p-4 dark:border-slate-700">
                    <div class="mb-3 font-semibold dark:text-white">Signos vitales</div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <InputLabel value="Presión arterial" />
                            <div class="flex items-center gap-2">
                                <TextInput
                                    v-model="form.blood_pressure_systolic"
                                    type="number"
                                    min="1"
                                    max="300"
                                    class="form-input"
                                    placeholder="Sístole"
                                    :disabled="!canEdit"
                                />
                                <span class="text-lg font-semibold text-white-dark">/</span>
                                <TextInput
                                    v-model="form.blood_pressure_diastolic"
                                    type="number"
                                    min="1"
                                    max="200"
                                    class="form-input"
                                    placeholder="Diástole"
                                    :disabled="!canEdit"
                                />
                            </div>
                            <InputError :message="form.errors.blood_pressure_systolic || form.errors.blood_pressure_diastolic" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Frecuencia cardiaca" />
                            <TextInput v-model="form.heart_rate" type="number" min="1" max="300" class="form-input" :disabled="!canEdit" />
                            <InputError :message="form.errors.heart_rate" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Frecuencia respiratoria" />
                            <TextInput v-model="form.respiratory_rate" type="number" min="1" max="120" class="form-input" :disabled="!canEdit" />
                            <InputError :message="form.errors.respiratory_rate" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Talla (cm)" />
                            <TextInput v-model="form.height" type="number" min="1" max="300" step="0.01" class="form-input" :disabled="!canEdit" />
                            <InputError :message="form.errors.height" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Peso (kg)" />
                            <TextInput v-model="form.weight" type="number" min="1" max="500" step="0.01" class="form-input" :disabled="!canEdit" />
                            <InputError :message="form.errors.weight" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="IMC" />
                            <TextInput v-model="form.body_mass_index" type="number" min="1" max="200" step="0.01" class="form-input" disabled />
                            <InputError :message="form.errors.body_mass_index" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div>
                    <h3 class="text-lg font-semibold dark:text-white">Tratamientos</h3>
                    <p class="text-sm text-white-dark">Farmacologicos, dentales, preventivos u otros tratamientos indicados.</p>
                </div>
                <button v-if="canEdit" type="button" class="btn btn-outline-primary btn-sm" @click="addTreatment">
                    <icon-plus class="w-4 h-4 mr-1" />
                    Agregar
                </button>
            </div>

            <div class="space-y-4">
                <div v-for="(treatment, index) in form.treatments" :key="index" class="grid grid-cols-1 md:grid-cols-12 gap-3 border border-slate-200 dark:border-slate-700 rounded p-3">
                    <div class="md:col-span-3">
                        <InputLabel value="Tipo" />
                        <select v-model="treatment.treatment_type" class="form-select" :disabled="!canEdit" @change="syncEndodonticData(treatment)">
                            <option v-for="type in treatmentTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <InputLabel value="Nombre" />
                        <TextInput v-model="treatment.name" :disabled="!canEdit" />
                    </div>
                    <div class="md:col-span-3">
                        <InputLabel value="Descripción" />
                        <textarea v-model="treatment.description" class="form-textarea" rows="2" :disabled="!canEdit"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel value="Indicaciones" />
                        <textarea v-model="treatment.indications" class="form-textarea" rows="2" :disabled="!canEdit"></textarea>
                    </div>
                    <div v-if="canEdit" class="md:col-span-1 flex items-end">
                        <button type="button" class="btn btn-outline-danger w-full" @click="removeTreatment(index)">
                            <icon-trash class="w-4 h-4 mx-auto" />
                        </button>
                    </div>
                    <EndodonticTreatmentFields
                        v-if="treatment.treatment_type === 'endodontico'"
                        v-model="treatment.endodontic_data"
                        :disabled="!canEdit"
                    />
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div>
                    <h3 class="text-lg font-semibold dark:text-white">Exámenes complementarios</h3>
                    <p class="text-sm text-white-dark">Radiografias, tomografias, laboratorio u otros estudios.</p>
                </div>
                <button v-if="canEdit" type="button" class="btn btn-outline-primary btn-sm" @click="addExam">
                    <icon-plus class="w-4 h-4 mr-1" />
                    Agregar
                </button>
            </div>

            <div class="space-y-4">
                <div v-for="(exam, index) in form.exams" :key="index" class="grid grid-cols-1 md:grid-cols-12 gap-3 border border-slate-200 dark:border-slate-700 rounded p-3">
                    <div class="md:col-span-2">
                        <InputLabel value="Tipo" />
                        <select v-model="exam.exam_type" class="form-select" :disabled="!canEdit">
                            <option value="radiografia">Radiografia</option>
                            <option value="tomografia">Tomografia</option>
                            <option value="laboratorio">Laboratorio</option>
                            <option value="otros">Otros</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel value="Nombre" />
                        <TextInput v-model="exam.name" :disabled="!canEdit" />
                    </div>
                    <div class="md:col-span-4">
                        <InputLabel value="Descripción / motivo" />
                        <textarea v-model="exam.description" class="form-textarea" rows="2" :disabled="!canEdit"></textarea>
                    </div>
                    <div class="md:col-span-3">
                        <InputLabel value="Resultado" />
                        <textarea v-model="exam.result" class="form-textarea" rows="2" :disabled="!canEdit"></textarea>
                    </div>
                    <div v-if="canEdit" class="md:col-span-1 flex items-end">
                        <button type="button" class="btn btn-outline-danger w-full" @click="removeExam(index)">
                            <icon-trash class="w-4 h-4 mx-auto" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="odontogramLoadedFromHistory" class="alert alert-info">
            Se cargó el último odontograma registrado del paciente para continuar la evolución.
        </div>

        <Odontogram v-if="isDentalServiceType(form.service_type)" v-model="form.odontogram" :disabled="!canEdit" />

        <div class="flex justify-between items-center">
            <span class="text-primary text-sm">Los campos paciente, doctor y momento de atención son obligatorios.</span>
            <Keypad>
                <template #botones>
                    <PrimaryButton :disabled="form.processing || !canEdit" :class="{ 'opacity-25': form.processing || !canEdit }">
                        {{ isEdit ? 'Actualizar' : 'Guardar' }}
                    </PrimaryButton>
                    <button v-if="isEdit && !isSigned" type="button" class="btn btn-danger ml-2" @click="signAttention">
                        Firmar
                    </button>
                    <Link :href="route('heal_attentions_list')" class="ml-2">
                        <SecondaryButton type="button">Volver</SecondaryButton>
                    </Link>
                </template>
            </Keypad>
        </div>

        <div v-if="showProcedureChargeModal" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 px-4 py-6">
            <div class="w-full max-w-5xl rounded bg-white shadow-xl dark:bg-[#0e1726]">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <div>
                        <h3 class="text-lg font-semibold dark:text-white">Procedimientos/cobros</h3>
                        <p class="text-sm text-white-dark">Agrega los procedimientos y precios antes de guardar la atención.</p>
                    </div>
                    <button type="button" class="btn btn-outline-dark btn-sm" @click="closeProcedureChargeModal">Cerrar</button>
                </div>

                <div class="max-h-[70vh] overflow-y-auto p-5">
                    <div class="mb-4 flex justify-end">
                        <button v-if="canEdit && billingProcedures.length" type="button" class="btn btn-outline-primary btn-sm" @click="addManualProcedureCharge">
                            <icon-plus class="w-4 h-4 mr-1" />
                            Agregar cobro
                        </button>
                    </div>

                    <div v-if="!billingProcedures.length" class="text-sm text-white-dark">No hay procedimientos activos configurados.</div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="(charge, index) in form.procedure_charges"
                            :key="`modal-${charge.procedure_id}-${index}`"
                            class="grid grid-cols-1 gap-3 rounded border border-slate-200 p-3 dark:border-slate-700 md:grid-cols-12"
                        >
                            <div class="md:col-span-4">
                                <InputLabel value="Procedimiento" />
                                <select v-model="charge.procedure_id" class="form-select">
                                    <option v-for="procedure in billingProcedures" :key="procedure.id" :value="procedure.id">
                                        {{ procedure.name }}
                                    </option>
                                </select>
                                <div v-if="charge.suggested" class="mt-1 text-xs text-primary">Sugerido automaticamente</div>
                            </div>
                            <div class="md:col-span-2">
                                <InputLabel value="Precio" />
                                <TextInput v-model="charge.price" type="number" step="0.01" min="0" />
                            </div>
                            <div class="md:col-span-2">
                                <InputLabel value="Cantidad" />
                                <TextInput v-model="charge.quantity" type="number" step="0.01" min="0.01" />
                            </div>
                            <div class="md:col-span-3">
                                <InputLabel value="Nota" />
                                <TextInput v-model="charge.notes" type="text" />
                            </div>
                            <div class="flex items-end md:col-span-1">
                                <button type="button" class="btn btn-outline-danger w-full" @click="removeProcedureCharge(index)">
                                    <icon-trash class="w-4 h-4 mx-auto" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                    <button type="button" class="btn btn-outline-dark" @click="closeProcedureChargeModal">Seguir editando</button>
                    <button type="button" class="btn btn-primary" :disabled="form.processing" @click="submitProcedureChargeModal">
                        {{ procedureChargeModalSubmitText }}
                    </button>
                </div>
            </div>
        </div>
            </div>
        </div>
    </form>
</template>
