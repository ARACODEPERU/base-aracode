<?php

namespace Modules\Academic\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Entities\AcaSchool;
use Throwable;

class ColegioPublicController extends Controller
{
    /**
     * Página institucional del colegio.
     */
    public function index()
    {
        return view('colegio.pages.home', [
            'school' => $this->school(),
            'levels' => $this->levels(),
            'year' => now()->year,
        ]);
    }

    /**
     * Datos institucionales del colegio por defecto.
     *
     * Se resuelven con valores de respaldo para que la página pública siga
     * funcionando si la tabla del colegio todavía no existe o está vacía.
     */
    protected function school(): array
    {
        try {
            $school = AcaSchool::defaultSchool();
        } catch (Throwable $e) {
            $school = null;
        }

        $logoUrl = null;

        if ($school?->logo) {
            try {
                if (Storage::disk('public')->exists($school->logo)) {
                    $logoUrl = Storage::disk('public')->url($school->logo);
                }
            } catch (Throwable $e) {
                $logoUrl = null;
            }
        }

        $name = $school?->name ?: config('app.name');

        return [
            'name' => $name,
            'initial' => mb_strtoupper(mb_substr($name, 0, 1)),
            'type' => $school?->type ? (AcaSchool::typeLabels()[$school->type] ?? null) : null,
            'address' => $school?->address,
            'phone' => $school?->phone,
            'phone_digits' => $school?->phone ? preg_replace('/\D+/', '', $school->phone) : null,
            'email' => $school?->email,
            'modular_code' => $school?->modular_code,
            'logo_url' => $logoUrl,
        ];
    }

    /**
     * Niveles educativos que se ofrecen en el sitio.
     *
     * Los tres niveles comparten el mismo login: después de validar la
     * matrícula, cada alumno entra a las vistas de su nivel.
     */
    private function levels(): array
    {
        return [
            [
                'key' => 'inicial',
                'name' => 'Inicial',
                'ages' => '3 a 5 años',
                'description' => 'Juego, exploración y afecto en un entorno seguro para dar los primeros pasos.',
                'icon' => 'sun',
                'available' => true,
                'url' => route('colegio.aula.login'),
            ],
            [
                'key' => 'primaria',
                'name' => 'Primaria',
                'ages' => '6 a 11 años',
                'description' => 'Lectura, curiosidad y pensamiento crítico acompañados por docentes cercanos.',
                'icon' => 'book',
                'available' => true,
                'url' => route('colegio.aula.login'),
            ],
            [
                'key' => 'secundaria',
                'name' => 'Secundaria',
                'ages' => '12 a 16 años',
                'description' => 'Formación académica sólida y acompañamiento para construir su proyecto de vida.',
                'icon' => 'star',
                'available' => true,
                'url' => route('colegio.secundaria.login'),
            ],
        ];
    }
}
