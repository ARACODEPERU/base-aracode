<script setup>
    import { ref } from 'vue';

    const startVideo = ref(false);
</script>
<template>
    <!-- From Uiverse.io by ayman-ashine -->
    <div class="vc-card ">
        <img class="vc-image" alt="" src="https://uiverse.io/astronaut.png" />
        <div class="vc-heading">Pronto Clips Educativo</div>
        <div class="vc-icons">
            <a class="vc-instagram" href="#">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                    <path fill="currentColor" d="M267.5 440.6c9.5 7.9 22.8 9.7 34.1 4.4s18.4-16.6 18.4-29l0-320c0-12.4-7.2-23.7-18.4-29s-24.5-3.6-34.1 4.4l-192 160L64 241 64 96c0-17.7-14.3-32-32-32S0 78.3 0 96L0 416c0 17.7 14.3 32 32 32s32-14.3 32-32l0-145 11.5 9.6 192 160z"/>
                </svg>
            </a>
            <a class="vc-x" href="#">
                <svg v-if="startVideo" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                    <path fill="currentColor" d="M48 64C21.5 64 0 85.5 0 112L0 400c0 26.5 21.5 48 48 48l32 0c26.5 0 48-21.5 48-48l0-288c0-26.5-21.5-48-48-48L48 64zm192 0c-26.5 0-48 21.5-48 48l0 288c0 26.5 21.5 48 48 48l32 0c26.5 0 48-21.5 48-48l0-288c0-26.5-21.5-48-48-48l-32 0z"/>
                </svg>
                <svg v-else class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                    <path fill="currentColor" d="M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80L0 432c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z"/>
                </svg>
            </a>
            <a class="vc-discord" href="#">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                    <path fill="currentColor" d="M0 128C0 92.7 28.7 64 64 64H320c35.3 0 64 28.7 64 64V384c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128z"/>
                </svg>
            </a>
            <a class="vc-discord" href="#">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                    <path fill="currentColor" d="M52.5 440.6c-9.5 7.9-22.8 9.7-34.1 4.4S0 428.4 0 416L0 96C0 83.6 7.2 72.3 18.4 67s24.5-3.6 34.1 4.4l192 160L256 241l0-145c0-17.7 14.3-32 32-32s32 14.3 32 32l0 320c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-145-11.5 9.6-192 160z"/>
                </svg>
            </a>
        </div>
    </div>

</template>
<style>
/* From Uiverse.io by ayman-ashine */
    .vc-card {
        --dark: #212121;
        --darker: #111111;
        --semidark: #2c2c2c;
        --lightgray: #e8e8e8;
        --unit: 10px;

        background-color: var(--darker);
        box-shadow: 0 0 var(--unit) var(--darker);
        border: calc(var(--unit) / 2) solid var(--darker);
        border-radius: var(--unit);
        position: relative;
        padding: var(--unit);
        overflow: hidden;
    }

    .vc-card::before {
        content: "";
        position: absolute;
        width: 120%;
        height: 20%;
        top: 40%;
        left: -10%;
        background: linear-gradient(144deg, #af40ff, #5b42f3 50%, #00ddeb);
        animation: keyframes-floating-light 2.5s infinite ease-in-out;
        filter: blur(20px);
    }

    @keyframes keyframes-floating-light {
        0% {
            transform: rotate(-5deg) translateY(-5%);
            opacity: 0.5;
        }

        50% {
            transform: rotate(5deg) translateY(5%);
            opacity: 1;
        }

        100% {
            transform: rotate(-5deg) translateY(-5%);
            opacity: 0.5;
        }
    }

    .vc-card::after {
        content: "";
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0%;
        left: 0%;
        background: linear-gradient(144deg, #af40ff, #5b42f3 50%, #00ddeb);
        filter: blur(20px);
        pointer-events: none;
        animation: keyframes-intro 1s ease-in forwards;
    }

    @keyframes keyframes-intro {
        100% {
            ransform: translate(-100%);
            opacity: 0;
        }
    }

    .vc-card .vc-image {
        margin: auto;
        width: 200px;
        animation: keyframes-floating-img 10s ease-in-out infinite;
    }

    @keyframes keyframes-floating-img {
        0% {
            transform: translate(-2%, 2%) scaleY(0.95) rotate(-5deg);
        }

        50% {
            transform: translate(2%, -2%) scaleY(1) rotate(5deg);
        }

        100% {
            transform: translate(-2%, 2%) scaleY(0.95) rotate(-5deg);
        }
    }

    .vc-card .vc-heading {
        font-weight: 600;
        font-size: small;
        text-align: center;
        margin-top: calc(var(--unit) * -2);
        padding-block: var(--unit);
        color: var(--lightgray);
        animation: keyframes-flash-text 0.5s infinite;
    }

    @keyframes keyframes-flash-text {
        50% {
            opacity: 0.5;
        }
    }

    .vc-card .vc-icons {
        display: flex;
        gap: var(--unit);
    }

    .vc-card .vc-icons a {
        display: flex;
        flex-grow: 1;
        align-items: center;
        justify-content: center;
        background-color: var(--dark);
        color: var(--lightgray);
        padding: calc(var(--unit) / 2);
        border-radius: calc(var(--unit) / 2);
    }

    .vc-card .vc-icons a:hover {
        transition: 0.2s;
        background-color: var(--semidark);
    }

</style>
