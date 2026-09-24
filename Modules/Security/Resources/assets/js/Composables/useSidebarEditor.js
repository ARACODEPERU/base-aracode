import { useSuperEditorStore } from 'Modules/Security/Resources/assets/js/stores/superEditor';

/**
 * Lo que el menú lateral necesita saber del Modo Super Editor.
 *
 * Vive aquí y no repartido por las filas del menú para que la lógica del editor
 * sea una sola cosa: con el modo activo se muestran TAMBIÉN las filas que el rol
 * actual no puede abrir (son las que hay que configurar), se marcan para el
 * toggle y no navegan.
 *
 * Las filas NO traen su propio toggle: se marcan como cualquier otro elemento y
 * el toggle flotante (Gear.vue) las atiende, con el hueco que declara
 * `rowAttrs` en data-super-place.
 *
 * `hasPermission` se recibe como argumento a propósito: el menú ya tiene su
 * propio checker sobre `auth.permissions` y no se duplica esa lectura aquí.
 */
export function useSidebarEditor(hasPermission) {
    const store = useSuperEditorStore();

    /**
     * Se lee del store en cada render, sin `computed` de por medio: un
     * ComputedRef guardado dentro de un objeto NO se desenvuelve en la
     * plantilla (`editor.active` sería el ref, siempre verdadero), así que el
     * aviso del menú quedaría visible con el modo apagado. `store.active` sí es
     * reactivo, así que esto se vuelve a evaluar cuando el modo cambia.
     */
    const isActive = () => store.active;

    /** ¿La fila se muestra? Con el modo activo, siempre (bloqueada o no). */
    const reveals = (permissions) => isActive() || hasPermission(permissions);

    /** Fila que solo se ve porque el modo está activo. */
    const isLocked = (permissions) => isActive() && !hasPermission(permissions);

    /**
     * Atributos que marcan una fila para el toggle. Con el modo apagado
     * devuelve nada: la fila queda exactamente como siempre.
     *
     * @param {string} place  Hueco del toggle (ver superEditorPlacement.js):
     *                        `corner` en el rail de módulos, `before-end` en las
     *                        opciones (para no pisar el chevron) y `end` en las
     *                        subopciones.
     */
    const rowAttrs = (item, kind, place = 'end') => {
        if (!isActive()) {
            return {};
        }

        return {
            'data-super-el': '',
            'data-super-perm': permissionOf(item) ?? undefined,
            'data-super-label': item?.text ?? '',
            'data-super-kind': kind,
            'data-super-place': place,
            'data-super-granted': hasPermission(item?.permissions) ? '1' : '0',
        };
    };

    /**
     * Una fila sin acceso se muestra para poder configurarla, pero no navega:
     * el backend la bloquearía igual (no se relaja la seguridad). El click en el
     * engrane sí pasa, que es el punto de la fila.
     */
    const guardClick = (event, item) => {
        if (!isLocked(item?.permissions) || event.target?.closest?.('[data-super-gear]')) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
    };

    return {
        get active() {
            return isActive();
        },
        reveals,
        isLocked,
        rowAttrs,
        guardClick,
    };
}

/** Un menú declara su permiso como lista (para el v-can) y como nombre suelto. */
function permissionOf(item) {
    return Array.isArray(item?.permissions) ? item.permissions[0] : item?.permissions;
}
