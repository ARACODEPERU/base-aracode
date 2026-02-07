<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import RegistrationForm from './Partials/RegistrationForm.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { faTrashAlt } from "@fortawesome/free-solid-svg-icons";
import SubscriptionForm from "./Partials/SubscriptionForm.vue";
import Keypad from '@/Components/Keypad.vue';

const props = defineProps({
    student:{
        type: Object,
        default : () => ({})
    },
    courses:{
        type: Object,
        default : () => ({})
    },
    registrations:{
        type: Object,
        default : () => ({})
    },
    subscriptionStudent:{
        type: Object,
        default : () => ({})
    },
    subscriptions:{
        type: Object,
        default : () => ({})
    },
    P000021: {
        type: Boolean,
        default: true
    }
});

</script>

<template>
    <AppLayout title="Crear Certificado">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {
                    route: route('aca_students_list'),
                    title: 'Estudiantes',
                },
                {route: route('aca_students_edit', student.id), title: student.person.full_name},
                {title: 'Matriculas'}
            ]"
        />
        <div class="pt-5">
            <div class="mb-5">
                <Keypad>
                    <template #botones>

                        <Link :href="route('aca_students_list')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                    </template>
                </Keypad>
            </div>
            <div class="grid grid-cols-6 gap-6">

                <div v-if="P000021" class="col-span-6 sm:col-span-3 space-y-4">
                    <SubscriptionForm
                        :faTrashAlt="faTrashAlt"
                        :subscriptionStudent="subscriptionStudent"
                        :student="student"
                        :subscriptions="subscriptions"
                    />
                </div>
                <div class="col-span-6 sm:col-span-3 space-y-4">
                    <RegistrationForm
                        :faTrashAlt="faTrashAlt"
                        :registrations="registrations"
                        :student="student"
                        :courses="courses"
                    />
                </div>

            </div>
        </div>
    </AppLayout>
</template>
