/**
 * Catálogo de ayuda de Aracodito para todo el sistema.
 *
 * Reúne tres fuentes:
 *  1. Guías generales del ERP (cómo navegar, guardar, buscar, exportar...).
 *  2. Las pantallas del menú que el usuario puede abrir, generadas desde
 *     MenuData y filtradas por sus permisos: así Aracodito le dice dónde está
 *     cada cosa y puede llevarlo con un clic.
 *  3. Las guías clínicas del módulo Salud, que ya existían.
 *
 * La búsqueda es por texto libre y sin acentos, para que la persona escriba
 * como habla ("como elimino un paciente", "donde esta caja").
 */

import MenuData from '@/Components/vristo/layout/MenuData.js';
import {
    AYUDA_GENERAL as AYUDA_SALUD_GENERAL,
    AYUDA_SALUD,
    normalizarTexto,
} from 'Modules/Health/Resources/assets/js/AracoditoAyuda';

/**
 * Guías que sirven en cualquier pantalla del sistema.
 */
export const AYUDA_SISTEMA = [
    {
        id: 'sistema-que-es',
        pregunta: '¿Qué es Aracodito y cómo me ayuda?',
        palabras: ['aracodito', 'ayuda', 'asistente', 'robot', 'como funciona', 'tutorial'],
        respuesta:
            'Soy el asistente del sistema. Este botón está en todas las pantallas: al abrirlo te digo para qué sirve la pantalla en la que estás y qué pasos seguir. También puedes escribir tu duda con tus palabras o el nombre de una opción, y te muestro dónde encontrarla y el botón para ir directo.',
    },
    {
        id: 'sistema-menu',
        pregunta: '¿Cómo encuentro una pantalla en el sistema?',
        palabras: ['menu', 'menú', 'pantalla', 'navegar', 'donde esta', 'dónde está', 'llegar', 'modulo', 'módulo'],
        respuesta:
            'Arriba a la izquierda está el menú de módulos. Elige el módulo (por ejemplo Salud, Tesorería o Ventas) y a la derecha se despliegan sus pantallas. Si no sabes en qué módulo está algo, escríbelo en el buscador de Aracodito: te digo el módulo, la opción y te llevo con un clic.',
    },
    {
        id: 'sistema-permisos',
        pregunta: 'No veo una opción que sí tiene mi compañero',
        palabras: ['no veo', 'no aparece', 'permiso', 'permisos', 'acceso', 'rol', 'no puedo entrar'],
        respuesta:
            'El menú solo muestra las pantallas para las que tu rol tiene permiso. Si te falta una opción, pide a un administrador que revise tus permisos en Seguridad → Roles y permisos. Escribir la dirección a mano tampoco sirve: las rutas vuelven a validar el permiso.',
    },
    {
        id: 'sistema-guardar',
        pregunta: '¿Cómo guardo un formulario?',
        palabras: ['guardar', 'grabar', 'registrar', 'formulario', 'no guarda', 'boton'],
        respuesta:
            'Completa los campos obligatorios (los que tienen asterisco) y pulsa el botón «Guardar» al final del formulario. Si falta algo, el sistema marca el campo en rojo con el motivo. No cierres la pestaña mientras guarda: el aviso de confirmación aparece cuando el servidor ya registró el dato.',
    },
    {
        id: 'sistema-buscar',
        pregunta: '¿Cómo encuentro un registro?',
        palabras: ['buscar', 'encontrar', 'filtrar', 'listado', 'no aparece', 'buscar por fecha'],
        respuesta:
            'En cada listado hay un buscador arriba: escribe el nombre, el documento o el número y la lista se filtra sola. Para acotar más, usa los filtros de fecha, estado o tipo, y pulsa «Limpiar» para volver a ver todo.',
    },
    {
        id: 'sistema-eliminar',
        pregunta: '¿Cómo elimino un registro?',
        palabras: ['eliminar', 'borrar', 'quitar', 'baja', 'anular'],
        respuesta:
            'Pulsa el botón rojo de la fila y confirma el aviso. En los módulos con historial (Salud, Tesorería) no se borra de verdad: el registro se desactiva o queda de baja y se conserva para auditoría, así que la acción sí queda registrada con tu usuario.',
    },
    {
        id: 'sistema-exportar',
        pregunta: '¿Cómo saco un reporte a Excel o PDF?',
        palabras: ['exportar', 'excel', 'csv', 'pdf', 'imprimir', 'reporte', 'informe', 'descargar'],
        respuesta:
            'En el listado o reporte busca los botones «Exportar», «CSV» o «PDF». Se descarga lo que tienes filtrado en pantalla, no todo: aplica primero el rango de fechas. En Salud cada impresión de historia, atención o receta queda auditada.',
    },
    {
        id: 'sistema-error',
        pregunta: 'Me salió un aviso de error, ¿qué hago?',
        palabras: ['error', 'fallo', 'no funciona', 'problema', 'no carga', 'se cayo', 'aviso rojo'],
        respuesta:
            'Lee el texto del aviso: casi siempre indica el campo o el dato que falta. Corrige y vuelve a intentar una vez. Si el error se repite, no lo intentes muchas veces seguidas: anota la hora, la pantalla y lo que estabas haciendo, y avisa al administrador. Los intentos quedan en la auditoría y ayudan a encontrar la causa.',
    },
    {
        id: 'sistema-cuenta',
        pregunta: '¿Cómo cambio mi contraseña, mi foto o mis datos?',
        palabras: ['contrasena', 'contraseña', 'clave', 'perfil', 'foto', 'usuario', 'datos personales'],
        respuesta:
            'Arriba a la derecha pulsa tu nombre o foto y entra a «Perfil». Ahí cambias contraseña, foto y datos de contacto. Si el administrador activó Aracodito en tu cuenta, también puedes ajustar su estilo y el audio desde «Configuración de Aracodito».',
    },
    {
        id: 'sistema-sesion',
        pregunta: '¿Cómo cierro sesión de forma segura?',
        palabras: ['cerrar sesion', 'cerrar sesión', 'salir', 'logout', 'computadora compartida'],
        respuesta:
            'Usa «Cerrar sesión» del menú de tu usuario, no cierres solo la pestaña. Al cerrar sesión se invalida el acceso en ese equipo, algo importante si la computadora se comparte en recepción o en caja. Todo lo que hiciste antes ya quedó auditado con tu usuario.',
    },
    {
        id: 'sistema-auditoria',
        pregunta: '¿El sistema guarda lo que hago?',
        palabras: ['auditoria', 'auditoría', 'registro', 'quien hizo', 'quién hizo', 'historial', 'seguridad'],
        respuesta:
            'Sí. Cada alta, cambio, baja, firma, cobro y consulta de historia clínica queda con el usuario, la fecha, la IP y el detalle del antes y el después. Ese registro es de solo lectura: nadie lo edita ni lo borra desde el sistema, y la auditoría clínica se conserva 5 años.',
    },
    {
        id: 'sistema-soporte',
        pregunta: '¿A quién aviso si algo no funciona?',
        palabras: ['soporte', 'avisar', 'administrador', 'ayuda tecnica', 'problema grave', 'reportar'],
        respuesta:
            'Avisa al administrador del sistema con tres datos: la pantalla donde estabas, la hora aproximada y qué intentabas hacer. Si había un mensaje de error, envíale el texto tal cual. No compartas tu contraseña ni el PIN de firma con nadie, tampoco por mensaje.',
    },
];

