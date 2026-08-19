<?php

namespace Modules\Commercial\Entities;

use App\Models\Person;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialNegotiation extends Model
{
    use HasFactory;

    protected $table = 'commercial_negotiations';

    protected $fillable = [
        'token',
        'title',
        'body',
        'total_price',
        'currency',
        'payment_type',
        'initial_amount',
        'schedule',
        'single_payment_days',
        'contact_channel',
        'contact_detail',
        'email',
        'payment_method',
        'payment_link',
        'status',
        'client_id',
        'client_data',
        'voucher_path',
        'sale_id',
        'sale_document_id',
        'email_sent_at',
        'process_progress',
        'rejected_reason',
        'created_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'schedule' => 'array',
        'client_data' => 'array',
        'verified_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'process_progress' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(CommercialNegotiationItem::class, 'negotiation_id');
    }

    public function client()
    {
        return $this->belongsTo(Person::class, 'client_id');
    }

    public function invoice()
    {
        return $this->hasOne(CommercialNegotiationInvoice::class, 'negotiation_id');
    }

    public function saleDocument()
    {
        return $this->belongsTo(SaleDocument::class, 'sale_document_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
