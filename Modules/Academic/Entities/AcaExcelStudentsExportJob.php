<?php

namespace Modules\Academic\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Academic\Database\factories\AcaExcelStudentsExportJobFactory;

class AcaExcelStudentsExportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'download_url',
        'progress',
        'status',
        'error_message',
    ];

    // Opcional: Relación con el usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
