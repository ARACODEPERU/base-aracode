<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Frase sugerida de conclusion descriptiva para una competencia y un
 * nivel de logro. El docente la elige y puede editarla libremente.
 */
class AcaSchoolCompetencyPhrase extends Model
{
    use HasFactory;

    protected $fillable = [
        'competency_id',
        'score',
        'phrase',
        'sort_order',
    ];

    public function competency(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolCompetency::class, 'competency_id');
    }
}
