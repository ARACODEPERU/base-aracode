<?php

namespace Modules\CMS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CmsSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name', 'email', 'phone', 'client_ip', 'read', 'subject', 'message',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'gclid', 'referer', 'landing_url', 'traffic_source'
    ];

    protected static function newFactory()
    {
        return \Modules\CMS\Database\factories\CmsSubscriberFactory::new();
    }
}
