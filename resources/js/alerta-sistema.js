import Swal2 from 'sweetalert2';
import './aracodito';

/**
 * Puente de alertas del sistema.
 *
 * Uso:
 *   window.alertaSistema('Guardado', 'Se guardó correctamente', 'success');
 *   window.alertaSistema('Atención', 'Revisa los datos', 'warning', { timer: 4000 });
 *
 * Botones compatibles con Swal:
 *   const result = await window.alertaSistema('Eliminar', '¿Continuar?', 'warning', {
 *       showCancelButton: true,
 *       confirmButtonText: 'Sí, eliminar',
 *       cancelButtonText: 'Cancelar',
 *       duration: 0,
 *   });
 *   if (result.isConfirmed) { ... }
 *
 * Confirmación con proceso asíncrono (solo Aracodito):
 *   const result = await window.alertaSistema('Eliminar', '¿Continuar?', 'warning', {
 *       duration: 0,
 *       showCancelButton: true,
 *       onConfirm: async () => {
 *           const { data } = await axios.delete(url);
 *           return { message: data.message, type: 'success' };
 *       },
 *   });
 *
 * En Aracodito, showConfirmButton es true por defecto (igual que Swal).
 *
 * Decisión:
 *   - Si el usuario tiene Aracodito activo Y la vista actual está marcada como
 *     nueva, se muestra el mensaje con window.Aracodito.showMessage().
 *   - En cualquier otro caso se usa SweetAlert2, igual que en las vistas
 *     antiguas: window.alertaSistema() es compatible con ambas.
 *
 * Cómo marcar una vista como nueva (solo las vistas nuevas deben hacerlo):
 *   Vue/Inertia:
 *     <Head>
 *         <meta name="aracodito-view" content="new" />
 *     </Head>
 *   Blade:
 *     <meta name="aracodito-view" content="new">
 *   Alternativa dinámica:
 *     document.body.dataset.aracoditoView = 'new';
 *
 * Las vistas sin marca se consideran "antiguas" y siguen usando Swal.
 */

const NEW_VIEW_MARKERS = ['new', 'nueva'];

function isNewView() {
    if (typeof document === 'undefined') {
        return false;
    }

    const meta = document.querySelector('meta[name="aracodito-view"]');
    const marker = meta?.getAttribute('content') || document.body?.dataset?.aracoditoView || '';

    return NEW_VIEW_MARKERS.includes(String(marker).trim().toLowerCase());
}

function getUserConfig() {
    return (typeof window !== 'undefined' && window.USER_CONFIG) || {};
}

/**
 * Indica si las alertas de la vista actual saldran con Aracodito (usuario con
 * el asistente activo y vista marcada como nueva).
 *
 * Sirve a las vistas que necesitan `onConfirm`: con Aracodito la confirmacion
 * permanece visible con la terminal del pecho hasta que termina el proceso
 * asincrono, algo que SweetAlert2 no puede hacer, asi que la vista elige el
 * flujo con esto.
 */
function enAracodito() {
    const config = getUserConfig();

    return Boolean(config.aracodito_enabled && isNewView() && window.Aracodito?.showMessage);
}

/**
 * Aracodito entiende success | warning | error | confirm | help | info | question.
 * Los alias en español también se normalizan en aracodito.js.
 */
const ARACODITO_TYPES = {
    success: 'success',
    warning: 'warning',
    error: 'error',
    confirm: 'confirm',
    confirmacion: 'confirm',
    'confirmación': 'confirm',
    help: 'help',
    ayuda: 'help',
    info: 'info',
    informativo: 'info',
    question: 'question',
    pregunta: 'question',
};

function toAracoditoType(icon) {
    return ARACODITO_TYPES[String(icon || 'info').trim().toLowerCase()] || 'info';
}

/**
 * @param {string} title  Título (en Aracodito se usa como texto si no hay `text`).
 * @param {string} text   Texto principal.
 * @param {string} icon   success | error | warning | info | question
 * @param {object} options Opciones de Swal o de Aracodito (audio, ia, duration).
 * @returns {Promise|object} Promesa de Swal2 o la API de Aracodito.
 */
function alertaSistema(title, text, icon = 'info', options = {}) {
    const config = getUserConfig();
    const opts = options && typeof options === 'object' ? options : {};

    if (enAracodito()) {
        const duration = opts.duration ?? opts.timer;
        const showConfirmButton = opts.showConfirmButton !== undefined
            ? opts.showConfirmButton
            : true;

        return window.Aracodito.showMessage(text || title || '', toAracoditoType(icon), {
            ...opts,
            audio: config.aracodito_audio_enabled === true,
            ia: config.aracodito_ia_enabled === true,
            showConfirmButton,
            ...(duration !== undefined ? { duration } : {}),
        });
    }

    // Vistas antiguas o Aracodito desactivado: comportamiento clásico.
    return Swal2.fire({
        title,
        text,
        icon: icon || 'info',
        ...opts,
    });
}

alertaSistema.enAracodito = enAracodito;

if (typeof window !== 'undefined') {
    window.alertaSistema = alertaSistema;
}

export default alertaSistema;
