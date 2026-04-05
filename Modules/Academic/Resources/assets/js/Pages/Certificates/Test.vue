<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { ref, reactive, onMounted, watch } from 'vue';

const stageRef = ref(null);
const bgImage = ref(null);

// 15 Fuentes gratuitas (Sistema y Web-Safe)
const fontOptions = [
    { name: 'Arial', value: 'Arial, sans-serif' },
    { name: 'Times New Roman', value: '"Times New Roman", serif' },
    { name: 'Georgia', value: 'Georgia, serif' },
    { name: 'Verdana', value: 'Verdana, sans-serif' },
    { name: 'Courier New', value: '"Courier New", monospace' },
    { name: 'Impact', value: 'Impact, Charcoal, sans-serif' },
    { name: 'Trebuchet MS', value: '"Trebuchet MS", sans-serif' },
    { name: 'Palatino', value: '"Palatino Linotype", Palatino, serif' },
    { name: 'Comic Sans', value: '"Comic Sans MS", cursive' },
    { name: 'Arial Black', value: '"Arial Black", sans-serif' },
    { name: 'Lucida Sans', value: '"Lucida Sans Unicode", sans-serif' },
    { name: 'Tahoma', value: 'Tahoma, Geneva, sans-serif' },
    { name: 'Garamond', value: 'Garamond, serif' },
    { name: 'Brush Script', value: '"Brush Script MT", cursive' },
    { name: 'Bookman', value: '"Bookman Old Style", serif' },
];

// Función para crear configuración de texto estandarizada
const createTextConfig = (text, y, fontSize, width = 600) => {
    const config = reactive({
        text, x: 400, y, fontSize, width,
        offsetX: width / 2, align: 'center', fill: '#000000',
        fontFamily: 'Arial, sans-serif', draggable: true
    });
    // Sincronizar offset con ancho para centrado real
    watch(() => config.width, (newW) => { config.offsetX = newW / 2; });
    return config;
};

// Elementos de Texto
const title = createTextConfig('DIPLOMA DE RECONOCIMIENTO', 100, 30);
const student = createTextConfig('NOMBRE DEL ESTUDIANTE', 230, 45);
const description = createTextConfig('Por haber completado con éxito todas las exigencias académicas del programa.', 320, 18, 500);
const dateText = createTextConfig('Fecha: 04 de Abril de 2026', 450, 14, 300);

// Configuración del QR (Imagen con Proporción)
const qrConfig = reactive({
    image: null, x: 650, y: 450, width: 100, height: 100,
    aspectRatio: 1, draggable: true, visible: false
});

// Mantener proporción del QR
watch(() => qrConfig.width, (newW) => {
    qrConfig.height = newW / qrConfig.aspectRatio;
});

onMounted(() => {
    const image = new window.Image();
    image.crossOrigin = "Anonymous";
    image.src = "https://marketplace.canva.com/EAFjGk7DTSQ/3/0/1600w/canva-diploma-curso-manicura-simple-beige-2o56ns73Xlk.jpg";
    image.onload = () => { bgImage.value = image; };
});

const handleQrUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            const img = new window.Image();
            img.src = event.target.result;
            img.onload = () => {
                qrConfig.aspectRatio = img.width / img.height;
                qrConfig.image = img;
                qrConfig.width = 100;
                qrConfig.height = 100 / qrConfig.aspectRatio;
                qrConfig.visible = true;
            };
        };
        reader.readAsDataURL(file);
    }
};

const download = () => {
    const uri = stageRef.value.getStage().toDataURL({ pixelRatio: 2 });
    const link = document.createElement('a');
    link.download = `Certificado_${student.text}.png`;
    link.href = uri;
    link.click();
};

// Componente de control reutilizable para evitar repetir HTML
const textElements = [
    { label: 'Título', state: title },
    { label: 'Nombre Alumno', state: student },
    { label: 'Descripción', state: description },
    { label: 'Fecha/Lugar', state: dateText },
];
</script>

