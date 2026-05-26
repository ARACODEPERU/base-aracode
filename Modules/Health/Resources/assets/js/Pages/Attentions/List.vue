<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Pagination from '@/Components/Pagination.vue';
import ModalLarge from '@/Components/ModalLarge.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import IconSearch from '@/Components/vristo/icon/icon-search.vue';
import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
import IconPencil from '@/Components/vristo/icon/icon-pencil.vue';
import IconEye from '@/Components/vristo/icon/icon-eye.vue';
import IconTrash from '@/Components/vristo/icon/icon-trash.vue';
import IconTooth from '@/Components/vristo/icon/icon-tooth.vue';
import IconHeart from '@/Components/vristo/icon/icon-heart.vue';
import IconGlasses from '@/Components/vristo/icon/icon-glasses.vue';
import IconNotes from '@/Components/vristo/icon/icon-notes.vue';
import IconFilePdf from '@/Components/vristo/icon/icon-file-pdf.vue';
import { serviceTypeOption } from '@/Components/Health/healthOptions.js';
import Swal from 'sweetalert2';
import PendingSignatureReminder from '../../Components/PendingSignatureReminder.vue';
import EstablishmentBadge from '../../Components/EstablishmentBadge.vue';

const props = defineProps({
    attentions: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    pendingSignatures: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    search: props.filters.search,
});

const displayPrescriptionModal = ref(false);
const selectedAttention = ref(null);

const selectedPharmacologicalTreatments = computed(() => {
    return (selectedAttention.value?.treatments || [])
        .filter((treatment) => treatment.treatment_type === 'farmacologica')
        .map((treatment) => ({
            ...treatment,
            pharmacological_data: treatment.pharmacological_data || {},
        }));
});

const hasPrescriptionIndications = computed(() => {
    return selectedPharmacologicalTreatments.value.some((treatment) => treatment.pharmacological_data?.administration_indications);
});

const search = () => {
    form.get(route('heal_attentions_list'), {
        preserveState: true,
        replace: true,
    });
};

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const patientAge = (attention) => {
    const birthdate = attention?.patient?.person?.birthdate;

    if (!birthdate) {
        return '-';
    }

    const birth = new Date(birthdate);
    const today = new Date();

    if (Number.isNaN(birth.getTime()) || birth > today) {
        return '-';
    }

    let years = today.getFullYear() - birth.getFullYear();
    let months = today.getMonth() - birth.getMonth();

    if (today.getDate() < birth.getDate()) {
        months -= 1;
    }

    if (months < 0) {
        years -= 1;
        months += 12;
    }

    return `${years}a ${months}m`;
};

const openPrescriptionModal = (attention) => {
    selectedAttention.value = attention;
    displayPrescriptionModal.value = true;
};

const closePrescriptionModal = () => {
    displayPrescriptionModal.value = false;
};

