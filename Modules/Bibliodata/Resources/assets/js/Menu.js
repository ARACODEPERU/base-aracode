import {
    faBookReader,
    faBook,
    faGripVertical,
    faUserPen,
    faTags,
    faLayerGroup,
    faBuilding,
    faIdCard,
} from "@fortawesome/free-solid-svg-icons";

const menuBibliodata = {
    status: false,
    text: "Biblio Data",
    icom: faBookReader,
    route: 'module',
    permissions: "bib_dashboard",
    items: [
        {
            route: route("bib_authors"),
            status: false,
            text: "Autores",
            icom: faUserPen,
            permissions: "bib_autores_listar",
        },
        {
            route: route("bib_categories"),
            status: false,
            text: "Categorías",
            icom: faGripVertical,
            permissions: "bib_categorias_listar",
        },
        {
            route: route("bib_tags"),
            status: false,
            text: "Tags",
            icom: faTags,
            permissions: "bib_tags_listar",
        },
        {
            route: route("bib_books"),
            status: false,
            text: "Libros",
            icom: faBook,
            permissions: "bib_libros_listado",
        },
        {
            route: route("bib_subscription_plans"),
            status: false,
            text: "Planes",
            icom: faLayerGroup,
            permissions: "bib_planes_listar",
        },
        {
            route: route("bib_organizations"),
            status: false,
            text: "Organizaciones",
            icom: faBuilding,
            permissions: "bib_organizaciones_listar",
        },
        {
            route: route("bib_subscriptions"),
            status: false,
            text: "Suscripciones",
            icom: faIdCard,
            permissions: "bib_suscripciones_listar",
        },
    ],
};

export default menuBibliodata;
