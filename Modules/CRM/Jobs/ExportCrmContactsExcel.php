<?php

namespace Modules\CRM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Modules\Academic\Entities\AcaStudent;
use App\Models\ExcelExportJob;
use Illuminate\Support\Facades\DB;

class ExportCrmContactsExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filters;
    protected $excelExportJobId;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $filters, int $excelExportJobId, int $userId)
    {
        $this->filters = $filters;
        $this->excelExportJobId = $excelExportJobId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Iniciando ExportCrmContactsExcel para usuario {$this->userId} (Job ID: {$this->excelExportJobId})");

        $excelExportJob = ExcelExportJob::find($this->excelExportJobId);
        if (!$excelExportJob) {
            Log::error("ExcelExportJob con ID {$this->excelExportJobId} no encontrado.");
            return;
        }

        try {
            $excelExportJob->update(['status' => 'processing', 'progress' => 0]);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Contactos CRM');

            // CABECERAS
            $headers = [
                'País',
                'Ciudad',
                'Nombre completo',
                'Tipo documento',
                'Número documento',
                'Correo electrónico',
                'Teléfono',
                'Fecha nacimiento',
                'Fuente de llegada',
                'Descripción',
                'Curso gratuito',
                'Cursos de interés',
            ];
            $sheet->fromArray($headers, NULL, 'A1');

            // Estilos cabeceras
            $sheet->getStyle('A1:L1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2563EB'], // Azul Tailwind
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $currentRow = 2;

            // --- Construcción de la consulta base ---
            $query = AcaStudent::query()
                ->with([
                    'person.country',
                    'person.identityDocumentType',
                    'arrivalSource:id,name',
                    'coursesInterest.course',
                    'registrations' => function ($q) {
                        $q->whereNotNull('arrival_source_id')->with('course');
                    },
                ])
                ->whereNotNull('arrival_source_id')
                ->select([
                    'aca_students.*',
                    DB::raw("(
                        SELECT title FROM cms_landings WHERE menu_id = '01' LIMIT 1
                    ) AS arrival_source_text")
                ]);

            // Aplicar filtros si existen
            if (isset($this->filters['country_id']) && $this->filters['country_id']) {
                $query->whereHas('person', function ($q) {
                    $q->where('country_id', $this->filters['country_id']);
                });
            }

            if (isset($this->filters['search']) && $this->filters['search']) {
                $search = $this->filters['search'];
                $query->whereHas('person', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('number', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if (isset($this->filters['arrival_source']) && $this->filters['arrival_source']) {
                $query->where('arrival_source_id', $this->filters['arrival_source_id']);
            }

            // Contar total para el progreso
            $total = $query->count();
            $processed = 0;

            // Procesar en chunks
            $query->chunk(1000, function ($students) use (&$sheet, &$currentRow, &$processed, $total, $excelExportJob) {
                foreach ($students as $student) {
                    $person = $student->person;

                    $pais = $person->country->description ?? '—';
                    $ciudad = $person->ubigeo_description ?? '—';
                    $nombre = $person->full_name ?? '—';
                    $tipoDoc = $person->identityDocumentType->description ?? '—';
                    $numero = $person->number ?? '—';
                    $correo = $person->email ?? '—';
                    $telefono = $person->telephone ?? '—';
                    $fechaNac = $person->birthdate ?? '—';
                    $fuente = $student->arrivalSource->name ?? '—';
                    $descripcion = $student->arrival_source_text ?? '—';

                    // Cursos gratuitos (registrations)
                    $cursosGratis = $student->registrations->map(fn($r) => $r->course->description ?? '—')->implode(', ');
                    if ($cursosGratis === '') $cursosGratis = '—';

                    // Cursos de interés
                    $intereses = $student->coursesInterest->map(fn($ci) => $ci->course->description ?? '—')->implode(', ');
                    if ($intereses === '') $intereses = '—';

                    $row = [
                        $pais,
                        $ciudad,
                        $nombre,
                        $tipoDoc,
                        $numero,
                        $correo,
                        $telefono,
                        $fechaNac,
                        $fuente,
                        $descripcion,
                        $cursosGratis,
                        $intereses,
                    ];

                    $sheet->fromArray($row, NULL, 'A' . $currentRow);
                    $currentRow++;
                }

                $processed += count($students);
                $progress = $total > 0 ? floor(($processed / $total) * 100) : 100;
                $excelExportJob->update(['progress' => $progress]);
            });

            // Autoajustar columnas
            foreach (range('A', $sheet->getHighestColumn()) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Guardar Excel
            $fileName = 'CONTACTOS_CRM_' . Carbon::now()->format('Ymd_His') . '.xlsx';
            $filePath = 'exports/' . $fileName;

            Storage::disk('public')->makeDirectory('exports');
            $writer = new Xlsx($spreadsheet);
            $writer->save(Storage::disk('public')->path($filePath));

            $downloadUrl = Storage::disk('public')->url($filePath);

            // Actualizar estado final
            $excelExportJob->update([
                'status' => 'completed',
                'file_name' => $fileName,
                'file_path' => $filePath,
                'download_url' => $downloadUrl,
                'progress' => 100,
            ]);

            Log::info("ExportCrmContactsExcel Job {$this->excelExportJobId} completado correctamente.");

        } catch (\Throwable $e) {
            Log::error("Error en ExportCrmContactsExcel Job ID {$this->excelExportJobId}: " . $e->getMessage());
            if ($excelExportJob) {
                $excelExportJob->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ExportCrmContactsExcel Job ID {$this->excelExportJobId} falló: " . $exception->getMessage());
        $excelExportJob = ExcelExportJob::find($this->excelExportJobId);
        if ($excelExportJob) {
            $excelExportJob->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
