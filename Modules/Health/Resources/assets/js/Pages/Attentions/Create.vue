<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link } from '@inertiajs/vue3';
import AttentionForm from './Partials/AttentionForm.vue';
import PendingSignatureReminder from '../../Components/PendingSignatureReminder.vue';
import EstablishmentBadge from '../../Components/EstablishmentBadge.vue';

defineProps({
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
    attentionDefaults: {
        type: Object,
        default: null,
    },
    pendingSignatures: {
        type: Array,
        default: () => [],
    },
    identityDocumentTypes: {
        type: [Array, Object],
        default: () => [],
    },
    ubigeo: {
        type: [Array, Object],
        default: () => [],
    },
});
</script>

<template>
    <AppLayout title="Crear Atención">
        <PendingSignatureReminder :items="pendingSignatures" />

        <div class="flex items-center justify-between">
            <Navigation :routeModule="route('health_dashboard')" :titleModule="'Salud'">
                <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                    <Link :href="route('heal_attentions_list')" class="text-primary hover:underline">Atenciones</Link>
                </li>
                <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                    <span>Nuevo</span>
                </li>
            </Navigation>
            <EstablishmentBadge />
        </div>
        <div class="pt-5">
            <AttentionForm
                :doctors="doctors"
                :allergy-types="allergyTypes"
                :patient-summary="patientSummary"
                :current-doctor="currentDoctor"
                :can-choose-doctor="canChooseDoctor"
                :attention-defaults="attentionDefaults"
                :identity-document-types="identityDocumentTypes"
                :ubigeo="ubigeo"
            />
        </div>
    </AppLayout>
</template>
