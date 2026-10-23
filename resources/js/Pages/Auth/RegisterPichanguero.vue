<script setup>
import { Link } from '@inertiajs/vue3';
import PichangueroAuthShell from './Partials/PichangueroAuthShell.vue';
import RegistroOrganizadorForm from './Partials/RegistroOrganizadorForm.vue';

/*
 * Variante de Pichanguero (portadas 2 y 5).
 *
 * La piel vive en PichangueroAuthShell.vue, compartida con el login. El
 * formulario y el alta siguen en RegistroOrganizadorForm.vue.
 */

const factos = [
    { value: '1 clic', label: 'para generar el fixture' },
    { value: '0', label: 'hojas de cálculo' },
    { value: '100 %', label: 'desde el navegador' },
];
</script>

<template>
    <PichangueroAuthShell
        page-title="Crea tu cuenta | Pichanguero"
        eyebrow="Torneos de fútbol y más"
        line1="Empieza a organizar"
        accent="tu torneo"
        lead="Registra tu organización y tu usuario responsable. Tendrás prueba gratuita para crear tus torneos, con fixture, tabla de posiciones y estadísticas desde el navegador."
        :facts="factos"
        kicker="Cuenta de organizador"
        card-title="Registro de tu organización"
        card-subtitle="Completa los datos de la organización y del usuario responsable de la cuenta."
    >
        <RegistroOrganizadorForm />

        <template #footer>
            ¿Ya tienes una cuenta?
            <Link :href="route('login')">Inicia sesión</Link>
        </template>
    </PichangueroAuthShell>
</template>
