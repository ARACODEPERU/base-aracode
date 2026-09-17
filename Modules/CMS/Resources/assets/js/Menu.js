import {
    faEarthAmericas,
    faCube,
    faPlaneArrival,
    faArrowTrendUp,
    faTable,
    faBlog,
    faFeather,
    faPeopleGroup,
    faFaceGrinStars,
    faSitemap,
    faEnvelope,
    faUsers
} from "@fortawesome/free-solid-svg-icons";
import { menuRoute } from "@/utils/menuRoute";

const menuCMS = {
    status: false,
    text: "CMS",
    icom: faEarthAmericas,
    route: 'module',
    permissions: "cms_dashboard",
    items: [
        {
            route: menuRoute("cms_items_list"),
            status: false,
            text: "Items",
            permissions: "cms_items",
            icom: faCube,
        },
        {
            route: menuRoute("cms_section_list"),
            status: false,
            text: "Secciones",
            permissions: "cms_seccion",
            icom: faTable,
        },
        {
            route: menuRoute("cms_pages_list"),
            status: false,
            text: "Paginas",
            permissions: "cms_pagina",
            icom: faEarthAmericas,
        },
        // {
        //     route: route("establishments.index"),
        //     status: false,
        //     text: "Centros de distribución",
        //     permissions: "sale_tienda",
        //     icom: faEarthAmericas,
        // },
        {
            route: menuRoute("blog-category.index"),
            status: false,
            text: "Blog Categorías",
            permissions: "blog_categorias",
            icom: faFeather,
        },
        {
            route: menuRoute("blog-article.index"),
            status: false,
            text: "Blog Artículos",
            permissions: "blog_articulos",
            icom: faBlog,
        },
        {
            route: menuRoute("blog_subscriber"),
            status: false,
            text: "Descargas de Brochures",
            permissions: "cms_subscribers_list",
            icom: faPeopleGroup
        },
        {
            route: menuRoute("cms_contact_messages_list"),
            status: false,
            text: "Mensajes de Contacto",
            permissions: "cms_mensajes_contacto",
            icom: faEnvelope,
        },
        {
            route: menuRoute("cms_blog_subscribers_list"),
            status: false,
            text: "Suscriptores Blog",
            permissions: "cms_blog_suscriptores",
            icom: faUsers,
        },
        {
            route: menuRoute("cms_testimonies_list"),
            status: false,
            text: "Testimonios",
            permissions: "cms_testimonios",
            icom: faFaceGrinStars,
        },
        {
            route: menuRoute("cms_advertising_list"),
            status: false,
            text: "Publicidad",
            permissions: "cms_publicidad",
            icom: faArrowTrendUp,
        },
        {
            route: null,
            status: false,
            text: "Landings",
            permissions: "cms_landings",
            icom: faPlaneArrival,
            items: [
                {
                    route: menuRoute("cms_landing_course_free",'01'),
                    status: false,
                    text: "Academico Curso Gratis",
                    permissions: "cms_landing_curso_gratis",
                    icom: faArrowTrendUp,
                }
            ]
        },
        {
            route: menuRoute("cms_sitemap"),
            status: false,
            text: "Sitemap",
            permissions: "cms_dashboard",
            icom: faSitemap,
        },
        // Las entradas sin ruta resoluble (ruta inexistente en Ziggy) se omiten;
        // las que son solo contenedores de subitems se conservan.
    ].filter((item) => item.route !== null || item.items?.length),
};
export default menuCMS;
