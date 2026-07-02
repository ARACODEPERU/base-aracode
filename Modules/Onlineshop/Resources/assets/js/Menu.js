import {
    faCube,
    faGlobe,
    faCircleDollarToSlot,
    faTriangleExclamation
} from "@fortawesome/free-solid-svg-icons";

const menuOnlineshop = {
    status: false,
    text: 'Comercio Online',
    icom: faGlobe,
    route: 'module',
    permissions: 'onli_dashboard',
    items: [
        {
            route: route('onlineshop_items'),
            status: false,
            text: 'Productos Web',
            permissions: 'onli_items',
            icom: faCube,
            info: {
                title: "Gestión de Productos para la pagina Web",
                content: `<p class="text-sm text-gray-500 mb-3">
                       Configuración de productos que se mostrarán en la web
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>➕ <span>Crear productos o servicios.</span></li>
                        <li>✏️ <span>Editar productos o servicios.</span></li>
                        <li>🗑️ <span>Eliminar registro.</span></li>
                    </ul>`,
                placement: 'right'
            }
        },
        {
            route: route('onlineshop_sales'),
            status: false,
            text: 'Pedidos en Línea',
            permissions: 'onli_pedidos',
            icom: faGlobe,
            info: {
                title: "Gestión de Pedidos en Línea",
                content: `<p class="text-sm text-gray-500 mb-3">
                       Gestión desde el carrito de compras
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>➕ <span>Crear pedido.</span></li>
                        <li>✉️ <span>Enviar correo con comprobante de venta.</span></li>
                        <li>🛒 <span>Ventas Especiales.</span></li>
                    </ul>`,
                placement: 'right'
            }
        },
        {
            route: route('onlineshop_payment_problems'),
            status: false,
            text: 'Problemas de Pago',
            permissions: 'onli_pedidos',
            icom: faTriangleExclamation,
            info: {
                title: "Problemas de Pago y Carritos Abandonados",
                content: `<p class="text-sm text-gray-500 mb-3">
                       Gestión de pagos inconclusos y carritos abandonados
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>🔴 <span>Pagos aprobados sin completar registro.</span></li>
                        <li>🟡 <span>Carritos abandonados (sin pago).</span></li>
                        <li>🗑️ <span>Limpiar registros antiguos (+30 días).</span></li>
                    </ul>`,
                placement: 'right'
            }
        },

    ]
};
export default menuOnlineshop;
