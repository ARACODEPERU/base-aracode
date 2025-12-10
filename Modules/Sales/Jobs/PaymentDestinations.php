<?php

namespace Modules\Sales\Jobs;

use App\Models\PaymentMethod;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Models\ExcelExportJob;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class PaymentDestinations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filters;
    protected $excelExportJobId;
    protected $userId;
    protected $paymentMethods;
    /**
     * Create a new job instance.
     */
    public function __construct(array $filters, int $excelExportJobId, int $userId)
    {
        $this->filters = $filters;
        $this->excelExportJobId = $excelExportJobId;
        $this->userId = $userId;
        $this->paymentMethods = PaymentMethod::with('bankAccount.bank')->get();
    }

    public function handle(): void
    {
        Log::info("Iniciando de Exportación de pagos {$this->userId} y Job ID {$this->excelExportJobId}.");

        $excelExportJob = ExcelExportJob::find($this->excelExportJobId);
        if (!$excelExportJob) {
            Log::error("ExcelExportJob con ID {$this->excelExportJobId} no encontrado.");
            return;
        }

        try {
            $excelExportJob->update(['status' => 'processing', 'progress' => 0]);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte de Ventas a cuotas');

            // Definir cabeceras de las columnas
            $headers = [
                'FECHA',
                'NOMBRE ESTUDIANTE',
                'DETALLE',
                'FORMA DE PAGO',
                'IMPORTE DE COBRANZA',
                'NRO. DE OPERACIÓN',
            ];

            $sheet->fromArray($headers, NULL, 'A1');

            // Aplicar estilos a las cabeceras
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], // Blanco
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF336699']], // Azul oscuro
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
            $currentRow = 2;

            // ----------------------------------------------------
            // Reconstruir la consulta de ventas basada en los filtros
            // ----------------------------------------------------
            $query = Sale::query()
                ->with([
                    'documents.schedules.destinations.method',
                    'client'
                ])
                ->where('payment_installments', true);
            // --- INICIO DE LA LÓGICA DE FECHA MODIFICADA ---
            if (isset($this->filters['issue_date'])) {
                $issueDateRange = $this->filters['issue_date'];
                $dates = explode(' a ', $issueDateRange);

                try {
                    if (count($dates) === 2) {
                        // Es un rango de fechas
                        $startDate = Carbon::parse($dates[0])->startOfDay();
                        $endDate = Carbon::parse($dates[1])->endOfDay();
                    } else {
                        // Es una sola fecha
                        $startDate = Carbon::parse($dates[0])->startOfDay();
                        $endDate = Carbon::parse($dates[0])->endOfDay();
                    }
                    $query->whereHas('documents', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('invoice_broadcast_date', [$startDate, $endDate]);
                    });

                } catch (\Exception $e) {
                    // Loguear el error si hay problemas al parsear la fecha en el Job
                    Log::error("Error al parsear fecha en issue_date en Job (ID: {$this->excelExportJobId}): {$issueDateRange} - " . $e->getMessage());
                    // Puedes decidir si el Job debe fallar o continuar sin este filtro.
                    // Por ahora, solo loguea y permite que el Job continúe si otros filtros son válidos.
                }
            }
            // --- FIN DE LA LÓGICA DE FECHA MODIFICADA ---

            if (isset($this->filters['search'])) {
                $searchTerm = $this->filters['search'];
                $query->whereHas('client', function (Builder $q) use ($searchTerm) {
                    $q->where('full_name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('number', $searchTerm);
                });
            }
            // ----------------------------------------------------

            $totalSales = $query->count();
            $processedCount = 0;

            $query->chunk(500, function ($sales) use (&$sheet, &$currentRow, &$processedCount, $totalSales, $excelExportJob) {

                foreach ($sales as $sale) {

                    $clientName = $sale->client->full_name ?? 'N/A';

                    foreach ($sale->documents as $document) {

                        foreach ($document->schedules as $schedule) {

                            foreach ($schedule->destinations as $destination) {

                                // FECHA DEL PAGO
                                $paymentDate = $document->invoice_broadcast_date ?? 'N/A';

                                // DETALLE: ejemplo => pago de cuota 1
                                $detalle = "pago de cuota: " . ($schedule->installment_number ?? '?');

                                // FORMA DE PAGO
                                $paymentMethod = $destination->method->description ?? 'N/A';

                                // IMPORTE
                                $amount = number_format($destination->amount ?? 0, 2, '.', '');

                                // Nº OPERACIÓN
                                $reference = $destination->reference ?? '-';

                                // ---- Fila final ----
                                $rowData = [
                                    $paymentDate,
                                    $clientName,
                                    $detalle,
                                    $paymentMethod,
                                    $amount,
                                    $reference,
                                ];

                                $sheet->fromArray($rowData, null, 'A' . $currentRow);
                                $currentRow++;
                            }
                        }
                    }
                }

                $processedCount += $sales->count();
                $progress = ($totalSales > 0) ? floor(($processedCount / $totalSales) * 100) : 0;
                $excelExportJob->update(['progress' => $progress]);
            });

            // Ajustar el ancho de las columnas automáticamente
            foreach (range('A', $sheet->getHighestColumn()) as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Guardar el archivo en storage/app/public/exports
            $fileName = 'VENTAS_MEDIODEPAGO' . Carbon::now()->format('Ymd') . '.xlsx';
            $filePath = 'exports/' . $fileName; // Ruta relativa dentro del disco

            Storage::disk('public')->makeDirectory('exports'); // Asegura que el directorio exista
            $writer = new Xlsx($spreadsheet);
            $writer->save(Storage::disk('public')->path($filePath));

            // Obtener la URL pública para la descarga
            $downloadUrl = Storage::disk('public')->url($filePath);

            // Actualizar el estado del job en la base de datos
            $excelExportJob->update([
                'status' => 'completed',
                'file_name' => $fileName,
                'file_path' => $filePath,
                'download_url' => $downloadUrl,
                'progress' => 100,
            ]);

            Log::info("Job de Exportación de Ventas {$this->excelExportJobId} completado. Archivo: {$downloadUrl}");

        } catch (\Throwable $e) {
            // Manejar errores
            Log::error("Error en ExportSalesExcel Job ID {$this->excelExportJobId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            if ($excelExportJob) {
                $excelExportJob->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function getPaymentTypeDescription($idToFind): string
    {
        if ($idToFind === null || empty($this->paymentMethods)) {
            return 'Tipo Desconocido';
        }

        // --- AJUSTE CLAVE: Comparación de tipos de datos ---
        foreach ($this->paymentMethods as $method) {
            // Convertimos ambos a string para asegurar la comparación si los tipos no coinciden
            // (ej. si uno es int y el otro string)
            if (isset($method->id) && (string) $method->id === (string) $idToFind) {
                return $method['description'] ?? 'Tipo ' . $idToFind;
            }
        }
        // Si no se encuentra, devolvemos 'Tipo [ID]' para depuración
        return 'Tipo ' . $idToFind;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ExportSalesExcel Job ID {$this->excelExportJobId} falló: " . $exception->getMessage());
        $excelExportJob = ExcelExportJob::find($this->excelExportJobId);
        if ($excelExportJob) {
            $excelExportJob->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
