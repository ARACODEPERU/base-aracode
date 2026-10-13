<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Fila del registro de datos de prueba del modulo escolar.
 *
 * Cada registro que crea el llenado de prueba se anota aqui con su entidad y su
 * id. El borrado del modo prueba se apoya en esta tabla: solo elimina lo que
 * tiene fila, de modo que nunca toca datos reales del colegio.
 */
class AcaSchoolTestRecord extends Model
{
    protected $table = 'aca_school_test_records';

    protected $fillable = [
        'run_id',
        'school_id',
        'entity',
        'record_id',
        'label',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'record_id' => 'integer',
    ];
}