const editAttention = (attention) => {
    if (attention.signed_at) {
        const doctorName = attention.doctor?.person?.full_name || 'el doctor';

        Swal.fire({
            title: '¡Atención firmada!',
            html: `
                <div class="flex flex-col items-center gap-4">
                    <div class="flex h-28 w-28 animate-pulse items-center justify-center rounded-full bg-warning/20 text-warning">
                        <svg width="68" height="68" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="animate-bounce">
                            <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="1.8" />
                            <path d="M2 12C2 12 5 4 12 4C19 4 22 12 22 12C22 12 19 20 12 20C5 20 2 12 2 12Z" stroke="currentColor" stroke-width="1.8" />
                            <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12" stroke="currentColor" stroke-width="1.8" />
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="m-0 text-base font-semibold">Esta atención ya fue <span class="text-warning">firmada</span></p>
                        <p class="m-0 mt-1 text-sm text-white-dark">
                            Fue firmada por <strong>${doctorName}</strong> y <span class="text-danger font-semibold">no puede modificarse</span>.
                        </p>
                    </div>
                    <div class="mt-2 rounded-lg border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-warning-dark dark:text-warning">
                        <svg class="inline-block h-4 w-4 ltr:mr-1 rtl:ml-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M12 8V12" stroke-linecap="round" />
                            <circle cx="12" cy="16" r="0.5" fill="currentColor" />
                        </svg>
                        Los documentos firmados quedan bloqueados para mantener su integridad médico legal.
                    </div>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'Entendido',
            customClass: {
                popup: 'sweet-alerts animate__animated animate__headShake',
                confirmButton: 'btn btn-warning',
            },
            buttonsStyling: false,
            padding: '2em',
        });
        return;
    }

    router.visit(route('heal_attentions_edit', attention.id));
};

const deleteAttention = (attention) => {
    if (attention.signed_at) {
        Swal.fire({
            title: '¡Atención firmada!',
            html: `
                <div class="flex flex-col items-center gap-4">
                    <div class="flex h-24 w-24 animate-pulse items-center justify-center rounded-full bg-danger/20 text-danger">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="animate-bounce">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8" />
                            <path d="M12 8V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <circle cx="12" cy="16" r="0.5" fill="currentColor" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </div>
                    <p class="m-0 text-base font-semibold">Esta atención ya fue <span class="text-danger">firmada</span> por el doctor</p>
                    <p class="m-0 text-sm text-white-dark">Las atenciones firmadas no pueden ser eliminadas.</p>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'Entendido',
            customClass: {
                popup: 'sweet-alerts animate__animated animate__headShake',
                confirmButton: 'btn btn-danger',
            },
            buttonsStyling: false,
            padding: '2em',
        });
        return;
    }

    Swal.fire({
        title: 'Eliminar atención',
        html: `
            <div class="flex flex-col items-center gap-3">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-danger/10 text-danger">
                    <svg width="52" height="52" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20.5 6H3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path d="M18.833 8.5L18.373 15.399C18.197 18.054 18.108 19.382 17.243 20.191C16.378 21 15.048 21 12.387 21H11.613C8.953 21 7.622 21 6.757 20.191C5.892 19.382 5.804 18.054 5.627 15.399L5.167 8.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path opacity="0.5" d="M6.5 6C7.432 5.978 8.159 5.455 8.439 4.68L8.571 4.286C8.654 4.037 8.696 3.913 8.751 3.807C8.97 3.386 9.376 3.094 9.845 3.019C9.962 3 10.093 3 10.355 3H13.645C13.907 3 14.038 3 14.155 3.019C14.624 3.094 15.03 3.386 15.249 3.807C15.304 3.913 15.346 4.037 15.429 4.286L15.561 4.68C15.841 5.455 16.568 5.978 17.5 6" stroke="currentColor" stroke-width="1.8" />
                    </svg>
                </div>
                <p class="m-0 text-sm text-white-dark">No podrás revertir esta acción.</p>
            </div>
        `,
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
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        axios.delete(route('heal_attentions_destroy', attention.id)).then(() => {
            router.reload({ only: ['attentions'] });
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'La atención fue eliminada correctamente.',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        });
    });
};
</script>

