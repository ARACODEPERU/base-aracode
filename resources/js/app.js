import "./bootstrap";
import "../css/app.css";
import "../../public/themes/vristo/css/app.css"
import 'easymde/dist/easymde.min.css';
import "./aracodito";
import "./alerta-sistema";

import { createApp, h } from "vue";
import VueGates from "vue-gates";
import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from 'ziggy-js';
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import VueTheMask from "vue-the-mask";
import Permissions from "./Plugins/Permissions";
import { createPinia } from 'pinia';
import i18n from '@/i18n';
import { createHead } from '@vueuse/head';
import appSetting from '@/app-setting';
import { TippyPlugin } from 'tippy.vue';
import { PerfectScrollbarPlugin } from 'vue3-perfect-scrollbar';
import HeightTransition from '@/Components/vristo/transitions/HeightTransition.vue';
import Popper from 'vue3-popper';
import * as Maska from 'maska';
import VueKonva from 'vue-konva';
import { setCsrfToken } from '@/utils/csrf';
import {
    initSuperEditorState,
    syncSuperEditorFromPage,
} from 'Modules/Security/Resources/assets/js/stores/superEditor';

const appName =
    window.document.getElementsByTagName("title")[0]?.innerText || "Laravel";

const pinia = createPinia();
const head = createHead();

const authPaths = [
    "/login",
    "/logout",
    "/forgot-password",
    "/reset-password",
    "/verify-email",
];

const buildLoginRedirectUrl = () => {
    const currentLocation = `${window.location.pathname}${window.location.search}`;

    if (authPaths.some((path) => window.location.pathname.startsWith(path))) {
        return "/login";
    }

    return `/login?redirect_to=${encodeURIComponent(currentLocation)}`;
};

createInertiaApp({
    //title: (title) => `${title} - ${appName}`,
    title: (title) => `${appName} - ${title}`,
    resolve: (name) => {
        let parts = name.split("::");
        if (parts.length > 1) {
            return resolvePageComponent(
                `../../Modules/${parts[0]}/Resources/assets/js/Pages/${parts[1]}.vue`,
                import.meta.glob(
                    "../../Modules/*/Resources/assets/js/Pages/**/*.vue"
                )
            );
        } else {
            return resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob("./Pages/**/*.vue")
            );
        }
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue, Ziggy)
            .use(VueTheMask)
            .use(VueGates)
            .use(Permissions, props.initialPage?.props)
            .use(i18n)
            .use(head)
            .use(TippyPlugin)
            .use(Maska)
            .use(PerfectScrollbarPlugin)
            .use(VueKonva)
            .component('Popper', Popper)
            .component('HeightTransition', HeightTransition)
            .component("font-awesome-icon", FontAwesomeIcon);

        //.mount(el);
        // Agregar el código de manejo de redireccionamiento
        app.mixin({
            mounted() {
                router.on("error", (error) => {
                    if (error.response && error.response.status === 401) {
                        router.visit(buildLoginRedirectUrl(), { replace: true });
                    }
                });
            },
        });
        appSetting.init();

        // Aracodito es global: al iniciar otra vista se retira el aviso anterior.
        router.on('navigate', () => {
            window.Aracodito?.hide('navigation');
        });

        router.on('success', (event) => {
            const page = event.detail?.page ?? event.page;
            const token = page?.props?.csrf_token;
            setCsrfToken(token);

            // Aracodito: la preferencia viaja en auth.user, así que se refresca
            // en cada navegación. Sin esto, window.USER_CONFIG quedaba con el
            // valor del primer documento y los avisos seguían saliendo con
            // Swal aunque el asistente estuviera activo en la cuenta.
            const usuario = page?.props?.auth?.user;

            if (usuario) {
                window.USER_CONFIG = {
                    ...(window.USER_CONFIG || {}),
                    aracodito_enabled: Boolean(Number(usuario.aracodito_enabled)),
                    aracodito_audio_enabled: Boolean(Number(usuario.aracodito_audio_enabled)),
                    aracodito_ia_enabled: Boolean(Number(usuario.aracodito_ia_enabled)),
                };
            }

            // Segundo punto de sincronización del Modo Super Editor: se ejecuta
            // justo después de actualizar la página y antes de que Vue repinte,
            // así que las directivas que se vuelvan a montar ya ven el modo.
            syncSuperEditorFromPage(page?.props);
        });

        setCsrfToken(props.initialPage?.props?.csrf_token);

        // Primer punto de sincronización: antes de montar, porque las directivas
        // v-can de la primera página se montan antes que cualquier componente.
        initSuperEditorState(pinia, props.initialPage?.props?.superEditor);

        return app.mount(el);
    },
    progress: {
        // The delay after which progress bar will appear, in milliseconds...
        delay: 100, // Reducido para mejor UX

        // The color of the progress bar...
        color: "#4B5563",

        // Whether to include default NProgress styles...
        includeCSS: true,

        // Whether NProgress spinner will be shown...
        showSpinner: false,
    },
});
