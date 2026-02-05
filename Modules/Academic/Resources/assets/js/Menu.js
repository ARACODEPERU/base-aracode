import {
    faBook,
    faUserGraduate,
    faLandmarkFlag,
    faUserTie,
    faBookOpen,
    faRocket,
    faCertificate,
    faPlay,
    faMugHot,
    faChartLine,
    faGraduationCap
} from "@fortawesome/free-solid-svg-icons";

const menuAcademic = {
    status: false,
    text: "Académico",
    icom: faGraduationCap,
    route: 'module',
    permissions: "aca_dashboard",
    items: [
        {
            route: route("aca_subscriptions_list"),
            status: false,
            text: "Tipo de suscripcion",
            icom: faRocket,
            permissions: "aca_suscripciones",
        },
        {
            route: route("aca_institutions_list"),
            status: false,
            text: "Instituciones",
            icom: faLandmarkFlag,
            permissions: "aca_institucion_listado",
        },
        {
            route: route("aca_teachers_list"),
            status: false,
            text: "Docentes",
            icom: faUserTie,
            permissions: "aca_docente_listado",
        },
        {
            route: route("aca_students_list"),
            status: false,
            text: "Estudiantes",
            icom: faUserGraduate,
            permissions: "aca_estudiante_listado",
            info: {
                title: "Gestión de Estudiantes",
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Aquí puede administrar toda la información de los estudiantes:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>✏️ <span>Editar datos del estudiante.</span></li>
                        <li>📜 <span>Ver certificados — habilitar o gestionar certificados.</span></li>
                        <li>📘 <span>Ver matrículas — inscribir y revisar cursos activos.</span></li>
                        <li>🔄 <span>Suscripciones — consultar y registrar nuevas.</span></li>
                        <li>💰 <span>Cobrar — crear boleta o factura.</span></li>
                        <li>⭐ <span>Cuotas pendientes especiales.</span>
                            <div class="text-xs text-gray-500 pl-6 mt-1 flex items-center">
                                👉 Pagos especiales como cuotas, reprogramación, cancelaciones artesanales, etc...
                            </div>
                        </li>
                        <li>
                        📄 <span>Lista de comprobantes:</span>
                            <div class="text-xs text-gray-500 pl-6 mt-1 flex items-center">
                                ➡️ <span class="ml-1">ver detalles</span>
                            </div>
                            <div class="text-xs text-gray-500 pl-6 mt-1 flex items-center">
                                ➡️ <span class="ml-1">descargar pdf.</span>
                            </div>
                            <div class="text-xs text-gray-500 pl-6 mt-1 flex items-center">
                                ➡️ <span class="ml-1">descargar xml.</span>
                            </div>
                            <div class="text-xs text-gray-500 pl-6 mt-1 flex items-center">
                                ➡️ <span class="ml-1">Enviar por correo.</span>
                            </div>
                        </li>
                        <li>🗑️ <span>Eliminar estudiante si es necesario.</span></li>
                    </ul>
                `,
                placement: 'right'
            }
        },
        {
            route: route("aca_courses_list"),
            status: false,
            text: "Cursos",
            icom: faBook,
            permissions: "aca_cursos_listado",
        },
        {
            route: route("aca_certificate_list"),
            status: false,
            text: "Certificados",
            icom: faCertificate,
            permissions: "aca_certificados_listado",
        },
        {
            route: null,
            status: false,
            text: "Tutoriales Cortos",
            icom: faPlay,
            permissions: "aca_tutoriales_cortos",
            items: [
                {
                    route: route("aca_tutorials_playlist"),
                    status: false,
                    text: "Lista de reproduccion",
                    icom: faCertificate,
                    permissions: "aca_tutoriales_lista",
                },
                {
                    route: route("aca_tutorials_videos_list"),
                    status: false,
                    text: "Videos",
                    icom: faCertificate,
                    permissions: "aca_tutoriales_videos",
                },
            ]
        },
        {
            route: route("aca_mycourses"),
            status: false,
            text: "Mis Cursos",
            icom: faBookOpen,
            permissions: "aca_miscursos",
            id: 'btnMenuMycourses'
        },
        {
            route: route("aca_student_exam_review_exams"),
            status: false,
            text: "Revisar examenes",
            icom: faMugHot,
            permissions: "aca_cursos_revisar_examenes",
            id: 'btnReviewExams'
        },
        {
            route: route('aca_reports_dashboard'),
            status: false,
            text: 'Reportes',
            permissions: 'aca_reportes',
            icom: faChartLine,
        }
    ],
};



// Llamamos la función para cargar los docentes al menú
export default menuAcademic;
