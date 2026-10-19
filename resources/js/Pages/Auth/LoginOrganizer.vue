<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

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

// Imágenes copiadas desde la app móvil (aracode-movil/assets/images) a public/img/torneos.
const imagenes = {
    estadio: '/img/torneos/chaos-soccer-gear-Cjfl8r_eYxY-unsplash.jpg',
    cancha: '/img/torneos/emilio-garcia-AWdCgDDedH0-unsplash.jpg',
    jugador: '/img/torneos/futbol_player_15060628.png',
    balon: '/img/torneos/balon.gif',
};

const escudos = [
    '/img/torneos/escudo1.png',
    '/img/torneos/escudo2.png',
    '/img/torneos/escudo3.png',
    '/img/torneos/escudo5.png',
];
</script>

<template>
    <Head title="Ingreso de organizadores" />

    <div class="relative min-h-screen overflow-hidden bg-emerald-950 text-white">
        <!-- Fondo: estadio con superposición verde -->
        <div class="absolute inset-0">
            <img :src="imagenes.estadio" alt="" class="h-full w-full object-cover opacity-30" />
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-950 via-emerald-900/90 to-lime-900/80"></div>
            <!-- Líneas de cancha -->
            <div class="absolute inset-x-6 top-1/2 h-px bg-white/10"></div>
            <div class="absolute left-1/2 top-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/10"></div>
        </div>

        <div class="relative mx-auto grid min-h-screen max-w-6xl items-center gap-10 px-6 py-12 lg:grid-cols-2">
            <!-- Panel temático -->
            <section class="space-y-6">
                <div class="flex items-center gap-3">
                    <img :src="imagenes.balon" alt="Balón" class="h-12 w-12 rounded-full object-cover" />
                    <span class="text-sm font-bold uppercase tracking-[0.3em] text-lime-300">Aracode Torneos</span>
                </div>

                <h1 class="text-4xl font-black leading-tight sm:text-5xl">
                    Tu torneo,
                    <span class="bg-gradient-to-r from-lime-300 to-emerald-400 bg-clip-text text-transparent">tu cancha</span>
                </h1>

                <p class="max-w-md text-lg text-emerald-100/80">
                    Gestiona fixture, marcadores, tabla de posiciones y estadísticas de jugadores desde un solo lugar.
                </p>

                <img
                    :src="imagenes.jugador"
                    alt="Jugador de fútbol"
                    class="hidden max-h-96 object-contain drop-shadow-2xl lg:block"
                />
            </section>

            <!-- Formulario -->
            <section class="w-full rounded-2xl border border-white/10 bg-black/40 p-8 shadow-2xl backdrop-blur">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-lime-300">Acceso seguro</p>
                        <h2 class="text-2xl font-extrabold">Iniciar sesión</h2>
                    </div>
                    <img :src="imagenes.balon" alt="" class="h-10 w-10 rounded-full" />
                </div>

                <div v-if="status" class="mb-4 rounded-lg border border-lime-400/40 bg-lime-400/10 px-4 py-3 text-sm text-lime-200">
                    {{ status }}
                </div>
                <div v-if="flashError" role="alert" class="mb-4 rounded-lg border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm font-semibold text-red-200">
                    {{ flashError }}
                </div>

                <form class="space-y-5" novalidate @submit.prevent="submit">
                    <div>
                        <label for="email" class="mb-1 block text-sm font-semibold text-emerald-100">Correo electrónico</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="username"
                            placeholder="tu@correo.com"
                            class="w-full rounded-lg border border-white/15 bg-white/5 px-4 py-3 text-white placeholder-white/40 focus:border-lime-400 focus:outline-none focus:ring-2 focus:ring-lime-400/40"
                            @input="(e) => (form.email = e.target.value.trim())"
                        />
                        <InputError :message="form.errors.email" class="mt-1" />
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-semibold text-emerald-100">Contraseña</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full rounded-lg border border-white/15 bg-white/5 px-4 py-3 text-white placeholder-white/40 focus:border-lime-400 focus:outline-none focus:ring-2 focus:ring-lime-400/40"
                        />
                        <InputError :message="form.errors.password" class="mt-1" />
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 text-emerald-100/80">
                            <input v-model="form.remember" type="checkbox" class="rounded border-white/20 bg-white/5 text-lime-500 focus:ring-lime-400" />
                            Recordarme
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="font-semibold text-lime-300 hover:text-lime-200"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-xl bg-gradient-to-r from-lime-400 to-emerald-500 px-4 py-3 font-bold text-emerald-950 shadow-lg shadow-lime-500/20 transition hover:from-lime-300 hover:to-emerald-400 disabled:opacity-60"
                    >
                        {{ form.processing ? 'Ingresando…' : 'Entrar a la cancha' }}
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-emerald-100/70">
                    ¿Aún no tienes cuenta?
                    <Link :href="route('register')" class="font-bold text-lime-300 hover:text-lime-200">Crea tu organización</Link>
                </p>
            </section>
        </div>
    </div>
</template>
