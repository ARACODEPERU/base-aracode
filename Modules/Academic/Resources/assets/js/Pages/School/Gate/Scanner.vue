<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';
import Swal2 from 'sweetalert2';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import QrCamera from './Partials/QrCamera.vue';
import GateScanFeed from './Partials/GateScanFeed.vue';
import { useHidScanner } from './composables/useHidScanner';
import { faRightToBracket, faRightFromBracket, faVolumeHigh, faVolumeXmark, faKeyboard, faFileArrowDown } from '@fortawesome/free-solid-svg-icons';

/**
 * Portería: registra la asistencia institucional escaneando el QR del carné.
 *
 * Está pensada para una cola en la puerta, así que no hay ningún paso que
 * confirmar: se lee, se envía y el aviso se disuelve solo. Tres entradas
 * conviven —cámara, pistola lectora y código a mano— y todas terminan en el
 * mismo endpoint.
 */
const props = defineProps({
    schoolName: { type: String, default: '' },
    schoolId: { type: Number, default: null },
    counters: { type: Object, default: () => ({ total: 0, attended: 0, late: 0, exited: 0 }) },
    recent: { type: Array, default: () => [] },
    today: { type: String, default: '' },
    canReport: { type: Boolean, default: false },
});

const mode = ref('in');
const counters = ref({ ...props.counters });
const feed = ref([...props.recent]);
const lastScan = ref(null);
const manualCode = ref('');
const soundOn = ref(true);
const flash = ref('');
const offline = ref(false);

const isEntry = computed(() => mode.value === 'in');
const modeLabel = computed(() => (isEntry.value ? 'Entrada' : 'Salida'));

/**
 * Códigos ya enviados hace poco. La cámara decodifica el mismo QR en varios
 * cuadros seguidos y la pistola puede dispararse dos veces por error: sin esto
 * el endpoint recibiría una ráfaga por cada carné.
 */
const sentCodes = new Map();
const DEDUPE_MS = 5000;
const FEED_LIMIT = 12;

let flashTimer = null;
let audioContext = null;

const normalize = (value) => String(value ?? '').replace(/\s+/g, '').toUpperCase();

const beep = (kind) => {
    if (! soundOn.value) {
        return;
    }

    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;

        if (! AudioContextClass) {
            return;
        }

        audioContext = audioContext ?? new AudioContextClass();

        const oscillator = audioContext.createOscillator();
        const gain = audioContext.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.value = kind === 'ok' ? 880 : kind === 'warn' ? 560 : 300;
        gain.gain.setValueAtTime(0.15, audioContext.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.14);

        oscillator.connect(gain);
        gain.connect(audioContext.destination);
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.15);
    } catch {
        // Sin audio disponible: el aviso visual sigue funcionando.
    }
};

const vibrate = (kind) => {
    try {
        navigator.vibrate?.(kind === 'ok' ? 60 : [50, 40, 50]);
    } catch {
        // Algunos navegadores no permiten vibrar: no es crítico.
    }
};

const showFlash = (kind) => {
    flash.value = kind;
    clearTimeout(flashTimer);
    flashTimer = setTimeout(() => (flash.value = ''), 250);
};

/**
 * Aviso sin botón: aparece, se disuelve solo y deja pasar la siguiente lectura.
 */
const toast = (icon, title, text) => {
    Swal2.fire({
        toast: true,
        position: 'top-end',
        icon,
        title,
        text,
        showConfirmButton: false,
        timer: icon === 'error' ? 1900 : 1200,
        timerProgressBar: true,
        didOpen: (element) => {
            element.addEventListener('mouseenter', Swal2.stopTimer);
            element.addEventListener('mouseleave', Swal2.resumeTimer);
        },
    });
};

const pushFeed = (row) => {
    feed.value = [row, ...feed.value].slice(0, FEED_LIMIT);
};

