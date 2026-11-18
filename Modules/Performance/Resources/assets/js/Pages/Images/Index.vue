<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import ModalSimple from '../../Components/ModalSimple.vue';
import AyudaTip from '../../Components/AyudaTip.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faBoxesStacked,
    faCircleInfo,
    faClone,
    faCode,
    faCompress,
    faExpand,
    faFilter,
    faImage,
    faImages,
    faMagnifyingGlassMinus,
    faMagnifyingGlassPlus,
    faRotate,
    faRotateLeft,
    faTrash,
    faTriangleExclamation,
    faUpRightFromSquare,
    faWeightScale,
    faXmark,
} from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    images: { type: Object, default: () => ({ data: [], total: 0, page: 1, last_page: 1 }) },
    summary: { type: Object, default: () => ({}) },
    tabs: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ tab: 'todos', search: '', min_kb: 0, sort: 'bytes' }) },
    presets: { type: Array, default: () => [1920, 1280, 800] },
    alerts: { type: Array, default: () => [] },
    backups: {
        type: Object,
        default: () => ({
            files: 0,
            bytes: 0,
            folders: 0,
            expired_files: 0,
            expired_bytes: 0,
            oldest: null,
            retention_days: 30,
        }),
    },
    limits: {
        type: Object,
        default: () => ({
            backup_days: 30,
            quarantine_days: 7,
            new_days: 7,
            webp_widths: [0, 1280, 800],
            gif_supported: false,
        }),
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
const progreso = ref({
    activo: false,
    total: 0,
    hechos: 0,
    actual: '',
    cambiadas: 0,
    omitidas: 0,
    errores: 0,
    ahorro: 0,
    items: [],
});
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

const porcentajeProgreso = computed(() =>
    progreso.value.total ? Math.round((progreso.value.hechos / progreso.value.total) * 100) : 0
);

const etiquetaEstado = (estado) =>
    ({
        enviando: 'Enviando…',
        resized: 'Actualizada',
        unchanged: 'Sin cambios',
        skipped: 'Omitida',
        error: 'Error',
    }[estado] || estado);

const claseEstado = (estado) =>
    ({
        enviando: 'text-primary',
        resized: 'text-success',
        unchanged: 'text-gray-500 dark:text-gray-400',
        skipped: 'text-warning',
        error: 'text-danger',
    }[estado] || 'text-gray-500');

const mimeDeImagen = (extension) =>
    ({
        jpg: 'image/jpeg',
        jpeg: 'image/jpeg',
        png: 'image/png',
        webp: 'image/webp',
    }[String(extension || '').toLowerCase()] || null);

const cargarBitmap = (blob) =>
    new Promise((resolve, reject) => {
        const url = URL.createObjectURL(blob);
        const imagen = new Image();

        imagen.onload = () => {
            URL.revokeObjectURL(url);
            resolve(imagen);
        };
        imagen.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('No se pudo decodificar la imagen en el navegador.'));
        };
        imagen.src = url;
    });

// Misma proporción que usa el servidor: nunca amplía la imagen.
const calcularDestino = (ancho, alto, maxAncho, maxAlto) => {
    let ratio = Number.POSITIVE_INFINITY;

    if (maxAncho) ratio = Math.min(ratio, maxAncho / ancho);
    if (maxAlto) ratio = Math.min(ratio, maxAlto / alto);

    if (!Number.isFinite(ratio) || ratio >= 1) return null;

    return {
        width: Math.max(1, Math.round(ancho * ratio)),
        height: Math.max(1, Math.round(alto * ratio)),
    };
};

const codificarCanvas = (canvas, mime, calidad) =>
    new Promise((resolve, reject) => {
        canvas.toBlob(
            (blob) => (blob ? resolve(blob) : reject(new Error('El navegador no pudo generar el archivo.'))),
            mime,
            mime === 'image/png' ? undefined : calidad / 100
        );
    });

// ---------------------------------------------------------------------------
// Visor de imágenes
// ---------------------------------------------------------------------------
// Se abre al pulsar la miniatura o «Ver». Muestra la imagen ajustada a la
// ventana, permite verla al 100 % y calcula al costado cómo quedaría después
// de comprimirla o reducirle la resolución, sin tocar el archivo del servidor.
const visor = ref({ abierto: false, image: null });
const visorZoom = ref(null); // null = ajustar a la ventana
const visorNatural = ref({ width: 0, height: 0 });
// Copia descargada sin caché del archivo: las dos comparaciones usan estos
// mismos píxeles, aunque el navegador tenga una versión anterior en caché.
const origenVisor = ref({ url: '', bytes: 0, width: 0, height: 0 });
let blobOriginal = null;
// Último ancho elegido automáticamente: solo ese se puede corregir cuando
// llega la medida real, para no pisar lo que el usuario haya escrito.
let anchoAutomatico = null;
const vistaPrevia = ref({
    estado: 'inactivo', // inactivo | calculando | listo | error
    url: '',
    bytes: 0,
    width: 0,
    height: 0,
    reducido: false,
    message: '',
});
const vistaPreviaForm = ref({ mode: 'resize', width: 1280, height: null, quality: 82 });
let vistaPreviaToken = 0;
let vistaPreviaTimer = null;

// Con varias imágenes seleccionadas la vista previa trabaja solo con la
// primera de la página; si no hay selección, con la que se está viendo.
const imagenEnVistaPrevia = computed(() => {
    const primera = (props.images.data || []).find((image) => seleccionados.value.includes(image.id));

    return primera || visor.value.image;
});

const seleccionMultipleVistaPrevia = computed(() => seleccionados.value.length > 1);

