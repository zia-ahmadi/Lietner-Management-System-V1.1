<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Deck;
use App\Services\AutoBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function index(Request $request, Deck $deck): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'due' => ['nullable', 'boolean'],
        ]);

        $limit = $validated['limit'] ?? 50;
        $cards = $deck->cards()->orderBy('next_review_at');

        if ((bool) ($validated['due'] ?? false)) {
            $cards->due();
        }

        return response()->json($cards->limit($limit)->get());
    }

    public function store(Request $request, Deck $deck): JsonResponse
    {
        $validated = $request->validate([
            'front_text' => ['required', 'string'],
            'back_text' => ['required', 'string'],
            'next_review_at' => ['nullable', 'date'],
        ]);

        $card = $deck->cards()->create([
            'front_text' => $validated['front_text'],
            'back_text' => $validated['back_text'],
            'box' => 1,
            'review_streak' => 0,
            'next_review_at' => $validated['next_review_at'] ?? now(),
        ]);
        $this->autoBackupFromDeck($deck);

        return response()->json($card, 201);
    }

    public function update(Request $request, Card $card): JsonResponse
    {
        $validated = $request->validate([
            'front_text' => ['sometimes', 'required', 'string'],
            'back_text' => ['sometimes', 'required', 'string'],
            'box' => ['sometimes', 'required', 'integer', 'min:1', 'max:255'],
            'next_review_at' => ['sometimes', 'required', 'date'],
        ]);

        $card->update($validated);
        $this->autoBackupFromDeckId($card->deck_id);

        return response()->json($card->refresh());
    }

    public function destroy(Card $card): JsonResponse
    {
        $deckId = $card->deck_id;
        $card->delete();
        $this->autoBackupFromDeckId($deckId);

        return response()->json(status: 204);
    }

    public function due(Request $request, Deck $deck): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $limit = $validated['limit'] ?? 20;

        $cards = $deck->cards()
            ->due()
            ->orderBy('next_review_at')
            ->limit($limit)
            ->get();

        return response()->json($cards);
    }

    private function autoBackupFromDeck(Deck $deck): void
    {
        if (is_numeric($deck->user_id)) {
            app(AutoBackupService::class)->backupUser((int) $deck->user_id);
        }
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
