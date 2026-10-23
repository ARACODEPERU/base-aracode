<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import PichangueroAuthShell from './Partials/PichangueroAuthShell.vue';

/*
 * Recuperación de contraseña del organizador (PW00001 = 2).
 *
 * La piel vive en PichangueroAuthShell.vue, compartida con el login y el
 * registro. La pantalla general (Auth/ForgotPassword.vue) no cambia.
 */

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <PichangueroAuthShell
        page-title="Recuperar acceso | Pichanguero"
        eyebrow="Acceso de organizadores"
        line1="Recupera"
        accent="tu acceso"
        line3="a la cancha"
        lead="Escribe el correo del organizador y te enviaremos un enlace seguro para restablecer tu contraseña y volver a tu torneo."
        kicker="Recuperar acceso"
        card-title="¿Olvidaste tu contraseña?"
        card-subtitle="Indícanos el correo de la cuenta y te enviaremos el enlace para elegir una nueva."
    >
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <div v-if="status" class="pgr-alert">
                {{ status }}
            </div>

            <div>
                <label for="email" class="pgr-label">Correo electrónico</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    placeholder="tu@correo.com"
                    class="form-input"
                    required
                    autofocus
                />
                <InputError :message="form.errors.email" />
            </div>

            <button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Enviando…' : 'Enviar enlace' }}
            </button>
        </form>

        <template #footer>
            ¿Ya la recordaste?
            <Link :href="route('login')">Volver al inicio de sesión</Link>
        </template>
    </PichangueroAuthShell>
</template>
