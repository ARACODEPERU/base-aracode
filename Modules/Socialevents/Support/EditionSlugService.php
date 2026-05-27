<?php

namespace Modules\Socialevents\Support;

use Illuminate\Support\Str;
use Modules\Socialevents\Entities\EventEdition;

class EditionSlugService
{
    public function generateUniqueSlug(string $name, int|string|null $year = null, ?int $ignoreEditionId = null): string
    {
        $base = Str::slug($name);

        if ($year !== null && $year !== '') {
            $yearSlug = Str::slug((string) $year);
            $base = $base !== '' ? "{$base}-{$yearSlug}" : $yearSlug;
        }

        if ($base === '') {
            $base = 'torneo';
        }

        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoreEditionId)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function normalizeSlug(?string $slug): ?string
    {
        if ($slug === null || trim($slug) === '') {
            return null;
        }

        $normalized = Str::slug($slug);

        return $normalized !== '' ? $normalized : null;
    }

    private function slugExists(string $slug, ?int $ignoreEditionId): bool
    {
        $query = EventEdition::query()->where('public_slug', $slug);

        if ($ignoreEditionId !== null) {
            $query->where('id', '!=', $ignoreEditionId);
        }

        return $query->exists();
    }
}
