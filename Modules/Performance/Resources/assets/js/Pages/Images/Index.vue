<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import ModalLarge from '@/Components/ModalLarge.vue';
import AyudaTip from '../../Components/AyudaTip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faBoxesStacked,
    faCircleInfo,
    faClone,
    faCode,
    faCompress,
    faFilter,
    faImage,
    faImages,
    faRotate,
    faRotateLeft,
    faTriangleExclamation,
    faUpRightFromSquare,
    faWeightScale,
} from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    images: { type: Object, default: () => ({ data: [], total: 0, page: 1, last_page: 1 }) },
    summary: { type: Object, default: () => ({}) },
    tabs: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ tab: 'paginas', search: '', min_kb: 0, sort: 'bytes' }) },
    presets: { type: Array, default: () => [1920, 1280, 800] },
    alerts: { type: Array, default: () => [] },
    limits: {
        type: Object,
        default: () => ({ backup_days: 30, new_days: 7, webp_widths: [0, 1280, 800], gif_supported: false }),
    },
});

const filtros = ref({
    tab: props.filters.tab,
    search: props.filters.search,
    min_kb: props.filters.min_kb,
    sort: props.filters.sort,
});

const seleccionados = ref([]);
const modal = ref(false);
const procesando = ref(false);
const alertasVisibles = ref(props.alerts.length > 0);
const webpModal = ref(false);
const webpResumen = ref({ files: [], saved: 0, results: [] });

const form = ref({
    items: [],
    mode: 'resize',
    width: 1280,
    height: null,
    quality: 82,
});

const alerta = (title, text, icon = 'info', options = {}) => {
    if (typeof window.alertaSistema === 'function') {
        return window.alertaSistema(title, text, icon, options);
    }

    return Promise.resolve({ isConfirmed: true });
};

const formatoBytes = (bytes) => {
    const valor = Number(bytes || 0);

    if (valor < 1024) return `${valor} B`;
    if (valor < 1024 * 1024) return `${(valor / 1024).toFixed(0)} KB`;

    return `${(valor / (1024 * 1024)).toFixed(2)} MB`;
};

const esPesada = (image) => Number(image.bytes) >= Number(props.summary.threshold_kb || 300) * 1024;

const enCuarentena = computed(() => filtros.value.tab === 'cuarentena');

// Ayuda de cada pestaña: qué agrupa y qué pasa si se usa.
const ayudasTabs = {
    todos: 'Todas las imágenes del inventario, se usen o no en el sitio.',
    paginas: 'Imágenes que aparecen en las páginas públicas del sitio.',
    cursos: 'Imágenes usadas por los cursos.',
    blog: 'Imágenes de los artículos del blog.',
    productos: 'Imágenes de los productos de la tienda.',
    otros: 'Imágenes que se usan en el CMS u otras secciones.',
    orfanas:
        'El archivo existe en disco, pero ninguna vista, hoja de estilos ni registro de la base de datos lo menciona. No se muestra en ninguna página; suele ser un resto de versiones anteriores. No se borra: se puede mover a cuarentena.',
    duplicadas:
        'Archivos con el mismo contenido (mismo hash) en varias rutas. Conviene conservar uno y mover el resto a cuarentena.',
    gif: 'GD no conserva la animación, por eso estos archivos no se comprimen automáticamente; se convierten cuando hay Imagick o gifsicle.',
    cuarentena:
        'Archivos que se sacaron de la carpeta pública. Ya no se muestran en el sitio y se pueden restaurar cuando quieras.',
};

const ayudaTab = (key) => ayudasTabs[key] || 'Imágenes agrupadas por la sección donde se muestran.';

const etiquetaTabActual = computed(
    () => tabsVisibles.value.find((tab) => tab.key === filtros.value.tab)?.label || filtros.value.tab
);

const ayudaTabActual = computed(() => ayudaTab(filtros.value.tab));

const tabsVisibles = computed(() => {
    const base = ['paginas', 'cursos', 'blog', 'productos', 'otros'];

    return [
        { key: 'todos', label: 'Todas', count: props.summary.total || 0, bytes: props.summary.bytes || 0 },
        ...props.tabs.filter((tab) => base.includes(tab.key) || tab.count > 0),
    ];
});

const idsPagina = computed(() => (props.images.data || []).map((image) => image.id));

const todosSeleccionados = computed(
    () => idsPagina.value.length > 0 && idsPagina.value.every((id) => seleccionados.value.includes(id))
);

const seleccionDetalle = computed(() =>
    (props.images.data || []).filter((image) => form.value.items.includes(image.id))
);

const primeraSeleccionada = computed(() => seleccionDetalle.value[0] || null);

const cambioEstimado = computed(() => {
    const image = primeraSeleccionada.value;

    if (!image || !image.width || !form.value.width || form.value.mode !== 'resize') return null;

    const ratio = form.value.width / image.width;

    if (ratio >= 1) return null;

    return {
        width: Math.round(image.width * ratio),
        height: Math.round(image.height * ratio),
    };
});

