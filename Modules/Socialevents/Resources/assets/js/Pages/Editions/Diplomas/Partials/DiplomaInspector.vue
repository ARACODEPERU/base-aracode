<script setup>
import { computed } from 'vue';
import { FALLBACK_FONT } from '../../../../utils/diplomaLayout';

/**
 * Propiedades del elemento seleccionado.
 *
 * Los cambios se emiten al editor, que los aplica al layout y marca el diseño
 * como modificado (el guardado es manual con autoguardado).
 */
const props = defineProps({
    element: { type: Object, default: null },
    fonts: { type: Array, default: () => [] },
});

const emit = defineEmits(['patch', 'delete', 'duplicate', 'move']);

const familyOptions = computed(() => {
    const families = props.fonts?.map((font) => font.family) || [];

    return families.length ? families : [FALLBACK_FONT, 'Arial'];
});

const isText = computed(() => props.element?.type === 'text');
const isSquad = computed(() => props.element?.type === 'squad');
const isQr = computed(() => props.element?.type === 'qr');
const isImage = computed(() => props.element?.type === 'image');
const isRect = computed(() => props.element?.type === 'rect');
const isLine = computed(() => props.element?.type === 'line');

const numberValue = (event, field) => {
    const raw = String(event.target.value ?? '').trim();

    // Mientras el usuario borra para escribir otro valor no tocamos el layout,
    // así el campo no se rellena solo con 0 y se puede editar con normalidad.
    if (raw === '') return;

    const value = Number(raw);

    emit('patch', { [field]: Number.isFinite(value) ? value : 0 });
};

const textValue = (event, field) => emit('patch', { [field]: event.target.value });
</script>

