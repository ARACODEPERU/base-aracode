<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentityDocumentType extends Model
{
     // Nombre de la tabla
    protected $table = 'identity_document_type';

    // Tipo de clave primaria
    protected $primaryKey = 'id';

    // Indica que la clave NO es autoincremental
    public $incrementing = false;

    // Indica que la clave es de tipo string
    protected $keyType = 'string';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'id',
        'description',
        'long_description',
        'sunat_code',
        'number_characters'
    ];

    /**
     * Indica si este tipo de documento corresponde a un extranjero (sunat_code = '00').
     */
    public static function isForeign($documentTypeId): bool
    {
        if ($documentTypeId === null || $documentTypeId === '') {
            return false;
        }

        $docType = static::find((string) $documentTypeId);

        return $docType && $docType->sunat_code === '00';
    }
}
