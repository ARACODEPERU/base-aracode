<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

/*
 * Marco visual de las pantallas de acceso de Pichanguero.
 *
 * Lo comparten el registro y el login de organizadores. Concentra el fondo, la
 * cabecera, la columna editorial, la tarjeta y los estilos de los controles.
 * Todo el CSS es `scoped`: no toca las pantallas de acceso de los demás temas.
 *
 * Las pantallas solo pasan sus textos y meten su formulario en el slot.
 */

const props = defineProps({
    pageTitle: { type: String, required: true },
    eyebrow: { type: String, required: true },
    line1: { type: String, required: true },
    accent: { type: String, required: true },
    line3: { type: String, default: '' },
    lead: { type: String, required: true },
    facts: { type: Array, default: () => [] },
    kicker: { type: String, required: true },
    cardTitle: { type: String, required: true },
    cardSubtitle: { type: String, required: true },
    // Permiten que el login use la foto de cancha y el tono deportivo del
    // acceso anterior sin cambiar la portada del registro.
    backgroundImage: { type: String, default: '' },
    backgroundPosition: { type: String, default: 'center 40%' },
    fieldLines: { type: Boolean, default: false },
    pitchTone: { type: Boolean, default: false },
});

const baseUrl = assetUrl;
const logo = `${baseUrl}themes/pichanguero/images/Logo-vertical.png`;
const fondo = computed(() => props.backgroundImage || `${baseUrl}themes/pichanguero/images/hero-bg.jpg`);
</script>

<template>
    <Head>
        <title>{{ pageTitle }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:ital,wght@0,700;0,800;1,800;1,900&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="pgr-page" :class="{ 'pgr-page--pitch': pitchTone }">
        <div class="pgr-bg" :class="{ 'pgr-bg--pitch': pitchTone }" aria-hidden="true">
            <img
                :src="fondo"
                alt=""
                width="1920"
                height="950"
                :style="{ objectPosition: backgroundPosition }"
            />
            <span v-if="fieldLines" class="pgr-field-circle"></span>
        </div>
        <span class="pgr-slash" aria-hidden="true"></span>
        <span class="pgr-slash is-thin" aria-hidden="true"></span>

        <header class="pgr-topbar">
            <a :href="route('pichanguero.home')" class="pgr-brand">
                <img :src="logo" alt="Pichanguero" width="260" height="48" />
            </a>

            <a :href="route('pichanguero.home')" class="pgr-back">
                Volver al sitio
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        </header>

        <main class="pgr-main">
            <section class="pgr-hero">
                <p class="pgr-eyebrow">{{ eyebrow }}</p>

                <h1 class="pgr-title">
                    {{ line1 }}<br />
                    <span class="pgr-accent">
                        {{ accent }}
                        <svg class="pgr-underline" viewBox="0 0 300 18" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M3 12c60-7 118-9 186-6 39 2 74 5 108 3" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" />
                        </svg>
                    </span>
                    <template v-if="line3"><br />{{ line3 }}</template>
                </h1>

                <p class="pgr-lead">{{ lead }}</p>

                <ul v-if="facts.length" class="pgr-facts">
                    <li v-for="(fact, index) in facts" :key="index">
                        <strong>{{ fact.value }}</strong>
                        <span>{{ fact.label }}</span>
                    </li>
                </ul>
            </section>

            <section class="pgr-card">
                <p class="pgr-kicker">{{ kicker }}</p>
                <h2>{{ cardTitle }}</h2>
                <p class="pgr-card-sub">{{ cardSubtitle }}</p>

                <div class="pgr-form">
                    <slot />
                </div>

                <p v-if="$slots.footer" class="pgr-login">
                    <slot name="footer" />
                </p>
            </section>
        </main>
    </div>
</template>

<style scoped>
.pgr-page {
    --pgr-navy: #101d2d;
    --pgr-green: #1b9447;
    --pgr-forest: #087443;
    --pgr-lime: #b8f34a;
    --pgr-ink: #0b2239;
    --pgr-slate: #5a6b7d;
    --pgr-line: #dce6ee;

    position: relative;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    min-height: 100dvh;
    /* Recorta las franjas decorativas: antes sobresalían por debajo del fondo
       y dejaban una banda del color del body al final de la página. */
    overflow: hidden;
    background-color: var(--pgr-navy);
    color: #ffffff;
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
    -webkit-font-smoothing: antialiased;
}

/* ---- Fondo: foto del sitio + velo azul noche + franjas lima ---- */
.pgr-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
    overflow: hidden;
}

.pgr-bg > img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 40%;
    opacity: 0.55;
}

