<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Cliente OAuth público registrado por un conector MCP (claude.ai, Cursor...).
 */
class McpOAuthClient extends Model
{
    // El plural automático partiría «OAuth» en dos palabras.
    protected $table = 'mcp_oauth_clients';

    protected $fillable = [
        'client_id',
        'client_name',
        'redirect_uris',
    ];

    protected $casts = [
        'redirect_uris' => 'array',
    ];

    /**
     * Indica si la URL de retorno está registrada para este cliente.
     */
    public function allowsRedirect(string $uri): bool
    {
        return in_array($uri, $this->redirect_uris ?? [], true);
    }
}
