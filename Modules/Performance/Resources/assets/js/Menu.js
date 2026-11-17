import { faGaugeHigh, faImages, faLayerGroup, faWeightScale } from "@fortawesome/free-solid-svg-icons";

const menuPerformance = {
    status: false,
    text: "Rendimiento",
    icom: faGaugeHigh,
    route: "module",
    permissions: "performance_images",
    items: [
        {
            route: route("performance_images_index"),
            status: false,
            text: "Imágenes",
            permissions: "performance_images",
            icom: faImages,
        },
        {
            route: route("performance_weight_index"),
            status: false,
            text: "Peso por página",
            permissions: "performance_weight",
            icom: faWeightScale,
        },
        {
            route: route("performance_queue_index"),
            status: false,
            text: "Colas y errores",
            permissions: "performance_queue",
            icom: faLayerGroup,
        },
    ],
};

export default menuPerformance;
