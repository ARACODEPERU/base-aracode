<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Academic\Database\factories\AcaCertificateParameterFactory;

class AcaCertificateParameter extends Model
{
    protected $table = 'aca_certificates_parameters';

    protected $fillable = [
        'course_id',
        'certificate_img',
        'fontfamily_date',
        'font_align_date',
        'font_vertical_align_date',
        'position_date_x',
        'position_date_y',
        'font_size_date',
        'color_date',
        'visible_date',
        'fontfamily_names',
        'font_align_names',
        'font_vertical_align_names',
        'position_names_x',
        'position_names_y',
        'font_size_names',
        'color_names',
        'visible_names',
        'fontfamily_title',
        'font_align_title',
        'font_vertical_align_title',
        'position_title_x',
        'position_title_y',
        'font_size_title',
        'max_width_title',
        'color_title',
        'visible_title',
        'position_qr_x',
        'position_qr_y',
        'size_qr',
        'font_align_qr',
        'visible_image_qr',
        'fontfamily_description',
        'font_align_description',
        'font_vertical_align_description',
        'position_description_x',
        'position_description_y',
        'font_size_description',
        'max_width_description',
        'color_description',
        'visible_description',
        'interspace_description',
        'name_certificate',
        'state',
        'certificate_img_finished',
    ];

    public function course()
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }
}
