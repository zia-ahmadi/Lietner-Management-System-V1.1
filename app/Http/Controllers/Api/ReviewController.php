<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Deck;
use App\Services\AutoBackupService;
use App\Services\LeitnerScheduler;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(Request $request, Card $card, LeitnerScheduler $scheduler): JsonResponse
    {
        $validated = $request->validate([
            'result' => ['required', 'in:correct,wrong'],
            'quality' => ['nullable', 'integer', 'min:1', 'max:5'],
            'reviewed_at' => ['nullable', 'date'],
        ]);

        $reviewedAt = isset($validated['reviewed_at'])
            ? Carbon::parse($validated['reviewed_at'])
            : now();

        $result = DB::transaction(function () use ($card, $scheduler, $validated, $reviewedAt) {
            $boxBefore = $card->box;
            $updatedCard = $scheduler->applyReview($card, $validated['result'], $reviewedAt);

            $review = $updatedCard->reviews()->create([
                'deck_id' => $updatedCard->deck_id,
                'result' => $validated['result'],
                'box_before' => $boxBefore,
                'box_after' => $updatedCard->box,
                'quality' => $validated['quality'] ?? null,
                'reviewed_at' => $reviewedAt,
                'next_review_at' => $updatedCard->next_review_at,
            ]);

            return [
                'review' => $review,
                'card' => $updatedCard,
            ];
        });
        $this->autoBackupFromDeckId($card->deck_id);

        return response()->json($result, 201);
    }

    private function autoBackupFromDeckId(mixed $deckId): void
    {
        if (! is_numeric($deckId)) {
            return;
        }

        $userId = Deck::query()->whereKey((int) $deckId)->value('user_id');

        if (is_numeric($userId)) {
            app(AutoBackupService::class)->backupUser((int) $userId);
        }
    }
}
