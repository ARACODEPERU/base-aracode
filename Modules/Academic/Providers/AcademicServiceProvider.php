<?php

namespace Modules\Academic\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class AcademicServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Academic';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'academic';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        // Comandos de consola (patron de Sales/Security: registro explicito via
        // ServiceProvider). En Laravel 12 Kernel::load() solo resuelve clases
        // bajo app/, de modo que los $this->load('Modules/*/Console') del Kernel
        // son inertes: sin este bloque artisan desconoce academic:* y la tarea
        // programada falla cada noche con exit code 1.
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Academic\Console\CheckExpiredSubscriptions::class,
            ]);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

        $this->registerTelegramRegistrantResolver();
        $this->registerTelegramAccountResolver();
    }

    /**
     * Vincula el padron academico al registro del bot de Telegram.
     *
     * Integrationhub no conoce alumnos: define el contrato y aqui se le dice
     * quien responde por el documento que alguien escribe en el chat del bot.
     * El enlace es condicional para que el modulo siga funcionando sin
     * Integrationhub instalado.
     */
    protected function registerTelegramRegistrantResolver()
    {
        $contract = \Modules\Integrationhub\Contracts\TelegramRegistrantResolver::class;

        if (! interface_exists($contract)) {
            return;
        }

        $this->app->singleton($contract, \Modules\Academic\Services\TelegramStudentDirectory::class);
    }

    /**
     * Vincula el padron academico a la consulta de cursos del bot de Telegram.
     *
     * Es el mismo padron del registro, pero para la consulta de cursos,
     * certificados y suscripcion con correo y documento. Condicional, para que el
     * modulo siga funcionando sin Integrationhub instalado.
     */
    protected function registerTelegramAccountResolver()
    {
        $contract = \Modules\Integrationhub\Contracts\TelegramAccountResolver::class;

        if (! interface_exists($contract)) {
            return;
        }

        $this->app->singleton($contract, \Modules\Academic\Services\TelegramStudentAccount::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'Resources/lang'));
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
