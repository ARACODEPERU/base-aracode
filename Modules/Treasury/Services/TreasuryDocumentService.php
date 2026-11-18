<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Treasury\Entities\TreasuryDocument;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Cartera de cheques y letras.
 *
 * Registra documentos por cobrar (de clientes) y por pagar (a proveedores) y
 * controla su ciclo: en cartera, depositado, cobrado/pagado, protestado o
 * anulado. Un documento nunca se borra: se anula con auditoría.
 *
 * Los documentos pendientes alimentan la previsión de caja por su vencimiento.
 */
class TreasuryDocumentService
{
    public function __construct(
        private TreasuryAuditService $audit,
        private TreasuryLedgerService $ledger,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws TreasuryOperationException
     */
    public function register(array $data, ?int $userId = null): TreasuryDocument
    {
        $type = (string) ($data['type'] ?? '');
        $direction = (string) ($data['direction'] ?? '');
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $issue = Carbon::parse($data['issue_date'] ?? now()->toDateString());
        $due = Carbon::parse($data['due_date'] ?? $issue->toDateString());

        if (! array_key_exists($type, TreasuryDocument::TYPE_LABELS)) {
            throw new TreasuryOperationException('El tipo de documento debe ser cheque o letra.');
        }

        if (! array_key_exists($direction, TreasuryDocument::DIRECTION_LABELS)) {
            throw new TreasuryOperationException('El documento debe ser por cobrar o por pagar.');
        }

        if ($amount <= 0) {
            throw new TreasuryOperationException('El monto del documento debe ser mayor que cero.');
        }

        if ($due->lt($issue)) {
            throw new TreasuryOperationException('El vencimiento no puede ser anterior a la emisión.');
        }

        if (trim((string) ($data['number'] ?? '')) === '') {
            throw new TreasuryOperationException('El número del documento es obligatorio.');
        }

        $document = TreasuryDocument::create([
            'type' => $type,
            'direction' => $direction,
            'number' => trim((string) $data['number']),
            'bank_name' => $data['bank_name'] ?? null,
            'issuer' => $data['issuer'] ?? null,
            'issuer_doc_type' => ($data['issuer_doc_type'] ?? null) ?: null,
            'issuer_doc_number' => filled($data['issuer_doc_number'] ?? null)
                ? preg_replace('/\D/', '', (string) $data['issuer_doc_number'])
                : null,
            'supplier_id' => $direction === TreasuryDocument::DIRECTION_PAYABLE
                ? ($data['supplier_id'] ?? null)
                : null,
            'amount' => $amount,
            'currency' => strtoupper(trim((string) ($data['currency'] ?? 'PEN'))) ?: 'PEN',
            'issue_date' => $issue->toDateString(),
            'due_date' => $due->toDateString(),
            'status' => TreasuryDocument::STATUS_PORTFOLIO,
            'treasury_account_id' => $data['treasury_account_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
        ]);

        $this->audit->log('document', $document->id, 'created', [], [
            'type' => $document->type,
            'direction' => $document->direction,
            'number' => $document->number,
            'amount' => $amount,
            'currency' => $document->currency,
            'due_date' => $document->due_date?->toDateString(),
        ]);

        return $document->load(['supplier', 'account', 'creator']);
    }

    /**
     * Cambia el estado respetando el ciclo de vida del documento.
     *
     * @throws TreasuryOperationException
     */
    public function changeStatus(
        TreasuryDocument $document,
        string $status,
        ?int $userId = null,
        ?string $note = null,
    ): TreasuryDocument {
        if ($document->status === $status) {
            return $document;
        }

        if (! array_key_exists($status, TreasuryDocument::STATUS_LABELS)) {
            throw new TreasuryOperationException('Estado de documento inválido.');
        }

        $allowed = [
            TreasuryDocument::STATUS_PORTFOLIO => [
                TreasuryDocument::STATUS_DEPOSITED,
                TreasuryDocument::STATUS_COLLECTED,
                TreasuryDocument::STATUS_PAID,
                TreasuryDocument::STATUS_PROTESTED,
                TreasuryDocument::STATUS_CANCELLED,
            ],
            TreasuryDocument::STATUS_DEPOSITED => [
                TreasuryDocument::STATUS_COLLECTED,
                TreasuryDocument::STATUS_PROTESTED,
                TreasuryDocument::STATUS_CANCELLED,
            ],
            TreasuryDocument::STATUS_COLLECTED => [],
            TreasuryDocument::STATUS_PAID => [],
            TreasuryDocument::STATUS_PROTESTED => [],
            TreasuryDocument::STATUS_CANCELLED => [],
        ];

        if (! in_array($status, $allowed[$document->status] ?? [], true)) {
            throw new TreasuryOperationException(
                'No se puede pasar de «' . $document->statusLabel() . '» a «'
                . (TreasuryDocument::STATUS_LABELS[$status] ?? $status) . '».'
            );
        }

        // Cada dirección tiene sus estados válidos.
        if ($document->isReceivable()
            && ! in_array($status, [
                TreasuryDocument::STATUS_DEPOSITED,
                TreasuryDocument::STATUS_COLLECTED,
                TreasuryDocument::STATUS_PROTESTED,
                TreasuryDocument::STATUS_CANCELLED,
            ], true)) {
            throw new TreasuryOperationException('Un documento por cobrar no se marca como pagado.');
        }

        if (! $document->isReceivable()
            && ! in_array($status, [TreasuryDocument::STATUS_PAID, TreasuryDocument::STATUS_CANCELLED], true)) {
            throw new TreasuryOperationException('Un documento por pagar solo se marca como pagado o anulado.');
        }

        $old = $document->status;

        DB::transaction(function () use ($document, $status, $userId, $note, $old) {
            $note = trim((string) $note);

            $document->status = $status;
            $document->updated_by = $userId;

            if ($note !== '') {
                $document->notes = trim(($document->notes ? $document->notes . "\n" : '') . $note);
            }

            $document->save();

            // Cobrar o pagar el documento mueve el libro de la cuenta elegida.
            $this->settleInLedger($document, $status, $userId);

            $this->audit->log('document', $document->id, 'status_changed', [
                'status' => $old,
            ], [
                'status' => $status,
                'note' => $note ?: null,
            ]);
        });

        return $document->load(['supplier', 'account', 'updater']);
    }

    /**
     * Asienta en el libro el cobro (documento por cobrar) o el pago
     * (documento por pagar) del documento.
     *
     * Solo ocurre si el documento tiene una cuenta de Tesorería asignada; sin
     * cuenta, el cambio de estado queda solo en la cartera. La firma de origen
     * evita duplicar el asiento si el cambio se reintenta, y un periodo cerrado
     * hace fallar la operación completa (el estado no avanza).
     *
     * @throws TreasuryOperationException
     */
    private function settleInLedger(TreasuryDocument $document, string $status, ?int $userId): void
    {
        $isCollection = $document->isReceivable() && $status === TreasuryDocument::STATUS_COLLECTED;
        $isPayment = ! $document->isReceivable() && $status === TreasuryDocument::STATUS_PAID;

        if ((! $isCollection && ! $isPayment) || ! $document->treasury_account_id) {
            return;
        }

        $account = $document->account;

        if (! $account) {
            return;
        }

        $manera = $isCollection ? 'cobrado' : 'pagado';
        $reference = $document->typeLabel() . ' ' . $document->number;
        $description = $reference . ' ' . $manera;
        $currency = strtoupper((string) ($document->currency ?: 'PEN')) ?: 'PEN';
        $date = now()->toDateString();

        $transaction = $isCollection
            ? $this->ledger->registerIncome(
                account: $account,
                date: $date,
                amount: (float) $document->amount,
                originType: 'treasury_document',
                originId: $document->id,
                reference: $reference,
                description: $description,
                source: TreasuryTransaction::SOURCE_AUTO,
                userId: $userId,
                currency: $currency,
            )
            : $this->ledger->registerExpense(
                account: $account,
                date: $date,
                amount: (float) $document->amount,
                originType: 'treasury_document',
                originId: $document->id,
                reference: $reference,
                description: $description,
                source: TreasuryTransaction::SOURCE_AUTO,
                userId: $userId,
                currency: $currency,
            );

        if (! $transaction) {
            return;
        }

        $this->audit->log('document', $document->id, 'settled', [], [
            'transaction_id' => $transaction->id,
            'account' => $account->displayName(),
            'type' => $transaction->type,
            'amount' => (float) $transaction->amount,
            'currency' => $currency,
        ]);
    }

    /**
     * Resumen de la cartera por moneda.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $documents = TreasuryDocument::query()
            ->pending()
            ->with(['supplier', 'account'])
            ->orderBy('due_date')
            ->get();

        $groups = $documents
            ->groupBy(fn (TreasuryDocument $document) => strtoupper((string) ($document->currency ?: 'PEN')) ?: 'PEN')
            ->map(fn ($rows, $currency) => [
                'currency' => $currency,
                'receivable' => round((float) $rows->where('direction', TreasuryDocument::DIRECTION_RECEIVABLE)->sum('amount'), 2),
                'payable' => round((float) $rows->where('direction', TreasuryDocument::DIRECTION_PAYABLE)->sum('amount'), 2),
                'overdue' => round((float) $rows->filter(fn (TreasuryDocument $row) => $row->isOverdue())->sum('amount'), 2),
            ])
            ->values()
            ->all();

        return [
            'groups' => $groups,
            'count' => $documents->count(),
            'receivable' => round((float) $documents->where('direction', TreasuryDocument::DIRECTION_RECEIVABLE)->sum('amount'), 2),
            'payable' => round((float) $documents->where('direction', TreasuryDocument::DIRECTION_PAYABLE)->sum('amount'), 2),
            'overdue' => $documents->filter(fn (TreasuryDocument $row) => $row->isOverdue())->count(),
            'due_soon' => $documents->filter(fn (TreasuryDocument $row) => $row->due_date
                && ! $row->isOverdue()
                && $row->due_date->lte(today()->addDays(7)))->count(),
        ];
    }

    /**
     * Documentos pendientes dentro del horizonte de la previsión.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forecastItems(Carbon $from, Carbon $to): array
    {
        return TreasuryDocument::query()
            ->pending()
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('due_date')
            ->get()
            ->map(fn (TreasuryDocument $document) => [
                'date' => $document->due_date?->toDateString(),
                'direction' => $document->isReceivable() ? 'in' : 'out',
                'currency' => strtoupper((string) ($document->currency ?: 'PEN')) ?: 'PEN',
                'amount' => (float) $document->amount,
                'concept' => $document->typeLabel() . ' ' . $document->number
                    . ($document->issuer ? ' · ' . $document->issuer : ''),
                'type' => 'documento',
            ])
            ->all();
    }
}
