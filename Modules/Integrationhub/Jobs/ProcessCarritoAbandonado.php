<?php

namespace Modules\Integrationhub\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Integrationhub\Support\TrafficSourceResolver;
use Modules\Onlineshop\Entities\OnliCarritoAbandonado;

class ProcessCarritoAbandonado implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $recordId;
    public string $phone;

    public function __construct(int $recordId, string $phone)
    {
        $this->recordId = $recordId;
        $this->phone = $phone;
    }

    public function handle(): void
    {
        try {
            $hub = app(IntegrationhubController::class);

            $record = OnliCarritoAbandonado::find($this->recordId);

            if (!$record) {
                return;
            }

            $paidItems = collect($record->cart_items ?? [])
                ->filter(fn ($item) => (float) ($item['price'] ?? 0) > 0)
                ->values();

            if ($paidItems->isEmpty()) {
                $record->update([
                    'notification_sent_at' => Carbon::now(),
                    'last_success_at' => Carbon::now(),
                ]);

                return;
            }

            $tracking = $record->only(TrafficSourceResolver::KEYS);
            $firstCourseId = (string) ($paidItems->first()['id'] ?? '');
            $courseNames = $paidItems
                ->map(fn ($item) => trim((string) ($item['name'] ?? 'Curso')))
                ->filter()
                ->implode(' y ');

            $actions = array_merge(
                [
                    [
                        'action' => 'set_field_value',
                        'field_name' => 'Abandono Carrito',
                        'value' => now()->toDateTimeString(),
                    ],
                    [
                        'action' => 'set_field_value',
                        'custom_field_id' => '806813',
                        'value' => $courseNames,
                    ],
                    [
                        'action' => 'send_flow',
                        'flow_id' => '1782496265219',
                    ],
                ],
                TrafficSourceResolver::actions($tracking)
            );

            $response = $hub->runEndpoint('create_contact', [
                'phone' => $this->phone,
                'email' => 'nadie_' . $firstCourseId . '@desconocido.com',
                'first_name' => 'usuario',
                'actions' => $actions,
            ], [], true);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new \RuntimeException(
                    'IntegrationHub create_contact respondió con HTTP ' . $response->getStatusCode()
                );
            }

            if ($record) {
                $record->update([
                    'notification_sent_at' => Carbon::now(),
                    'notification_count' => $record->notification_count + 1,
                    'last_success_at' => Carbon::now(),
                ]);
            }
        } catch (\Throwable $th) {
            IntegrationError::create([
                'message' => 'ProcessCarritoAbandonado: ' . $th->getMessage(),
                'source' => 'ProcessCarritoAbandonado',
            ]);

            // También contar el intento fallido para llegar al límite de 5
            $record = OnliCarritoAbandonado::find($this->recordId);
            if ($record) {
                $record->update([
                    'notification_sent_at' => Carbon::now(),
                    'notification_count' => $record->notification_count + 1,
                ]);
            }
        }
    }
}
