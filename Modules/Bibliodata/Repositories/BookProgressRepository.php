<?php

declare(strict_types=1);

namespace Modules\Bibliodata\Repositories;

use Illuminate\Support\Carbon;
use Modules\Bibliodata\Contracts\ProgressRepository;
use Modules\Bibliodata\Entities\BibBookProgress;

class BookProgressRepository implements ProgressRepository
{
    public function get(int $userId, int $resourceId): ?array
    {
        $row = BibBookProgress::query()
            ->where('user_id', $userId)
            ->where('book_id', $resourceId)
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'book_id' => (int) $row->book_id,
            'current_page' => $row->page_id ? (int) $row->page_id : null,
            'progress' => $row->progress !== null ? (int) round((float) $row->progress) : null,
            'chapter' => $row->section_id ? (int) $row->section_id : null,
            'updated_at' => $row->last_viewed_at instanceof Carbon
                ? $row->last_viewed_at->toIso8601String()
                : null,
        ];
    }

    public function save(int $userId, int $resourceId, array $data): void
    {
        BibBookProgress::updateOrCreate(
            ['user_id' => $userId, 'book_id' => $resourceId],
            [
                'page_id' => $data['current_page'] ?? null,
                'section_id' => $data['chapter'] ?? null,
                'progress' => $data['progress'] ?? null,
                'last_viewed_at' => now(),
            ]
        );
    }

    public function delete(int $userId, int $resourceId): void
    {
        BibBookProgress::query()
            ->where('user_id', $userId)
            ->where('book_id', $resourceId)
            ->delete();
    }
}
