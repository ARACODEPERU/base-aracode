import {
    faGauge,
    faBriefcase,
    faFileSignature,
    faHandshake,
    faUsers,
} from "@fortawesome/free-solid-svg-icons";

const menuCommercial = {
    status: false,
    text: "Comercial",
    icom: faBriefcase,
    route: "module",
    permissions: "comm_dashboard",
    items: [
        {
            route: route("comm_dashboard"),
            status: false,
            text: "Dashboard",
            icom: faGauge,
            permissions: "comm_dashboard",
            dashboard: true,
        },
        {
            route: route("comm_clients"),
            status: false,
            text: "Clientes",
            icom: faUsers,
            permissions: "comm_clientes_listado",
        },
        {
            route: route("comm_contracts"),
            status: false,
            text: "Contratos",
            icom: faFileSignature,
            permissions: "comm_contratos_listado",
        },
        {
            route: route("comm_negotiations"),
            status: false,
            text: "Negociaciones",
            icom: faHandshake,
            permissions: "comm_negociaciones_listado",
        },
    ],
};

export default menuCommercial;
