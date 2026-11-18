<?php

namespace Modules\Treasury\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Extracto importado a la conciliación: guarda el archivo, el mapeo que
 * confirmó el usuario y el resultado (líneas emparejadas y partidas nuevas).
 */
class TreasuryStatementImport extends Model
{
    protected $fillable = [
        'treasury_account_id',
        'file_name',
        'format',
        'delimiter',
        'rows_total',
        'rows_matched',
        'rows_created',
        'mapping',
        'summary',
        'created_by',
    ];

    protected $casts = [
        'mapping' => 'array',
        'summary' => 'array',
        'rows_total' => 'integer',
        'rows_matched' => 'integer',
        'rows_created' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TreasuryReconciliationItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
