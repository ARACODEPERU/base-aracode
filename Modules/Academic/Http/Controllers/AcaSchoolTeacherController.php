<?php

namespace Modules\Academic\Http\Controllers;

/**
 * Mantenedor de docentes del colegio.
 *
 * Clon de AcaTeacherController (capacitaciones): hereda toda la logica
 * (listado, crear/editar/eliminar, curriculum) pero renderiza las vistas
 * Academic::School/Teachers y se enruta bajo school/teachers con los
 * permisos propios aca_school_docente_*, para que el grupo Colegio del
 * menu no dependa del grupo Capacitacion ni active ese grupo en el
 * sidebar.
 */
class AcaSchoolTeacherController extends AcaTeacherController
{
    protected function viewNamespace(): string
    {
        return 'Academic::School/';
    }

    protected function routeName(string $suffix): string
    {
        return 'aca_school_teachers_' . $suffix;
    }
}
