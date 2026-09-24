import { useSuperEditorStore } from 'Modules/Security/Resources/assets/js/stores/superEditor';
import {
    evaluateGate,
    kindForTag,
    labelForText,
    permissionOf,
} from 'Modules/Security/Resources/assets/js/Plugins/superEditorGateEvaluation';

/**
 * Adaptador DOM de las directivas de vue-gates (`v-can`, `v-permission`).
 *
 * Toda la decisión vive en superEditorGateEvaluation.js (probado en Node). Aquí
 * solo se traduce esa decisión al DOM:
 *
 *   - Modo APAGADO  -> comportamiento idéntico al de vue-gates: evalúa el gate
 *                      con los mismos métodos y elimina el elemento o aplica
 *                      los modificadores. No marca NADA en el DOM.
 *   - Modo ENCENDIDO -> nunca elimina; marca el elemento con data-super-* para
 *                      que el engrane sepa qué permiso, etiqueta y tipo tiene.
 *
 * Se registran desde resources/js/Plugins/Permissions.js, que se instala
 * después de `app.use(VueGates)`, así que estas versiones sustituyen a las
 * originales. `role` y `role-or-permission` se dejan intactas.
 */

let cachedStore = null;

const editorStore = () => {
    if (cachedStore) {
        return cachedStore;
    }

    try {
        cachedStore = useSuperEditorStore();
    } catch (error) {
        cachedStore = null;
    }

    return cachedStore;
};

/** Marca el elemento para que el engrane pueda configurarlo. */
const decorate = (el, binding, gates, decision) => {
    const permission = permissionOf(binding.value);

    el.dataset.superEl = '';
    el.dataset.superKind =
        decision?.kind || metaKind(binding) || kindForTag(el.tagName);
    el.dataset.superLabel =
        decision?.label || labelForText(el.innerText || el.textContent, permission);
    el.classList.add('super-editor-element');

    if (!permission) {
        return;
    }

    el.dataset.superPerm = permission;

    try {
        el.dataset.superGranted = gates?.hasPermission?.(permission) ? '1' : '0';
    } catch (error) {
        el.dataset.superGranted = '1';
    }
};

const metaKind = (binding) => (binding.value && typeof binding.value === 'object' ? binding.value.kind : null);

const registerSuperEditorDirectives = (app) => {
    const gates = () => app.config.globalProperties.$gates;

    const handler = (name) => (el, binding) => {
        const value = binding.value;

        if (!value) {
            console.error('You must specify a value in the directive.');

            return;
        }

        const decision = evaluateGate({
            gate: gates(),
            name,
            arg: binding.arg ?? null,
            value,
            modifiers: binding.modifiers,
            editorActive: !!editorStore()?.active,
        });

        if (decision.action === 'remove') {
            el.parentNode?.removeChild(el);

            return;
        }

        if (decision.action === 'assign') {
            Object.assign(el, decision.attributes);
        }

        if (decision.decorate) {
            decorate(el, binding, gates(), decision.decorate);
        }
    };

    app.directive('can', { mounted: handler('permission') });
    app.directive('permission', { mounted: handler('permission') });
};

export default registerSuperEditorDirectives;
