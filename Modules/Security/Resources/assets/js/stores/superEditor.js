import { defineStore } from 'pinia';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

/**
 * Modo Super Editor — estado del cliente.
 *
 * Este store NO inventa estado: lo refleja desde el servidor. Los props
 * compartidos (`superEditor`) se sincronizan en dos puntos (ver
 * initSuperEditorState y syncSuperEditorFromPage más abajo) y eso es lo que
 * permite que las directivas v-can sepan si están en modo editor en el mismo
 * render en que se montan.
 *
 * Además: el borrador se guarda en el servidor, no aquí. Lo único que vive en
 * memoria es el panel abierto y el prompt de contraseña.
 *
 * Entrar al modo no pide contraseña (la puerta es el rol admin, que decide el
 * servidor). La contraseña se pide solo al salir aplicando un borrador con
 * cambios, porque es el único momento en que se escriben permisos.
 */

const emptyState = () => ({
    canUse: false,
    active: false,
    expiresAt: null,
    ttlMinutes: 30,
    dirty: 0,
    // Permisos con cambios en borrador: el toggle de cada elemento se pinta en
    // ámbar con esto, así que el dato viene del servidor en cada respuesta.
    stagedPermissions: [],
    protectedPermissions: [],
    role: 'admin',
});

const extractError = (error, fallback) => {
    const data = error?.response?.data;

    if (!data) {
        return fallback;
    }

    if (data.errors) {
        const first = Object.values(data.errors)[0];

        if (Array.isArray(first) && first[0]) {
            return first[0];
        }
    }

    return data.message || fallback;
};

const toast = (message, icon = 'success') => {
    if (typeof window === 'undefined') {
        return;
    }

    // sweetalert2 ya se usa en el proyecto (para no añadir dependencias).
    import('sweetalert2').then(({ default: Swal }) => {
        Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4200,
            timerProgressBar: true,
            customClass: { container: 'toast' },
        }).fire({ icon, title: message });
    }).catch(() => {
        // Sin librería de toasts disponible: el mensaje ya quedó en el panel.
    });
};

