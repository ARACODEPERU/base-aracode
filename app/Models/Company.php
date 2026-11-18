<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'ruc',
        'name',
        'business_name',
        'tradename',
        'fiscal_address',
        'phone',
        'representative',
        'email',
        'logo',
        'logo_document',
        'key_sunat',
        'user_sunat',
        'certificate_sunat',
        'mode',
        'ubigeo',
        'logo_negative',
        'logo_dark',
        'isotipo',
        'isotipo_negative',
        'isotipo_dark',
        'social_networks',
        'withdrawal_account_number',
        'brand_primary',
        'brand_secondary',
        'sire_mode',
        'sire_client_id',
        'sire_client_secret',
        'sire_sol_user',
        'sire_sol_key',
    ];

    /**
     * Las cuatro piezas de la credencial del SIRE se guardan cifradas: el
     * client_id/client_secret identifican a la aplicación y el usuario/clave
     * SOL al contribuyente.
     */
    protected $casts = [
        'sire_client_id' => 'encrypted',
        'sire_client_secret' => 'encrypted',
        'sire_sol_user' => 'encrypted',
        'sire_sol_key' => 'encrypted',
    ];

    public function district(): HasOne
    {
        return $this->hasOne(District::class, 'id', 'ubigeo');
    }
}
