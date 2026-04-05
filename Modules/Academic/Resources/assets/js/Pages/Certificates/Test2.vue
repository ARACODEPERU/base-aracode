<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import { ref, reactive, onMounted } from 'vue';

const stageRef = ref(null);
const bgImage = ref(null);
const qrImage = ref(null);

// --- DATOS DE PRUEBA (Aquí es donde luego pondrás tus props de Laravel) ---
const certificateData = {
    title: "CERTIFICADO DE EXCELENCIA",
    student: "JUAN PÉREZ RODRÍGUEZ",
    description: "Por su destacada participación y aprobación del curso avanzado de 'Desarrollo Web con Laravel e Inertia.js', cumpliendo con un total de 120 horas académicas.",
    date: "Nuevo Chimbote, 04 de Abril de 2026",
    qrUrl: "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=Certificado-Validado-ID-12345",
    bgUrl: "https://marketplace.canva.com/EAFjGk7DTSQ/3/0/1600w/canva-diploma-curso-manicura-simple-beige-2o56ns73Xlk.jpg"
};

// --- CONFIGURACIÓN FIJA DE LOS ELEMENTOS ---
const stageSize = { width: 800, height: 600 };

const titleConfig = reactive({
    text: certificateData.title,
    x: 400, y: 120, width: 700, offsetX: 350,
    fontSize: 32, fontFamily: 'Georgia, serif', fill: '#1a1c2d', align: 'center'
});

const studentConfig = reactive({
    text: certificateData.student,
    x: 400, y: 240, width: 600, offsetX: 300,
    fontSize: 48, fontFamily: 'Arial, sans-serif', fill: '#c2410c', align: 'center', fontStyle: 'bold'
});

const descConfig = reactive({
    text: certificateData.description,
    x: 400, y: 340, width: 550, offsetX: 275,
    fontSize: 18, fontFamily: 'Verdana, sans-serif', fill: '#374151', align: 'center', lineHeight: 1.5
});

const dateConfig = reactive({
    text: certificateData.date,
    x: 400, y: 460, width: 400, offsetX: 200,
    fontSize: 14, fontFamily: 'Times New Roman, serif', fill: '#6b7280', align: 'center'
});

const qrConfig = reactive({
    image: null, x: 620, y: 440, width: 90, height: 90
});

onMounted(() => {
    // Cargar imagen de fondo
    const imgBg = new window.Image();
    imgBg.crossOrigin = "Anonymous";
    imgBg.src = certificateData.bgUrl;
    imgBg.onload = () => { bgImage.value = imgBg; };

    // Cargar imagen QR
    const imgQr = new window.Image();
    imgQr.crossOrigin = "Anonymous";
    imgQr.src = certificateData.qrUrl;
    imgQr.onload = () => { qrConfig.image = imgQr; };
});

const downloadCertificate = () => {
    const uri = stageRef.value.getStage().toDataURL({ pixelRatio: 3 }); // Calidad ultra alta
    const link = document.createElement('a');
    link.download = `Certificado_${certificateData.student}.png`;
    link.href = uri;
    link.click();
};
</script>

<template>
    <AppLayout title="Visualizar Certificado">
        <div class="pt-5 flex flex-col items-center">

            <div class="panel w-full max-w-4xl shadow-xl border-none mb-6 text-center">
                <h1 class="text-2xl font-bold text-gray-700 mb-2">Vista Previa del Certificado</h1>
                <p class="text-gray-500 mb-4">Los datos se han cargado automáticamente desde el sistema.</p>

                <div class="flex justify-center gap-4">
                    <button @click="downloadCertificate" class="btn btn-primary shadow-none">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Descargar en Alta Resolución
                    </button>
                </div>
            </div>

            <div class="bg-gray-800 p-4 rounded-lg shadow-2xl overflow-hidden border-[1px] border-white/10">
                <v-stage ref="stageRef" :config="stageSize">
                    <v-layer>
                        <v-image v-if="bgImage" :config="{ image: bgImage, width: 800, height: 600 }" />

                        <v-image v-if="qrConfig.image" :config="qrConfig" />

                        <v-text :config="titleConfig" />
                        <v-text :config="studentConfig" />
                        <v-text :config="descConfig" />
                        <v-text :config="dateConfig" />
                    </v-layer>
                </v-stage>
            </div>

            <div class="mt-8 text-white-dark text-sm flex items-center gap-2">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                Renderizado de seguridad completado. Certificado verificado.
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Evitar que se vea el panel de navegación al imprimir */
@media print {
    .btn, .panel:not(.canvas-container) {
        display: none !important;
    }
}
</style>
