import {
    faMasksTheater,
    faLayerGroup,
    faBuildingFlag,
    faElevator,
    faTicket,
    faPeopleGroup,
    faBoltLightning
} from "@fortawesome/free-solid-svg-icons";

const menuSocialevents = {
    status: false,
    text: "Eventos",
    icom: faMasksTheater,
    route: 'module',
    permissions: "even_dashboard",
    items: [
        {
            route: route("even_categories_list"),
            status: false,
            text: "Categorías",
            permissions: "even_categoria_listado",
            icom: faLayerGroup,
        },
        {
            route: route("even_local_list"),
            status: false,
            text: "Locales",
            permissions: "even_local_listado",
            icom: faBuildingFlag,
        },
        {
            route: route("even_eventos_list"),
            status: false,
            text: "Eventos",
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
        },
    ],
};
export default menuSocialevents;
