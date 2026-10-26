<script setup>
import { computed, ref, watch } from 'vue';

/**
 * Alta y edición de un premiado.
 *
 * El tipo de premio decide si se premia a un equipo (un solo diploma con su
 * plantel) o a un jugador (diploma individual).
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    award: { type: Object, default: null },
    catalog: { type: Object, default: () => ({}) },
    teams: { type: Array, default: () => [] },
    players: { type: Array, default: () => [] },
    preselectedKey: { type: String, default: null },
    preselectedCandidate: { type: Object, default: null },
    saving: { type: Boolean, default: false },
    isNew: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'save']);

const form = ref(emptyForm());

function emptyForm() {
    return {
        award_key: 'champion',
        label: '',
        recipient_type: 'team',
        team_id: null,
        person_id: null,
        place: null,
        meta: null,
        source: 'manual',
        is_published: true,
    };
}

const keyOptions = computed(() => Object.entries(props.catalog).map(([key, value]) => ({ key, ...value })));

const isCustom = computed(() => form.value.award_key === 'custom');

const parseCandidate = (candidate) => {
    if (!candidate) return null;

    return {
        recipient_type: candidate.recipient_type,
        team_id: candidate.team_id || null,
        person_id: candidate.person_id || null,
        place: candidate.place ?? null,
        meta: candidate.meta || null,
        source: 'auto',
    };
};

const reset = () => {
    const award = props.award;

    if (award) {
        form.value = {
            award_key: award.award_key,
            label: award.label || '',
            recipient_type: award.recipient_type || 'player',
            team_id: award.team_id || null,
            person_id: award.person_id || null,
            place: award.place ?? null,
            meta: award.meta || null,
            source: award.source || 'manual',
            is_published: award.is_published !== false,
        };
        return;
    }

    const key = props.preselectedKey || 'champion';
    const preset = props.catalog[key] || {};
    const candidate = parseCandidate(props.preselectedCandidate);

    form.value = {
        ...emptyForm(),
        award_key: key,
        label: preset.label || '',
        recipient_type: preset.recipient_type || 'team',
        source: props.preselectedCandidate ? 'auto' : 'manual',
        ...(candidate || {}),
    };
};

watch(
    () => [props.open, props.award, props.preselectedKey, props.preselectedCandidate],
    () => {
        if (props.open) reset();
    },
    { immediate: true }
);

const onKeyChange = () => {
    const preset = props.catalog[form.value.award_key];

    if (preset) {
        form.value.label = preset.label;
        form.value.recipient_type = preset.recipient_type;
        form.value.place = preset.place ?? null;
        form.value.team_id = null;
        form.value.person_id = null;
    }
};

const onPlayerChange = () => {
    const player = props.players.find((row) => Number(row.person_id) === Number(form.value.person_id));
    form.value.team_id = player?.team_id || null;
};

const submit = () => {
    const payload = { ...form.value };

    payload.award_key = isCustom.value
        ? `custom_${slugify(payload.label || 'premio')}`
        : payload.award_key;

    if (payload.recipient_type === 'team') {
        payload.person_id = null;
    }

    emit('save', payload);
};

function slugify(value) {
    return String(value)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
        .slice(0, 40) || 'premio';
}
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4" @click.self="emit('close')">
        <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900">
                        {{ isNew ? 'Agregar premiado' : 'Editar premiado' }}
                    </h2>
                    <p class="text-sm text-slate-500">El diploma se emite después, desde la lista.</p>
                </div>
                <button type="button" class="rounded-full p-2 text-slate-500 hover:bg-slate-100" @click="emit('close')">✕</button>
            </header>

            <div class="space-y-3 px-5 py-4">
                <label class="award-field">
                    <span>Tipo de premio</span>
                    <select v-model="form.award_key" :disabled="!isNew" @change="onKeyChange">
                        <option v-for="option in keyOptions" :key="option.key" :value="option.key">{{ option.label }}</option>
                        <option value="custom">Premio libre…</option>
                    </select>
                </label>

                <label class="award-field">
                    <span>Nombre visible del premio</span>
                    <input v-model="form.label" type="text" maxlength="120" placeholder="Mejor jugador" />
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <label class="award-field">
                        <span>Premiar a</span>
                        <select v-model="form.recipient_type">
                            <option value="team">Equipo (con plantel)</option>
                            <option value="player">Jugador</option>
                            <option value="person">Persona</option>
                        </select>
                    </label>

                    <label v-if="form.recipient_type === 'team'" class="award-field">
                        <span>Equipo</span>
                        <select v-model.number="form.team_id">
                            <option :value="null">Selecciona…</option>
                            <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                        </select>
                    </label>

                    <label v-else class="award-field">
                        <span>Jugador</span>
                        <select v-model.number="form.person_id" @change="onPlayerChange">
                            <option :value="null">Selecciona…</option>
                            <option v-for="player in players" :key="player.person_id" :value="player.person_id">
                                {{ player.name }}<template v-if="player.team_name"> · {{ player.team_name }}</template>
                            </option>
                        </select>
                    </label>
                </div>

                <label v-if="form.recipient_type === 'team'" class="award-field">
                    <span>Puesto (opcional)</span>
                    <input v-model.number="form.place" type="number" min="1" max="99" placeholder="1" />
                </label>

                <div v-if="form.meta && Object.keys(form.meta).length" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="mb-1 text-xs font-black uppercase tracking-wide text-slate-500">Datos del candidato</p>
                    <div class="flex flex-wrap gap-3 text-xs font-semibold text-slate-700">
                        <span v-for="(value, key) in form.meta" :key="key" v-show="value !== null && value !== ''">
                            {{ key }}: {{ value }}
                        </span>
                    </div>
                </div>
            </div>

            <footer class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                <button type="button" class="award-btn award-btn--ghost" @click="emit('close')">Cancelar</button>
                <button type="button" class="award-btn award-btn--primary" :disabled="saving" @click="submit">
                    {{ saving ? 'Guardando…' : 'Guardar premiado' }}
                </button>
            </footer>
        </div>
    </div>
</template>

<style scoped>
.award-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #475569;
}

.award-field select,
.award-field input {
    border: 1px solid #cbd5e1;
    border-radius: 0.6rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: #0f172a;
    text-transform: none;
    letter-spacing: normal;
}

.award-field select:focus,
.award-field input:focus {
    outline: 2px solid #84cc16;
    outline-offset: 1px;
}

.award-btn {
    display: inline-flex;
    min-height: 2.5rem;
    align-items: center;
    border-radius: 0.7rem;
    padding: 0 1rem;
    font-size: 0.82rem;
    font-weight: 800;
}

.award-btn--primary {
    background: #0b1f2b;
    border: 1px solid #2b4b5d;
    color: #d9f99d;
}

.award-btn--primary:hover:not(:disabled) {
    background: #17394a;
    border-color: #84cc16;
}

.award-btn--ghost {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
}

.award-btn--ghost:hover {
    border-color: #84cc16;
    color: #3f6212;
}
</style>
