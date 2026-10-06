import { 
    faWheelchair, 
    faKitMedical,
    faUserDoctor,
    faTooth,
    faNotesMedical,
    faCashRegister,
    faFileMedical,
    faCalendarDays,
    faClipboardList,
    faGear,
    faBell,
    faCalendarCheck
} from "@fortawesome/free-solid-svg-icons";
import menuDental from 'Modules/Dental/Resources/assets/js/Menu.js';

const menuHealth = {
    status:false,
    text: 'Salud',
    icom: faKitMedical,
    route: 'module',
    permissions: 'heal_dashboard',
    items: [
        {
            route: route('heal_doctors_list'),
            status: false,
            text: 'Doctores',
            icom: faUserDoctor,
            permissions: 'heal_doctores_listado',
        },
        {
            route: route('heal_patients_list'),
            status: false,
            text: 'Pacientes',
            icom: faWheelchair,
            permissions: 'heal_pacientes_listado',
        },
        {
            route: route('heal_attentions_list'),
            status: false,
            text: 'Atenciones',
            icom: faNotesMedical,
            permissions: 'heal_atenciones_listado',
        },
        {
            route: route('heal_agendas_list'),
            status: false,
            text: 'Agendas',
            icom: faCalendarDays,
            permissions: 'heal_citas_listado',
        },
        {
            route: route('heal_appointment_notices'),
            status: false,
            text: 'Avisos',
            icom: faBell,
            permissions: 'heal_avisos',
            info: {
                title: 'Notificaciones a pacientes',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Recordatorios automáticos de citas por SMS.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>📲 <span>Aviso antes de la cita: 30 minutos, 1 hora o el tiempo que definas.</span></li>
                        <li>📅 <span>Aviso un día antes, a la hora que elijas.</span></li>
                        <li>✍️ <span>Mensajes editables con variables como {paciente}, {hora_cita} y {nombre_dr}.</span></li>
                    </ul>
                `,
                placement: 'right'
            }
        },
        {
            route: route('heal_google_calendar'),
            status: false,
            text: 'Google Calendar',
            icom: faCalendarCheck,
            permissions: 'heal_google_calendar',
            info: {
                title: 'Sincronización con Google Calendar',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Las citas de la Agenda también se ven en el calendario del consultorio.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>📅 <span>Crear, mover o cancelar una cita actualiza el evento en Google.</span></li>
                        <li>🔄 <span>Los eventos creados o editados en Google vuelven a la Agenda.</span></li>
                        <li>📥 <span>Los eventos que no se pueden resolver quedan en la bandeja "Por revisar".</span></li>
                    </ul>
                `,
                placement: 'right'
            }
        },
        {
            route: route('heal_clinical_records_list'),
            status: false,
            text: 'Historias Clínicas',
            icom: faFileMedical,
            permissions: 'heal_pacientes_listado',
        },
        {
            route: route('heal_procedure_charges_list'),
            status: false,
            text: 'Procedimientos/cobros',
            icom: faCashRegister,
            permissions: 'heal_atenciones_listado',
        },
        menuDental,
        {
            route: route('heal_activities_list'),
            status: false,
            text: 'Registro de Actividades',
            icom: faClipboardList,
            permissions: 'heal_actividades_listado',
        },
        {
            route: route('heal_settings'),
            status: false,
            text: 'Configuración',
            icom: faGear,
            permissions: 'heal_configuracion',
        },
    ]
    
};
export default menuHealth;
