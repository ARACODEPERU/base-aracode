import {
    faBook,
    faUserGraduate,
    faLandmarkFlag,
    faUserTie,
    faBookOpen,
    faRocket,
    faCertificate,
    faPlay,
    faUserGroup
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
                    route: route("aca_tutorials_playlist"),
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
        }
    ],
};

// 🔥 Función para cargar los docentes y agregarlos al menú
async function loadTeachers() {
    try {
        const response = await fetch(route('crm_contacts_docents_chat'), {
            method: 'POST', // Cambiamos a POST
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') // Si usas Laravel con Blade
            },
            body: JSON.stringify({}) // Puedes enviar datos adicionales si es necesario
        });

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const data = await response.json();

        // Mapear los docentes a objetos de menú
        const docentesMenu = data.docents.map(docente => ({
            route: route("crm_application_ai_prompt", { cont: docente.person.id }),
            status: false,
            text: docente.person.full_name,
            permissions: "crm_clientes_preguntas_ia",
            img: docente.person.image
        }));
        //console.log(docentesMenu)
        const chatDodent = {
            status: false,
            text: "Chat",
            icom: faUserGroup,
            route: null,
            permissions: "crm_clientes_preguntas_ia",
            items: docentesMenu,
            avatar: true
        }
        // // Agregar los docentes al menú
        menuAcademic.items.push(chatDodent);
    } catch (error) {
        console.error("Error cargando docentes:", error);
    }
}

// Llamamos la función para cargar los docentes al menú
loadTeachers();
export default menuAcademic;
