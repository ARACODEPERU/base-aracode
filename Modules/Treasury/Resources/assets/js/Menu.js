import {
    faLandmark,
    faGauge,
    faBuildingColumns,
    faMoneyCheckDollar,
    faMoneyBillWave,
    faLink,
    faScaleBalanced,
    faTags,
} from "@fortawesome/free-solid-svg-icons";

const menuTreasury = {
    status: false,
    text: "Tesorería",
    icom: faLandmark,
    route: "module",
    permissions: "treasury_dashboard",
    items: [
        {
            route: route("treasury_dashboard"),
            status: false,
            text: "Dashboard",
            permissions: "treasury_dashboard",
            icom: faGauge,
        },
        {
            route: route("treasury_accounts_index"),
            status: false,
            text: "Cuentas",
            permissions: "treasury_cuentas",
            icom: faBuildingColumns,
        },
        {
            route: route("treasury_transactions_index"),
            status: false,
            text: "Movimientos",
            permissions: "treasury_movimientos",
            icom: faMoneyCheckDollar,
        },
        {
            route: route("treasury_cash_index"),
            status: false,
            text: "Efectivo",
            permissions: "treasury_efectivo",
            icom: faMoneyBillWave,
        },
        {
            route: route("treasury_collections_index"),
            status: false,
            text: "Cobros sin cuenta",
            permissions: "treasury_cobros_vincular",
            icom: faLink,
        },
        {
            route: route("treasury_reconciliation_index"),
            status: false,
            text: "Conciliación",
            permissions: "treasury_conciliacion",
            icom: faScaleBalanced,
        },
        {
            route: route("treasury_categories_index"),
            status: false,
            text: "Categorías",
            permissions: "treasury_categorias",
            icom: faTags,
        },
    ],
};
export default menuTreasury;
