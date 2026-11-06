<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Sales\Support\GuiaRemisionPermissions;

return new class extends Migration
{
    /**
     * Producción: `php artisan migrate` crea los permisos, los agrupa en el
     * módulo Facturación Electrónica y los asigna a los roles administrativos.
     * Es idempotente: se puede volver a ejecutar sin duplicar nada.
     */
    public function up(): void
    {
        GuiaRemisionPermissions::sync();
    }

    public function down(): void
    {
        GuiaRemisionPermissions::remove();
    }
};