// Se prefieren las medidas reales que decodifica el navegador: las guardadas
// en el inventario pueden estar desactualizadas (por ejemplo, tras restaurar
// una copia) y descuadrarían la escala compartida.
const anchoNaturalVisor = computed(
    () => visorNatural.value.width || visor.value.image?.width || 0
);

const altoNaturalVisor = computed(
    () => visorNatural.value.height || visor.value.image?.height || 0
);

// Las dos imágenes se dibujan con la MISMA escala de píxeles para poder
// comparar de verdad: en «Ajustar» se calcula una única escala con las
// medidas del original y el espacio disponible, y se aplica también al
// resultado.
const panelOriginal = ref(null);
const escalaAjuste = ref(1);

const medirEscalaAjuste = () => {
    const panel = panelOriginal.value;
    const ancho = anchoNaturalVisor.value;
    const alto = altoNaturalVisor.value;

    if (!panel || !ancho || !alto) return;

    // offsetWidth/offsetHeight incluyen la barra de desplazamiento y no
    // cambian cuando esta aparece: con clientWidth la escala podía oscilar
    // entre dos valores y dejar la pestaña congelada calculando sin parar.
    const disponibleAncho = Math.max(1, panel.offsetWidth - 28);
    const disponibleAlto = Math.max(1, panel.offsetHeight - 28);
    const escala = Math.min(1, disponibleAncho / ancho, disponibleAlto / alto);

    // Ignora variaciones imperceptibles para no re-renderizar en bucle.
    if (Math.abs(escala - escalaAjuste.value) < 0.002) return;

    escalaAjuste.value = escala;
};

// El panel cambia de tamaño al abrir el visor y al adaptarse la ventana; el
// observador vuelve a medir para que «Ajustar» siempre quepa de verdad.
let observadorPanel = null;
let cuadroPanel = null;

const observarPanel = () => {
    if (typeof ResizeObserver === 'undefined') return;

    observadorPanel?.disconnect();

    if (!panelOriginal.value) return;

    // Se mide una sola vez por cuadro: así el observador nunca encadena
    // mediciones dentro de la misma actualización de diseño.
    observadorPanel = new ResizeObserver(() => {
        if (cuadroPanel) return;

        cuadroPanel = requestAnimationFrame(() => {
            cuadroPanel = null;
            medirEscalaAjuste();
        });
    });
    observadorPanel.observe(panelOriginal.value);
};

const dejarDeObservarPanel = () => {
    observadorPanel?.disconnect();
    observadorPanel = null;

    if (cuadroPanel) {
        cancelAnimationFrame(cuadroPanel);
        cuadroPanel = null;
    }
};

const escalaVisor = computed(() =>
    visorZoom.value === null ? escalaAjuste.value : visorZoom.value
);

const estiloImagenVisor = computed(() => {
    const ancho = anchoNaturalVisor.value;

    if (!ancho) return { maxWidth: '100%', height: 'auto' };

    return {
        width: `${Math.round(ancho * escalaVisor.value)}px`,
        maxWidth: 'none',
        maxHeight: 'none',
        height: 'auto',
    };
});

const estiloImagenVistaPrevia = computed(() => {
    if (!vistaPrevia.value.width) return {};

    return {
        width: `${Math.round(vistaPrevia.value.width * escalaVisor.value)}px`,
        maxWidth: 'none',
        maxHeight: 'none',
        height: 'auto',
    };
});

const etiquetaZoom = computed(() =>
    visorZoom.value === null
        ? `Ajustada · ${Math.round(escalaVisor.value * 100)} %`
        : `${Math.round(visorZoom.value * 100)} %`
);

const aplicarZoom = (valor) => {
    // «Ajustar» se recalcula al pulsarlo: así siempre cabe aunque la ventana
    // haya cambiado de tamaño desde que se abrió el visor.
    if (valor === null) medirEscalaAjuste();

    visorZoom.value = valor;
};

const acercar = () => {
    const actual = visorZoom.value ?? 1;
    visorZoom.value = Math.min(3, Math.round((actual + 0.25) * 100) / 100);
};

const alejar = () => {
    const actual = visorZoom.value ?? 1;
    visorZoom.value = Math.max(0.1, Math.round((actual - 0.25) * 100) / 100);
};

const liberarUrlVistaPrevia = () => {
    if (vistaPrevia.value.url?.startsWith('blob:')) {
        URL.revokeObjectURL(vistaPrevia.value.url);
    }
};

const liberarOrigen = () => {
    if (origenVisor.value.url?.startsWith('blob:')) {
        URL.revokeObjectURL(origenVisor.value.url);
    }
};

const reiniciarOrigen = () => {
    liberarOrigen();
    blobOriginal = null;
    origenVisor.value = { url: '', bytes: 0, width: 0, height: 0 };
};

// Descarga el archivo una sola vez por apertura y lo reutiliza para el
// original y para la vista previa.
const cargarOriginal = async (image) => {
    if (blobOriginal) return blobOriginal;

    const respuesta = await fetch(image.url, { credentials: 'same-origin', cache: 'no-store' });

    if (!respuesta.ok) throw new Error('No se pudo leer el archivo de la imagen.');

    const blob = await respuesta.blob();

    blobOriginal = blob;
    liberarOrigen();
    origenVisor.value = { url: URL.createObjectURL(blob), bytes: blob.size, width: 0, height: 0 };

    return blob;
};

// Ancho inicial de la comparación: el mayor preset que aún reduzca la
// imagen, para que la vista previa muestre un cambio desde el principio.
const anchoPreferido = (image) => {
    const ancho = Number(image?.width || 0);
    const presets = [...props.presets].sort((a, b) => b - a);

    if (!ancho) return presets.includes(1280) ? 1280 : presets[0] || 1280;

    return presets.find((preset) => preset < ancho) || Math.min(ancho, presets[0] || ancho);
};

