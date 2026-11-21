<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Academic\Entities\AcaStudent;

/**
 * Ficha académica de solo lectura: cursos, acceso, avance, exámenes,
 * asistencias, notas, certificados y suscripciones.
 */
#[Name('academic_get_student')]
#[Description('Solo lectura. Devuelve la ficha académica de un estudiante por id, documento o correo: cursos, acceso, porcentaje de avance, exámenes, asistencias, notas, certificados y suscripciones por vencer. No devuelve contraseñas ni credenciales.')]
#[IsReadOnly]
class AcademicGetStudentTool extends AcademicTool
{
    protected ?string $permission = 'aca_estudiante_listado';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'student_id' => 'nullable|integer|exists:aca_students,id',
            'document' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
        ], [], [
            'student_id' => 'id del estudiante',
            'document' => 'documento',
            'email' => 'correo',
        ]);

        if (blank($datos['student_id'] ?? null) && blank($datos['document'] ?? null) && blank($datos['email'] ?? null)) {
            return Response::error('Indica student_id, document o email para identificar al estudiante.');
        }

        $consulta = AcaStudent::query()->with('person');

        if (! empty($datos['student_id'])) {
            $consulta->whereKey($datos['student_id']);
        } elseif (! empty($datos['document'])) {
            $consulta->whereHas('person', fn ($persona) => $persona->where('number', trim((string) $datos['document'])));
        } else {
            $consulta->whereHas('person', fn ($persona) => $persona->where('email', trim((string) $datos['email'])));
        }

        $estudiantes = $consulta->limit(2)->get();

        if ($estudiantes->isEmpty()) {
            return Response::error('No se encontró un estudiante con esos datos.');
        }

        if ($estudiantes->count() > 1) {
            return Response::error('Hay más de un estudiante con esos datos; usa student_id para elegir uno.');
        }

        return $this->json($this->insights()->studentDetail($estudiantes->first()));
    }
}
