<?php

namespace Modules\Socialevents\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Socialevents\Database\factories\EventTeamFactory;

class EventTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'ubigeo',
        'ubigeo_description',
        'logo_path',
        'manager_id',
        'champion',
        'status'
    ];
    public function manager(): HasOne
    {
        return $this->hasOne(Person::class, 'id', 'manager_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(EventEditionTeamPlayer::class, 'team_id', 'id');
    }

    public function editions(): HasMany
    {
        return $this->hasMany(EventEditionTeam::class, 'team_id', 'id');
    }
}
