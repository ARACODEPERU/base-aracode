<?php

namespace Modules\Sales\Jobs;

use App\Models\ExcelExportJob;
use App\Models\LocalSale;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Sales\Services\InventoryKardexQueryService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportInventoryKardexExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $filters;

    protected int $excelExportJobId;

    protected int $userId;

    public function __construct(array $filters, int $excelExportJobId, int $userId)
    {
        $this->filters = $filters;
        $this->excelExportJobId = $excelExportJobId;
        $this->userId = $userId;
    }

    public function handle(InventoryKardexQueryService $queryService): void
    {
        $excelExportJob = ExcelExportJob::find($this->excelExportJobId);

        if (! $excelExportJob) {
            Log::error("ExcelExportJob con ID {$this->excelExportJobId} no encontrado.");

            return;
        }

        try {
            $excelExportJob->update(['status' => 'processing', 'progress' => 0]);

            $filters = $queryService->normalizeFilters($this->filters);
            $totalRows = $queryService->count($filters);

            $this->updateProgress($excelExportJob, $filters, 5, 0, $totalRows);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Kardex inventario');

            $sheet->setCellValue('A1', 'Reporte de Kardex de Inventario');
            $sheet->mergeCells('A1:H1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $filterSummary = $this->buildFilterSummary($filters);
            $sheet->setCellValue('A2', $filterSummary);
            $sheet->mergeCells('A2:H2');
            $sheet->getStyle('A2')->getFont()->setItalic(true);

            $this->updateProgress($excelExportJob, $filters, 8, 0, $totalRows);

            $headers = ['Fecha', 'Local', 'Código', 'Producto', 'Talla', 'Tipo', 'Cantidad', 'Descripción'];
            $headerRow = 4;
            $sheet->fromArray($headers, null, 'A'.$headerRow);

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF336699'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
            $sheet->getStyle('A'.$headerRow.':H'.$headerRow)->applyFromArray($headerStyle);

            $currentRow = $headerRow + 1;
            $processed = 0;
            $lastProgressUpdate = 8;

            if ($totalRows === 0) {
                $sheet->setCellValue('A'.$currentRow, 'Sin movimientos con los filtros seleccionados.');
                $sheet->mergeCells('A'.$currentRow.':H'.$currentRow);
            } else {
                foreach ($queryService->cursor($filters) as $row) {
                    $mapped = $queryService->mapRow($row);

                    $sheet->fromArray([
                        $mapped['date_of_issue'],
                        $mapped['local_name'],
                        $mapped['interne'],
                        $mapped['product_description'],
                        $mapped['size'] ?? '—',
                        $mapped['motion_label'],
                        $mapped['quantity'],
                        $mapped['description'] ?? '—',
                    ], null, 'A'.$currentRow);

                    $currentRow++;
                    $processed++;

                    if ($processed % 25 === 0 || $processed === $totalRows) {
                        $progress = 8 + (int) floor(($processed / $totalRows) * 84);
                        if ($progress > $lastProgressUpdate) {
                            $this->updateProgress($excelExportJob, $filters, $progress, $processed, $totalRows);
                            $lastProgressUpdate = $progress;
                        }
                    }
                }
            }

            foreach (range('A', 'H') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $this->updateProgress($excelExportJob, $filters, 95, $processed, $totalRows);

            $fileName = 'kardex_inventario_'.date('Ymd_His').'.xlsx';
            $filePath = 'exports/'.$fileName;

            Storage::disk('public')->makeDirectory('exports');
            $writer = new Xlsx($spreadsheet);
            $writer->save(Storage::disk('public')->path($filePath));

            $downloadUrl = Storage::disk('public')->url($filePath);

            $excelExportJob->update([
                'status' => 'completed',
                'progress' => 100,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'download_url' => $downloadUrl,
                'filters' => array_merge($filters, [
                    '_export_meta' => [
                        'processed_rows' => $processed,
                        'total_rows' => $totalRows,
                    ],
                ]),
            ]);

            Log::info("Kardex Excel export completed for user {$this->userId}. File: {$fileName}");
        } catch (\Throwable $e) {
            Log::error("Kardex Excel export failed for user {$this->userId}, Job ID {$this->excelExportJobId}: ".$e->getMessage());

            $excelExportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'progress' => 0,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $excelExportJob = ExcelExportJob::find($this->excelExportJobId);

        if ($excelExportJob) {
            $excelExportJob->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'progress' => 0,
            ]);
        }

        Log::error("Kardex Excel export failed for user {$this->userId}, Job ID {$this->excelExportJobId}: ".$exception->getMessage());
    }

    protected function updateProgress(ExcelExportJob $job, array $filters, int $progress, int $processed, int $total): void
    {
        $job->update([
            'progress' => $progress,
            'filters' => array_merge($filters, [
                '_export_meta' => [
                    'processed_rows' => $processed,
                    'total_rows' => $total,
                ],
            ]),
        ]);
    }

    protected function buildFilterSummary(array $filters): string
    {
        $parts = [];

        if ($filters['local_id'] > 0) {
            $local = LocalSale::find($filters['local_id']);
            $parts[] = 'Local: '.($local->description ?? $filters['local_id']);
        } else {
            $parts[] = 'Local: Todos';
        }

        if ($filters['product_id'] > 0) {
            $product = Product::find($filters['product_id']);
            $parts[] = 'Producto: '.($product ? "{$product->interne} — {$product->description}" : $filters['product_id']);
        } else {
            $parts[] = 'Producto: Todos';
        }

        if ($filters['size'] !== '') {
            $parts[] = 'Talla: '.$filters['size'];
        }

        if ($filters['date_from']) {
            $parts[] = 'Desde: '.$filters['date_from'];
        }

        if ($filters['date_to']) {
            $parts[] = 'Hasta: '.$filters['date_to'];
        }

        return implode(' | ', $parts);
    }
}
