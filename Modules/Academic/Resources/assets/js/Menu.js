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
    faGraduationCap,
    faClock,
    faGavel,
    faHand,
    faTags,
    faSchool,
    faCalendarDays,
    faCalendarCheck,
    faSitemap,
    faFileSignature,
    faChalkboardUser,
    faLayerGroup,
    faBell,
    faQrcode,
    faMoneyBillWave

} from "@fortawesome/free-solid-svg-icons";

/**
 * Tipo de negocio (parametro P000009 "Tipo de negocio o empresa, para ventas
 * en linea"). Se expone desde app.blade.php como window.businessType:
 *   5 = Colegio                  -> menu escolar puro
 *   6 = Colegio y capacitaciones -> menu escolar + capacitaciones/talleres
 * Cualquier otro valor (1,2,3,4,99...) conserva el menu de capacitaciones de
 * siempre, sin el grupo Colegio. window es el mismo mecanismo que ya usa
 * window.assetUrl en app.blade.php.
 */
const BUSINESS_COLEGIO = '5';
const BUSINESS_COLEGIO_CAPACITACIONES = '6';
const businessType = String(typeof window !== 'undefined' && window.businessType !== undefined ? window.businessType : '');
const isColegio = businessType === BUSINESS_COLEGIO || businessType === BUSINESS_COLEGIO_CAPACITACIONES;
const isColegioConCapacitaciones = businessType === BUSINESS_COLEGIO_CAPACITACIONES;

/* ------------------------------------------------------------------
 * Grupo Capacitación: el negocio actual (cursos de pago, certificados,
 * suscripciones). Visible cuando el colegio tambien vende capacitaciones.
 * ------------------------------------------------------------------ */
const capacitacionGroup = {
    route: null,
    status: false,
    text: "Capacitación",
    icom: faLayerGroup,
    // Permiso de entrada del grupo (el header evalua el string completo):
    // los items internos filtran con su propio permiso.
    permissions: "aca_cursos_listado",
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
            route: route("aca_course_options"),
            status: false,
            text: "Categorías/Tipo/Sector",
            icom: faTags,
            permissions: "aca_category_sector_type_modality",
        },
        {
            route: route("aca_notifications"),
            status: false,
            text: "Notificaciones",
            icom: faBell,
            permissions: "aca_send_notifications",
        },
        {
            route: route("aca_certificate_list"),
            status: false,
            text: "Certificados",
            icom: faCertificate,
            permissions: "aca_certificados_listado",
        },
    ],
};

/* ------------------------------------------------------------------
 * Grupo Aula y Seguimiento: herramientas de uso diario de Docente y
 * Alumno (examenes, asistencia, participaciones, calificaciones).
 * ------------------------------------------------------------------ */
const aulaGroup = {
    route: null,
    status: false,
    text: "Aula y Seguimiento",
    icom: faChalkboardUser,
    // Permiso de entrada del grupo; los items internos filtran con el suyo.
    permissions: "aca_miscursos",
    items: [
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
            route: route("aca_attendance_administration"),
            status: false,
            text: "Revisar Asistencia de alumnos",
            icom: faClock,
            permissions: "aca_asistencia_administrador",
            id: 'btnAsistencia'
        },
        {
            route: route("aca_students_course_participations"),
            status: false,
            text: "Calificación de participaciones",
            icom: faHand,
            permissions: "aca_gestion_de_participaciones",
            id: 'btnParticipaciones'
        },
        {
            route: route("aca_grade_management_panel"),
            status: false,
            text: "Gestión de Calificaciones",
            icom: faGavel,
            permissions: "aca_gestion_de_calificaciones",
            id: 'btnCalificaciones'
        },
    ],
};

/* ------------------------------------------------------------------
 * Grupo Colegio: administracion escolar (matriculas por nivel/grado/
 * seccion). Se muestra solo con el tipo de negocio 5 o 6 (P000009).
 * ------------------------------------------------------------------ */
