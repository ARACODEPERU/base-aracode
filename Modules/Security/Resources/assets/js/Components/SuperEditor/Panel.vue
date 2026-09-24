<script setup>
/**
 * Panel "Configurar permisos de acceso".
 *
 * Es la versión contextual del editor de roles: en lugar de recorrer cientos
 * de permisos, aquí se editan los del elemento que se acaba de clickear (su
 * permiso y sus hermanos del mismo prefijo, agrupados por acción).
 *
 * Guardar NO aplica nada: deja el cambio en el borrador del servidor. La
 * aplicación real ocurre al salir del modo confirmando la contraseña.
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useSuperEditorStore } from 'Modules/Security/Resources/assets/js/stores/superEditor';

const PANEL_WIDTH = 372;
// Cuando la ventana es estrecha el panel se achica hasta aquí en vez de salirse.
const PANEL_MIN_WIDTH = 260;
// Alto estimado del panel (cabecera + acciones + lista de roles acotada). Se usa
// solo para colocarlo en el primer render; después manda el alto medido.
const PANEL_HEIGHT = 580;
// Alto mínimo con el que todavía se lee el panel. Es una red de seguridad para
// ventanas diminutas: por debajo de esto no se achica más, pero nunca se pasa
// del borde inferior de la ventana.
const PANEL_MIN_HEIGHT = 120;
// Separación entre el panel y lo que ya está ocupado (menú lateral, bordes).
const CHROME_GAP = 8;
const EDGE_GAP = 12;
// Menú lateral en las dos maquetas del panel (Vristo usa `nav.sidebar`, la otra
// un `aside` que se desliza): cualquiera de los dos tapa el panel si lo pisa.
const CHROME_SELECTOR = '.sidebar, aside, nav[class*="sidebar"]';

const store = useSuperEditorStore();

/**
 * Referencias del panel y su entorno.
 *
 * El panel es `position: fixed` con coordenadas calculadas a mano, así que hay
 * tres cosas que el CSS no puede resolver solo y se miden aquí:
 *
 *   - `.sidebar` (menú lateral) es `z-50` y el panel `z-19`: si el panel cae
 *     debajo del menú, el menú lo tapa. Por eso el panel nunca se coloca a la
 *     izquierda del borde derecho del menú visible, y se recoloca cuando el
 *     menú se despliega o se colapsa (300ms de animación incluidos).
 *   - La ventana. El panel no debe salirse por abajo: su alto se acota al hueco
 *     real disponible y el cuerpo pasa a tener scroll, con el pie siempre a la
 *     vista.
 *   - El alto real del panel, medido con ResizeObserver, para no colocarlo
 *     usando una estimación.
 */
const panelEl = ref(null);
const measuredHeight = ref(0);
const chromeRight = ref(0);
const viewport = ref({ width: 0, height: 0 });

let resizeObserver = null;
let chromeObserver = null;
let chromeEls = [];

const activeTab = ref(0);
const activePermission = ref(null);
const roleFilter = ref('');
const creating = ref(false);
const createError = ref('');

const panel = computed(() => store.panel);
const entries = computed(() => panel.value?.actions ?? []);
const entry = computed(() => entries.value[activeTab.value] ?? entries.value[0] ?? null);

/**
 * Permiso concreto que se está editando dentro de la acción activa. Una misma
 * acción puede tener varias formas en la base (`usuarios` y `usuarios_ver` son
 * las dos "Ver"), así que la pestaña edita una y ofrece las otras a un click.
 */
const activeCandidate = computed(() => {
    const candidates = entry.value?.candidates ?? [];

    return candidates.find((item) => item.permission === activePermission.value) ?? candidates[0] ?? null;
});

const activePerm = computed(() => activeCandidate.value?.permission ?? entry.value?.permission ?? '');

const activeEnforced = computed(() => !!activeCandidate.value?.enforced);

/**
 * Al abrirse el panel se muestra la acción del propio elemento (engrane en el
 * botón "NUEVO" -> pestaña "Crear"), y al cambiar de acción se vuelve a la
 * preferencia que calculó el servidor.
 */
watch(
    [entries, () => panel.value?.permission],
    () => {
        activePermission.value = null;

        const ownIndex = entries.value.findIndex((item) => item.own);

        activeTab.value = ownIndex === -1 ? 0 : ownIndex;
    }
);

/**
 * El panel vive por debajo del chrome de la app (ver la clase del contenedor),
 * así que no debe empezar detrás del header pegajoso: si el elemento decorado
 * está en la barra superior, el panel arranca justo debajo de ella.
 */
const minTop = () => {
    const headerBottom = document.querySelector('header')?.getBoundingClientRect().bottom ?? 0;

    return Math.max(EDGE_GAP, Math.round(headerBottom) + CHROME_GAP);
};

