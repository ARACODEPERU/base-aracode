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
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaCertificate;
use Modules\Academic\Entities\AcaStudentGrade;
use Modules\Academic\Entities\AcaStudentGradeDetail;
use Modules\Academic\Entities\AcaStudentExam;
use Modules\Academic\Entities\AcaStudentAttendance;
use Modules\Academic\Entities\AcaStudentParticipation;

class ExportStudentPerformanceExcel implements ShouldQueue
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
            $courseId = $this->filters['course_id'] ?? null;
            $year = $this->filters['year'] ?? null;
            $month = $this->filters['month'] ?? null;

            // Obtener el curso (siempre debe haber un courseId seleccionado)
            $course = AcaCourse::with(['modules'])->findOrFail($courseId);
            $modules = $course->modules->map(function ($module) {
                return [
                    'id' => $module->id,
                    'description' => $module->description,
                ];
            });

            $query = AcaCapRegistration::with(['student.person', 'course'])
                ->select('aca_cap_registrations.*')
                ->where('status', true)
                ->where('course_id', $courseId);

            if ($year) {
                $query->whereYear('created_at', $year);
            }
            if ($month) {
                $query->whereMonth('created_at', $month);
            }

            $registrations = $query->orderBy('created_at', 'desc')->get();

            // Preparar datos de estudiantes
            $students = $registrations->map(function ($reg) use ($modules, $courseId) {
                $savedGrade = AcaStudentGrade::where('registration_id', $reg->id)->first();

                if ($savedGrade) {
                    $savedDetails = AcaStudentGradeDetail::where('grade_id', $savedGrade->id)->get();

                    $studentModules = $modules->map(function ($module) use ($savedDetails) {
                        $detail = $savedDetails->firstWhere('module_id', $module['id']);

                        return [
                            'module_id' => $module['id'],
                            'module_name' => $module['description'],
                            'participation_score' => $detail ? $detail->participation_score : null,
                            'attendance_score' => $detail ? $detail->attendance_score : null,
                            'exam_score' => $detail ? $detail->exam_score : null,
                            'average' => $detail ? $detail->average : null,
                        ];
                    })->toArray();

                    $finalAverage = $savedGrade->final_average;
                } else {
                    $studentModules = $modules->map(function ($module) use ($courseId, $reg) {
                        $studentExams = AcaStudentExam::whereHas('exam', function($query) use ($courseId, $module) {
                            $query->where('course_id', $courseId);
                            if ($module) {
                                $query->where('module_id', $module['id']);
                            }
                        })->where('student_id', $reg->student->id)->get();

                        $exam = $studentExams->first();
                        $examScore = $exam ? (float) $exam->punctuation : null;

                        $attendances = AcaStudentAttendance::where('course_id', $courseId)
                            ->where('student_id', $reg->student->id)
                            ->where('module_id', $module['id'] ?? null)
                            ->get();
                        $attendanceCount = $attendances->count();
                        $totalContents = AcaStudentAttendance::where('course_id', $courseId)
                            ->whereNotNull('module_id')
                            ->select('module_id')
                            ->selectRaw('COUNT(DISTINCT content_id) as total_contents')
                            ->groupBy('module_id')
                            ->get()
                            ->keyBy('module_id')
                            ->get($module['id'])?->total_contents ?? 0;
                        $attendanceScore = $totalContents > 0 ? round(($attendanceCount / $totalContents) * 12, 2) : null;

                        $participations = AcaStudentParticipation::where('course_id', $courseId)
                            ->where('student_id', $reg->student->id)
                            ->where('module_id', $module['id'] ?? null)
                            ->get();
                        $participationCount = $participations->count();
                        $participationScore = $participationCount > 0
                            ? round($participations->sum('participation_score') / $participationCount, 2)
                            : null;

                        $examVal = $examScore ?? 0;
                        $attendanceVal = $attendanceScore ?? 0;
                        $participationVal = $participationScore ?? 0;
                        $average = round(($examVal * 0.6) + ($attendanceVal * 0.2) + ($participationVal * 0.2), 2);

                        return [
                            'module_id' => $module['id'],
                            'module_name' => $module['description'],
                            'participation_score' => $participationScore,
                            'attendance_score' => $attendanceScore,
                            'exam_score' => $examScore,
                            'average' => $average,
                        ];
                    })->toArray();

                    $finalAverage = collect($studentModules)->avg('average');
                    $finalAverage = $finalAverage !== null ? round($finalAverage, 2) : null;
                }

                return [
                    'id' => $reg->student->id,
                    'full_name' => $reg->student->person ? $reg->student->person->full_name : 'Sin nombre',
                    'dni' => $reg->student->person ? $reg->student->person->number : '',
                    'course_name' => $reg->course->description ?? '',
                    'final_average' => $finalAverage,
                    'modules' => $studentModules,
                ];
            });

            // Crear Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Desempeño');

            // Función helper para convertir columna y fila a referencia de celda
            $getCell = function($col, $row) {
                return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
            };

            // Estilos
            $headerStyle = [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];

            $subHeaderStyle = [
                'font' => ['bold' => true, 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];

            $approvedStyle = [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']], // blue-100
                'font' => ['bold' => true, 'color' => ['rgb' => '1E40AF']], // blue-800
            ];

            $disapprovedStyle = [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']], // red-100
                'font' => ['bold' => true, 'color' => ['rgb' => '991B1B']], // red-800
            ];

            // Construir headers
            $col = 1;
            $sheet->setCellValue($getCell($col, 1), '#');
            $col++;

            // Estudiantes (colspan=2)
            $sheet->setCellValue($getCell($col, 1), 'Alumnos');
            $sheet->mergeCells($getCell($col, 1) . ':' . $getCell($col + 1, 1));
            $col += 2;

            // Módulos
            $moduleStartCol = $col;
            foreach ($modules as $module) {
                $sheet->setCellValue($getCell($col, 1), $module['description']);
                $sheet->mergeCells($getCell($col, 1) . ':' . $getCell($col + 3, 1));
                $col += 4;
            }

            // Promedio Final
            $sheet->setCellValue($getCell($col, 1), 'Promedio Final');
            $col++;

            // Fila 2 - Subheaders
            $col = 1;
            // # - dejar vacío para efecto rowspan=2
            $sheet->setCellValue($getCell($col, 2), '');
            $col++;
            
            $sheet->setCellValue($getCell($col, 2), 'Nombres y Apellidos');
            $col++;
            $sheet->setCellValue($getCell($col, 2), 'DNI');
            $col++;

            foreach ($modules as $module) {
                $sheet->setCellValue($getCell($col, 2), 'P');
                $col++;
                $sheet->setCellValue($getCell($col, 2), 'A');
                $col++;
                $sheet->setCellValue($getCell($col, 2), 'E');
                $col++;
                $sheet->setCellValue($getCell($col, 2), 'PROM');
                $col++;
            }

            // Promedio Final - dejar vacío para efecto rowspan=2
            $sheet->setCellValue($getCell($col, 2), '');

            // Aplicar estilos a headers
            $highestColumn = $sheet->getHighestColumn();
            $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray($headerStyle);
            $sheet->getStyle('A2:' . $highestColumn . '2')->applyFromArray($subHeaderStyle);

            // Datos
            $row = 3;
            foreach ($students as $key => $student) {
                $col = 1;

                // #
                $sheet->setCellValue($getCell($col, $row), $key + 1);
                $col++;

                // Nombre
                $sheet->setCellValue($getCell($col, $row), $student['full_name']);
                $col++;

                // DNI
                $sheet->setCellValue($getCell($col, $row), $student['dni']);
                $col++;

                // Módulos
                foreach ($student['modules'] as $module) {
                    // P
                    $sheet->setCellValue($getCell($col, $row), $module['participation_score'] ?? '-');
                    $col++;
                    // A
                    $sheet->setCellValue($getCell($col, $row), $module['attendance_score'] ?? '-');
                    $col++;
                    // E
                    $sheet->setCellValue($getCell($col, $row), $module['exam_score'] ?? '-');
                    $col++;
                    // Prom
                    $promCell = $sheet->getCell($getCell($col, $row));
                    $promValue = $module['average'] ?? '-';
                    $promCell->setValue($promValue);
                    if ($promValue !== '-' && $promValue >= 11) {
                        $promCell->getStyle()->applyFromArray($approvedStyle);
                    } elseif ($promValue !== '-') {
                        $promCell->getStyle()->applyFromArray($disapprovedStyle);
                    }
                    $col++;
                }

                // Promedio Final
                $finalCell = $sheet->getCell($getCell($col, $row));
                $finalValue = $student['final_average'] ?? '-';
                $finalCell->setValue($finalValue);
                if ($finalValue !== '-' && $finalValue >= 11) {
                    $finalCell->getStyle()->applyFromArray($approvedStyle);
                } elseif ($finalValue !== '-') {
                    $finalCell->getStyle()->applyFromArray($disapprovedStyle);
                }

                $row++;

                // Actualizar progreso
                $progress = round(($key + 1) / count($students) * 100);
                $excelExportJob->update(['progress' => $progress]);
            }

            // Ajustar ancho de columnas
            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(12);

            $moduleCount = count($modules);
            $moduleCols = ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
            for ($i = 0; $i < $moduleCount && $i < 23; $i++) {
                $colLetter = $moduleCols[$i] ?? ('D' . $i);
                $sheet->getColumnDimension($colLetter)->setWidth(8);
            }

            $lastCol = chr(65 + 2 + $moduleCount * 4);
            $sheet->getColumnDimension($lastCol)->setWidth(15);

            // Guardar archivo
            $fileName = 'reporte_desempeno_' . date('Ymd_His') . '.xlsx';
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
            Log::error("Error en ExportStudentPerformanceExcel: " . $e->getMessage());
            $excelExportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
