import {
    faGear,
    faBuilding,
    faDiamond,
    faBookmark,
    faLockOpen,
    faUser,
    faDna,
    faClock
} from "@fortawesome/free-solid-svg-icons";

const menuConfig = {
    status: false,
    text: "Configuraciones",
    icom: faGear,
    route: null,
    permissions: "configuracion",
    items: [
        {
            route: route("company_show"),
            status: false,
            icom: faBuilding,
            text: "Empresa",
            permissions: "empresa",
        },
        {
            route: route("modulos.index"),
            status: false,
            text: "Modulos",
            icom: faDiamond,
            permissions: "modulos",
        },
        {
            route: route("roles.index"),
            status: false,
            text: "Roles",
            icom: faBookmark,
            permissions: "roles",
        },
        {
            route: route("permissions.index"),
            status: false,
            text: "Permisos",
            icom: faLockOpen,
            permissions: "permisos",
        },
        {
            route: route("users.index"),
            status: false,
            text: "usuarios",
            icom: faUser,
            permissions: "usuarios",
        },
        {
            route: route("parameters"),
            status: false,
            text: "Parámetros del sistema",
            icom:faDna,
            permissions: "parametros",
        },
        {
            route: route("user_activity_logs"),
            status: false,
            text: "Historial de Actividades",
            icom: faClock,
            permissions: "conf_historial_actividades",
        },

    ],
};
export default menuConfig;
