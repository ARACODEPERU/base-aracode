<script setup>
    import { computed, ref } from 'vue';
    import { useAppStore } from '@/stores/index';
    import AuthLayout from '@/Layouts/Vristo/AuthLayout.vue';
    import IconMail from '@/Components/vristo/icon/icon-mail.vue';
    import IconLockDots from '@/Components/vristo/icon/icon-lock-dots.vue';
    import IconInstagram from '@/Components/vristo/icon/icon-instagram.vue';
    import IconFacebookCircle from '@/Components/vristo/icon/icon-facebook-circle.vue';
    import IconTwitter from '@/Components/vristo/icon/icon-twitter.vue';
    import IconYoutube from '@/Components/vristo/icon/icon-youtube.vue';
    import IconSun from '@/Components/vristo/icon/icon-sun.vue';
    import IconMoon from '@/Components/vristo/icon/icon-moon.vue';
    import { Link, useForm, Head, usePage } from '@inertiajs/vue3';
    import Checkbox from '@/Components/vristo/inputs/Checkbox.vue';
    import InputError from '@/Components/InputError.vue';
    import SpinnerLoading from '@/Components/SpinnerLoading.vue';
    import { useAracodeLoginAnimations } from '@/composables/useAracodeLoginAnimations';
    import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
    import {
        faBriefcase,
        faPuzzlePiece,
        faGraduationCap,
        faCalendarDays,
        faChurch,
        faBook,
        faCoins,
        faShieldHalved,
        faPlug,
        faCube,
        faChartLine,
        faCloud,
        faLock,
        faUsers,
        faBolt,
    } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        activeModules: {
            type: Array,
            default: () => [],
        },
        socialNetworks: {
            type: Array,
            default: () => [],
        },
        status: String,
    });

    const store = useAppStore();
    const page = usePage();
    const company = page.props.company;
    const loginRoot = ref(null);

    useAracodeLoginAnimations(loginRoot);

    const toggleTheme = (checked) => {
        store.toggleTheme(checked ? 'dark' : 'light');
    };

    const baseUrl = assetUrl;

    const benefits = [
        {
            icon: faChartLine,
            title: 'Gestión integral',
            text: 'Centraliza ventas, academico, eventos y más en una sola plataforma.',
        },
        {
            icon: faCloud,
            title: 'Facturación electrónica',
            text: 'Emisión de comprobantes SUNAT con trazabilidad y reportes.',
        },
        {
            icon: faLock,
            title: 'Seguridad por roles',
            text: 'Accesos controlados con permisos granulares por usuario.',
        },
        {
            icon: faUsers,
            title: 'Multi-módulo',
            text: 'Activa solo lo que tu empresa necesita, sin complejidad extra.',
        },
    ];

    const moduleIconMap = {
        faBriefcase,
        faPuzzlePiece,
        faGraduationCap,
        faCalendarDays,
        faChurch,
        faBook,
        faCoins,
        faShieldHalved,
        faPlug,
        faCube,
    };

    const resolveModuleIcon = (module) => {
        if (module?.icon && moduleIconMap[module.icon]) {
            return moduleIconMap[module.icon];
        }

        const desc = (module?.description || '').toLowerCase();

        if (desc.includes('academ')) return faGraduationCap;
        if (desc.includes('evento')) return faCalendarDays;
        if (desc.includes('iglesia') || desc.includes('comunidad')) return faChurch;
        if (desc.includes('biblio')) return faBook;
        if (desc.includes('cobrar') || desc.includes('cobran')) return faCoins;
        if (desc.includes('seguridad') || desc.includes('configur')) return faShieldHalved;
        if (desc.includes('integr')) return faPlug;
        if (desc.includes('comercial')) return faBriefcase;

        return faCube;
    };

    const defaultCompanyAssets = {
        logo: '/img/logo176x32.png',
        logo_dark: '/img/logo176x32Dark.jpeg',
        logo_negative: '/img/logo176x32_negativo.png',
    };

    const resolveCompanyAsset = (path, fallbackPath = null) => {
        const resolvedPath = path || fallbackPath;

        if (!resolvedPath) {
            return null;
        }

        if (resolvedPath.startsWith('http://') || resolvedPath.startsWith('https://')) {
            return resolvedPath;
        }

        if (resolvedPath.startsWith('/img/')) {
            return `${baseUrl}${resolvedPath.replace(/^\//, '')}`;
        }

        if (resolvedPath.startsWith('/')) {
            return `${baseUrl}${resolvedPath.replace(/^\//, '')}`;
        }

        return `${baseUrl}storage/${resolvedPath}`;
    };

    const companyLogoUrl = computed(() => {
        if (store.isDarkMode) {
            return (
                resolveCompanyAsset(company?.logo_degative, defaultCompanyAssets.logo_dark)
                || resolveCompanyAsset(company?.isotipo_dark)
                || `${baseUrl}themes/vristo/images/auth/logo-white.svg`
            );
        }

        return (
            resolveCompanyAsset(company?.logo, defaultCompanyAssets.logo)
            || resolveCompanyAsset(company?.logo_document, defaultCompanyAssets.logo)
            || `${baseUrl}themes/vristo/images/logo.svg`
        );
    });

    const companyLogoWrapClass = computed(() => (
        store.isDarkMode ? 'aracode-login__logo-wrap aracode-login__logo-wrap--dark' : 'aracode-login__logo-wrap aracode-login__logo-wrap--light'
    ));

    const companyName = computed(() => company?.tradename || company?.business_name || company?.name || 'ARACODE');

    const redirectTo = computed(() => {
        const queryString = page.url?.split('?')[1] ?? '';
        const params = new URLSearchParams(queryString);

        return params.get('redirect_to') ?? '';
    });

    const form = useForm({
        email: '',
        password: '',
        remember: false,
        redirect_to: redirectTo.value,
    });

    const submit = () => {
        form.post(route('login'), {
            onFinish: () => {
                form.reset('password');
            },
        });
    };
