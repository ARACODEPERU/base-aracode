<script setup>
import Welcome from '@/Components/Welcome.vue';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Shortcuts from '@/Components/Shortcuts.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import StudentsEnrolledMonth from 'Modules/Academic/Resources/assets/js/Components/StudentsEnrolledMonth.vue';
import StudentDashboard from 'Modules/Academic/Resources/assets/js/Components/StudentDashboard.vue';
import StorageIndicator from 'Modules/Security/Resources/assets/js/Pages/Dashboard/StorageIndicator.vue';
import MinimumStockNotice from 'Modules/Sales/Resources/assets/js/Components/MinimumStockNotice.vue';
import StatusProducts from 'Modules/Sales/Resources/assets/js/Components/StatusProducts.vue';
import TotalBalance from 'Modules/Sales/Resources/assets/js/Components/TotalBalance.vue';
import SalesSummary from 'Modules/Sales/Resources/assets/js/Components/SalesSummary.vue';
import SubscriptionPrices from 'Modules/Academic/Resources/assets/js/Components/SubscriptionPrices.vue';
import CoursesTotalStudents from 'Modules/Academic/Resources/assets/js/Components/CoursesTotalStudents.vue';
import BirthDays from './Person/Partials/BirthDays.vue';


const userData = usePage().props.auth.user;

const props = defineProps({
    authPerson: {
        type: Object,
        default: () => ({})
    },
    P000009:{
        type: String,
        default: 1
    }
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template v-if="userData.roles.length > 0">
            <template v-for="role in userData.roles">
                <!--modulo academico -->
                <!-- <StudentDashboard v-if="role.name == 'Alumno'"
                    :userData="userData"
                    :authPerson="authPerson"
                /> -->
                <!-- fin modulo academico -->

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!--modulo seguridad -->
                    <div v-if="role.name == 'admin' || role.name == 'webAdmin' || role.name == 'Administrador'" class="space-y-6">
                        <BirthDays />
                        <StorageIndicator />
                    </div>
                    <!-- ventas stock Minimo -->
                    <!-- modulo ventas -->
                    <template v-if="P000009 == 2">
                        <MinimumStockNotice v-if="role.name == 'admin' || role.name == 'webAdmin' || role.name == 'Administrador'" />
                        <StatusProducts v-if="role.name == 'admin' || role.name == 'webAdmin' || role.name == 'Administrador'" />
                        <div class="space-y-6">
                            <TotalBalance v-if="role.name == 'admin' || role.name == 'webAdmin' || role.name == 'Administrador' || role.name == 'Contabilidad'" />
                            <SalesSummary v-if="role.name == 'admin' || role.name == 'webAdmin' || role.name == 'Administrador' || role.name == 'Contabilidad'" />
                        </div>
                    </template>
                   <!--modulo academico -->
                    <div v-if="role.name == 'admin' || role.name == 'webAdmin' || role.name == 'Administrador'" class="col-span-3 sm:col-span-2 space-y-6">
                        <template v-if="P000009 == 1">
                            <StudentsEnrolledMonth />
                            <CoursesTotalStudents />
                        </template>
                    </div>
                    <!-- fin modulo academico -->
                </div>
            </template>
        </template>
        <template v-else>
            <SubscriptionPrices />
        </template>
    </AppLayout>
</template>