export const useSuperEditorStore = defineStore('superEditor', {
    state: () => ({
        ...emptyState(),
        panel: null,
        panelLoading: false,
        panelError: null,
        prompt: { open: false, mode: 'apply', error: '', busy: false },
        busy: false,
    }),

    getters: {
        /**
         * Segundos restantes de la sesión de edición (0 si ya venció).
         */
        remainingSeconds: (state) => {
            if (!state.active || !state.expiresAt) {
                return 0;
            }

            return Math.max(0, Math.floor((new Date(state.expiresAt).getTime() - Date.now()) / 1000));
        },

        panelPermission: (state) => state.panel?.permission ?? null,

        /**
         * ¿Este permiso tiene cambios sin aplicar? Lo usa el toggle del elemento
         * para pintarse en ámbar (mismo aviso que el contador de la cabecera).
         */
        hasDraft: (state) => (permission) =>
            !!permission && (state.stagedPermissions || []).includes(permission),

        /**
         * ¿Hay un cambio en borrador para este permiso y rol?
         */
        stagedValue: (state) => (permission, roleId) => {
            if (!state.panel?.staged) {
                return null;
            }

            const hit = state.panel.staged.find(
                (item) => item.permission === permission && item.role_id === roleId
            );

            return hit ? hit.allowed : null;
        },
    },

    actions: {
        /**
         * Aplica el estado que viene del servidor.
         */
        applyState(state) {
            if (!state) {
                return;
            }

            this.canUse = !!state.can_use;
            this.active = !!state.active;
            this.expiresAt = state.expires_at ?? null;
            this.ttlMinutes = state.ttl_minutes ?? 30;
            this.dirty = state.dirty ?? 0;
            this.stagedPermissions = Array.isArray(state.staged_permissions) ? state.staged_permissions : [];
            this.protectedPermissions = state.protected_permissions ?? [];
            this.role = state.role ?? 'admin';

            if (!this.active) {
                this.panel = null;
                this.stagedPermissions = [];
            }
        },

        // -----------------------------------------------------------------
        // Entrar / salir
        // -----------------------------------------------------------------

        /**
         * Abre el modal de contraseña. Solo hay un caso que la pida: aplicar el
         * borrador al salir.
         */
        openPrompt(mode = 'apply') {
            this.prompt = { open: true, mode, error: '', busy: false };
        },

        /**
         * Envío del formulario de contraseña (lo llama PasswordModal).
         *
         * No se guarda ninguna promesa pendiente a propósito: el modal se queda
         * abierto cuando la contraseña falla, así que cada intento tiene que
         * poder enviarse otra vez. Con una promesa de un solo uso, el segundo
         * intento se resolvía contra un resolver ya consumido y el botón
         * parecía no hacer nada.
         */
        submitPrompt(password) {
            if (!password || this.prompt.busy) {
                return null;
            }

            return this.exit(password, this.prompt.mode);
        },

        /**
         * Entra al modo editor. No pide contraseña: la única puerta es el rol
         * autorizado y el servidor la comprueba.
         *
         * Al terminar recarga la página sin preservar estado: es ese remount el
         * que vuelve a montar las directivas con el modo ya activo.
         */
        async enter() {
            this.busy = true;

            try {
                const { data } = await axios.post(route('super_editor_enter'));

                this.applyState(data.state);
                toast(data.message, 'success');
                this.reloadPage();

                return data;
            } catch (error) {
                toast(extractError(error, 'No se pudo activar el Modo Super Editor.'), 'error');

                return null;
            } finally {
                this.busy = false;
            }
        },

        /**
         * Salir del modo. Solo se pide la contraseña cuando hay un borrador con
         * cambios que aplicar: descartar, o salir sin cambios, no escribe nada
         * y no tiene nada que confirmar.
         */
        requestExit(mode = 'apply') {
            if (mode === 'apply' && this.dirty > 0) {
                this.openPrompt('apply');

                return null;
            }

            return this.exit(null, mode);
        },

        /**
         * Sale del modo. `mode` es 'apply' (aplica y registra) o 'discard'.
         */
        async exit(password, mode = 'apply') {
            this.prompt.busy = true;
            this.prompt.error = '';

            try {
                const { data } = await axios.post(route('super_editor_exit'), { password, mode });

                this.applyState(data.state);
                this.closePrompt();
                toast(data.message, mode === 'discard' ? 'info' : 'success');
                this.reloadPage();

                return data;
            } catch (error) {
                this.prompt.busy = false;
                this.prompt.error = extractError(error, 'No se pudo salir del Modo Super Editor.');

                return null;
            }
        },

        /**
         * Recarga la página SIN preservar estado del componente.
         *
         * Ojo: no se usa router.reload() porque Inertia 1.3 aplica sus valores
         * por defecto DELANTE de las opciones (`{ ...options, preserveState: true }`),
         * así que preserveState:false se ignora y el componente de página nunca se
         * vuelve a montar. Con router.visit() sí se remonta: y ese remount es lo que
         * hace que las directivas v-can vuelvan a evaluarse, que es justo lo que
         * enciende (o apaga) los engranes al entrar y al salir del modo.
         */
        reloadPage() {
            router.visit(window.location.href, { preserveScroll: true, preserveState: false });
        },

        // -----------------------------------------------------------------
        // Prompt de contraseña (lo pinta PasswordModal.vue)
        // -----------------------------------------------------------------

        closePrompt() {
            this.prompt = { open: false, mode: 'enter', error: '', busy: false };
        },

        // -----------------------------------------------------------------
        // Panel "Configurar permisos de acceso"
        // -----------------------------------------------------------------

        /**
         * Abre el panel para un elemento de la interfaz. `payload`:
         * { permission, label, kind, url, rect }.
         */
        async openPanel(payload) {
            this.panel = {
                ...payload,
                actions: [],
                roles: [],
                staged: [],
            };
            this.panelLoading = true;
            this.panelError = null;

            try {
                const { data } = await axios.get(route('super_editor_element'), {
                    params: {
                        permission: payload.permission,
                        label: payload.label,
                        kind: payload.kind,
                        url: payload.url,
                    },
                });

                this.panel = {
                    ...this.panel,
                    permission: data.panel.element.permission,
                    label: data.panel.element.label,
                    kind: data.panel.element.kind,
                    url: data.panel.element.url,
                    exists: data.panel.element.exists,
                    actions: data.panel.element.actions,
                    roles: data.panel.roles,
                    staged: data.panel.staged,
                };

                return data.panel;
            } catch (error) {
                this.panelError = extractError(error, 'No se pudieron cargar los permisos del elemento.');
                this.handleModeError(error);

                return null;
            } finally {
                this.panelLoading = false;
            }
        },

        closePanel() {
            this.panel = null;
            this.panelError = null;
            this.panelLoading = false;
        },

        /**
         * Deja un cambio en el borrador del servidor (no aplica nada todavía).
         */
        async stageChange(permission, roleId, allowed) {
            if (!this.panel) {
                return null;
            }

            this.busy = true;

            try {
                const { data } = await axios.post(route('super_editor_stage'), {
                    permission,
                    role_id: roleId,
                    allowed,
                    label: this.panel.label,
                    kind: this.panel.kind,
                    url: this.panel.url,
                });

                this.applyState(data.state);
                this.applyStagedLocally(permission, roleId, data.change ? allowed : null, data.change?.allowed_before ?? null);

                return data;
            } catch (error) {
                this.panelError = extractError(error, 'No se pudo guardar el cambio en el borrador.');
                this.handleModeError(error);

                return null;
            } finally {
                this.busy = false;
            }
        },

        /**
         * Refresca el panel tras un cambio (los roles y el borrador cambian).
         */
        applyStagedLocally(permission, roleId, stagedAllowed, allowedBefore) {
            if (!this.panel) {
                return;
            }

            const staged = this.panel.staged.filter(
                (item) => !(item.permission === permission && item.role_id === roleId)
            );

            if (stagedAllowed !== null) {
                staged.push({
                    permission,
                    role_id: roleId,
                    allowed: stagedAllowed,
                    allowed_before: allowedBefore,
                });
            }

            this.panel.staged = staged;
            this.panel.roles = this.panel.roles.map((role) => {
                if (role.id !== roleId) {
                    return role;
                }

                return {
                    ...role,
                    staged: { ...role.staged, [permission]: stagedAllowed },
                };
            });
        },

        /**
         * Crea el permiso al que apunta el elemento cuando todavía no existe.
         * Devuelve el mensaje de error si algo falla (null si todo fue bien).
         */
        async createPermission() {
            if (!this.panel?.permission) {
                return 'No hay un elemento seleccionado.';
            }

            this.busy = true;

            try {
                const { data } = await axios.post(route('super_editor_permission'), {
                    permission: this.panel.permission,
                    label: this.panel.label,
                    kind: this.panel.kind,
                    url: this.panel.url,
                });

                this.applyState(data.state);
                this.panel = {
                    ...this.panel,
                    exists: data.panel.element.exists,
                    actions: data.panel.element.actions,
                    roles: data.panel.roles,
                    staged: data.panel.staged,
                };
                toast(data.message, 'success');

                return null;
            } catch (error) {
                return extractError(error, 'No se pudo crear el permiso.');
            } finally {
                this.busy = false;
            }
        },

        /**
         * Retira del borrador todos los cambios del elemento abierto.
         */
        async revertElement() {
            if (!this.panel?.permission) {
                return null;
            }

            this.busy = true;

            try {
                const { data } = await axios.delete(route('super_editor_revert'), {
                    data: { permission: this.panel.permission },
                });

                this.applyState(data.state);
                this.panel.staged = this.panel.staged.filter(
                    (item) => item.permission !== this.panel.permission
                );
                this.panel.roles = this.panel.roles.map((role) => ({
                    ...role,
                    staged: Object.keys(role.staged || {}).reduce((carry, key) => {
                        carry[key] = key === this.panel.permission ? null : role.staged[key];

                        return carry;
                    }, {}),
                }));

                return data;
            } catch (error) {
                this.panelError = extractError(error, 'No se pudieron retirar los cambios del elemento.');
                this.handleModeError(error);

                return null;
            } finally {
                this.busy = false;
            }
        },

        /**
         * Si el servidor dice que la sesión de edición ya no existe (expirada o
         * cerrada en otra pestaña), el modo se apaga y se avisa al usuario.
         */
        handleModeError(error) {
            const code = error?.response?.data?.super_editor;

            if (!code || code === 'forbidden') {
                return;
            }

            this.applyState({ ...emptyState() });
            toast(error.response.data.message ?? 'La sesión de edición terminó.', 'warning');
        },
    },
});

/**
 * Primer punto de sincronización: antes de montar la app. Se llama desde
 * resources/js/app.js con el pinia ya creado, porque las directivas v-can de la
 * primera página se montan antes de que cualquier componente pueda sincronizar
 * nada por su cuenta.
 */
export function initSuperEditorState(pinia, superEditorProps) {
    const store = useSuperEditorStore(pinia);
    store.applyState(superEditorProps);

    return store;
}

/**
 * Segundo punto de sincronización: en cada respuesta de Inertia. Se dispara
 * justo después de actualizar la página y antes de que Vue repinte, así que las
 * directivas que se vuelvan a montar ya ven el modo correcto.
 */
export function syncSuperEditorFromPage(pageProps) {
    if (!pageProps || !Object.prototype.hasOwnProperty.call(pageProps, 'superEditor')) {
        return;
    }

    const store = useSuperEditorStore();
    store.applyState(pageProps.superEditor);

    // La página cambió: el panel estaba anclado a un elemento de la anterior, y
    // dejarlo abierto además taparía controles de la página nueva (dejaría de
    // comportarse como si el editor no existiera). Los cambios ya están en el
    // borrador del servidor, así que cerrarlo no pierde nada.
    store.closePanel();
}