.pgr-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background:
        linear-gradient(105deg, rgba(16, 29, 45, 0.97) 0%, rgba(16, 29, 45, 0.9) 42%, rgba(16, 29, 45, 0.68) 100%),
        linear-gradient(to top, var(--pgr-navy) 0%, transparent 58%);
}

/* Variante del login: la foto de cancha se mezcla con el verde del tema. */
.pgr-bg--pitch > img {
    opacity: 0.8;
    filter: saturate(1.05);
}

.pgr-bg--pitch::after {
    background:
        linear-gradient(105deg, rgba(16, 29, 45, 0.95) 0%, rgba(9, 44, 36, 0.85) 42%, rgba(6, 58, 40, 0.52) 100%),
        linear-gradient(to top, var(--pgr-navy) 0%, transparent 64%);
}

/* Líneas de cancha del acceso anterior: centro y círculo, muy tenues. */
.pgr-bg--pitch::before {
    content: '';
    position: absolute;
    z-index: 1;
    top: 50%;
    left: 0;
    right: 0;
    height: 2px;
    background-color: rgba(255, 255, 255, 0.16);
}

.pgr-field-circle {
    position: absolute;
    z-index: 1;
    top: 50%;
    left: 50%;
    width: min(46vmin, 420px);
    height: min(46vmin, 420px);
    border: 2px solid rgba(255, 255, 255, 0.15);
    border-radius: 50%;
    transform: translate(-50%, -50%);
}

.pgr-slash {
    position: absolute;
    z-index: 1;
    top: -10%;
    right: -14%;
    width: 30%;
    height: 130%;
    background-image: linear-gradient(180deg, var(--pgr-lime) 0%, var(--pgr-green) 55%, var(--pgr-forest) 100%);
    transform: skewX(-14deg);
    opacity: 0.12;
    -webkit-mask-image: linear-gradient(90deg, transparent, #000 70%);
    mask-image: linear-gradient(90deg, transparent, #000 70%);
    pointer-events: none;
}

.pgr-slash.is-thin {
    top: -12%;
    right: 5%;
    width: 5%;
    opacity: 0.28;
}

/* ---- Cabecera ---- */
.pgr-topbar {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.05rem clamp(1rem, 4vw, 2.5rem);
    border-bottom: 1px solid rgba(255, 255, 255, 0.14);
    background-color: rgba(16, 29, 45, 0.35);
    backdrop-filter: blur(6px);
}

.pgr-brand {
    display: inline-flex;
    align-items: center;
}

.pgr-brand img {
    display: block;
    width: auto;
    height: 38px;
}

.pgr-back {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    color: #c9d6e2;
    font-size: 0.82rem;
    font-weight: 600;
    transition: color 0.2s ease;
}

.pgr-back svg {
    width: 16px;
    height: 16px;
}

.pgr-back:hover {
    color: var(--pgr-lime);
}

/* ---- Composición ---- */
.pgr-main {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    gap: 2.25rem;
    width: 100%;
    max-width: 1180px;
    margin: 0 auto;
    padding: clamp(2rem, 5vw, 4.5rem) clamp(1rem, 4vw, 2.5rem) clamp(2.5rem, 6vw, 5rem);
    flex: 1;
}

@media (min-width: 1180px) {
    .pgr-main {
        grid-template-columns: minmax(0, 1fr) minmax(0, 540px);
        align-items: start;
        gap: clamp(2.5rem, 5vw, 4rem);
    }
}

/* ---- Columna editorial ---- */
.pgr-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.42rem 0.85rem;
    border: 1px solid rgba(184, 243, 74, 0.42);
    border-radius: 999px;
    background-color: rgba(184, 243, 74, 0.12);
    color: var(--pgr-lime);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.pgr-eyebrow::before {
    content: '';
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background-color: var(--pgr-lime);
    box-shadow: 0 0 0 4px rgba(184, 243, 74, 0.18);
}

.pgr-title {
    margin: 1.25rem 0 0;
    color: #ffffff;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: clamp(2.15rem, 4.8vw, 3.7rem);
    font-style: italic;
    font-weight: 900;
    line-height: 0.96;
    letter-spacing: -0.025em;
    text-transform: uppercase;
}

.pgr-accent {
    position: relative;
    display: inline-block;
    color: var(--pgr-lime);
}

.pgr-underline {
    position: absolute;
    left: -2%;
    right: -2%;
    bottom: -0.14em;
    width: 104%;
    height: 0.16em;
    color: var(--pgr-lime);
    overflow: visible;
    pointer-events: none;
}

.pgr-lead {
    max-width: 36ch;
    margin: 1.5rem 0 0;
    color: #c9d6e2;
    font-size: 1.02rem;
    line-height: 1.65;
}

.pgr-facts {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    margin: 2.1rem 0 0;
    padding: 1.25rem 0 0;
    border-top: 1px solid rgba(255, 255, 255, 0.14);
    list-style: none;
}

.pgr-facts strong {
    display: block;
    color: #ffffff;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 1.2rem;
    font-weight: 800;
    line-height: 1.1;
}

.pgr-facts span {
    display: block;
    margin-top: 0.18rem;
    color: #9fb0c0;
    font-size: 0.74rem;
    line-height: 1.35;
}

/* ---- Tarjeta ---- */
.pgr-card {
    position: relative;
    width: 100%;
    max-width: 720px;
    margin-right: auto;
    margin-left: auto;
    padding: clamp(1.35rem, 3vw, 2.15rem);
    border: 1px solid rgba(220, 230, 238, 0.9);
    border-top: 4px solid var(--pgr-lime);
    border-radius: 22px;
    background-color: #ffffff;
    color: var(--pgr-ink);
    box-shadow: 0 40px 80px -40px rgba(0, 0, 0, 0.8);
}

/* El login usa un borde parejo: sin franja superior, la tarjeta se ve
   integrada y no aparece la línea suelta sobre la esquina redondeada. */
.pgr-page--pitch .pgr-card {
    border-top-width: 1px;
    border-top-color: rgba(220, 230, 238, 0.9);
}

/* Punto lima del encabezado: reemplaza la franja sin añadir otra línea. */
.pgr-page--pitch .pgr-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
}

.pgr-page--pitch .pgr-kicker::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background-color: var(--pgr-lime);
    box-shadow: 0 0 0 3px rgba(184, 243, 74, 0.18);
}

