<?php

namespace Modules\Commercial\Entities;

use Illuminate\Database\Eloquent\Model;

class CommercialNegotiationItem extends Model
{
    protected $table = 'commercial_negotiation_items';

    protected $fillable = [
        'negotiation_id',
        'item_type',
        'entity_name_product',
        'item_id',
        'title',
        'price',
    ];

    public function negotiation()
    {
        return $this->belongsTo(CommercialNegotiation::class, 'negotiation_id');
    }

    /**
     * Retorna la clase de la que proviene el item (FQCN) o null si no se conoce.
     */
    public function entityClass(): ?string
    {
        $class = $this->entity_name_product;

        if ($class && class_exists($class)) {
            return $class;
        }

        // Respaldo por item_type para items creados antes de persistir la clase.
        return match ($this->item_type) {
            'course' => \Modules\Academic\Entities\AcaCourse::class,
            'subscription' => \Modules\Academic\Entities\AcaSubscriptionType::class,
            default => null,
        };
    }
}
