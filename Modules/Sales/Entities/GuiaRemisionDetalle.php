<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuiaRemisionDetalle extends Model
{
    protected $table = 'guia_remision_detalles';

    protected $fillable = [
        'guia_remision_id',
        'product_id',
        'sale_document_item_id',
        'codigo',
        'descripcion',
        'unidad',
        'cantidad',
        'cod_prod_sunat',
        'peso',
        'atributos',
        'orden',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'peso' => 'float',
        'atributos' => 'array',
        'orden' => 'integer',
    ];

    public function guia(): BelongsTo
    {
        return $this->belongsTo(GuiaRemision::class, 'guia_remision_id');
    }
}
