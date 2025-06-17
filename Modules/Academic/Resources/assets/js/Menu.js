import {
    faBook,
    faUserGraduate,
    faLandmarkFlag,
    faUserTie,
    faBookOpen,
    faRocket,
    faCertificate,
    faPlay,
    faMugHot
} from "@fortawesome/free-solid-svg-icons";

const menuAcademic = {
    status: false,
    text: "Académico",
    icom: faBook,
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
        }
    ],
};



// Llamamos la función para cargar los docentes al menú
export default menuAcademic;
