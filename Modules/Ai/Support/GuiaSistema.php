<?php

namespace Modules\Ai\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Guía funcional breve de Aracode.
 *
 * Es la fuente única de dos consumidores:
 *  - la IA, que recibe el texto resumido para explicar procesos y relacionar
 *    conceptos con pantallas;
 *  - el catálogo estático del panel, que usa los conceptos para responder
 *    aunque la IA no esté disponible o falle.
 *
 * Los conceptos solo llegan al usuario si la pantalla de destino está entre
 * las que su rol puede abrir (MapaSistema). Así la guía no revela pantallas
 * sin permiso.
 */
class GuiaSistema
{
    /**
     * Resumen para el prompt de la IA. Debe mantenerse corto: la lista de
     * pantallas permitidas se agrega aparte.
     */
    public const TEXTO = <<<'TXT'
Guía rápida de Aracode (resumen funcional; úsala para explicar procesos y relacionar conceptos con pantallas):
- Salud: Pacientes, Atenciones (consulta clínica), Historia clínica, Agendas y citas, Procedimientos y cobros, Avisos. El odontograma NO es una pantalla del menú: se abre dentro de Salud › Atenciones, en la atención del paciente, y solo aparece cuando el doctor que atiende es odontólogo. Si el profesional no es odontólogo, la atención se registra como consulta médica normal.
- Tesorería: efectivo/caja, cobros, pagos, transferencias, conciliación, impuestos, documentos y reportes.
- Ventas: comprobantes, notas de crédito, cuentas por cobrar, productos y clientes.
- Compras: proveedores, órdenes y documentos de compra.
- Académico: estudiantes, docentes, cursos, matrículas, notas, asistencia y pensiones.
- CRM: contactos, embudo, chat interno y libro de reclamaciones.
- Seguridad: usuarios, roles y permisos, auditoría y configuración.
- IA: consumo, precios, configuración y pruebas del asistente.
Reglas generales:
- El menú solo muestra lo que el rol permite. Si una opción no aparece, se piden permisos en Seguridad → Roles y permisos; escribir la dirección a mano tampoco funciona.
- Los listados tienen buscador y filtros; exportar (CSV/PDF) descarga lo que está filtrado en pantalla.
- En módulos con historial (Salud, Tesorería) los registros no se borran: se desactivan y quedan auditados con usuario y fecha.
- Antes de consultar a la IA, el servidor enmascara los datos personales.
- Si algo no está en esta guía ni en la lista de pantallas, di que no está documentado y no lo inventes. Nunca afirmes que una pantalla o función no existe si aparece en la guía o en la lista.
TXT;

