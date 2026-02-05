<?php

namespace Modules\Academic\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Sale;
use App\Models\ExcelExportJob;
use App\Models\PaymentMethod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ExportSalesExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filters;
    protected $excelExportJobId;
    protected $userId;
    protected $paymentMethods;

    public function __construct(array $filters, int $excelExportJobId, int $userId)
    {
        $this->filters = $filters;
        $this->excelExportJobId = $excelExportJobId;
        $this->userId = $userId;
        $this->paymentMethods = PaymentMethod::all();
    }

    public function handle(): void
    {
        Log::info("Iniciando Job de Exportación de Ventas ID {$this->excelExportJobId}.");

        $excelExportJob = ExcelExportJob::find($this->excelExportJobId);
        if (!$excelExportJob) return;

        try {
            $excelExportJob->update(['status' => 'processing', 'progress' => 0]);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte de Ventas');

            // Cabeceras actualizadas con DOCUMENTO en la posición correcta
            $headers = [
                'FECHA',
                'NOMBRE O RAZON SOCIAL',
                'CURSOS',
                'CELULAR',
                'ALUMNO',
                'DOCUMENTO',
                'FORMA DE PAGO',
                'IMPORTE DE COBRANZA',
                'DETALLE DE PAGOS'
            ];

            $sheet->fromArray($headers, NULL, 'A1');
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF336699']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
            // Estilo aplicado de A1 hasta I1 (9 columnas)
            $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

            $currentRow = 2;

            $query = Sale::query()->select([
                'id', 'user_id', 'client_id', 'local_id',
                'total', 'advancement', 'total_discount',
                'payments', 'sale_date'
            ])->with(['saleProduct', 'document', 'client']);

            // Filtros
            if (!empty($this->filters['paymentMethod_id'])) {
                $query->whereJsonContains('payments', [['type' => (int)$this->filters['paymentMethod_id']]]);
            }

            if (!empty($this->filters['issue_date'])) {
                $dates = explode(' a ', $this->filters['issue_date']);
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = count($dates) === 2 ? Carbon::parse($dates[1])->endOfDay() : Carbon::parse($dates[0])->endOfDay();
                $query->whereBetween('sale_date', [$startDate, $endDate]);
            }

            if (!empty($this->filters['search'])) {
                $searchTerm = $this->filters['search'];
                $query->whereHas('client', function (Builder $q) use ($searchTerm) {
                    $q->where('full_name', 'like', "%$searchTerm%")->orWhere('number', $searchTerm);
                });
            }

            $totalSales = $query->count();
            $processedCount = 0;

            $query->chunk(500, function ($sales) use (&$sheet, &$currentRow, &$processedCount, $totalSales, $excelExportJob) {
                foreach ($sales as $sale) {

                    // Limpieza de pagos
                    $paymentsArray = $sale->payments;
                    if (is_string($paymentsArray)) {
                        $paymentsArray = json_decode($paymentsArray, true);
                        if (is_string($paymentsArray)) {
                            $paymentsArray = json_decode($paymentsArray, true);
                        }
                    }
                    $paymentsArray = is_array($paymentsArray) ? $paymentsArray : [];

                    $paymentsStrings = [];
                    if (empty($paymentsArray)) {
                        $paymentsDetailString = "No se realizaron pagos";
                    } else {
                        foreach ($paymentsArray as $p) {
                            $desc = $this->getPaymentTypeDescription($p['type'] ?? null);
                            $amount = number_format($p['amount'] ?? 0, 2);
                            $ref = !empty($p['reference']) ? " (CÓDIGO: {$p['reference']})" : "";
                            $paymentsStrings[] = "• {$desc}: S/ {$amount}{$ref}";
                        }
                        $paymentsDetailString = implode("\n", $paymentsStrings);
                    }

                    // Preparar cursos con doble validación (title o description)
                    $courses = $sale->saleProduct->map(function($sp) {
                        // Decodificamos el JSON del producto
                        $data = json_decode($sp->saleProduct, true);

                        // Si data es null o no es array (por si el JSON está mal), devolvemos N/A
                        if (!is_array($data)) {
                            return 'N/A';
                        }

                        // Intentamos obtener 'title', si no existe 'description', si no 'N/A'
                        return $data['title'] ?? $data['description'] ?? 'N/A';
                    })->implode(', ');

                    // Formateo de Documento: SERIE-CORRELATIVO
                    $documentoFull = $sale->document
                        ? "{$sale->document->invoice_serie}-{$sale->document->invoice_correlative}"
                        : 'N/A';

                    $rowData = [
                        Carbon::parse($sale->sale_date)->format('d/m/Y'),
                        $sale->document->client_rzn_social ?? 'N/A',
                        $courses,
                        $sale->client->telephone ?? 'N/A',
                        $sale->client->full_name ?? 'N/A',
                        $documentoFull, // Columna F
                        ($sale->document->forma_pago ?? 'N/A'),
                        $sale->total,
                        $paymentsDetailString // Columna I
                    ];

                    $sheet->fromArray($rowData, NULL, 'A' . $currentRow);

                    // Salto de línea en la celda de detalle de pagos (Columna I)
                    $sheet->getStyle('I' . $currentRow)->getAlignment()->setWrapText(true);

                    $currentRow++;
                }

                $processedCount += $sales->count();
                $progress = ($totalSales > 0) ? floor(($processedCount / $totalSales) * 100) : 0;
                $excelExportJob->update(['progress' => $progress]);
            });

            // Ajuste automático de columnas de A hasta I
            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $fileName = 'REPORTE_VENTAS_' . now()->format('Ymd_His') . '.xlsx';
            $filePath = 'exports/' . $fileName;

            Storage::disk('public')->makeDirectory('exports');
            $writer = new Xlsx($spreadsheet);
            $writer->save(Storage::disk('public')->path($filePath));

            $excelExportJob->update([
                'status' => 'completed',
                'file_name' => $fileName,
                'download_url' => Storage::disk('public')->url($filePath),
                'progress' => 100,
            ]);

        } catch (\Throwable $e) {
            Log::error("Error en ExportSalesExcel: " . $e->getMessage());
            if ($excelExportJob) {
                $excelExportJob->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            }
        }
    }

    protected function getPaymentTypeDescription($idToFind): string
    {
        if (!$idToFind) return 'No se realizaron pagos';
        $method = $this->paymentMethods->firstWhere('id', $idToFind);
        return $method ? $method->description : "Tipo $idToFind";
    }
}
