<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Services\CourseAuthoringService;

/**
 * Árbol completo de un curso: módulos, clases, contenidos y estado. Sirve para
 * revisar lo creado antes de pedirle a una persona que lo active.
 */
#[Name('academic_get_course')]
#[Description('Devuelve un curso con sus módulos, clases y contenidos, más su estado. Por defecto recorta el HTML de los contenidos; envía include_html: true para verlo completo. Usa course_id.')]
#[IsReadOnly]
class AcademicGetCourseTool extends AcademicTool
{
    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',
            'include_html' => 'nullable|boolean',
        ], [], [
            'course_id' => 'id del curso',
            'include_html' => 'incluir HTML completo',
        ]);

        $incluirHtml = (bool) ($datos['include_html'] ?? false);

        $curso = AcaCourse::with(['category:id,description', 'modality:id,description'])
            ->find($datos['course_id']);

        if (! $curso) {
            return Response::error('No existe un curso con ese id.');
        }

        $modulos = $curso->modules()
            ->with(['themes.contents.exam', 'exam.questions', 'mockExam.questions'])
            ->get()
            ->sortBy(fn (AcaModule $modulo) => (int) $modulo->position)
            ->values()
            ->map(fn (AcaModule $modulo) => [
                'id' => $modulo->id,
                'position' => (int) $modulo->position,
                'title' => $modulo->description,
                'exam' => $this->resumenExamen($modulo->exam),
                'mock_exam' => $this->resumenExamen($modulo->mockExam),
                'lessons' => $modulo->themes
                    ->sortBy(fn (AcaTheme $clase) => (int) $clase->position)
                    ->values()
                    ->map(fn (AcaTheme $clase) => [
                        'id' => $clase->id,
                        'position' => (int) $clase->position,
                        'title' => $clase->description,
                        'contents' => $clase->contents
                            ->sortBy(fn (AcaContent $contenido) => (int) $contenido->position)
                            ->values()
                            ->map(function (AcaContent $contenido) use ($incluirHtml) {
                                $texto = (string) $contenido->content;
                                $esHtml = in_array((int) $contenido->is_file, [0, 6], true);

                                return [
                                    'id' => $contenido->id,
                                    'position' => (int) $contenido->position,
                                    'title' => $contenido->description,
                                    'type' => CourseAuthoringService::contentType($contenido),
                                    // Sin include_html, el HTML se recorta para no
                                    // llenar el contexto del agente con un curso completo.
                                    'content' => $incluirHtml || ! $esHtml ? $texto : mb_substr($texto, 0, 280),
                                    'characters' => mb_strlen($texto),
                                    'truncated' => $esHtml && ! $incluirHtml && mb_strlen($texto) > 280,
                                    'html' => $incluirHtml && $esHtml ? $texto : null,
                                    'exam_id' => $contenido->exam?->id,
                                ];
                            })
                            ->all(),
                    ])
                    ->all(),
            ])
            ->all();

        $docentes = AcaTeacher::with('person')
            ->whereIn('id', $curso->teachers()->pluck('teacher_id'))
            ->get()
            ->map(fn (AcaTeacher $docente) => [
                'id' => $docente->id,
                'name' => $docente->person?->full_name ?? 'Docente sin nombre',
                'code' => $docente->teacher_code,
                'main' => (int) $curso->teacher_id === (int) $docente->id,
            ])
            ->values()
            ->all();

        return $this->json([
            'course' => [
                'id' => $curso->id,
                'title' => $curso->description,
                'slug' => $curso->slug,
                'status' => $curso->status ? 'activo' : 'borrador',
                'category' => $curso->category?->description,
                'modality' => $curso->modality?->description,
                'type' => $curso->type_description,
                'sector' => $curso->sector_description,
                'price' => $curso->price,
                'start_date' => trim($curso->course_year . '-' . $curso->course_month . '-' . $curso->course_day, '-'),
                'certificate_title' => $curso->certificate_title,
                'created_via' => $curso->created_via,
                'teacher_id' => $curso->teacher_id,
                'teacher' => collect($docentes)->firstWhere('main', true),
                'teachers' => $docentes,
                'final_exam' => $this->resumenExamen($this->authoring()->finalExam($curso)),
                'url' => route('aca_courses_information', $curso->id),
            ],
            'html_included' => $incluirHtml,
            'modules' => $modulos,
            'totals' => [
                'modules' => count($modulos),
                'lessons' => array_sum(array_map(fn (array $modulo) => count($modulo['lessons']), $modulos)),
                'contents' => array_sum(array_map(
                    fn (array $modulo) => array_sum(array_map(fn (array $clase) => count($clase['contents']), $modulo['lessons'])),
                    $modulos
                )),
                'module_exams' => count(array_filter(array_map(fn (array $modulo) => $modulo['exam'], $modulos))),
                'mock_exams' => count(array_filter(array_map(fn (array $modulo) => $modulo['mock_exam'], $modulos))),
            ],
        ]);
    }

    /**
     * Resumen legible de un examen del módulo, regular o simulacro.
     */
    private function resumenExamen(?AcaExam $examen): ?array
    {
        if (! $examen) {
            return null;
        }

        return [
            'id' => $examen->id,
            'title' => $examen->description,
            'type' => $examen->is_mock ? 'simulacro' : ($examen->module_id === null ? 'final' : 'regular'),
            'status' => $examen->status ? 'activo' : 'inactivo',
            'date_start' => $examen->date_start ? (string) $examen->date_start : null,
            'date_end' => $examen->date_end ? (string) $examen->date_end : null,
            'duration_minutes' => (int) $examen->duration_minutes,
            'attempts' => (int) $examen->attempts,
            'questions' => $examen->questions->count(),
        ];
    }
}
