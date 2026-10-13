<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Sitio web corporativo de ARACODE (páginas públicas, blog,
            // carrito y consultas públicas). Sin prefijo para conservar las
            // mismas URLs que tenía en routes/web.php.
            Route::middleware('web')->group(base_path('routes/aracode.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Banco de Plantillas Web: catálogo, fichas y abridor de demos.
            // Archivo propio para no tocar las rutas del sitio corporativo.
            Route::middleware('web')->group(base_path('routes/plantillas.php'));

            // Sitios web independientes de cada producto (Blade puro).
            // Cada archivo declara su propio prefijo en una sola línea.
            Route::middleware('web')->group(base_path('routes/pichanguero.php'));
            Route::middleware('web')->group(base_path('routes/kapta.php'));
            Route::middleware('web')->group(base_path('routes/kirafact.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Formulario de contacto (la página y el modal «Solicitar Asesoría»
        // comparten este destino). Dos topes por IP en lugar de uno solo: el de
        // minuto corta la ráfaga de un bot y el de hora frena al que va despacio
        // para no llamar la atención. La respuesta se escribe aquí para que el
        // aviso salga en español —el de fábrica dice «Too Many Attempts»— y para
        // que el AJAX reciba el mismo JSON que en cualquier otro error. Cuenta
        // TODO envío, incluidos los que luego descarta el campo trampa.
        RateLimiter::for('contacto', function (Request $request) {
            $respuesta = function (Request $request, array $headers) {
                $mensaje = $this->mensajeDeLimite((int) ($headers['Retry-After'] ?? 60));

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $mensaje,
                    ], 429, $headers);
                }

                return redirect()->route('contacto')->with('error', $mensaje);
            };

            // Cada límite lleva su propio sufijo en la clave: el middleware
            // guarda el contador como «nombre + by()», así que dos límites con el
            // mismo by() compartirían contador y cada envío gastaría cupo de los
            // dos, cortando la ráfaga al cuarto envío en vez de al sexto.
            return [
                Limit::perMinute(5)->by($request->ip() . ':minuto')->response($respuesta),
                Limit::perHour(20)->by($request->ip() . ':hora')->response($respuesta),
            ];
        });
    }

    /**
     * Mensaje del límite de envíos, con la espera que ya calculó el middleware.
     */
    protected function mensajeDeLimite(int $segundos): string
    {
        $espera = '1 segundo';

        if ($segundos >= 120) {
            $minutos = (int) ceil($segundos / 60);
            $espera = $minutos . ' minutos';
        } elseif ($segundos >= 60) {
            $espera = '1 minuto';
        } elseif ($segundos > 1) {
            $espera = $segundos . ' segundos';
        }

        return 'Has enviado demasiadas solicitudes seguidas. Espera ' . $espera . ' y vuelve a intentarlo.';
    }
}
