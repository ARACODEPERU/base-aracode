<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExcelExportJob extends Model
{
    protected $fillable = [
        'user_id',
        'report_type',
        'status', // pending, processing, completed, failed
        'file_name',
        'file_path',
        'download_url',
        'error_message',
        'progress', // 0-100
        'filters', // JSON string de los filtros usados
    ];

    protected $casts = [
        'filters' => 'array', // Para castear automáticamente a array
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
