<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import StudentCertificateForm from './Partials/StudentCertificateForm.vue';
import { Link } from '@inertiajs/vue3';
import { faTrashAlt } from "@fortawesome/free-solid-svg-icons";
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    student:{
        type: Object,
        default : () => ({})
    },
    courses:{
        type: Object,
        default : () => ({})
    },
    certificates:{
        type: Object,
        default : () => ({})
    },
    P000016: {
        type: String,
        default: null
    }
});

</script>

<template>
    <AppLayout title="Crear Certificado">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_students_list'), title: 'Estudiantes'},
                {route: route('aca_students_edit', student.id), title: student.person.full_name},
                {title: 'Certificados'}
            ]"
        />
        <div class="pt-5">
            <StudentCertificateForm
                :faTrashAlt="faTrashAlt"
                :certificates="certificates"
                :student="student"
                :courses="courses"
                :P000016="P000016"
            />
        </div>
    </AppLayout>
</template>
