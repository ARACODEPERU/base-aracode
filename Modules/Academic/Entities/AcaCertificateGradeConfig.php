<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaCertificateGradeConfig extends Model
{
    protected $table = 'aca_certificates_grade_config';

    protected $fillable = [
        'certificate_id',
        'back_fontfamily_grade',
        'back_font_size_grade',
        'back_color_grade',
        'back_position_grade_x',
        'back_position_grade_y',
        'back_visible_grade',
        'back_rectangle_width',
        'back_rectangle_height',
        'back_rectangle_color',
    ];

    protected $casts = [
        'back_visible_grade' => 'boolean',
        'back_rectangle_width' => 'integer',
        'back_rectangle_height' => 'integer',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(AcaCertificateParameter::class, 'certificate_id');
    }
}