/**
 * Secciones del módulo Salud que corresponden a cada dirección.
 * El orden importa: la primera coincidencia gana.
 */
const SECCIONES_POR_RUTA = [
    { prueba: /^\/health\/patients\/(create|edit|update|store)/, secciones: ['pacienteForm', 'pacientes'] },
    { prueba: /^\/health\/patients/, secciones: ['pacientes'] },
    { prueba: /^\/health\/attentions/, secciones: ['atenciones'] },
    { prueba: /^\/health\/(agendas|avisos)/, secciones: ['agenda'] },
    { prueba: /^\/calendar/, secciones: ['agenda'] },
    { prueba: /^\/health\/clinical-records/, secciones: ['historia'] },
    { prueba: /^\/health\/admin\/activities/, secciones: ['actividades'] },
];

/**
 * Guías por módulo, con los pasos reales de cada pantalla importante.
 * Cada tema indica la pantalla (ruta del menú), los pasos y el aviso que
 * confirma que el cambio quedó guardado. Se muestran solo si la persona
 * está dentro de ese módulo (según la ruta actual).
 */
const GUIAS_POR_MODULO = [
    {
        // Gestión deportiva: torneos, equipos, jugadores y partidos.
        prueba: /^\/socialevents(\/|$)/,
        temas: [
            {
                id: 'torneo-editar-jugador',
                pregunta: '¿Cómo edito la información de un jugador?',
                palabras: ['editar', 'jugador', 'cambiar', 'datos', 'jugadora', 'modificar', 'corregir'],
                respuesta:
                    'Ve a Ediciones, abre la edición del torneo, entra a «Equipos» y pulsa el ícono de «Jugadores» del equipo. En la fila del jugador pulsa «Editar datos». En la ventana «Editar jugador» corrige los campos y pulsa «Guardar cambios». Luego confirma con «Sí, guardar». El cambio quedó guardado cuando aparece el aviso «Enhorabuena: Jugador actualizado correctamente», y la lista se recarga sola.',
                url: '/socialevents/editions',
            },
            {
                id: 'torneo-agregar-jugador',
                pregunta: '¿Cómo agrego un jugador a un equipo?',
                palabras: ['agregar', 'añadir', 'inscribir', 'jugador', 'nuevo jugador', 'buscar jugador'],
                respuesta:
                    'Ve a Ediciones, abre la edición del torneo, entra a «Equipos» y pulsa el ícono de «Jugadores» del equipo. En la lista de jugadores pulsa «Buscar». En la ventana de búsqueda escribe el número de documento o los nombres para encontrar a la persona. Si no existe, también puedes registrar un jugador nuevo ahí mismo: completa sus datos y pulsa «Guardar»; el aviso «Enhorabuena: Se registró correctamente» confirma que la persona quedó creada. Luego pulsa «Seleccionar» y después «Agregar al equipo».',
                url: '/socialevents/editions',
            },
            {
                id: 'torneo-fotochet',
                pregunta: '¿Cómo imprimo el fotochet de los jugadores?',
                palabras: ['fotochet', 'credencial', 'carnet', 'imprimir jugador', 'tarjeta', 'pdf'],
                respuesta:
                    'Ve a Ediciones, abre la edición del torneo, entra a «Equipos» y pulsa el ícono de «Jugadores» del equipo. En la fila del jugador pulsa «Imprimir fotochet». Para varios, marca sus casillas y pulsa «Imprimir carnets seleccionados» en la parte superior.',
                url: '/socialevents/editions',
            },
            {
                id: 'torneo-crear-equipo',
                pregunta: '¿Cómo creo un equipo?',
                palabras: ['crear equipo', 'nuevo equipo', 'registrar equipo', 'equipo'],
                respuesta:
                    'Ve a Eventos → Equipos y pulsa el botón para crear uno nuevo. Escribe el nombre del equipo (es obligatorio), sube el escudo si lo tienes o usa la generación con IA, y pulsa «Guardar». Verás el aviso de confirmación cuando el equipo quede creado.',
                url: '/socialevents/teams',
            },
            {
                id: 'torneo-agregar-equipo-torneo',
                pregunta: '¿Cómo agrego un equipo a un torneo?',
                palabras: ['equipo', 'torneo', 'inscribir equipo', 'agregar equipo', 'edicion'],
                respuesta:
                    'Ve a Ediciones, abre la edición del torneo y entra a «Equipos». Elige el equipo en el selector de la parte superior y pulsa «Agregar». El equipo aparece en la tabla de la edición.',
                url: '/socialevents/editions',
            },
            {
                id: 'torneo-puntos-bonus',
                pregunta: '¿Cómo doy puntos extra (bonus) a un equipo?',
                palabras: ['bonus', 'bonificacion', 'puntos extra', 'puntos', 'sancion', 'tabla'],
                respuesta:
                    'Ve a Ediciones, abre la edición del torneo, entra a «Equipos» y pulsa «Puntos extra». Elige los equipos, escribe los puntos y el motivo, y pulsa «Asignar puntos». El registro queda en el historial de puntos extra de la misma pantalla.',
                url: '/socialevents/editions',
            },
            {
                id: 'torneo-reporte-temporada',
                pregunta: '¿Dónde veo la tabla y el podio del torneo?',
                palabras: ['tabla', 'podio', 'posiciones', 'ranking', 'reporte', 'temporada', 'campeon'],
                respuesta:
                    'Entra a Eventos → «Reporte de temporada», elige el torneo y su edición. Ahí ves la tabla de posiciones, el podio, el historial y los rankings. Para imprimirlo pulsa «Exportar PDF» arriba, marca las secciones que quieres en la ventana «Secciones para imprimir» y pulsa «Descargar PDF».',
                url: '/socialevents/reports/season',
            },
            {
                id: 'torneo-crear-partido',
                pregunta: '¿Cómo registro un partido?',
                palabras: ['partido', 'match', 'fixture', 'programar', 'resultado', 'marcador'],
                respuesta:
                    'Ve a Ediciones, abre la edición del torneo y entra a «Partidos». Pulsa «Nuevo» para crear el partido. Para cargar el marcador, pulsa el botón de resultado del partido, completa los datos y pulsa «Guardar resultados»; el aviso «Enhorabuena» confirma que quedó registrado.',
                url: '/socialevents/editions',
            },
            {
                id: 'torneo-crear-evento',
                pregunta: '¿Cómo creo un torneo nuevo?',
                palabras: ['crear torneo', 'nuevo torneo', 'evento', 'torneo', 'nueva edicion', 'edicion'],
                respuesta:
                    'Ve a Eventos → Torneos (en el menú general esta sección se llama Eventos) y pulsa «Nuevo torneo». Completa nombre, categoría y fechas, y pulsa «Crear torneo».',
                url: '/socialevents/events',
            },
            {
                id: 'torneo-categorias',
                pregunta: '¿Dónde configuro las categorías?',
                palabras: ['categoria', 'categorías', 'categorias', 'disciplina', 'deporte'],
                respuesta:
                    'Entra a Eventos → Categorías (esta sección aparece en el menú del modo general; en modo deportivo está oculta). Ahí creas o editas las categorías que usarán tus torneos y guardas cada cambio con «Guardar».',
                url: '/socialevents/categories',
            },
            {
                id: 'torneo-locales',
                pregunta: '¿Dónde registro el complejo o las canchas?',
                palabras: ['local', 'complejo', 'cancha', 'sede', 'lugar'],
                respuesta:
                    'Entra a Eventos → Complejo deportivo (en el menú general se llama Locales). Completa los datos del complejo y pulsa «Guardar».',
                url: '/socialevents/locales',
            },
        ],
    },
];

