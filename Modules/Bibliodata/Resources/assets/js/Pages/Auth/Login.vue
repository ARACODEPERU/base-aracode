<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import ReaderAuthLayout from '../../layouts/ReaderAuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import { animateBibliotecaAuthEntrance } from '../../composables/useBibliotecaAuthBackground.js';

defineProps({
    status: { type: String, default: '' },
    canResetPassword: { type: Boolean, default: false },
    branding: {
        type: Object,
        default: () => ({
            appName: 'Biblio Data',
            tagline: 'Accede a tu biblioteca digital',
            coverUrl: null,
        }),
    },
});

const pageRef = ref(null);
animateBibliotecaAuthEntrance(pageRef);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('bib_login_store'), {
        onFinish: () => form.reset('password'),
    });
};

const features = [
    { icon: '📖', title: 'Un libro, tu espacio', text: 'Lee capítulo a capítulo con progreso guardado.' },
    { icon: '✨', title: 'Experiencia inmersiva', text: 'Diseño pensado para lectura digital, sin distracciones.' },
    { icon: '🔒', title: 'Acceso seguro', text: 'Tu cuenta y suscripción protegidas en todo momento.' },
];
</script>

<template>
    <ReaderAuthLayout>
        <Head :title="`Acceso — ${branding.appName}`" />

        <div ref="pageRef" class="bib-auth-shell">
            <section class="bib-auth-hero" data-bib-auth-enter>
                <div class="bib-auth-hero-glow" aria-hidden="true" />
                <div class="relative z-10 max-w-lg">
                    <span class="bib-auth-hero-badge">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-cyan-400 shadow-[0_0_8px_#22d3ee]" />
                        Biblioteca digital
                    </span>
                    <h2 class="bib-auth-hero-title">
                        {{ branding.appName }}
                    </h2>
                    <p class="bib-auth-hero-lead">
                        {{ branding.tagline }}
                    </p>
                    <p class="bib-auth-hero-desc">
                        Disfruta de una lectura fluida, con un entorno visual dinámico y elegante.
                    </p>
                    <ul class="mt-10 space-y-3">
                        <li
                            v-for="(item, idx) in features"
                            :key="idx"
                            class="bib-auth-feature"
                            data-bib-auth-enter
                        >
                            <span class="bib-auth-feature-icon" aria-hidden="true">{{ item.icon }}</span>
                            <div>
                                <p class="bib-auth-feature-title">{{ item.title }}</p>
                                <p class="bib-auth-feature-text">{{ item.text }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="bib-auth-panel">
                <div class="bib-auth-panel-intro lg:hidden" data-bib-auth-enter>
                    <span class="bib-auth-hero-badge">Biblioteca digital</span>
                    <h2 class="bib-auth-panel-intro-title">{{ branding.appName }}</h2>
                    <p class="bib-auth-panel-intro-tagline">{{ branding.tagline }}</p>
                </div>

                <div class="bib-auth-form" data-bib-auth-enter>
                    <header class="bib-auth-form-header">
                        <div v-if="branding.coverUrl" class="bib-auth-cover">
                            <img
                                :src="branding.coverUrl"
                                :alt="branding.appName"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div v-else class="bib-auth-avatar">
                            {{ branding.appName.charAt(0) }}
                        </div>
                        <h1 class="bib-auth-form-title">Iniciar sesión</h1>
                        <p class="bib-auth-form-subtitle">{{ branding.appName }}</p>
                    </header>

                    <div
                        v-if="status"
                        class="bib-auth-status"
                        data-bib-auth-enter
                    >
                        {{ status }}
                    </div>

                    <form class="bib-auth-form-fields" data-bib-auth-enter @submit.prevent="submit">
                        <div>
                            <label for="bib-email" class="bib-auth-label">Correo electrónico</label>
                            <div class="bib-auth-input-wrap">
                                <svg
                                    class="bib-auth-input-icon"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"
                                    />
                                </svg>
                                <input
                                    id="bib-email"
                                    v-model="form.email"
                                    type="email"
                                    class="bib-auth-input"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="tu@correo.com"
                                />
                            </div>
                            <InputError class="mt-1.5" :message="form.errors.email" />
                        </div>

                        <div>
                            <label for="bib-password" class="bib-auth-label">Contraseña</label>
                            <div class="bib-auth-input-wrap">
                                <svg
                                    class="bib-auth-input-icon"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                                    />
                                </svg>
                                <input
                                    id="bib-password"
                                    v-model="form.password"
                                    type="password"
                                    class="bib-auth-input"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                />
                            </div>
                            <InputError class="mt-1.5" :message="form.errors.password" />
                        </div>

                        <div class="bib-auth-form-row">
                            <label class="bib-auth-remember">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="bib-auth-checkbox"
                                />
                                Recordarme
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="bib-auth-link"
                            >
                                ¿Olvidaste tu contraseña?
                            </Link>
                        </div>

                        <button type="submit" class="bib-auth-btn" :disabled="form.processing">
                            {{ form.processing ? 'Ingresando...' : 'Entrar a mi biblioteca' }}
                        </button>
                    </form>
                </div>

                <p class="bib-auth-footer" data-bib-auth-enter>
                    ¿Eres administrador?
                    <Link :href="route('login')" class="bib-auth-footer-link">
                        Acceso al panel general
                    </Link>
                </p>
            </section>
        </div>
    </ReaderAuthLayout>
</template>
