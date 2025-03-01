<script setup>
    defineProps({
        texto:{
            type: String,
            default: 'Text writing'
        }
    });
</script>

<template>
    <!-- From Uiverse.io by JkHuger -->
    <div class="aranimation" style="padding: 0px; margin: 0px">{{ texto }}</div>
</template>

<style>
    /* From Uiverse.io by JkHuger */
    @keyframes typing {
        from {
            width: 0;
        }
        to {
            width: 13.20ch; /* Asegúrate de que este valor coincida con la longitud del texto */
        }
    }

    @keyframes blink-caret {
        50% {
            border-color: transparent;
        }
    }

    .aranimation {
        font: bold 100% Consolas, Monaco, monospace;
        border-right: .1em solid black;
        width: 13.20ch; /* Ajusta este valor según la longitud del texto */
        white-space: nowrap;
        overflow: hidden;
        animation: typing 5s steps(13, end), /* Efecto de escritura */
                   blink-caret .5s step-end infinite,
                   erasing 0.05s steps(13, end) 0.05s; /* Parpadeo del cursor */
        animation-iteration-count: infinite; /* Repetir en bucle infinito */
    }
</style>
