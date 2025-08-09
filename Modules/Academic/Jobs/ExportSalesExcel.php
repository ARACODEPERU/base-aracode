<?php

namespace Modules\Academic\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Sale; // Tu modelo de Ventas
use App\Models\ExcelExportJob; // Tu modelo para rastrear los Jobs de exportación
use App\Models\PaymentMethod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon; // Para manejar fechas
use Illuminate\Database\Eloquent\Builder;

class ExportSalesExcel implements ShouldQueue
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

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Iniciando Job de Exportación de Ventas para usuario {$this->userId} y Job ID {$this->excelExportJobId}.");

        $excelExportJob = ExcelExportJob::find($this->excelExportJobId);
        if (!$excelExportJob) {
            Log::error("ExcelExportJob con ID {$this->excelExportJobId} no encontrado.");
            return;
        }

        try {
            $excelExportJob->update(['status' => 'processing', 'progress' => 0]);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte de Ventas');

            // Definir cabeceras de las columnas
            $headers = [
                'FECHA',
                'NOMBRE O RAZON SOCIAL',
                'CURSOS',
                'CELULAR',
                'ALUMNO',
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
            $query = Sale::query()->select([
                    'id', // Siempre incluir el ID para Eloquent
                    'user_id',
                    'client_id',
                    'local_id',
                    'total',
                    'advancement',
                    'total_discount',
                    'payments',
                    'sale_date',
                ])->with(['saleProduct', 'document', 'client']); // Cargar relaciones necesarias para los datos


            // Aplicar filtros (¡copiado de tu SaleController, asegúrate de que sea consistente!)
            if (isset($this->filters['paymentMethod_id']) && $this->filters['paymentMethod_id'] !== null) {
                $query->whereJsonContains('payments', ['type'=> $this->filters['paymentMethod_id']]);
            }

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
                    $query->whereBetween('sale_date', [$startDate, $endDate]);

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

            // Procesar los datos en chunks para manejar grandes volúmenes de datos
            $totalSales = $query->count();
            $processedCount = 0;

            $query->chunk(1000, function ($sales) use (&$sheet, &$currentRow, &$processedCount, $totalSales, $excelExportJob) {
                foreach ($sales as $sale) {
                    // 1. FECHA
                    $saleDate = Carbon::parse($sale->sale_date)->format('Y-m-d');

                    // 2. NOMBRE O RAZON SOCIAL
                    $clientRznSocial = $sale->document->client_rzn_social ?? 'N/A'; // Accede a la relación 'document'

                    // 3. CURSOS
                    $courses = [];
                    if ($sale->saleProduct->isNotEmpty()) { // Asume que saleProduct es una colección de productos
                        foreach ($sale->saleProduct as $product) {
                            // JSON.parse(product.saleProduct).title
                            // En PHP, asumimos que 'saleProduct' en la base de datos es un JSON string que necesitas decodificar
                            $productData = json_decode($product->saleProduct ?? '{}', true);
                            if (isset($productData['title'])) {
                                $courses[] = $productData['title'];
                            }
                        }
                    }
                    $coursesString = implode(', ', $courses); // Une los cursos con coma y espacio

                    // 4. CELULAR
                    $clientTelephone = $sale->client->telephone ?? 'N/A';

                    // 5. ALUMNO
                    $clientFullName = $sale->client->full_name ?? 'N/A';

                    $formaPago = $sale->document->forma_pago == 'Contado' ? 'Contado' : 'Crédito';
                    // 6. IMPORTE DE COBRANZA
                    $totalAmount = number_format($sale->total, 2, '.', ''); // Formato numérico

                    // 7. NRO. DE OPERACIÓN
                    // --- INICIO DE LA LÓGICA DE PAGOS MODIFICADA ---
                    $paymentsDetail = [];
                    // Decodificar el JSON del campo 'payments'
                    $paymentsDetailString =  null;
                    if($sale->payments){

                        $paymentsArray = $sale->payments;

                        // Asegurarse de que $paymentsArray es un array y no está vacío
                        if (is_array($paymentsArray) && !empty($paymentsArray)) {
                            foreach ($paymentsArray as $payment) {
                                $paymentTypeDescription = $this->getPaymentTypeDescription($payment['type']);
                                $paymentAmount = number_format($payment['amount'] ?? 0, 2, '.', '');
                                $paymentReference = $payment['reference'] ?? null;

                                $detail = "{$paymentTypeDescription}: S/. {$paymentAmount}";
                                if ($paymentReference) {
                                    $detail .= " (CÓDIGO: {$paymentReference})";
                                }
                                $paymentsDetail[] = $detail;
                            }
                        }
                        $paymentsDetailString = implode("\n", $paymentsDetail);
                        // --- FIN DE LA LÓGICA DE PAGOS MODIFICADA ---
                    }


                    $rowData = [
                        $saleDate,
                        $clientRznSocial,
                        $coursesString,
                        $clientTelephone,
                        $clientFullName,
                        $formaPago,
                        $totalAmount,
                        $paymentsDetailString,
                    ];

                    $sheet->fromArray($rowData, NULL, 'A' . $currentRow);
                    $currentRow++;
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
            $fileName = 'VENTAS_ESTUDIANTES' . Carbon::now()->format('Ymd') . '.xlsx';
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
