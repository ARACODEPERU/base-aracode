import {
    faBook,
    faGripVertical,
} from "@fortawesome/free-solid-svg-icons";

const menuBibliodata = {
    status: false,
    text: "Biblio Data",
    icom: faBook,
    route: 'module',
    permissions: "aca_dashboard",
    items: [
        {
            route: route("bib_categories"),
            status: false,
            text: "Categorías",
            icom: faGripVertical,
            permissions: "bib_categorias_listar",
            info: {
                title: "Gestión de Categorías",
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Aquí puede administrar toda la información de las Categorías:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>✏️ <span>Editar Categoría.</span></li>
                        <li>➕ <span>Nueva Categoría.</span></li>
                        <li>🗑️ <span>Eliminar Categoría.</span></li>
                    </ul>
                `,
                placement: 'right'
            }
        },
        {
            route: route("bib_book"),
            status: false,
            text: "Libros",
            icom: faGripVertical,
            permissions: "bib_libros_listado",
            info: {
                title: "Gestión de libros",
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Aquí puede administrar toda la información de los libros:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>✏️ <span>Editar Categoría.</span></li>
                        <li>➕ <span>Nueva Categoría.</span></li>
                        <li>🗑️ <span>Eliminar Categoría.</span></li>
                    </ul>
                `,
                placement: 'rightBottom'
            }
        },
    ],
};



// Llamamos la función para cargar los docentes al menú
export default menuBibliodata;
