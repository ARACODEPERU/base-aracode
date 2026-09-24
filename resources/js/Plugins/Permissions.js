import { usePage } from "@inertiajs/vue3";
import registerSuperEditorDirectives from "Modules/Security/Resources/assets/js/Plugins/superEditorDirectives";

const getAuth = (fallbackProps = null) => {
    return usePage()?.props?.auth ?? fallbackProps?.auth ?? null;
};

const syncGates = (component, fallbackProps = null) => {
    const auth = getAuth(fallbackProps);

    if (auth !== null && component.$gates) {
        component.$gates.setRoles(auth.roles ?? []);
        component.$gates.setPermissions(auth.permissions ?? []);
    }
};

export default {

    install: (app, initialProps = null) => {
        const auth = getAuth(initialProps);

        if (auth !== null && app.config.globalProperties.$gates) {
            app.config.globalProperties.$gates.setRoles(auth.roles ?? []);
            app.config.globalProperties.$gates.setPermissions(auth.permissions ?? []);
        }

        app.mixin({
            beforeMount(){
                syncGates(this, initialProps);
            },
            mounted(){
                syncGates(this, initialProps);
            }
        })

        // Modo Super Editor: reemplaza v-can / v-permission después de que
        // VueGates registró las suyas (este plugin se instala después de
        // app.use(VueGates)), de modo que con el modo activo los elementos no se
        // oculten sino que queden marcados para el engrane. Con el modo apagado
        // el comportamiento es el mismo de siempre.
        registerSuperEditorDirectives(app);
    }
}
