<script setup>
import { computed, onMounted, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';

const page = usePage();
const alerts = computed(() => page.props.organizationBillingAlerts || []);
const shown = ref(false);

onMounted(async () => {
    if (shown.value || !alerts.value.length) return;
    shown.value = true;
    const alert = alerts.value[0];
    const result = await Swal2.fire({
        title: alert.type === 'overdue' ? 'Pago vencido' : alert.type === 'upcoming' ? 'Pago próximo' : 'Aviso de pago',
        text: alert.message,
        icon: alert.type === 'overdue' ? 'warning' : 'info',
        showCancelButton: true,
        confirmButtonText: 'Ver pagos',
        cancelButtonText: 'Cerrar',
        customClass: 'sweet-alerts',
    });
    if (result.isConfirmed) {
        router.post(route('even_organizations.alerts.read', alert.id), {}, {
            preserveScroll: true,
            onFinish: () => router.visit(route('even_organizations.topup.index')),
        });
    } else {
        router.post(route('even_organizations.alerts.read', alert.id), {}, { preserveScroll: true });
    }
});
</script>

<template><span class="hidden" aria-hidden="true" /></template>
