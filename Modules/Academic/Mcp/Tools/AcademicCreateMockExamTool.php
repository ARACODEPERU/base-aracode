<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Simulacro del módulo (is_mock): el alumno lo resuelve como práctica y no
 * afecta el promedio ni el certificado.
 */
#[Name('academic_create_mock_exam')]
#[Description('Crea o actualiza el simulacro de examen de un módulo (is_mock, no afecta el promedio). Envía module_id, title, date_start, date_end, duration_minutes y las preguntas en questions. Queda inactivo hasta que una persona active el curso. Usa academic_add_exam_questions para agregar más preguntas después.')]
class AcademicCreateMockExamTool extends AcademicModuleExamTool
{
    protected function esSimulacro(): bool
    {
        return true;
    }
}
