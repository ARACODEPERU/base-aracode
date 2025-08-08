<?php

namespace Modules\Sales\Console;

use App\Models\SaleDocument;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Sales\Entities\SaleDocumentQuota;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class DailySalesTasks extends Command
{

    protected $signature = 'sales:daily-tasks'; // Prefijo específico para este módulo
    protected $description = 'Executes daily scheduled tasks for the Sales module.';


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
        $this->info('Iniciando tareas de Modulo Sales diarias...');

        $this->checkExpiredQuotas(); // Tu lógica de cuotas vencidas

        // Aquí podrías añadir otras tareas específicas del módulo Sales:

        $this->info('Tareas de ventas diarias completadas.');
        return Command::SUCCESS;
    }

    private function checkExpiredQuotas()
    {
        $this->info('  - Comprobando cuotas de ventas vencidas...');
        $today = Carbon::today(); // E.g., 2025-08-05 00:00:00

        $expiredQuotas = SaleDocumentQuota::whereDate('due_date', '<', $today)
                                             ->whereIn('status', ['Pendiente', 'Parcialmente Pagada', 'Amortizado'])
                                            ->get();

        $countUpdatedQuotas = 0;
        $countUpdatedSaleDocuments = 0;
        $updatedSaleDocumentIds = []; // To keep track of SaleDocuments already marked

        foreach ($expiredQuotas as $quota) {
            if ($quota->balance > 0) {
                $quota->status = 'Vencido';
                $quota->save();
                $countUpdatedQuotas++;

                $this->info(" -> Cuota ID {$quota->id} (Doc Venta: {$quota->sale_document_id}) actualizada a 'Vencido'.");

                // --- ¡NUEVA LÓGICA PARA ACTUALIZAR EL SALE DOCUMENT! ---
                if ($quota->sale_document_id && !in_array($quota->sale_document_id, $updatedSaleDocumentIds)) {
                    $saleDocument = SaleDocument::find($quota->sale_document_id);
                    if ($saleDocument && !$saleDocument->overdue_fee) { //Solo actualizar si no es verdadero
                        $saleDocument->overdue_fee = true;
                        $saleDocument->save();
                        $countUpdatedSaleDocuments++;
                        $updatedSaleDocumentIds[] = $saleDocument->id; // Agregar para evitar múltiples actualizaciones para el mismo documento
                        $this->info(" -> Documento de Venta ID {$saleDocument->id} marcado con 'overdue_fee = true'.");
                    }
                }
                // ---------------------------------------------------
            } else {
                $this->info(" -> Cuota ID {$quota->id} (Doc Venta: {$quota->sale_document_id}) no actualizada: Saldo es 0. Balance: {$quota->balance}");
            }
        }


        $this->info(" - Se terminó de verificar las cuotas de ventas vencidas. {$countUpdatedQuotas} cuotas actualizadas a 'Vencido'.");
        $this->info(" - Se actualizaron {$countUpdatedSaleDocuments} documentos de venta con 'overdue_fee = true'.");
    }
}