// Al decodificar el original se corrigen las medidas y, si el ancho de la
// comparación seguía en el valor inicial, se recalcula con el tamaño real.
const alCargarOriginal = (event) => {
    const natural = {
        width: event.target.naturalWidth,
        height: event.target.naturalHeight,
    };

    visorNatural.value = natural;
    medirEscalaAjuste();

    if (Number(vistaPreviaForm.value.width) !== Number(anchoAutomatico)) return;

    const corregido = anchoPreferido({ width: natural.width });

    if (corregido !== vistaPreviaForm.value.width) {
        vistaPreviaForm.value.width = corregido;
    }

    anchoAutomatico = corregido;
};

// Indica si el visor se abrió desde el modal de procesado, para devolverlo
// al cerrarlo conservando la selección y la configuración.
const volverAlProceso = ref(false);

const abrirVisorDesdeLista = (image) => {
    volverAlProceso.value = false;
    abrirVisor(image);
};

// El modal de procesado se cierra antes de abrir el visor: así no hay dos
// capas compitiendo por el foco y el visor responde siempre. Al cerrarlo se
// reabre con la misma selección y los mismos ajustes.
const abrirVisorDesdeProceso = (image) => {
    if (!image || procesando.value) return;

    volverAlProceso.value = true;
    modal.value = false;
    abrirVisor(image);
};

const abrirVisor = (image) => {
    if (!image) return;

    liberarUrlVistaPrevia();
    reiniciarOrigen();
    visor.value = { abierto: true, image };
    visorZoom.value = null;
    visorNatural.value = { width: 0, height: 0 };
    vistaPrevia.value = { estado: 'inactivo', url: '', bytes: 0, width: 0, height: 0, reducido: false, message: '' };
    vistaPreviaForm.value = {
        mode: 'resize',
        width: anchoPreferido(image),
        height: null,
        quality: 82,
    };
    anchoAutomatico = vistaPreviaForm.value.width;

    nextTick(() => {
        medirEscalaAjuste();
        observarPanel();
    });
};

const cerrarVisor = () => {
    dejarDeObservarPanel();
    vistaPreviaToken++;
    clearTimeout(vistaPreviaTimer);
    liberarUrlVistaPrevia();
    reiniciarOrigen();
    visor.value = { abierto: false, image: null };
    vistaPrevia.value = { estado: 'inactivo', url: '', bytes: 0, width: 0, height: 0, reducido: false, message: '' };

    if (volverAlProceso.value && form.value.items.length && !procesando.value) {
        modal.value = true;
    }

    volverAlProceso.value = false;
};

