/**
 * Catálogo de ayuda de Aracodito para el módulo Salud.
 *
 * Cada sección corresponde a una pantalla. Las preguntas se buscan por texto
 * libre: se comparan contra la pregunta y las palabras clave, sin acentos y
 * en minúsculas, para que la persona escriba como habla.
 */

export const AYUDA_GENERAL = [
    {
        id: 'general-auditoria',
        pregunta: '¿Qué queda registrado en el historial de actividades?',
        palabras: ['auditoria', 'auditoría', 'registro', 'actividades', 'historial', 'quien', 'quién'],
        respuesta:
            'Toda alta, modificación, baja, firma y consulta de historia clínica queda registrada con el usuario, la fecha, la IP y el detalle del cambio. Ese registro es de solo lectura: no se puede editar ni borrar desde el sistema. Solo un administrador puede purgar accesos antiguos (más de 5 años) con el comando de retención.',
    },
    {
        id: 'general-permisos',
        pregunta: 'No veo una opción del menú de Salud',
        palabras: ['menu', 'menú', 'no veo', 'permiso', 'permisos', 'opcion', 'opción', 'acceso'],
        respuesta:
            'El menú solo muestra las pantallas para las que tu rol tiene permiso. Si te falta una opción, pide a un administrador que revise tus permisos en Seguridad → Roles y permisos. Entrar por URL directa tampoco funciona: las rutas del módulo validan el mismo permiso.',
    },
    {
        id: 'general-eliminar',
        pregunta: '¿Eliminar un paciente borra su historia clínica?',
        palabras: ['eliminar', 'borrar', 'baja', 'anonimizar', 'historia', 'paciente'],
        respuesta:
            'No. Al eliminar un paciente se hace una baja lógica: la ficha y la historia clínica se conservan para auditoría, pero sus datos personales (nombre, documento, teléfono, correo, dirección e imagen) se anonimizan. La acción queda registrada con quién la hizo y la fecha.',
    },
];

