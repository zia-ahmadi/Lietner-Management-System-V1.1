<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deck;
use App\Services\AutoBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeckController extends Controller
{
    public function index(): JsonResponse
    {
        $decks = Deck::query()
            ->withCount('cards')
            ->withCount(['cards as due_cards_count' => fn ($query) => $query->due()])
            ->latest()
            ->get();

        return response()->json($decks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $deck = Deck::create($validated);
        $this->autoBackupFromUserId($deck->user_id);

        return response()->json($deck, 201);
    }

    public function show(Deck $deck): JsonResponse
    {
        $deck->loadCount('cards');
        $dueCardsCount = $deck->cards()->due()->count();

        return response()->json([
            'deck' => $deck,
            'stats' => [
                'total_cards' => $deck->cards_count,
                'due_cards' => $dueCardsCount,
            ],
        ]);
    }

    public function update(Request $request, Deck $deck): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $deck->update($validated);
        $this->autoBackupFromUserId($deck->user_id);

        return response()->json($deck->refresh());
    }

    public function destroy(Deck $deck): JsonResponse
    {
        $userId = $deck->user_id;
        $deck->delete();
        $this->autoBackupFromUserId($userId);

        return response()->json(status: 204);
    }

    public function stats(Deck $deck): JsonResponse
    {
        $boxDistribution = $deck->cards()
            ->selectRaw('box, COUNT(*) as total')
            ->groupBy('box')
            ->orderBy('box')
            ->pluck('total', 'box');

        return response()->json([
            'total_cards' => $deck->cards()->count(),
            'due_cards' => $deck->cards()->due()->count(),
            'box_distribution' => $boxDistribution,
            'reviews_count' => $deck->reviews()->count(),
        ]);
    }

    private function autoBackupFromUserId(mixed $userId): void
    {
        if (is_numeric($userId)) {
            app(AutoBackupService::class)->backupUser((int) $userId);
        }
    }
}
