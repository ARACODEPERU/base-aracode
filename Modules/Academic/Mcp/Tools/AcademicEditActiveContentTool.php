<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Services\CourseAuthoringService;

/**
 * Cambia el contenido de una fila existente sin eliminarla. No borra clases,
 * módulos, exámenes ni cursos.
 */
#[Name('academic_edit_active_content')]
#[Description('Permiso de activos. Cambia un contenido de un curso ACTIVO sin eliminarlo: update_text para texto; replace_file para reemplazar un PDF/Excel/Word; replace_link para reemplazar un enlace o convertir su tipo (por ejemplo videoconferencia Zoom/Meet/Teams a video de YouTube/Vimeo). No permite borrar contenidos, clases, módulos ni cursos. Primero usa confirm=false para ver el antes/después y pide confirmación; después repite con confirm=true y reason.')]
class AcademicEditActiveContentTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write_active';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'content_id' => 'required|integer',
            'operation' => ['required', Rule::in(['update_text', 'replace_file', 'replace_link'])],
            'title' => 'sometimes|required|string|max:255',
            'html' => 'sometimes|required|string|max:' . CourseAuthoringService::MAX_HTML_CHARS,
            'filename' => 'sometimes|nullable|string|max:255',
            'file_base64' => 'sometimes|nullable|string',
            'file_url' => 'sometimes|nullable|url:https|max:2048',
            'link_type' => ['sometimes', Rule::in(['zoom', 'meet', 'teams', 'vimeo', 'youtube', 'archivo'])],
            'url' => 'sometimes|url:https|max:2048',
            'confirm' => 'sometimes|boolean',
            'reason' => 'nullable|string|min:10|max:500|required_if:confirm,true',
        ], [
            'operation.in' => 'Operación no permitida. Usa update_text, replace_file o replace_link.',
            'reason.required_if' => 'Para aplicar un cambio en un curso activo escribe el motivo y la confirmación de la persona.',
            'html.max' => 'El HTML supera el ' . CourseAuthoringService::MAX_HTML_CHARS . ' caracteres permitido; divídelo en varios contenidos.',
        ], [
            'content_id' => 'id del contenido',
            'operation' => 'operación',
            'title' => 'título',
            'html' => 'HTML',
            'file_base64' => 'archivo en base64',
            'file_url' => 'URL del archivo',
            'link_type' => 'tipo de enlace',
            'url' => 'URL del enlace',
            'confirm' => 'confirmación',
            'reason' => 'motivo',
        ]);

        $contenido = AcaContent::with('theme.module.course')->find($datos['content_id']);

        if (! $contenido || ! $contenido->theme?->module?->course) {
            throw ValidationException::withMessages([
                'content_id' => 'No existe un contenido con ese id o no pertenece a un curso.',
            ]);
        }

        $curso = $this->activeEditing()->cursoActivo((int) $contenido->theme->module->course_id);
        $operacion = (string) $datos['operation'];

        $this->validarOperacion($contenido, $operacion, $datos);
        $before = $this->resumen($contenido);

        $proposed = [
            'operation' => $operacion,
            'title' => $datos['title'] ?? $contenido->description,
        ];

        if ($operacion === 'update_text') {
            $proposed['html_chars'] = mb_strlen((string) ($datos['html'] ?? ''));
        } elseif ($operacion === 'replace_file') {
            $proposed['filename'] = $datos['filename'] ?? null;
            $proposed['source'] = filled($datos['file_url'] ?? null) ? 'file_url' : 'file_base64';
        } else {
            $proposed['link_type'] = $datos['link_type'] ?? null;
            $proposed['url'] = $datos['url'] ?? null;
        }

        if (! ($datos['confirm'] ?? false)) {
            return $this->json([
                'requires_confirmation' => true,
                'course_id' => $curso->id,
                'preview' => [
                    'before' => $before,
                    'proposed' => $proposed,
                    'protected' => 'No se elimina el contenido ni se cambia el precio del curso.',
                ],
                'note' => 'No se guardó nada. Muestra el contenido actual y el cambio propuesto, confirma que el curso y el contenido son los correctos y vuelve a llamar con confirm=true y reason.',
            ]);
        }

        $resultado = $this->activeEditing()->cambiarContenido($contenido, $datos);

        return $this->json([
            'requires_confirmation' => false,
            'operation' => $operacion,
            'reason' => $datos['reason'],
            'result' => $resultado,
            'note' => 'Se reemplazó el contenido existente. No se eliminó ningún contenido, módulo, clase, examen ni curso.',
        ]);
    }

    /**
     * Impide que una operación cambie un contenido de un tipo incompatible o
     * que se use la vía de activos para borrar.
     */
    private function validarOperacion(AcaContent $contenido, string $operacion, array $datos): void
    {
        $tipo = (int) $contenido->is_file;
        $valor = (string) $contenido->content;

        if ($operacion === 'update_text') {
            $esTexto = $tipo === 6
                || ($tipo === 0 && ! str_contains($valor, '<iframe'))
                || ($tipo === 1 && ! preg_match('#^https?://#i', $valor));

            if (! $esTexto) {
                throw ValidationException::withMessages([
                    'operation' => 'Ese contenido no es de texto. Para un archivo usa replace_file; para un enlace o video usa replace_link.',
                ]);
            }

            if (blank($datos['html'] ?? null)) {
                throw ValidationException::withMessages([
                    'html' => 'Para update_text envía el HTML del contenido.',
                ]);
            }

            return;
        }

        if ($operacion === 'replace_file') {
            if ($tipo !== 2) {
                throw ValidationException::withMessages([
                    'operation' => 'replace_file solo se usa con un contenido que ya es un archivo.',
                ]);
            }

            if (blank($datos['file_base64'] ?? null) && blank($datos['file_url'] ?? null)) {
                throw ValidationException::withMessages([
                    'file_base64' => 'Envía file_base64 o file_url para reemplazar el archivo.',
                ]);
            }

            return;
        }

        if (! in_array($tipo, [0, 1, 3, 5], true) || ($tipo === 0 && ! str_contains($valor, '<iframe'))) {
            throw ValidationException::withMessages([
                'operation' => 'replace_link solo se usa con un video, videoconferencia, grabación o enlace existente.',
            ]);
        }

        if (blank($datos['link_type'] ?? null) || blank($datos['url'] ?? null)) {
            throw ValidationException::withMessages([
                'url' => 'Para replace_link envía link_type y url.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resumen(AcaContent $contenido): array
    {
        $valor = (string) $contenido->content;

        return [
            'id' => $contenido->id,
            'title' => $contenido->description,
            'type' => CourseAuthoringService::contentType($contenido),
            'is_file' => (int) $contenido->is_file,
            'content_preview' => mb_strlen($valor) > 300 ? mb_substr($valor, 0, 300) . '…' : $valor,
        ];
    }
}
