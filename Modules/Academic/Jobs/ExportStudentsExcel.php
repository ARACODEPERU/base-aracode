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
use Illuminate\Support\Facades\Log;
use Modules\Academic\Entities\AcaExcelStudentsExportJob;

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
        dd('aca llega');
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
            'ID',
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
            'Es Proveedor',
            'Es Cliente',
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
            'Redes Sociales',
            'Descripción Ubigeo',
            'ID Empresa/Persona',
            'ID Industria',
            'Industria',
            'Profesión',
            'Empresa',
            'ID Profesión',
            'ID Ocupación'
        ];

        $sheet->fromArray($headers, NULL, 'A1');

        $chunkSize = 1000;
        // Asumiendo que tu modelo para la tabla `people` se llama `Person`
        $totalRecords = Person::count();
        $currentRow = 2;

        Person::chunkById($chunkSize, function ($people) use (&$sheet, &$currentRow) {
            foreach ($people as $person) {
                // 2. Mapear los datos de cada fila del modelo a las columnas del Excel
                $rowData = [
                    $person->id,
                    $person->document_type_id,
                    $person->short_name,
                    $person->full_name,
                    $person->description,
                    $person->number,
                    $person->telephone,
                    $person->email,
                    $person->image,
                    $person->address,
                    $person->contact_telephone,
                    $person->contact_name,
                    $person->contact_email,
                    $person->is_provider ? 'Sí' : 'No', // Convertir booleano a texto legible
                    $person->is_client ? 'Sí' : 'No',   // Convertir booleano a texto legible
                    $person->ubigeo,
                    $person->created_at ? $person->created_at->format('Y-m-d H:i:s') : '', // Formatear fechas
                    $person->updated_at ? $person->updated_at->format('Y-m-d H:i:s') : '',
                    $person->birthdate ? $person->birthdate->format('Y-m-d') : '',
                    $person->NAMES,
                    $person->father_lastname,
                    $person->mother_lastname,
                    $person->ocupacion,
                    $person->presentacion,
                    $person->gender,
                    $person->STATUS,
                    $person->social_networks,
                    $person->ubigeo_description,
                    $person->company_person_id,
                    $person->industry_id,
                    $person->industry,
                    $person->profession,
                    $person->company,
                    $person->profession_id,
                    $person->occupation_id
                ];
                $sheet->fromArray($rowData, NULL, 'A' . $currentRow);
                $currentRow++;
            }
        });

        $fileName = 'personas_'. Str::random(10) . '.xlsx'; // Cambiamos el nombre del archivo
        $filePath = 'exports/' . $fileName;
        dd($filePath );
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