/**
 * Guías del módulo en el que está la persona según la ruta actual.
 */
export function guiasDeModulo(url) {
    const ruta = rutaNormalizada(url);

    return GUIAS_POR_MODULO.filter((modulo) => modulo.prueba.test(ruta)).flatMap((modulo) =>
        modulo.temas.map((tema) => ({ ...tema, seccion: 'modulo' }))
    );
}

/**
 * Normaliza una dirección para compararla con el menú.
 */
function rutaNormalizada(url) {
    if (!url) {
        return '/';
    }

    try {
        const parsed = new URL(url, window.location.origin);
        const path = `${parsed.pathname}${parsed.search}`;
        return path.length > 1 && path.endsWith('/') ? path.slice(0, -1) : path;
    } catch {
        return String(url);
    }
}

/**
 * Secciones de ayuda del módulo Salud para la pantalla actual.
 */
export function seccionesDeRuta(url) {
    const ruta = rutaNormalizada(url);
    const encontrada = SECCIONES_POR_RUTA.find((item) => item.prueba.test(ruta));

    return encontrada ? encontrada.secciones : [];
}

/**
 * Guías clínicas de la pantalla actual (vacío si no es una pantalla de Salud).
 */
export function guiasDePantalla(url) {
    return seccionesDeRuta(url).flatMap((seccion) =>
        (AYUDA_SALUD[seccion] || []).map((tema) => ({ ...tema, seccion }))
    );
}

