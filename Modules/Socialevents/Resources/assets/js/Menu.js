import {
    faMasksTheater,
    faLayerGroup,
    faBuildingFlag,
    faElevator,
    faTicket,
    faPeopleGroup,
    faBoltLightning,
    faGauge,
    faHouseChimney,
    faFutbol,
} from "@fortawesome/free-solid-svg-icons";

const menuSocialevents = {
    status: false,
    text: "Eventos",
    icom: faMasksTheater,
    route: "module",
    permissions: "even_dashboard",
    items: [
        {
            route: route("even_dashboard"),
            status: false,
            text: "Dashboard",
            permissions: "even_dashboard",
            icom: faGauge,
            dashboard: true,
        },
        {
            route: route("even_categories_list"),
            status: false,
            text: "Categorías",
            permissions: "even_categoria_listado",
            icom: faLayerGroup,
            sportsHidden: true,
        },
        {
            route: route("even_local_list"),
            status: false,
            text: "Locales",
            permissions: "even_local_listado",
            icom: faBuildingFlag,
        },
        {
            route: route("even_alquiler_local_index"),
            status: false,
            text: "Alquiler de local",
            permissions: "even_alquiler_local_listado",
            icom: faHouseChimney,
        },
        {
            route: route("even_eventos_list"),
            status: false,
            text: "Eventos",
            sportsText: "Torneos",
            permissions: "even_evento_listado",
            icom: faElevator,
        },
        {
            route: route("even_equipos_listado"),
            status: false,
            text: "Equipos",
            permissions: "even_equipos_listado",
            icom: faPeopleGroup,
        },
        {
            route: route("even_ediciones_listado"),
            status: false,
            text: "Ediciones",
            permissions: "even_ediciones_listado",
            icom: faBoltLightning,
        },
        {
            route: route("even_tickets_listado"),
            status: false,
            text: "Ticket Vendidos",
            permissions: "even_ventas_listado",
            icom: faTicket,
            sportsHidden: true,
        },
        {
            route: route("even_organizations.index"),
            status: false,
            text: "Organizaciones y tarifas",
            permissions: "even_dashboard",
            sportsOnly: true,
            superAdminOnly: true,
            icom: faBuildingFlag,
        },
    ],
};

export const configureSocialeventsMenu = (sportsMode) => {
    if (! sportsMode) {
        return menuSocialevents;
    }

    return {
        ...menuSocialevents,
        text: "GESTIÓN DEPORTIVA",
        icom: faFutbol,
        items: menuSocialevents.items
            .filter((item) => ! item.sportsHidden && (! item.sportsOnly || sportsMode))
            .map((item) => ({
                ...item,
                text: item.sportsText ?? item.text,
            })),
    };
};

export default menuSocialevents;
