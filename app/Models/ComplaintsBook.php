<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintsBook extends Model
{
    protected $fillable = [
        'composite_code',
        'user_id',
        'names',
        'document_type_id',
        'dni',
        'email',
        'telefono',
        'type_service',
        'description_service',
        'currency',
        'amount',
        'type_claim',
        'claim',
        'called',
        'accepts',
        'status'
    ];

    protected static function boot()
    {
        parent::boot();

        // Registra un callback que se ejecutará JUSTO ANTES de que un nuevo registro
        // de ComplaintsBook sea guardado (insertado) en la base de datos.
        static::creating(function ($complaint) {
            // Asigna el folio generado al campo 'composite_code' de la instancia del reclamo.
            // Esto asegura que el folio ya esté presente antes de la operación INSERT.
            $complaint->composite_code = self::generateNewAnnualCompositeCode();
        });
    }

    public static function generateNewAnnualCompositeCode(): string
    {
        $prefix = 'ARA'; // Prefijo de Aracode Perú
        $currentYear = Carbon::now()->format('Y'); // Obtiene el año actual (ej: '2025')
        $sequentialNumberLength = 5; // Define la longitud del número consecutivo (ej: 00001)

        // Prepara el patrón de búsqueda para encontrar códigos del año actual
        // Ejemplo: "ARA-2025-%" buscará todos los códigos que empiecen así.
        $searchPattern = "{$prefix}-{$currentYear}-%";

        // Consulta la base de datos para obtener el último código compuesto registrado para el año actual.
        // Se ordena por el 'composite_code' en forma descendente para asegurar que el primero sea el de mayor número.
        $lastComplaintThisYear = self::where('composite_code', 'like', $searchPattern)
                                     ->orderBy('composite_code', 'desc')
                                     ->first();

        $nextSequentialNumber = 1; // Si no hay códigos este año, empezamos en 1.

        if ($lastComplaintThisYear) {
            // Si encontramos un código del año actual, extraemos su parte numérica.
            // Ejemplo: de "ARA-2025-00123", extraemos "00123".
            $lastCodeNumberString = substr($lastComplaintThisYear->composite_code, - $sequentialNumberLength);
            $lastCodeNumber = (int) $lastCodeNumberString; // Convertimos a entero (123)

            // Incrementamos el número para el siguiente código.
            $nextSequentialNumber = $lastCodeNumber + 1;
        }

        // Formatea el número secuencial con ceros a la izquierda para alcanzar la longitud deseada.
        // Ejemplo: si nextSequentialNumber es 12, se convierte en "00012".
        $formattedSequentialNumber = str_pad($nextSequentialNumber, $sequentialNumberLength, '0', STR_PAD_LEFT);

        // Ensambla el código compuesto completo en el formato ARA-YYYY-XXXXX.
        return "{$prefix}-{$currentYear}-{$formattedSequentialNumber}";
    }

    public function attentions(): HasMany
    {
        return $this->hasMany(ComplaintsBookAttention::class,'complaints_book_id');
    }
}