<template>
    <div class="space-y-4">
        <div v-if="!element" class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
            Selecciona un elemento del lienzo o de la lista de capas para editarlo.
        </div>

        <template v-else>
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-black uppercase tracking-wide text-slate-600">Elemento</h3>
                <div class="flex items-center gap-1">
                    <button type="button" class="diploma-icon-btn" title="Subir capa" @click="emit('move', -1)">↑</button>
                    <button type="button" class="diploma-icon-btn" title="Bajar capa" @click="emit('move', 1)">↓</button>
                    <button type="button" class="diploma-icon-btn" title="Duplicar" @click="emit('duplicate')">⧉</button>
                    <button type="button" class="diploma-icon-btn diploma-icon-btn--danger" title="Eliminar" @click="emit('delete')">✕</button>
                </div>
            </div>

            <label class="diploma-field">
                <span>Nombre de la capa</span>
                <input type="text" :value="element.name" maxlength="60" @input="textValue($event, 'name')" />
            </label>

            <div class="grid grid-cols-2 gap-2">
                <label class="diploma-field">
                    <span>X</span>
                    <input type="number" :value="Math.round(element.x)" @change="numberValue($event, 'x')" />
                </label>
                <label class="diploma-field">
                    <span>Y</span>
                    <input type="number" :value="Math.round(element.y)" @change="numberValue($event, 'y')" />
                </label>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <label class="diploma-field">
                    <span>Rotación</span>
                    <input type="number" :value="Math.round(element.rotation || 0)" step="1" @change="numberValue($event, 'rotation')" />
                </label>
                <label class="diploma-field">
                    <span>Visible</span>
                    <select :value="element.visible === false ? '0' : '1'" @change="emit('patch', { visible: $event.target.value === '1' })">
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                </label>
            </div>

            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input
                    type="checkbox"
                    :checked="element.locked === true"
                    @change="emit('patch', { locked: $event.target.checked })"
                />
                Bloquear posición
            </label>

            <!-- Texto -->
            <template v-if="isText">
                <label class="diploma-field">
                    <span>Texto (admite variables)</span>
                    <textarea rows="3" :value="element.text" maxlength="500" @input="textValue($event, 'text')"></textarea>
                </label>

                <label class="diploma-field">
                    <span>Fuente</span>
                    <select :value="element.fontFamily" @change="textValue($event, 'fontFamily')">
                        <option v-for="family in familyOptions" :key="family" :value="family">{{ family }}</option>
                    </select>
                </label>

                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Tamaño</span>
                        <input type="number" min="6" max="400" :value="element.fontSize" @change="numberValue($event, 'fontSize')" />
                    </label>
                    <label class="diploma-field">
                        <span>Color</span>
                        <input type="color" :value="element.color || '#111111'" @input="textValue($event, 'color')" />
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Alineación</span>
                        <select :value="element.align" @change="textValue($event, 'align')">
                            <option value="left">Izquierda</option>
                            <option value="center">Centro</option>
                            <option value="right">Derecha</option>
                        </select>
                    </label>
                    <label class="diploma-field">
                        <span>Ancho</span>
                        <input type="number" :value="Math.round(element.width || 0)" @change="numberValue($event, 'width')" />
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Interlineado</span>
                        <input type="number" step="0.05" :value="element.lineHeight" @change="numberValue($event, 'lineHeight')" />
                    </label>
                    <label class="diploma-field">
                        <span>Espaciado</span>
                        <input type="number" step="0.5" :value="element.letterSpacing || 0" @change="numberValue($event, 'letterSpacing')" />
                    </label>
                </div>
            </template>

            <!-- Plantel -->
            <template v-if="isSquad">
                <p class="rounded-lg bg-lime-50 px-3 py-2 text-xs font-semibold text-lime-900">
                    Solo se imprime en diplomas de equipo. Los jugadores salen de la plantilla de la edición.
                </p>

                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Columnas</span>
                        <input type="number" min="1" max="4" :value="element.columns" @change="numberValue($event, 'columns')" />
                    </label>
                    <label class="diploma-field">
                        <span>Ordenar por</span>
                        <select :value="element.sortBy" @change="textValue($event, 'sortBy')">
                            <option value="number">Camiseta</option>
                            <option value="name">Nombre</option>
                            <option value="position">Posición</option>
                        </select>
                    </label>
                </div>

                <label class="diploma-field">
                    <span>Título del bloque</span>
                    <input type="text" maxlength="60" :value="element.title" @input="textValue($event, 'title')" />
                </label>

                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Fuente</span>
                        <select :value="element.fontFamily" @change="textValue($event, 'fontFamily')">
                            <option v-for="family in familyOptions" :key="family" :value="family">{{ family }}</option>
                        </select>
                    </label>
                    <label class="diploma-field">
                        <span>Tamaño</span>
                        <input type="number" min="6" max="200" :value="element.fontSize" @change="numberValue($event, 'fontSize')" />
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Tamaño del título</span>
                        <input type="number" min="6" max="200" :value="element.titleFontSize" @change="numberValue($event, 'titleFontSize')" />
                    </label>
                    <label class="diploma-field">
                        <span>Color</span>
                        <input type="color" :value="element.color || '#111111'" @input="textValue($event, 'color')" />
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Ancho</span>
                        <input type="number" :value="Math.round(element.width || 0)" @change="numberValue($event, 'width')" />
                    </label>
                    <label class="diploma-field">
                        <span>Separación</span>
                        <input type="number" :value="Math.round(element.gap || 0)" @change="numberValue($event, 'gap')" />
                    </label>
                </div>

                <div class="flex flex-wrap gap-4 text-sm font-semibold text-slate-700">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" :checked="element.showJerseyNumber !== false" @change="emit('patch', { showJerseyNumber: $event.target.checked })" />
                        Camiseta
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" :checked="element.showPosition === true" @change="emit('patch', { showPosition: $event.target.checked })" />
                        Posición
                    </label>
                </div>
            </template>

            <!-- QR -->
            <template v-if="isQr">
                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Tamaño</span>
                        <input type="number" min="20" max="1500" :value="element.size" @change="numberValue($event, 'size')" />
                    </label>
                    <label class="diploma-field">
                        <span>Destino</span>
                        <select :value="element.mode || 'landing'" @change="textValue($event, 'mode')">
                            <option value="landing">Landing del torneo</option>
                            <option value="diploma">Página de logros (próximamente)</option>
                        </select>
                    </label>
                </div>
            </template>

            <!-- Imagen -->
            <template v-if="isImage">
                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Ancho</span>
                        <input type="number" :value="Math.round(element.width || 0)" @change="numberValue($event, 'width')" />
                    </label>
                    <label class="diploma-field">
                        <span>Alto</span>
                        <input type="number" :value="Math.round(element.height || 0)" @change="numberValue($event, 'height')" />
                    </label>
                </div>
            </template>

            <!-- Marco -->
            <template v-if="isRect">
                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Ancho</span>
                        <input type="number" :value="Math.round(element.width || 0)" @change="numberValue($event, 'width')" />
                    </label>
                    <label class="diploma-field">
                        <span>Alto</span>
                        <input type="number" :value="Math.round(element.height || 0)" @change="numberValue($event, 'height')" />
                    </label>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Borde</span>
                        <input type="color" :value="element.stroke || '#111111'" @input="textValue($event, 'stroke')" />
                    </label>
                    <label class="diploma-field">
                        <span>Grosor</span>
                        <input type="number" step="0.5" :value="element.strokeWidth" @change="numberValue($event, 'strokeWidth')" />
                    </label>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Radio</span>
                        <input type="number" :value="element.radius || 0" @change="numberValue($event, 'radius')" />
                    </label>
                    <label class="diploma-field">
                        <span>Relleno</span>
                        <input type="color" :value="element.fill || '#ffffff'" @input="textValue($event, 'fill')" />
                    </label>
                </div>
            </template>

            <!-- Línea -->
            <template v-if="isLine">
                <div class="grid grid-cols-2 gap-2">
                    <label class="diploma-field">
                        <span>Largo</span>
                        <input type="number" :value="Math.round(element.width || 0)" @change="numberValue($event, 'width')" />
                    </label>
                    <label class="diploma-field">
                        <span>Grosor</span>
                        <input type="number" step="0.5" :value="element.strokeWidth" @change="numberValue($event, 'strokeWidth')" />
                    </label>
                </div>
                <label class="diploma-field">
                    <span>Color</span>
                    <input type="color" :value="element.stroke || '#111111'" @input="textValue($event, 'stroke')" />
                </label>
            </template>
        </template>
    </div>
</template>

<style scoped>
.diploma-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #475569;
}

.diploma-field input,
.diploma-field select,
.diploma-field textarea {
    border: 1px solid #cbd5e1;
    border-radius: 0.6rem;
    background: #fff;
    padding: 0.45rem 0.6rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: #0f172a;
    text-transform: none;
    letter-spacing: normal;
}

.diploma-field input:focus,
.diploma-field select:focus,
.diploma-field textarea:focus {
    outline: 2px solid #84cc16;
    outline-offset: 1px;
}

.diploma-field input[type='color'] {
    padding: 0.15rem;
    height: 2.4rem;
}

.diploma-icon-btn {
    display: inline-flex;
    height: 1.9rem;
    width: 1.9rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    font-size: 0.85rem;
    color: #334155;
    transition: all 0.15s ease;
}

.diploma-icon-btn:hover {
    border-color: #84cc16;
    color: #3f6212;
}

.diploma-icon-btn--danger:hover {
    border-color: #fca5a5;
    background: #fef2f2;
    color: #b91c1c;
}
</style>
