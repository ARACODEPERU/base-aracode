<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    edition: { type: Object, default: null },
    sportsMode: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const loading = ref(false);
const error = ref('');
const data = ref(null);
const isDark = ref(false);
let controller = null;
let themeObserver = null;

const totals = computed(() => data.value?.totals || {});
const breakdowns = computed(() => data.value?.breakdowns || {});
const recent = computed(() => data.value?.recent || []);
const hasVisits = computed(() => Number(totals.value.views || 0) > 0);

const number = (value) => Number(value || 0).toLocaleString('es-PE');
const percent = (item, list) => {
    const max = Math.max(1, ...list.map((row) => Number(row.total || 0)));
    return Math.max(4, Math.round((Number(item.total || 0) / max) * 100));
};

const dateLabel = (value, fallback = 'Sin registros') => {
    if (!value) return fallback;
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return fallback;
    return new Intl.DateTimeFormat('es-PE', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    }).format(date);
};

const breakdownCards = computed(() => [
    { key: 'sources', title: 'De dónde ingresó', icon: '🧭', items: breakdowns.value.sources || [], empty: 'Sin datos de procedencia' },
    { key: 'cities', title: 'Ciudad', icon: '📍', items: breakdowns.value.cities || [], empty: 'Sin ciudad identificada' },
    { key: 'countries', title: 'País', icon: '🌎', items: breakdowns.value.countries || [], empty: 'Sin país identificado' },
    { key: 'browsers', title: 'Navegador', icon: '🌐', items: breakdowns.value.browsers || [], empty: 'Sin navegador identificado' },
    { key: 'platforms', title: 'Sistema operativo', icon: '💻', items: breakdowns.value.platforms || [], empty: 'Sin sistema identificado' },
    { key: 'devices', title: 'Dispositivo', icon: '📱', items: breakdowns.value.devices || [], empty: 'Sin dispositivo identificado' },
    { key: 'referrers', title: 'Sitios de referencia', icon: '🔗', items: breakdowns.value.referrers || [], empty: 'Sin sitios de referencia' },
]);

const deviceLabel = (device) => ({
    mobile: 'Móvil', tablet: 'Tablet', desktop: 'Computadora',
}[device] || device || 'Sin identificar');

const reset = () => {
    data.value = null;
    error.value = '';
};

const load = async () => {
    if (!props.open || !props.edition?.id) return;

    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    error.value = '';

    try {
        const response = await axios.get(route('even_ediciones_landing_visits', props.edition.id), {
            signal: controller.signal,
        });
        data.value = response.data?.data || null;
    } catch (exception) {
        if (exception?.code === 'ERR_CANCELED' || exception?.name === 'CanceledError') return;
        error.value = 'No se pudieron cargar las visitas. Inténtalo de nuevo.';
    } finally {
        loading.value = false;
    }
};

const close = () => {
    controller?.abort();
    emit('close');
};

const handleKeydown = (event) => {
    if (event.key === 'Escape' && props.open) close();
};

const lockScroll = (locked) => {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = locked ? 'hidden' : '';
};

watch(
    () => props.open,
    (open) => {
        lockScroll(open);
        if (open) {
            reset();
            load();
        } else {
            controller?.abort();
        }
    },
    { immediate: true },
);

watch(() => props.edition?.id, () => {
    if (props.open) {
        reset();
        load();
    }
});

