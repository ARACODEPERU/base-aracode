<?php

namespace Modules\Churchcommunity\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Churchcommunity\Database\factories\CigleMemberFactory;

class CigleMember extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sede_id',
        'person_id',
        'unique_code',
        'type_id',
        'main',
        'date_statement',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($complaint) {
            $complaint->unique_code = self::generateNewAnnualCompositeCode();
        });
    }

    public static function generateNewAnnualCompositeCode(): string
    {
        $prefix = 'JRS'; // Prefijo de Aracode Perú
        $currentYear = Carbon::now()->format('Y'); // Obtiene el año actual (ej: '2025')
        $sequentialNumberLength = 4; // Define la longitud del número consecutivo (ej: 00001)

        $searchPattern = "{$prefix}{$currentYear}%";

        $lastComplaintThisYear = self::where('unique_code', 'like', $searchPattern)
                                     ->orderBy('unique_code', 'desc')
                                     ->first();

        $nextSequentialNumber = 1; // Si no hay códigos este año, empezamos en 1.

        if ($lastComplaintThisYear) {
            $lastCodeNumberString = substr($lastComplaintThisYear->composite_code, - $sequentialNumberLength);
            $lastCodeNumber = (int) $lastCodeNumberString;
            $nextSequentialNumber = $lastCodeNumber + 1;
        }

        $formattedSequentialNumber = str_pad($nextSequentialNumber, $sequentialNumberLength, '0', STR_PAD_LEFT);
        return "{$prefix}{$currentYear}{$formattedSequentialNumber}";
    }

    public function person(): HasOne
    {
        return $this->hasOne(CiglePerson::class, 'id', 'person_id');
    }

    public function believers(): HasOne
    {
        return $this->hasOne(CigleBeliever::class, 'member_id', 'id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(CigleMemberType::class, 'type_id', 'id');
    }
}
