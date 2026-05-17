<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Deck;
use App\Models\Material;
use App\Models\Review;
use Illuminate\Support\Facades\File;

class AutoBackupService
{
    public function backupUser(int $userId): ?string
    {
        if ($userId <= 0) {
            return null;
        }

        $basePath = trim((string) config('auto_backup.path'));

        if ($basePath === '') {
            return null;
        }

        File::ensureDirectoryExists($basePath);

        if (! File::isDirectory($basePath) || ! is_writable($basePath)) {
            return null;
        }

        $prefix = (string) config('auto_backup.filename_prefix', 'leitner_auto_backup_user');
        $filePath = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$prefix.$userId.'.json';

        $payload = [
            'meta' => [
                'app' => config('app.name'),
                'type' => 'automatic_backup',
                'user_id' => $userId,
                'created_at' => now()->toIso8601String(),
                'schedule_days' => [1, 3, 7, 14, 30],
            ],
            'skills' => Deck::query()->where('user_id', $userId)->get()->toArray(),
            'cards' => Card::query()->whereHas('deck', fn ($q) => $q->where('user_id', $userId))->get()->toArray(),
            'reviews' => Review::query()->whereHas('deck', fn ($q) => $q->where('user_id', $userId))->get()->toArray(),
            'materials' => Material::query()->where('user_id', $userId)->get()->toArray(),
        ];

        File::put($filePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $filePath;
    }
}
