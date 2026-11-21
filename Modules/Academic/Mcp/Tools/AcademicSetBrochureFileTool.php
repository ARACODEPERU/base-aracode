<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * PDF del brochure: recibe el archivo en base64 o una URL https pública.
 */
#[Name('academic_set_brochure_file')]
#[Description('Carga o reemplaza el PDF del brochure de un curso en borrador. Acepta file_base64 o file_url (https público), con filename .pdf. El PDF queda enlazado al brochure.')]
class AcademicSetBrochureFileTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',
            'filename' => 'nullable|string|max:255',
            'file_base64' => 'required_without:file_url|nullable|string',
            'file_url' => 'required_without:file_base64|nullable|url:https',
        ], [
            'file_base64.required_without' => 'Envía file_base64 o file_url.',
            'file_url.required_without' => 'Envía file_base64 o file_url.',
        ], [
            'course_id' => 'id del curso',
            'file_base64' => 'PDF en base64',
            'file_url' => 'URL del PDF',
        ]);

        $curso = $this->cursoBorrador((int) $datos['course_id']);
        $brochure = app(\Modules\Academic\Services\CourseLandingService::class)->setBrochureFile($curso, $datos);

        return $this->json([
            'course_id' => $curso->id,
            'brochure' => [
                'file' => $brochure->path_file,
                'blocks' => [
                    'resolution' => filled($brochure->resolution),
                    'presentation' => filled($brochure->presentation),
                    'benefits' => filled($brochure->benefits),
                    'frequent_questions' => filled($brochure->frequent_questions),
                    'curriculum_plan' => filled($brochure->curriculum_plan),
                ],
            ],
            'message' => 'PDF del brochure guardado. Revisa que se abra correctamente antes de publicar.',
        ]);
    }
}
