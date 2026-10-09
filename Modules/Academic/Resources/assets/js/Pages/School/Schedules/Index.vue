<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import ModalMedium from '@/Components/ModalMedium.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal2 from 'sweetalert2';
import {
    faPlus,
    faPencil,
    faTrash,
    faClock,
    faCopy,
    faClipboardList,
} from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    school: { type: Object, default: null },
    years: { type: Array, default: () => [] },
    yearId: { type: Number, default: null },
    levels: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    sectionId: { type: Number, default: null },
    section: { type: Object, default: null },
    blocks: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    teachers: { type: Array, default: () => [] },
    journeys: { type: Array, default: () => [] },
    journey: { type: Object, default: null },
    days: { type: Array, default: () => [] },
    shifts: { type: Object, default: () => ({}) },
});

/* ----------------------- Ayudas de aviso ----------------------- */
const showMessage = (msg = '', type = 'success') => {
    const toast = Swal2.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3000,
        customClass: { container: 'toast' },
    });
    toast.fire({ icon: type, title: msg, padding: '10px 20px' });
};

const errorMessage = (err, fallback = 'No se pudo guardar') => {
    const errors = err.response?.data?.errors ?? {};

    return err.response?.data?.message ?? Object.values(errors)[0] ?? fallback;
};

/**
 * Los errores de Laravel llegan como arreglo y los del controlador como texto:
 * este ayudante acepta ambos y devuelve siempre un mensaje legible.
 */
const fieldError = (errors, field) => {
    const value = errors?.[field];

    if (! value) {
        return null;
    }

    return Array.isArray(value) ? value[0] : value;
};

const reload = () => router.reload({ preserveScroll: true, preserveState: false });

/* ----------------------- Navegación ----------------------- */
const changeYear = (event) => {
    router.get(
        route('aca_school_schedules_index'),
        { year_id: event.target.value, section_id: props.sectionId },
        { preserveScroll: true, preserveState: false }
    );
};

const changeSection = (event) => {
    router.get(
        route('aca_school_schedules_index'),
        { year_id: props.yearId, section_id: event.target.value },
        { preserveScroll: true, preserveState: false }
    );
};

/* ----------------------- Grilla ----------------------- */
const daysToShow = computed(() => {
    const withBlocks = new Set(props.blocks.map((b) => b.weekday));

    return props.days.filter((day) => day.value <= 6 || withBlocks.has(day.value));
});

const blocksByDay = computed(() => {
    const map = {};

    daysToShow.value.forEach((day) => {
        map[day.value] = props.blocks
            .filter((block) => block.weekday === day.value)
            .sort((a, b) => (a.start_time > b.start_time ? 1 : -1));
    });

    return map;
});

const daySummary = (weekday) => {
    const blocks = blocksByDay.value[weekday] ?? [];
    const minutes = blocks.reduce((total, block) => total + (block.minutes ?? 0), 0);

    return {
        blocks: blocks.length,
        hours: minutes ? (minutes / 60).toFixed(1) : '0',
    };
};

const weekSummary = computed(() => {
    const minutes = props.blocks.reduce((total, block) => total + (block.minutes ?? 0), 0);

    return {
        blocks: props.blocks.length,
        hours: (minutes / 60).toFixed(1),
    };
});

/* ----------------------- Modal de bloque ----------------------- */
const blockModal = ref(false);
const editingBlockId = ref(null);
const savingBlock = ref(false);
const blockForm = ref({
    weekday: 1,
    area_id: null,
    teacher_person_id: null,
    start_time: '07:30',
    end_time: '08:20',
    room: '',
    status: true,
});
const blockErrors = ref({});

/*
 * Docentes del modal de bloque.
 *
 * El combo solo ofrece los docentes que corresponden al nivel de la sección
 * elegida (tutor/auxiliar de una sección del nivel o con bloques ya dictados
 * ahí) y deja primero a los vinculados a esta sección. El check "Mostrar
 * todos" permite agregar a un docente recién registrado que todavía no tiene
 * vinculación, para no dejar el formulario sin opciones.
 */
const showAllTeachers = ref(false);

