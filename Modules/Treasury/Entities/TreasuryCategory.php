<?php

namespace Modules\Treasury\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreasuryCategory extends Model
{
    use HasFactory;

    public const APPLIES_INCOME = 'income';
    public const APPLIES_EXPENSE = 'expense';
    public const APPLIES_BOTH = 'both';

    protected $fillable = [
        'name',
        'applies_to',
        'is_system',
        'color',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(TreasuryTransaction::class, 'treasury_category_id');
    }

    /**
     * Indica si la categoría aplica a un tipo de movimiento dado.
     */
    public function appliesTo(string $type): bool
    {
        return $this->applies_to === self::APPLIES_BOTH || $this->applies_to === $type;
    }
}
