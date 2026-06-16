import {
    faBellConcierge,
    faClipboardList,
    faGauge,
    faList,
    faUtensils,
    faMoneyBillWave,
    faEllipsisVertical,
    faBoxesStacked,
    faCartShopping,
    faClipboardCheck,
} from "@fortawesome/free-solid-svg-icons";

const menuRestaurant = {
    status: false,
    text: "Restaurante",
    icom: faBellConcierge,
    route: 'module',
    permissions: "res_dashboard",
    items: [
        {
            route: route("res_dashboard"),
            status: false,
            text: "Panel",
            icom: faGauge,
            permissions: "res_dashboard",
            dashboard: true,
        },
        {
            route: route("res_comandas_list"),
            status: false,
            text: "Comandas",
            icom: faUtensils,
            permissions: "res_comandas",
        },
        {
            route: route("res_menu_list"),
            status: false,
            text: "Carta Del Día",
            icom: faList,
            permissions: "res_menu",
        },
        {
            route: route("res_sales_create"),
            status: false,
            text: "Vender",
            icon: faMoneyBillWave,
            permissions: "res_venta_nuevo",
        },
        {
            route: route("res_sales_list"),
            status: false,
            text: "Listado de Ventas",
            permissions: "res_venta",
            icon: faEllipsisVertical,
        },
        {
            route: route("res_sales_cuisine"),
            status: false,
            text: "Cocina",
            icom: faClipboardList,
            permissions: "res_venta",
        },
        {
            route: route("res_supplies_list"),
            status: false,
            text: "Insumos",
            icom: faBoxesStacked,
            permissions: "res_insumos",
        },
        {
            route: route("res_supplies_purchase"),
            status: false,
            text: "Registrar compra",
            icom: faCartShopping,
            permissions: "res_insumos_compra",
        },
        {
            route: route("res_supplies_shopping_list"),
            status: false,
            text: "Lista para el mercado",
            icom: faClipboardCheck,
            permissions: "res_lista_compras",
        },
    ],
};

export default menuRestaurant;
