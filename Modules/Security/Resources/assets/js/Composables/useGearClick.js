import { onBeforeUnmount, onMounted } from 'vue';
import { gearOwnsClick } from 'Modules/Security/Resources/assets/js/Plugins/superEditorGearHit';

/**
 * Reconoce el click que pertenece a un engrane (el flotante y los de las filas
 * del menú comparten este camino).
 *
 * Los engranes llevan `pointer-events: none`, así que su click propio se
 * detecta aquí y se registra en fase de CAPTURA para poderlo cancelar antes de
 * que llegue al elemento decorado, que es lo que evita que configurar un
 * permiso dispare también la acción del botón o del enlace de debajo.
 *
 * @param {() => DOMRect|null} rect        Rectángulo visible del engrane.
 * @param {() => boolean} active           ¿El engrane está visible y disponible?
 * @param {() => void} onOwnClick          Qué hacer cuando el click es suyo.
 */
export function useGearClick({ rect, active, onOwnClick }) {
    const handle = (event) => {
        if (!gearOwnsClick(event, rect(), active())) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        onOwnClick();
    };

    onMounted(() => document.addEventListener('click', handle, true));
    onBeforeUnmount(() => document.removeEventListener('click', handle, true));
}
