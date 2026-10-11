<?php

namespace Modules\Socialevents\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Socialevents\Database\factories\EventEditionTeamPlayerFactory;

class EventEditionTeamPlayer extends Model
{
    use HasFactory;

    // Asegúrate de que Laravel sepa que esta es la tabla pivote custom

    protected $table = 'event_edition_team_players';

    protected $fillable = [
        'edition_id',
        'team_id',
        'person_id',
        'jersey_number',
        'position',
        'role_in_team',
        'registration_date'
    ];
    // Deshabilita los auto-incrementales ya que es una clave compuesta
    public $incrementing = false;

    /**
     * La tabla no tiene columna `id`: su clave primaria es la terna
     * (edition_id, team_id, person_id). Sin esto, Eloquent filtraba por `id`
     * en update(), delete(), refresh() y fresh(), y esas operaciones fallaban
     * con "Unknown column 'id'" (guardar o quitar un jugador no hacía nada).
     */
    protected function setKeysForSelectQuery($query)
    {
        return $this->setCompositeKeys($query);
    }

    protected function setKeysForSaveQuery($query)
    {
        return $this->setCompositeKeys($query);
    }

    private function setCompositeKeys($query)
    {
        return $query
            ->where('edition_id', $this->getAttribute('edition_id'))
            ->where('team_id', $this->getAttribute('team_id'))
            ->where('person_id', $this->getAttribute('person_id'));
    }

    // Define las relaciones para acceder fácilmente a los objetos:
    public function edition()
    {
        return $this->belongsTo(EventEdition::class, 'edition_id');
    }
    public function team()
    {
        return $this->belongsTo(EventTeam::class, 'team_id');
    }
    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
    public function sanctions()
    {
        return $this->hasMany(EventEditionMatchSanction::class, 'player_id', 'person_id');
    }
    public function stats()
    {
        return $this->hasMany(EventEditionMatchPlayerStat::class, 'player_id', 'person_id');
    }
    public function participations()
    {
        return $this->hasMany(EventEditionMatchParticipation::class, 'player_id', 'person_id');
    }
}
