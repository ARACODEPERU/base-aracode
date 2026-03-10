<?php

namespace Modules\Academic\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use App\Models\ExcelExportJob;

class ExportEnrollmentDocumentsExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;
    public $jobId;
    public $filters;

    public function __construct(int $userId, int $jobId, array $filters = [])
    {
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->filters = $filters;
    }

    public function handle()
    {
        $excelExportJob = ExcelExportJob::find($this->jobId);

        if (!$excelExportJob) {
            Log::error("ExcelExportJob ID {$this->jobId} not found for user {$this->userId}. Aborting export.");
            return;
        }

        $excelExportJob->update(['status' => 'processing', 'progress' => 0]);

        try {
            $userId = $this->filters['user_id'] ?? null;
            $dateStart = $this->filters['date_start'] ?? null;
            $dateEnd = $this->filters['date_end'] ?? null;

            $query = \Modules\Academic\Entities\AcaCapRegistration::with([
                'student.person',
                'course',
                'document'
            ])->where('status', true);

            // Filtrar por usuario responsable
            if ($userId === 'sin') {
                $query->whereNull('user_id_registers');
            } elseif ($userId === 'con') {
                $query->whereNotNull('user_id_registers');
            } elseif ($userId) {
                $query->where('user_id_registers', $userId);
            }

            // Filtrar por rango de fechas
            if ($dateStart) {
                $query->whereDate('created_at', '>=', $dateStart);
            }
            if ($dateEnd) {
                $query->whereDate('created_at', '<=', $dateEnd);
            }

            $registrations = $query->orderBy('created_at', 'desc')->get();

            // Crear Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Documentos de Ventas');

            // Estilos
            $headerStyle = [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];

            // Headers
            $sheet->setCellValue('A1', '#');
            $sheet->setCellValue('B1', 'Nombres y Apellidos');
            $sheet->setCellValue('C1', 'Fecha de Matrícula');
            $sheet->setCellValue('D1', 'Curso');
            $sheet->setCellValue('E1', 'Comprobante de Pago');
            $sheet->setCellValue('F1', 'Responsable');

            $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

            // Ancho de columnas
            $sheet->getColumnDimension('A')->setWidth(8);
            $sheet->getColumnDimension('B')->setWidth(30);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(30);
            $sheet->getColumnDimension('E')->setWidth(20);
            $sheet->getColumnDimension('F')->setWidth(25);

            // Datos
            $row = 2;
            foreach ($registrations as $key => $reg) {
                $sheet->setCellValue('A' . $row, $key + 1);
                $sheet->setCellValue('B' . $row, $reg->student?->person?->full_name ?? 'Sin nombre');
                $sheet->setCellValue('C' . $row, $reg->created_at->format('Y-m-d H:i:s'));
                $sheet->setCellValue('D' . $row, $reg->course?->description ?? 'Sin curso');

                $comprobante = $reg->saleDocument
                    ? $reg->saleDocument->invoice_serie . '-' . $reg->saleDocument->invoice_correlative
                    : '-';
                $sheet->setCellValue('E' . $row, $comprobante);

                $responsible = $reg->user_id_registers
                    ? (\App\Models\User::find($reg->user_id_registers)?->name ?? 'Usuario no encontrado')
                    : 'Sin responsable';
                $sheet->setCellValue('F' . $row, $responsible);

                $row++;

                // Actualizar progreso
                $progress = round(($key + 1) / count($registrations) * 100);
                $excelExportJob->update(['progress' => $progress]);
            }

            // Guardar archivo
            $fileName = 'reporte_documentos_ventas_' . date('Ymd_His') . '.xlsx';
            $filePath = 'exports/' . $fileName;

            $writer = new Xlsx($spreadsheet);
            Storage::disk('public')->put($filePath, '');
            $writer->save(Storage::disk('public')->path($filePath));

            $excelExportJob->update([
                'status' => 'completed',
                'progress' => 100,
                'file_name' => $fileName,
                'download_url' => Storage::disk('public')->url($filePath),
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error en ExportEnrollmentDocumentsExcel: " . $e->getMessage());
            $excelExportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
