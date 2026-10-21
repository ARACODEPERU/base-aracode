<script setup>
    import { computed, onMounted, onUnmounted, ref } from 'vue';

    const props = defineProps({
        // Espera maxima configurada en el parametro IA-0000 (minutos).
        maxWaitMinutes: {
            type: Number,
            default: 6,
        },
        // Texto principal del aviso. Se puede cambiar desde el modal.
        message: {
            type: String,
            default: 'La consulta a un Agente puede tomar 5 minutos aproximadamente.',
        },
    });

    // Cronometro visible: el agente trabaja de forma asincrona y el usuario
    // necesita saber que el sistema sigue esperando (no se colgo).
    const elapsedSeconds = ref(0);
    let timer = null;

    onMounted(() => {
        timer = window.setInterval(() => {
            elapsedSeconds.value += 1;
        }, 1000);
    });

    onUnmounted(() => {
        if (timer) window.clearInterval(timer);
    });

    const elapsedLabel = computed(() => {
        const minutes = Math.floor(elapsedSeconds.value / 60);
        const seconds = elapsedSeconds.value % 60;

        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    });

    // Paquetes de datos que recorren las pistas del circuito.
    const packets = [0, 1, 2, 3];
</script>

<template>
    <div class="ac-loader">
        <div class="ac-loader__stage">
            <span class="ac-loader__scan"></span>

            <!-- Pistas del circuito impreso -->
            <svg class="ac-loader__board" viewBox="0 0 340 180" fill="none" aria-hidden="true">
                <path class="ac-loader__trace" d="M8 34 H64 L84 54 H128" />
                <path class="ac-loader__trace" d="M332 34 H276 L256 54 H212" />
                <path class="ac-loader__trace" d="M8 146 H70 L92 124 H140" />
                <path class="ac-loader__trace" d="M332 146 H270 L248 124 H200" />
                <path class="ac-loader__trace ac-loader__trace--dim" d="M18 62 H54 L72 80 H104" />
                <path class="ac-loader__trace ac-loader__trace--dim" d="M322 62 H286 L268 80 H236" />
                <path class="ac-loader__trace ac-loader__trace--dim" d="M18 118 H58 L78 100 H108" />
                <path class="ac-loader__trace ac-loader__trace--dim" d="M322 118 H282 L262 100 H232" />

                <circle class="ac-loader__node" cx="8" cy="34" r="3" />
                <circle class="ac-loader__node" cx="332" cy="34" r="3" />
                <circle class="ac-loader__node" cx="8" cy="146" r="3" />
                <circle class="ac-loader__node" cx="332" cy="146" r="3" />
            </svg>

            <!-- Pieza electronica que corre de un lado a otro por la pista superior -->
            <div class="ac-loader__rail ac-loader__rail--top">
                <span
                    v-for="(packet, index) in packets"
                    :key="'top-' + index"
                    class="ac-loader__packet"
                    :style="{ animationDelay: `${index * 0.9}s`, animationDuration: `${3.4 + index * 0.35}s` }"
                ></span>
            </div>

            <!-- Pieza que corre en sentido contrario por la pista inferior -->
            <div class="ac-loader__rail ac-loader__rail--bottom">
                <span
                    v-for="(packet, index) in packets"
                    :key="'bottom-' + index"
                    class="ac-loader__packet ac-loader__packet--reverse"
                    :style="{ animationDelay: `${index * 1.1}s`, animationDuration: `${3.8 + index * 0.3}s` }"
                ></span>
            </div>

            <!-- Microchips que se deslizan y regresan -->
            <div class="ac-loader__drift ac-loader__drift--one" aria-hidden="true">
                <span class="ac-loader__chip"><i></i><i></i><i></i></span>
            </div>
            <div class="ac-loader__drift ac-loader__drift--two" aria-hidden="true">
                <span class="ac-loader__chip ac-loader__chip--small"><i></i><i></i></span>
            </div>
            <div class="ac-loader__drift ac-loader__drift--three" aria-hidden="true">
                <span class="ac-loader__chip ac-loader__chip--small"><i></i><i></i></span>
            </div>

            <!-- Anillo orbital con marcas de placa -->
            <span class="ac-loader__orbit" aria-hidden="true"></span>
            <span class="ac-loader__orbit ac-loader__orbit--inner" aria-hidden="true"></span>

            <!-- CPU central: nucleo latiendo y pines encendidos -->
            <div class="ac-loader__cpu" aria-hidden="true">
                <span class="ac-loader__pin ac-loader__pin--n1"></span>
                <span class="ac-loader__pin ac-loader__pin--n2"></span>
                <span class="ac-loader__pin ac-loader__pin--n3"></span>
                <span class="ac-loader__pin ac-loader__pin--n4"></span>
                <span class="ac-loader__pin ac-loader__pin--n5"></span>
                <span class="ac-loader__pin ac-loader__pin--n6"></span>
                <div class="ac-loader__cpu-body">
                    <span class="ac-loader__cpu-core"></span>
                    <span class="ac-loader__cpu-label">AI</span>
                </div>
            </div>

            <!-- Destellos de actividad -->
            <span class="ac-loader__spark ac-loader__spark--a" aria-hidden="true"></span>
            <span class="ac-loader__spark ac-loader__spark--b" aria-hidden="true"></span>
            <span class="ac-loader__spark ac-loader__spark--c" aria-hidden="true"></span>
        </div>

        <p class="ac-loader__message" role="status" aria-live="polite">{{ message }}</p>

        <!-- El cronometro es informativo: no debe interrumpir al lector de pantalla. -->
        <div class="ac-loader__meta" aria-hidden="true">
            <span class="ac-loader__led"></span>
            <span>Procesando con el Agente</span>
            <span class="ac-loader__time">{{ elapsedLabel }}</span>
            <span class="ac-loader__limit">máx. {{ maxWaitMinutes }} min</span>
        </div>

        <div class="ac-loader__bar" aria-hidden="true">
            <span class="ac-loader__bar-fill"></span>
        </div>
    </div>
