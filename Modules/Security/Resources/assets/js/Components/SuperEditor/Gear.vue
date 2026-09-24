<script setup>
/**
 * Toggle flotante del Modo Super Editor.
 *
 * No hay un toggle por elemento: hay uno solo, que sigue al elemento marcado
 * con data-super-el bajo el puntero. Así no se altera el DOM de las páginas
 * (nada de wrappers ni de posicionamiento relativo) y funciona igual en el menú
 * lateral, en botones de tabla, en enlaces sueltos y en los ítems de un menú
 * desplegable.
 *
 * El toggle aparece al pasar el mouse por encima del elemento y es transparente
 * al puntero (`pointer-events: none`), así que no le roba el click ni al
 * elemento que decora ni al que tiene al lado: la página se comporta como si el
 * editor no existiera. Su propio click se reconoce por geometría (useGearClick)
 * y mientras el puntero está encima de él se queda quieto, para poder clickearlo
 * en un solo movimiento.
 *
 * Dos detalles que deciden si el toggle se ve y estorba:
 *
 *   - El hueco lo elige superEditorPlacement: fuera de la caja del elemento
 *     (para no robarle clicks) y nunca encima del centro de otro control, salvo
 *     en el menú lateral, que trae el suyo declarado en data-super-place.
 *   - El z-index se calcula desde los ancestros: un ítem dentro de un menú
 *     desplegado (vue3-popper 9999, ant-design 1050) necesita un toggle por
 *     encima del menú, no debajo.
 */
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useSuperEditorStore } from 'Modules/Security/Resources/assets/js/stores/superEditor';
import { useGearClick } from 'Modules/Security/Resources/assets/js/Composables/useGearClick';
import { pointerOnGear } from 'Modules/Security/Resources/assets/js/Plugins/superEditorGearHit';
import {
    MIN_GEAR_Z,
    TOGGLE_SIZES,
    centerOf,
    fitsInBounds,
    gearZIndex,
    pickSlot,
    sizeFor,
    slotAt,
} from 'Modules/Security/Resources/assets/js/Plugins/superEditorPlacement';
import GearToggle from 'Modules/Security/Resources/assets/js/Components/SuperEditor/GearToggle.vue';
import './super-editor.css';

/**
 * Cajas que el toggle no debe pisar por el centro. Se miden a demanda (al
 * empezar a decorar un elemento), nunca en cada mousemove.
 */
const BLOCKER_SELECTOR = '[data-super-el], a[href], button, [role="button"], input, select, textarea';
const BLOCKER_BUDGET = 1200;
const BLOCKER_TTL = 150;

/**
 * Margen alrededor del toggle en el que el puntero sigue siendo «suyo».
 *
 * Cuando el hueco elegido está fuera del elemento (a su derecha), el puntero
 * tiene que cruzar unos pixels que ya no son del elemento ni del toggle: sin
 * este margen, el toggle se apagaría a medio camino y no se podría clickear.
 */
const HOT_ZONE = 10;

const store = useSuperEditorStore();

const current = ref(null);
const placement = ref(null);
const toggle = reactive({
    visible: false,
    top: 0,
    left: 0,
    width: TOGGLE_SIZES.md.width,
    height: TOGGLE_SIZES.md.height,
    zIndex: MIN_GEAR_Z,
    size: 'md',
    granted: true,
    staged: false,
});

let rafId = null;
let lastEvent = null;
let blockers = null;
let blockersAt = 0;
let rescanId = null;
let observer = null;

const active = computed(() => store.active);

/** Rectángulo del toggle tal como lo coloca este componente (sin leer el DOM). */
/**
 * Rectángulo del toggle tal como lo coloca este componente (sin leer el DOM).
 * `pad` agranda la caja: el click solo es del toggle dentro de su rectángulo
 * exacto, pero el seguimiento del puntero usa la caja con margen (HOT_ZONE).
 */
const toggleRect = (pad = 0) =>
    toggle.visible
        ? {
              top: toggle.top - pad,
              left: toggle.left - pad,
              right: toggle.left + toggle.width + pad,
              bottom: toggle.top + toggle.height + pad,
              width: toggle.width + pad * 2,
              height: toggle.height + pad * 2,
          }
        : null;

