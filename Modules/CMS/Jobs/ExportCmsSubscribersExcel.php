<?php

namespace Modules\CMS\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\ExcelExportJob;
use Modules\CMS\Entities\CmsSubscriber;
use Illuminate\Support\Facades\DB;

class ExportCmsSubscribersExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;
    public $jobId;
    public $dates;
    public $search;

    public function __construct(int $userId, int $jobId, ?string $dates, ?string $search)
    {
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->dates = $dates;
        $this->search = $search;
    }

    public function handle()
    {

        $excelExportJob = ExcelExportJob::find($this->jobId);
        if (!$excelExportJob) {
            Log::error("ExcelExportJob ID {$this->jobId} not found for user {$this->userId}. Aborting export.");
            return;
        }
        $excelExportJob->update(['status' => 'processing', 'progress' => 0]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Suscriptores');

        // Definir las CABECERAS del Excel
        $headers = [
            'Nombre completo',
            'Email',
            'Teléfono',
            'Asunto',
            'Mensaje',
        ];

        $sheet->fromArray($headers, NULL, 'A1');

        $chunkSize = 1000;
        $query = CmsSubscriber::query();

        // Aplicar filtros
        if ($this->search) {
            $query->where('email', 'like', '%' . $this->search . '%');
        }

        if ($this->dates) {
            if (str_contains($this->dates, ' to ') || str_contains($this->dates, ' a ')) {
                $separator = str_contains($this->dates, ' to ') ? ' to ' : ' a ';
                [$startDate, $endDate] = explode($separator, $this->dates);
                $query->whereDate('created_at', '>=', $startDate)
                      ->whereDate('created_at', '<=', $endDate);
            } else {
                $query->whereDate('created_at', $this->dates);
            }
        }

        $totalRecords = $query->count();
        $currentRow = 2;

        $query->chunk($chunkSize, function ($subscribers) use (&$sheet, &$currentRow) {
            foreach ($subscribers as $subscriber) {
                $rowData = [
                    $subscriber->full_name,
                    $subscriber->email,
                    $subscriber->phone,
                    $subscriber->subject,
                    $subscriber->message,
                ];
                $sheet->fromArray($rowData, NULL, 'A' . $currentRow);
                $currentRow++;
            }
        });

        $fileName = 'SUSCRIPTORES_' . Carbon::now()->format('d-m-Y') . '.xlsx';
        $filePath = 'exports/' . $fileName;

        $writer = new Xlsx($spreadsheet);
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
        $excelExportJob = ExcelExportJob::find($this->jobId);
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