const sectionTeachers = computed(() => {
    const levelId = props.section?.level_id ?? null;

    if (! levelId) {
        return [];
    }

    return props.teachers.filter((teacher) => (teacher.level_ids ?? []).includes(levelId));
});

const teachersFiltered = computed(() => ! showAllTeachers.value && sectionTeachers.value.length > 0);

const modalTeachers = computed(() => {
    const source = teachersFiltered.value ? sectionTeachers.value : props.teachers;
    const rank = (teacher) => ((teacher.section_ids ?? []).includes(props.sectionId) ? 0 : 1);

    const list = [...source].sort(
        (a, b) => rank(a) - rank(b) || (a.full_name ?? '').localeCompare(b.full_name ?? '')
    );

    // Al editar, el docente ya asignado al bloque no debe desaparecer del
    // combo aunque hoy no cumpla el filtro del nivel (se cambiaría sin querer
    // al guardar).
    const currentId = blockForm.value.teacher_person_id;

    if (currentId && ! list.some((teacher) => teacher.person_id === currentId)) {
        const current = props.teachers.find((teacher) => teacher.person_id === currentId);

        if (current) {
            list.unshift(current);
        }
    }

    return list;
});

const teacherInSection = (teacher) => (teacher.section_ids ?? []).includes(props.sectionId);

const openBlockCreate = (weekday = 1) => {
    editingBlockId.value = null;
    blockErrors.value = {};
    showAllTeachers.value = false;
    blockForm.value = {
        weekday,
        area_id: null,
        teacher_person_id: null,
        start_time: '07:30',
        end_time: '08:20',
        room: '',
        status: true,
    };
    blockModal.value = true;
};

const openBlockEdit = (block) => {
    editingBlockId.value = block.id;
    blockErrors.value = {};
    showAllTeachers.value = false;
    blockForm.value = {
        weekday: block.weekday,
        area_id: block.area_id,
        teacher_person_id: block.teacher_person_id,
        start_time: block.start_time,
        end_time: block.end_time,
        room: block.room ?? '',
        status: true,
    };
    blockModal.value = true;
};

const closeBlockModal = () => {
    blockModal.value = false;
    blockErrors.value = {};
};

const saveBlock = () => {
    savingBlock.value = true;
    blockErrors.value = {};

    const payload = {
        section_id: props.sectionId,
        year_id: props.yearId,
        weekday: blockForm.value.weekday,
        area_id: blockForm.value.area_id || null,
        teacher_person_id: blockForm.value.teacher_person_id || null,
        start_time: blockForm.value.start_time,
        end_time: blockForm.value.end_time,
        room: blockForm.value.room || null,
        status: blockForm.value.status,
    };

    const request = editingBlockId.value
        ? axios.put(route('aca_school_schedules_update', editingBlockId.value), payload)
        : axios.post(route('aca_school_schedules_store'), payload);

    request
        .then(() => {
            closeBlockModal();
            showMessage(editingBlockId.value ? 'Bloque actualizado correctamente' : 'Bloque registrado correctamente');
            reload();
        })
        .catch((err) => {
            blockErrors.value = err.response?.data?.errors ?? {};
            showMessage(errorMessage(err), 'error');
        })
        .finally(() => {
            savingBlock.value = false;
        });
};

