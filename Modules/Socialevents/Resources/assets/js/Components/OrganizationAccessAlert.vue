<script setup>
import { onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import { debeAvisarAccesoVencido } from '../utils/organizationAccessAlert.js';

/**
 * Aviso único de acceso vencido para el organizador.
 *
 * Se monta en el layout, así que aparece en cualquier pantalla del sistema y una
 * sola vez por carga: el organizador entiende por qué dejó de poder registrar y
 * tiene el botón que lo lleva a pagar. La lógica de "cuándo" vive en
 * utils/organizationAccessAlert.js.
 */
const page = usePage();

onMounted(async () => {
    const roles = page.props.auth?.roles || [];

    if (! debeAvisarAccesoVencido(page.props.sportsOrganization || {}, roles, page.url)) {
        return;
    }

    const result = await Swal2.fire({
        title: page.props.sportsOrganization?.status === 'suspended' ? 'Cuenta bloqueada' : 'Acceso vencido',
        text: page.props.sportsOrganization?.status === 'suspended'
            ? 'Tu cuenta está bloqueada. No puedes registrar ni modificar información, pero puedes realizar el pago para solicitar la reactivación.'
            : 'El acceso de tu organización venció: el sistema quedó en solo lectura y ya no admite registros ni cambios. Regulariza el pago para reactivarlo.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ir a pagar',
        cancelButtonText: 'Cerrar',
        padding: '2em',
        customClass: 'sweet-alerts',
    });

    if (result.isConfirmed) {
        router.visit(route('even_organizations.topup.index'));
    }
});
</script>

<template>
    <span class="hidden" aria-hidden="true" />
</template>
