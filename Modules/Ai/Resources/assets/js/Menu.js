import {
    faRobot,
    faGauge,
    faSliders,
    faChartLine,
    faTags,
    faFlask,
} from '@fortawesome/free-solid-svg-icons';

const menuAi = {
    status: false,
    text: 'IA',
    icom: faRobot,
    route: 'module',
    permissions: 'ia_dashboard',
    items: [
        {
            route: route('ai_dashboard'),
            status: false,
            text: 'Dashboard',
            icom: faGauge,
            permissions: 'ia_dashboard',
        },
        {
            route: route('ai_configuracion'),
            status: false,
            text: 'Configuración',
            icom: faSliders,
            permissions: 'ia_configuracion',
        },
        {
            route: route('ai_consumo'),
            status: false,
            text: 'Consumo',
            icom: faChartLine,
            permissions: 'ia_consumo',
        },
        {
            route: route('ai_precios'),
            status: false,
            text: 'Precios',
            icom: faTags,
            permissions: 'ia_precios',
        },
        {
            route: route('ai_probar'),
            status: false,
            text: 'Probar IA',
            icom: faFlask,
            permissions: 'ia_probar',
        },
    ],
};

export default menuAi;