const syncTheme = () => {
    isDark.value = typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    syncTheme();
    themeObserver = new MutationObserver(syncTheme);
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onBeforeUnmount(() => {
    controller?.abort();
    document.removeEventListener('keydown', handleKeydown);
    themeObserver?.disconnect();
    lockScroll(false);
});
</script>

<template>
    <Teleport to="body">
        <Transition name="lv-fade">
            <div v-if="open" class="lv-overlay" :class="[sportsMode ? 'lv--sports' : '', isDark && !sportsMode ? 'lv--dark' : '']" role="dialog" aria-modal="true" aria-labelledby="landing-visits-title">
                <button type="button" class="lv-backdrop" aria-label="Cerrar el detalle de visitas" @click="close"></button>

                <section class="lv-dialog">
                    <header class="lv-header">
                        <div class="lv-header__glow" aria-hidden="true"></div>
                        <div class="relative flex min-w-0 items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="lv-eyebrow">Analítica de la landing</p>
                                <h2 id="landing-visits-title" class="lv-title">{{ edition?.name || 'Visitas del torneo' }}</h2>
                                <p class="lv-subtitle">{{ edition?.evento?.title || 'Torneo' }} · {{ edition?.year || '' }}</p>
                            </div>
                            <button type="button" class="lv-close" aria-label="Cerrar" @click="close">✕</button>
                        </div>

                        <div v-if="data" class="relative mt-4 flex flex-wrap items-end gap-x-8 gap-y-3">
                            <div>
                                <p class="lv-header__metric">{{ number(totals.views) }}</p>
                                <p class="lv-header__caption">visualizaciones totales</p>
                            </div>
                            <div>
                                <p class="lv-header__metric lv-header__metric--small">{{ number(totals.unique_visitors) }}</p>
                                <p class="lv-header__caption">visitantes únicos</p>
                            </div>
                            <div class="ml-auto flex flex-wrap gap-2">
                                <button type="button" class="lv-ghost-btn" :disabled="loading" @click="load">
                                    {{ loading ? 'Actualizando…' : 'Actualizar' }}
                                </button>
                                <a v-if="data.edition?.landing_url" :href="data.edition.landing_url" target="_blank" rel="noopener" class="lv-ghost-btn">
                                    Abrir landing ↗
                                </a>
                            </div>
                        </div>
                    </header>

                    <div class="lv-body">
                        <div v-if="loading && !data" class="lv-state">
                            <span class="lv-spinner" aria-hidden="true"></span>
                            <p>Cargando visitas de la landing…</p>
                        </div>

                        <div v-else-if="error" class="lv-state lv-state--error">
                            <p>{{ error }}</p>
                            <button type="button" class="lv-primary-btn" @click="load">Reintentar</button>
                        </div>

                        <template v-else-if="data">
                            <div class="lv-kpis">
                                <article class="lv-kpi">
                                    <span class="lv-kpi__icon" aria-hidden="true">👁️</span>
                                    <p class="lv-kpi__value">{{ number(totals.views) }}</p>
                                    <p class="lv-kpi__label">Visitas totales</p>
                                </article>
                                <article class="lv-kpi">
                                    <span class="lv-kpi__icon" aria-hidden="true">🧑‍🤝‍🧑</span>
                                    <p class="lv-kpi__value">{{ number(totals.unique_visitors) }}</p>
                                    <p class="lv-kpi__label">Visitantes únicos</p>
                                </article>
                                <article class="lv-kpi">
                                    <span class="lv-kpi__icon" aria-hidden="true">📅</span>
                                    <p class="lv-kpi__value">{{ number(totals.today) }}</p>
                                    <p class="lv-kpi__label">Visitas de hoy</p>
                                </article>
                                <article class="lv-kpi">
                                    <span class="lv-kpi__icon" aria-hidden="true">📈</span>
                                    <p class="lv-kpi__value">{{ number(totals.last_7_days) }}</p>
                                    <p class="lv-kpi__label">Últimos 7 días</p>
                                </article>
                            </div>

                            <p class="lv-last">
                                Última visita registrada:
                                <strong>{{ dateLabel(totals.last_visit_at) }}</strong>
                                <span v-if="data.generated_at"> · Actualizado {{ dateLabel(data.generated_at) }}</span>
                            </p>

                            <div v-if="!hasVisits" class="lv-empty">
                                <span class="lv-empty__icon" aria-hidden="true">📡</span>
                                <h3>Todavía no hay visitas registradas</h3>
                                <p>Cuando compartas la landing del torneo, aquí verás de dónde llegan los visitantes, su ciudad, navegador y dispositivo.</p>
                            </div>

                            <template v-else>
                                <div class="lv-breakdowns">
                                    <article v-for="card in breakdownCards" :key="card.key" class="lv-card">
                                        <header class="lv-card__head">
                                            <span aria-hidden="true">{{ card.icon }}</span>
                                            <h3>{{ card.title }}</h3>
                                        </header>
                                        <ul v-if="card.items.length" class="lv-list">
                                            <li v-for="item in card.items" :key="`${card.key}-${item.label}`">
                                                <div class="lv-list__line">
                                                    <span :title="item.label">
                                                        {{ card.key === 'devices' ? deviceLabel(item.label) : item.label }}
                                                    </span>
                                                    <strong>{{ number(item.total) }}</strong>
                                                </div>
                                                <div class="lv-bar" aria-hidden="true">
                                                    <span :style="{ width: percent(item, card.items) + '%' }"></span>
                                                </div>
                                            </li>
                                        </ul>
                                        <p v-else class="lv-card__empty">{{ card.empty }}</p>
                                    </article>
                                </div>

                                <section class="lv-recent">
                                    <header class="lv-recent__head">
                                        <div>
                                            <h3>Últimas visitas</h3>
                                            <p>Detalle de los accesos más recientes, sin datos personales.</p>
                                        </div>
                                        <span class="lv-chip">{{ number(recent.length) }} recientes</span>
                                    </header>

                                    <div class="lv-table-wrap">
                                        <table class="lv-table">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Fecha y hora</th>
                                                    <th scope="col">Ingresó desde</th>
                                                    <th scope="col">Ubicación</th>
                                                    <th scope="col">Navegador</th>
                                                    <th scope="col">Sistema</th>
                                                    <th scope="col">Dispositivo</th>
                                                    <th scope="col">Visitante</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="visit in recent" :key="visit.id">
                                                    <td class="whitespace-nowrap">{{ visit.viewed_at_label || dateLabel(visit.viewed_at) }}</td>
                                                    <td>
                                                        <span class="lv-source">{{ visit.source }}</span>
                                                        <small v-if="visit.referrer_host" :title="visit.referrer">{{ visit.referrer_host }}</small>
                                                    </td>
                                                    <td>
                                                        <span>{{ visit.location || 'Sin ubicación' }}</span>
                                                        <small v-if="visit.country_code && !visit.country">{{ visit.country_code }}</small>
                                                    </td>
                                                    <td>{{ visit.browser || 'Sin identificar' }}</td>
                                                    <td>{{ visit.platform || 'Sin identificar' }}</td>
                                                    <td><span class="lv-device">{{ visit.device_label || deviceLabel(visit.device_type) }}</span></td>
                                                    <td class="lv-visitor">{{ visit.visitor }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            </template>

                            <p class="lv-note">
                                El contador excluye accesos automáticos de buscadores y bots. La ciudad se completa con la ubicación aproximada de la conexión; una visita directa no trae sitio de referencia.
                            </p>
                        </template>
                    </div>
                </section>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.lv-overlay {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding: 0;
}

.lv--sports {
    --lv-bg: #0b1f2b;
    --lv-surface: #0f2735;
    --lv-surface-2: #122c3a;
    --lv-border: #2b4b5d;
    --lv-text: #e9f4f2;
    --lv-muted: #9ab4bd;
    --lv-accent: #c8f169;
    --lv-accent-text: #08251a;
    --lv-track: rgb(255 255 255 / 8%);
}

.lv-overlay:not(.lv--sports) {
    --lv-bg: #ffffff;
    --lv-surface: #f8fafc;
    --lv-surface-2: #f1f5f9;
    --lv-border: #e2e8f0;
    --lv-text: #0f172a;
    --lv-muted: #64748b;
    --lv-accent: #0284c7;
    --lv-accent-text: #ffffff;
    --lv-track: #e2e8f0;
}

.lv-overlay.lv--dark {
    --lv-bg: #0f172a;
    --lv-surface: #111c30;
    --lv-surface-2: #17233a;
    --lv-border: #273449;
    --lv-text: #e2e8f0;
    --lv-muted: #94a3b8;
    --lv-accent: #38bdf8;
    --lv-accent-text: #082f49;
    --lv-track: rgb(255 255 255 / 9%);
}

.lv-backdrop {
    position: absolute;
    inset: 0;
    border: 0;
    background: rgb(2 6 23 / 72%);
    backdrop-filter: blur(3px);
}

.lv-dialog {
    position: relative;
    display: flex;
    max-height: 94dvh;
    width: 100%;
    max-width: 72rem;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--lv-border);
    border-radius: 1.5rem 1.5rem 0 0;
    background: var(--lv-bg);
    color: var(--lv-text);
    box-shadow: 0 30px 90px rgb(0 0 0 / 45%);
}

@media (min-width: 640px) {
    .lv-overlay {
        align-items: center;
        padding: 1rem;
    }

    .lv-dialog {
        border-radius: 1.5rem;
    }
}

.lv-header {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    padding: 1.1rem 1.1rem 1.25rem;
    background: linear-gradient(135deg, #0b1f2b 0%, #123446 55%, #17394a 100%);
    color: #f8fafc;
}

@media (min-width: 640px) {
    .lv-header {
        padding: 1.5rem 1.75rem;
    }
}

.lv-header__glow {
    position: absolute;
    inset: 0;
    z-index: -1;
    background:
        radial-gradient(60% 140% at 92% -20%, rgb(200 241 105 / 18%), transparent 62%),
        radial-gradient(50% 130% at 0% 120%, rgb(56 189 248 / 18%), transparent 60%);
}

.lv-eyebrow {
    margin: 0 0 0.35rem;
    font-size: 0.69rem;
    font-weight: 800;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #c8f169;
}

.lv-title {
    margin: 0;
    font-size: clamp(1.25rem, 2.5vw, 1.8rem);
    font-weight: 900;
    line-height: 1.15;
    letter-spacing: -0.02em;
    overflow-wrap: anywhere;
}

.lv-subtitle {
    margin: 0.3rem 0 0;
    font-size: 0.82rem;
    color: #b7cbd2;
}

.lv-close {
    display: grid;
    height: 2.3rem;
    width: 2.3rem;
    flex: none;
    place-items: center;
    border: 1px solid rgb(255 255 255 / 18%);
    border-radius: 9999px;
    background: rgb(255 255 255 / 6%);
    color: #e2e8f0;
    font-size: 0.95rem;
    transition: background-color 0.18s ease, border-color 0.18s ease;
}

.lv-close:hover {
    border-color: rgb(200 241 105 / 50%);
    background: rgb(200 241 105 / 12%);
    color: #d9f99d;
}

.lv-close:focus-visible,
.lv-ghost-btn:focus-visible,
.lv-primary-btn:focus-visible {
    outline: 2px solid #c8f169;
    outline-offset: 2px;
}

.lv-header__metric {
    margin: 0;
    font-size: clamp(1.9rem, 4vw, 2.8rem);
    font-weight: 900;
    line-height: 1;
    color: #ffffff;
    font-variant-numeric: tabular-nums;
}

.lv-header__metric--small {
    font-size: clamp(1.4rem, 3vw, 2rem);
    color: #d9f99d;
}

.lv-header__caption {
    margin: 0.15rem 0 0;
    font-size: 0.72rem;
    font-weight: 600;
    color: #a9c0c8;
}

.lv-ghost-btn {
    display: inline-flex;
    min-height: 2.4rem;
    align-items: center;
    justify-content: center;
    border: 1px solid rgb(255 255 255 / 20%);
    border-radius: 0.75rem;
    background: rgb(255 255 255 / 6%);
    padding: 0.45rem 0.85rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #e9f4f2;
    transition: border-color 0.18s ease, background-color 0.18s ease;
}

.lv-ghost-btn:hover:not(:disabled) {
    border-color: rgb(200 241 105 / 55%);
    background: rgb(200 241 105 / 10%);
    color: #d9f99d;
}

.lv-ghost-btn:disabled {
    opacity: 0.6;
    cursor: wait;
}

.lv-body {
    overflow-y: auto;
    padding: 1rem;
    background: var(--lv-bg);
}

@media (min-width: 640px) {
    .lv-body {
        padding: 1.35rem 1.75rem 1.75rem;
    }
}

.lv-state {
    display: grid;
    min-height: 18rem;
    place-items: center;
    gap: 0.7rem;
    text-align: center;
    color: var(--lv-muted);
    font-size: 0.9rem;
}

.lv-state--error {
    color: #b91c1c;
}

.lv--dark .lv-state--error {
    color: #fca5a5;
}

.lv-spinner {
    height: 2rem;
    width: 2rem;
    border: 3px solid var(--lv-track);
    border-top-color: var(--lv-accent);
    border-radius: 9999px;
    animation: lv-spin 0.8s linear infinite;
}

@keyframes lv-spin {
    to { transform: rotate(360deg); }
}

.lv-primary-btn {
    min-height: 2.5rem;
    border: 0;
    border-radius: 0.75rem;
    background: var(--lv-accent);
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
    font-weight: 800;
    color: var(--lv-accent-text);
}

.lv-kpis {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
}

@media (min-width: 768px) {
    .lv-kpis {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.8rem;
    }
}

.lv-kpi {
    min-width: 0;
    border: 1px solid var(--lv-border);
    border-radius: 1rem;
    background: var(--lv-surface);
    padding: 0.85rem;
}

.lv-kpi__icon {
    display: inline-grid;
    height: 1.8rem;
    width: 1.8rem;
    place-items: center;
    border-radius: 0.55rem;
    background: var(--lv-surface-2);
    font-size: 0.9rem;
}

.lv-kpi__value {
    margin: 0.5rem 0 0;
    font-size: 1.45rem;
    font-weight: 900;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.lv-kpi__label {
    margin: 0.2rem 0 0;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--lv-muted);
}

.lv-last {
    margin: 0.8rem 0 0;
    font-size: 0.75rem;
    color: var(--lv-muted);
}

.lv-last strong {
    color: var(--lv-text);
}

.lv-empty {
    margin-top: 1rem;
    border: 1px dashed var(--lv-border);
    border-radius: 1rem;
    padding: 2.2rem 1rem;
    text-align: center;
}

.lv-empty__icon {
    font-size: 2rem;
}

.lv-empty h3 {
    margin: 0.5rem 0 0.2rem;
    font-size: 1rem;
    font-weight: 800;
}

.lv-empty p {
    margin: 0 auto;
    max-width: 32rem;
    font-size: 0.82rem;
    color: var(--lv-muted);
}

.lv-breakdowns {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
    margin-top: 1rem;
}

@media (min-width: 768px) {
    .lv-breakdowns {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (min-width: 1180px) {
    .lv-breakdowns {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

.lv-card {
    min-width: 0;
    border: 1px solid var(--lv-border);
    border-radius: 1rem;
    background: var(--lv-surface);
    padding: 0.9rem;
}

.lv-card__head {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 0.65rem;
}

.lv-card__head h3 {
    margin: 0;
    font-size: 0.77rem;
    font-weight: 800;
    letter-spacing: 0.02em;
}

.lv-list {
    display: grid;
    gap: 0.55rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.lv-list__line {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.6rem;
    font-size: 0.75rem;
}

.lv-list__line span {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--lv-text);
}

.lv-list__line strong {
    flex: none;
    font-size: 0.72rem;
    font-variant-numeric: tabular-nums;
    color: var(--lv-muted);
}

.lv-bar {
    height: 0.35rem;
    margin-top: 0.25rem;
    overflow: hidden;
    border-radius: 9999px;
    background: var(--lv-track);
}

.lv-bar span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #38bdf8, #c8f169);
}

.lv--sports .lv-bar span {
    background: linear-gradient(90deg, #22d3ee, #c8f169);
}

.lv-overlay:not(.lv--sports) .lv-bar span {
    background: linear-gradient(90deg, #0ea5e9, #0284c7);
}

.lv-card__empty {
    margin: 0;
    font-size: 0.75rem;
    color: var(--lv-muted);
}

.lv-recent {
    margin-top: 1rem;
    border: 1px solid var(--lv-border);
    border-radius: 1rem;
    background: var(--lv-surface);
    overflow: hidden;
}

.lv-recent__head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.6rem;
    border-bottom: 1px solid var(--lv-border);
    padding: 0.9rem;
}

.lv-recent__head h3 {
    margin: 0;
    font-size: 0.88rem;
    font-weight: 800;
}

.lv-recent__head p {
    margin: 0.15rem 0 0;
    font-size: 0.72rem;
    color: var(--lv-muted);
}

.lv-chip {
    border: 1px solid var(--lv-border);
    border-radius: 9999px;
    background: var(--lv-surface-2);
    padding: 0.2rem 0.6rem;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--lv-muted);
}

.lv-table-wrap {
    max-height: 22rem;
    overflow: auto;
}

.lv-table {
    width: 100%;
    min-width: 58rem;
    border-collapse: collapse;
    font-size: 0.75rem;
}

.lv-table th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: var(--lv-surface-2);
    padding: 0.55rem 0.7rem;
    text-align: left;
    font-size: 0.64rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--lv-muted);
}

.lv-table td {
    border-top: 1px solid var(--lv-border);
    padding: 0.55rem 0.7rem;
    vertical-align: top;
    color: var(--lv-text);
}

.lv-table tbody tr:hover {
    background: color-mix(in srgb, var(--lv-surface-2) 70%, transparent);
}

.lv-table td small {
    display: block;
    margin-top: 0.1rem;
    max-width: 14rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.64rem;
    color: var(--lv-muted);
}

.lv-source {
    display: inline-flex;
    border-radius: 9999px;
    background: color-mix(in srgb, var(--lv-accent) 16%, transparent);
    padding: 0.1rem 0.45rem;
    font-weight: 700;
}

.lv-device {
    display: inline-flex;
    border: 1px solid var(--lv-border);
    border-radius: 9999px;
    padding: 0.1rem 0.5rem;
    font-size: 0.68rem;
    font-weight: 700;
}

.lv-visitor {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 0.68rem;
    color: var(--lv-muted) !important;
}

.lv-note {
    margin: 0.9rem 0 0;
    font-size: 0.68rem;
    line-height: 1.55;
    color: var(--lv-muted);
}

.lv-fade-enter-active,
.lv-fade-leave-active {
    transition: opacity 0.18s ease;
}

.lv-fade-enter-active .lv-dialog,
.lv-fade-leave-active .lv-dialog {
    transition: transform 0.2s ease, opacity 0.2s ease;
}

.lv-fade-enter-from,
.lv-fade-leave-to {
    opacity: 0;
}

.lv-fade-enter-from .lv-dialog,
.lv-fade-leave-to .lv-dialog {
    transform: translateY(1rem) scale(0.99);
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .lv-fade-enter-active,
    .lv-fade-leave-active,
    .lv-fade-enter-active .lv-dialog,
    .lv-fade-leave-active .lv-dialog {
        transition: none;
    }

    .lv-spinner {
        animation-duration: 2s;
    }
}
</style>