const rowFromResponse = (response) => ({
    id: `local-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
    student: response.student ?? 'N/D',
    code: response.code,
    section: response.section,
    entry_time: response.mode === 'in' ? response.time : null,
    exit_time: response.mode === 'out' ? response.time : null,
    status: response.status,
    status_label: response.status_label,
    early_exit: !!response.early_exit,
    inside: response.mode === 'in',
});

const handleSuccess = (response, icon) => {
    if (response.counters) {
        counters.value = response.counters;
    }

    if (response.result !== 'not_found' && response.result !== 'no_enrollment') {
        pushFeed(rowFromResponse(response));
    }

    lastScan.value = {
        student: response.student,
        section: response.section,
        code: response.code,
        time: response.time,
        status_label: response.status_label,
        mode: response.mode,
        result: response.result,
    };

    const detail = [response.student, response.section, response.time].filter(Boolean).join(' · ');

    if (response.result === 'duplicate') {
        toast(icon, 'Ya registrado', response.message);
        showFlash('warn');
        beep('warn');
        vibrate('warn');

        return;
    }

    toast(icon, isEntry.value ? 'Entrada registrada' : 'Salida registrada', detail || response.message);
    showFlash('ok');
    beep('ok');
    vibrate('ok');
};

const handleFailure = (response) => {
    const messages = {
        not_found: { title: 'Código no encontrado', text: 'Revise que sea el carné de este colegio.' },
        no_enrollment: { title: 'Sin matrícula activa', text: response.message },
        error: { title: 'No se pudo registrar', text: response.message },
    };

    const message = messages[response.result] ?? messages.error;

    lastScan.value = {
        student: response.student ?? 'Código no reconocido',
        section: response.section,
        code: response.code,
        time: null,
        status_label: null,
        mode: null,
        result: response.result,
    };

    toast('error', message.title, message.text);
    showFlash('error');
    beep('error');
    vibrate('error');
};

/**
 * Envía un código leído. Un reintento automático cubre el hipo de red de la
 * puerta; si vuelve a fallar, se avisa en gris y se libera el código para que
 * se pueda escanear otra vez.
 */
const sendCode = async (rawCode, attempt = 0) => {
    const code = normalize(rawCode);

    if (code.length < 3) {
        return;
    }

    const lastSent = sentCodes.get(code);

    if (lastSent && Date.now() - lastSent < DEDUPE_MS) {
        return;
    }

    sentCodes.set(code, Date.now());

    try {
        const { data } = await axios.post(route('aca_school_gate_scan'), { code, mode: mode.value });

        offline.value = false;

        if (data.ok) {
            handleSuccess(data, data.result === 'duplicate' ? 'warning' : 'success');
        } else {
            handleFailure(data);
        }
    } catch (error) {
        if (attempt === 0) {
            // Un solo reintento inmediato: en la puerta la red a veces titubea.
            return sendCode(code, 1);
        }

        sentCodes.delete(code);
        offline.value = true;
        lastScan.value = null;
        toast('error', 'Sin conexión', 'No se pudo registrar. Vuelva a escanear el carné.');
        showFlash('error');
        beep('error');
    }
};

const submitManualCode = () => {
    const code = manualCode.value;

    manualCode.value = '';
    sendCode(code);
};

const toggleMode = (value) => {
    mode.value = value;
    sentCodes.clear();
};

const onShortcut = (event) => {
    if (event.key === 'F2') {
        event.preventDefault();
        toggleMode(isEntry.value ? 'out' : 'in');
    }
};

// La pistola lectora escribe el código y cierra con Enter: el composable arma
// el buffer sin exigir que ningún campo tenga el foco.
const { buffer: hidBuffer } = useHidScanner((code) => sendCode(code));

onMounted(() => document.addEventListener('keydown', onShortcut));
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onShortcut);
    clearTimeout(flashTimer);
    audioContext?.close?.();
});
</script>

<template>
    <AppLayout title="Portería">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[{ title: 'Colegio' }, { title: 'Portería' }]" />

        <!-- Flash a pantalla completa: el color se ve desde lejos aunque el toast no. -->
        <div
            v-if="flash"
            class="fixed inset-0 z-[60] pointer-events-none transition-opacity duration-200"
            :class="flash === 'ok' ? 'bg-success/25' : flash === 'warn' ? 'bg-warning/25' : 'bg-danger/25'"
        ></div>

        <div class="panel mt-5">
            <div class="p-5 border-b border-[#ebedf2] dark:border-[#191e3a] flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold">Portería · Asistencia de la I.E.</h3>
                    <p class="text-sm text-white-dark mt-1">
                        {{ schoolName || 'Colegio' }}<span v-if="today"> · {{ today }}</span>
                    </p>
                </div>

                <!-- Conmutador Entrada/Salida: la entrada es el modo por defecto. -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="btn"
                        :class="isEntry ? 'btn-success' : 'btn-outline-success'"
                        @click="toggleMode('in')"
                    >
                        <font-awesome-icon :icon="faRightToBracket" class="mr-1" /> Entrada
                    </button>
                    <button
                        type="button"
                        class="btn"
                        :class="! isEntry ? 'btn-info' : 'btn-outline-info'"
                        @click="toggleMode('out')"
                    >
                        <font-awesome-icon :icon="faRightFromBracket" class="mr-1" /> Salida
                    </button>
                </div>
            </div>

            <div class="p-5 grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 space-y-4">
                    <div
                        class="rounded-md p-3 flex items-center gap-3"
                        :class="isEntry ? 'bg-success/10' : 'bg-info/10'"
                    >
                        <span class="text-lg font-semibold">Modo: {{ modeLabel }}</span>
                        <span class="text-xs text-white-dark">
                            Escanee el carné o dispare la pistola. F2 cambia de modo.
                        </span>
                    </div>

                    <QrCamera @detected="sendCode" />

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <font-awesome-icon :icon="faKeyboard" class="text-white-dark" />
                            <input
                                v-model="manualCode"
                                type="text"
                                class="form-input w-48"
                                placeholder="Código a mano"
                                autocomplete="off"
                                @keyup.enter="submitManualCode"
                            />
                            <button type="button" class="btn btn-outline-primary" @click="submitManualCode">
                                Registrar
                            </button>
                        </div>

                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="soundOn" type="checkbox" class="form-checkbox" />
                            <font-awesome-icon :icon="soundOn ? faVolumeHigh : faVolumeXmark" />
                            Sonido
                        </label>

                        <a
                            v-if="canReport"
                            :href="route('aca_school_gate_report')"
                            target="_blank"
                            class="btn btn-outline-secondary ml-auto"
                        >
                            <font-awesome-icon :icon="faFileArrowDown" class="mr-1" />
                            Reporte del día
                        </a>
                    </div>

                    <p v-if="hidBuffer" class="text-xs text-white-dark">
                        Pistola leyendo: <span class="font-mono">{{ hidBuffer }}</span>
                    </p>

                    <p v-if="offline" class="text-danger text-sm">
                        Se perdió la conexión con el servidor. Los escaneos se reintentan solos.
                    </p>
                </div>

                <div class="lg:col-span-1 space-y-4">
                    <!-- Último escaneado en grande: lo confirman el operador y el alumno. -->
                    <div
                        class="rounded-md p-4 border"
                        :class="lastScan && lastScan.result === 'ok'
                            ? 'border-success bg-success/5'
                            : lastScan && lastScan.result === 'duplicate'
                                ? 'border-warning bg-warning/5'
                                : lastScan
                                    ? 'border-danger bg-danger/5'
                                    : 'border-[#ebedf2] dark:border-[#1b2e4b]'"
                    >
                        <p class="text-xs text-white-dark">Último escaneado</p>

                        <template v-if="lastScan">
                            <p class="text-xl font-semibold mt-1">{{ lastScan.student }}</p>
                            <p class="text-sm text-white-dark">{{ lastScan.section || '—' }}</p>
                            <p class="text-sm mt-2">
                                <span v-if="lastScan.status_label" class="badge bg-primary mr-2">{{ lastScan.status_label }}</span>
                                <span v-if="lastScan.time">{{ lastScan.time }}</span>
                            </p>
                        </template>

                        <p v-else class="text-sm text-white-dark mt-2">
                            Todavía no se ha escaneado ningún carné en esta sesión.
                        </p>
                    </div>

                    <GateScanFeed :counters="counters" :rows="feed" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
