<?php

namespace Modules\Socialevents\Entities;

use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialeventOrganizationDocumentRequest extends Model
{
    protected $table = 'socialevent_organization_document_requests';

    protected $fillable = [
        'organization_id',
        'requested_by',
        'purchase_type',
        'purchase_id',
        'document_type_id',
        'status',
        'billing_data',
        'sale_document_id',
        'issued_by',
        'issued_at',
        'delivery_channels',
        'delivery_log',
        'sent_at',
        'rejection_reason',
    ];

    protected $casts = [
        'billing_data' => 'array',
        'delivery_channels' => 'array',
        'delivery_log' => 'array',
        'issued_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(SocialeventOrganization::class, 'organization_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function saleDocument(): BelongsTo
    {
        return $this->belongsTo(SaleDocument::class, 'sale_document_id');
    }
}
