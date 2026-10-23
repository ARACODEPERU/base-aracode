<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import PichangueroAuthShell from './Partials/PichangueroAuthShell.vue';

/*
 * Login de organizadores (PW00001 = 2).
 *
 * La piel vive en PichangueroAuthShell.vue, compartida con el registro. El
 * login general (Auth/Login.vue) no cambia.
 */

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const page = usePage();

// Alerta enviada por el servidor (por ejemplo: correo ya registrado al crear la cuenta).
const flashError = computed(() => page.props.flash?.error || '');

// Recibe redirect_to desde la URL, igual que el login general.
const redirectTo = computed(() => {
    const queryString = page.url?.split('?')[1] ?? '';

    return new URLSearchParams(queryString).get('redirect_to') ?? '';
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    redirect_to: redirectTo.value,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <PichangueroAuthShell
        page-title="Ingreso de organizadores | Pichanguero"
        eyebrow="Acceso de organizadores"
        line1="Tu torneo,"
        accent="tu cancha"
        lead="Gestiona fixture, marcadores, tabla de posiciones y estadísticas de jugadores desde un solo lugar."
        kicker="Acceso seguro"
        card-title="Iniciar sesión"
        card-subtitle="Ingresa con la cuenta del organizador de tu torneo."
    >
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <div v-if="status" class="pgr-alert">
                {{ status }}
            </div>
            <div v-if="flashError" role="alert" class="pgr-alert pgr-alert-error">
                {{ flashError }}
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
                    @input="(e) => (form.email = e.target.value.trim())"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="pgr-label">Contraseña</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="form-input"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="pgr-login-row">
                <label class="pgr-check">
                    <input v-model="form.remember" type="checkbox" class="form-checkbox" />
                    <span>Recordarme</span>
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="pgr-forgot"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Ingresando…' : 'Entrar a la cancha' }}
            </button>
        </form>

        <template #footer>
            ¿Aún no tienes cuenta?
            <Link :href="route('register')">Crea tu organización</Link>
        </template>
    </PichangueroAuthShell>
</template>
