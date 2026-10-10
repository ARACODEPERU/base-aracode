<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import jsQR from 'jsqr';

/**
 * Lee el QR del carné con la cámara del dispositivo (webcam integrada, USB o la
 * trasera del celular/tablet).
 *
 * Se decodifica sobre un canvas reducido a ~320 px de ancho y a ~10 cuadros por
 * segundo: alcanza para leer un QR de carné a la distancia de la mano y deja la
 * CPU libre. Después de cada acierto la lectura se pausa unos milisegundos: si
 * no, el mismo QR se decodifica en diez cuadros seguidos y se registraría diez
 * veces.
 *
 * Usa BarcodeDetector cuando el navegador lo trae (Chrome/Android, que es más
 * rápido) y jsQR siempre como respaldo: Safari de iOS y Firefox no lo tienen.
 */
const props = defineProps({
    // Cuánto tiempo se ignora la cámara después de una lectura. Sin esta pausa
    // el mismo QR se decodifica en varios cuadros seguidos.
    pauseMs: { type: Number, default: 900 },
    // Lado del recuadro de lectura.
    width: { type: Number, default: 320 },
});

const emit = defineEmits(['detected']);

const video = ref(null);
const running = ref(false);
const starting = ref(false);
const errorMessage = ref('');
const cameras = ref([]);
const deviceId = ref('');
const lastCode = ref('');

let stream = null;
let timer = null;
let detector = null;
let busy = false;
let pausedUntil = 0;
let stopped = false;

const isSecure = typeof window !== 'undefined' && window.isSecureContext;
const hasMediaDevices = typeof navigator !== 'undefined' && !!navigator.mediaDevices?.getUserMedia;

const ctaLabel = computed(() => (running.value ? 'Cámara encendida' : 'Activar cámara'));

const describeError = (error) => {
    if (! error) {
        return 'No se pudo iniciar la cámara.';
    }

    if (error.name === 'NotAllowedError' || error.name === 'SecurityError') {
        return 'Permiso de cámara denegado. Habilítalo en el navegador para leer los carnés.';
    }

    if (error.name === 'NotFoundError' || error.name === 'OverconstrainedError') {
        return 'No se encontró ninguna cámara conectada a este equipo.';
    }

    if (error.name === 'NotReadableError') {
        return 'La cámara está siendo usada por otro programa.';
    }

    return error.message || 'No se pudo iniciar la cámara.';
};

const handleCode = (raw) => {
    const code = String(raw ?? '').replace(/\s+/g, '').trim();

    if (code.length < 3) {
        return;
    }

    // Sigue en pausa por la lectura anterior.
    if (Date.now() < pausedUntil) {
        return;
    }

    // La pausa posterior al acierto evita que el mismo QR se envíe muchas veces.
    pausedUntil = Date.now() + props.pauseMs;
    lastCode.value = code;
    emit('detected', code);
};

const scanFrame = async () => {
    if (busy || stopped) {
        return;
    }

    const element = video.value;

    if (! element || element.readyState < 2 || ! element.videoWidth) {
        return;
    }

    busy = true;

    try {
        const width = props.width;
        const height = Math.max(1, Math.round(width * (element.videoHeight / element.videoWidth)));
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const context = canvas.getContext('2d', { willReadFrequently: true });
        context.drawImage(element, 0, 0, width, height);

        let code = null;

        if (detector) {
            try {
                const results = await detector.detect(canvas);
                code = results?.[0]?.rawValue ?? null;
            } catch {
                // El detector nativo falló en este equipo: jsQR se hace cargo.
                detector = null;
            }
        }

        if (! code) {
            const image = context.getImageData(0, 0, width, height);
            const found = jsQR(image.data, width, height, { inversionAttempts: 'dontInvert' });
            code = found?.data ?? null;
        }

        if (code) {
            handleCode(code);
        }
    } catch {
        // Un cuadro ilegible no es un error: se sigue con el próximo.
    } finally {
        busy = false;
    }
};

