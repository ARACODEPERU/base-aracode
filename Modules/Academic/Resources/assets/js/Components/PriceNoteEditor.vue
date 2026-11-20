<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Antes S/ 1800'
    },
    // 0 = sin limite; con un valor, el editor no deja pasar de esas lineas.
    maxLines: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['update:modelValue']);

const editor = ref(null);
const color = ref('#c3121b');
const active = ref({
    bold: false,
    italic: false,
    underline: false,
    strikeThrough: false
});

const buttonClass = 'rounded px-2 py-1 text-sm font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none dark:text-gray-200 dark:hover:bg-gray-600';
const activeClass = 'bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-white';

// Se conserva el HTML tal cual (los espacios finales incluidos, para no
// descolocar el cursor); solo el contenido sin texto se considera vacio.
const normalize = (html) => {
    const value = html || '';
    const texto = value
        .replace(/<br\s*\/?>/gi, '')
        .replace(/<[^>]*>/g, '')
        .replace(/&nbsp;/gi, ' ')
        .trim();

    return texto === '' ? '' : value;
};

const lastValid = ref(normalize(props.modelValue));

const currentHtml = () => normalize(editor.value?.innerHTML);

/**
 * Cuenta las lineas escritas: los <div>/<p> que crea el navegador y los <br>
 * se toman como saltos, y se ignoran los saltos vacios del final.
 */
const linesOf = (html) => {
    const container = document.createElement('div');
    container.innerHTML = html || '';

    container.querySelectorAll('br').forEach((br) => br.replaceWith(document.createTextNode('\n')));
    container.querySelectorAll('div, p').forEach((block) => {
        const fragment = document.createDocumentFragment();

        // El salto va antes del bloque: si fuera despues, dos bloques seguidos
        // se leerian como una sola linea.
        if (block.previousSibling) {
            fragment.appendChild(document.createTextNode('\n'));
        }

        while (block.firstChild) {
            fragment.appendChild(block.firstChild);
        }

        block.replaceWith(fragment);
    });

    const text = (container.textContent || '').replace(/\n+$/, '');

    return text === '' ? 1 : text.split('\n').length;
};

const placeCaretAtEnd = () => {
    if (!editor.value) {
        return;
    }

    const range = document.createRange();
    range.selectNodeContents(editor.value);
    range.collapse(false);

    const selection = window.getSelection();
    selection?.removeAllRanges();
    selection?.addRange(range);
};

const sync = () => {
    const html = currentHtml();

    // Si el contenido paso el maximo (por ejemplo al pegar), se restaura lo
    // ultimo valido en lugar de guardar algo que el limite no permite.
    if (props.maxLines > 0 && linesOf(html) > props.maxLines) {
        if (editor.value) {
            editor.value.innerHTML = lastValid.value;
            placeCaretAtEnd();
        }

        return;
    }

    lastValid.value = html;
    emit('update:modelValue', html);
};

// El salto se inserta siempre como <br> (y no como <div> del navegador), asi
// el cursor se queda donde esta y la pagina publica lo pinta igual.
const onKeydown = (event) => {
    if (event.key !== 'Enter' || event.isComposing) {
        return;
    }

    event.preventDefault();

    if (props.maxLines > 0 && linesOf(currentHtml()) >= props.maxLines) {
        return;
    }

    document.execCommand('insertLineBreak');
    sync();
};

const refreshActive = () => {
    const selection = window.getSelection();

    if (!editor.value || !selection || !editor.value.contains(selection.anchorNode)) {
        return;
    }

    try {
        active.value = {
            bold: document.queryCommandState('bold'),
            italic: document.queryCommandState('italic'),
            underline: document.queryCommandState('underline'),
            strikeThrough: document.queryCommandState('strikeThrough')
        };
    } catch (error) {
        // Algunos navegadores no exponen queryCommandState: el formato sigue funcionando.
    }
};

const command = (name, value = null) => {
    if (!editor.value) {
        return;
    }

    editor.value.focus();
    document.execCommand('styleWithCSS', false, true);
    document.execCommand(name, false, value);
    sync();
    refreshActive();
};

const sizes = [12, 14, 16, 18, 20, 24, 28];

