<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Support\EditionSlugService;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('event_editions', 'public_slug')) {
            return;
        }

        $service = app(EditionSlugService::class);

        EventEdition::query()
            ->whereNull('public_slug')
            ->orWhere('public_slug', '')
            ->orderBy('id')
            ->each(function (EventEdition $edition) use ($service) {
                $edition->public_slug = $service->generateUniqueSlug(
                    $edition->name ?? 'torneo',
                    $edition->year,
                    $edition->id
                );
                $edition->saveQuietly();
            });
    }

    public function down(): void
    {
        // No revertir slugs generados.
    }
};