<template>
    <AppLayout title="Atenciones">
        <PendingSignatureReminder :items="pendingSignatures" />

        <div class="flex items-center justify-between">
            <Navigation :routeModule="route('health_dashboard')" :titleModule="'Salud'">
                <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                    <span>Atenciones</span>
                </li>
            </Navigation>
            <EstablishmentBadge />
        </div>

        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Atenciones</h2>
                <div class="flex sm:flex-row flex-col sm:items-center gap-3 w-full sm:w-auto">
                    <Link :href="route('heal_attentions_create')" class="btn btn-primary">
                        <IconPlus class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                        Nuevo
                    </Link>

                    <div class="relative">
                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="Buscar paciente"
                            class="form-input py-2 ltr:pr-11 rtl:pl-11 peer"
                            @keyup.enter="search"
                        />
                        <button type="button" class="absolute ltr:right-[11px] rtl:left-[11px] top-1/2 -translate-y-1/2 peer-focus:text-primary" @click="search">
                            <IconSearch class="mx-auto" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="panel p-0 border-0 overflow-hidden mt-5">
                <Pagination :data="attentions">
                    <div class="table-responsive">
                        <table class="table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="!text-center">Acciones</th>
                                    <th>Momento</th>
                                    <th>Tipo</th>
                                    <th>Firma</th>
                                    <th>Historia</th>
                                    <th>Paciente</th>
                                    <th>Doctor</th>
                                    <th>Relato</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(attention, index) in attentions.data"
                                    :key="attention.id"
                                    :class="[
                                        'transition-all duration-150 hover:-translate-y-0.5 hover:scale-[1.01] hover:shadow-md att-row',
                                        index % 2 === 0 ? 'att-sky' : 'att-amber'
                                    ]"
                                >
                                    <td>
                                        <div class="flex gap-1 items-center justify-center">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary"
                                                :class="{ 'opacity-50 cursor-not-allowed': attention.signed_at }"
                                                v-tippy="{
                                                    content: attention.signed_at ? 'Firmada - No se puede editar' : 'Editar',
                                                    placement: 'bottom'
                                                }"
                                                @click="editAttention(attention)"
                                            >
                                                <IconPencil class="w-4 h-4 text-white" />
                                            </button>
                                            <Link
                                                :href="route('heal_attentions_edit', attention.id)"
                                                class="btn btn-sm btn-info"
                                                v-tippy="{ content: 'Ver Atención', placement: 'bottom' }"
                                            >
                                                <IconEye class="w-4 h-4 text-white" />
                                            </Link>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-success inline-flex items-center gap-1"
                                                v-tippy="{ content: 'Ver receta', placement: 'bottom' }"
                                                @click="openPrescriptionModal(attention)"
                                            >
                                                <IconNotes class="w-4 h-4 text-white" />
                                                Receta
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger"
                                                :class="{ 'opacity-50 cursor-not-allowed': attention.signed_at }"
                                                v-tippy="{
                                                    content: attention.signed_at ? 'Firmada - No se puede eliminar' : 'Eliminar',
                                                    placement: 'bottom'
                                                }"
                                                @click="deleteAttention(attention)"
                                            >
                                                <IconTrash class="w-4 h-4 text-white" />
                                            </button>
                                        </div>
                                    </td>
                                    <td>{{ formatDate(attention.attention_at) }}</td>
                                    <td>
                                        <span v-if="serviceTypeOption(attention.service_type).dental" class="badge bg-info inline-flex items-center gap-1">
                                            <IconTooth class="w-4 h-4" />
                                            {{ serviceTypeOption(attention.service_type).label }}
                                        </span>
                                        <span v-else-if="attention.service_type === 'oftalmologia'" class="badge bg-primary inline-flex items-center gap-1">
                                            <IconGlasses class="w-4 h-4" />
                                            {{ serviceTypeOption(attention.service_type).label }}
                                        </span>
                                        <span v-else class="badge bg-primary inline-flex items-center gap-1">
                                            <IconHeart class="w-4 h-4" />
                                            {{ serviceTypeOption(attention.service_type).label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span v-if="attention.signed_at" class="badge bg-success">Firmada</span>
                                        <span v-else class="badge bg-warning">Pendiente</span>
                                    </td>
                                    <td>{{ attention.history?.history_code }}</td>
                                    <td>{{ attention.patient?.person?.full_name }}</td>
                                    <td>{{ attention.doctor?.person?.full_name }}</td>
                                    <td class="max-w-[280px] truncate">{{ attention.patient_story }}</td>
                                </tr>
                                <tr v-if="!attentions.data?.length">
                                    <td colspan="8" class="text-center py-8 text-white-dark">
                                        No existen atenciones registradas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Pagination>
            </div>
        </div>

        <ModalLarge :show="displayPrescriptionModal" :onClose="closePrescriptionModal">
            <template #title>
                Receta medica
            </template>
            <template #message>
                {{ selectedAttention?.patient?.person?.full_name || 'Paciente sin nombre' }}
            </template>
            <template #content>
                <div v-if="selectedAttention" class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded border border-gray-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-800">
                            <div class="text-xs font-semibold uppercase text-white-dark">Paciente</div>
                            <div class="mt-1 font-semibold">{{ selectedAttention.patient?.person?.full_name || '-' }}</div>
                            <div class="mt-1 text-sm text-white-dark">Edad: {{ patientAge(selectedAttention) }}</div>
                        </div>
                        <div class="rounded border border-gray-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-800">
                            <div class="text-xs font-semibold uppercase text-white-dark">Doctor</div>
                            <div class="mt-1 font-semibold">{{ selectedAttention.doctor?.person?.full_name || '-' }}</div>
                            <div class="mt-1 text-sm text-white-dark">{{ selectedAttention.doctor?.specialty || '-' }}</div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <div class="mb-2 text-sm font-semibold text-primary">Terapia farmacologica</div>
                            <div v-if="selectedPharmacologicalTreatments.length" class="space-y-2">
                                <div
                                    v-for="treatment in selectedPharmacologicalTreatments"
                                    :key="treatment.id"
                                    class="rounded border border-gray-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-800"
                                >
                                    <div class="font-semibold">
                                        <template v-if="treatment.pharmacological_data.active_ingredient && treatment.pharmacological_data.brand">
                                            {{ treatment.pharmacological_data.active_ingredient }} ({{ treatment.pharmacological_data.brand }})
                                        </template>
                                        <template v-else>
                                            {{ treatment.pharmacological_data.active_ingredient || treatment.pharmacological_data.brand || treatment.name || treatment.description || 'Medicamento' }}
                                        </template>
                                    </div>
                                    <div class="mt-1 text-sm text-white-dark">
                                        {{ treatment.pharmacological_data.dosage || 'Dosis no registrada' }}
                                    </div>
                                    <div v-if="treatment.pharmacological_data.frequency" class="mt-1 text-sm text-white-dark">
                                        {{ treatment.pharmacological_data.frequency }}
                                    </div>
                                </div>
                            </div>
                            <div v-else class="rounded border border-dashed border-gray-300 p-4 text-sm text-white-dark dark:border-gray-600">
                                No se indicaron medicamentos.
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 text-sm font-semibold text-primary">Indicaciones</div>
                            <div v-if="hasPrescriptionIndications || selectedAttention.non_pharmacological_indications" class="space-y-2">
                                <template v-for="treatment in selectedPharmacologicalTreatments" :key="`indication-${treatment.id}`">
                                    <div
                                        v-if="treatment.pharmacological_data.administration_indications"
                                        class="rounded border border-gray-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-800"
                                    >
                                        <div class="text-sm font-semibold">
                                            {{ treatment.pharmacological_data.active_ingredient || treatment.pharmacological_data.brand || treatment.name || 'Medicamento' }}
                                        </div>
                                        <div class="mt-1 whitespace-pre-line text-sm text-white-dark">
                                            {{ treatment.pharmacological_data.administration_indications }}
                                        </div>
                                    </div>
                                </template>
                                <div
                                    v-if="selectedAttention.non_pharmacological_indications"
                                    class="rounded border border-gray-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-800"
                                >
                                    <div class="text-sm font-semibold">Indicaciones no farmacologicas</div>
                                    <div class="mt-1 whitespace-pre-line text-sm text-white-dark">
                                        {{ selectedAttention.non_pharmacological_indications }}
                                    </div>
                                </div>
                            </div>
                            <div v-else class="rounded border border-dashed border-gray-300 p-4 text-sm text-white-dark dark:border-gray-600">
                                Sin indicaciones registradas.
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <a
                    v-if="selectedAttention"
                    :href="route('heal_attentions_prescription', { id: selectedAttention.id, download: 1 })"
                    class="btn btn-primary inline-flex items-center gap-2"
                >
                    <IconFilePdf class="h-5 w-5" />
                    Ver PDF
                </a>
            </template>
        </ModalLarge>
    </AppLayout>
</template>

<style>
/* ===== Attentions List - Row colors & hover ===== */
/* Light mode */
table tbody tr.att-row.att-sky {
    background-color: #e0f2fe !important; /* sky-100 */
}
table tbody tr.att-row.att-amber {
    background-color: #fef3c7 !important; /* amber-100 */
}
table tbody tr.att-row:hover {
    background-color: #a7f3d0 !important; /* emerald-200 */
}
table tbody tr.att-row td {
    background-color: transparent !important;
}

/* Dark mode */
.dark table tbody tr.att-row.att-sky {
    background-color: #0c4a6e !important; /* sky-900 */
    color: #f1f5f9 !important;
}
.dark table tbody tr.att-row.att-amber {
    background-color: #78350f !important; /* amber-900 */
    color: #f1f5f9 !important;
}
.dark table tbody tr.att-row:hover {
    background-color: #065f46 !important; /* emerald-800 */
    color: #f1f5f9 !important;
}
.dark table tbody tr.att-row td {
    background-color: transparent !important;
}
</style>
