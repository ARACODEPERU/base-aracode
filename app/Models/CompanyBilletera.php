<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBilletera extends Model
{
    use HasFactory;

    protected $fillable = [
        'billetera_id',
        'account_name',
        'account_number',
        'qr_image',
        'has_payment_link',
        'bank_account_id',
        'bank_transfer_days',
        'status',
    ];

    protected $casts = [
        'bank_transfer_days' => 'integer',
        'has_payment_link' => 'boolean',
        'status' => 'boolean',
    ];

    public function billetera(): BelongsTo
    {
        return $this->belongsTo(BilleteraDigital::class, 'billetera_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