const buildDetector = () => {
    if (detector || typeof window === 'undefined' || ! ('BarcodeDetector' in window)) {
        return;
    }

    try {
        detector = new window.BarcodeDetector({ formats: ['qr_code'] });
    } catch {
        detector = null;
    }
};

const listCameras = async () => {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        cameras.value = devices.filter((device) => device.kind === 'videoinput');
    } catch {
        cameras.value = [];
    }
};

const startCamera = async (wantedDeviceId = '') => {
    if (! hasMediaDevices) {
        errorMessage.value = 'Este navegador no permite usar la cámara.';

        return;
    }

    starting.value = true;
    errorMessage.value = '';
    stopped = false;

    try {
        stopStream();

        const constraints = {
            audio: false,
            video: wantedDeviceId
                ? { deviceId: { exact: wantedDeviceId }, width: { ideal: 1280 }, height: { ideal: 720 } }
                : { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
        };

        stream = await navigator.mediaDevices.getUserMedia(constraints);

        if (video.value) {
            video.value.srcObject = stream;
            await video.value.play();
        }

        running.value = true;
        pausedUntil = 0;
        lastCode.value = '';
        buildDetector();
        await listCameras();

        timer = window.setInterval(scanFrame, 100);
    } catch (error) {
        running.value = false;
        errorMessage.value = describeError(error);
    } finally {
        starting.value = false;
    }
};

const stopStream = () => {
    if (timer) {
        window.clearInterval(timer);
        timer = null;
    }

    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
    }

    if (video.value) {
        video.value.srcObject = null;
    }

    running.value = false;
};

const stopCamera = () => {
    stopped = true;
    stopStream();
};

const switchCamera = async (id) => {
    deviceId.value = id;
    await startCamera(id);
};

onBeforeUnmount(stopCamera);

defineExpose({ startCamera, stopCamera, switchCamera });
</script>

<template>
    <div class="gate-camera">
        <div class="relative rounded-md overflow-hidden bg-black aspect-video flex items-center justify-center">
            <video
                ref="video"
                class="w-full h-full object-cover"
                :class="running ? '' : 'hidden'"
                playsinline
                muted
            ></video>

            <div v-if="! running" class="text-center px-6 py-10">
                <p class="text-white text-sm">
                    <template v-if="! isSecure">
                        La cámara necesita una conexión segura (HTTPS). Abra esta pantalla por HTTPS o desde localhost.
                    </template>
                    <template v-else-if="! hasMediaDevices">
                        Este navegador no permite usar la cámara.
                    </template>
                    <template v-else>
                        Active la cámara para leer los carnés.
                    </template>
                </p>
            </div>

            <!-- Guía de encuadre: ayuda a apuntar sin pensarlo. -->
            <div
                v-if="running"
                class="absolute inset-0 flex items-center justify-center pointer-events-none"
            >
                <div class="border-2 border-white/70 rounded-lg w-40 h-40 sm:w-52 sm:h-52"></div>
            </div>
        </div>

        <p v-if="errorMessage" class="text-danger text-xs mt-2">{{ errorMessage }}</p>

        <div class="flex flex-wrap items-center gap-2 mt-3">
            <button
                type="button"
                class="btn btn-primary"
                :disabled="starting || running || ! hasMediaDevices || ! isSecure"
                @click="startCamera(deviceId)"
            >
                {{ starting ? 'Encendiendo…' : ctaLabel }}
            </button>

            <button v-if="running" type="button" class="btn btn-outline-danger" @click="stopCamera">
                Apagar
            </button>

            <select
                v-if="cameras.length > 1"
                :value="deviceId"
                class="form-select w-auto"
                @change="switchCamera($event.target.value)"
            >
                <option value="">Cámara automática</option>
                <option v-for="(camera, index) in cameras" :key="camera.deviceId" :value="camera.deviceId">
                    {{ camera.label || `Cámara ${index + 1}` }}
                </option>
            </select>
        </div>
    </div>
</template>
