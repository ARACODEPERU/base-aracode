<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import QRCode from 'qrcode';
import { jsPDF } from 'jspdf';

const props = defineProps({
    certificateId: { type: Number, default: null },
});
const emit = defineEmits(['close']);

const previewLoading = ref(false);
const certificatePreview = ref(null);
const activeSideKey = ref('front');
const stageRef = ref(null);
const previewWrap = ref(null);
const stageWidth = ref(900);
const stageMaxHeight = ref(650);
const loadedImages = ref({});
const qrImages = ref({});

const activeSide = computed(() => {
    return certificatePreview.value?.sides?.find((side) => side.key === activeSideKey.value) || certificatePreview.value?.sides?.[0] || null;
});

const stageScale = computed(() => {
    if (!activeSide.value?.width) return 1;

    const widthScale = stageWidth.value / activeSide.value.width;
    const heightScale = stageMaxHeight.value / activeSide.value.height;

    return Math.min(widthScale, heightScale, 1);
});

const stageConfig = computed(() => ({
    width: activeSide.value ? activeSide.value.width * stageScale.value : stageWidth.value,
    height: activeSide.value ? activeSide.value.height * stageScale.value : 500,
}));
const layerConfig = computed(() => ({
    scaleX: stageScale.value,
    scaleY: stageScale.value,
}));

const baseImage = computed(() => loadedImages.value[activeSide.value?.key] || null);
const qrImage = computed(() => qrImages.value[activeSide.value?.key] || null);

const loadImage = (src) => new Promise((resolve) => {
    if (!src) {
        resolve(null);
        return;
    }

    const image = new Image();
    image.crossOrigin = 'anonymous';
    image.onload = () => resolve(image);
    image.onerror = () => resolve(null);
    image.src = src;
});

const updateStageWidth = () => {
    stageWidth.value = previewWrap.value?.clientWidth || 900;
    stageMaxHeight.value = previewWrap.value?.clientHeight || 650;
};

const prepareSideAssets = async (side) => {
    if (!side) return;

    const image = await loadImage(side.base_image);
    loadedImages.value = { ...loadedImages.value, [side.key]: image };

    if (side.qr?.text) {
        const qrData = await QRCode.toDataURL(side.qr.text, {
            margin: 2,
            width: Math.max(side.qr.size || 120, 120),
        });
        const qr = await loadImage(qrData);
        qrImages.value = { ...qrImages.value, [side.key]: qr };
    }
};