/* Campos apenas tintados para que se distingan del blanco de la tarjeta.
   Se repite la clase del contenedor para ganarle a la regla base de los
   controles, que aparece más abajo en la hoja. */
.pgr-page.pgr-page--pitch .pgr-form :deep(.form-input),
.pgr-page.pgr-page--pitch .pgr-form :deep(.form-select) {
    border-color: #d7e3ec;
    background-color: #f8fafc;
}

.pgr-page.pgr-page--pitch .pgr-form :deep(.form-input:focus),
.pgr-page.pgr-page--pitch .pgr-form :deep(.form-select:focus) {
    background-color: #ffffff;
}

.pgr-kicker {
    color: var(--pgr-forest);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.pgr-card h2 {
    margin: 0.4rem 0 0;
    color: var(--pgr-ink);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: clamp(1.35rem, 2.4vw, 1.65rem);
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: -0.015em;
}

.pgr-card-sub {
    margin: 0.55rem 0 1.6rem;
    color: var(--pgr-slate);
    font-size: 0.9rem;
    line-height: 1.5;
}

/* ---- Controles: misma piel para el registro y el login ---- */
.pgr-page .pgr-form :deep(fieldset) {
    min-width: 0;
    margin: 0;
    padding: 0;
    border: 0;
}

.pgr-page .pgr-form :deep(legend) {
    width: 100%;
    margin: 0 0 0.85rem;
    padding: 0 0 0.55rem;
    border-bottom: 1px solid var(--pgr-line);
    color: var(--pgr-ink);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.01em;
}

.pgr-page .pgr-form :deep(fieldset label),
.pgr-page .pgr-form :deep(.pgr-label) {
    display: block;
    margin: 0 0 0.4rem;
    color: #33475b;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.075em;
    line-height: 1.35;
    text-transform: uppercase;
}

.pgr-page .pgr-form :deep(.form-input),
.pgr-page .pgr-form :deep(.form-select) {
    width: 100%;
    min-height: 46px;
    padding: 0.72rem 0.85rem;
    border: 1px solid var(--pgr-line);
    border-radius: 10px;
    background-color: #ffffff;
    color: var(--pgr-ink);
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
    font-size: 0.92rem;
    line-height: 1.4;
    box-shadow: none;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.pgr-page .pgr-form :deep(.form-input::placeholder) {
    color: #93a3b3;
}

.pgr-page .pgr-form :deep(.form-input:focus),
.pgr-page .pgr-form :deep(.form-select:focus) {
    border-color: var(--pgr-green);
    background-color: #ffffff;
    color: var(--pgr-ink);
    outline: none;
    box-shadow: 0 0 0 3px rgba(27, 148, 71, 0.16);
}

.pgr-page .pgr-form :deep(.form-checkbox) {
    width: 18px;
    height: 18px;
    margin-top: 0.1rem;
    border: 1px solid #b9c8d6;
    border-radius: 4px;
    background-image: none;
    color: var(--pgr-green);
    accent-color: var(--pgr-green);
    appearance: auto;
    box-shadow: none;
}

.pgr-page .pgr-form :deep(.form-checkbox:focus) {
    outline: none;
    box-shadow: 0 0 0 3px rgba(27, 148, 71, 0.16);
}

.pgr-page .pgr-form :deep(.form-checkbox + span) {
    color: var(--pgr-slate);
    font-size: 0.82rem;
    font-weight: 400;
    letter-spacing: 0;
    line-height: 1.5;
    text-transform: none;
}

.pgr-page .pgr-form :deep(.form-checkbox + span a),
.pgr-page .pgr-form :deep(a) {
    color: var(--pgr-forest);
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.pgr-page .pgr-form :deep(button[type='submit']) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 1rem 1.4rem;
    border: 0;
    border-radius: 10px;
    background-color: var(--pgr-lime);
    color: var(--pgr-navy);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    line-height: 1.2;
    text-align: center;
    text-transform: uppercase;
    cursor: pointer;
    box-shadow: 0 18px 40px -20px rgba(184, 243, 74, 0.55);
    transition: transform 0.2s ease, filter 0.2s ease, opacity 0.2s ease;
}

.pgr-page--pitch .pgr-form :deep(button[type='submit']) {
    background-image: linear-gradient(120deg, var(--pgr-lime) 0%, #4ade80 52%, var(--pgr-green) 100%);
    box-shadow: 0 18px 40px -18px rgba(27, 148, 71, 0.6);
}

.pgr-page .pgr-form :deep(button[type='submit']:hover:not(:disabled)) {
    filter: brightness(1.06);
    transform: translateY(-2px);
}

.pgr-page .pgr-form :deep(button[type='submit']:disabled) {
    cursor: not-allowed;
    opacity: 0.45;
    box-shadow: none;
}

.pgr-page .pgr-form :deep(.text-red-600),
.pgr-page .pgr-form :deep(.text-red-400) {
    margin-top: 0.35rem;
    color: #b42318;
    font-size: 0.8rem;
    line-height: 1.4;
}

/* ---- Piezas propias del login ---- */
.pgr-page .pgr-form :deep(.pgr-login-row) {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.pgr-page .pgr-form :deep(.pgr-check) {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    color: var(--pgr-slate);
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
    font-size: 0.84rem;
    font-weight: 400;
    letter-spacing: 0;
    text-transform: none;
    cursor: pointer;
}

.pgr-page .pgr-form :deep(.pgr-forgot) {
    color: var(--pgr-forest);
    font-size: 0.8rem;
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.pgr-page .pgr-form :deep(.pgr-forgot:hover) {
    color: var(--pgr-green);
}

.pgr-page .pgr-form :deep(.pgr-alert) {
    margin: 0;
    padding: 0.75rem 0.9rem;
    border: 1px solid rgba(27, 148, 71, 0.35);
    border-radius: 10px;
    background-color: rgba(27, 148, 71, 0.08);
    color: #0b5d38;
    font-size: 0.84rem;
    line-height: 1.45;
}

.pgr-page .pgr-form :deep(.pgr-alert-error) {
    border-color: rgba(180, 35, 24, 0.3);
    background-color: #fef3f2;
    color: #b42318;
    font-weight: 600;
}

.pgr-login {
    margin: 1.5rem 0 0;
    color: var(--pgr-slate);
    font-size: 0.86rem;
    text-align: center;
}

.pgr-login :deep(a) {
    color: var(--pgr-forest);
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.pgr-page a:focus-visible {
    border-radius: 4px;
    outline: 2px solid var(--pgr-lime);
    outline-offset: 3px;
}

@media (max-width: 640px) {
    .pgr-topbar {
        padding: 0.9rem 1rem;
    }

    .pgr-brand img {
        height: 32px;
    }

    .pgr-back {
        font-size: 0.76rem;
    }

    .pgr-main {
        padding-top: 2rem;
    }

    .pgr-facts {
        gap: 0.75rem;
    }

    .pgr-facts strong {
        font-size: 1.02rem;
    }

    .pgr-facts span {
        font-size: 0.66rem;
    }

    .pgr-card {
        padding: 1.25rem;
    }
}

/* En móvil el login muestra solo la cabecera y el formulario. */
@media (max-width: 767px) {
    .pgr-page--pitch .pgr-hero {
        display: none;
    }

    .pgr-page--pitch .pgr-main {
        gap: 0;
        padding-top: 1.25rem;
        padding-bottom: 1.5rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .pgr-page * {
        transition: none !important;
    }
}
</style>