const applyFontSize = (event) => {
    const px = event.target.value;

    if (!px || !editor.value) {
        return;
    }

    editor.value.focus();
    // execCommand solo maneja tamanos 1-7; se aplica el 7 y luego se convierte
    // en un <span> con el tamano exacto en px, que es lo que acepta el saneado.
    document.execCommand('styleWithCSS', false, false);
    document.execCommand('fontSize', false, '7');

    editor.value.querySelectorAll('font[size="7"], span[style]').forEach((node) => {
        const marker = node.tagName === 'FONT' && node.getAttribute('size') === '7';
        const keyword = node.tagName === 'SPAN' && /xxx-large/i.test(node.style.fontSize || '');

        if (!marker && !keyword) {
            return;
        }

        const span = document.createElement('span');
        span.style.fontSize = px + 'px';
        span.innerHTML = node.innerHTML;
        node.replaceWith(span);
    });

    sync();
    refreshActive();
    event.target.value = '';
};

const pickColor = (event) => {
    color.value = event.target.value;
    command('foreColor', color.value);
};

const paste = (event) => {
    event.preventDefault();

    const pasted = (event.clipboardData || window.clipboardData)?.getData('text/plain') ?? '';
    // Con limite de lineas se pega en una sola linea y el usuario decide donde
    // cortar; sin limite se respeta el texto tal cual.
    const text = props.maxLines > 0 ? pasted.replace(/\r?\n+/g, ' ') : pasted;
    document.execCommand('insertText', false, text);
    sync();
};

const onSelectionChange = () => refreshActive();

onMounted(() => {
    if (editor.value) {
        editor.value.innerHTML = props.modelValue || '';
    }

    lastValid.value = normalize(props.modelValue);
    document.addEventListener('selectionchange', onSelectionChange);
});

onBeforeUnmount(() => document.removeEventListener('selectionchange', onSelectionChange));

watch(() => props.modelValue, (value) => {
    const next = value || '';

    // Mientras el usuario escribe no se reescribe el contenido (perderia el
    // cursor); el DOM ya es la fuente de verdad en ese momento.
    if (!editor.value || editor.value === document.activeElement || editor.value.innerHTML === next) {
        return;
    }

    editor.value.innerHTML = next;
    lastValid.value = normalize(next);
});
</script>

<template>
    <div class="rounded-md border border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700">
        <div class="flex flex-wrap items-center gap-1 border-b border-gray-200 px-2 py-1.5 dark:border-gray-600">
            <button type="button" title="Negrita" :class="[buttonClass, active.bold ? activeClass : '']"
                @mousedown.prevent="command('bold')">
                <b>B</b>
            </button>
            <button type="button" title="Cursiva" :class="[buttonClass, active.italic ? activeClass : '']"
                @mousedown.prevent="command('italic')">
                <i>I</i>
            </button>
            <button type="button" title="Subrayado" :class="[buttonClass, active.underline ? activeClass : '']"
                @mousedown.prevent="command('underline')">
                <u>U</u>
            </button>
            <button type="button" title="Tachado" :class="[buttonClass, active.strikeThrough ? activeClass : '']"
                @mousedown.prevent="command('strikeThrough')">
                <s>S</s>
            </button>
            <select title="Tamaño de letra"
                class="rounded border border-gray-300 bg-white px-1 py-1 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                @change="applyFontSize">
                <option value="">Tamaño</option>
                <option v-for="px in sizes" :key="px" :value="px">{{ px }} px</option>
            </select>
            <span class="mx-1 h-5 w-px bg-gray-300 dark:bg-gray-600"></span>
            <label class="inline-flex cursor-pointer items-center gap-1 rounded px-1 py-1 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
                title="Color del texto">
                <span aria-hidden="true">A</span>
                <input v-model="color" type="color"
                    class="h-5 w-6 cursor-pointer border-0 bg-transparent p-0"
                    @input="pickColor" />
            </label>
            <span class="mx-1 h-5 w-px bg-gray-300 dark:bg-gray-600"></span>
            <button type="button" title="Quitar formato" :class="buttonClass" @mousedown.prevent="command('removeFormat')">
                Quitar formato
            </button>
        </div>

        <div ref="editor" contenteditable="true" role="textbox" aria-multiline="true"
            class="price-note-editor min-h-[46px] px-3 py-2 text-sm text-gray-900 outline-none dark:text-white"
            :data-placeholder="placeholder" @input="sync" @blur="sync" @paste="paste"
            @keydown="onKeydown"></div>
    </div>
</template>

<style scoped>
.price-note-editor:empty::before {
    content: attr(data-placeholder);
    color: #9ca3af;
    pointer-events: none;
}

.price-note-editor:focus {
    box-shadow: inset 0 0 0 1px #4b5563;
}
</style>
