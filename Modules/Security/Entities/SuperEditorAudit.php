<?php

namespace Modules\Security\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperEditorAudit extends Model
{
    protected $table = 'super_editor_audits';

    /**
     * La bitácora solo escribe created_at (no hay updated_at).
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'session_id',
        'user_id',
        'permission_name',
        'role_name',
        'element_label',
        'element_kind',
        'source_url',
        'action',
        'allowed_before',
        'allowed_after',
        'ip',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'allowed_before' => 'boolean',
        'allowed_after' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Etiquetas legibles para la vista de historial.
     */
    public static function actionLabels(): array
    {
        return [
            'session_opened' => 'Sesión de edición abierta',
            'session_closed' => 'Sesión de edición cerrada',
            'staged' => 'Cambio en borrador',
            'reverted' => 'Cambio descartado del borrador',
            'applied' => 'Cambio aplicado y registrado',
            'discarded' => 'Cambio descartado',
            'blocked' => 'Cambio bloqueado (permiso protegido)',
            'password_failed' => 'Contraseña incorrecta',
        ];
    }

    public function actionLabel(): string
    {
        return self::actionLabels()[$this->action] ?? $this->action;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SuperEditorSession::class, 'session_id');
    }
}
