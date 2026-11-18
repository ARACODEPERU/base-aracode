<?php

namespace Modules\Treasury\Entities;

use App\Models\CompanyBilletera;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transferencia programada de una billetera digital a su banco vinculado.
 *
 * Pendiente: el dinero ya está en la cuenta de la billetera y todavía no se
 * ha movido al banco. Al cumplirse la fecha hábil, el comando programado crea
 * la transferencia real y deja la fila procesada con su enlace.
 */
class TreasuryWalletTransfer extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'company_billetera_id',
        'wallet_account_id',
        'bank_account_id',
        'source_bridge_id',
        'amount',
        'currency',
        'scheduled_date',
        'status',
        'treasury_transfer_id',
        'reference',
        'error',
        'user_id',
        'executed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'scheduled_date' => 'date',
        'executed_at' => 'datetime',
    ];

    public function companyBilletera(): BelongsTo
    {
        return $this->belongsTo(CompanyBilletera::class, 'company_billetera_id');
    }

    public function walletAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'wallet_account_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'bank_account_id');
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransfer::class, 'treasury_transfer_id');
    }

    public function sourceBridge(): BelongsTo
    {
        return $this->belongsTo(TreasurySourceMovement::class, 'source_bridge_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
