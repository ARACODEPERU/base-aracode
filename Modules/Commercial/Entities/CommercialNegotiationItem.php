<?php

namespace Modules\Commercial\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialNegotiationItem extends Model
{
    use HasFactory;

    protected $table = 'commercial_negotiation_items';

    protected $fillable = [
        'negotiation_id',
        'item_type',
        'item_id',
        'title',
        'price',
    ];

    public function negotiation()
    {
        return $this->belongsTo(CommercialNegotiation::class, 'negotiation_id');
    }
}
