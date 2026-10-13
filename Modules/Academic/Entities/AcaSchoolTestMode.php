<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Estado del modo prueba del modulo escolar (una sola fila).
 *
 * locked_at con fecha significa que la instalacion quedo en modo produccion: la
 * pantalla deja de permitir llenar o borrar datos de prueba. Desde la interfaz
 * el bloqueo no se revierte (es definitivo); revertirlo exigiria tocar la base
 * de datos a proposito.
 */
class AcaSchoolTestMode extends Model
{
    protected $table = 'aca_school_test_mode';

    /** La tabla guarda una sola fila: el estado del sistema. */
    public const STATE_ROW_ID = 1;

    protected $fillable = [
        'locked_at',
        'locked_by_user_id',
        'locked_by_name',
        'last_fill_at',
        'last_purge_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'locked_by_user_id' => 'integer',
        'last_fill_at' => 'datetime',
        'last_purge_at' => 'datetime',
    ];

    /** Fila unica de estado (la crea si todavia no existe). */
    public static function state(): self
    {
        return self::firstOrCreate(['id' => self::STATE_ROW_ID]);
    }

    /** true cuando el sistema ya quedo en modo produccion (bloqueo definitivo). */
    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }
}
