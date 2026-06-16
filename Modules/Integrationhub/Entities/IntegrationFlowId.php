<?php

namespace Modules\Integrationhub\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IntegrationFlowId extends Model
{
    use HasFactory;

    protected $table = 'integration_flow_ids';

    protected $fillable = [
        'key',
        'flow_id',
        'label',
    ];
}
