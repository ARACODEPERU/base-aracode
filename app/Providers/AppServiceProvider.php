<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Parameter;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\ServiceProvider;
use App\Rules\SizeExistence;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureWritableStorageDirectories();

        Validator::extend('size_existence', function ($attribute, $value, $parameters, $validator) {
            $rule = new SizeExistence($parameters);

            return $rule->passes($attribute, $value);
        });

        // El sitio web (paginas, componentes y layout) vive dentro de
        // resources/views/aracode. Se registra el path de componentes anonimos
        // para que los tags <x-...> del sitio sigan resolviendo igual aunque
        // ya no esten en resources/views/components.
        Blade::anonymousComponentPath(resource_path('views/aracode/components'));

        Inertia::share('company', function () {
            return Company::first();
        });

        Inertia::share([
            'MERCADOPAGO_KEY' => config('services.mercadopago.key'),
            'MERCADOPAGO_MAX_INSTALLMENTS' => config('services.mercadopago.max_installments'),
        ]);

        // SQLite no conoce las collations de MySQL. Las pruebas corren sobre
        // sqlite :memory: y algunas migraciones declaran collation explicita,
        // asi que se registran en el PDO para que el esquema pueda crearse.
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            if ($event->connection->getDriverName() !== 'sqlite') {
                return;
            }

            $pdo = $event->connection->getPdo();

            if (!$pdo instanceof \PDO) {
                return;
            }

            foreach ([
                'utf8mb4_unicode_ci',
                'utf8mb4_general_ci',
                'utf8mb4_spanish_ci',
                'utf8mb4_spanish2_ci',
                'utf8mb4_0900_ai_ci',
                'utf8mb4_bin',
            ] as $collation) {
                $pdo->sqliteCreateCollation($collation, fn ($a, $b) => strcmp($a, $b));
            }
        });
    }

    /**
     * Asegura los directorios donde el framework escribe en tiempo de ejecucion
     * (cache de archivos, sesiones, vistas compiladas, logs y config cacheada).
     *
     * En despliegues que no copian carpetas vacias (git archive, rsync con
     * exclusiones, imagenes Docker reconstruidas) esos directorios desaparecen y
     * la primera escritura revienta con "file_put_contents(...): Failed to open
     * stream: No such file or directory". Se recrean al arrancar y, si el sistema
     * de archivos no lo permite, se deja el motivo en el log en lugar de dejar
     * solo el error 500.
     */
    protected function ensureWritableStorageDirectories(): void
    {
        $directories = [
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $directory) {
            if (is_dir($directory)) {
                continue;
            }

            try {
                // El enlace roto se reporta abajo: mkdir no puede reemplazarlo.
                if (! is_link($directory)) {
                    @mkdir($directory, 0775, true);
                }
            } catch (\Throwable $e) {
                // Se informa en el log de la comprobacion siguiente.
            }
        }

        $cacheDirectory = storage_path('framework/cache/data');

        if (is_dir($cacheDirectory) && is_writable($cacheDirectory)) {
            return;
        }

        Log::warning('El directorio de cache por archivos no existe o no es escribible; la cache (incluida la de permisos de Spatie) fallara.', [
            'path' => $cacheDirectory,
            'existe' => file_exists($cacheDirectory),
            'es_enlace' => is_link($cacheDirectory),
            'enlace_roto' => is_link($cacheDirectory) && ! is_dir($cacheDirectory),
            'proceso' => function_exists('posix_geteuid') ? posix_geteuid() : null,
        ]);
    }
}