const isCandidate = (el) => !!el?.dataset?.superPerm && !el.closest('.super-editor-panel');

const invalidate = () => {
    blockers = null;
};

/** Otros controles de la página, con su caja y su centro. */
const blockerIndex = () => {
    const now = Date.now();

    if (blockers && now - blockersAt < BLOCKER_TTL) {
        return blockers;
    }

    const nodes = document.querySelectorAll(BLOCKER_SELECTOR);
    const list = [];
    const step = nodes.length > BLOCKER_BUDGET ? Math.ceil(nodes.length / BLOCKER_BUDGET) : 1;

    for (let index = 0; index < nodes.length; index += step) {
        const node = nodes[index];

        if (node.classList.contains('super-editor-gear') || node.closest('.super-editor-panel')) {
            continue;
        }

        const rect = node.getBoundingClientRect();

        if (!rect.width || !rect.height) {
            continue;
        }

        list.push({ node, rect });
    }

    blockers = list;
    blockersAt = now;

    return list;
};

const blockersFor = (el) =>
    blockerIndex()
        .filter((entry) => entry.node !== el && !el.contains(entry.node))
        .map((entry) => ({ rect: entry.rect, center: centerOf(entry.rect) }));

/**
 * Se llama solo al cambiar de elemento: elige el hueco, el apilado y el estado
 * que pinta el toggle. El hueco elegido se reutiliza en cada frame para que el
 * toggle no salte mientras el mouse se mueve dentro del mismo elemento.
 */
const measure = (el) => {
    const rect = el.getBoundingClientRect();
    const sizeName = sizeFor(rect);
    const declared = el.dataset.superPlace;

    placement.value =
        declared && slotAt(rect, declared, sizeName)
            ? { place: declared, sizeName }
            : {
                  place: pickSlot(rect, sizeName, blockersFor(el), {
                      width: window.innerWidth,
                      height: window.innerHeight,
                  }).place,
                  sizeName,
              };

    toggle.zIndex = gearZIndex(el);
    toggle.granted = el.dataset.superGranted !== '0';
    toggle.staged = store.hasDraft(el.dataset.superPerm);
};

const clamp = (value, extent, size) =>
    Math.min(Math.max(Math.round(value), 4), Math.max(4, extent - size - 4));

const reposition = () => {
    const el = current.value;
    const chosen = placement.value;

    if (!el || !chosen || !el.isConnected) {
        clear();

        return;
    }

    const bounds = { width: window.innerWidth, height: window.innerHeight };
    let slot = slotAt(el.getBoundingClientRect(), chosen.place, chosen.sizeName);

    // Un hueco que no cabe en la ventana se recortaría encima del elemento: en
    // ese caso se vuelve a elegir hueco con la ventana como límite.
    if (!fitsInBounds(slot, bounds)) {
        measure(el);
        slot = slotAt(el.getBoundingClientRect(), placement.value.place, placement.value.sizeName);
    }

    if (!slot) {
        clear();

        return;
    }

    const size = TOGGLE_SIZES[chosen.sizeName] ?? TOGGLE_SIZES.md;

    toggle.width = size.width;
    toggle.height = size.height;
    toggle.size = chosen.sizeName;
    toggle.top = clamp(slot.top, window.innerHeight, size.height);
    toggle.left = clamp(slot.left, window.innerWidth, size.width);
    toggle.visible = true;
};

const clear = () => {
    current.value?.classList.remove('super-editor-hover');
    current.value = null;
    placement.value = null;
    toggle.visible = false;
};

const sync = (event) => {
    if (!active.value || store.panel) {
        clear();

        return;
    }

    // El puntero está encima del toggle (o cruzando su margen): se queda donde
    // está (y sobre el mismo elemento) aunque debajo haya otro elemento marcado.
    if (pointerOnGear(event, toggleRect(HOT_ZONE))) {
        return;
    }

    const target = event?.target;
    const el = target instanceof Element ? target.closest('[data-super-el]') : null;

    if (!isCandidate(el)) {
        clear();

        return;
    }

    if (current.value !== el) {
        current.value?.classList.remove('super-editor-hover');
        current.value = el;
        el.classList.add('super-editor-hover');
        measure(el);
    }

    reposition();
};

