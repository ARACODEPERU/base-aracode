<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

class Breadcrumbs
{
    /**
     * Resuelve los niveles de miga que debe pintar el header público.
     *
     * @param  string|null  $routeName  Nombre de la ruta actual.
     * @param  array|null   $override   Niveles enviados por la página (prop :breadcrumb).
     * @return array<int, array{label: string, url: string|null}>
     */
    public static function forRoute(?string $routeName, ?array $override = null): array
    {
        $levels = self::normalize($override) ?: self::normalize(self::fromConfig($routeName));

        $levels = self::withHome($levels);

        // Un solo nivel (solo "Inicio") no justifica la barra.
        return count($levels) > 1 ? $levels : [];
    }

    /**
     * Niveles declarados en config/breadcrumbs.php para la ruta dada.
     */
    protected static function fromConfig(?string $routeName): array
    {
        if (! $routeName) {
            return [];
        }

        $route = config("breadcrumbs.routes.{$routeName}", []);

        return is_array($route) ? $route : [];
    }

    /**
     * Normaliza los niveles a ['label' => string, 'url' => string|null].
     *
     * Acepta strings, ['label' => ..., 'url' => ...], ['label' => ..., 'route' => ...]
     * y la forma corta ['Label', 'https://...'].
     */
    protected static function normalize(?array $levels): array
    {
        $normalized = [];

        foreach ($levels ?? [] as $level) {
            if (is_string($level)) {
                $normalized[] = ['label' => $level, 'url' => null];

                continue;
            }

            if (! is_array($level)) {
                continue;
            }

            $label = $level['label'] ?? (is_string($level[0] ?? null) ? $level[0] : null);

            if (! $label) {
                continue;
            }

            $url = $level['url'] ?? null;

            if (! $url && ! empty($level['route']) && Route::has($level['route'])) {
                $url = route($level['route'], $level['params'] ?? []);
            }

            $normalized[] = ['label' => $label, 'url' => $url];
        }

        return $normalized;
    }

    /**
     * Antepone el nivel raíz ("Inicio") cuando la página no lo trae ya.
     */
    protected static function withHome(array $levels): array
    {
        if (empty($levels)) {
            return [];
        }

        $homeLabel = config('breadcrumbs.home.label', 'Inicio');
        $homeRoute = config('breadcrumbs.home.route');

        if (mb_strtolower(trim($levels[0]['label'])) === mb_strtolower(trim($homeLabel))) {
            return $levels;
        }

        $url = ($homeRoute && Route::has($homeRoute)) ? route($homeRoute) : null;

        array_unshift($levels, ['label' => $homeLabel, 'url' => $url]);

        return $levels;
    }
}