/**
 * Primer x libre a la derecha del menú lateral (o el margen de la ventana).
 *
 * Si a la derecha del menú no cabe ni el ancho mínimo del panel, se renuncia al
 * límite: en una ventana así de estrecha el panel no cabría en ningún sitio y es
 * preferible que se monte sobre el menú a que se salga de la pantalla.
 */
const minLeft = () => {
    const limit = Math.max(EDGE_GAP, chromeRight.value + CHROME_GAP);

    return viewport.value.width - limit - EDGE_GAP >= PANEL_MIN_WIDTH ? limit : EDGE_GAP;
};

/** Ancho disponible para el panel sin salirse de la ventana ni pisar el menú. */
const width = computed(() => {
    const available = viewport.value.width - minLeft() - EDGE_GAP;

    return Math.max(PANEL_MIN_WIDTH, Math.min(PANEL_WIDTH, Math.round(available)));
});

/** Hueco vertical real desde el header hasta el borde inferior. */
const maxHeight = computed(() =>
    Math.max(PANEL_MIN_HEIGHT, Math.round(viewport.value.height - minTop() - EDGE_GAP))
);

const style = computed(() => {
    const rect = panel.value?.rect;
    const floor = minTop();
    const height = Math.min(measuredHeight.value || PANEL_HEIGHT, maxHeight.value);
    const top = rect
        ? Math.min(
              Math.max(rect.top, floor),
              Math.max(floor, Math.round(viewport.value.height - height - EDGE_GAP))
          )
        : floor;

    if (!rect) {
        return { top: `${top}px`, left: '50%', maxHeight: `${maxHeight.value}px`, width: `${width.value}px` };
    }

    const min = minLeft();
    let left = rect.right + 48;

    if (left + width.value > viewport.value.width - EDGE_GAP) {
        left = rect.left - width.value - 48;
    }

    // Nunca por debajo del menú lateral (z-50 lo taparía) ni fuera de la ventana.
    left = Math.min(Math.max(left, min), Math.max(min, viewport.value.width - width.value - EDGE_GAP));

    return { top: `${top}px`, left: `${left}px`, maxHeight: `${maxHeight.value}px`, width: `${width.value}px` };
});

const measure = () => {
    measuredHeight.value = panelEl.value?.offsetHeight ?? 0;
};

const readViewport = () => {
    viewport.value = { width: window.innerWidth, height: window.innerHeight };
};

/**
 * El menú lateral se despliega con una transición de 300ms, así que se observa
 * su ancho: mientras se abre, el panel se corre a la derecha en cada frame en
 * lugar de quedarse escondido detrás. Solo cuenta el menú que realmente ocupa
 * una franja en el borde izquierdo (si está fuera de pantalla, no cuenta).
 */
const readChrome = () => {
    let right = 0;

    for (const el of chromeEls) {
        const box = el.getBoundingClientRect();

        if (box.width > 0 && box.height > 100 && box.right > 0 && box.left < window.innerWidth * 0.5) {
            right = Math.max(right, box.right);
        }
    }

    chromeRight.value = Math.round(right);
};

const measureEverything = () => {
    readViewport();
    readChrome();
    measure();
};

onMounted(() => {
    chromeEls = [...document.querySelectorAll(CHROME_SELECTOR)];
    measureEverything();

    if (typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(() => measure());
        observePanel();

        if (chromeEls.length) {
            chromeObserver = new ResizeObserver(() => readChrome());
            chromeEls.forEach((el) => chromeObserver.observe(el));
        }
    }

    window.addEventListener('resize', measureEverything);
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    chromeObserver?.disconnect();
    window.removeEventListener('resize', measureEverything);
});

/** El panel se monta con la primera respuesta del servidor: se observa al aparecer. */
const observePanel = () => {
    if (panelEl.value) {
        resizeObserver?.observe(panelEl.value);
        measure();
    }
};

watch(panelEl, (el) => {
    if (el) {
        observePanel();
    }
});

const protectedList = computed(() =>
    (store.protectedPermissions || []).map((name) => String(name).toLowerCase())
);

const isProtected = (permission) => {
    const name = String(permission || '').toLowerCase();

    return name.startsWith('super_editor') || protectedList.value.includes(name);
};

const rows = computed(() => {
    if (!panel.value || !entry.value) {
        return [];
    }

    const search = roleFilter.value.trim().toLowerCase();
    const permission = activePerm.value;

    return (panel.value.roles || [])
        .filter((role) => !search || String(role.name).toLowerCase().includes(search))
        .map((role) => {
            const current = !!role.current?.[permission];
            const draft = role.staged?.[permission];
            const checked = draft === null || draft === undefined ? current : !!draft;

            return {
                id: role.id,
                name: role.name,
                current,
                checked,
                staged: draft !== null && draft !== undefined,
                // El rol autorizado no puede perder un permiso protegido: el
                // backend lo rechaza y aquí se bloquea la casilla.
                locked: checked && isProtected(permission) && role.name === store.role,
            };
        });
});