const onMouseMove = (event) => {
    lastEvent = event;

    if (rafId) {
        return;
    }

    rafId = window.requestAnimationFrame(() => {
        rafId = null;
        sync(lastEvent);
    });
};

/**
 * Antes de que se resuelva un click, el toggle tiene que estar donde toca: si
 * se deja el seguimiento solo al mousemove, un click rápido (o un navegador que
 * retrasa los frames) se decide contra el rectángulo anterior y puede abrir el
 * panel del elemento que estaba debajo del puntero un frame antes. El
 * pointerdown siempre trae coordenadas y siempre precede al click, así que aquí
 * se sincroniza de forma síncrona.
 */
const onPointerDown = (event) => {
    sync(event);
};

/**
 * El DOM cambió (se abrió un menú desplegable, navegó Inertia, se expandió el
 * menú lateral): hay que volver a medir, porque los huecos y el apilado ya no
 * son los mismos.
 */
const rescan = () => {
    invalidate();

    if (!current.value) {
        return;
    }

    if (!current.value.isConnected) {
        clear();

        return;
    }

    measure(current.value);
    reposition();
};

const scheduleRescan = () => {
    if (rescanId) {
        window.clearTimeout(rescanId);
    }

    rescanId = window.setTimeout(() => {
        rescanId = null;
        rescan();
    }, 120);
};

const onScroll = () => {
    invalidate();

    if (current.value) {
        reposition();
    }
};

const onTransitionEnd = (event) => {
    // Solo lo que mueve o redimensiona cajas (colapso del menú, scroll suave
    // del perfect-scrollbar). Las transiciones de color no cambian geometría.
    if (['height', 'width', 'max-height', 'transform'].includes(event.propertyName)) {
        scheduleRescan();
    }
};

const openPanel = () => {
    const el = current.value;

    if (!el) {
        return;
    }

    const rect = el.getBoundingClientRect();

    store.openPanel({
        permission: el.dataset.superPerm,
        label: el.dataset.superLabel,
        kind: el.dataset.superKind,
        url: window.location.href,
        granted: el.dataset.superGranted === '1',
        rect: {
            top: rect.top,
            left: rect.left,
            right: rect.right,
            bottom: rect.bottom,
            width: rect.width,
            height: rect.height,
        },
    });
};

useGearClick({
    rect: () => toggleRect(),
    active: () => active.value && toggle.visible && !!current.value && !store.panel,
    onOwnClick: openPanel,
});

onMounted(() => {
    document.addEventListener('mousemove', onMouseMove, { passive: true });
    document.addEventListener('pointerdown', onPointerDown, true);
    window.addEventListener('scroll', onScroll, true);
    window.addEventListener('resize', scheduleRescan);
    document.addEventListener('transitionend', onTransitionEnd, true);

    observer = new MutationObserver(scheduleRescan);
    observer.observe(document.body, { childList: true, subtree: true });
});

onBeforeUnmount(() => {
    document.removeEventListener('mousemove', onMouseMove, { passive: true });
    document.removeEventListener('pointerdown', onPointerDown, true);
    window.removeEventListener('scroll', onScroll, true);
    window.removeEventListener('resize', scheduleRescan);
    document.removeEventListener('transitionend', onTransitionEnd, true);

    observer?.disconnect();

    if (rafId) {
        window.cancelAnimationFrame(rafId);
    }

    if (rescanId) {
        window.clearTimeout(rescanId);
    }
});
</script>

<template>
    <GearToggle
        :visible="active && toggle.visible && !store.panel"
        :top="toggle.top"
        :left="toggle.left"
        :z-index="toggle.zIndex"
        :size="toggle.size"
        :granted="toggle.granted"
        :staged="toggle.staged"
    />
</template>
