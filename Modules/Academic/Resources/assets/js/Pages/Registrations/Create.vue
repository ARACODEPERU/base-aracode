<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import RegistrationForm from './Partials/RegistrationForm.vue';
import { Link } from '@inertiajs/vue3';
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
    }
});

</script>

<template>
    <AppLayout title="Crear Certificado">
        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="javascript:;" class="text-primary hover:underline">Académico</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('aca_students_list')" class="text-primary hover:underline">Estudiantes</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('aca_students_edit', student.id)" class="text-primary hover:underline">{{ student.person.full_name  }}</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Matriculas</span>
            </li>
        </ul>
        <div class="pt-5">
            <div class="mb-5">
                <Keypad>
                    <template #botones>
                        
                        <Link :href="route('aca_students_list')"  class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                    </template>
                </Keypad>
            </div>
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-3 space-y-4">
                    <SubscriptionForm
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
