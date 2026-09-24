<?php

namespace Modules\Security\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuperEditorSession extends Model
{
    protected $table = 'super_editor_sessions';

    protected $fillable = [
        'user_id',
        'ip',
        'user_agent',
        'started_at',
        'expires_at',
        'ended_at',
        'close_reason',
        'changes_applied',
        'changes_discarded',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'ended_at' => 'datetime',
        'changes_applied' => 'integer',
        'changes_discarded' => 'integer',
    ];

    public function stagedChanges(): HasMany
    {
        return $this->hasMany(SuperEditorStagedChange::class, 'session_id');
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }

    public function isExpired(): bool
    {
        return $this->isOpen() && $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function audits(): HasMany
    {
        return $this->hasMany(SuperEditorAudit::class, 'session_id');
    }
}