const snippetWebp = computed(() => {
    const result = (webpResumen.value.results || []).find((item) => (item.files || []).length);

    if (!result) return '';

    const image = props.images.data.find((item) => item.id === result.path);
    const sources = result.files.map((file) => `${file.url} ${file.width}w`).join(', ');
    const dimensions = image?.width ? ` width="${image.width}" height="${image.height}"` : '';

    return `<picture>\n  <source type="image/webp" srcset="${sources}" sizes="(max-width: 768px) 100vw, 1280px">\n  <img src="${image?.url || ''}" alt="" loading="lazy"${dimensions}>\n</picture>`;
});

const aplicarFiltros = (page = 1, extra = {}) => {
    router.get(
        route('performance_images_index'),
        {
            tab: filtros.value.tab,
            search: filtros.value.search,
            min_kb: filtros.value.min_kb,
            sort: filtros.value.sort,
            page,
            ...extra,
        },
        { preserveScroll: true, preserveState: true }
    );
};

const cambiarTab = (tab) => {
    filtros.value.tab = tab;
    seleccionados.value = [];
    aplicarFiltros(1);
};

const irPagina = (page) => {
    if (page < 1 || page > props.images.last_page) return;

    seleccionados.value = [];
    aplicarFiltros(page);
};

const actualizarInventario = () => aplicarFiltros(1, { refresh: 1 });

const toggleTodos = () => {
    if (todosSeleccionados.value) {
        seleccionados.value = seleccionados.value.filter((id) => !idsPagina.value.includes(id));

        return;
    }

    seleccionados.value = Array.from(new Set([...seleccionados.value, ...idsPagina.value]));
};

const abrirModal = (image = null, mode = 'resize') => {
    const items = image ? [image.id] : [...seleccionados.value];

    if (!items.length) {
        alerta('Sin selección', 'Elige al menos una imagen para procesar.', 'info');

        return;
    }

    form.value = {
        items,
        mode,
        width: 1280,
        height: null,
        quality: 82,
    };

    modal.value = true;
};

const cerrarModal = () => {
    if (procesando.value) return;

    modal.value = false;
};

const aplicarPreset = (ancho) => {
    form.value.width = ancho;
    form.value.height = null;
};

const ejecutarOptimizacion = async () => {
    if (procesando.value) return;

    if (form.value.mode === 'resize' && !form.value.width && !form.value.height) {
        alerta('Falta el tamaño', 'Indica un ancho o un alto máximo.', 'warning');

        return;
    }

    const comprimir = form.value.mode === 'compress';
    const total = form.value.items.length;

    const confirmacion = await alerta(
        comprimir ? 'Comprimir imágenes' : 'Redimensionar imágenes',
        comprimir
            ? `Se recomprimirán ${total} archivo(s) sin cambiar su resolución, conservando el nombre. Se guarda una copia antes de cada cambio.`
            : `Se sobrescribirán ${total} archivo(s) con el mismo nombre. Se guarda una copia de seguridad antes de cada cambio.`,
        'warning',
        {
            duration: 0,
            showCancelButton: true,
            confirmButtonText: comprimir ? 'Sí, comprimir' : 'Sí, redimensionar',
            cancelButtonText: 'Cancelar',
        }
    );

    if (!confirmacion?.isConfirmed) return;

    procesando.value = true;

    try {
        const { data } = await axios.post(route('performance_images_optimize'), {
            items: form.value.items,
            mode: form.value.mode,
            width: form.value.mode === 'resize' ? form.value.width || null : null,
            height: form.value.mode === 'resize' ? form.value.height || null : null,
            quality: form.value.quality || 82,
        });

        const results = data.results || [];
        const cambiadas = results.filter((r) => r.status === 'resized');
        const omitidas = results.filter((r) => r.status === 'skipped' || r.status === 'unchanged');
        const errores = results.filter((r) => r.status === 'error');
        const ahorro = cambiadas.reduce(
            (suma, r) => suma + Math.max(0, (r.before?.bytes || 0) - (r.after?.bytes || 0)),
            0
        );

        let mensaje = `${cambiadas.length} imagen(es) procesadas. Ahorro: ${formatoBytes(ahorro)}.`;

        if (omitidas.length) mensaje += ` ${omitidas.length} sin cambios.`;
        if (errores.length) mensaje += ` ${errores.length} con error.`;

        modal.value = false;
        seleccionados.value = [];

        alerta(errores.length ? 'Proceso con avisos' : 'Imágenes actualizadas', mensaje, errores.length ? 'warning' : 'success');

        router.reload({ only: ['images', 'summary', 'tabs'] });
    } catch (error) {
        alerta('Error', error?.response?.data?.message || 'No se pudieron procesar las imágenes.', 'error');
    } finally {
        procesando.value = false;
    }
};