// Genera en el navegador la versión comprimida/reducida para compararla.
// No envía nada: solo mide lo que pasaría si se aplicara el cambio.
const generarVistaPrevia = async () => {
    const image = imagenEnVistaPrevia.value;

    if (!visor.value.abierto || !image) return;

    const token = ++vistaPreviaToken;

    liberarUrlVistaPrevia();
    vistaPrevia.value = { estado: 'calculando', url: '', bytes: 0, width: 0, height: 0, reducido: false, message: '' };

    try {
        const original = await cargarOriginal(image);
        const bitmap = await cargarBitmap(original);
        const mime = mimeDeImagen(image.type || original.type.split('/').pop());

        if (!mime) throw new Error('Este formato no se puede previsualizar en el navegador.');

        const destino =
            vistaPreviaForm.value.mode === 'resize'
                ? calcularDestino(
                      bitmap.naturalWidth,
                      bitmap.naturalHeight,
                      vistaPreviaForm.value.width || null,
                      vistaPreviaForm.value.height || null
                  )
                : { width: bitmap.naturalWidth, height: bitmap.naturalHeight };

        if (!destino) {
            if (token === vistaPreviaToken) {
                vistaPrevia.value = {
                    estado: 'listo',
                    url: origenVisor.value.url || image.url,
                    bytes: origenVisor.value.bytes || image.bytes || original.size,
                    width: bitmap.naturalWidth,
                    height: bitmap.naturalHeight,
                    reducido: false,
                    message: 'Con este ancho la imagen no se reduce: se muestra el original.',
                };
            }

            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = destino.width;
        canvas.height = destino.height;

        const contexto = canvas.getContext('2d');
        contexto.imageSmoothingEnabled = true;
        contexto.imageSmoothingQuality = 'high';

        if (mime === 'image/jpeg') {
            contexto.fillStyle = '#ffffff';
            contexto.fillRect(0, 0, destino.width, destino.height);
        }

        contexto.drawImage(bitmap, 0, 0, destino.width, destino.height);

        const archivo = await codificarCanvas(canvas, mime, vistaPreviaForm.value.quality || 82);

        if (token !== vistaPreviaToken) return;

        vistaPrevia.value = {
            estado: 'listo',
            url: URL.createObjectURL(archivo),
            bytes: archivo.size,
            width: destino.width,
            height: destino.height,
            reducido: archivo.size < Number(image.bytes || original.size || 0),
            message: '',
        };
    } catch (error) {
        if (token !== vistaPreviaToken) return;

        vistaPrevia.value = {
            estado: 'error',
            url: '',
            bytes: 0,
            width: 0,
            height: 0,
            reducido: false,
            message: error?.message || 'No se pudo calcular la vista previa.',
        };
    }
};

const programarVistaPrevia = () => {
    clearTimeout(vistaPreviaTimer);
    vistaPreviaTimer = setTimeout(generarVistaPrevia, 250);
};

const ahorroVistaPrevia = computed(() => {
    const image = imagenEnVistaPrevia.value;

    if (!image || !vistaPrevia.value.bytes) return 0;

    return Math.max(0, Number(image.bytes || 0) - Number(vistaPrevia.value.bytes || 0));
});

const ayudaVisor = () =>
    alerta(
        'Visor y vista previa',
        [
            'Haz clic en la miniatura de cualquier fila para abrir el visor, también en las imágenes en cuarentena.',
            'La imagen se abre ajustada a la ventana: usa «Ajustar», «100 %» o los botones + y − para revisar los detalles.',
            'A la derecha se calcula en tu navegador cómo quedaría con el ancho y la calidad elegidos. No se envía ni se modifica nada hasta que confirmes el procesado.',
            'Si tienes varias imágenes seleccionadas, la comparación se hace solo con la primera de la lista.',
        ].join('\n\n'),
        'info'
    );

watch(vistaPreviaForm, programarVistaPrevia, { deep: true });
watch(imagenEnVistaPrevia, programarVistaPrevia);

const alRedimensionarVentana = () => medirEscalaAjuste();

watch(visorNatural, medirEscalaAjuste);

window.addEventListener('resize', alRedimensionarVentana);

onBeforeUnmount(() => {
    clearTimeout(vistaPreviaTimer);
    liberarUrlVistaPrevia();
    liberarOrigen();
    dejarDeObservarPanel();
    window.removeEventListener('resize', alRedimensionarVentana);
});

// Procesa y envía una sola imagen. Se llama en secuencia desde
// ejecutarOptimizacion, nunca en paralelo.
const procesarImagen = async (imagen) => {
    const mime = mimeDeImagen(imagen.type);

    if (!mime || imagen.type === 'gif') {
        return { status: 'skipped', message: 'Formato no compatible con el procesado en el navegador.' };
    }

    const respuesta = await fetch(imagen.url, { credentials: 'same-origin', cache: 'no-store' });

    if (!respuesta.ok) throw new Error('No se pudo descargar la imagen desde el servidor.');

    const original = await respuesta.blob();
    const bitmap = await cargarBitmap(original);
    const destino =
        form.value.mode === 'resize'
            ? calcularDestino(
                  bitmap.naturalWidth,
                  bitmap.naturalHeight,
                  form.value.width || null,
                  form.value.height || null
              )
            : { width: bitmap.naturalWidth, height: bitmap.naturalHeight };

    if (!destino) {
        return {
            status: 'skipped',
            message: 'La imagen ya es igual o más pequeña que el tamaño pedido.',
            before: { bytes: imagen.bytes, width: imagen.width, height: imagen.height },
        };
    }

    const canvas = document.createElement('canvas');
    canvas.width = destino.width;
    canvas.height = destino.height;

    const contexto = canvas.getContext('2d');
    contexto.imageSmoothingEnabled = true;
    contexto.imageSmoothingQuality = 'high';

    if (mime === 'image/jpeg') {
        contexto.fillStyle = '#ffffff';
        contexto.fillRect(0, 0, destino.width, destino.height);
    }

    contexto.drawImage(bitmap, 0, 0, destino.width, destino.height);

    const archivo = await codificarCanvas(canvas, mime, form.value.quality || 82);

    // No se envía nada que no reduzca el peso: el original queda intacto.
    if (archivo.size >= Number(imagen.bytes || 0)) {
        return {
            status: 'unchanged',
            message: 'El archivo resultante no era más liviano; se dejó el original.',
            before: { bytes: imagen.bytes, width: imagen.width, height: imagen.height },
            after: { bytes: archivo.size, width: destino.width, height: destino.height },
        };
    }

    const datos = new FormData();
    datos.append('path', imagen.path);
    datos.append('file', archivo, imagen.name);

    const { data } = await axios.post(route('performance_images_replace'), datos);

    return data.result || { status: 'error', message: data.message || 'Respuesta inesperada del servidor.' };
};

const enCuarentena = computed(() => filtros.value.tab === 'cuarentena');

// Ayuda de cada pestaña: qué agrupa y qué pasa si se usa.
const ayudasTabs = {
    todos: 'Todas las imágenes subidas por los usuarios, se usen o no en el sitio.',
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
        'Archivos que se sacaron de la carpeta pública. Ya no se muestran en el sitio; se pueden restaurar en cualquier momento y eliminar definitivamente cuando llevan varios días aquí.',
};

const ayudaTab = (key) => ayudasTabs[key] || 'Imágenes agrupadas por la sección donde se muestran.';

const etiquetaTabActual = computed(
    () => tabsVisibles.value.find((tab) => tab.key === filtros.value.tab)?.label || filtros.value.tab
);

const ayudaTabActual = computed(() => ayudaTab(filtros.value.tab));

const tabsVisibles = computed(() => {
    const base = ['cursos', 'blog', 'productos', 'otros'];

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

    progreso.value = {
        activo: false,
        total: 0,
        hechos: 0,
        actual: '',
        cambiadas: 0,
        omitidas: 0,
        errores: 0,
        ahorro: 0,
        items: [],
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
            ? `Se recomprimirán ${total} archivo(s) sin cambiar su resolución, conservando el nombre. Se procesan y envían de una en una desde el navegador, con copia antes de cada cambio.`
            : `Se sobrescribirán ${total} archivo(s) con el mismo nombre. Se procesan y envían de una en una desde el navegador, con copia de seguridad antes de cada cambio.`,
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
    progreso.value = {
        activo: true,
        total,
        hechos: 0,
        actual: '',
        cambiadas: 0,
        omitidas: 0,
        errores: 0,
        ahorro: 0,
        items: [],
    };

    try {
        // Una imagen por vez: se modifica en el navegador, se envía, y solo
        // cuando el servidor responde se pasa a la siguiente. Así ninguna
        // petición lleva el lote completo y el progreso avanza foto por foto.
        for (const id of form.value.items) {
            const imagen =
                props.images.data.find((item) => item.id === id) || {
                    id,
                    path: id,
                    name: String(id).split('/').pop(),
                    type: String(id).split('.').pop(),
                };

            progreso.value.actual = imagen.name;
            progreso.value.items.push({ path: id, name: imagen.name, estado: 'enviando', message: '' });

            const entrada = progreso.value.items[progreso.value.items.length - 1];
            let resultado;

            try {
                resultado = await procesarImagen(imagen);
            } catch (error) {
                resultado = {
                    status: 'error',
                    message:
                        error?.response?.data?.message || error?.message || 'No se pudo procesar la imagen.',
                };
            }

            entrada.estado = resultado.status || 'error';
            entrada.message = resultado.message || '';
            entrada.ahorro = Math.max(
                0,
                Number(resultado.before?.bytes || 0) - Number(resultado.after?.bytes || 0)
            );

            if (resultado.status === 'resized') {
                progreso.value.cambiadas++;
                progreso.value.ahorro += entrada.ahorro;
            } else if (resultado.status === 'error') {
                progreso.value.errores++;
            } else {
                progreso.value.omitidas++;
            }

            progreso.value.hechos++;
        }

        let mensaje = `${progreso.value.cambiadas} imagen(es) procesadas. Ahorro: ${formatoBytes(progreso.value.ahorro)}.`;

        if (progreso.value.omitidas) mensaje += ` ${progreso.value.omitidas} sin cambios.`;
        if (progreso.value.errores) mensaje += ` ${progreso.value.errores} con error.`;

        modal.value = false;
        seleccionados.value = [];

        alerta(
            progreso.value.errores ? 'Proceso con avisos' : 'Imágenes actualizadas',
            mensaje,
            progreso.value.errores ? 'warning' : 'success'
        );

        actualizarInventario();
    } catch (error) {
        alerta('Error', error?.response?.data?.message || 'No se pudieron procesar las imágenes.', 'error');
    } finally {
        procesando.value = false;
        progreso.value.activo = false;
        progreso.value.actual = '';
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

// El inventario guarda la fecha de cuarentena como "Y-m-d H:i".
const diasEnCuarentena = (image) => {
    const fecha = new Date(String(image.modified || '').replace(' ', 'T')).getTime();

    if (!Number.isFinite(fecha)) return 0;

    return Math.floor((Date.now() - fecha) / 86400000);
};

const puedeEliminarCuarentena = (image) =>
    diasEnCuarentena(image) >= Number(props.limits.quarantine_days || 7);

const seleccionCuarentena = computed(() =>
    (props.images.data || []).filter((image) => image.quarantined && seleccionados.value.includes(image.id))
);

const seleccionEliminable = computed(() => seleccionCuarentena.value.filter(puedeEliminarCuarentena));

const eliminarCuarentena = async (items = null) => {
    const lista = items ?? seleccionEliminable.value.map((image) => image.id);

    if (!lista.length) {
        alerta(
            'Todavía no se puede eliminar',
            `Los archivos deben llevar al menos ${props.limits.quarantine_days} días en cuarentena. Mientras tanto puedes restaurarlos.`,
            'info'
        );

        return;
    }

    const confirmacion = await alerta(
        'Eliminar definitivamente',
        `Se borrarán ${lista.length} archivo(s) de la cuarentena. No se podrán restaurar desde la pantalla; las copias de seguridad siguen disponibles hasta cumplir su retención.`,
        'warning',
        { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' }
    );

    if (!confirmacion?.isConfirmed) return;

    await ejecutarAccion('performance_images_quarantine_delete', lista, 'Archivos eliminados', 'Se eliminaron');
};

const limpiarCopias = async () => {
    if (procesando.value) return;

    const confirmacion = await alerta(
        'Eliminar copias antiguas',
        `Se borrarán ${props.backups.expired_files || 0} archivo(s) de copia con más de ${props.limits.backup_days} días. Después de eso ya no se podrá deshacer esos cambios.`,
        'warning',
        { duration: 0, showCancelButton: true, confirmButtonText: 'Eliminar copias', cancelButtonText: 'Cancelar' }
    );

    if (!confirmacion?.isConfirmed) return;

    procesando.value = true;

    try {
        const { data } = await axios.post(route('performance_backups_purge'));

        alerta(
            'Copias eliminadas',
            `${data.purged?.files || 0} archivo(s) · ${formatoBytes(data.purged?.bytes || 0)} liberados.`,
            'success'
        );

        router.reload({ only: ['backups', 'images', 'summary', 'tabs'] });
    } catch (error) {
        alerta('Error', error?.response?.data?.message || 'No se pudieron eliminar las copias.', 'error');
    } finally {
        procesando.value = false;
    }
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
                        Revisa peso, resolución y dónde se muestra cada imagen. Solo se listan las imágenes que suben
                        los usuarios (storage/app/public); los archivos propios del proyecto no aparecen. Puedes
                        redimensionar, recomprimir sin cambiar la resolución, generar WebP, mover a cuarentena y
                        deshacer con las copias de seguridad. Pulsa una miniatura para verla y comparar cómo quedaría
                        antes de aplicarlo, también en cuarentena.
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
                            texto="Vuelve a leer storage/app/public para detectar imágenes subidas, borradas o modificadas. Puede tardar unos segundos."
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

            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <font-awesome-icon :icon="faRotateLeft" class="mt-1 text-primary" />
                    <div>
                        <h3 class="font-semibold text-gray-800 dark:text-white">Copias de seguridad</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ backups.files || 0 }} archivo(s) · {{ formatoBytes(backups.bytes) }} · se conservan
                            {{ limits.backup_days }} días
                            <span v-if="backups.oldest"> · más antigua: {{ backups.oldest }}</span>
                        </p>
                        <p
                            class="mt-1 text-xs"
                            :class="backups.expired_files ? 'text-warning' : 'text-gray-400'"
                        >
                            {{
                                backups.expired_files
                                    ? `${backups.expired_files} archivo(s) ya superaron la retención (${formatoBytes(backups.expired_bytes)}).`
                                    : 'Ninguna copia superó todavía la retención.'
                            }}
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm shrink-0"
                    :disabled="procesando || !backups.expired_files"
                    :title="
                        backups.expired_files
                            ? `Borra las copias con más de ${limits.backup_days} días; libera espacio del disco`
                            : `Solo se pueden borrar copias con más de ${limits.backup_days} días`
                    "
                    @click="limpiarCopias"
                >
                    <font-awesome-icon :icon="faTrash" class="mr-2" />
                    Eliminar copias antiguas
                </button>
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
                            <strong class="text-gray-600 dark:text-gray-300">Visor:</strong>
                            pulsa la miniatura de cualquier fila (también de cuarentena) para verla ajustada, al 100 %
                            y con una vista previa del resultado antes de comprimir o reducir.
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
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            :disabled="procesando || !seleccionEliminable.length"
                            :title="
                                seleccionEliminable.length
                                    ? 'Borra definitivamente los archivos que ya cumplieron el plazo en cuarentena'
                                    : `Solo se pueden eliminar después de ${limits.quarantine_days} días en cuarentena`
                            "
                            @click="eliminarCuarentena()"
                        >
                            <font-awesome-icon :icon="faTrash" class="mr-2" />
                            Eliminar definitivamente
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
                                    <button
                                        v-if="image.url"
                                        type="button"
                                        class="group relative block"
                                        :title="`Ver ${image.name} y cómo quedaría al procesarla`"
                                        @click="abrirVisorDesdeLista(image)"
                                    >
                                        <img
                                            :src="image.url"
                                            :alt="image.name"
                                            loading="lazy"
                                            class="h-12 w-12 rounded-lg border border-gray-200 bg-gray-50 object-cover dark:border-gray-700 dark:bg-gray-800"
                                            @error="$event.target.style.visibility = 'hidden'"
                                        />
                                        <span
                                            class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/40 text-white opacity-0 transition-opacity group-hover:opacity-100"
                                        >
                                            <font-awesome-icon :icon="faExpand" class="text-xs" />
                                        </span>
                                    </button>
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
                                        <button
                                            v-if="image.url"
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Ver imagen y su vista previa"
                                            @click="abrirVisorDesdeLista(image)"
                                        >
                                            <font-awesome-icon :icon="faImage" />
                                        </button>

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
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                :disabled="procesando || !puedeEliminarCuarentena(image)"
                                                :title="
                                                    puedeEliminarCuarentena(image)
                                                        ? 'Eliminar definitivamente'
                                                        : `Disponible después de ${limits.quarantine_days} días en cuarentena (${diasEnCuarentena(image)} día(s))`
                                                "
                                                @click="eliminarCuarentena([image.id])"
                                            >
                                                <font-awesome-icon :icon="faTrash" />
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

        <ModalSimple :show="modal" :on-close="cerrarModal">
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
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary shrink-0"
                            title="Abre el visor y compara el resultado antes de procesar"
                            @click="abrirVisorDesdeProceso(primeraSeleccionada)"
                        >
                            <font-awesome-icon :icon="faExpand" class="mr-2" />
                            Ver y comparar
                        </button>
                    </div>

                    <template v-if="form.mode === 'resize'">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="preset in presets"
                                :key="preset"
                                type="button"
                                class="btn btn-sm"
                                :class="form.width === preset && !form.height ? 'btn-primary' : 'btn-outline-primary'"
                                :disabled="procesando"
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
                                <input
                                    v-model.number="form.width"
                                    type="number"
                                    min="16"
                                    max="8000"
                                    class="form-input w-full"
                                    :disabled="procesando"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Alto máximo (px, opcional)
                                </label>
                                <input
                                    v-model.number="form.height"
                                    type="number"
                                    min="16"
                                    max="8000"
                                    class="form-input w-full"
                                    placeholder="Automático"
                                    :disabled="procesando"
                                />
                            </div>
                        </div>
                    </template>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Calidad (30-100)
                        </label>
                        <input
                            v-model.number="form.quality"
                            type="number"
                            min="30"
                            max="100"
                            class="form-input w-full md:max-w-[200px]"
                            :disabled="procesando"
                        />
                        <p class="mt-1 text-xs text-gray-400">
                            82 es un buen equilibrio entre calidad y peso. La calidad se aplica a JPG y WebP; PNG se
                            codifica sin pérdida, así que solo reduce peso al cambiar de tamaño.
                        </p>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Se guarda una copia en el servidor durante {{ limits.backup_days }} días. Puedes deshacer desde
                                                    «Restaurar original».
                    </p>

                    <div v-if="progreso.total" class="space-y-2 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                            <p class="font-medium text-gray-800 dark:text-white">
                                {{ progreso.hechos }} de {{ progreso.total }} imagen(es)
                                <span v-if="progreso.actual" class="font-normal text-gray-500 dark:text-gray-400">
                                    · {{ progreso.actual }}
                                </span>
                            </p>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ progreso.cambiadas }} actualizada(s) · {{ progreso.omitidas }} sin cambios ·
                                {{ progreso.errores }} error(es)
                            </span>
                        </div>

                        <div
                            class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
                            role="progressbar"
                            :aria-valuenow="porcentajeProgreso"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div
                                class="h-full rounded-full bg-primary transition-all"
                                :style="{ width: `${porcentajeProgreso}%` }"
                            ></div>
                        </div>

                        <ul class="max-h-40 space-y-1 overflow-y-auto text-xs">
                            <li
                                v-for="item in progreso.items"
                                :key="item.path"
                                class="flex items-start justify-between gap-2"
                                :title="item.message || item.name"
                            >
                                <span class="min-w-0 break-all text-gray-600 dark:text-gray-300">{{ item.name }}</span>
                                <span class="shrink-0 font-medium" :class="claseEstado(item.estado)">
                                    {{ etiquetaEstado(item.estado) }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex justify-end pt-2">
                        <PrimaryButton type="button" :disabled="procesando" @click="ejecutarOptimizacion">
                            {{
                                procesando
                                    ? `Procesando ${progreso.hechos}/${progreso.total}…`
                                    : form.mode === 'compress'
                                      ? 'Comprimir'
                                      : 'Redimensionar'
                            }}
                        </PrimaryButton>
                    </div>
                </div>
            </template>
        </ModalSimple>

        <ModalSimple :show="webpModal" :on-close="() => (webpModal = false)">
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
        </ModalSimple>

        <!-- Visor: imagen completa, zoom y comparación con el resultado -->
        <div
            v-if="visor.abierto && visor.image"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/75 p-2 sm:p-4"
            @click.self="cerrarVisor"
        >
            <div
                class="flex max-h-[96vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800"
            >
                <div
                    class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-700"
                >
                    <div class="min-w-0">
                        <h3 class="truncate font-semibold text-gray-800 dark:text-white">
                            {{ visor.image.name }}
                            <span
                                v-if="visor.image.quarantined"
                                class="ml-1 rounded bg-warning/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-warning"
                            >
                                En cuarentena
                            </span>
                        </h3>
                        <p class="mt-0.5 break-all text-xs text-gray-500 dark:text-gray-400">
                            {{ visor.image.path }} · {{ visorNatural.width || visor.image.width || '—' }} ×
                            {{ visorNatural.height || visor.image.height || '—' }} ·
                            {{ formatoBytes(origenVisor.bytes || visor.image.bytes) }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <span class="group relative inline-flex">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                title="Cómo usar el visor y la vista previa"
                                @click="ayudaVisor"
                            >
                                <font-awesome-icon :icon="faCircleInfo" class="mr-2" />
                                ¿Cómo funciona?
                            </button>
                            <AyudaTip
                                izquierda
                                texto="La vista previa se calcula en el navegador con el ancho y la calidad elegidos; no modifica el archivo hasta que confirmes el procesado."
                            />
                        </span>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            title="Cerrar el visor"
                            @click="cerrarVisor"
                        >
                            <font-awesome-icon :icon="faXmark" />
                        </button>
                    </div>
                </div>

                <div class="grid min-h-0 flex-1 grid-cols-1 overflow-y-auto lg:grid-cols-[minmax(0,1fr)_340px] lg:overflow-hidden">
                    <!-- Imagen y zoom -->
                    <div class="flex min-h-0 flex-col border-b border-gray-200 dark:border-gray-700 lg:border-b-0 lg:border-r">
                        <div class="flex flex-wrap items-center gap-1.5 border-b border-gray-200 px-3 py-2 dark:border-gray-700">
                            <button
                                type="button"
                                class="btn btn-sm"
                                :class="visorZoom === null ? 'btn-primary' : 'btn-outline-secondary'"
                                title="Que la imagen quepa completa en la vista"
                                @click="aplicarZoom(null)"
                            >
                                Ajustar
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm"
                                :class="visorZoom === 1 ? 'btn-primary' : 'btn-outline-secondary'"
                                title="Tamaño real de la imagen, un píxel de pantalla por píxel de imagen"
                                @click="aplicarZoom(1)"
                            >
                                100 %
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                title="Reducir el zoom"
                                @click="alejar"
                            >
                                <font-awesome-icon :icon="faMagnifyingGlassMinus" />
                            </button>
                            <span class="min-w-[64px] text-center text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ etiquetaZoom }}
                            </span>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                title="Ampliar el zoom"
                                @click="acercar"
                            >
                                <font-awesome-icon :icon="faMagnifyingGlassPlus" />
                            </button>
                            <span class="ml-auto text-right text-xs text-gray-400">
                                El mismo zoom en las dos imágenes; desplázate para comparar
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-2 p-3 sm:grid-cols-2">
                            <!-- Original -->
                            <div
                                class="flex h-[32vh] flex-col overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 sm:h-[44vh]"
                            >
                                <p
                                    class="border-b border-gray-200 bg-gray-50 px-2 py-1 text-[11px] font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    Original
                                    <span class="font-normal text-gray-400">
                                        · {{ visorNatural.width || visor.image.width || '—' }} ×
                                        {{ visorNatural.height || visor.image.height || '—' }} ·
                                        {{ formatoBytes(origenVisor.bytes || visor.image.bytes) }}
                                    </span>
                                </p>
                                <div
                                    ref="panelOriginal"
                                    class="flex flex-1 items-start justify-center overflow-auto bg-[repeating-conic-gradient(#e5e7eb_0%_25%,#f9fafb_0%_50%)] bg-[length:20px_20px] p-2.5 [scrollbar-gutter:stable] dark:bg-[repeating-conic-gradient(#374151_0%_25%,#1f2937_0%_50%)]"
                                >
                                    <img
                                        :src="origenVisor.url || visor.image.url"
                                        :alt="visor.image.name"
                                        :style="estiloImagenVisor"
                                        class="shrink-0 rounded shadow-sm"
                                        @load="alCargarOriginal"
                                        @error="$event.target.style.visibility = 'hidden'"
                                    />
                                </div>
                            </div>

                            <!-- Resultado del procesado -->
                            <div
                                class="flex h-[32vh] flex-col overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 sm:h-[44vh]"
                            >
                                <p
                                    class="border-b border-gray-200 bg-gray-50 px-2 py-1 text-[11px] font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    Resultado
                                    <span
                                        v-if="vistaPrevia.estado === 'listo'"
                                        class="font-normal"
                                        :class="vistaPrevia.reducido ? 'text-success' : 'text-warning'"
                                    >
                                        · {{ vistaPrevia.width }} × {{ vistaPrevia.height }} ·
                                        {{ formatoBytes(vistaPrevia.bytes) }}
                                    </span>
                                    <span v-else class="font-normal text-gray-400">· calculando…</span>
                                </p>
                                <div
                                    class="flex flex-1 items-start justify-center overflow-auto bg-[repeating-conic-gradient(#e5e7eb_0%_25%,#f9fafb_0%_50%)] bg-[length:20px_20px] p-2.5 [scrollbar-gutter:stable] dark:bg-[repeating-conic-gradient(#374151_0%_25%,#1f2937_0%_50%)]"
                                >
                                    <p
                                        v-if="vistaPrevia.estado === 'calculando'"
                                        class="m-auto text-center text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Calculando vista previa…
                                    </p>
                                    <img
                                        v-else-if="vistaPrevia.estado === 'listo' && vistaPrevia.url"
                                        :src="vistaPrevia.url"
                                        :alt="`Vista previa de ${visor.image.name}`"
                                        :style="estiloImagenVistaPrevia"
                                        class="shrink-0 rounded shadow-sm"
                                    />
                                    <p
                                        v-else-if="vistaPrevia.estado === 'error'"
                                        class="m-auto text-center text-xs text-danger"
                                    >
                                        {{ vistaPrevia.message }}
                                    </p>
                                    <p v-else class="m-auto text-center text-xs text-gray-400">
                                        Elige el tamaño para ver la comparación.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vista previa del procesado -->
                    <div class="flex min-h-0 flex-col gap-3 p-4">
                        <div class="flex items-start gap-2">
                            <font-awesome-icon :icon="faCompress" class="mt-1 text-primary" />
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-white">
                                    Así quedaría al procesarla
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <template v-if="seleccionMultipleVistaPrevia">
                                        Se compara solo la primera imagen seleccionada ({{ seleccionados.length }}
                                        seleccionadas).
                                    </template>
                                    <template v-else>
                                        Cálculo en tu navegador; el archivo no se toca.
                                    </template>
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="preset in presets"
                                    :key="`vp-${preset}`"
                                    type="button"
                                    class="btn btn-sm"
                                    :class="
                                        vistaPreviaForm.mode === 'resize' && vistaPreviaForm.width === preset
                                            ? 'btn-primary'
                                            : 'btn-outline-primary'
                                    "
                                    @click="
                                        vistaPreviaForm.mode = 'resize';
                                        vistaPreviaForm.width = preset;
                                        vistaPreviaForm.height = null;
                                    "
                                >
                                    {{ preset }} px
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm"
                                    :class="vistaPreviaForm.mode === 'compress' ? 'btn-primary' : 'btn-outline-secondary'"
                                    title="Misma resolución, solo menos peso"
                                    @click="vistaPreviaForm.mode = 'compress'"
                                >
                                    Solo comprimir
                                </button>
                            </div>

                            <div
                                v-if="vistaPreviaForm.mode === 'resize'"
                                class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                            >
                                <label class="shrink-0" for="vp-ancho">Ancho máximo</label>
                                <input
                                    id="vp-ancho"
                                    v-model.number="vistaPreviaForm.width"
                                    type="number"
                                    min="16"
                                    max="8000"
                                    class="form-input h-8 w-24 py-1 text-xs"
                                />
                                <span>px</span>
                            </div>

                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <label class="shrink-0" for="vp-calidad">Calidad</label>
                                <input
                                    id="vp-calidad"
                                    v-model.number="vistaPreviaForm.quality"
                                    type="number"
                                    min="30"
                                    max="100"
                                    class="form-input h-8 w-24 py-1 text-xs"
                                    :disabled="visor.image.type === 'png'"
                                />
                                <span>
                                    {{ visor.image.type === 'png' ? 'El PNG se guarda sin pérdida' : '30-100' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-1 text-xs text-gray-600 dark:text-gray-300">
                            <p>
                                Original:
                                <strong>{{ formatoBytes(origenVisor.bytes || visor.image.bytes) }}</strong> ·
                                {{ visorNatural.width || visor.image.width || '—' }} ×
                                {{ visorNatural.height || visor.image.height || '—' }}
                            </p>
                            <template v-if="vistaPrevia.estado === 'listo'">
                                <p>
                                    Resultado: <strong>{{ formatoBytes(vistaPrevia.bytes) }}</strong> ·
                                    {{ vistaPrevia.width }} × {{ vistaPrevia.height }}
                                </p>
                                <p v-if="vistaPrevia.reducido" class="text-success">
                                    Ahorro estimado: {{ formatoBytes(ahorroVistaPrevia) }}
                                </p>
                                <p v-else class="text-warning">
                                    No reduce el peso: al procesar se dejaría el original.
                                </p>
                                <p v-if="vistaPrevia.message" class="text-gray-400">{{ vistaPrevia.message }}</p>
                            </template>
                        </div>

                        <p class="mt-auto text-[11px] leading-snug text-gray-400">
                            La vista previa es una estimación del navegador; el resultado final puede variar unos
                            pocos KB. Los PNG se codifican sin pérdida, por eso la calidad no los afecta.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
