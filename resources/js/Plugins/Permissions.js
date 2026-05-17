import { usePage } from "@inertiajs/vue3";

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
    }
}
