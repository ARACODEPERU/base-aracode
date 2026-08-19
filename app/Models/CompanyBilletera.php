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
        'bank_account_id',
        'status',
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
