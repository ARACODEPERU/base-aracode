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
use Illuminate\Support\Str;

use App\Models\Person; // <-- ASUMIENDO que tu modelo se llama 'Person'
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Entities\AcaExcelStudentsExportJob;
use Modules\Academic\Entities\AcaPerson;
use Modules\Academic\Entities\AcaStudent;

class ExportStudentsExcel implements ShouldQueue
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

        $excelExportJob = AcaExcelStudentsExportJob::find($this->jobId);
        if (!$excelExportJob) {
            Log::error("ExcelExportJob ID {$this->jobId} not found for user {$this->userId}. Aborting export.");
            return;
        }
        $excelExportJob->update(['status' => 'processing', 'progress' => 0]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Lista de Personas'); // Cambiamos el título a 'Lista de Personas' o 'Alumnos'

        // 1. Definir las CABECERAS del Excel usando los nombres de las columnas SQL
        // Ajusta los nombres si quieres que sean más amigables en el Excel
        $headers = [
            'Tipo Documento ID',
            'Nombre Corto',
            'Nombre Completo',
            'Descripción',
            'Número Documento',
            'Teléfono',
            'Email',
            'Imagen',
            'Dirección',
            'Teléfono Contacto',
            'Nombre Contacto',
            'Email Contacto',
            'Ubigeo',
            'Fecha Creación',
            'Fecha Actualización',
            'Fecha Nacimiento',
            'Nombres', // Corresponde a 'NAMES'
            'Apellido Paterno', // Corresponde a 'father_lastname'
            'Apellido Materno', // Corresponde a 'mother_lastname'
            'Ocupación', // Corresponde a 'ocupacion'
            'Presentación', // Corresponde a 'presentacion'
            'Género', // Corresponde a 'gender'
            'Estado', // Corresponde a 'STATUS'
            'Descripción Ubigeo',
            'Industria',
            'Profesión',
            'Empresa'
        ];

        $sheet->fromArray($headers, NULL, 'A1');

        $chunkSize = 1000;
        // Asumiendo que tu modelo para la tabla `people` se llama `Person`
        $totalRecords = AcaStudent::count();
        $currentRow = 2;

        AcaStudent::with('person')->chunkById($chunkSize, function ($people) use (&$sheet, &$currentRow) {
            foreach ($people as $student) {
                // 2. Mapear los datos de cada fila del modelo a las columnas del Excel
                $rowData = [
                    $student->person->document_type_id,
                    $student->person->short_name,
                    $student->person->full_name,
                    $student->person->description,
                    $student->person->number,
                    $student->person->telephone,
                    $student->person->email,
                    $student->person->image,
                    $student->person->address,
                    $student->person->contact_telephone,
                    $student->person->contact_name,
                    $student->person->contact_email,
                    $student->person->ubigeo,
                    $student->person->created_at ? Carbon::parse($student->person->created_at)->format('Y-m-d H:i:s') : '', // Formatear fechas
                    $student->person->updated_at ? Carbon::parse($student->person->updated_at)->format('Y-m-d H:i:s') : '',
                    $student->person->birthdate ? Carbon::parse($student->person->birthdate)->format('Y-m-d') : '',
                    $student->person->names,
                    $student->person->father_lastname,
                    $student->person->mother_lastname,
                    $student->person->ocupacion,
                    $student->person->presentacion,
                    $student->person->gender,
                    $student->person->status,
                    $student->person->ubigeo_description,
                    $student->person->industry,
                    $student->person->profession,
                    $student->person->company,
                ];
                $sheet->fromArray($rowData, NULL, 'A' . $currentRow);
                $currentRow++;
            }
        });

        $fileName = 'ESTUDIANTES_'. Carbon::now()->format('d-m-Y') . '.xlsx'; // Cambiamos el nombre del archivo
        $filePath = 'exports/' . $fileName;

        $writer = new Xlsx($spreadsheet);
        // Para que el archivo sea accesible públicamente para descarga, guárdalo en el disco 'public'
        Storage::disk('public')->put($filePath, '');
        $writer->save(Storage::disk('public')->path($filePath));

        $excelExportJob->update([
            'status' => 'completed',
            'progress' => 100,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'download_url' => Storage::disk('public')->url($filePath),
        ]);

        Log::info("Excel export completed for user {$this->userId}. File: {$fileName}");
    }

    public function failed(\Throwable $exception)
    {
        $excelExportJob = AcaExcelStudentsExportJob::find($this->jobId);
        if ($excelExportJob) {
            $excelExportJob->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'progress' => 0,
            ]);
        }
        Log::error("Excel export failed for user {$this->userId}, Job ID {$this->jobId}: " . $exception->getMessage());
    }
}
