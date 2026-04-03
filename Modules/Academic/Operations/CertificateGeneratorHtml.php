<?php

namespace Modules\Academic\Operations;

use Exception;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class CertificateGeneratorHtml
{
    /**
     * Genera una imagen PNG a partir de HTML usando Browsershot
     *
     * @param  string  $viewName  Nombre de la vista Blade (sin extensión)
     * @param  array  $data  Datos a pasar a la vista
     * @param  int  $width  Ancho de la imagen en píxeles
     * @param  int  $height  Alto de la imagen en píxeles
     * @return string Ruta de la imagen generada
     */
    public function generateFromView($viewName, $data, $width, $height)
    {

        // Cargar la vista usando la ruta directa del archivo
        $viewPath = base_path('Modules/Academic/Resources/views/certificates/'.$viewName.'.blade.php');

        if (! File::exists($viewPath)) {
            Log::error('View not found: '.$viewPath);

            return null;
        }

        // Cargar y renderizar la vista directamente desde el archivo
        $html = view()->file($viewPath, $data)->render();

        return $this->generateFromHtml($html, $width, $height);
    }

    /**
     * Genera una imagen PNG a partir de HTML
     *
     * @param  string  $html  Contenido HTML
     * @param  int  $width  Ancho de la imagen en píxeles
     * @param  int  $height  Alto de la imagen en píxeles
     * @return string Ruta de la imagen generada
     */
    public function generateFromHtml($html, $width, $height)
    {
        $directory = public_path('storage/tmp_html_cert');

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = Str::random(20).'.png';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        try {

            $rutaDeNode = env('NODE_BINARY_PATH');

            $browsershot = Browsershot::html($html)
                ->windowSize($width, $height)
                ->waitUntilNetworkIdle()
                ->setOption('omitBackground', true);

            // Aplicar configuración condicional
            empty($rutaDeNode)
                ? null // No hacer nada especial
                : $browsershot->setNodeBinary($rutaDeNode.'node')->setNpmBinary($rutaDeNode.'npm')->noSandbox() // <--- OBLIGATORIO al ejecutar como root o en Docker
                    ->setChromePath(env('RUTA_CHROMIUM')) // Usa el que instalamos con apt si el de npx falla;
                    ->addChromiumArguments([
                        'disable-setuid-sandbox',
                        'disable-dev-shm-usage',
                        'disable-extensions',
                        'no-zygote',
                    ]);
            $browsershot->save($path);

            return $path;

        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }

    /**
     * Prepara los datos del contenido del curso para la vista
     *
     * @param  object  $course  Curso con módulos y temas
     * @param  bool  $isModule  Si es certificado por módulo
     * @param  int|null  $moduleId  ID del módulo específico (para certificados por módulo)
     * @return array
     */
    public function prepareCourseContent($course, $isModule = false, $moduleId = null)
    {
        $content = [];
        $courseTitle = $course->description ?? 'Curso';

        if ($isModule && $moduleId) {
            $module = $course->modules()->where('id', $moduleId)->first();
            if ($module) {
                $content[] = [
                    'name' => $module->description,
                    'themes' => $module->themes()->pluck('description')->toArray(),
                ];
            }
        } else {
            $modules = $course->modules()->orderBy('position')->get();
            foreach ($modules as $module) {
                $content[] = [
                    'name' => $module->description,
                    'themes' => $module->themes()->orderBy('position')->pluck('description')->toArray(),
                ];
            }
        }

        return [
            'courseTitle' => $courseTitle,
            'content' => $content,
        ];
    }

    /**
     * Prepara datos de ejemplo para preview
     *
     * @param  string  $type  Tipo de contenido: 'list' o 'table'
     * @param  int  $width  Ancho de la imagen
     * @param  int  $height  Alto de la imagen
     * @return array
     */
    public function getExampleData($type = 'list')
    {
        return [
            'courseTitle' => 'Curso de Desarrollo Web Avanzado',
            'showExamGrade' => true,
            'showThemes' => true,
            'examGrade' => [
                '14.3',
                '16.5',
                '12.0',
            ],
            'content' => [
                [
                    'name' => 'Módulo 1: Fundamentos de Laravel',
                    'themes' => [
                        'Introducción a Laravel',
                        'Configuración del entorno',
                        'Routing y Controladores',
                        'Bases de datos con Eloquent',
                    ],
                ],
                [
                    'name' => 'Módulo 2: APIs RESTful',
                    'themes' => [
                        'Creación de API',
                        'Autenticación con JWT',
                        'Validación de datos',
                        'Documentación con Swagger',
                    ],
                ],
                [
                    'name' => 'Módulo 3: Vue.js Integrado',
                    'themes' => [
                        'Introducción a Vue 3',
                        'Componentes y Props',
                        'Estado con Pinia',
                        'Consumo de API',
                    ],
                ],
            ],
        ];
    }
}
