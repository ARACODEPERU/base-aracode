<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import InformationForm from './Partials/InformationForm.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { faCheck, faTrashAlt } from "@fortawesome/free-solid-svg-icons";

const props = defineProps({
    brochure:{
        type: Object,
        default: () => ({}),
    },
    course: {
        type: Object,
        default: () => ({}),
    },
    tiny_api_key: {
        type: String,
        default: () => ({}),
    },
    teachers: {
        type: Object,
        default: () => ({}),
    },
    course_teachers: {
        type: Object,
        default: () => ({}),
    }
});

</script>

<template>
    <AppLayout title="Información">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_courses_list'), title: 'Cursos'},
                {route: route('aca_courses_edit',course.id), title: course.description},
                {title: 'Información'}
            ]"
        />
        <div class="pt-5">
            <InformationForm
                :course_teachers="course_teachers"
                :teachers="teachers"
                :course="course"
                :tiny_api_key="tiny_api_key"
                :brochure="brochure"
                :faCheck="faCheck"
                :faTrashAlt="faTrashAlt"
            />
        </div>
    </AppLayout>
</template>
