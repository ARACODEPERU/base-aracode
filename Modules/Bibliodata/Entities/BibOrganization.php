<?php

namespace Modules\Bibliodata\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibOrganization extends Model
{
    protected $table = 'bib_organizations';

    protected $fillable = [
        'person_id',
        'name',
        'document_number',
        'email',
        'phone',
        'status',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bib_organization_users', 'organization_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function organizationUsers(): HasMany
    {
        return $this->hasMany(BibOrganizationUser::class, 'organization_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(BibSubscription::class, 'organization_id');
    }
}