const generarWebp = async (items = null) => {
    const lista = items ?? [...seleccionados.value];

    if (!lista.length) {
        alerta('Sin selección', 'Elige al menos una imagen para generar WebP.', 'info');

        return;
    }

    const confirmacion = await alerta(
        'Generar variantes WebP',
        `Se crearán variantes WebP junto a ${lista.length} imagen(es) sin modificar el original. Después podrás usar srcset en las vistas.`,
        'info',
        { duration: 0, showCancelButton: true, confirmButtonText: 'Generar', cancelButtonText: 'Cancelar' }
    );

    if (!confirmacion?.isConfirmed) return;

    procesando.value = true;

    try {
        const { data } = await axios.post(route('performance_images_webp'), { items: lista });
        const results = data.results || [];
        const files = results.flatMap((r) => r.files || []);
        const saved = results.reduce((suma, r) => suma + (r.saved || 0), 0);

        if (!files.length) {
            const motivo = results.find((r) => r.message)?.message || 'No se generó ninguna variante.';

            alerta('Sin cambios', motivo, 'warning');

            return;
        }

        webpResumen.value = { files, saved, results, errors: results.filter((r) => r.status === 'error').length };
        webpModal.value = true;
        router.reload({ only: ['images', 'summary', 'tabs'] });
    } catch (error) {
        alerta('Error', error?.response?.data?.message || 'No se pudieron generar las variantes WebP.', 'error');
    } finally {
        procesando.value = false;
    }
};

const copiarSnippet = async () => {
    try {
        await navigator.clipboard.writeText(snippetWebp.value);
        alerta('Copiado', 'El fragmento se copió al portapapeles.', 'success');
    } catch (error) {
        alerta('No se pudo copiar', 'Selecciona el texto y cópialo manualmente.', 'warning');
    }
};

const moverCuarentena = async (items = null) => {
    const lista = items ?? [...seleccionados.value];

    if (!lista.length) {
        alerta('Sin selección', 'Elige al menos una imagen para mover a cuarentena.', 'info');

        return;
    }

    const confirmacion = await alerta(
        'Mover a cuarentena',
        `Se moverán ${lista.length} archivo(s) fuera de la carpeta pública. Podrás restaurarlos desde la pestaña Cuarentena.`,
        'warning',
        { duration: 0, showCancelButton: true, confirmButtonText: 'Mover', cancelButtonText: 'Cancelar' }
    );

    if (!confirmacion?.isConfirmed) return;

    await ejecutarAccion('performance_images_quarantine', lista, 'Imágenes en cuarentena', 'Se movieron');
};

const restaurarCuarentena = async (items = null) => {
    const lista = items ?? [...seleccionados.value];

    if (!lista.length) {
        alerta('Sin selección', 'Elige al menos una imagen para restaurar.', 'info');

        return;
    }

    await ejecutarAccion('performance_images_quarantine_restore', lista, 'Imágenes restauradas', 'Se restauraron');
};

const restaurarBackup = async (items = null) => {
    const lista = items ?? [...seleccionados.value];

    if (!lista.length) {
        alerta('Sin selección', 'Elige al menos una imagen para restaurar su copia.', 'info');

        return;
    }

    const confirmacion = await alerta(
        'Restaurar copia de seguridad',
        `Se reemplazará ${lista.length} archivo(s) con el original guardado. El estado actual también se respalda.`,
        'question',
        { duration: 0, showCancelButton: true, confirmButtonText: 'Restaurar', cancelButtonText: 'Cancelar' }
    );

    if (!confirmacion?.isConfirmed) return;

    await ejecutarAccion('performance_images_restore', lista, 'Original restaurado', 'Se restauraron');
};

const ejecutarAccion = async (endpoint, items, titulo, verbo) => {
    if (procesando.value) return;

    procesando.value = true;

    try {
        const { data } = await axios.post(route(endpoint), { items });
        const results = data.results || [];
        const errores = results.filter((r) => r.status === 'error');
        const ok = results.length - errores.length;

        let mensaje = `${verbo} ${ok} archivo(s).`;
        if (errores.length) mensaje += ` ${errores.length} con error.`;

        seleccionados.value = [];
        alerta(errores.length ? 'Proceso con avisos' : titulo, mensaje, errores.length ? 'warning' : 'success');
        router.reload({ only: ['images', 'summary', 'tabs'] });
    } catch (error) {
        alerta('Error', error?.response?.data?.message || 'No se pudo completar la acción.', 'error');
    } finally {
        procesando.value = false;
    }
};

const revisarAlertas = async () => {
    try {
        await axios.post(route('performance_alerts_review'));
        alertasVisibles.value = false;
        router.reload({ only: ['alerts'] });
    } catch (error) {
        alerta('Error', 'No se pudieron marcar las alertas como revisadas.', 'error');
    }
};