<template>
    <AppLayout title="Certificados">
        <div class="p-5 flex flex-col lg:flex-row gap-5 font-sans bg-gray-100 dark:bg-transparent min-h-screen">

            <div class="w-full lg:w-[450px] space-y-4 max-h-[90vh] overflow-y-auto pr-2 custom-scrollbar">
                <div class="panel shadow-lg border-none">
                    <h2 class="text-xl font-bold mb-4 text-primary uppercase tracking-tight">Editor Maestro</h2>

                    <div v-for="(item, idx) in textElements" :key="idx" class="mb-6 pb-4 border-b last:border-0 border-gray-200">
                        <div class="flex justify-between items-center mb-2">
                            <span class="badge badge-outline-primary text-[10px]">{{ item.label }}</span>
                            <div class="flex gap-1">
                                <button @click="item.state.align = 'left'" :class="item.state.align === 'left' ? 'text-primary' : 'text-gray-400'"><i class="text-xs">L</i></button>
                                <button @click="item.state.align = 'center'" :class="item.state.align === 'center' ? 'text-primary' : 'text-gray-400'"><i class="text-xs">C</i></button>
                                <button @click="item.state.align = 'right'" :class="item.state.align === 'right' ? 'text-primary' : 'text-gray-400'"><i class="text-xs">R</i></button>
                            </div>
                        </div>

                        <textarea v-model="item.state.text" class="form-input mb-3" rows="1"></textarea>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase">Fuente</label>
                                <select v-model="item.state.fontFamily" class="form-select py-1 text-xs">
                                    <option v-for="f in fontOptions" :key="f.name" :value="f.value">{{ f.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase">Alineación</label>
                                <select v-model="item.state.align" class="form-select py-1 text-xs">
                                    <option value="left">Izquierda</option>
                                    <option value="center">Centro</option>
                                    <option value="right">Derecha</option>
                                    <option value="justify">Justificado</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mt-2">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase">Tamaño: {{ item.state.fontSize }}px</label>
                                <input v-model.number="item.state.fontSize" type="number" class="form-input py-1" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase">Ancho Parrafo: {{ item.state.width }}px</label>
                                <input v-model.number="item.state.width" type="number" class="form-input py-1" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/10 p-3 rounded-lg border border-yellow-200">
                        <label class="font-bold text-xs text-yellow-700 uppercase block mb-2">QR / Imagen Extra</label>
                        <input type="file" @change="handleQrUpload" class="form-input text-xs mb-3" accept="image/*" />
                        <div v-if="qrConfig.visible" class="space-y-2">
                            <label class="text-[10px] font-bold">AJUSTAR ANCHO (Mantiene proporción):</label>
                            <input v-model.number="qrConfig.width" type="range" min="20" max="400" class="w-full accent-yellow-600" />
                        </div>
                    </div>

                    <button @click="download" class="btn btn-primary w-full mt-6 py-4 font-bold shadow-lg">
                        GENERAR CERTIFICADO HD
                    </button>
                </div>
            </div>

            <div class="flex-1 panel p-0 bg-[#1a1c2d] flex flex-col justify-center items-center overflow-hidden min-height-[750px]">
                <div class="mb-4 text-white/40 text-xs italic">Puedes arrastrar cualquier elemento con el mouse</div>

                <div class="shadow-2xl border-[16px] border-white/5 rounded-sm">
                    <v-stage ref="stageRef" :config="{ width: 800, height: 600 }">
                        <v-layer>
                            <v-image v-if="bgImage" :config="{ image: bgImage, width: 800, height: 600 }" />

                            <v-image v-if="qrConfig.visible" :config="qrConfig" @dragend="(e) => { qrConfig.x = e.target.x(); qrConfig.y = e.target.y(); }" />

                            <v-text v-for="(el, i) in textElements" :key="i" :config="el.state" @dragend="(e) => { el.state.x = e.target.x(); el.state.y = e.target.y(); }" />
                        </v-layer>
                    </v-stage>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.form-input, .form-select { @apply text-xs; }
</style>
