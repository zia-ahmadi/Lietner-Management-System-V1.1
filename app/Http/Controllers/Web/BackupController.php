<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Material;
use App\Models\Review;
use Carbon\Carbon;
use App\Services\AutoBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function index(Request $request): View
    {
        $defaultPath = storage_path('app/backups/user_'.auth()->id());
        $backupPath = (string) ($request->query('path') ?? session('backup_path', $defaultPath));

        $backups = $this->recentBackups($backupPath);

        return view('backups', [
            'backupPath' => $backupPath,
            'backups' => $backups,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup_path' => ['required', 'string', 'max:1000'],
        ]);

        $backupPath = trim($validated['backup_path']);

        if (! File::exists($backupPath)) {
            File::ensureDirectoryExists($backupPath);
        }

        if (! File::isDirectory($backupPath) || ! is_writable($backupPath)) {
            return back()->withErrors(['backup_path' => 'Backup directory is not writable.']);
        }

        $userId = (int) $request->user()->id;
        $timestamp = now()->format('Ymd_His');
        $fileName = "leitner_backup_user{$userId}_{$timestamp}.json";
        $filePath = rtrim($backupPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$fileName;

        $payload = [
            'meta' => [
                'app' => config('app.name'),
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

        return redirect()
            ->route('settings.edit', ['path' => $backupPath])
            ->with('status', 'Backup created successfully: '.$fileName)
            ->with('backup_path', $backupPath);
    }

    public function restore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'restore_file' => ['required', 'file', 'max:20480', 'mimes:json,txt'],
            'backup_path' => ['nullable', 'string', 'max:1000'],
        ]);

        $backupPath = trim((string) ($validated['backup_path'] ?? storage_path('app/backups/user_'.auth()->id())));
        $content = File::get($validated['restore_file']->getRealPath());
        $payload = json_decode($content, true);

        if (! is_array($payload)) {
            return back()->withErrors(['restore_file' => 'Invalid backup file content.']);
        }

        /** @var array<int, array<string, mixed>> $skills */
        $skills = is_array($payload['skills'] ?? null) ? $payload['skills'] : [];
        /** @var array<int, array<string, mixed>> $cards */
        $cards = is_array($payload['cards'] ?? null) ? $payload['cards'] : [];
        /** @var array<int, array<string, mixed>> $reviews */
        $reviews = is_array($payload['reviews'] ?? null) ? $payload['reviews'] : [];
        /** @var array<int, array<string, mixed>> $materials */
        $materials = is_array($payload['materials'] ?? null) ? $payload['materials'] : [];

        $userId = (int) $request->user()->id;

        DB::transaction(function () use ($userId, $skills, $cards, $reviews, $materials): void {
            Material::query()->where('user_id', $userId)->delete();
            Deck::query()->where('user_id', $userId)->delete();

            $skillMap = [];

            foreach ($skills as $skill) {
                $newSkill = Deck::query()->create([
                    'user_id' => $userId,
                    'name' => (string) ($skill['name'] ?? 'Imported Skill'),
                    'description' => (string) ($skill['description'] ?? ''),
                    'created_at' => $this->normalizeTimestamp($skill['created_at'] ?? null),
                    'updated_at' => $this->normalizeTimestamp($skill['updated_at'] ?? null),
                ]);

                $oldId = (int) ($skill['id'] ?? 0);
                if ($oldId > 0) {
                    $skillMap[$oldId] = (int) $newSkill->id;
                }
            }

            $fallbackSkillId = $this->ensureDefaultSkill($userId);
            $cardMap = [];

            foreach ($cards as $card) {
                $oldSkillId = (int) ($card['deck_id'] ?? 0);
                $newSkillId = $skillMap[$oldSkillId] ?? $fallbackSkillId;

                $newCard = Card::query()->create([
                    'deck_id' => $newSkillId,
                    'front_text' => (string) ($card['front_text'] ?? ''),
                    'back_text' => (string) ($card['back_text'] ?? ''),
                    'box' => (int) ($card['box'] ?? 1),
                    'review_streak' => (int) ($card['review_streak'] ?? 0),
                    'last_reviewed_at' => $card['last_reviewed_at'] ?? null,
                    'next_review_at' => $this->normalizeTimestamp($card['next_review_at'] ?? null),
                    'created_at' => $this->normalizeTimestamp($card['created_at'] ?? null),
                    'updated_at' => $this->normalizeTimestamp($card['updated_at'] ?? null),
                ]);

                $oldCardId = (int) ($card['id'] ?? 0);
                if ($oldCardId > 0) {
                    $cardMap[$oldCardId] = (int) $newCard->id;
                }
            }

            foreach ($reviews as $review) {
                $oldSkillId = (int) ($review['deck_id'] ?? 0);
                $oldCardId = (int) ($review['card_id'] ?? 0);
                $newSkillId = $skillMap[$oldSkillId] ?? $fallbackSkillId;
                $newCardId = $cardMap[$oldCardId] ?? null;

                if ($newCardId === null) {
                    continue;
                }

                Review::query()->create([
                    'deck_id' => $newSkillId,
                    'card_id' => $newCardId,
                    'result' => (string) ($review['result'] ?? 'correct'),
                    'box_before' => (int) ($review['box_before'] ?? 1),
                    'box_after' => (int) ($review['box_after'] ?? 1),
                    'quality' => isset($review['quality']) ? (int) $review['quality'] : null,
                    'reviewed_at' => $this->normalizeTimestamp($review['reviewed_at'] ?? null),
                    'next_review_at' => $this->normalizeTimestamp($review['next_review_at'] ?? null),
                    'created_at' => $this->normalizeTimestamp($review['created_at'] ?? null),
                    'updated_at' => $this->normalizeTimestamp($review['updated_at'] ?? null),
                ]);
            }

            foreach ($materials as $material) {
                Material::query()->create([
                    'user_id' => $userId,
                    'title' => (string) ($material['title'] ?? 'Imported Material'),
                    'category' => (string) ($material['category'] ?? ''),
                    'content' => (string) ($material['content'] ?? ''),
                    'resource_url' => (string) ($material['resource_url'] ?? ''),
                    'created_at' => $this->normalizeTimestamp($material['created_at'] ?? null),
                    'updated_at' => $this->normalizeTimestamp($material['updated_at'] ?? null),
                ]);
            }
        });
        app(AutoBackupService::class)->backupUser($userId);

        return redirect()
            ->route('settings.edit', ['path' => $backupPath])
            ->with('status', 'Backup restored successfully.')
            ->with('backup_path', $backupPath);
    }

    /**
     * @return array<int, array<string, string|int>>
     */
    private function recentBackups(string $backupPath): array
    {
        if (! File::isDirectory($backupPath)) {
            return [];
        }

        $files = collect(File::files($backupPath))
            ->filter(fn ($file) => str_starts_with($file->getFilename(), 'leitner_backup_'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->take(15)
            ->map(function ($file): array {
                return [
                    'name' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size_kb' => (int) ceil($file->getSize() / 1024),
                    'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            })
            ->values()
            ->all();

        return $files;
    }

    private function ensureDefaultSkill(int $userId): int
    {
        $skill = Deck::query()->firstOrCreate(
            ['user_id' => $userId, 'name' => 'General Skill'],
            ['description' => 'Auto-created fallback skill for restored cards.']
        );

        return (int) $skill->id;
    }

    private function normalizeTimestamp(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return now()->toDateTimeString();
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return now()->toDateTimeString();
        }
    }
}
