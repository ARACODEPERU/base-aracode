<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link } from '@inertiajs/vue3';
import AttentionForm from './Partials/AttentionForm.vue';
import PendingSignatureReminder from '../../Components/PendingSignatureReminder.vue';
import EstablishmentBadge from '../../Components/EstablishmentBadge.vue';
import IconFile from '@/Components/vristo/icon/icon-file.vue';

defineProps({
    attention: {
        type: Object,
        required: true,
    },
    doctors: {
        type: Array,
        default: () => [],
    },
    allergyTypes: {
        type: Array,
        default: () => [],
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
    pendingSignatures: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <AppLayout title="Editar Atención">
        <PendingSignatureReminder :items="pendingSignatures" />

        <div class="flex items-center justify-between">
            <Navigation :routeModule="route('health_dashboard')" :titleModule="'Salud'">
                <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                    <Link :href="route('heal_attentions_list')" class="text-primary hover:underline">Atenciones</Link>
                </li>
                <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                    <span>Editar</span>
                </li>
            </Navigation>
            <div class="flex items-center gap-2">
                <a
                    :href="route('heal_attentions_prescription', { id: attention.id, download: 1 })"
                    target="_blank"
                    class="btn btn-success btn-sm"
                    v-tippy="{ content: 'Descargar receta medica', placement: 'bottom' }"
                >
                    <IconFile class="h-4 w-4 ltr:mr-1 rtl:ml-1" />
                    Receta
                </a>
                <a
                    :href="route('heal_attentions_pdf', attention.id)"
                    target="_blank"
                    class="btn btn-outline-primary btn-sm"
                    v-tippy="{ content: 'Descargar reporte PDF', placement: 'bottom' }"
                >
                    <IconFile class="h-4 w-4 ltr:mr-1 rtl:ml-1" />
                    PDF
                </a>
                <EstablishmentBadge />
            </div>
        </div>
        <div class="pt-5">
            <AttentionForm
                :attention="attention"
                :doctors="doctors"
                :allergy-types="allergyTypes"
                :patient-summary="patientSummary"
                :current-doctor="currentDoctor"
                :can-choose-doctor="canChooseDoctor"
            />
        </div>
    </AppLayout>
</template>