    /**
     * Conceptos consultables sin IA. `urls` se resuelve contra el mapa de
     * pantallas permitidas; se usa la primera que el usuario pueda abrir.
     */
    private const CONCEPTOS = [
        [
            'clave' => 'odontograma',
            'pregunta' => '¿Dónde está el odontograma?',
            'palabras' => ['odontograma', 'odontologico', 'odontológico', 'diente', 'dientes', 'dental', 'pieza dental', 'odontologia', 'odontología'],
            'respuesta' => 'El odontograma no es una pantalla aparte: se abre dentro de Salud › Atenciones, en la atención del paciente. Solo aparece cuando el doctor que atiende es odontólogo; si el profesional no es odontólogo, la atención se registra como consulta médica normal y no muestra el odontograma.',
            'urls' => ['/health/attentions'],
        ],
        [
            'clave' => 'atenciones',
            'pregunta' => '¿Cómo registro una atención?',
            'palabras' => ['atencion', 'atención', 'atenciones', 'consulta', 'consultorio', 'registrar consulta'],
            'respuesta' => 'Entra a Salud › Atenciones, elige al paciente y registra la consulta: motivo, signos, diagnóstico, procedimientos y receta. Al guardar puedes firmar la atención con tu PIN; una vez firmada no se edita, se corrige con una nota. Si el doctor es odontólogo, dentro de la misma atención aparece el odontograma.',
            'urls' => ['/health/attentions'],
        ],
        [
            'clave' => 'historia',
            'pregunta' => '¿Dónde veo la historia clínica?',
            'palabras' => ['historia', 'historia clinica', 'historia clínica', 'ficha', 'antecedentes', 'evolucion', 'evolución'],
            'respuesta' => 'En Salud › Historias Clínicas. La ficha reúne atenciones, diagnósticos, procedimientos, recetas y antecedentes del paciente. Cada consulta e impresión queda auditada; la historia clínica se conserva 5 años y no se elimina desde el sistema.',
            'urls' => ['/health/clinical-records'],
        ],
        [
            'clave' => 'agenda',
            'pregunta' => '¿Cómo agendo una cita?',
            'palabras' => ['agenda', 'agendar', 'cita', 'citas', 'calendario', 'horario'],
            'respuesta' => 'En Salud › Agendas eliges al doctor, el día y el horario libre, y registras al paciente. El calendario de citas muestra lo agendado por profesional. Para mover o anular una cita, hazlo desde la agenda para que el horario quede libre y el cambio quede auditado.',
            'urls' => ['/health/agendas', '/calendar'],
        ],
        [
            'clave' => 'caja',
            'pregunta' => '¿Dónde está la caja o el efectivo?',
            'palabras' => ['caja', 'efectivo', 'arqueo', 'turno', 'apertura', 'cierre de caja'],
            'respuesta' => 'En Tesorería › Efectivo. Ahí se abre el turno, se registran ingresos y egresos y se cierra con el arqueo. El cierre compara lo contado con lo esperado; si hay diferencia, queda registrada con tu usuario y el detalle.',
            'urls' => ['/treasury/cash'],
        ],
        [
            'clave' => 'cobros',
            'pregunta' => '¿Cómo registro un cobro o un pago?',
            'palabras' => ['cobro', 'cobros', 'pago', 'pagos', 'abono', 'deuda', 'cuenta por cobrar'],
            'respuesta' => 'En Tesorería › Cobros se registra el pago contra la deuda del paciente o cliente y se emite el comprobante. Los pagos parciales quedan como saldo pendiente. En Ventas › Cuentas por cobrar puedes revisar la deuda y su estado.',
            'urls' => ['/treasury/collections'],
        ],
        [
            'clave' => 'notas',
            'pregunta' => '¿Cómo registro notas o asistencia?',
            'palabras' => ['nota', 'notas', 'calificacion', 'calificación', 'asistencia', 'falta', 'tarea', 'promedio'],
            'respuesta' => 'En Académico, según el nivel: Colegio › Docente › Notas para calificaciones y Colegio › Asistencia (o Asistencia › Administración) para marcar presentes y faltas. El sistema calcula el promedio con las notas registradas y guarda quién hizo cada cambio.',
            'urls' => ['/academic/school/teacher/grades', '/academic/attendance/administration'],
        ],
        [
            'clave' => 'comprobantes',
            'pregunta' => '¿Dónde veo los comprobantes o facturas?',
            'palabras' => ['comprobante', 'comprobantes', 'factura', 'boleta', 'nota de credito', 'nota de crédito', 'sunat'],
            'respuesta' => 'En Ventas › Comprobantes (documentos de cuentas por cobrar). Ahí se consultan boletas, facturas y notas de crédito, y se reimprimen o envían al cliente. La emisión electrónica y su estado SUNAT quedan registrados.',
            'urls' => ['/sales/accountsreceivable/document/list'],
        ],
        [
            'clave' => 'permisos',
            'pregunta' => '¿Cómo doy permisos a un usuario?',
            'palabras' => ['permiso', 'permisos', 'rol', 'roles', 'usuario', 'acceso', 'no puede entrar'],
            'respuesta' => 'En Seguridad › Roles y permisos se asigna a cada rol qué pantallas y acciones puede usar; luego el rol se asigna al usuario. El menú y las rutas vuelven a validar el permiso, así que ocultar una opción no basta. Los cambios de permisos quedan auditados.',
            'urls' => ['/security/roles'],
            // La ruta roles.index no declara permiso en el middleware (lo
            // valida el controlador), así que el concepto exige el permiso
            // del menú para no ofrecérselo a un alumno.
            'permiso' => 'roles',
        ],
        [
            'clave' => 'exportar',
            'pregunta' => '¿Cómo exporto un reporte?',
            'palabras' => ['exportar', 'excel', 'csv', 'pdf', 'imprimir', 'reporte', 'informe', 'descargar'],
            'respuesta' => 'En cada listado o reporte busca los botones «Exportar», «CSV» o «PDF». Se descarga lo que tienes filtrado en pantalla, no todo: aplica primero el rango de fechas y los filtros. En Salud cada impresión de historia, atención o receta queda auditada.',
            'urls' => [],
        ],
    ];

    /**
     * Texto que recibe la IA.
     */
    public static function texto(): string
    {
        return self::TEXTO;
    }

    /**
     * Conceptos que puede ver el usuario, con la primera pantalla permitida.
     *
     * @return array<int, array{clave: string, pregunta: string, palabras: array<int, string>, respuesta: string, url: ?string}>
     */
    public static function paraUsuario(?Authenticatable $usuario): array
    {
        if ($usuario === null) {
            return [];
        }

        $permitidas = collect(MapaSistema::paraUsuario($usuario))->keyBy('url');

        $conceptos = [];

        foreach (self::CONCEPTOS as $concepto) {
            if (isset($concepto['permiso']) && ! self::puede($usuario, $concepto['permiso'])) {
                continue;
            }

            $url = null;

            foreach ($concepto['urls'] as $candidata) {
                if ($permitidas->has($candidata)) {
                    $url = $candidata;
                    break;
                }
            }

            // Un concepto con pantallas solo se muestra si al menos una de
            // ellas está permitida; los generales (sin url) se muestran siempre.
            if ($concepto['urls'] !== [] && $url === null) {
                continue;
            }

            $conceptos[] = [
                'clave' => $concepto['clave'],
                'pregunta' => $concepto['pregunta'],
                'palabras' => $concepto['palabras'],
                'respuesta' => $concepto['respuesta'],
                'url' => $url,
            ];
        }

        return $conceptos;
    }

    /**
     * Comprueba un permiso del menú contra el usuario.
     */
    private static function puede(Authenticatable $usuario, string $permiso): bool
    {
        if (method_exists($usuario, 'hasPermissionTo')) {
            return $usuario->hasPermissionTo($permiso);
        }

        return method_exists($usuario, 'can') && $usuario->can($permiso);
    }
}
