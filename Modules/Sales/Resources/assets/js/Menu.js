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
    faFileFragment,
    faFileCircleQuestion,
    faCircleDollarToSlot,
    faHandHoldingDollar,
} from "@fortawesome/free-solid-svg-icons";

const menuSales = [
    {
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
                text: "Establecimiento",
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
        status: true,
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
            },
            // {
            //     route: route('reports_invoice'),
            //     status: false,
            //     text: 'Reportes',
            //     permissions: 'invo_reportes',
            //     icom: faChartLine,
            // }
        ]
    },
    {
        status: true,
        text: 'Cuentas por cobrar',
        icom: faHandHoldingDollar,
        route: 'module',
        permissions: 'acco_dashboard',
        items: [
            {
                route: route('acco_document_list'),
                status: false,
                text: 'Documentos al Crédito',
                permissions: 'acco_documento_listado',
                icom: faFileCircleQuestion,
                info: {
                    title: "Documentos al Crédito",
                    content: `<p class="text-sm text-gray-500 mb-3">
                        Registrar pagos sin emitir comprobantes
                        </p>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li>✔ <span> Se entiende que son ventas a crédito</span></li>
                            <li>✔ <span>Solo se registran pagos.</span></li>
                            <li>✔ <span>No genera boletas/facturas por los abonos.</span></li>
                        </ul>`,
                    placement: 'right'
                }
            },
            {
                route: route('acco_sales_special_rates'),
                status: false,
                text: 'Gestión Ventas en Cuotas',
                permissions: 'acco_pagos_cuotas_especiales',
                icom: faCircleDollarToSlot,
                info: {
                    title: "Gestión Ventas en Cuotas",
                    content: `<p class="text-sm text-gray-500 mb-3">
                        Registrar pagos con emisión de factura/boleta por cada cuota
                        </p>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li>✔ <span>Se entiende que es un financiamiento por cuotas.</span></li>
                            <li>✔ <span>Sí genera documento de venta por cada pago.</span></li>
                        </ul>`,
                    placement: 'right'
                }
            },
        ]
    },

];

export default menuSales;
