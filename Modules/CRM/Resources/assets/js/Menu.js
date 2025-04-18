import {
    faGear,
    faUserGroup,
    faComments,
    faEnvelopesBulk ,
    faBuildingUser,
    faPersonCircleQuestion
} from "@fortawesome/free-solid-svg-icons";

const menuCRM = {
    status: false,
    text: "Gestión de Clientes y Comunicación",
    icom: faGear,
    route: 'module',
    permissions: "crm_dashboard",
    items: [
        {
            route: route("crm_companies_list"),
            status: false,
            text: "Empresas",
            permissions: "crm_empresas_listado",
            icom: faBuildingUser,
        },
        {
            route: route("crm_contacts_list"),
            status: false,
            text: "Contactos",
            permissions: "crm_contactos_listado",
            icom: faUserGroup,
        },
        {
            route: route("crm_chat_dashboard"),
            status: false,
            text: "Chat",
            permissions: "crm_chat_dashboard",
            icom: faComments,
        },
        {
            route: route("crm_mailbox_dashboard"),
            status: false,
            text: "Buzón de correo",
            permissions: "crm_mailbox_dashboard",
            icom: faEnvelopesBulk,
        },
        {
            route: route("crm_common_questions"),
            status: false,
            text: "Dudas Comunes",
            permissions: "crm_dudas_comunes",
            icom: faPersonCircleQuestion,
        },
    ],
};

export default menuCRM;
