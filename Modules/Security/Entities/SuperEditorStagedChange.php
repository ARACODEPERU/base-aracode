<?php

namespace Modules\Security\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cambio en borrador (todavía no aplicado) dentro de una sesión de edición.
 */
class SuperEditorStagedChange extends Model
{
    protected $table = 'super_editor_staged_changes';

    protected $fillable = [
        'session_id',
        'permission_name',
        'role_id',
        'role_name',
        'allowed',
        'allowed_before',
        'element_label',
        'element_kind',
        'source_url',
    ];

    protected $casts = [
        'allowed' => 'boolean',
        'allowed_before' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SuperEditorSession::class, 'session_id');
    }

    /**
     * Clave estable del cambio: un rol y un permiso por sesión.
     */
    public function changeKey(): string
    {
        return $this->permission_name . '|' . $this->role_id;
    }
}
