<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Examen regular del módulo: el que cuenta para la nota y el certificado.
 */
#[Name('academic_create_module_exam')]
#[Description('Crea o actualiza el examen regular de un módulo (cuenta para la nota). Envía module_id, title, date_start, date_end, duration_minutes y, si ya las tienes, las preguntas en questions. Queda inactivo hasta que una persona active el curso.')]
class AcademicCreateModuleExamTool extends AcademicModuleExamTool
{
    protected function esSimulacro(): bool
    {
        return false;
    }
}
