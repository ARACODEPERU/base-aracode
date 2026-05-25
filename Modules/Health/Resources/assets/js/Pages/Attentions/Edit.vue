<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link } from '@inertiajs/vue3';
import AttentionForm from './Partials/AttentionForm.vue';
import PendingSignatureReminder from '../../Components/PendingSignatureReminder.vue';

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

        <Navigation :routeModule="route('health_dashboard')" :titleModule="'Salud'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <Link :href="route('heal_attentions_list')" class="text-primary hover:underline">Atenciones</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Editar</span>
            </li>
        </Navigation>
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