const dirtyForElement = computed(() => (panel.value?.staged ?? []).length);

const totalDirty = computed(() => store.dirty);

const toggle = async (row) => {
    if (row.locked || !activePerm.value) {
        return;
    }

    await store.stageChange(activePerm.value, row.id, !row.checked);
};

const revert = async () => {
    await store.revertElement();
};

const createPermission = async () => {
    if (!panel.value?.permission) {
        return;
    }

    creating.value = true;
    createError.value = '';

    const error = await store.createPermission(panel.value.permission);

    creating.value = false;

    if (error) {
        createError.value = error;
    }
};
</script>

<template>
    <div
        v-if="panel"
        ref="panelEl"
        class="super-editor-panel fixed z-[19] flex max-w-[calc(100vw-24px)] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800"
        :style="style"
    >
        <!-- Encabezado -->
        <div class="shrink-0 border-b border-slate-100 px-4 py-3 dark:border-slate-700">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">
                        Configurar permisos de acceso
                    </p>
                    <p class="mt-1 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                        {{ panel.kind || 'elemento' }} «{{ panel.label }}»
                    </p>
                    <p class="mt-0.5 truncate font-mono text-[10px] text-slate-400">
                        {{ panel.permission }}
                    </p>
                </div>

                <button
                    type="button"
                    class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700"
                    title="Cerrar"
                    @click="store.closePanel()"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                <span
                    class="super-editor-badge bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200"
                >
                    {{ totalDirty }} sin aplicar
                </span>
                <span
                    v-if="panel.granted === false"
                    class="super-editor-badge bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-200"
                    title="Tu rol no tiene acceso a este elemento: se muestra solo para poder configurarlo"
                >
                    sin acceso con tu rol
                </span>
                <span
                    v-if="dirtyForElement > 0"
                    class="super-editor-badge bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-200"
                >
                    {{ dirtyForElement }} con borrador
                </span>
            </div>
        </div>

        <!-- Cuerpo con scroll propio: el panel nunca crece más que la ventana,
             así que el pie (Deshacer / Cerrar / Guardar) siempre queda visible. -->
        <div class="min-h-0 flex-1 overflow-y-auto">
        <!-- Permiso inexistente -->
        <div v-if="panel.exists === false" class="border-b border-amber-100 bg-amber-50/70 px-4 py-3 dark:border-amber-500/20 dark:bg-amber-500/10">
            <p class="text-xs text-amber-800 dark:text-amber-200">
                Este permiso todavía no existe en la base de datos. Crearlo permite concedérselo a los roles
                (empezará concedido al rol {{ store.role }}).
            </p>
            <button
                type="button"
                class="mt-2 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-amber-700 disabled:opacity-50"
                :disabled="creating"
                @click="createPermission"
            >
                {{ creating ? 'Creando…' : 'Crear permiso' }}
            </button>
            <p v-if="createError" class="mt-2 text-xs text-rose-600 dark:text-rose-300">{{ createError }}</p>
        </div>

        <!-- Pestañas: una por acción, nunca dos con la misma etiqueta -->
        <div v-if="entries.length > 1" class="flex flex-wrap gap-1 border-b border-slate-100 px-3 py-2 dark:border-slate-700">
            <button
                v-for="(item, index) in entries"
                :key="item.action"
                type="button"
                class="rounded-lg px-2.5 py-1 text-xs font-semibold transition"
                :class="
                    index === activeTab
                        ? 'bg-blue-600 text-white'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600'
                "
                :title="item.label + ' · ' + item.permission"
                @click="activeTab = index"
            >
                {{ item.label }}
            </button>
        </div>

        <!-- Qué permiso se edita en esta acción y si alguien lo exige -->
        <div v-if="entry" class="border-b border-slate-100 px-4 py-2.5 dark:border-slate-700">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">
                    Acción {{ entry.label }}
                </span>
                <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-600 dark:bg-slate-700 dark:text-slate-200">
                    {{ activePerm }}
                </code>
            </div>

            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                <span
                    v-if="activePerm === panel.permission"
                    class="super-editor-badge bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-200"
                >
                    la usa este elemento
                </span>
                <span
                    v-if="activeEnforced"
                    class="super-editor-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200"
                    title="Rutas de la aplicación exigen este permiso: concederlo o quitarlo también abre o cierra esas rutas"
                >
                    exigido por rutas
                </span>
                <span
                    v-else
                    class="super-editor-badge bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-200"
                    title="Ninguna ruta registrada pide este permiso: cambiarlo solo afecta a lo que el frontend muestre con él (como este elemento)"
                >
                    sin ruta que lo exija
                </span>
            </div>

            <!-- Otras formas de decir la misma acción (p. ej. usuarios_ver) -->
            <div v-if="entry.candidates.length > 1" class="mt-2 flex flex-wrap gap-1">
                <button
                    v-for="candidate in entry.candidates"
                    :key="candidate.permission"
                    type="button"
                    class="rounded-md border px-2 py-0.5 font-mono text-[10px] transition"
                    :class="
                        candidate.permission === activePerm
                            ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-500/20 dark:text-blue-100'
                            : 'border-slate-200 text-slate-500 hover:border-blue-300 hover:text-blue-600 dark:border-slate-600 dark:text-slate-300'
                    "
                    :title="candidate.enforced ? 'Permiso exigido por rutas de la aplicación' : 'Permiso que ninguna ruta registrada pide'"
                    @click="activePermission = candidate.permission"
                >
                    {{ candidate.permission }}<span v-if="!candidate.enforced"> · sin uso</span>
                </button>
            </div>
        </div>

        <!-- Cuerpo -->
        <div class="px-4 py-3">
            <!-- Ocupa el mismo alto que el bloque de acciones y la lista reales:
                 si no, el panel crecía al llegar la respuesta y los botones del
                 pie se movían justo cuando el usuario iba a clickearlos. -->
            <div v-if="store.panelLoading" class="space-y-2">
                <div class="h-[52px] animate-pulse rounded-lg bg-slate-100 dark:bg-slate-700"></div>
                <div class="h-9 animate-pulse rounded-lg bg-slate-100 dark:bg-slate-700"></div>
                <div v-for="n in 4" :key="n" class="h-8 animate-pulse rounded-lg bg-slate-100 dark:bg-slate-700"></div>
            </div>

            <template v-else>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                    Roles que pueden {{ entry?.phrase || 'usar' }} este elemento
                </p>

                <input
                    v-model="roleFilter"
                    type="text"
                    placeholder="Buscar rol…"
                    class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                />

                <ul class="mt-2 max-h-56 space-y-1 overflow-y-auto pr-1">
                    <li v-for="row in rows" :key="row.id">
                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 transition hover:bg-slate-50 dark:hover:bg-slate-700/60"
                            :class="{
                                'cursor-not-allowed opacity-60': row.locked,
                                'bg-amber-50/70 dark:bg-amber-500/10': row.staged,
                            }"
                        >
                            <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                :checked="row.checked"
                                :disabled="row.locked || store.busy"
                                @change="toggle(row)"
                            />
                            <span class="flex min-w-0 flex-1 items-center justify-between gap-2">
                                <span class="truncate text-xs font-medium text-slate-700 dark:text-slate-200">
                                    {{ row.name }}
                                </span>
                                <span class="flex shrink-0 items-center gap-1">
                                    <span
                                        v-if="row.staged"
                                        class="super-editor-badge bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200"
                                    >
                                        en borrador
                                    </span>
                                    <span
                                        v-else-if="row.current"
                                        class="super-editor-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200"
                                    >
                                        concedido
                                    </span>
                                    <span v-if="row.locked" title="Permiso protegido: no se puede quitar al rol autorizado">
                                        🔒
                                    </span>
                                </span>
                            </span>
                        </label>
                    </li>
                </ul>

                <p v-if="rows.length === 0" class="mt-2 text-xs text-slate-400">Ningún rol coincide con la búsqueda.</p>

                <p v-if="store.panelError" class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-200">
                    {{ store.panelError }}
                </p>

                <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-[11px] leading-relaxed text-slate-500 dark:bg-slate-900/60 dark:text-slate-400">
                    Los cambios quedan en borrador. Al salir del Modo Super Editor se aplican todos juntos,
                    pidiendo tu contraseña otra vez, y quedan registrados en el historial.
                </p>
            </template>
        </div>

        </div>

        <!-- Pie -->
        <div class="flex shrink-0 items-center justify-between gap-2 border-t border-slate-100 px-4 py-3 dark:border-slate-700">
            <button
                type="button"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-700"
                :disabled="dirtyForElement === 0 || store.busy"
                @click="revert"
            >
                Deshacer
            </button>

            <div class="flex items-center gap-2">
                <!-- Cada casilla se guarda en el borrador al marcarla, así que
                     esto solo cierra el panel. Se llama "Cerrar" y no
                     "Cancelar" porque cancelar promete deshacer, y deshacer es
                     el botón "Deshacer" de la izquierda. -->
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700"
                    title="Cerrar el panel. Lo marcado sigue en el borrador; para retirarlo usa Deshacer"
                    @click="store.closePanel()"
                >
                    Cerrar
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700"
                    @click="store.closePanel()"
                >
                    Guardar
                </button>
            </div>
        </div>
    </div>
</template>
