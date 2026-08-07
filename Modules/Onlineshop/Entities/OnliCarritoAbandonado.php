<?php

namespace Modules\Onlineshop\Entities;

use Illuminate\Database\Eloquent\Model;

class OnliCarritoAbandonado extends Model
{
    protected $table = 'onli_carrito_abandonado';

    protected $fillable = [
        'client_id',
        'phone_country',
        'phone',
        'name',
        'email',
        'cart_items',
        'cart_total',
        'notification_sent_at',
        'notification_count',
        'paid',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id',
        'fbclid', 'gclid', 'referer', 'landing_url', 'traffic_source',
    ];

    protected $casts = [
        'cart_items' => 'array',
        'cart_total' => 'decimal:2',
        'notification_sent_at' => 'datetime',
        'notification_count' => 'integer',
    ];
}
