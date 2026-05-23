<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import OrgForm from './Partials/Form.vue';

defineProps({
    organization: { type: Object, required: true },
    identityDocumentTypes: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout title="Editar organización">
        <Navigation
            :routeModule="route('bib_dashboard')"
            :titleModule="'Biblio Data'"
            :data="[
                { title: 'Organizaciones', route: route('bib_organizations') },
                { title: 'Editar' },
            ]"
        />
        <div class="pt-5">
            <OrgForm
                :organization="organization"
                :identity-document-types="identityDocumentTypes"
            />
        </div>
    </AppLayout>
</template>