</script>

<template>
    <AuthLayout>
        <Head title="Acceso" />

        <div
            ref="loginRoot"
            class="aracode-login"
            :class="store.isDarkMode ? 'aracode-login--dark' : 'aracode-login--light'"
        >
            <div class="aracode-login__bg" data-login-bg aria-hidden="true">
                <div class="aracode-login__grid" />
            </div>

            <div class="aracode-login__shell">
                <!-- Panel informativo -->
                <section class="aracode-login__info" data-login-enter>
                    <div class="aracode-login__brand">
                        <div :class="companyLogoWrapClass">
                            <img
                                :src="companyLogoUrl"
                                :alt="companyName"
                                class="aracode-login__logo"
                            />
                        </div>
                        <p class="aracode-login__badge">
                            <FontAwesomeIcon :icon="faBolt" class="mr-1" />
                            Plataforma empresarial ARACODE
                        </p>
                        <h1 class="aracode-login__title">
                            Bienvenido a <span>{{ companyName }}</span>
                        </h1>
                        <p class="aracode-login__subtitle">
                            Accede a tu ecosistema digital: operaciones, reportes y módulos activos en un solo lugar.
                        </p>
                    </div>

                    <ul class="aracode-login__benefits">
                        <li
                            v-for="(benefit, index) in benefits"
                            :key="benefit.title"
                            class="aracode-login__benefit"
                            data-login-enter
                            :style="{ animationDelay: `${index * 80}ms` }"
                        >
                            <span class="aracode-login__benefit-icon">
                                <FontAwesomeIcon :icon="benefit.icon" />
                            </span>
                            <div>
                                <strong>{{ benefit.title }}</strong>
                                <p>{{ benefit.text }}</p>
                            </div>
                        </li>
                    </ul>

                    <div v-if="activeModules.length" class="aracode-login__modules" data-login-enter>
                        <h2 class="aracode-login__modules-title">Módulos activos en tu empresa</h2>
                        <ul class="aracode-login__modules-grid">
                            <li
                                v-for="module in activeModules"
                                :key="module.identifier"
                                class="aracode-login__module-card"
                                data-login-module
                            >
                                <span class="aracode-login__module-icon">
                                    <FontAwesomeIcon :icon="resolveModuleIcon(module)" />
                                </span>
                                <div class="aracode-login__module-text">
                                    <strong>{{ module.description }}</strong>
                                    <small>{{ module.identifier }}</small>
                                </div>
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- Panel formulario -->
                <section class="aracode-login__form-panel" data-login-enter>
                    <div class="aracode-login__form-top">
                        <p class="aracode-login__form-label">Acceso seguro</p>
                        <div class="aracode-login__theme-toggle">
                            <IconSun class="w-4 h-4 text-amber-400" />
                            <label class="aracode-login__switch">
                                <input
                                    type="checkbox"
                                    class="sr-only"
                                    :checked="store.isDarkMode"
                                    @change="toggleTheme($event.target.checked)"
                                />
                                <span class="aracode-login__switch-track" />
                            </label>
                            <IconMoon class="w-4 h-4 text-indigo-300" />
                        </div>
                    </div>

                    <h2 class="aracode-login__form-title">Iniciar sesión</h2>
                    <p class="aracode-login__form-desc">Ingresa tus credenciales corporativas</p>

                    <div v-if="status" class="aracode-login__status">{{ status }}</div>

                    <form class="aracode-login__form" @submit.prevent="submit" novalidate>
                        <div class="aracode-login__field">
                            <label for="Email">Correo electrónico</label>
                            <div class="aracode-login__input-wrap">
                                <IconMail class="aracode-login__input-icon" />
                                <input
                                    id="Email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="tu@empresa.com"
                                    autocomplete="username"
                                    @input="(e) => form.email = e.target.value.trim()"
                                />
                            </div>
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="aracode-login__field">
                            <div class="aracode-login__field-head">
                                <label for="Password">Contraseña</label>
                                <Link :href="route('password.request')" class="aracode-login__link">
                                    ¿Olvidaste tu contraseña?
                                </Link>
                            </div>
                            <div class="aracode-login__input-wrap">
                                <IconLockDots class="aracode-login__input-icon" />
                                <input
                                    id="Password"
                                    v-model="form.password"
                                    type="password"
                                    placeholder="••••••••"
                                    autocomplete="current-password"
                                    @input="(e) => form.password = e.target.value.trim()"
                                />
                            </div>
                            <InputError :message="form.errors.password" />
                        </div>

                        <label class="aracode-login__remember">
                            <Checkbox v-model:checked="form.remember" />
                            <span>Recordarme en este equipo</span>
                        </label>

                        <button type="submit" class="aracode-login__submit" :disabled="form.processing">
                            <span v-if="form.processing" class="aracode-login__submit-loading">
                                <SpinnerLoading :display="true" class="aracode-login__submit-spinner" />
                                <span>Iniciando...</span>
                            </span>
                            <span v-else>Entrar al sistema</span>
                        </button>
                    </form>

                    <div v-if="socialNetworks?.length" class="aracode-login__social">
                        <p>Síguenos</p>
                        <ul>
                            <li v-for="network in socialNetworks" :key="network.id">
                                <a
                                    v-if="network.route"
                                    :href="network.route"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    :aria-label="network.id"
                                >
                                    <IconInstagram v-if="network.id === 'instagram'" class="h-4 w-4" />
                                    <IconFacebookCircle v-else-if="network.id === 'facebook'" class="h-4 w-4" />
                                    <IconTwitter v-else-if="network.id === 'x-twiter'" class="h-4 w-4" :fill="true" />
                                    <IconYoutube v-else-if="network.id === 'youtube'" class="h-4 w-4" />
                                    <span v-else class="text-xs font-bold">{{ network.id.substring(0, 2).toUpperCase() }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <p class="aracode-login__footer">
                        © {{ new Date().getFullYear() }} {{ companyName }} · ARACODE
                    </p>
                </section>
            </div>
        </div>
    </AuthLayout>
</template>

<style scoped>
.aracode-login {
    --login-accent: #06b6d4;
    --login-accent-2: #6366f1;
    --login-glow: rgba(6, 182, 212, 0.35);
    --login-surface: rgba(255, 255, 255, 0.72);
    --login-border: rgba(148, 163, 184, 0.35);
    --login-text: #0f172a;
    --login-muted: #64748b;

    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    overflow: hidden;
    color: var(--login-text);
    background: linear-gradient(135deg, #ecfeff 0%, #e0e7ff 45%, #f0f9ff 100%);
}

.aracode-login--dark {
    --login-accent: #22d3ee;
    --login-accent-2: #818cf8;
    --login-glow: rgba(34, 211, 238, 0.28);
    --login-surface: rgba(15, 23, 42, 0.78);
    --login-border: rgba(148, 163, 184, 0.18);
    --login-text: #f8fafc;
    --login-muted: #94a3b8;

    background: linear-gradient(135deg, #020617 0%, #0f172a 40%, #1e1b4b 100%);
}

.aracode-login__bg {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.aracode-login__grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(99, 102, 241, 0.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(99, 102, 241, 0.08) 1px, transparent 1px);
    background-size: 48px 48px;
    mask-image: radial-gradient(circle at center, black 30%, transparent 85%);
}

.aracode-login--dark .aracode-login__grid {
    background-image:
        linear-gradient(rgba(34, 211, 238, 0.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(34, 211, 238, 0.06) 1px, transparent 1px);
}

.aracode-login__shell {
    position: relative;
    z-index: 1;
    width: min(1120px, 100%);
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}

@media (min-width: 1024px) {
    .aracode-login__shell {
        grid-template-columns: 1.15fr 0.85fr;
        gap: 1.5rem;
    }
}

.aracode-login__info,
.aracode-login__form-panel {
    border: 1px solid var(--login-border);
    border-radius: 1.5rem;
    backdrop-filter: blur(18px);
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
}

.aracode-login__info {
    background: var(--login-surface);
    padding: 1.75rem;
}

.aracode-login__form-panel {
    background: var(--login-surface);
    padding: 1.75rem;
    display: flex;
    flex-direction: column;
}

.aracode-login__logo-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    max-width: 100%;
}

.aracode-login__logo-wrap--light {
    padding: 0.75rem 1rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid var(--login-border);
}

.aracode-login__logo-wrap--dark {
    padding: 0;
    background: transparent;
    border: none;
    box-shadow: none;
}

.aracode-login__logo {
    max-height: 4rem;
    max-width: min(280px, 100%);
    width: auto;
    object-fit: contain;
}

.aracode-login__logo-wrap--dark .aracode-login__logo {
    max-height: 4.5rem;
    filter: drop-shadow(0 8px 24px rgba(34, 211, 238, 0.15));
}

.aracode-login__badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--login-accent);
    margin-bottom: 0.75rem;
}

.aracode-login__title {
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 0.75rem;
}

.aracode-login__title span {
    background: linear-gradient(90deg, var(--login-accent), var(--login-accent-2));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.aracode-login__subtitle {
    color: var(--login-muted);
    line-height: 1.6;
    max-width: 38rem;
}

.aracode-login__benefits {
    margin-top: 1.5rem;
    display: grid;
    gap: 0.75rem;
}

@media (min-width: 768px) {
    .aracode-login__benefits {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.aracode-login__benefit {
    display: flex;
    gap: 0.75rem;
    padding: 0.85rem;
    border-radius: 0.9rem;
    border: 1px solid var(--login-border);
    background: rgba(255, 255, 255, 0.45);
}

.aracode-login--dark .aracode-login__benefit {
    background: rgba(2, 6, 23, 0.35);
}

.aracode-login__benefit-icon {
    flex-shrink: 0;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.65rem;
    display: grid;
    place-items: center;
    color: white;
    background: linear-gradient(135deg, var(--login-accent), var(--login-accent-2));
    box-shadow: 0 8px 20px var(--login-glow);
}

.aracode-login__benefit strong {
    display: block;
    font-size: 0.92rem;
    margin-bottom: 0.15rem;
}

.aracode-login__benefit p {
    font-size: 0.82rem;
    color: var(--login-muted);
    line-height: 1.45;
}

.aracode-login__modules {
    margin-top: 1.5rem;
}

.aracode-login__modules-title {
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.aracode-login__modules-grid {
    display: grid;
    gap: 0.55rem;
}

@media (min-width: 640px) {
    .aracode-login__modules-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.aracode-login__module-card {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.65rem 0.75rem;
    border-radius: 0.75rem;
    border: 1px solid var(--login-border);
    background: rgba(255, 255, 255, 0.35);
}

.aracode-login--dark .aracode-login__module-card {
    background: rgba(2, 6, 23, 0.4);
}

.aracode-login__module-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.55rem;
    display: grid;
    place-items: center;
    font-size: 0.85rem;
    color: var(--login-accent);
    background: rgba(6, 182, 212, 0.12);
    border: 1px solid rgba(6, 182, 212, 0.25);
}

.aracode-login__module-text strong {
    display: block;
    font-size: 0.82rem;
    line-height: 1.3;
}

.aracode-login__module-text small {
    color: var(--login-muted);
    font-size: 0.72rem;
}

.aracode-login__form-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.aracode-login__form-label {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--login-accent);
}

.aracode-login__theme-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.aracode-login__switch {
    position: relative;
    display: inline-flex;
    cursor: pointer;
}

.aracode-login__switch-track {
    width: 2.75rem;
    height: 1.4rem;
    border-radius: 999px;
    background: linear-gradient(90deg, #bae6fd, #c7d2fe);
    transition: background 0.3s ease;
    position: relative;
}

.aracode-login--dark .aracode-login__switch-track {
    background: linear-gradient(90deg, #334155, #4338ca);
}

.aracode-login__switch-track::after {
    content: '';
    position: absolute;
    top: 0.15rem;
    left: 0.15rem;
    width: 1.1rem;
    height: 1.1rem;
    border-radius: 50%;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease;
}

.aracode-login__switch input:checked + .aracode-login__switch-track::after {
    transform: translateX(1.35rem);
}

.aracode-login__form-title {
    font-size: 1.65rem;
    font-weight: 800;
    margin-bottom: 0.35rem;
}

.aracode-login__form-desc {
    color: var(--login-muted);
    margin-bottom: 1.25rem;
}

.aracode-login__status {
    margin-bottom: 1rem;
    padding: 0.65rem 0.85rem;
    border-radius: 0.65rem;
    font-size: 0.85rem;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #047857;
}

.aracode-login--dark .aracode-login__status {
    color: #6ee7b7;
}

.aracode-login__form {
    display: grid;
    gap: 1rem;
}

.aracode-login__field label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.35rem;
}

.aracode-login__field-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.35rem;
}

.aracode-login__input-wrap {
    position: relative;
}

.aracode-login__input-icon {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1.1rem;
    height: 1.1rem;
    color: var(--login-muted);
    pointer-events: none;
}

.aracode-login__input-wrap input {
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid var(--login-border);
    background: rgba(255, 255, 255, 0.65);
    color: var(--login-text);
    padding: 0.7rem 0.85rem 0.7rem 2.5rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.aracode-login--dark .aracode-login__input-wrap input {
    background: rgba(2, 6, 23, 0.45);
}

.aracode-login__input-wrap input:focus {
    outline: none;
    border-color: var(--login-accent);
    box-shadow: 0 0 0 3px var(--login-glow);
}

.aracode-login__link {
    font-size: 0.8rem;
    color: var(--login-accent-2);
}

.aracode-login__remember {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--login-muted);
}

.aracode-login__submit {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    border: none;
    border-radius: 0.85rem;
    padding: 0.85rem 1rem;
    font-weight: 700;
    color: white;
    cursor: pointer;
    background: linear-gradient(90deg, var(--login-accent), var(--login-accent-2));
    box-shadow: 0 12px 30px var(--login-glow);
    transition: transform 0.2s ease, opacity 0.2s ease;
}

.aracode-login__submit-loading {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    line-height: 1;
}

.aracode-login__submit-spinner {
    width: 1rem;
    height: 1rem;
    margin: 0 !important;
    flex-shrink: 0;
    display: block;
}

.aracode-login__submit:hover:not(:disabled) {
    transform: translateY(-1px);
}

.aracode-login__submit:disabled {
    opacity: 0.75;
    cursor: wait;
}

.aracode-login__social {
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--login-border);
}

.aracode-login__social p {
    font-size: 0.78rem;
    color: var(--login-muted);
    margin-bottom: 0.5rem;
}

.aracode-login__social ul {
    display: flex;
    gap: 0.5rem;
}

.aracode-login__social a {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 999px;
    display: grid;
    place-items: center;
    border: 1px solid var(--login-border);
    color: var(--login-muted);
    transition: all 0.2s ease;
}

.aracode-login__social a:hover {
    color: var(--login-accent);
    border-color: var(--login-accent);
}

.aracode-login__footer {
    margin-top: auto;
    padding-top: 1.25rem;
    font-size: 0.78rem;
    color: var(--login-muted);
    text-align: center;
}
</style>

<style>
.aracode-login-orb {
    position: absolute;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(34, 211, 238, 0.9), transparent 70%);
    pointer-events: none;
}

.aracode-login-ring {
    position: absolute;
    border-radius: 50%;
    border: 1px solid rgba(99, 102, 241, 0.25);
    pointer-events: none;
}

.aracode-login--dark .aracode-login-ring {
    border-color: rgba(34, 211, 238, 0.2);
}
</style>
