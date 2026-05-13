<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import AuthorForm from './Partials/Form.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    author: { type: Object, required: true },
    identityDocumentTypes: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout title="Editar Autor">
        <Navigation :routeModule="route('bib_dashboard')" :titleModule="'Biblio Data'"
            :data="[
                {title: 'Autores', route: route('bib_authors')},
                {title: 'Editar'}
            ]"
        />

        <div class="pt-5">
            <AuthorForm
                :author="author"
                :identity-document-types="identityDocumentTypes"
                :countries="countries"
            />
        </div>
    </AppLayout>
</template>