const destroyBlock = (block) => {
    Swal2.fire({
        title: '¿Eliminar bloque del horario?',
        text: `${block.area_name ?? 'Sin área'} · ${block.weekday_label} de ${block.start_time} a ${block.end_time}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            axios
                .delete(route('aca_school_schedules_destroy', block.id))
                .then(() => {
                    showMessage('Bloque eliminado');
                    reload();
                })
                .catch((err) => showMessage(errorMessage(err, 'No se pudo eliminar'), 'error'));
        }
    });
};

/* ----------------------- Copiar día ----------------------- */
const copyModal = ref(false);
const savingCopy = ref(false);
const copyErrors = ref({});
const copyForm = ref({ from_weekday: 1, to_weekdays: [], replace: false });

const closeCopyModal = () => {
    copyModal.value = false;
};

const openCopy = (weekday = 1) => {
    copyErrors.value = {};
    copyForm.value = {
        from_weekday: weekday,
        to_weekdays: props.days.filter((day) => day.value <= 5 && day.value !== weekday).map((day) => day.value),
        replace: false,
    };
    copyModal.value = true;
};

const saveCopy = () => {
    savingCopy.value = true;
    copyErrors.value = {};

    axios
        .post(route('aca_school_schedules_copy_day'), {
            section_id: props.sectionId,
            from_weekday: copyForm.value.from_weekday,
            to_weekdays: copyForm.value.to_weekdays,
            replace: copyForm.value.replace,
        })
        .then((res) => {
            copyModal.value = false;
            showMessage(res.data.message);
            reload();
        })
        .catch((err) => {
            copyErrors.value = err.response?.data?.errors ?? {};
            showMessage(errorMessage(err, 'No se pudo copiar'), 'error');
        })
        .finally(() => {
            savingCopy.value = false;
        });
};

/* ----------------------- Copiar de otra sección ----------------------- */
const sectionCopyModal = ref(false);
const savingSectionCopy = ref(false);
const sectionCopyErrors = ref({});
const sectionCopyForm = ref({ source_section_id: null, replace: false });

const closeSectionCopyModal = () => {
    sectionCopyModal.value = false;
};

const openSectionCopy = () => {
    sectionCopyErrors.value = {};
    sectionCopyForm.value = { source_section_id: null, replace: false };
    sectionCopyModal.value = true;
};

const otherSections = computed(() => props.sections.filter((s) => s.id !== props.sectionId));

/**
 * Niveles disponibles para la jornada: la jornada se configura por nivel, con
 * o sin secciones creadas, asi que se usan los niveles de la estructura del
 * colegio. Si el colegio todavia no registro niveles, se cae de vuelta a los
 * niveles que aparecen en las secciones para no dejar el combo vacio.
 */
const levelOptions = computed(() => {
    if (props.levels.length) {
        return props.levels;
    }

    const map = new Map();

    props.sections.forEach((item) => {
        if (item.level_id && ! map.has(item.level_id)) {
            map.set(item.level_id, { level_id: item.level_id, level_name: item.level_name });
        }
    });

    return [...map.values()];
});

const saveSectionCopy = () => {
    savingSectionCopy.value = true;
    sectionCopyErrors.value = {};

    axios
        .post(route('aca_school_schedules_copy_section'), {
            section_id: props.sectionId,
            source_section_id: sectionCopyForm.value.source_section_id,
            replace: sectionCopyForm.value.replace,
        })
        .then((res) => {
            sectionCopyModal.value = false;
            showMessage(res.data.message);
            reload();
        })
        .catch((err) => {
            sectionCopyErrors.value = err.response?.data?.errors ?? {};
            showMessage(errorMessage(err, 'No se pudo copiar'), 'error');
        })
        .finally(() => {
            savingSectionCopy.value = false;
        });
};

/* ----------------------- Jornada ----------------------- */
const journeyModal = ref(false);
const savingJourney = ref(false);
const journeyErrors = ref({});
const journeyForm = ref({
    level_id: null,
    shift: 'manana',
    entry_time: '07:30',
    exit_time: '13:30',
    tolerance_minutes: 10,
    recess_start: '',
    recess_end: '',
});

const closeJourneyModal = () => {
    journeyModal.value = false;
};

const openJourney = (journey = null) => {
    journeyErrors.value = {};

    if (journey) {
        journeyForm.value = {
            level_id: journey.level_id,
            shift: journey.shift,
            entry_time: journey.entry_time,
            exit_time: journey.exit_time,
            tolerance_minutes: journey.tolerance_minutes,
            recess_start: journey.recess_start ?? '',
            recess_end: journey.recess_end ?? '',
        };
    } else {
        journeyForm.value = {
            level_id: props.section?.level_id ?? null,
            shift: props.section?.shift ?? 'manana',
            entry_time: props.journey?.entry_time ?? '07:30',
            exit_time: props.journey?.exit_time ?? '13:30',
            tolerance_minutes: props.journey?.tolerance_minutes ?? 10,
            recess_start: '',
            recess_end: '',
        };
    }

    journeyModal.value = true;
};

const saveJourney = () => {
    savingJourney.value = true;
    journeyErrors.value = {};

    axios
        .post(route('aca_school_schedules_journey_store'), { ...journeyForm.value })
        .then(() => {
            journeyModal.value = false;
            showMessage('Jornada guardada correctamente');
            reload();
        })
        .catch((err) => {
            journeyErrors.value = err.response?.data?.errors ?? {};
            showMessage(errorMessage(err, 'No se pudo guardar la jornada'), 'error');
        })
        .finally(() => {
            savingJourney.value = false;
        });
};

const destroyJourney = (journey) => {
    Swal2.fire({
        title: '¿Eliminar jornada?',
        text: `${journey.level_name} · ${journey.shift_label} (${journey.entry_time} - ${journey.exit_time})`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            axios
                .delete(route('aca_school_schedules_journey_destroy', journey.id))
                .then(() => {
                    showMessage('Jornada eliminada');
                    reload();
                })
                .catch((err) => showMessage(errorMessage(err, 'No se pudo eliminar'), 'error'));
        }
    });
};
</script>

<template>
    <AppLayout title="Horarios">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { title: 'Horarios' },
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">
                    Horarios
                    <span v-if="school" class="text-white-dark text-base">( {{ school.name }} )</span>
                </h2>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" :disabled="!section" @click="openSectionCopy">
                        <font-awesome-icon :icon="faClipboardList" class="w-4 h-4 mr-1" />
                        Copiar de otra sección
                    </button>
                    <button type="button" class="btn btn-outline-primary" @click="openJourney(null)">
                        <font-awesome-icon :icon="faClock" class="w-4 h-4 mr-1" />
                        Jornada del colegio
                    </button>
                </div>
            </div>

            <!-- Sin colegio / sin secciones -->
            <div v-if="!school" class="mt-5 panel p-6 text-center text-white-dark">
                No hay un colegio configurado. Registre el colegio y su estructura académica para cargar el horario.
            </div>

            <div v-else-if="sections.length === 0" class="mt-5 panel p-6 text-center text-white-dark">
                Aún no hay secciones registradas. Cree la estructura académica (nivel, grado y sección) para cargar el horario.
                <div class="mt-3">
                    <a :href="route('aca_school_structure')" class="btn btn-primary">Ir a Estructura Académica</a>
                </div>
            </div>

            <template v-else>
                <!-- Filtros -->
                <div class="mt-5 panel">
                    <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div>
                            <label class="form-label">Año escolar</label>
                            <select class="form-select" :value="yearId" @change="changeYear">
                                <option v-for="year in years" :key="year.id" :value="year.id">
                                    {{ year.year }} {{ year.status === 'active' ? '(activo)' : '' }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Sección</label>
                            <select class="form-select" :value="sectionId" @change="changeSection">
                                <option v-for="item in sections" :key="item.id" :value="item.id">
                                    {{ item.level_name }} · {{ item.label }} — {{ item.shift_label }}
                                </option>
                            </select>
                        </div>
                        <div class="text-sm text-white-dark md:text-right">
                            <div v-if="section">
                                <span class="badge bg-info mr-1">{{ section.level_name }}</span>
                                <span class="badge bg-warning mr-1">{{ section.grade_name }}</span>
                                <span class="badge bg-secondary mr-1">Sección {{ section.name }}</span>
                                <span class="badge bg-dark">{{ section.students }} alumnos</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-5 pb-5 flex flex-wrap items-center gap-3 text-sm">
                        <span v-if="journey" class="badge bg-success">
                            Jornada {{ journey.shift_label }}: {{ journey.entry_time }} - {{ journey.exit_time }}
                            (tolerancia {{ journey.tolerance_minutes }} min)
                        </span>
                        <span v-else class="badge bg-danger">
                            Sin jornada configurada: defina la hora de entrada y salida
                        </span>
                        <span class="text-white-dark">
                            {{ weekSummary.blocks }} bloques por semana · {{ weekSummary.hours }} horas lectivas
                        </span>
                    </div>
                </div>

                <!-- Grilla semanal -->
                <div class="mt-5 space-y-4">
                    <div v-for="day in daysToShow" :key="day.value" class="panel p-0 overflow-hidden">
                        <div class="p-4 flex items-center justify-between flex-wrap gap-3 border-b border-white-light dark:border-dark">
                            <div class="flex items-center gap-3">
                                <h3 class="font-semibold">{{ day.label }}</h3>
                                <span class="text-xs text-white-dark">
                                    {{ daySummary(day.value).blocks }} bloques · {{ daySummary(day.value).hours }} h
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    v-if="daySummary(day.value).blocks > 0"
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    @click="openCopy(day.value)"
                                >
                                    <font-awesome-icon :icon="faCopy" class="w-3.5 h-3.5 mr-1" />
                                    Copiar a otros días
                                </button>
                                <button type="button" class="btn btn-sm btn-primary" @click="openBlockCreate(day.value)">
                                    <font-awesome-icon :icon="faPlus" class="w-3.5 h-3.5 mr-1" />
                                    Agregar bloque
                                </button>
                            </div>
                        </div>

                        <div class="p-4">
                            <p v-if="(blocksByDay[day.value] ?? []).length === 0" class="text-white-dark text-sm">
                                Sin bloques registrados este día.
                            </p>

                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                                <div
                                    v-for="block in blocksByDay[day.value]"
                                    :key="block.id"
                                    class="border rounded-md p-3 border-white-light dark:border-dark"
                                >
                                    <div class="flex items-start justify-between">
                                        <span class="badge bg-primary">{{ block.start_time }} - {{ block.end_time }}</span>
                                        <div class="flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-info" @click="openBlockEdit(block)">
                                                <font-awesome-icon :icon="faPencil" />
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" @click="destroyBlock(block)">
                                                <font-awesome-icon :icon="faTrash" />
                                            </button>
                                        </div>
                                    </div>
                                    <p class="mt-2 font-semibold">{{ block.area_name ?? 'Sin área' }}</p>
                                    <p class="text-xs text-white-dark">
                                        {{ block.teacher_name ?? 'Sin docente asignado' }}
                                    </p>
                                    <p v-if="block.room" class="text-xs text-white-dark mt-1">Aula: {{ block.room }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Modal de bloque -->
        <ModalMedium :show="blockModal" :onClose="closeBlockModal" :icon="'/img/aula.png'">
            <template #title>
                {{ editingBlockId ? 'Editar bloque del horario' : 'Nuevo bloque del horario' }}
            </template>
            <template #message>
                Cada bloque relaciona una sección, un área curricular, el docente que la dicta y su día y hora.
            </template>
            <template #content>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Día *</label>
                            <select v-model="blockForm.weekday" class="form-select">
                                <option v-for="day in days" :key="day.value" :value="day.value">{{ day.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Aula (opcional)</label>
                            <input v-model="blockForm.room" type="text" maxlength="40" class="form-input" placeholder="Ej. Aula 2" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Hora de inicio *</label>
                            <input v-model="blockForm.start_time" type="time" class="form-input" />
                            <p v-if="fieldError(blockErrors, 'start_time')" class="text-danger text-xs mt-1">{{ fieldError(blockErrors, 'start_time') }}</p>
                        </div>
                        <div>
                            <label class="form-label">Hora de fin *</label>
                            <input v-model="blockForm.end_time" type="time" class="form-input" />
                            <p v-if="fieldError(blockErrors, 'end_time')" class="text-danger text-xs mt-1">{{ fieldError(blockErrors, 'end_time') }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Área curricular</label>
                        <select v-model="blockForm.area_id" class="form-select">
                            <option :value="null">Sin área</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                        <p v-if="areas.length" class="text-xs text-white-dark mt-1">
                            {{ areas.length }} área(s) de {{ section?.level_name ?? 'este nivel' }}
                            ({{ section?.grade_name }} · {{ section?.name }}).
                        </p>
                        <p v-else class="text-warning text-xs mt-1">
                            No hay áreas activas para {{ section?.level_name ?? 'este nivel' }}.
                            <a :href="route('aca_school_areas_list')" class="underline">Cárguelas en Áreas Curriculares</a>.
                        </p>
                        <p v-if="blockErrors.area_id" class="text-danger text-xs mt-1">{{ fieldError(blockErrors, 'area_id') }}</p>
                    </div>
                    <div>
                        <label class="form-label">Docente que dicta</label>
                        <select v-model="blockForm.teacher_person_id" class="form-select">
                            <option :value="null">Sin docente</option>
                            <option v-for="teacher in modalTeachers" :key="teacher.person_id" :value="teacher.person_id">
                                {{ teacher.full_name }}{{ teacherInSection(teacher) ? ' · ya vinculado a esta sección' : '' }}
                            </option>
                        </select>
                        <p class="text-xs text-white-dark mt-1">
                            <template v-if="teachersFiltered">
                                {{ modalTeachers.length }} docente(s) de {{ section?.level_name ?? 'este nivel' }}.
                            </template>
                            <template v-else>
                                Mostrando los {{ modalTeachers.length }} docentes del colegio.
                            </template>
                        </p>
                        <p v-if="! teachersFiltered && sectionTeachers.length === 0" class="text-warning text-xs mt-1">
                            Ningún docente está vinculado todavía a {{ section?.level_name ?? 'este nivel' }}
                            (tutor, auxiliar o bloque ya asignado).
                        </p>
                        <div class="flex items-center mt-2">
                            <input id="show_all_teachers" v-model="showAllTeachers" type="checkbox" class="form-checkbox" />
                            <label for="show_all_teachers" class="ml-2 text-sm">
                                Mostrar todos los docentes del colegio ({{ teachers.length }})
                            </label>
                        </div>
                        <p v-if="blockErrors.teacher_person_id" class="text-danger text-xs mt-1">{{ fieldError(blockErrors, 'teacher_person_id') }}</p>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="saveBlock" :disabled="savingBlock">
                    <iconLoader v-if="savingBlock" class="w-4 h-4 mr-1" />
                    Guardar
                </PrimaryButton>
            </template>
        </ModalMedium>

        <!-- Modal copiar día -->
        <ModalMedium :show="copyModal" :onClose="closeCopyModal" :icon="'/img/aula.png'">
            <template #title>Copiar los bloques de un día</template>
            <template #message>
                Se copian los bloques del día de origen a los días que elija. Los bloques que ya existan a la misma hora se omiten.
            </template>
            <template #content>
                <div class="space-y-3">
                    <div>
                        <label class="form-label">Día de origen *</label>
                        <select v-model="copyForm.from_weekday" class="form-select">
                            <option v-for="day in daysToShow" :key="day.value" :value="day.value">{{ day.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Copiar a</label>
                        <div class="flex flex-wrap gap-3">
                            <label v-for="day in daysToShow" :key="day.value" class="inline-flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    class="form-checkbox"
                                    :value="day.value"
                                    v-model="copyForm.to_weekdays"
                                    :disabled="day.value === copyForm.from_weekday"
                                />
                                {{ day.short }}
                            </label>
                        </div>
                        <p v-if="copyErrors.to_weekdays" class="text-danger text-xs mt-1">{{ fieldError(copyErrors, 'to_weekdays') }}</p>
                        <p v-if="copyErrors.from_weekday" class="text-danger text-xs mt-1">{{ fieldError(copyErrors, 'from_weekday') }}</p>
                    </div>
                    <div class="flex items-center">
                        <input id="copy_replace" v-model="copyForm.replace" type="checkbox" class="form-checkbox" />
                        <label for="copy_replace" class="ml-2 text-sm">Reemplazar los bloques que ya existen en los días de destino</label>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="saveCopy" :disabled="savingCopy">
                    <iconLoader v-if="savingCopy" class="w-4 h-4 mr-1" />
                    Copiar
                </PrimaryButton>
            </template>
        </ModalMedium>

        <!-- Modal copiar de otra sección -->
        <ModalMedium :show="sectionCopyModal" :onClose="closeSectionCopyModal" :icon="'/img/aula.png'">
            <template #title>Copiar el horario de otra sección</template>
            <template #message>
                Útil cuando varias secciones del mismo grado comparten el horario: se copian todos los bloques del año elegido.
            </template>
            <template #content>
                <div class="space-y-3">
                    <div>
                        <label class="form-label">Sección de origen *</label>
                        <select v-model="sectionCopyForm.source_section_id" class="form-select">
                            <option :value="null">Seleccione una sección</option>
                            <option v-for="item in otherSections" :key="item.id" :value="item.id">
                                {{ item.level_name }} · {{ item.label }} — {{ item.shift_label }}
                            </option>
                        </select>
                        <p v-if="sectionCopyErrors.source_section_id" class="text-danger text-xs mt-1">
                            {{ fieldError(sectionCopyErrors, 'source_section_id') }}
                        </p>
                    </div>
                    <p class="text-sm text-white-dark">
                        Se copiará en <span class="font-semibold">{{ section?.label }}</span> ({{ section?.level_name }} · {{ section?.shift_label }}).
                    </p>
                    <div class="flex items-center">
                        <input id="section_copy_replace" v-model="sectionCopyForm.replace" type="checkbox" class="form-checkbox" />
                        <label for="section_copy_replace" class="ml-2 text-sm">
                            Reemplazar el horario actual de esta sección
                        </label>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="saveSectionCopy" :disabled="savingSectionCopy">
                    <iconLoader v-if="savingSectionCopy" class="w-4 h-4 mr-1" />
                    Copiar
                </PrimaryButton>
            </template>
        </ModalMedium>

        <!-- Modal jornada -->
        <ModalMedium :show="journeyModal" :onClose="closeJourneyModal" :icon="'/img/aula.png'">
            <template #title>Jornada del colegio</template>
            <template #message>
                Hora oficial de entrada y salida por nivel y turno. Los bloques del horario deben caer dentro de la jornada.
            </template>
            <template #content>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Nivel</label>
                            <select v-model="journeyForm.level_id" class="form-select">
                                <option :value="null">Todo el colegio</option>
                                <option v-for="item in levelOptions" :key="item.level_id" :value="item.level_id">
                                    {{ item.level_name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Turno *</label>
                            <select v-model="journeyForm.shift" class="form-select">
                                <option v-for="(label, code) in shifts" :key="code" :value="code">{{ label }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="form-label">Entrada *</label>
                            <input v-model="journeyForm.entry_time" type="time" class="form-input" />
                        </div>
                        <div>
                            <label class="form-label">Salida *</label>
                            <input v-model="journeyForm.exit_time" type="time" class="form-input" />
                            <p v-if="journeyErrors.exit_time" class="text-danger text-xs mt-1">{{ fieldError(journeyErrors, 'exit_time') }}</p>
                        </div>
                        <div>
                            <label class="form-label">Tolerancia (min)</label>
                            <input v-model="journeyForm.tolerance_minutes" type="number" min="0" max="60" class="form-input" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Inicio del recreo (opcional)</label>
                            <input v-model="journeyForm.recess_start" type="time" class="form-input" />
                        </div>
                        <div>
                            <label class="form-label">Fin del recreo (opcional)</label>
                            <input v-model="journeyForm.recess_end" type="time" class="form-input" />
                        </div>
                    </div>

                    <div v-if="journeys.length" class="pt-2 border-t border-white-light dark:border-dark">
                        <p class="form-label mt-3">Jornadas configuradas</p>
                        <div class="space-y-2">
                            <div
                                v-for="item in journeys"
                                :key="item.id"
                                class="flex items-center justify-between gap-3 text-sm border rounded-md p-2 border-white-light dark:border-dark"
                            >
                                <div>
                                    <span class="font-semibold">{{ item.level_name }}</span> · {{ item.shift_label }}
                                    <span class="text-white-dark">— {{ item.entry_time }} a {{ item.exit_time }} ({{ item.tolerance_minutes }} min)</span>
                                </div>
                                <div class="flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-info" @click="openJourney(item)">
                                        <font-awesome-icon :icon="faPencil" />
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="destroyJourney(item)">
                                        <font-awesome-icon :icon="faTrash" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="saveJourney" :disabled="savingJourney">
                    <iconLoader v-if="savingJourney" class="w-4 h-4 mr-1" />
                    Guardar jornada
                </PrimaryButton>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
