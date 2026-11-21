<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Services\CourseAuthoringService;

/**
 * Materiales descargables de una clase: PDF, Excel o Word. Equivale a la
 * opción «Subir Archivo» del mantenedor del curso.
 */
#[Name('academic_add_lesson_files')]
#[Description('Sube archivos PDF, Excel (.xlsx, .xls) o Word (.doc, .docx) a una clase en borrador. Cada archivo lleva title y file_base64 o file_url (https). Máximo 10 MB por archivo y 10 archivos por llamada. Usa el id de la clase que devolvió academic_add_lessons.')]
class AcademicAddLessonFilesTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'theme_id' => 'required|integer',
            'files' => 'required|array|min:1|max:' . CourseAuthoringService::MAX_FILES_PER_CALL,
            'files.*.title' => 'required|string|max:255',
            'files.*.filename' => 'nullable|string|max:255',
            'files.*.file_base64' => 'nullable|string',
            'files.*.file_url' => 'nullable|url:https|max:2048',
            'files.*.position' => 'nullable|integer|min:1|max:99',
        ], [
            'files.*.title.required' => 'Cada archivo necesita un title.',
            'files.*.file_url.url' => 'file_url debe ser una URL completa.',
        ], [
            'theme_id' => 'id de la clase',
            'files' => 'archivos',
            'files.*.title' => 'título del archivo',
            'files.*.filename' => 'nombre con extensión',
            'files.*.file_base64' => 'archivo en base64',
            'files.*.file_url' => 'URL https del archivo',
            'files.*.position' => 'posición del contenido',
        ]);

        $clase = AcaTheme::with('module')->find($datos['theme_id']);

        if (! $clase || ! $clase->module) {
            throw ValidationException::withMessages([
                'theme_id' => 'No existe una clase con ese id.',
            ]);
        }

        $this->cursoBorrador((int) $clase->module->course_id);

        foreach ($datos['files'] as $indice => $archivo) {
            if (blank($archivo['file_base64'] ?? null) && blank($archivo['file_url'] ?? null)) {
                throw ValidationException::withMessages([
                    "files.{$indice}" => 'Cada archivo necesita file_base64 o file_url.',
                ]);
            }
        }

        $creados = $this->authoring()->addLessonFiles($clase, $datos['files']);

        return $this->json([
            'theme_id' => $clase->id,
            'files' => array_map(fn (AcaContent $contenido) => [
                'id' => $contenido->id,
                'position' => (int) $contenido->position,
                'title' => $contenido->description,
                'path' => $contenido->content,
                'url' => asset('storage/' . $contenido->content),
            ], $creados),
            'next_step' => 'Los archivos ya están disponibles para el alumno. Cuando el curso esté completo llama a academic_validate_course.',
        ]);
    }
}