export const AYUDA_SALUD = {
    pacientes: [
        {
            id: 'pacientes-nuevo',
            pregunta: '¿Cómo registro un paciente nuevo?',
            palabras: ['registrar', 'nuevo', 'crear', 'alta', 'paciente'],
            respuesta:
                'Pulsa «Nuevo» arriba a la derecha. Completa tipo y número de documento, nombres y apellidos, fecha de nacimiento, teléfono (9 dígitos que empiezan con 9, sin +51), correo, dirección y ciudad. El documento no puede repetirse para el mismo tipo y el correo debe ser único. Al guardar, el paciente aparece en el listado.',
        },
        {
            id: 'pacientes-editar',
            pregunta: '¿Cómo corrijo los datos de un paciente?',
            palabras: ['editar', 'corregir', 'cambiar', 'datos', 'actualizar'],
            respuesta:
                'En la fila del paciente pulsa el botón del lápiz (Editar). Cambia lo necesario y guarda. Cada campo modificado queda en el registro de actividades con el valor anterior y el nuevo, así que conviene revisar antes de guardar.',
        },
        {
            id: 'pacientes-eliminar',
            pregunta: '¿Cómo doy de baja a un paciente?',
            palabras: ['eliminar', 'borrar', 'baja', 'quitar'],
            respuesta:
                'Pulsa el botón rojo (Eliminar) de la fila. Se pedirá confirmación: la baja conserva la historia clínica y anonimiza los datos personales. No se puede deshacer desde la pantalla; si fue un error, un administrador debe restaurar el registro desde la base de datos.',
        },
        {
            id: 'pacientes-historia',
            pregunta: '¿Dónde veo la historia clínica del paciente?',
            palabras: ['historia', 'clinica', 'clínica', 'atenciones', 'ver'],
            respuesta:
                'Pulsa el botón del documento (Historia) en la fila del paciente. Verás sus atenciones por fecha y podrás abrir el detalle. Cada apertura de historia queda auditada con tu usuario, la fecha y la IP.',
        },
        {
            id: 'pacientes-citas',
            pregunta: '¿Cómo agendo una cita desde la ficha del paciente?',
            palabras: ['cita', 'agendar', 'agenda', 'calendario'],
            respuesta:
                'Pulsa el botón del reloj (Citas) y luego «Nueva cita». Se abre la agenda con el paciente ya seleccionado: elige doctor, fecha, hora y duración. El sistema solo acepta horarios libres dentro del horario de atención.',
        },
        {
            id: 'pacientes-buscar',
            pregunta: 'No encuentro a un paciente en el listado',
            palabras: ['buscar', 'encontrar', 'no aparece', 'filtrar'],
            respuesta:
                'Escribe en el buscador de la parte superior: filtra por nombre completo. Si el paciente fue dado de baja, ya no aparece en el listado; su historia sigue disponible para auditoría. Si tampoco aparece escribiendo el documento completo, revisa que no esté registrado con otro tipo de documento.',
        },
    ],
    pacienteForm: [
        {
            id: 'form-telefono',
            pregunta: 'El teléfono no me deja guardar',
            palabras: ['telefono', 'teléfono', 'celular', 'numero', 'número', 'error'],
            respuesta:
                'Salud acepta celulares peruanos de 9 dígitos que empiezan con 9. Puedes escribirlo con +51 o con espacios, pero no se aceptan fijos ni números de 8 dígitos.',
        },
        {
            id: 'form-documento',
            pregunta: 'Me dice que el documento ya está registrado',
            palabras: ['documento', 'dni', 'ruc', 'duplicado', 'repetido', 'unique'],
            respuesta:
                'El número de documento es único por tipo de documento. Busca al paciente por ese número en el listado: probablemente ya existe. Si pertenece a otra persona, revisa el tipo de documento seleccionado.',
        },
        {
            id: 'form-foto',
            pregunta: '¿Cómo cambio la foto del paciente?',
            palabras: ['foto', 'imagen', 'avatar', 'perfil'],
            respuesta:
                'En el formulario de edición usa el recortador de imagen: elige la foto, ajusta el encuadre y guarda. Se aceptan JPG, PNG, GIF y WEBP.',
        },
    ],
    atenciones: [
        {
            id: 'atenciones-crear',
            pregunta: '¿Cómo registro una atención?',
            palabras: ['atencion', 'atención', 'registrar', 'nueva', 'consulta'],
            respuesta:
                'Pulsa «Nueva atención». Busca al paciente, elige el doctor (los administradores pueden elegir; cada doctor queda fijado al suyo), completa el motivo, signos vitales, diagnóstico CIE-10 y tratamiento, y guarda. Después podrás firmarla.',
        },
        {
            id: 'atenciones-firmar',
            pregunta: '¿Cómo firmo una atención?',
            palabras: ['firmar', 'firma', 'pin', 'cerrar'],
            respuesta:
                'Abre la atención pendiente y pulsa Firmar. Necesitas el PIN de firma del doctor: 4 dígitos que se configuran en Parámetros → PIN de firma. Si el doctor no tiene PIN configurado, el sistema no permite firmar; ya no existe el 1234 por defecto.',
        },
        {
            id: 'atenciones-pin',
            pregunta: 'Olvidé el PIN de firma',
            palabras: ['olvide', 'olvidé', 'pin', 'perdi', 'perdí', 'reset'],
            respuesta:
                'Un administrador puede enviar el restablecimiento desde la lista de atenciones (acción de acceso del doctor). El PIN anterior se borra y el doctor debe configurar uno nuevo con su correo y contraseña antes de volver a firmar.',
        },
        {
            id: 'atenciones-pdf',
            pregunta: '¿Cómo imprimo la atención o la receta?',
            palabras: ['pdf', 'imprimir', 'receta', 'descargar', 'documento'],
            respuesta:
                'En la fila de la atención pulsa el botón de PDF para la atención completa, o el de receta para la prescripción. La descarga queda registrada en la auditoría como consulta de documento clínico.',
        },
        {
            id: 'atenciones-firmada',
            pregunta: 'No puedo editar una atención firmada',
            palabras: ['editar', 'firmada', 'bloqueada', 'no puedo', 'modificar'],
            respuesta:
                'Una atención firmada es un documento cerrado: no se edita para no romper la trazabilidad. Si hubo un error, registra una atención nueva o una nota de corrección; la firma anterior se conserva.',
        },
    ],
    agenda: [
        {
            id: 'agenda-cita',
            pregunta: '¿Cómo creo una cita?',
            palabras: ['crear', 'cita', 'agendar', 'nueva', 'reservar'],
            respuesta:
                'Pulsa «Nueva cita», busca el paciente, elige doctor, fecha, hora y duración. Solo se muestran horarios libres dentro del horario de atención. Si el horario está ocupado, el sistema te sugiere los espacios disponibles.',
        },
        {
            id: 'agenda-mover',
            pregunta: '¿Cómo reprogramo una cita?',
            palabras: ['mover', 'reprogramar', 'cambiar', 'hora', 'reagendar'],
            respuesta:
                'Arrastra la cita al nuevo horario en la agenda, o ábrela y elige «Reprogramar». Solo se mueven citas pendientes. Al moverla se borran los avisos ya programados para que se generen con el nuevo horario.',
        },
        {
            id: 'agenda-doctor',
            pregunta: 'No veo la agenda de otro doctor',
            palabras: ['otro', 'doctor', 'agenda', 'no veo', 'cambiar'],
            respuesta:
                'Cada doctor ve únicamente la agenda propia; solo los administradores pueden cambiar de doctor y consultar todas. Si necesitas revisar la agenda de un colega, pide a un administrador.',
        },
        {
            id: 'agenda-no-show',
            pregunta: '¿Qué significa «no concretada»?',
            palabras: ['no concretada', 'no show', 'ausente', 'vencida', 'estado'],
            respuesta:
                'El sistema marca automáticamente como no concretada una cita pendiente cuyo horario ya pasó y que no tiene atención registrada. Esas citas dejan de contar como ocupadas para nuevos horarios.',
        },
    ],
    historia: [
        {
            id: 'historia-listado',
            pregunta: '¿Qué muestra el listado de historias clínicas?',
            palabras: ['listado', 'historia', 'pacientes', 'ultima', 'atención'],
            respuesta:
                'Muestra cada paciente con su última atención y el número de atenciones registradas. Usa el buscador por nombre o documento y pulsa «Ver historia» para abrir el detalle por fechas.',
        },
        {
            id: 'historia-acceso',
            pregunta: '¿Quién puede ver la historia clínica?',
            palabras: ['quien', 'quién', 'permiso', 'ver', 'acceso', 'privacidad'],
            respuesta:
                'Cualquier doctor con permiso de pacientes puede consultar la historia de cualquier paciente, tal como definió el consultorio. Cada consulta queda registrada con usuario, fecha e IP para poder auditar quién vio qué.',
        },
        {
            id: 'historia-signos',
            pregunta: 'No aparecen los últimos signos vitales',
            palabras: ['signos', 'vitales', 'presion', 'presión', 'peso', 'talla'],
            respuesta:
                'El panel toma los signos de la última atención que tenga al menos un dato registrado (presión, frecuencia, peso, talla o IMC). Si la atención no los tiene, completa esos campos al registrar la consulta.',
        },
    ],
    actividades: [
        {
            id: 'actividades-filtrar',
            pregunta: '¿Cómo busco un cambio concreto en la auditoría?',
            palabras: ['buscar', 'filtrar', 'fecha', 'tipo', 'cambio', 'auditoria', 'auditoría'],
            respuesta:
                'Combina el buscador (usuario, paciente o tipo) con el tipo de actividad y el rango Desde/Hasta. Pulsa el ojo de una fila para ver el detalle del antes y el después del cambio.',
        },
        {
            id: 'actividades-exportar',
            pregunta: '¿Puedo exportar el registro de actividades?',
            palabras: ['exportar', 'csv', 'excel', 'descargar', 'informe'],
            respuesta:
                'Sí: pulsa «Exportar CSV» para descargar hasta 5000 entradas con los filtros aplicados. Cada exportación queda auditada con el usuario y los filtros usados.',
        },
        {
            id: 'actividades-integridad',
            pregunta: '¿Cómo sé que la auditoría no fue alterada?',
            palabras: ['integridad', 'alterada', 'hash', 'verificar', 'cadena'],
            respuesta:
                'Cada entrada guarda una huella y el enlace con la entrada anterior. El comando «php artisan health:audit:verify» recalcula la cadena y señala cualquier fila alterada. Las filas anteriores a esta mejora se informan aparte como previas a la cadena.',
        },
    ],
};

