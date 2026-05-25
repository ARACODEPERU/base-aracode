import { 
    faWheelchair, 
    faKitMedical,
    faUserDoctor,
    faTooth,
    faNotesMedical,
    faCashRegister,
    faFileMedical,
    faCalendarDays
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
        menuDental
    ]
    
};
export default menuHealth;
