<?php

namespace Modules\Commercial\Entities;

use App\Models\Person;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CommercialContract extends Model
{
    use HasFactory;

    protected $table = 'commercial_contracts';

    protected $fillable = [
        'client_id',
        'responsible_person_id',
        'service_id',
        'contract_type',
        'title',
        'start_date',
        'end_date',
        'amount',
        'currency',
        'body',
        'signed_pdf_path',
        'status',
    ];

    public function client()
    {
        return $this->belongsTo(Person::class, 'client_id');
    }

    public function responsible()
    {
        return $this->belongsTo(Person::class, 'responsible_person_id');
    }

    public function service()
    {
        return $this->belongsTo(Product::class, 'service_id');
    }

    public function payments()
    {
        return $this->hasMany(CommercialContractPayment::class, 'contract_id')->orderBy('payment_number');
    }
}