/**
 * Indica si el usuario tiene alguno de los permisos pedidos.
 */
function tienePermiso(permisos, disponibles) {
    if (!permisos) {
        return true;
    }

    const lista = Array.isArray(permisos) ? permisos : [permisos];

    return lista.some((permiso) => disponibles.includes(permiso));
}

/**
 * Aplana el menú del usuario en entradas consultables.
 */
function entradasDelMenu(permisos) {
    const entradas = [];

    for (const modulo of MenuData.value || []) {
        if (!tienePermiso(modulo.permissions, permisos)) {
            continue;
        }

        entradas.push({
            tipo: 'modulo',
            modulo: modulo.text,
            texto: modulo.text,
            url: modulo.route,
            fullPage: Boolean(modulo.fullPage),
        });

        const recorrer = (items, padres) => {
            for (const item of items || []) {
                if (!tienePermiso(item.permissions, permisos)) {
                    continue;
                }

                const camino = [...padres, item.text];

                entradas.push({
                    tipo: 'pantalla',
                    modulo: modulo.text,
                    texto: camino.join(' › '),
                    url: item.route,
                    fullPage: Boolean(item.fullPage),
                });

                recorrer(item.items, camino);
            }
        };

        recorrer(modulo.items, []);
    }

    return entradas;
}

