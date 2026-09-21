<?php

namespace Modules\Academic\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Emails\StudentElectronicTicket;
use Modules\Onlineshop\Entities\OnliSale;

class SendStudentElectronicTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array<string, mixed> */
    public array $data;
    public ?int $onliSaleId;
    public int $tries = 3;
    public array $backoff = [60, 300];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data, ?int $onliSaleId = null)
    {
        $this->data = $data;
        $this->onliSaleId = $onliSaleId;
    }

    public function handle(): void
    {
        $recipient = trim((string) ($this->data['for_mail'] ?? ''));

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El destinatario del comprobante no es válido.');
        }

        Mail::to($recipient)->send(new StudentElectronicTicket($this->data));

        if ($this->onliSaleId) {
            OnliSale::whereKey($this->onliSaleId)->update(['email_sent' => true]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        if ($this->onliSaleId) {
            OnliSale::whereKey($this->onliSaleId)->update(['email_sent' => false]);
        }

        Log::error('SendStudentElectronicTicketJob failed', [
            'document_id' => $this->data['document_id'] ?? null,
            'onli_sale_id' => $this->onliSaleId,
            'recipient' => $this->data['for_mail'] ?? null,
            'message' => $exception->getMessage(),
        ]);
    }
}
