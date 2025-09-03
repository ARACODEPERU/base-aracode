import {
    faSchool,
    faPeopleRobbery
} from "@fortawesome/free-solid-svg-icons";

const menuCiglesia = {
    status: false,
    text: "Comunidad de la iglesia",
    icom: faSchool,
    route: 'module',
    permissions: "cigle_dashboard",
    items: [
        {
            route: null,
            status: false,
            text: "Miembros",
            permissions: "cigle_miembros",
            icom: faPeopleRobbery,
            items: [
                {
                    status: false,
                    route: route("cigle_member_list"),
                    text: "Listado",
                    permissions: "cigle_miembros",
                },
                {
                    status: false,
                    route: route("cigle_member_believing_create"),
                    text: "Ficha de nuevo creyente",
                    permissions: "cigle_creyente_nuevo",
                },
            ]
        },

    ],
};
export default menuCiglesia;
