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
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use App\Models\ExcelExportJob;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaStudentAttendance;
use Modules\Academic\Entities\AcaContent;

class ExportAttendanceExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;
    public $jobId;

    public function __construct(int $userId, int $jobId)
    {
        $this->userId = $userId;
        $this->jobId = $jobId;
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
            $filters = $excelExportJob->filters;
            $contentId = $filters['content_id'] ?? null;

            if (!$contentId) {
                throw new \Exception('Content ID no proporcionado en los filtros.');
            }

            $content = AcaContent::with('theme.module.course')->find($contentId);

            if (!$content) {
                throw new \Exception('Contenido no encontrado.');
            }

            $courseId = $content->theme->module->course_id;

            $registrations = AcaCapRegistration::with(['student.person'])
                ->where('course_id', $courseId)
                ->get();

            $attendances = AcaStudentAttendance::with('userEdit')
                ->where('content_id', $contentId)
                ->get()
                ->keyBy('student_id');

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Asistencia');

            // Título
            $sheet->mergeCells('A1:G1');
            $sheet->setCellValue('A1', 'REPORTE DE ASISTENCIA');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            // Info del curso y clase
            $sheet->setCellValue('A3', 'Curso:');
            $sheet->setCellValue('B3', $content->theme->module->course->description);
            $sheet->setCellValue('A4', 'Clase:');
            $sheet->setCellValue('B4', $content->description);
            $sheet->setCellValue('A5', 'Fecha de exportación:');
            $sheet->setCellValue('B5', Carbon::now()->format('d/m/Y H:i:s'));

            $sheet->getStyle('A3:A5')->getFont()->setBold(true);

            // Headers
            $headers = ['N°', 'DNI', 'Nombre del Estudiante', 'Asistencia', 'Fecha Registro', 'Observación', 'Modificado Por'];
            $sheet->fromArray($headers, NULL, 'A7');

            // Estilo para headers
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4B5563']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            $sheet->getStyle('A7:G7')->applyFromArray($headerStyle);

            // Data
            $currentRow = 8;
            foreach ($registrations as $index => $reg) {
                $attendance = $attendances->get($reg->student_id);

                $rowData = [
                    $index + 1,
                    $reg->student->person->number,
                    $reg->student->person->formatted_name ?? $reg->student->person->full_name,
                    $attendance ? 'Presente' : 'Ausente',
                    $attendance?->registered_at?->format('d/m/Y H:i:s') ?? '-',
                    $attendance?->observation ?? '-',
                    $attendance?->userEdit?->name ?? '-'
                ];

                $sheet->fromArray($rowData, NULL, 'A' . $currentRow);

                // Color de fila según asistencia
                if ($attendance) {
                    $sheet->getStyle('D' . $currentRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('D1FAE5'); // Verde claro
                } else {
                    $sheet->getStyle('D' . $currentRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FEE2E2'); // Rojo claro
                }

                $currentRow++;
            }

            // Resumen al final
            $currentRow++;
            $sheet->setCellValue('A' . $currentRow, 'RESUMEN:');
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
            $currentRow++;
            $sheet->setCellValue('A' . $currentRow, 'Total estudiantes:');
            $sheet->setCellValue('B' . $currentRow, $registrations->count());
            $currentRow++;
            $sheet->setCellValue('A' . $currentRow, 'Presentes:');
            $sheet->setCellValue('B' . $currentRow, $attendances->count());
            $sheet->getStyle('B' . $currentRow)->getFont()->setBold(true)->getColor()->setRGB('059669');
            $currentRow++;
            $sheet->setCellValue('A' . $currentRow, 'Ausentes:');
            $sheet->setCellValue('B' . $currentRow, $registrations->count() - $attendances->count());
            $sheet->getStyle('B' . $currentRow)->getFont()->setBold(true)->getColor()->setRGB('DC2626');

            // Ajustar anchos de columna
            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(40);
            $sheet->getColumnDimension('D')->setWidth(12);
            $sheet->getColumnDimension('E')->setWidth(20);
            $sheet->getColumnDimension('F')->setWidth(30);
            $sheet->getColumnDimension('G')->setWidth(20);

            // Guardar archivo
            $fileName = 'ASISTENCIA_' . \Illuminate\Support\Str::slug($content->description) . '_' . Carbon::now()->format('d-m-Y_His') . '.xlsx';
            $filePath = 'exports/' . $fileName;

            Storage::disk('public')->put($filePath, '');
            $writer = new Xlsx($spreadsheet);
            $writer->save(Storage::disk('public')->path($filePath));

            $excelExportJob->update([
                'status' => 'completed',
                'progress' => 100,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'download_url' => Storage::disk('public')->url($filePath),
            ]);

            Log::info("Attendance Excel export completed for user {$this->userId}. File: {$fileName}");

        } catch (\Exception $e) {
            $excelExportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'progress' => 0,
            ]);
            Log::error("Attendance Excel export failed for user {$this->userId}, Job ID {$this->jobId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        $excelExportJob = ExcelExportJob::find($this->jobId);

        if ($excelExportJob) {
            $excelExportJob->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'progress' => 0,
            ]);
        }

        Log::error("Attendance Excel export failed for user {$this->userId}, Job ID {$this->jobId}: " . $exception->getMessage());
    }
}
