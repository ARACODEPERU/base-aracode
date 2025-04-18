<script setup>
    import { ref, reactive, onMounted } from 'vue'
    import axios from 'axios'

    const startVideo = ref(false)
    const vcLoading = ref(false)
    const videos = ref([])

    // Video actual (objeto reactivo)
    const video = reactive({
        title: null,
        iframe: null,
        type: null
    })

    // Índice del video actual
    const currentIndex = ref(0)
    const iframeLoaded = ref(false)

    // Función para actualizar el video actual según el índice
    const updateVideo = (index) => {
        if (videos.value.length > 0) {
            const selected = videos.value[index]
            iframeLoaded.value = false // Reiniciamos el estado de carga
            video.title = selected.title;
            video.iframe = selected.video;
            video.type = selected.link
        }
    }

    const newHeight = ref(210);

    const modifiedContent = (content) => {
        if (!content) return ''
        let modifiedContent = content
        modifiedContent = modifiedContent.replace(/height="\d+"/g, `height="${newHeight.value}"`)
        modifiedContent = modifiedContent.replace(/width="\d+"/g, `width="100%"`)
        return modifiedContent
    }

    // Carga de videos y asignación inicial (usando localStorage)
    const getVideos = () => {
        vcLoading.value = true
        axios.post(route('aca_tutorials_video_todos_estudiante')).then((res) => res.data).then((res) => {
            videos.value = res.videos
        }).then(() => {
            // Revisar en localStorage si existe un índice guardado
            let storedIndex = localStorage.getItem('aca_short_video')
            if (storedIndex === null) {
                // Si no existe, usa el primer video
                currentIndex.value = 0
                localStorage.setItem('aca_short_video', currentIndex.value)
            } else {
                currentIndex.value = parseInt(storedIndex)
                // Validar que el índice sea correcto
                if (currentIndex.value < 0 || currentIndex.value >= videos.value.length) {
                    currentIndex.value = 0
                    localStorage.setItem('aca_short_video', currentIndex.value)
                }
            }
            updateVideo(currentIndex.value)
        }).finally(() => {
            vcLoading.value = false
        });
    }

    // Método para avanzar al siguiente video
    function nextVideo() {
        if (currentIndex.value < videos.value.length - 1) {
            currentIndex.value++
            localStorage.setItem('aca_short_video', currentIndex.value)
            updateVideo(currentIndex.value)
        }
    }

    // Método para retroceder al video anterior
    function previousVideo() {
        if (currentIndex.value > 0) {
            currentIndex.value--
            localStorage.setItem('aca_short_video', currentIndex.value)
            updateVideo(currentIndex.value)
        }
    }

    onMounted(() => {
        getVideos()
    });

    const getIframeSrc = (item) => {
        if(video.type == 1){
            return video.iframe
        }else{
            let iframeHtml = video.iframe;
            const match = iframeHtml.match(/src="([^"]+)"/)
            return match ? match[1] : ''
        }
    }
</script>
<template>
    <!-- From Uiverse.io by ayman-ashine -->
    <div class="vc-card ">
        <template v-if="videos.length > 0 && video.iframe">
            <!-- <div v-html="modifiedContent(video.iframe)"></div> -->
            <div class="relative w-full" v-if="video.iframe">
                <div v-if="!iframeLoaded" class="absolute inset-0 z-10 flex items-center justify-center bg-white bg-opacity-50">
                    <!-- Spinner -->
                    <svg role="status" class="inline h-6 w-6 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                    </svg>
                </div>
                <iframe
                    :src="getIframeSrc(video)"
                    width="100%"
                    :height="newHeight"
                    frameborder="0"
                    allowfullscreen
                    @load="iframeLoaded = true"
                    class="w-full"
                ></iframe>
            </div>
        </template>
        <template v-else>
            <img  class="vc-image" alt="" src="https://uiverse.io/astronaut.png" />
            <div class="vc-heading">Pronto Clips Educativo</div>
        </template>

        <div v-if="videos.length > 0" class="vc-icons mt-2">
            <button @click="previousVideo"  class="vc-button" type="button">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                    <path fill="currentColor" d="M267.5 440.6c9.5 7.9 22.8 9.7 34.1 4.4s18.4-16.6 18.4-29l0-320c0-12.4-7.2-23.7-18.4-29s-24.5-3.6-34.1 4.4l-192 160L64 241 64 96c0-17.7-14.3-32-32-32S0 78.3 0 96L0 416c0 17.7 14.3 32 32 32s32-14.3 32-32l0-145 11.5 9.6 192 160z"/>
                </svg>
            </button>
            <button @click="nextVideo" class="vc-button" type="button">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                    <path fill="currentColor" d="M52.5 440.6c-9.5 7.9-22.8 9.7-34.1 4.4S0 428.4 0 416L0 96C0 83.6 7.2 72.3 18.4 67s24.5-3.6 34.1 4.4l192 160L256 241l0-145c0-17.7 14.3-32 32-32s32 14.3 32 32l0 320c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-145-11.5 9.6-192 160z"/>
                </svg>
            </button>
        </div>
        <div v-else class="vc-icons">
            <a class="vc-link" href="#">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                    <path fill="currentColor" d="M267.5 440.6c9.5 7.9 22.8 9.7 34.1 4.4s18.4-16.6 18.4-29l0-320c0-12.4-7.2-23.7-18.4-29s-24.5-3.6-34.1 4.4l-192 160L64 241 64 96c0-17.7-14.3-32-32-32S0 78.3 0 96L0 416c0 17.7 14.3 32 32 32s32-14.3 32-32l0-145 11.5 9.6 192 160z"/>
                </svg>
            </a>
            <a class="vc-link" href="#">
                <svg v-if="startVideo" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                    <path fill="currentColor" d="M48 64C21.5 64 0 85.5 0 112L0 400c0 26.5 21.5 48 48 48l32 0c26.5 0 48-21.5 48-48l0-288c0-26.5-21.5-48-48-48L48 64zm192 0c-26.5 0-48 21.5-48 48l0 288c0 26.5 21.5 48 48 48l32 0c26.5 0 48-21.5 48-48l0-288c0-26.5-21.5-48-48-48l-32 0z"/>
                </svg>
                <svg v-else class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                    <path fill="currentColor" d="M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80L0 432c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z"/>
                </svg>
            </a>
            <a  class="vc-link" href="#">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                    <path fill="currentColor" d="M0 128C0 92.7 28.7 64 64 64H320c35.3 0 64 28.7 64 64V384c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128z"/>
                </svg>
            </a>
            <a class="vc-link" href="#">
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
            transform: translate(-100%);
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

    .vc-card .vc-icons .vc-link, .vc-button {
        display: flex;
        flex-grow: 1;
        align-items: center;
        justify-content: center;
        background-color: var(--dark);
        color: var(--lightgray);
        padding: calc(var(--unit) / 2);
        border-radius: calc(var(--unit) / 2);
    }

    .vc-card .vc-icons .vc-link:hover, .vc-button:hover  {
        transition: 0.2s;
        background-color: var(--semidark);
    }

</style>
