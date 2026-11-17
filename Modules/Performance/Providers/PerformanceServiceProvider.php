<?php

namespace Modules\Performance\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Performance\Services\ImageBackupService;
use Modules\Performance\Services\ImageInventoryService;
use Modules\Performance\Services\ImageReferenceResolver;
use Modules\Performance\Services\ImageResizerService;
use Modules\Performance\Services\ImageToolsService;
use Modules\Performance\Services\PageWeightService;
use Modules\Performance\Services\QueueHealthService;

class PerformanceServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Performance';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'performance';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Performance\Console\AuditImagesCommand::class,
            ]);
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(ImageReferenceResolver::class);
        $this->app->singleton(ImageInventoryService::class);
        $this->app->singleton(ImageResizerService::class);
        $this->app->singleton(ImageBackupService::class);
        $this->app->singleton(ImageToolsService::class);
        $this->app->singleton(PageWeightService::class);
        $this->app->singleton(QueueHealthService::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Register views.
     */
    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     */
    protected function registerTranslations(): void
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
