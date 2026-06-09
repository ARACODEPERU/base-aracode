<?php

namespace Modules\Integrationhub\Console;

use Illuminate\Console\Command;

class CarritoAbandonado extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'integrationhub:carrito-abandonado';

    /**
     * The console command description.
     */
    protected $description = 'Ejecuta la función carrito_abandonado';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Ejecutando carrito_abandonado...');

        // TODO: Agregar lógica de carrito abandonado

        $this->info('carrito_abandonado enviado a queue:work correctamente.');

        return 0;
    }
}