const colegioGroup = {
    route: null,
    status: false,
    text: 'Colegio',
    icom: faSchool,
    // Permiso de entrada del grupo (lista con semantica OR): se muestra si el
    // usuario tiene cualquiera de los permisos de sus items; los items internos
    // se filtran con el suyo. Sin el OR, el Docente (solo aca_school_docente_notas)
    // no veia el grupo y con ello tampoco "Registro de Notas".
    permissions: [
        'aca_school_year_listado',
        'aca_school_estructura',
        'aca_school_alumno_listado',
        'aca_school_matricula_listado',
        'aca_school_tarifa_listado',
        'aca_school_cobro_listado',
        'aca_school_docente_listado',
        'aca_school_docente_notas',
        'aca_school_area_listado',
        'aca_school_listado',
        'aca_school_horario_listado',
        'aca_school_horario_docente',
    ],
    items: [
        {
            route: route('aca_schools_list'),
            status: false,
            text: 'Colegios',
            icom: faLandmarkFlag,
            permissions: 'aca_school_listado',
            info: {
                title: 'Colegios',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Registro de los colegios que administra la plataforma:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>🏫 <span>Crear y editar colegios (nombre, código modular, dirección y contacto).</span></li>
                        <li>🛡️ <span>Cargar el logo o escudo que se usa en carnés y documentos.</span></li>
                        <li>🏷️ <span>Definir el tipo: privado o nacional.</span></li>
                        <li>⭐ <span>Marcar el colegio por defecto del sistema.</span></li>
                        <li>🔄 <span>Activar o desactivar un colegio.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_years_list'),
            status: false,
            text: 'Años Escolares',
            icom: faCalendarDays,
            permissions: 'aca_school_year_listado',
            info: {
                title: 'Años Escolares',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Administración de los años lectivos del colegio:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>📅 <span>Crear el año escolar (ej. 2027) para poder matricular.</span></li>
                        <li>✅ <span>Activar el año en curso: matrículas, notas y carnés se registran en el año activo.</span></li>
                        <li>📝 <span>Agregar observaciones del año lectivo.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_fees_list'),
            status: false,
            text: 'Tarifas',
            icom: faTags,
            permissions: 'aca_school_tarifa_listado',
            info: {
                title: 'Tarifas',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Conceptos y montos de pago del año escolar:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>💰 <span>Definir matrícula y mensualidades por nivel y grado.</span></li>
                        <li>🗓️ <span>Configurar el cronograma de vencimientos de cada cuota.</span></li>
                        <li>✏️ <span>Editar o desactivar tarifas cuando cambian los montos.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_structure'),
            status: false,
            text: 'Estructura Académica',
            icom: faSitemap,
            permissions: 'aca_school_estructura',
            info: {
                title: 'Estructura Académica',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Organización del colegio en niveles, grados y secciones:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>🏗️ <span>Crear niveles (Inicial, Primaria, Secundaria) y sus grados.</span></li>
                        <li>👥 <span>Abrir secciones por grado con turno y capacidad.</span></li>
                        <li>👩‍🏫 <span>Asignar tutor y auxiliar de cada sección.</span></li>
                        <li>🪑 <span>Ver los asientos disponibles antes de matricular.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_areas_list'),
            status: false,
            text: 'Áreas Curriculares',
            icom: faLayerGroup,
            permissions: 'aca_school_area_listado',
            info: {
                title: 'Áreas Curriculares',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Catálogo de cursos o áreas que se evalúan en las notas:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>📚 <span>Cargar las áreas oficiales del CNEB por nivel con un clic.</span></li>
                        <li>✏️ <span>Crear áreas propias del colegio y ordenarlas para el registro de notas.</span></li>
                        <li>🔄 <span>Activar o desactivar áreas que ya no se evalúan.</span></li>
                        <li>🔗 <span>Estas áreas aparecen automáticamente en el Registro de Notas.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_schedules_index'),
            status: false,
            text: 'Horarios',
            icom: faClock,
            permissions: 'aca_school_horario_listado',
            info: {
                title: 'Horarios',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Jornada del colegio y horario de clases por sección:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>🕒 <span>Definir la hora de entrada y salida por nivel y turno.</span></li>
                        <li>📅 <span>Cargar los bloques de cada sección: área, día y hora.</span></li>
                        <li>👩‍🏫 <span>Asignar el docente que dicta cada curso.</span></li>
                        <li>📋 <span>Copiar el horario de un día a otros o de otra sección del mismo grado.</span></li>
                        <li>⏰ <span>Es la base del control de asistencia por hora del docente.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_schedules_my'),
            status: false,
            text: 'Mi Horario',
            icom: faClock,
            permissions: 'aca_school_horario_docente',
            info: {
                title: 'Mi Horario',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Su horario de clases como docente:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>📅 <span>Consultar sus bloques día por día.</span></li>
                        <li>🏫 <span>Ver el nivel, la sección y el aula de cada clase.</span></li>
                        <li>📚 <span>Conocer el área curricular que le toca dictar.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_teachers_list'),
            status: false,
            text: 'Docentes',
            icom: faUserTie,
            permissions: 'aca_school_docente_listado',
            info: {
                title: 'Docentes',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Registro del personal docente del colegio:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>👩‍🏫 <span>Registrar docentes con sus datos y documento.</span></li>
                        <li>✏️ <span>Editar o desactivar docentes.</span></li>
                        <li>🔎 <span>Buscar docentes por nombre o documento.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_students_list'),
            status: false,
            text: 'Estudiantes',
            icom: faUserGraduate,
            permissions: 'aca_school_alumno_listado',
            info: {
                title: 'Estudiantes',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Ficha completa de cada alumno del colegio:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>✏️ <span>Registrar y editar datos del alumno y su documento.</span></li>
                        <li>👨‍👩‍👧 <span>Gestionar apoderados y su relación con el alumno.</span></li>
                        <li>🪪 <span>Imprimir el carné del alumno con QR para asistencia.</span></li>
                        <li>💰 <span>Cobrar matrícula o mensualidad.</span></li>
                        <li>🗑️ <span>Eliminar alumnos sin matrículas activas.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_enrollments_list'),
            status: false,
            text: 'Matrículas',
            icom: faFileSignature,
            permissions: 'aca_school_matricula_listado',
            info: {
                title: 'Matrículas',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Inscripción de alumnos en el año escolar activo:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>📝 <span>Matricular alumnos en una sección del año activo.</span></li>
                        <li>🏷️ <span>Registrar el tipo: nueva, promovida, repitente, traslado o reingreso.</span></li>
                        <li>👨‍👩‍👧 <span>Asignar apoderado y teléfono de contacto.</span></li>
                        <li>🪑 <span>Ver asientos disponibles por sección.</span></li>
                        <li>🔄 <span>Retirar, anular o dar traslado de salida.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_teacher_grades'),
            status: false,
            text: 'Registro de Notas',
            icom: faChalkboardUser,
            permissions: 'aca_school_docente_notas',
            info: {
                title: 'Registro de Notas',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Evaluación del docente en sus secciones (tutor o auxiliar):
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>📚 <span>Registrar notas por área curricular y bimestre.</span></li>
                        <li>🔤 <span>Escala AD/A/B/C o literal según el nivel de la sección.</span></li>
                        <li>👥 <span>Solo ve las secciones donde es tutor o auxiliar.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_attendance_index'),
            status: false,
            text: 'Registro de Asistencias',
            icom: faCalendarCheck,
            permissions: 'aca_school_docente_notas',
            info: {
                title: 'Registro de Asistencias',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Asistencia mensual de la I.E., con la dinamica de SIAGIE (MINEDU):
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>🗓️ <span>Marcar cada alumno por día: A, T, J o F.</span></li>
                        <li>⚡ <span>Completar asistencias hasta un día dado con un clic.</span></li>
                        <li>📄 <span>Exportar la hoja mensual a PDF.</span></li>
                        <li>👥 <span>Solo ve las secciones donde es tutor o auxiliar.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_gate_scanner'),
            status: false,
            text: 'Portería',
            icom: faQrcode,
            permissions: 'aca_school_porteria_escaner',
            info: {
                title: 'Portería',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Asistencia de la institución: el paso de los alumnos por la puerta:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>📷 <span>Escanear el QR del carné con la cámara del equipo.</span></li>
                        <li>🔫 <span>O con la pistola lectora, sin hacer clic en ningún campo.</span></li>
                        <li>⏱️ <span>La entrada se marca como asistencia o tardanza según la jornada.</span></li>
                        <li>🚪 <span>La salida se registra aparte y es opcional.</span></li>
                        <li>📄 <span>No confundir con la asistencia de aula que registra el docente.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
        {
            route: route('aca_school_enrollments_list'),
            status: false,
            text: 'Cobros',
            icom: faMoneyBillWave,
            permissions: 'aca_school_cobro_listado',
            info: {
                title: 'Cobros',
                content: `
                    <p class="text-sm text-gray-500 mb-3">
                        Cobranza de matrícula y mensualidades por alumno:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>💰 <span>Registrar cobros de matrícula y mensualidades.</span></li>
                        <li>🧾 <span>Emitir boleta o factura del pago.</span></li>
                        <li>🗓️ <span>Consultar el cronograma y los pagos pendientes.</span></li>
                    </ul>
                `,
                placement: 'right'
            },
        },
    ],
};

/* ------------------------------------------------------------------
 * Tutoriales y Reportes (comunes a todos los tipos de negocio).
 * ------------------------------------------------------------------ */
const tutorialesGroup = {
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
    ],
};

const reportesItem = {
    route: route('aca_reports_dashboard'),
    status: false,
    text: 'Reportes',
    permissions: 'aca_reportes',
    icom: faChartLine,
};

/* ------------------------------------------------------------------
 * Armado del menu segun el tipo de negocio:
 * - Colegio (5): solo administracion escolar + tutoriales + reportes.
 * - Colegio y capacitaciones (6): los 3 grupos.
 * - Resto (1,2,3,4,99): el menu de capacitaciones clasico, con el
 *   mismo orden de siempre; los grupos solo aplican al modo colegio.
 * ------------------------------------------------------------------ */
let academicItems;

if (isColegioConCapacitaciones) {
    academicItems = [
        capacitacionGroup,
        aulaGroup,
        colegioGroup,
        tutorialesGroup,
        reportesItem,
    ];
} else if (isColegio) {
    academicItems = [
        colegioGroup,
        tutorialesGroup,
        reportesItem,
    ];
} else {
    academicItems = [
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
            info: capacitacionGroup.items[3].info,
        },
        {
            route: route("aca_courses_list"),
            status: false,
            text: "Cursos",
            icom: faBook,
            permissions: "aca_cursos_listado",
        },
        {
            route: route("aca_course_options"),
            status: false,
            text: "Categorías/Tipo/Sector",
            icom: faTags,
            permissions: "aca_category_sector_type_modality",
        },
        {
            route: route("aca_certificate_list"),
            status: false,
            text: "Certificados",
            icom: faCertificate,
            permissions: "aca_certificados_listado",
        },
        aulaGroup,
        tutorialesGroup,
        reportesItem,
    ];
}

const menuAcademic = {
    status: false,
    text: "Académico",
    icom: faGraduationCap,
    route: 'module',
    permissions: "aca_dashboard",
    items: academicItems,
};

// Llamamos la función para cargar los docentes al menú
export default menuAcademic;
