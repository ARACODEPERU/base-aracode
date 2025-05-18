import {
    faCashRegister,
    faFileInvoiceDollar,
    faScrewdriverWrench,
    faChartLine,
    faLocationDot,
    faBolt,
    faMoneyBillTrendUp,
    faFileInvoice,
    faListOl,
    faFileLines,
    faFileArrowDown,
    faFileFragment
} from "@fortawesome/free-solid-svg-icons";

const menuSales = [{
        status: true,
        text: "Ventas",
        icom: faCashRegister,
        route: 'module',
        permissions: "sale_dashboard",
        items: [

            {
                status: false,
                route: null,
                text: "Administración",
                permissions: "productos",
                icom: faScrewdriverWrench,
                items: [
                    {
                        status: false,
                        route: route("clients.index"),
                        text: "Clientes",
                        permissions: "clientes",
                    },
                    {
                        status: false,
                        route: route("sale_brands_product_list"),
                        text: "Marcas",
                        permissions: "sale_marcas",
                    },
                    {
                        status: false,
                        route: route("sale_category_product_list"),
                        text: "Categoría",
                        permissions: "sale_categorias",
                    },
                    {
                        status: false,
                        route: route("products.index"),
                        text: "Productos",
                        permissions: "productos",
                    },
                    {
                        status: false,
                        route: route("sales_services"),
                        text: "Servicios",
                        permissions: "sale_servicios",
                    },
                ]
            },
            {
                route: route("establishments.index"),
                status: false,
                text: "Tiendas",
                permissions: "sale_tienda",
                icom: faLocationDot,
            },
            {
                route: route("pettycash.index"),
                status: false,
                text: "Caja Chica",
                permissions: "caja_chica",
                icom: faMoneyBillTrendUp
            },
            {
                route: route("sales.index"),
                status: false,
                text: "Punto de venta (POS)",
                permissions: "punto_ventas",
                icom: faCashRegister,
            },
            {
                route: route("sale_physical_document_list"),
                status: false,
                text: "Documento Físico",
                permissions: "sale_documento_fisico",
                icom: faFileInvoiceDollar,
            },
            {
                route: route("reports"),
                status: false,
                text: "Reportes",
                permissions: "sale_reportes",
                icom: faChartLine,
            },
        ],

    },
    {
        status:false,
        text: 'Facturación Electrónica',
        icom: faBolt,
        route: 'module',
        permissions: 'invo_dashboard',
        items: [
            {
                route: route('saledocuments_create'),
                status: false,
                text: 'Crear Documento',
                permissions: 'invo_documento',
                icom: faFileInvoice,
            },
            {
                route: route('saledocuments_list'),
                status: false,
                text: 'Lista de Documentos',
                permissions: 'invo_documento_lista',
                icom: faListOl,
            },
            {
                route: route('sale_credit_notes_list'),
                status: false,
                text: 'Notas de credito & debito',
                permissions: 'invo_nota_credito',
                icom: faFileFragment,
            },
            {
                route: route('salesummaries_list'),
                status: false,
                text: 'Resumen',
                permissions: 'invo_resumenes_lista',
                icom: faFileLines,
            },
            {
                route: route('low_communication_list'),
                status: false,
                text: 'Comunicacion de Baja',
                permissions: 'invo_comunicacion_baja',
                icom: faFileArrowDown,
            }
        ]
    }
];

export default menuSales;