/**
 * Devuelve todos los temas de una sección más los generales.
 */
export function temasDeSeccion(seccion) {
    return [...(AYUDA_SALUD[seccion] || []), ...AYUDA_GENERAL];
}

/**
 * Normaliza para comparar sin acentos ni mayúsculas.
 */
export function normalizarTexto(texto) {
    return String(texto || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

/**
 * Busca los temas que coinciden con la consulta libre de la persona.
 * Sin consulta devuelve todos; con consulta ordena por coincidencias.
 */
export function buscarTemas(seccion, consulta) {
    const temas = temasDeSeccion(seccion);
    const texto = normalizarTexto(consulta);

    if (!texto) {
        return temas;
    }

    const palabras = texto.split(/\s+/).filter((palabra) => palabra.length > 2);

    return temas
        .map((tema) => {
            const contenido = normalizarTexto(
                [tema.pregunta, tema.respuesta, ...(tema.palabras || [])].join(' ')
            );
            const puntaje = palabras.reduce(
                (total, palabra) => total + (contenido.includes(palabra) ? 1 : 0),
                0
            );

            return { tema, puntaje };
        })
        .filter((item) => item.puntaje > 0)
        .sort((a, b) => b.puntaje - a.puntaje)
        .map((item) => item.tema);
}
