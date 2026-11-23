import { faCartShopping, faFileInvoice, faHandshake } from "@fortawesome/free-solid-svg-icons";

const menuPurchases = {
    status: false,
    text: "Compras",
    icom: faCartShopping,
    route: 'module',
    permissions: "purc_dashboard",
    items: [
        {
            route: route("providers.index"),
            status: false,
            text: "Proveedores",
            permissions: "proveedores",
            icom: faHandshake,
        },
        {
            route: route("purc_documents_list"),
            status: false,
            text: "Documentos",
            permissions: "purc_documentos_listado",
            icom: faCartShopping,
        },
        {
            // Registro de Compras Electrónico del período (SIRE).
            route: route("sire_rce_compras_index"),
            status: false,
            text: "Registro de compras (SIRE)",
            permissions: "sire_rce_compras",
            icom: faFileInvoice,
        },
        
    ],
};
export default menuPurchases;
