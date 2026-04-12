<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaCertificateModuleConfig extends Model
{
    protected $table = 'aca_certificates_module_config';

    protected $fillable = [
        'certificate_id',
        'fontfamily_module_description',
        'font_align_module_description',
        'font_vertical_module_description',
        'position_module_description_x',
        'position_module_description_y',
        'font_size_module_description',
        'max_width_module_description',
        'text_align_module_description',
        'color_module_description',
        'visible_module_description',
    ];

    protected $casts = [
        'visible_module_description' => 'boolean',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(AcaCertificateParameter::class, 'certificate_id');
    }
}