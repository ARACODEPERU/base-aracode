<?php

namespace Modules\Commercial\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialNegotiationInvoice extends Model
{
    use HasFactory;

    protected $table = 'commercial_negotiation_invoices';

    protected $fillable = [
        'negotiation_id',
        'invoice_type',
        'boleta_documento_tipo',
        'boleta_numero',
        'boleta_nombre',
        'ruc',
        'razon_social',
        'direccion',
        'estado',
        'condicion',
        'ubigeo',
        'distrito',
        'provincia',
        'departamento',
    ];

    public function negotiation()
    {
        return $this->belongsTo(CommercialNegotiation::class, 'negotiation_id');
    }
}