const loadPreview = async () => {
    previewLoading.value = true;

    try {
        const response = await axios.get(route('aca_image_download', props.certificateId), {
            params: { preview: 1 },
        });

        certificatePreview.value = response.data;
        activeSideKey.value = response.data?.sides?.[0]?.key || 'front';
        await nextTick();
        updateStageWidth();
        await Promise.all((response.data?.sides || []).map(prepareSideAssets));
    } catch (error) {
        const title = error.response?.status === 404 ? 'Certificado no encontrado' : 'Error al descargar';
        const message = error.response?.data?.message || 'Falta configuración del administrador.';
        Swal.fire({
            icon: 'error',
            title: title,
            text: message,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        emit('close');
    } finally {
        previewLoading.value = false;
    }
};

onMounted(async () => {
    await loadPreview();
    window.addEventListener('resize', updateStageWidth);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateStageWidth);
});

const closeModal = () => {
    emit('close');
};

const textConfig = (item) => {
    const fontSize = Number(item.font_size || 18);
    const fontFamily = item.font_family || 'Arial';
    const align = item.align || 'left';
    const config = {
        x: Number(item.x || 0),
        y: Number(item.y || 0),
        text: item.text || '',
        fontSize,
        fontFamily,
        fill: item.color || '#000000',
        align,
        lineHeight: Number(item.line_height || 1.25),
        opacity: 0.96,
    };

    // Si tiene rectángulo, centrar el texto dentro del recuadro
    if (item.rect_width && item.rect_height) {
        config.x = Number(item.x || 0) - item.rect_width / 2;
        config.y = Number(item.y || 0) - item.rect_height / 2;
        config.width = item.rect_width;
        config.height = item.rect_height;
        config.align = 'center';
        config.verticalAlign = 'middle';
    } else if (item.width !== null && item.width !== undefined && item.width !== '') {
        config.width = Number(item.width);
    }

    return config;
};

const rectConfig = (item) => {
    if (!item.rect_width || !item.rect_height) return null;
    return {
        x: Number(item.x || 0) - item.rect_width / 2,
        y: Number(item.y || 0) - item.rect_height / 2,
        width: item.rect_width,
        height: item.rect_height,
        stroke: item.rect_color || item.color || '#000000',
        strokeWidth: Number(item.rect_stroke_width || 1),
        fill: 'transparent',
    };
};

const qrAnchorToPosition = (align, x, y, size) => {
    const normalized = align || 'top-left';
    let positionX = Number(x || 0);
    let positionY = Number(y || 0);

    if (normalized.includes('right')) positionX = Number(activeSide.value?.width || 0) - size - positionX;
    if (normalized.includes('center')) positionX = (Number(activeSide.value?.width || 0) - size) / 2 + positionX;
    if (normalized.includes('bottom')) positionY = Number(activeSide.value?.height || 0) - size - positionY;

    return { x: positionX, y: positionY };
};

const qrConfig = computed(() => {
    if (!activeSide.value?.qr || !qrImage.value) return null;

    const size = Number(activeSide.value.qr.size || 120);
    const position = qrAnchorToPosition(
        activeSide.value.qr.align,
        activeSide.value.qr.x,
        activeSide.value.qr.y,
        size
    );

    return {
        image: qrImage.value,
        x: position.x,
        y: position.y,
        width: size,
        height: size,
        opacity: 0.98,
    };
});

const contentRows = (item) => {
    if (item.type === 'table') {
        return (item.modules || []).map((module) => ({
            module: module.title || 'Modulo',
            grade: module.grade !== null && module.grade !== undefined ? String(module.grade) : '-',
        }));
    }
    return (item.modules || []).map((module, index) => ({
        module: `${index + 1}. ${module.title || 'Modulo'}`,
        content: (module.themes || []).join(', '),
    }));
};

const rowHeight = (item, row) => {
    const fontSize = Number(item.font_size || 14);
    if (item.type === 'table') {
        return Math.max(fontSize * 2.2, 34);
    }
    const contentLength = Math.max((row.content || '').length, 1);
    const lines = Math.max(1, Math.ceil(contentLength / 72));
    return Math.max(fontSize * (lines + 1.2), 34);
};

const rowY = (item, rowIndex) => {
    const rows = contentRows(item);
    return rows.slice(0, rowIndex).reduce((total, row) => total + rowHeight(item, row), 34);
};

const tableHeight = (item) => {
    return contentRows(item).reduce((total, row) => total + rowHeight(item, row), 34);
};

const contentText = (item) => {
    return (item.modules || []).map((module, index) => {
        const themes = (module.themes || []).map((theme) => `- ${theme}`).join('\n');
        return `${index + 1}. ${module.title || 'Modulo'}${themes ? `\n${themes}` : ''}`;
    }).join('\n\n');
};

const downloadDataUrl = (dataUrl, fileName) => {
    const link = document.createElement('a');
    link.href = dataUrl;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    link.remove();
};

const getStageDataUrl = () => {
    const stage = stageRef.value?.getStage();
    return stage?.toDataURL({ pixelRatio: 2 / stageScale.value, mimeType: 'image/png' });
};

const downloadPng = () => {
    const dataUrl = getStageDataUrl();
    if (!dataUrl || !activeSide.value) return;
    downloadDataUrl(dataUrl, `${certificatePreview.value?.file_name || 'certificado'}_${activeSide.value.key}.png`);
};

const downloadPdf = () => {
    const dataUrl = getStageDataUrl();
    if (!dataUrl || !activeSide.value) return;

    const orientation = activeSide.value.width >= activeSide.value.height ? 'landscape' : 'portrait';
    const pdf = new jsPDF({
        orientation,
        unit: 'px',
        format: [activeSide.value.width, activeSide.value.height],
    });

    pdf.addImage(dataUrl, 'PNG', 0, 0, activeSide.value.width, activeSide.value.height);
    pdf.save(`${certificatePreview.value?.file_name || 'certificado'}_${activeSide.value.key}.pdf`);
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
        <div class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Certificado</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Visualiza el certificado antes de descargarlo</p>
                </div>
                <button
                    type="button"
                    class="rounded-full p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                    @click="closeModal"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                <div class="flex gap-2">
                    <button
                        v-for="side in certificatePreview?.sides || []"
                        :key="side.key"
                        type="button"
                        :class="[
                            'rounded px-4 py-2 text-sm font-semibold',
                            activeSideKey === side.key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200'
                        ]"
                        @click="activeSideKey = side.key"
                    >
                        {{ side.label }}
                    </button>
                </div>

                <div class="flex gap-2">
                    <button type="button" class="btn btn-primary" :disabled="previewLoading || !activeSide" @click="downloadPdf">
                        Descargar en PDF
                    </button>
                    <button type="button" class="btn btn-outline-primary" :disabled="previewLoading || !activeSide" @click="downloadPng">
                        Descargar en PNG
                    </button>
                </div>
            </div>

            <div ref="previewWrap" class="min-h-0 flex-1 overflow-auto bg-gray-100 p-4 dark:bg-gray-950">
                <div v-if="previewLoading" class="flex h-96 items-center justify-center text-gray-500">
                    Preparando certificado...
                </div>
                <div v-else-if="activeSide" class="mx-auto w-fit overflow-hidden rounded bg-white shadow">
                    <v-stage ref="stageRef" :config="stageConfig">
                        <v-layer :config="layerConfig">
                            <v-image
                                v-if="baseImage"
                                :config="{ image: baseImage, x: 0, y: 0, width: activeSide.width, height: activeSide.height }"
                            />
                            <template v-for="item in activeSide.texts" :key="item.id">
                                <v-rect
                                    v-if="item.rect_width"
                                    :config="rectConfig(item)"
                                />
                                <v-text :config="textConfig(item)" />
                            </template>
                            <template v-for="item in activeSide.contents" :key="item.id">
                                <v-group v-if="item.type === 'table'" :config="{ x: item.x, y: item.y }">
                                    <v-rect :config="{ x: 0, y: 0, width: item.width, height: tableHeight(item), stroke: item.color || '#111827', strokeWidth: 1 }" />
                                    <v-rect :config="{ x: 0, y: 0, width: item.width, height: 34, fill: 'rgba(243, 244, 246, 0.92)', stroke: item.color || '#111827', strokeWidth: 1 }" />
                                    <v-line :config="{ points: [item.width * 0.70, 0, item.width * 0.70, tableHeight(item)], stroke: item.color || '#111827', strokeWidth: 1 }" />
                                    <v-text :config="{ x: 8, y: 9, text: 'MÓDULO', fontSize: item.font_size, fontFamily: item.font_family || 'Arial', fontStyle: 'bold', fill: item.color || '#000000', width: item.width * 0.70 - 16 }" />
                                    <v-text :config="{ x: item.width * 0.70 + 8, y: 9, text: 'NOTA', fontSize: item.font_size, fontFamily: item.font_family || 'Arial', fontStyle: 'bold', fill: item.color || '#000000', width: item.width * 0.30 - 16, align: 'center' }" />
                                    <template v-for="(row, rowIndex) in contentRows(item)" :key="`${item.id}-${rowIndex}`">
                                        <v-line :config="{ points: [0, rowY(item, rowIndex), item.width, rowY(item, rowIndex)], stroke: item.color || '#111827', strokeWidth: 1 }" />
                                        <v-text :config="{ x: 8, y: rowY(item, rowIndex) + 8, text: row.module, fontSize: item.font_size, fontFamily: item.font_family || 'Arial', fill: item.color || '#000000', width: item.width * 0.70 - 16 }" />
                                        <v-text :config="{ x: item.width * 0.70 + 8, y: rowY(item, rowIndex) + 8, text: row.grade, fontSize: item.font_size, fontFamily: item.font_family || 'Arial', fill: item.color || '#000000', width: item.width * 0.30 - 16, align: 'center' }" />
                                    </template>
                                </v-group>
                                <v-text
                                    v-else
                                    :config="{ x: item.x, y: item.y, text: contentText(item), fontSize: item.font_size, fontFamily: item.font_family || 'Arial', fill: item.color || '#000000', width: item.width, lineHeight: 1.25 }"
                                />
                            </template>
                            <v-image
                                v-if="qrConfig"
                                :config="qrConfig"
                            />
                        </v-layer>
                    </v-stage>
                </div>
            </div>
        </div>
    </div>
</template>
