import {
    faLink,
    faPlus,
    faExclamationTriangle,
    faFileAlt,
} from "@fortawesome/free-solid-svg-icons";
// menuRoute() no lanza cuando la ruta no esta en el listado de Ziggy (modulo
// deshabilitado o nombre desincronizado).
import { menuRoute } from "@/utils/menuRoute";

const menuIntegrationhub = {
    status: true,
    text: "Centro de Integraciones",
    icom: faLink,
    route: 'module',
    permissions: "integrationhub_listado",
    items: [
        {
            status: false,
            route: menuRoute("integrationhub_listado"),
            text: "Listado de Integraciones",
            permissions: "integrationhub_listado",
            icom: faLink,
        },
        {
            status: false,
            route: menuRoute("integrationhub_create"),
            text: "Nueva Integración",
            permissions: "integrationhub_crear",
            icom: faPlus,
        },
        {
            status: false,
            route: menuRoute("integrationhub_errores"),
            text: "Errores de Integración",
            permissions: "integrationhub_listado",
            icom: faExclamationTriangle,
        },
        {
            status: false,
            route: menuRoute("integrationhub_flow_ids"),
            text: "Plantillas / Flujos",
            permissions: "integrationhub_listado",
            icom: faFileAlt,
        },
    ],
};

export default menuIntegrationhub;
