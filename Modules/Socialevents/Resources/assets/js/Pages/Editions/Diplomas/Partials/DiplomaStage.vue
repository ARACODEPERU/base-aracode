<script setup>
import { computed, nextTick, onMounted, onUpdated, ref, shallowRef, watch } from 'vue';
import {
    imageConfig,
    lineConfig,
    loadImage,
    qrConfig,
    qrDataUrl,
    rectConfig,
    squadColumnConfigs,
    squadHeight,
    textConfig,
} from '../../../../utils/diplomaRender';

/**
 * Lienzo del diploma.
 *
 * En el editor es interactivo (selección, arrastre y transformación); en la
 * vista previa se usa igual pero sin interacción, de modo que lo que se ve es
 * exactamente lo que se exporta.
 */
const props = defineProps({
    side: { type: Object, required: true },
    scale: { type: Number, default: 1 },
    interactive: { type: Boolean, default: false },
    selectedId: { type: String, default: null },
});

const emit = defineEmits(['select', 'update']);

const stageRef = ref(null);
const layerRef = ref(null);
// Nodos y elementos del DOM nunca deben ser reactivos profundos: un Proxy de
// Konva o de una <img> rompe el dibujo del canvas (y llega a congelarlo).
const backgroundImage = shallowRef(null);
const elementImages = shallowRef({});
const qrImages = shallowRef({});
const selectedNode = shallowRef(null);

const imageCache = new Map();
const qrCache = new Map();

const canvasWidth = computed(() => Number(props.side?.width || 1550));
const canvasHeight = computed(() => Number(props.side?.height || 1096));

const stageConfig = computed(() => ({
    width: canvasWidth.value * props.scale,
    height: canvasHeight.value * props.scale,
}));

const layerConfig = computed(() => ({
    scaleX: props.scale,
    scaleY: props.scale,
}));

const backgroundConfig = computed(() => ({
    x: 0,
    y: 0,
    width: canvasWidth.value,
    height: canvasHeight.value,
    image: backgroundImage.value,
    listening: false,
}));

/**
 * Tipo del elemento seleccionado: decide si el transformer permite redimensionar.
 */
const selectedType = computed(
    () => (props.side?.elements || []).find((element) => element.id === props.selectedId)?.type || null
);

const transformerConfig = computed(() => ({
    nodes: selectedNode.value ? [selectedNode.value] : [],
    rotateEnabled: true,
    // El plantel se ajusta por el inspector: escalarlo dejaría el bloque
    // deformado sin forma de guardar el cambio.
    resizeEnabled: selectedType.value !== 'squad',
    enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right'],
    borderStroke: '#c8f169',
    borderStrokeWidth: 1.5,
    anchorStroke: '#c8f169',
    anchorFill: '#0b1f2b',
    anchorSize: 9,
    rotateAnchorOffset: 26,
    ignoreStroke: true,
}));

const elements = computed(() => (props.side?.elements || []).filter((element) => element.visible !== false));

/**
 * Firma estable de los recursos: sólo cambia si de verdad hay que volver a
 * cargar el fondo, una imagen o un QR. Con esto el editor no recarga nada al
 * escribir, mover o cambiar colores.
 */
const assetSignature = computed(() =>
    JSON.stringify({
        background: props.side?.background_url || '',
        elements: (props.side?.elements || []).map((element) => [
            element.id,
            element.type,
            element.type === 'image' ? element.src || '' : '',
            element.type === 'qr' ? `${element.qr_text || ''}|${element.size || 0}` : '',
        ]),
    })
);

const cachedImage = async (src) => {
    if (!src) return null;
    if (imageCache.has(src)) return imageCache.get(src);

    const image = await loadImage(src);

    if (imageCache.size > 60) imageCache.clear();
    imageCache.set(src, image);

    return image;
};

let loadToken = 0;

const loadAssets = async () => {
    const token = (loadToken += 1);
    const background = await cachedImage(props.side?.background_url);

    const images = {};
    const qrs = {};

    for (const element of props.side?.elements || []) {
        if (element.type === 'image' && element.src) {
            images[element.id] = await cachedImage(element.src);
        } else if (element.type === 'qr') {
            const key = `${element.qr_text || ''}|${element.size || 120}`;

            if (!qrCache.has(key)) {
                if (qrCache.size > 24) qrCache.clear();
                qrCache.set(key, await loadImage(await qrDataUrl(element)));
            }

            qrs[element.id] = qrCache.get(key);
        }

        // Si mientras cargábamos cambió el diseño, descartamos este resultado.
        if (token !== loadToken) return;
    }

    if (token !== loadToken) return;

    backgroundImage.value = background;
    elementImages.value = images;
    qrImages.value = qrs;
};

watch(assetSignature, loadAssets, { immediate: true });

onMounted(async () => {
    if (document.fonts?.ready) {
        await document.fonts.ready;
    }

    await nextTick();
    syncSelection(props.selectedId);
});

watch(
    () => props.selectedId,
    (id) => syncSelection(id)
);

const syncSelection = (id) => {
    if (!props.interactive) return;

    const layer = layerRef.value?.getNode();
    const node = id && layer ? layer.findOne('#' + id) || null : null;

    if (node !== selectedNode.value) {
        selectedNode.value = node;
    }
};

