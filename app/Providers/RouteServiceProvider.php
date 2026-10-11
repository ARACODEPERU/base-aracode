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
    }
}
