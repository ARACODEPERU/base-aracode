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
            'Origen',
            'UTM Source',
            'UTM Medium',
            'UTM Campaign',
            'UTM Term',
            'UTM Content',
            'UTM ID',
            'FBCLID',
            'GCLID',
            'Referer',
            'Landing URL',
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
                    $this->getOrigenLabel($subscriber),
                    $subscriber->utm_source,
                    $subscriber->utm_medium,
                    $subscriber->utm_campaign,
                    $subscriber->utm_term,
                    $subscriber->utm_content,
                    $subscriber->utm_id,
                    $subscriber->fbclid,
                    $subscriber->gclid,
                    $subscriber->referer,
                    $subscriber->landing_url,
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

    /**
     * Devuelve la etiqueta de origen legible (misma lógica que la columna Origen del listado).
     */
    private function getOrigenLabel($subscriber): string
    {
        $map = [
            'facebook_ads' => 'Facebook Ads',
            'google_ads'   => 'Google Ads',
            'cpc'          => 'CPC',
            'social'       => 'Social',
            'organic'      => 'Orgánico',
            'email'        => 'Email',
            'direct'       => 'Orgánico',
        ];

        $source = $subscriber->traffic_source;

        if ($source === 'referrer') {
            $host = parse_url((string) $subscriber->referer, PHP_URL_HOST);
            if ($host) {
                return (string) preg_replace('/^www\./', '', $host);
            }
            return 'Otra página';
        }

        return $map[$source] ?? ($source ?: 'Orgánico');
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
