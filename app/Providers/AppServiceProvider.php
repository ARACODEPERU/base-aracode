<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Parameter;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\ServiceProvider;
use App\Rules\SizeExistence;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
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
        Validator::extend('size_existence', function ($attribute, $value, $parameters, $validator) {
            $rule = new SizeExistence($parameters);

            return $rule->passes($attribute, $value);
        });

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
}
