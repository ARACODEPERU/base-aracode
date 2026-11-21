<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Código de autorización de un solo uso, guardado como hash y atado al cliente,
 * a la URL de retorno y al desafío PKCE.
 */
class McpOAuthCode extends Model
{
    // El plural automático partiría «OAuth» en dos palabras.
    protected $table = 'mcp_oauth_codes';

    protected $fillable = [
        'code_hash',
        'client_id',
        'user_id',
        'redirect_uri',
        'code_challenge',
        'scope',
        'autonomy',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'autonomy' => 'boolean',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
