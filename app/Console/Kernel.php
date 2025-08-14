<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Tarea para el módulo Académico: Verificar suscripciones vencidas
        // Se ejecutará a las 00:00 (medianoche) en la zona horaria de Chimbote.
        $schedule->command('academic:check-expired-subscriptions')
                    ->dailyAt('00:00')
                    ->timezone('America/Lima');

        // Tarea de ventas: Se ejecutará unos minutos DESPUÉS de la tarea académica.
        // Sugiero 10 minutos para dar tiempo a que la primera finalice.
        $schedule->command('sales:daily-tasks')
                    ->dailyAt('00:20') // Cambiado a 00:10
                    ->timezone('America/Lima');

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        $this->load(base_path('Modules/Sales/Console'));
        $this->load(base_path('Modules/Academic/Console'));
        require base_path('routes/console.php');
    }
}
