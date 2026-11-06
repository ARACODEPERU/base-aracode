<?php

namespace App\Console\Commands;

use App\Helpers\Invoice\Sunat\SunatTicketQuery;
use Modules\Sales\Entities\SaleLowCommunication;
use Modules\Sales\Entities\SaleSummary;
use Illuminate\Console\Command;
use Throwable;

class SunatCheckTickets extends Command
{
    protected $signature = 'sunat:check-tickets
                            {--limit=25 : Máximo de resúmenes y bajas a consultar por corrida}
                            {--dry-run : Solo muestra el estado devuelto por SUNAT}';

    protected $description = 'Consulta los tickets pendientes de resúmenes diarios y comunicaciones de baja, y actualiza su estado.';

    public function handle(SunatTicketQuery $tickets): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $summaries = SaleSummary::query()
            ->whereNotNull('ticket')
            ->whereIn('status', ['Enviado', 'fue_enviado', 'registrado'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $communications = SaleLowCommunication::query()
            ->whereNotNull('ticket')
            ->whereIn('status', ['Enviado', 'fue_enviado', 'registrado'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($summaries->isEmpty() && $communications->isEmpty()) {
            $this->info('No hay tickets pendientes de consulta.');

            return self::SUCCESS;
        }

        $counters = ['aceptados' => 0, 'rechazados' => 0, 'proceso' => 0, 'errores' => 0];

        foreach ($summaries as $summary) {
            $this->poll(
                'Resumen '.($summary->summary_name ?: '#'.$summary->id),
                $summary->ticket,
                $dryRun,
                fn (array $result) => $tickets->applyToSummary($summary, $result),
                $counters
            );
        }

        foreach ($communications as $communication) {
            $this->poll(
                'Baja '.($communication->communication_name ?: '#'.$communication->id),
                $communication->ticket,
                $dryRun,
                fn (array $result) => $tickets->applyToLowCommunication($communication, $result),
                $counters
            );
        }

        $this->info(sprintf(
            'Resumen: %d aceptados, %d rechazados, %d en proceso, %d con error de red.',
            $counters['aceptados'],
            $counters['rechazados'],
            $counters['proceso'],
            $counters['errores']
        ));

        return self::SUCCESS;
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $apply
     * @param  array<string, int>  $counters
     */
    private function poll(string $label, ?string $ticket, bool $dryRun, callable $apply, array &$counters): void
    {
        try {
            $result = app(SunatTicketQuery::class)->query((string) $ticket);
            $state = $result['state'] ?? SunatTicketQuery::ERROR;

            match ($state) {
                SunatTicketQuery::ACEPTADO => $counters['aceptados']++,
                SunatTicketQuery::RECHAZADO => $counters['rechazados']++,
                SunatTicketQuery::EN_PROCESO => $counters['proceso']++,
                default => $counters['errores']++,
            };

            $this->line(sprintf('%s [%s] %s: %s', $label, $ticket, $state, mb_substr((string) ($result['message'] ?? ''), 0, 160)));

            if ($dryRun) {
                return;
            }

            $applied = $apply($result);

            if (! empty($applied['updated']) && in_array($state, [SunatTicketQuery::ACEPTADO, SunatTicketQuery::RECHAZADO], true)) {
                $this->info('  -> estado actualizado en base de datos.');
            }
        } catch (Throwable $e) {
            $counters['errores']++;
            $this->error($label.': '.$e->getMessage());
        }
    }
}