const buscarAlerta = (alert) => {
    filtros.value.tab = 'todos';
    filtros.value.search = alert.name;
    filtros.value.min_kb = 0;
    aplicarFiltros(1);
};
</script>

<template>
    <AppLayout title="Rendimiento · Imágenes">
        <Head title="Rendimiento · Imágenes">
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Rendimiento</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Imágenes</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Inventario de imágenes</h2>
                    <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Revisa peso, resolución y dónde se muestra cada imagen. Puedes redimensionar, recomprimir sin
                        cambiar la resolución, generar WebP, mover a cuarentena y deshacer con las copias de seguridad.
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <span class="group relative inline-flex">
                        <Link :href="route('performance_weight_index')" class="btn btn-outline-primary">
                            <font-awesome-icon :icon="faWeightScale" class="mr-2" />
                            Peso por página
                        </Link>
                        <AyudaTip
                            texto="Muestra cuánto pesan las imágenes de cada página y avisa de las que superan el presupuesto."
                        />
                    </span>
                    <span class="group relative inline-flex">
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            title="Vuelve a leer la carpeta de imágenes para reflejar los archivos nuevos o borrados"
                            @click="actualizarInventario"
                        >
                            <font-awesome-icon :icon="faRotate" class="mr-2" />
                            Actualizar inventario
                        </button>
                        <AyudaTip
                            texto="Vuelve a leer la carpeta pública para detectar imágenes nuevas, borradas o modificadas. Puede tardar unos segundos."
                        />
                    </span>
                </div>
            </div>

            <div v-if="alerts.length && alertasVisibles" class="panel border border-warning/40 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <font-awesome-icon :icon="faTriangleExclamation" class="mt-1 text-warning" />
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-white">
                                {{ alerts.length }} imagen(es) nueva(s) y pesada(s)
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Se subieron o modificaron en los últimos {{ limits.new_days }} días y superan
                                {{ summary.threshold_kb }} KB. Revísalas antes de que se acumulen.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary"
                        title="Oculta estas alertas; no modifica ninguna imagen"
                        @click="revisarAlertas"
                    >
                        Marcar como revisadas
                    </button>
                </div>

                <ul class="mt-3 flex flex-wrap gap-2">
                    <li v-for="alert in alerts" :key="alert.id" class="max-w-full">
                        <button
                            type="button"
                            class="inline-flex max-w-full items-center gap-2 rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-600 hover:bg-primary/10 hover:text-primary dark:bg-gray-800 dark:text-gray-300"
                            @click="buscarAlerta(alert)"
                        >
                            <span class="break-all text-left">{{ alert.name }}</span>
                            <span class="shrink-0 font-semibold text-warning">{{ formatoBytes(alert.bytes) }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel p-5" title="Cuenta todos los archivos escaneados, incluidos los de cuarentena">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Imágenes analizadas</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ summary.total || 0 }}</p>
                    <p class="mt-1 text-xs text-gray-400">En todas las pestañas</p>
                </div>
                <div class="panel p-5" title="Suma del peso de todos los archivos encontrados en disco">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Peso total</p>
                    <p class="mt-2 text-2xl font-bold text-primary">{{ formatoBytes(summary.bytes) }}</p>
                    <p class="mt-1 text-xs text-gray-400">Suma de los archivos escaneados</p>
                </div>
                <div class="panel p-5" :title="`Archivos de ${summary.threshold_kb || 300} KB o más, cuenten o no en el sitio`">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Imágenes pesadas</p>
                    <p class="mt-2 text-2xl font-bold text-warning">{{ summary.oversized || 0 }}</p>
                    <p class="mt-1 text-xs text-gray-400">De {{ summary.threshold_kb || 300 }} KB o más</p>
                </div>
                <div
                    class="panel p-5"
                    title="Peso que se liberaría al dejar una sola copia de cada imagen repetida (no incluye huérfanas)"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">Copias repetidas</p>
                    <p class="mt-2 text-2xl font-bold text-success">{{ formatoBytes(summary.savings?.duplicates || 0) }}</p>
                    <p class="mt-1 text-xs text-gray-400">
                        {{ summary.orphans || 0 }} huérfana(s) aparte
                    </p>
                </div>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="flex flex-wrap gap-2 border-b border-gray-200 p-4 dark:border-gray-700">
                    <div v-for="tab in tabsVisibles" :key="tab.key" class="group relative">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                            :class="
                                filtros.tab === tab.key
                                    ? 'bg-primary text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                            "
                            :title="ayudaTab(tab.key)"
                            @click="cambiarTab(tab.key)"
                        >
                            {{ tab.label }}
                            <span
                                class="ml-1 rounded-full px-1.5 py-0.5 text-xs"
                                :class="filtros.tab === tab.key ? 'bg-white/20' : 'bg-white dark:bg-gray-700'"
                            >
                                {{ tab.count }}
                            </span>
                            <span class="ml-1 hidden text-xs opacity-70 sm:inline">{{ formatoBytes(tab.bytes) }}</span>
                        </button>
                        <AyudaTip :texto="ayudaTab(tab.key)" abajo izquierda />
                    </div>
                </div>

                <div
                    class="flex items-start gap-2 border-b border-gray-200 bg-gray-50/70 px-4 py-2.5 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800/40 dark:text-gray-400"
                >
                    <font-awesome-icon :icon="faCircleInfo" class="mt-0.5 shrink-0 text-primary" />
                    <p>
                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ etiquetaTabActual }}:</span>
                        {{ ayudaTabActual }}
                    </p>
                </div>

                <details class="border-b border-gray-200 px-4 py-2 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    <summary class="cursor-pointer select-none font-medium text-primary">
                        ¿Qué significa cada etiqueta y columna?
                    </summary>
                    <ul class="mt-2 grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                        <li>
                            <span class="rounded bg-warning/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-warning">Pesada</span>
                            Pesa {{ summary.threshold_kb || 300 }} KB o más; conviene recomprimirla.
                        </li>
                        <li>
                            <span class="rounded bg-success/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-success">Nueva</span>
                            Se subió o modificó en los últimos {{ limits.new_days }} días.
                        </li>
                        <li>
                            <span class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-primary">×N</span>
                            El mismo contenido está repetido en N rutas distintas.
                        </li>
                        <li>
                            <span class="rounded bg-gray-200 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-300">Huérfana</span>
                            Ninguna vista, hoja de estilos ni registro la usa: no se muestra en el sitio.
                        </li>
                        <li>
                            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-gray-400 dark:bg-gray-800">Copia</span>
                            Hay una copia guardada; puedes deshacer con “Restaurar original”.
                        </li>
                        <li>
                            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-gray-400 dark:bg-gray-800">WebP listo</span>
                            Ya existe una variante <code>.webp</code> junto al original.
                        </li>
                        <li class="sm:col-span-2">
                            <strong class="text-gray-600 dark:text-gray-300">Dónde se muestra:</strong>
                            página, curso, artículo o producto que menciona el archivo. Si está vacío, no se usa
                            en ninguna parte.
                        </li>
                    </ul>
                </details>

                <form
                    class="flex flex-col gap-3 border-b border-gray-200 p-4 dark:border-gray-700 lg:flex-row lg:items-center"
                    @submit.prevent="aplicarFiltros(1)"
                >
                    <input
                        v-model="filtros.search"
                        type="search"
                        class="form-input lg:max-w-xs"
                        placeholder="Buscar por nombre o página donde se usa"
                    />

                    <select v-model.number="filtros.min_kb" class="form-select lg:max-w-[180px]">
                        <option :value="0">Cualquier peso</option>
                        <option :value="100">Desde 100 KB</option>
                        <option :value="300">Desde 300 KB</option>
                        <option :value="500">Desde 500 KB</option>
                        <option :value="1024">Desde 1 MB</option>
                    </select>

                    <select v-model="filtros.sort" class="form-select lg:max-w-[200px]">
                        <option value="bytes">Más pesadas primero</option>
                        <option value="width">Mayor resolución primero</option>
                        <option value="date">Más recientes primero</option>
                        <option value="name">Nombre (A-Z)</option>
                    </select>

                    <button type="submit" class="btn btn-primary">
                        <font-awesome-icon :icon="faFilter" class="mr-2" />
                        Filtrar
                    </button>

                    <span class="text-sm text-gray-500 dark:text-gray-400 lg:ml-auto">
                        {{ images.total }} resultado(s)
                    </span>
                </form>

                <div
                    v-if="seleccionados.length"
                    class="flex flex-wrap items-center gap-2 border-b border-primary/20 bg-primary/5 p-3"
                >
                    <span class="text-sm font-medium text-primary">
                        {{ seleccionados.length }} imagen(es) seleccionadas
                    </span>

                    <template v-if="enCuarentena">
                        <button
                            type="button"
                            class="btn btn-success btn-sm"
                            title="Devuelve los archivos a su carpeta original para que vuelvan a mostrarse"
                            :disabled="procesando"
                            @click="restaurarCuarentena()"
                        >
                            <font-awesome-icon :icon="faRotateLeft" class="mr-2" />
                            Restaurar
                        </button>
                    </template>
                    <template v-else>
                        <button
                            type="button"
                            class="btn btn-primary btn-sm"
                            title="Reduce el ancho y alto, sobrescribe el archivo con el mismo nombre y guarda copia"
                            :disabled="procesando"
                            @click="abrirModal(null, 'resize')"
                        >
                            <font-awesome-icon :icon="faCompress" class="mr-2" />
                            Redimensionar
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm"
                            title="Recomprime sin cambiar la resolución: misma medida, menos peso"
                            :disabled="procesando"
                            @click="abrirModal(null, 'compress')"
                        >
                            <font-awesome-icon :icon="faCompress" class="mr-2" />
                            Comprimir
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm"
                            title="Crea variantes .webp junto al original, sin reemplazarlo"
                            :disabled="procesando"
                            @click="generarWebp()"
                        >
                            <font-awesome-icon :icon="faClone" class="mr-2" />
                            WebP
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-warning btn-sm"
                            title="Saca los archivos de la carpeta pública; se pueden restaurar después"
                            :disabled="procesando"
                            @click="moverCuarentena()"
                        >
                            <font-awesome-icon :icon="faBoxesStacked" class="mr-2" />
                            Cuarentena
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            title="Devuelve el archivo al estado anterior a cualquier cambio"
                            :disabled="procesando"
                            @click="restaurarBackup()"
                        >
                            <font-awesome-icon :icon="faRotateLeft" class="mr-2" />
                            Restaurar original
                        </button>
                    </template>

                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="seleccionados = []">
                        Limpiar selección
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="min-w-full table-hover">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="w-10">
                                    <input
                                        type="checkbox"
                                        class="form-checkbox"
                                        :checked="todosSeleccionados"
                                        aria-label="Seleccionar todas las de esta página"
                                        @change="toggleTodos"
                                    />
                                </th>
                                <th class="w-16" title="Miniatura del archivo; ábrela para verla completa">Vista</th>
                                <th class="min-w-[240px]">Archivo</th>
                                <th class="w-28">Peso</th>
                                <th class="w-32">Resolución</th>
                                <th
                                    class="min-w-[240px]"
                                    title="Página, curso, artículo o producto que menciona el archivo. Vacío = no se muestra en el sitio"
                                >
                                    Dónde se muestra
                                </th>
                                <th class="w-40 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!images.data.length">
                                <td colspan="7" class="py-10 text-center text-sm text-gray-500">
                                    No hay imágenes con estos filtros.
                                </td>
                            </tr>
                            <tr v-for="image in images.data" :key="image.id">
                                <td>
                                    <input
                                        v-model="seleccionados"
                                        type="checkbox"
                                        class="form-checkbox"
                                        :value="image.id"
                                        :aria-label="`Seleccionar ${image.name}`"
                                    />
                                </td>
                                <td>
                                    <a v-if="image.url" :href="image.url" target="_blank" rel="noopener" class="block">
                                        <img
                                            :src="image.url"
                                            :alt="image.name"
                                            loading="lazy"
                                            class="h-12 w-12 rounded-lg border border-gray-200 bg-gray-50 object-cover dark:border-gray-700 dark:bg-gray-800"
                                            @error="$event.target.style.visibility = 'hidden'"
                                        />
                                    </a>
                                    <span
                                        v-else
                                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 text-gray-400 dark:bg-gray-800"
                                        title="En cuarentena"
                                    >
                                        <font-awesome-icon :icon="faBoxesStacked" />
                                    </span>
                                </td>
                                <td>
                                    <p class="flex flex-wrap items-center gap-1 font-medium text-gray-800 dark:text-gray-100">
                                        {{ image.name }}
                                        <span
                                            v-if="esPesada(image)"
                                            class="rounded bg-warning/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-warning"
                                        >
                                            Pesada
                                        </span>
                                        <span
                                            v-if="image.is_new"
                                            class="rounded bg-success/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-success"
                                        >
                                            Nueva
                                        </span>
                                        <span
                                            v-if="image.duplicate_count > 1"
                                            class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-primary"
                                            title="Este archivo está repetido con el mismo contenido"
                                        >
                                            ×{{ image.duplicate_count }}
                                        </span>
                                        <span
                                            v-if="image.references.length === 0 && !image.quarantined"
                                            class="rounded bg-gray-200 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-300"
                                            title="Ninguna vista, hoja de estilos ni registro la menciona: no se muestra en el sitio"
                                        >
                                            Huérfana
                                        </span>
                                        <span
                                            v-if="image.has_backup"
                                            class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-gray-400 dark:bg-gray-800"
                                            title="Tiene copia de seguridad"
                                        >
                                            Copia
                                        </span>
                                    </p>
                                    <p class="mt-0.5 break-all text-xs text-gray-400">{{ image.path }}</p>
                                    <p class="mt-0.5 text-xs text-gray-400">
                                        Modificada: {{ image.modified || '—' }}
                                        <span v-if="image.optimized_at"> · Optimizada: {{ image.optimized_at }}</span>
                                    </p>
                                </td>
                                <td class="font-semibold text-gray-700 dark:text-gray-200">
                                    {{ formatoBytes(image.bytes) }}
                                    <span
                                        v-if="image.compressed_bytes && image.compressed_bytes < image.bytes"
                                        class="block text-[11px] font-normal text-success"
                                    >
                                        Optimizada a {{ formatoBytes(image.compressed_bytes) }}
                                    </span>
                                </td>
                                <td class="text-gray-600 dark:text-gray-300">
                                    <template v-if="image.width && image.height">{{ image.width }} × {{ image.height }}</template>
                                    <template v-else>—</template>
                                    <span v-if="image.webp_url" class="mt-0.5 block text-[11px] text-success">WebP listo</span>
                                    <span v-else-if="image.type !== 'gif' && !image.quarantined" class="mt-0.5 block text-[11px] text-gray-400">
                                        Sin WebP
                                    </span>
                                </td>
                                <td>
                                    <div v-if="image.references.length" class="flex flex-wrap gap-1">
                                        <template v-for="(referencia, index) in image.references" :key="index">
                                            <a
                                                v-if="referencia.url"
                                                :href="referencia.url"
                                                target="_blank"
                                                rel="noopener"
                                                class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 hover:bg-primary/10 hover:text-primary dark:bg-gray-800 dark:text-gray-300"
                                                :title="referencia.label"
                                            >
                                                {{ referencia.label }}
                                                <font-awesome-icon :icon="faUpRightFromSquare" class="text-[10px]" />
                                            </a>
                                            <span
                                                v-else
                                                class="inline-block rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                                :title="referencia.label"
                                            >
                                                {{ referencia.label }}
                                            </span>
                                        </template>
                                    </div>
                                    <span
                                        v-else
                                        class="text-xs text-gray-400"
                                        :title="
                                            image.quarantined
                                                ? 'El archivo está fuera de la carpeta pública; no se muestra'
                                                : 'Ninguna vista, CSS ni registro la usa: no se muestra en el sitio'
                                        "
                                    >
                                        {{ image.quarantined ? 'En cuarentena' : 'Huérfana: no se usa' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex flex-wrap justify-end gap-1">
                                        <a
                                            v-if="image.url"
                                            :href="image.url"
                                            target="_blank"
                                            rel="noopener"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Ver imagen"
                                        >
                                            <font-awesome-icon :icon="faImage" />
                                        </a>

                                        <template v-if="image.quarantined">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-success"
                                                title="Restaurar desde cuarentena"
                                                :disabled="procesando"
                                                @click="restaurarCuarentena([image.id])"
                                            >
                                                <font-awesome-icon :icon="faRotateLeft" />
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                :disabled="!image.resizable"
                                                :title="image.resizable ? 'Redimensionar' : image.resizable_reason || 'No se puede redimensionar'"
                                                @click="abrirModal(image, 'resize')"
                                            >
                                                <font-awesome-icon :icon="faCompress" />
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                :disabled="!image.resizable"
                                                title="Comprimir sin cambiar la resolución"
                                                @click="abrirModal(image, 'compress')"
                                            >
                                                <font-awesome-icon :icon="faCode" />
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :disabled="image.gif_animated"
                                                :title="image.gif_animated ? 'GIF animado: requiere Imagick' : 'Generar WebP'"
                                                @click="generarWebp([image.id])"
                                            >
                                                <font-awesome-icon :icon="faClone" />
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-warning"
                                                title="Mover a cuarentena"
                                                @click="moverCuarentena([image.id])"
                                            >
                                                <font-awesome-icon :icon="faBoxesStacked" />
                                            </button>
                                        </template>

                                        <button
                                            v-if="image.has_backup"
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Restaurar el original guardado"
                                            @click="restaurarBackup([image.id])"
                                        >
                                            <font-awesome-icon :icon="faRotateLeft" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Página {{ images.page }} de {{ images.last_page }} · {{ images.total }} imagen(es)
                    </p>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            :disabled="images.page <= 1"
                            @click="irPagina(images.page - 1)"
                        >
                            Anterior
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            :disabled="images.page >= images.last_page"
                            @click="irPagina(images.page + 1)"
                        >
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="filtros.tab === 'gif'" class="panel p-5">
                <div class="flex items-start gap-3">
                    <font-awesome-icon :icon="faTriangleExclamation" class="mt-1 text-warning" />
                    <div>
                        <h3 class="font-semibold text-gray-800 dark:text-white">Conversión de GIF animados</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            GD no conserva la animación, así que estos archivos no se comprimen automáticamente.
                            <template v-if="limits.gif_supported">
                                Imagick está disponible: puedes convertir cada GIF a WebP animado desde su acción.
                            </template>
                            <template v-else>
                                Para reducirlos instala la extensión <strong>Imagick</strong> en PHP (o usa
                                <strong>gifsicle</strong>) y vuelve a intentarlo; la pantalla los detecta y no los toca.
                            </template>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <ModalLarge :show="modal" :on-close="cerrarModal">
            <template #title>
                {{ form.mode === 'compress' ? 'Comprimir imágenes' : 'Redimensionar imágenes' }}
            </template>
            <template #message>
                <span>{{ form.items.length }} archivo(s). Se conserva el nombre y el formato; hay copia de seguridad.</span>
            </template>

            <template #content>
                <div class="space-y-5">
                    <div class="rounded-lg border border-warning/30 bg-warning/5 p-3 text-sm text-gray-600 dark:text-gray-300">
                        <p class="flex items-start gap-2">
                            <font-awesome-icon :icon="faTriangleExclamation" class="mt-0.5 text-warning" />
                            <span v-if="form.mode === 'compress'">
                                Se recomprime el archivo sin cambiar su resolución. Si el resultado no pesa menos, se
                                deja el original intacto.
                            </span>
                            <span v-else>
                                La imagen original se sobrescribe. Si el resultado no reduce el peso, se deja el
                                original intacto.
                            </span>
                        </p>
                    </div>

                    <div v-if="primeraSeleccionada" class="flex items-center gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <img
                            :src="primeraSeleccionada.url"
                            :alt="primeraSeleccionada.name"
                            class="h-14 w-14 rounded-lg border border-gray-200 object-cover dark:border-gray-700"
                        />
                        <div class="min-w-0 text-sm">
                            <p class="truncate font-medium text-gray-800 dark:text-gray-100">
                                {{ primeraSeleccionada.name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Actual: {{ primeraSeleccionada.width }} × {{ primeraSeleccionada.height }} ·
                                {{ formatoBytes(primeraSeleccionada.bytes) }}
                            </p>
                            <p v-if="cambioEstimado" class="text-xs text-primary">
                                Quedará: {{ cambioEstimado.width }} × {{ cambioEstimado.height }}
                            </p>
                            <p v-else-if="form.mode === 'compress'" class="text-xs text-gray-400">
                                Misma resolución, menor peso si es posible.
                            </p>
                            <p v-else class="text-xs text-gray-400">
                                Con este ancho no se reduce la imagen.
                            </p>
                        </div>
                    </div>

                    <template v-if="form.mode === 'resize'">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="preset in presets"
                                :key="preset"
                                type="button"
                                class="btn btn-sm"
                                :class="form.width === preset && !form.height ? 'btn-primary' : 'btn-outline-primary'"
                                @click="aplicarPreset(preset)"
                            >
                                {{ preset }} px de ancho
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Ancho máximo (px)
                                </label>
                                <input v-model.number="form.width" type="number" min="16" max="8000" class="form-input w-full" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Alto máximo (px, opcional)
                                </label>
                                <input v-model.number="form.height" type="number" min="16" max="8000" class="form-input w-full" placeholder="Automático" />
                            </div>
                        </div>
                    </template>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Calidad (30-100)
                        </label>
                        <input v-model.number="form.quality" type="number" min="30" max="100" class="form-input w-full md:max-w-[200px]" />
                        <p class="mt-1 text-xs text-gray-400">82 es un buen equilibrio entre calidad y peso.</p>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Se guarda una copia en el servidor durante {{ limits.backup_days }} días. Puedes deshacer desde
                                                    «Restaurar original».
                    </p>

                    <div class="flex justify-end pt-2">
                        <PrimaryButton type="button" :disabled="procesando" @click="ejecutarOptimizacion">
                            {{ procesando ? 'Procesando…' : form.mode === 'compress' ? 'Comprimir' : 'Redimensionar' }}
                        </PrimaryButton>
                    </div>
                </div>
            </template>
        </ModalLarge>

        <ModalLarge :show="webpModal" :on-close="() => (webpModal = false)">
            <template #title>Variantes WebP generadas</template>
            <template #message>
                <span>{{ webpResumen.files.length }} archivo(s). Ahorro estimado: {{ formatoBytes(webpResumen.saved) }}.</span>
            </template>

            <template #content>
                <div class="space-y-4">
                    <ul class="max-h-48 space-y-1 overflow-y-auto text-sm">
                        <li
                            v-for="file in webpResumen.files"
                            :key="file.path"
                            class="flex items-center justify-between gap-3 rounded bg-gray-50 px-2 py-1 dark:bg-gray-800"
                        >
                            <a :href="file.url" target="_blank" rel="noopener" class="truncate text-primary hover:underline">
                                {{ file.path }}
                            </a>
                            <span class="shrink-0 text-xs text-gray-400">
                                {{ file.width }}w · {{ formatoBytes(file.bytes) }}
                            </span>
                        </li>
                    </ul>

                    <div v-if="snippetWebp">
                        <div class="mb-1 flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Fragmento para la primera imagen</p>
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="copiarSnippet">
                                Copiar
                            </button>
                        </div>
                        <pre class="overflow-x-auto rounded-lg bg-gray-900 p-3 text-xs text-green-300">{{ snippetWebp }}</pre>
                        <p class="mt-1 text-xs text-gray-400">
                            Sustituye el <code>&lt;img&gt;</code> de la vista por este bloque: el navegador usará WebP
                            cuando pueda y el original como respaldo.
                        </p>
                    </div>
                </div>
            </template>
        </ModalLarge>
    </AppLayout>
</template>
