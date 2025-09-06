<?php
namespace App\Services;
use App\Models\Tag;


//Sync tags helper (creates missing tags)
class SyncNoteTags
{
    public static function handle($note, array $names = []): void
    {
        if (empty($names)) {
            $note->tags()->sync([]);
            return;
        }
        $ids = collect($names)
            ->map(fn($n) => trim(mb_strtolower($n)))
            ->filter()
            ->unique()
            ->map(fn($n) => Tag::firstOrCreate(['name' => $n])->id)
            ->values()
            ->all();
        $note->tags()->sync($ids);
    }
}
