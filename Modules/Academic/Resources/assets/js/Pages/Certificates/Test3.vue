<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import { ref, onMounted, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    certificates: {
        type: Object,
        default: () => ({})
    }
});

// 1. Inicializamos el formulario con los datos recibidos de los props
const form = useForm({
    id: props.certificates.id,
    // Datos del fondo
    certificate_img: props.certificates.certificate_img || "https://marketplace.canva.com/EAGlAaM4OX4/1/0/1600w/canva-certificado-diploma-dibujos-%C3%BAtiles-escolares-ilustrativo-infantil-colorido-YKSKPjRZetE.jpg",
    // Datos del Nombre del Alumno (basado en tus columnas)
    fontfamily_names: props.certificates.fontfamily_names || 'Arial, sans-serif',
    font_align_names: props.certificates.font_align_names || 'center',
    position_names_x: props.certificates.position_names_x || 400,
    position_names_y: props.certificates.position_names_y || 230,
    font_size_names: props.certificates.font_size_names || 45,
    color_names: props.certificates.color_names || '#000000',
    visible_names: props.certificates.visible_names ?? 1, // tinyint
    text: props.certificates.certificate_img,

    // Datos del QR
    position_qr_x: props.certificates.position_qr_x || 650,
    position_qr_y: props.certificates.position_qr_y || 450,
    size_qr: props.certificates.size_qr || 100,
    visible_image_qr: props.certificates.visible_image_qr ?? 0,
});

const stageRef = ref(null);
const bgImage = ref(null);
const qrImage = ref(null);

const fontOptions = [
    { name: 'Arial', value: 'Arial, sans-serif' },
    { name: 'Times New Roman', value: '"Times New Roman", serif' },
    { name: 'Georgia', value: 'Georgia, serif' },
    { name: 'Verdana', value: 'Verdana, sans-serif' },
    { name: 'Courier New', value: '"Courier New", monospace' },
    { name: 'Impact', value: 'Impact, Charcoal, sans-serif' },
    { name: 'Comic Sans', value: '"Comic Sans MS", cursive' },
    { name: 'Garamond', value: 'Garamond, serif' },
    { name: 'Brush Script', value: '"Brush Script MT", cursive' },
];

// 2. Configuración reactiva para Konva (se alimenta del FORM)
const studentConfig = computed(() => ({
    text: props.certificates.certificate_img,
    x: parseInt(form.position_names_x),
    y: parseInt(form.position_names_y),
    fontSize: parseInt(form.font_size_names),
    fontFamily: form.fontfamily_names,
    fill: form.color_names,
    align: form.font_align_names,
    visible: form.visible_names === 1,
    draggable: true,
    width: 600,
    offsetX: 300 // Centrado manual (width/2)
}));

const qrConfig = computed(() => ({
    image: qrImage.value,
    x: parseInt(form.position_qr_x),
    y: parseInt(form.position_qr_y),
    width: parseInt(form.size_qr),
    height: parseInt(form.size_qr),
    draggable: true,
    visible: form.visible_image_qr === 1
}));

onMounted(() => {
    // Cargar imagen de fondo
    const image = new window.Image();
    image.crossOrigin = "Anonymous";
    image.src = form.certificate_img;
    image.onload = () => { bgImage.value = image; };

    // Cargar QR de ejemplo
    const qrImg = new window.Image();
    qrImg.crossOrigin = "Anonymous";
    qrImg.src = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=VERIFICACION";
    qrImg.onload = () => { qrImage.value = qrImg; };
});

// 3. Capturar nuevas posiciones al soltar el ratón en el canvas
const handleDragName = (e) => {
    form.position_names_x = Math.round(e.target.x());
    form.position_names_y = Math.round(e.target.y());
};

const handleDragQr = (e) => {
    form.position_qr_x = Math.round(e.target.x());
    form.position_qr_y = Math.round(e.target.y());
};

const saveChanges = () => {
    form.put(route('aca_certificates_parameters.update', form.id), {
        preserveScroll: true,
        onSuccess: () => alert('¡Parámetros guardados con éxito!')
    });
};

const download = () => {
    const uri = stageRef.value.getStage().toDataURL({ pixelRatio: 2 });
    const link = document.createElement('a');
    link.download = `Certificado_Editado.png`;
    link.href = uri;
    link.click();
};
</script>

<template>
    <AppLayout title="Certificados">
        <div class="p-5 flex flex-col lg:flex-row gap-5 font-sans bg-gray-100 dark:bg-transparent min-h-screen">

            <div class="w-full lg:w-[450px] space-y-4 max-h-[90vh] overflow-y-auto pr-2 custom-scrollbar">
                <div class="panel shadow-lg border-none">
                    <h2 class="text-xl font-bold mb-4 text-primary uppercase tracking-tight border-b pb-2">Configurar Parámetros</h2>

                    <div class="mb-6 pb-4 border-b border-gray-200">
                        <div class="flex justify-between items-center mb-3">
                            <span class="badge badge-outline-primary">NOMBRE DEL ALUMNO</span>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold">VISIBLE</span>
                                <input type="checkbox" v-model="form.visible_names" :true-value="1" :false-value="0" class="form-checkbox" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase">Fuente</label>
                                <select v-model="form.fontfamily_names" class="form-select py-1 text-xs">
                                    <option v-for="f in fontOptions" :key="f.name" :value="f.value">{{ f.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase">Alineación</label>
                                <select v-model="form.font_align_names" class="form-select py-1 text-xs">
                                    <option value="left">Izquierda</option>
                                    <option value="center">Centro</option>
                                    <option value="right">Derecha</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase">Tamaño: {{ form.font_size_names }}px</label>
                                <input v-model.number="form.font_size_names" type="number" class="form-input py-1" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase">Color de Letra</label>
                                <input v-model="form.color_names" type="color" class="form-input p-0 h-9" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 dark:bg-yellow-900/10 p-3 rounded-lg border border-yellow-200 mb-6">
                        <div class="flex justify-between items-center mb-3">
                            <label class="font-bold text-xs text-yellow-700 uppercase">Configuración QR</label>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold text-yellow-700">MOSTRAR</span>
                                <input type="checkbox" v-model="form.visible_image_qr" :true-value="1" :false-value="0" class="form-checkbox text-yellow-600" />
                            </div>
                        </div>
                        <div v-if="form.visible_image_qr === 1" class="space-y-2">
                            <label class="text-[10px] font-bold">Tamaño: {{ form.size_qr }}px</label>
                            <input v-model.number="form.size_qr" type="range" min="30" max="250" class="w-full accent-yellow-600" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <button @click="saveChanges" :disabled="form.processing" class="btn btn-success w-full py-4 font-bold uppercase">
                            {{ form.processing ? 'Guardando...' : 'Guardar en Base de Datos' }}
                        </button>
                        <button @click="download" class="btn btn-outline-primary w-full py-2 font-bold text-xs">
                            DESCARGAR PREVIEW PNG
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex-1 panel p-0 bg-[#1a1c2d] flex flex-col justify-center items-center overflow-hidden min-h-[650px]">
                <div class="mb-4 text-white/40 text-xs italic">Puedes arrastrar el nombre y el QR con el mouse</div>

                <div class="shadow-2xl border-[16px] border-white/5 rounded-sm">
                    <v-stage ref="stageRef" :config="{ width: 800, height: 600 }">
                        <v-layer>
                            <v-image v-if="bgImage" :config="{ image: bgImage, width: 800, height: 600 }" />

                            <v-image :config="qrConfig" @dragend="handleDragQr" />

                            <v-text :config="studentConfig" @dragend="handleDragName" />
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