/**
 * Convierte el menú del usuario en preguntas de ayuda.
 */
function temasDelMenu(permisos) {
    return entradasDelMenu(permisos).map((entrada, indice) => {
        if (entrada.tipo === 'modulo') {
            return {
                id: `menu-modulo-${indice}`,
                pregunta: `¿Qué puedo hacer en ${entrada.modulo}?`,
                palabras: [entrada.modulo, 'modulo', 'módulo', 'pantallas'],
                respuesta: `Abre el módulo ${entrada.modulo} en el menú y elige la pantalla que necesitas. Si quieres ir directo, pulsa «Abrir pantalla».`,
                url: entrada.url,
                fullPage: entrada.fullPage,
            };
        }

        return {
            id: `menu-pantalla-${indice}`,
            pregunta: `¿Dónde está ${entrada.texto}?`,
            palabras: [entrada.texto, entrada.modulo, 'pantalla', 'donde', 'dónde'],
            respuesta: `Está en el módulo ${entrada.modulo}, opción ${entrada.texto}. Pulsa «Abrir pantalla» y te llevo.`,
            url: entrada.url,
            fullPage: entrada.fullPage,
        };
    });
}

/**
 * Detecta en qué módulo y pantalla está la persona, según el menú.
 */
export function ubicacionActual(url, permisos = []) {
    const ruta = rutaNormalizada(url);
    let mejor = null;

    for (const entrada of entradasDelMenu(permisos)) {
        if (!entrada.url) {
            continue;
        }

        const destino = rutaNormalizada(entrada.url);

        if (destino === ruta || (destino !== '/' && ruta.startsWith(destino))) {
            if (!mejor || destino.length > mejor.destino.length) {
                mejor = { ...entrada, destino };
            }
        }
    }

    return mejor ? { modulo: mejor.modulo, pantalla: mejor.texto } : null;
}

/**
 * Busca temas en todo el catálogo.
 *
 * Sin consulta devuelve las guías de la pantalla actual más las generales.
 * Con consulta ordena por coincidencias: primero la pregunta, luego el resto.
 */
export function buscarTemas({ permisos = [], ruta = '/', consulta = '', limite = 12, conceptos = [] } = {}) {
    // conceptos viene de la guía del servidor (GuiaSistema): sirve para
    // responder dudas de proceso aunque la IA no esté disponible.
    const temasGuia = (conceptos || []).map((concepto) => ({
        id: `guia-${concepto.clave}`,
        pregunta: concepto.pregunta,
        palabras: concepto.palabras || [],
        respuesta: concepto.respuesta,
        url: concepto.url || null,
    }));

    const temas = [
        ...guiasDeModulo(ruta),
        ...AYUDA_SISTEMA,
        ...AYUDA_SALUD_GENERAL,
        ...guiasDePantalla(ruta),
        ...temasGuia,
        ...temasDelMenu(permisos),
    ];

    const texto = normalizarTexto(consulta);

    if (!texto) {
        return temas.slice(0, limite);
    }

    const palabras = texto.split(/\s+/).filter((palabra) => palabra.length > 2);

    return temas
        .map((tema) => {
            const pregunta = normalizarTexto(tema.pregunta);
            const contenido = normalizarTexto(
                [tema.pregunta, tema.respuesta, ...(tema.palabras || [])].join(' ')
            );

            const puntaje = palabras.reduce((total, palabra) => {
                if (pregunta.includes(palabra)) {
                    return total + 3;
                }

                return total + (contenido.includes(palabra) ? 1 : 0);
            }, 0);

            return { tema, puntaje };
        })
        .filter((item) => item.puntaje > 0)
        .sort((a, b) => b.puntaje - a.puntaje)
        .slice(0, limite)
        .map((item) => item.tema);
}
