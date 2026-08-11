<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Material;
use App\Models\Review;
use App\Services\AutoBackupService;
use App\Services\LeitnerScheduler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudyController extends Controller
{
    public function home(): View
    {
        return view('home');
    }

    public function dashboard(LeitnerScheduler $scheduler): View
    {
        $user = auth()->user();

        $decks = Deck::query()
            ->where('user_id', $user->id)
            ->withCount('cards')
            ->withCount(['cards as due_cards_count' => fn ($query) => $query->due()])
            ->latest()
            ->get();

        $dueCards = $this->userCardQuery($user->id)
            ->with('deck:id,name')
            ->due()
            ->orderBy('next_review_at')
            ->limit(10)
            ->get();

        $stats = [
            'total_cards' => $this->userCardQuery($user->id)->count(),
            'completed_cards' => $this->userCardQuery($user->id)->where('created_at', '<=', now()->subDays(30))->count(),
            'due_today' => $this->userCardQuery($user->id)->due()->count(),
            'total_skills' => Deck::query()->where('user_id', $user->id)->count(),
            'materials' => Material::query()->where('user_id', $user->id)->count(),
            'total_reviews' => Review::query()->whereHas('deck', fn ($q) => $q->where('user_id', $user->id))->count(),
        ];

        return view('dashboard', compact('decks', 'dueCards', 'stats'));
    }

    public function skills(): View
    {
        $skills = Deck::query()
            ->where('user_id', auth()->id())
            ->withCount('cards')
            ->withCount(['cards as due_cards_count' => fn ($query) => $query->due()])
            ->latest()
            ->get();

        return view('skills', compact('skills'));
    }

    public function editSkill(Request $request, int $skill): View
    {
        $skillModel = Deck::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($skill)
            ->withCount('cards')
            ->withCount(['cards as due_cards_count' => fn ($query) => $query->due()])
            ->firstOrFail();

        return view('skills-edit', ['skill' => $skillModel]);
    }

    public function cards(Request $request): View
    {
        $userId = (int) auth()->id();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'deck_id' => [
                'nullable',
                'integer',
                Rule::exists('decks', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'box' => ['nullable', 'integer', 'min:1', 'max:255'],
            'due' => ['nullable', 'in:all,due,not_due'],
            'sort' => ['nullable', 'in:newest,oldest,due_soon,due_late,box_high,box_low'],
        ]);

        $cardsQuery = $this->userCardQuery($userId)
            ->with('deck:id,name')
            ->when(isset($filters['q']) && $filters['q'] !== '', function (Builder $query) use ($filters): void {
                $term = trim((string) $filters['q']);
                $query->where(function (Builder $nested) use ($term): void {
                    $nested->where('front_text', 'like', "%{$term}%")
                        ->orWhere('back_text', 'like', "%{$term}%");
                });
            })
            ->when(isset($filters['deck_id']), fn (Builder $query) => $query->where('deck_id', (int) $filters['deck_id']))
            ->when(isset($filters['box']), fn (Builder $query) => $query->where('box', (int) $filters['box']))
            ->when(($filters['due'] ?? 'all') === 'due', fn (Builder $query) => $query->due())
            ->when(($filters['due'] ?? 'all') === 'not_due', fn (Builder $query) => $query->where('next_review_at', '>', now()));

        $sort = $filters['sort'] ?? 'newest';

        match ($sort) {
            'oldest' => $cardsQuery->orderBy('created_at'),
            'due_soon' => $cardsQuery->orderBy('next_review_at'),
            'due_late' => $cardsQuery->orderByDesc('next_review_at'),
            'box_high' => $cardsQuery->orderByDesc('box')->orderByDesc('created_at'),
            'box_low' => $cardsQuery->orderBy('box')->orderByDesc('created_at'),
            default => $cardsQuery->latest(),
        };

        $cards = $cardsQuery->get();

        $dueCount = $this->userCardQuery($userId)
            ->due()
            ->count();

        $decks = Deck::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get();

        return view('cards', compact('cards', 'dueCount', 'decks', 'filters'));
    }

    public function reviewToday(Request $request): View
    {
        $userId = (int) auth()->id();

        $filters = $request->validate([
            'deck_id' => [
                'nullable',
                'integer',
                Rule::exists('decks', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
        ]);

        $dueCardsQuery = $this->userCardQuery($userId)
            ->with('deck:id,name')
            ->due()
            ->orderBy('next_review_at');

        if (array_key_exists('deck_id', $filters) && $filters['deck_id'] !== null) {
            $dueCardsQuery->where('deck_id', (int) $filters['deck_id']);
        }

        $dueCards = $dueCardsQuery->get();

        $currentCard = $dueCards->first();

        $decks = Deck::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get();

        $selectedDeckId = $filters['deck_id'] ?? null;

        $selectedDeck = $selectedDeckId !== null
            ? $decks->firstWhere('id', (int) $selectedDeckId)
            : null;

        return view('review-today', [
            'currentCard' => $currentCard,
            'dueCount' => $dueCards->count(),
            'upcomingCards' => $dueCards->skip(1)->take(5),
            'decks' => $decks,
            'selectedDeck' => $selectedDeck,
            'selectedDeckId' => $selectedDeckId,
        ]);
    }

    public function submitTodayReview(Request $request, int $card, LeitnerScheduler $scheduler): RedirectResponse
    {
        $validated = $request->validate([
            'result' => ['required', 'in:correct,wrong'],
            'deck_id' => [
                'nullable',
                'integer',
                Rule::exists('decks', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
        ]);

        $cardModel = $this->findUserCardOrFail((int) $request->user()->id, $card);

        DB::transaction(function () use ($cardModel, $validated, $scheduler): void {
            $reviewedAt = now();
            $boxBefore = $cardModel->box;

            $updatedCard = $scheduler->applyReview($cardModel, $validated['result'], $reviewedAt);

            $updatedCard->reviews()->create([
                'deck_id' => $updatedCard->deck_id,
                'result' => $validated['result'],
                'box_before' => $boxBefore,
                'box_after' => $updatedCard->box,
                'quality' => null,
                'reviewed_at' => $reviewedAt,
                'next_review_at' => $updatedCard->next_review_at,
            ]);
        });
        $this->autoBackup((int) $request->user()->id);

        $redirectParameters = [];
        if (!empty($validated['deck_id'])) {
            $redirectParameters['deck_id'] = (int) $validated['deck_id'];
        }

        return redirect()
            ->route('review.today', $redirectParameters)
            ->with('status', 'Review saved. Next card loaded.');
    }

    public function materials(): View
    {
        $materials = Material::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('materials', compact('materials'));
    }

    /**
     * Management dashboard for user data (clear per-skill cards, reset app).
     */
    public function manage(): View
    {
        $userId = (int) auth()->id();

        $decks = Deck::query()
            ->where('user_id', $userId)
            ->withCount('cards')
            ->withCount(['cards as due_cards_count' => fn ($q) => $q->due()])
            ->orderBy('name')
            ->get();

        return view('manage', compact('decks'));
    }

    public function clearSkill(Request $request, int $skill): RedirectResponse
    {
        $skillModel = Deck::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($skill)
            ->firstOrFail();

        DB::transaction(function () use ($skillModel): void {
            Review::query()->where('deck_id', $skillModel->id)->delete();
            Card::query()->where('deck_id', $skillModel->id)->delete();
        });

        $this->autoBackup((int) $request->user()->id);

        return redirect()
            ->route('manage.index')
            ->with('status', 'All cards and reviews for the skill were removed.');
    }

    public function resetApp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'confirm' => ['required', 'in:RESET'],
        ]);

        $userId = (int) $request->user()->id;

        DB::transaction(function () use ($userId): void {
            // Deleting decks will cascade to cards and reviews via foreign keys
            Deck::query()->where('user_id', $userId)->delete();
            Material::query()->where('user_id', $userId)->delete();
        });

        $this->autoBackup($userId);

        return redirect()
            ->route('manage.index')
            ->with('status', 'Application reset: all skills, cards, reviews, and materials removed.');
    }

    public function storeSkill(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Deck::query()->create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);
        $this->autoBackup((int) $request->user()->id);

        return redirect()
            ->route('skills.index')
            ->with('status', 'Skill created successfully.');
    }

    public function updateSkill(Request $request, int $skill): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $skillModel = Deck::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($skill)
            ->firstOrFail();

        $skillModel->update($validated);
        $this->autoBackup((int) $request->user()->id);

        return redirect()
            ->route('skills.index')
            ->with('status', 'Skill updated successfully.');
    }

    public function destroySkill(Request $request, int $skill): RedirectResponse
    {
        $skillModel = Deck::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($skill)
            ->firstOrFail();

        $skillModel->delete();
        $this->autoBackup((int) $request->user()->id);

        return redirect()
            ->route('skills.index')
            ->with('status', 'Skill deleted successfully.');
    }

    public function storeCard(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'deck_id' => [
                'nullable',
                'integer',
                Rule::exists('decks', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'front_text' => ['required', 'string'],
            'back_text' => ['nullable', 'string'],
        ]);

        $deckId = $validated['deck_id'] ?? $this->defaultDeckIdForUser((int) $request->user()->id);

        Card::query()->create([
            'deck_id' => $deckId,
            'front_text' => $validated['front_text'],
            'back_text' => $validated['back_text'] ?? '',
            'box' => 1,
            'review_streak' => 0,
            'next_review_at' => now(),
        ]);
        $this->autoBackup((int) $request->user()->id);

        return redirect()
            ->route('cards.index')
            ->with('status', 'Card created successfully.');
    }

    public function updateCard(Request $request, int $card): RedirectResponse
    {
        $validated = $request->validate([
            'deck_id' => [
                'nullable',
                'integer',
                Rule::exists('decks', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'front_text' => ['required', 'string'],
            'back_text' => ['nullable', 'string'],
        ]);

        $cardModel = $this->findUserCardOrFail((int) $request->user()->id, $card);
        $deckId = $validated['deck_id'] ?? $this->defaultDeckIdForUser((int) $request->user()->id);

        $cardModel->update([
            'deck_id' => $deckId,
            'front_text' => $validated['front_text'],
            'back_text' => $validated['back_text'] ?? '',
        ]);
        $this->autoBackup((int) $request->user()->id);

        return redirect()
            ->route('cards.index')
            ->with('status', 'Card updated successfully.');
    }

    public function destroyCard(Request $request, int $card): RedirectResponse
    {
        $cardModel = $this->findUserCardOrFail((int) $request->user()->id, $card);
        $cardModel->delete();
        $this->autoBackup((int) $request->user()->id);

        return redirect()
            ->route('cards.index')
            ->with('status', 'Card deleted successfully.');
    }

    public function restartCardReview(Request $request, int $card): RedirectResponse
    {
        $cardModel = $this->findUserCardOrFail((int) $request->user()->id, $card);

        $cardModel->forceFill([
            'box' => 1,
            'review_streak' => 0,
            'next_review_at' => now(),
        ])->save();
        $this->autoBackup((int) $request->user()->id);

        return redirect()
            ->route('cards.index')
            ->with('status', 'Card moved back to Box 1 and added to today review.');
    }

    public function storeMaterial(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'content' => ['nullable', 'string'],
            'resource_url' => ['nullable', 'url', 'max:2000'],
        ]);

        Material::query()->create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);
        $this->autoBackup((int) $request->user()->id);

        return redirect()
            ->route('materials')
            ->with('status', 'Material saved successfully.');
    }

    private function userCardQuery(int $userId): Builder
    {
        return Card::query()->whereHas('deck', fn ($query) => $query->where('user_id', $userId));
    }

    private function findUserCardOrFail(int $userId, int $cardId): Card
    {
        return $this->userCardQuery($userId)->whereKey($cardId)->firstOrFail();
    }

    private function defaultDeckIdForUser(int $userId): int
    {
        $deck = Deck::query()->firstOrCreate(
            ['user_id' => $userId, 'name' => 'General Skill'],
            ['description' => 'Auto-created skill for cards without a selected skill.']
        );

        return (int) $deck->id;
    }

    private function autoBackup(int $userId): void
    {
        app(AutoBackupService::class)->backupUser($userId);
    }
}
