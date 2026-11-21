<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * El agente consulta al inicio si la persona le dio permiso absoluto. Así sabe
 * si debe pedir confirmación antes de cada lote de escritura o si puede
 * continuar directo con la creación.
 */
#[Name('academic_get_permissions')]
#[Description('Devuelve el modo de permiso de esta conexión: autonomo (permiso absoluto para crear y editar borradores) o confirmar. También indica can_edit_active y active_edit_requires_confirmation. Los cambios en cursos activos y sus landings siempre requieren confirmación y motivo, incluso con permiso absoluto. No modifica datos.')]
#[IsReadOnly]
class AcademicGetPermissionsTool extends AcademicTool
{
    /** No toca datos del ERP: no exige un permiso Spatie adicional. */
    protected ?string $permission = null;

    protected function execute(Request $request): Response
    {
        $usuario = $request->user();
        $token = $usuario?->currentAccessToken();
        $tokenReal = $token instanceof PersonalAccessToken;

        // Una sesión de navegador no es una conexión MCP: se trata como modo
        // normal para no conceder autonomía por accidente.
        $autonomo = $tokenReal && $token->can('academic:autonomous');
        $escritura = $tokenReal
            ? $token->can('academic:write')
            : ($usuario?->can('aca_cursos_nuevo') || $usuario?->can('aca_cursos_editar'));
        $editarActivos = $tokenReal
            && $token->can('academic:write_active')
            && $token->can('academic:read');

        return $this->json([
            'mode' => $autonomo ? 'autonomo' : 'confirmar',
            'autonomous' => $autonomo,
            'can_write' => (bool) $escritura || $editarActivos,
            'can_edit_active' => $editarActivos,
            'active_edit_requires_confirmation' => true,
            'abilities' => $tokenReal ? array_values($token->abilities ?? []) : [],
            'meaning' => $autonomo
                ? 'La persona ya autorizó crear y editar borradores sin confirmaciones del servidor. El permiso absoluto NO se aplica a cursos activos ni a sus landings: esas herramientas siempre piden confirmación y motivo.'
                : 'La persona no dio permiso absoluto. Pide confirmación antes de cada lote de escritura (crear, editar, subir archivos o publicar).',
            'active_edit_limits' => [
                'sí: texto, archivos, enlaces, conversión de videoconferencia a video, imágenes, brochure y secciones de landing',
                'sí: reemplazar un documento o enlace por otro, o quitar solo una imagen de portada',
                'no: precios, descuentos, tipo de curso, estado, slug, publicación ni sección de inversión',
                'no: eliminar cursos, módulos, clases, contenidos, exámenes o documentos',
            ],
            'still_requires_confirmation' => [
                'activar el curso (no existe herramienta para hacerlo)',
                'borrar cursos, módulos, clases, contenidos o exámenes',
                'cualquier cambio en un curso activo o en su landing, incluso con permiso absoluto',
            ],
            'client_note' => 'El cliente MCP puede mostrar sus propios avisos de seguridad por herramienta; esta preferencia gobierna las confirmaciones del servidor y las instrucciones del agente.',
        ]);
    }
}
