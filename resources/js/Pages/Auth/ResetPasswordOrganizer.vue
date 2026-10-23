<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import PichangueroAuthShell from './Partials/PichangueroAuthShell.vue';

/*
 * Nueva contraseña del organizador (PW00001 = 2).
 *
 * La piel vive en PichangueroAuthShell.vue, compartida con el login y el
 * registro. La pantalla general (Auth/ResetPassword.vue) no cambia.
 */

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <PichangueroAuthShell
        page-title="Nueva contraseña | Pichanguero"
        eyebrow="Acceso de organizadores"
        line1="Vuelve a"
        accent="tu cancha"
        lead="Define una nueva contraseña para la cuenta del organizador y sigue gestionando tu torneo."
        kicker="Nueva contraseña"
        card-title="Restablece tu acceso"
        card-subtitle="Escribe una nueva contraseña para la cuenta del organizador."
    >
        <form class="space-y-5" novalidate @submit.prevent="submit">
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

            <div>
                <label for="password" class="pgr-label">Nueva contraseña</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="form-input"
                    required
                />
                <InputError :message="form.errors.password" />
            </div>

            <div>
                <label for="password_confirmation" class="pgr-label">Confirmar contraseña</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="form-input"
                    required
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Guardando…' : 'Restablecer contraseña' }}
            </button>
        </form>

        <template #footer>
            ¿Ya la recordaste?
            <Link :href="route('login')">Volver al inicio de sesión</Link>
        </template>
    </PichangueroAuthShell>
</template>