// El lienzo se vuelve a dibujar al cambiar el diseño: reconectamos el
// transformer para que el elemento elegido siga siendo manipulable.
onUpdated(() => syncSelection(props.selectedId));

const squadChildren = (element) =>
    squadColumnConfigs(element).map(({ key, config }) => ({
        key,
        config: {
            ...config,
            x: config.x - Number(element.x || 0),
            y: config.y - Number(element.y || 0),
        },
    }));

const selectElement = (element, event) => {
    if (!props.interactive) return;

    selectedNode.value = event.target;
    emit('select', element.id);
};

const onStageClick = (event) => {
    if (!props.interactive) return;

    if (event.target === event.target.getStage()) {
        selectedNode.value = null;
        emit('select', null);
    }
};

const commitNode = (element, node) => {
    const changes = {
        x: Math.round(node.x()),
        y: Math.round(node.y()),
        rotation: Math.round(node.rotation()),
    };

    if (element.type === 'qr') {
        changes.size = Math.max(20, Math.round(node.width() * node.scaleX()));
        node.scaleX(1);
        node.scaleY(1);
    } else if (element.type === 'image' || element.type === 'rect') {
        changes.width = Math.max(10, Math.round(node.width() * node.scaleX()));
        changes.height = Math.max(10, Math.round(node.height() * node.scaleY()));
        node.scaleX(1);
        node.scaleY(1);
    } else if (element.type === 'line') {
        changes.width = Math.max(10, Math.round(node.width() * node.scaleX()));
        node.scaleX(1);
    } else if (element.type === 'text') {
        changes.width = Math.max(40, Math.round(node.width() * node.scaleX()));
        node.scaleX(1);
    }

    emit('update', { id: element.id, changes });
};

const onDragEnd = (element, event) => commitNode(element, event.target);
const onTransformEnd = (element, event) => commitNode(element, event.target);
</script>

<template>
    <v-stage ref="stageRef" :config="stageConfig" @click="onStageClick" @tap="onStageClick">
        <v-layer ref="layerRef" :config="layerConfig">
            <v-image v-if="backgroundImage" :config="backgroundConfig" />

            <template v-for="element in elements" :key="element.id">
                <v-text
                    v-if="element.type === 'text'"
                    :config="{
                        ...textConfig(element),
                        id: element.id,
                        draggable: interactive && !element.locked,
                    }"
                    @mousedown="selectElement(element, $event)"
                    @touchstart="selectElement(element, $event)"
                    @dragend="onDragEnd(element, $event)"
                    @transformend="onTransformEnd(element, $event)"
                />

                <v-group
                    v-else-if="element.type === 'squad'"
                    :config="{
                        x: Number(element.x || 0),
                        y: Number(element.y || 0),
                        id: element.id,
                        draggable: interactive && !element.locked,
                    }"
                    @mousedown="selectElement(element, $event)"
                    @touchstart="selectElement(element, $event)"
                    @dragend="onDragEnd(element, $event)"
                >
                    <v-rect
                        :config="{
                            x: 0,
                            y: 0,
                            width: Number(element.width || 400),
                            height: squadHeight(element),
                            fill: 'rgba(0,0,0,0.001)',
                        }"
                    />
                    <v-text
                        v-for="child in squadChildren(element)"
                        :key="child.key"
                        :config="child.config"
                    />
                </v-group>

                <v-image
                    v-else-if="element.type === 'image' && elementImages[element.id]"
                    :config="{
                        ...imageConfig(element, elementImages[element.id]),
                        id: element.id,
                        draggable: interactive && !element.locked,
                    }"
                    @mousedown="selectElement(element, $event)"
                    @touchstart="selectElement(element, $event)"
                    @dragend="onDragEnd(element, $event)"
                    @transformend="onTransformEnd(element, $event)"
                />

                <v-image
                    v-else-if="element.type === 'qr' && qrImages[element.id]"
                    :config="{
                        ...qrConfig(element, qrImages[element.id]),
                        id: element.id,
                        draggable: interactive && !element.locked,
                    }"
                    @mousedown="selectElement(element, $event)"
                    @touchstart="selectElement(element, $event)"
                    @dragend="onDragEnd(element, $event)"
                    @transformend="onTransformEnd(element, $event)"
                />

                <v-rect
                    v-else-if="element.type === 'rect'"
                    :config="{
                        ...rectConfig(element),
                        id: element.id,
                        draggable: interactive && !element.locked,
                    }"
                    @mousedown="selectElement(element, $event)"
                    @touchstart="selectElement(element, $event)"
                    @dragend="onDragEnd(element, $event)"
                    @transformend="onTransformEnd(element, $event)"
                />

                <v-line
                    v-else-if="element.type === 'line'"
                    :config="{
                        ...lineConfig(element),
                        id: element.id,
                        draggable: interactive && !element.locked,
                    }"
                    @mousedown="selectElement(element, $event)"
                    @touchstart="selectElement(element, $event)"
                    @dragend="onDragEnd(element, $event)"
                    @transformend="onTransformEnd(element, $event)"
                />
            </template>

            <v-transformer v-if="interactive && selectedNode" :config="transformerConfig" />
        </v-layer>
    </v-stage>
</template>