</template>

<style scoped>
    .ac-loader {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.85rem;
        width: min(100%, 380px);
        padding: 1rem 1.1rem 1.15rem;
        border-radius: 1.1rem;
        background: radial-gradient(circle at 50% 40%, #10233f 0%, #070f1e 62%, #04070e 100%);
        border: 1px solid rgba(56, 189, 248, 0.28);
        box-shadow:
            0 0 0 1px rgba(14, 165, 233, 0.08),
            0 18px 45px rgba(7, 15, 30, 0.55),
            inset 0 0 35px rgba(14, 165, 233, 0.08);
        color: #e2f4ff;
        font-family: inherit;
    }

    .ac-loader__stage {
        position: relative;
        width: 100%;
        height: 148px;
        overflow: hidden;
        border-radius: 0.85rem;
        background-image:
            linear-gradient(rgba(56, 189, 248, 0.07) 1px, transparent 1px),
            linear-gradient(90deg, rgba(56, 189, 248, 0.07) 1px, transparent 1px);
        background-size: 21px 21px;
        background-position: center;
    }

    .ac-loader__stage::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 50% 50%, rgba(14, 165, 233, 0.16), transparent 62%);
        animation: ac-breathe 3.2s ease-in-out infinite;
    }

    /* Barrido horizontal tipo escaner */
    .ac-loader__scan {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 28%;
        background: linear-gradient(90deg, transparent, rgba(125, 211, 252, 0.16), transparent);
        filter: blur(2px);
        animation: ac-scan 4.6s linear infinite;
    }

    .ac-loader__board {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }

    .ac-loader__trace {
        stroke: #22d3ee;
        stroke-width: 1.6;
        stroke-linecap: round;
        stroke-dasharray: 14 108;
        opacity: 0.75;
        animation: ac-trace 2.8s linear infinite;
        filter: drop-shadow(0 0 4px rgba(34, 211, 238, 0.6));
    }

    .ac-loader__trace--dim {
        stroke: #1e5f8a;
        opacity: 0.55;
        animation-duration: 4.4s;
        filter: none;
    }

    .ac-loader__node {
        fill: #7dd3fc;
        opacity: 0.9;
        animation: ac-node 1.9s ease-in-out infinite;
    }

    .ac-loader__rail {
        position: absolute;
        left: 9%;
        right: 9%;
        height: 2px;
        border-radius: 999px;
        background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.45), transparent);
    }

    .ac-loader__rail--top {
        top: 31px;
    }

    .ac-loader__rail--bottom {
        bottom: 29px;
    }

    .ac-loader__packet {
        position: absolute;
        top: 50%;
        left: 0;
        width: 22px;
        height: 8px;
        margin-top: -4px;
        border-radius: 2px;
        background: linear-gradient(90deg, #0369a1, #38bdf8 45%, #e0f2fe);
        box-shadow: 0 0 10px rgba(56, 189, 248, 0.85);
        animation-name: ac-packet;
        animation-timing-function: cubic-bezier(0.45, 0.05, 0.55, 0.95);
        animation-iteration-count: infinite;
    }

    .ac-loader__packet::after {
        content: '';
        position: absolute;
        inset: 2px 3px;
        border-radius: 1px;
        background: repeating-linear-gradient(90deg, rgba(7, 15, 30, 0.85) 0 2px, transparent 2px 4px);
    }

    .ac-loader__packet--reverse {
        animation-direction: reverse;
        background: linear-gradient(90deg, #e0f2fe, #22d3ee 55%, #0e7490);
    }

    .ac-loader__drift {
        position: absolute;
        animation: ac-drift 5.2s ease-in-out infinite alternate;
    }

    .ac-loader__drift--one {
        top: 56px;
        left: 13%;
    }

    .ac-loader__drift--two {
        top: 92px;
        right: 15%;
        animation-delay: -1.6s;
        animation-duration: 4.4s;
    }

    .ac-loader__drift--three {
        bottom: 16px;
        left: 22%;
        animation-delay: -2.7s;
        animation-duration: 6s;
    }

    .ac-loader__chip {
        display: flex;
        gap: 2px;
        padding: 3px 4px;
        border-radius: 3px;
        background: linear-gradient(145deg, #0f2e4a, #07182b);
        border: 1px solid rgba(56, 189, 248, 0.55);
        box-shadow: 0 0 12px rgba(56, 189, 248, 0.35);
    }

    .ac-loader__chip i {
        display: block;
        width: 4px;
        height: 9px;
        border-radius: 1px;
        background: #7dd3fc;
        opacity: 0.85;
        animation: ac-chip-blink 1.4s ease-in-out infinite;
    }

    .ac-loader__chip i:nth-child(2) {
        animation-delay: 0.25s;
    }

    .ac-loader__chip i:nth-child(3) {
        animation-delay: 0.5s;
    }

    .ac-loader__chip--small {
        padding: 3px;
    }

    .ac-loader__chip--small i {
        height: 7px;
        background: #22d3ee;
    }

    .ac-loader__orbit {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 118px;
        height: 118px;
        margin: -59px 0 0 -59px;
        border-radius: 50%;
        border: 1px dashed rgba(56, 189, 248, 0.4);
        animation: ac-orbit 9s linear infinite;
    }

    .ac-loader__orbit--inner {
        width: 92px;
        height: 92px;
        margin: -46px 0 0 -46px;
        border-style: dotted;
        border-color: rgba(125, 211, 252, 0.45);
        animation-direction: reverse;
        animation-duration: 6s;
    }

    .ac-loader__cpu {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 74px;
        height: 74px;
        transform: translate(-50%, -50%);
        animation: ac-float 3.4s ease-in-out infinite;
    }

    .ac-loader__cpu-body {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        border-radius: 9px;
        background: linear-gradient(150deg, #0b2740, #05101f 70%);
        border: 1px solid rgba(56, 189, 248, 0.7);
        box-shadow:
            0 0 18px rgba(34, 211, 238, 0.45),
            inset 0 0 18px rgba(34, 211, 238, 0.2);
    }

    .ac-loader__cpu-body::before {
        content: '';
        position: absolute;
        inset: 8px;
        border-radius: 5px;
        border: 1px solid rgba(125, 211, 252, 0.35);
    }

    .ac-loader__cpu-core {
        width: 26px;
        height: 26px;
        border-radius: 5px;
        background: radial-gradient(circle, #e0f2fe 0%, #22d3ee 45%, #0369a1 100%);
        box-shadow: 0 0 22px rgba(34, 211, 238, 0.9);
        animation: ac-core 1.5s ease-in-out infinite;
    }

    .ac-loader__cpu-label {
        position: absolute;
        bottom: 4px;
        right: 6px;
        font-size: 7px;
        font-weight: 700;
        letter-spacing: 0.14em;
        color: #7dd3fc;
        opacity: 0.85;
    }

    /* Pines encendidos alrededor del procesador */
    .ac-loader__pin {
        position: absolute;
        background: #38bdf8;
        border-radius: 1px;
        box-shadow: 0 0 7px rgba(56, 189, 248, 0.9);
        animation: ac-pin 1.8s ease-in-out infinite;
    }

    .ac-loader__pin--n1 { top: -5px; left: 14px; width: 6px; height: 4px; }
    .ac-loader__pin--n2 { top: -5px; right: 14px; width: 6px; height: 4px; animation-delay: 0.3s; }
    .ac-loader__pin--n3 { bottom: -5px; left: 14px; width: 6px; height: 4px; animation-delay: 0.6s; }
    .ac-loader__pin--n4 { bottom: -5px; right: 14px; width: 6px; height: 4px; animation-delay: 0.9s; }
    .ac-loader__pin--n5 { top: 14px; left: -5px; width: 4px; height: 6px; animation-delay: 0.45s; }
    .ac-loader__pin--n6 { bottom: 14px; right: -5px; width: 4px; height: 6px; animation-delay: 0.75s; }

    .ac-loader__spark {
        position: absolute;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: #bae6fd;
        box-shadow: 0 0 12px rgba(186, 230, 253, 0.95);
        animation: ac-spark 2.6s ease-in-out infinite;
    }

    .ac-loader__spark--a { top: 24%; left: 18%; }
    .ac-loader__spark--b { top: 68%; right: 16%; animation-delay: 0.8s; }
    .ac-loader__spark--c { bottom: 18%; left: 41%; animation-delay: 1.5s; }

    .ac-loader__message {
        margin: 0;
        text-align: center;
        font-size: 0.9rem;
        font-weight: 600;
        line-height: 1.35;
        color: #e0f2fe;
    }

    .ac-loader__meta {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.4rem;
        font-size: 0.68rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #7dd3fc;
    }

    .ac-loader__led {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #4ade80;
        box-shadow: 0 0 10px rgba(74, 222, 128, 0.9);
        animation: ac-led 1.3s ease-in-out infinite;
    }

    .ac-loader__time {
        padding: 0.05rem 0.4rem;
        border-radius: 999px;
        background: rgba(56, 189, 248, 0.14);
        border: 1px solid rgba(56, 189, 248, 0.3);
        font-variant-numeric: tabular-nums;
        color: #bae6fd;
    }

    .ac-loader__limit {
        color: #64748b;
    }

    .ac-loader__bar {
        position: relative;
        width: 78%;
        height: 4px;
        overflow: hidden;
        border-radius: 999px;
        background: rgba(56, 189, 248, 0.12);
    }

    .ac-loader__bar-fill {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 38%;
        border-radius: 999px;
        background: linear-gradient(90deg, transparent, #22d3ee, #bae6fd, transparent);
        animation: ac-bar 2.4s ease-in-out infinite;
    }

    @keyframes ac-breathe {
        0%, 100% { opacity: 0.55; }
        50% { opacity: 1; }
    }

    @keyframes ac-scan {
        0% { transform: translateX(-120%); }
        100% { transform: translateX(460%); }
    }

    @keyframes ac-trace {
        from { stroke-dashoffset: 122; }
        to { stroke-dashoffset: 0; }
    }

    @keyframes ac-node {
        0%, 100% { opacity: 0.35; }
        50% { opacity: 1; }
    }

    @keyframes ac-packet {
        0% { left: -12%; opacity: 0; }
        12% { opacity: 1; }
        88% { opacity: 1; }
        100% { left: 100%; opacity: 0; }
    }

    @keyframes ac-drift {
        0% { transform: translateX(-34px) rotate(-3deg); }
        100% { transform: translateX(42px) rotate(3deg); }
    }

    @keyframes ac-chip-blink {
        0%, 100% { opacity: 0.35; }
        50% { opacity: 1; }
    }

    @keyframes ac-orbit {
        to { transform: rotate(360deg); }
    }

    @keyframes ac-float {
        0%, 100% { transform: translate(-50%, -50%); }
        50% { transform: translate(-50%, -54%); }
    }

    @keyframes ac-core {
        0%, 100% { transform: scale(0.86); opacity: 0.8; }
        50% { transform: scale(1.08); opacity: 1; }
    }

    @keyframes ac-pin {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 1; }
    }

    @keyframes ac-spark {
        0%, 100% { transform: scale(0.5); opacity: 0.2; }
        50% { transform: scale(1.5); opacity: 1; }
    }

    @keyframes ac-led {
        0%, 100% { opacity: 0.35; }
        50% { opacity: 1; }
    }

    @keyframes ac-bar {
        0% { transform: translateX(-110%); }
        100% { transform: translateX(280%); }
    }

    @media (prefers-reduced-motion: reduce) {
        .ac-loader__scan,
        .ac-loader__packet,
        .ac-loader__drift,
        .ac-loader__orbit,
        .ac-loader__cpu,
        .ac-loader__trace,
        .ac-loader__spark,
        .ac-loader__bar-fill,
        .ac-loader__cpu-core,
        .ac-loader__led {
            animation: none !important;
        }
    }
</style>
