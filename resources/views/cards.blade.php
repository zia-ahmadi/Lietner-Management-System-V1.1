@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Cards Workspace</h1>
    <div class="actions">
        <a href="{{ route('dashboard') }}" class="btn btn-soft">Dashboard</a>
        <a href="{{ route('review.today') }}" class="btn btn-primary">Review Today ({{ $dueCount }})</a>
    </div>
</div>

<section class="panel" style="margin-bottom:1rem;">
    <h2 style="margin-bottom:0.6rem;">Create Card</h2>
    <p class="muted" style="margin-top:0;">Skill selection is optional. If skipped, card is assigned to <strong>General Skill</strong>.</p>

    <form method="POST" action="{{ route('cards.store') }}" class="stack">
        @csrf
        <div>
            <label for="skill-search-create">Skill (optional)</label>
            <input id="skill-search-create" name="skill_lookup" type="text" placeholder="Search skills" autocomplete="off" list="skill-options-create" value="{{ old('skill_lookup') }}" style="width:100%;">
            <input type="hidden" id="deck_id" name="deck_id" value="{{ old('deck_id', '') }}">
            <datalist id="skill-options-create">
                <option value="No skill (use General Skill)" data-deck-id=""></option>
                @foreach ($decks as $deck)
                    <option value="{{ $deck->name }}" data-deck-id="{{ $deck->id }}"></option>
                @endforeach
            </datalist>
            <p class="muted" style="margin-top:0.45rem;">Type to find a skill quickly.</p>
        </div>
        <div>
            <label for="front_text">Question / Prompt</label>
            <textarea id="front_text" name="front_text" rows="3" placeholder="Enter concept, question, or long note title" required>{{ old('front_text') }}</textarea>
        </div>
        <div>
            <label for="back_text">Answer / Notes (optional)</label>
            <textarea id="back_text" name="back_text" rows="5" placeholder="Optional detailed explanation, keywords, or summary">{{ old('back_text') }}</textarea>
        </div>
        <div><button class="btn btn-primary" type="submit">Create Card</button></div>
    </form>
</section>

<section class="panel">
    <h2 style="margin-bottom:0.75rem;">All Cards</h2>
    <form method="GET" action="{{ route('cards.index') }}" class="grid" style="grid-template-columns: repeat(5, minmax(0, 1fr)); margin-bottom:0.85rem;">
        <div style="grid-column: span 2;">
            <label for="q">Search</label>
            <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Find by question or answer">
        </div>
        <div>
            <label for="filter_deck_id">Skill</label>
            <select id="filter_deck_id" name="deck_id">
                <option value="">All skills</option>
                @foreach ($decks as $deck)
                    <option value="{{ $deck->id }}" @selected((string) ($filters['deck_id'] ?? '') === (string) $deck->id)>{{ $deck->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="box">Box</label>
            <input id="box" name="box" type="number" min="1" max="255" value="{{ $filters['box'] ?? '' }}" placeholder="Any">
        </div>
        <div>
            <label for="due">Review Status</label>
            <select id="due" name="due">
                <option value="all" @selected(($filters['due'] ?? 'all') === 'all')>All</option>
                <option value="due" @selected(($filters['due'] ?? 'all') === 'due')>Due now</option>
                <option value="not_due" @selected(($filters['due'] ?? 'all') === 'not_due')>Not due</option>
            </select>
        </div>
        <div>
            <label for="sort">Sort</label>
            <select id="sort" name="sort">
                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                <option value="oldest" @selected(($filters['sort'] ?? 'newest') === 'oldest')>Oldest</option>
                <option value="due_soon" @selected(($filters['sort'] ?? 'newest') === 'due_soon')>Due soon</option>
                <option value="due_late" @selected(($filters['sort'] ?? 'newest') === 'due_late')>Due late</option>
                <option value="box_high" @selected(($filters['sort'] ?? 'newest') === 'box_high')>Box high</option>
                <option value="box_low" @selected(($filters['sort'] ?? 'newest') === 'box_low')>Box low</option>
            </select>
        </div>
        <div class="actions">
            <button type="submit" class="btn btn-primary">Apply</button>
            <a href="{{ route('cards.index') }}" class="btn btn-soft">Reset</a>
        </div>
    </form>

    <p class="muted" style="margin-top:0; margin-bottom:0.75rem;">Showing {{ $cards->count() }} card(s).</p>

    @forelse ($cards as $card)
        @php
            $ageDays = (int) $card->created_at->copy()->startOfDay()->diffInDays(now()->startOfDay());
            $isCompleted = $ageDays >= 30;
            $daysUntilReview = (int) now()->startOfDay()->diffInDays($card->next_review_at->copy()->startOfDay(), false);
        @endphp
        <article class="list-item" style="margin-bottom:0.75rem;">
            <div class="page-header" style="margin-bottom:0.35rem;">
                <div class="text-panel scroll" style="flex:1; min-width:220px;">
                    <div class="text-rich text-front">{{ $card->front_text }}</div>
                </div>
                <small class="meta">Created: {{ $card->created_at?->format('Y-m-d H:i') }}</small>
            </div>

            <div class="text-panel scroll" style="margin:0.35rem 0;">
                <div class="text-rich">{{ $card->back_text ?: 'No answer saved for this card.' }}</div>
            </div>

            <div class="meta" style="margin-bottom:0.6rem;">
                Skill: {{ $card->deck?->name }} | Box: {{ $card->box }} | Streak: {{ $card->review_streak }} | Next: {{ $card->next_review_at?->format('Y-m-d H:i') }} | Turn: {{ $daysUntilReview <= 0 ? 'Today' : 'In '.$daysUntilReview.' day(s)' }} | Progress: {{ $isCompleted ? 'Completed' : $ageDays.' day(s) since created' }}
            </div>

            <div class="actions" style="margin-bottom:0.5rem;">
                <form method="POST" action="{{ route('cards.restart', $card->id) }}">@csrf<button type="submit" class="btn btn-soft">Add Back To Today Review</button></form>

                <details>
                    <summary class="btn btn-soft" style="list-style:none; cursor:pointer;">Edit Card</summary>
                    <form method="POST" action="{{ route('cards.update', $card->id) }}" class="stack" style="margin-top:0.7rem; min-width:min(760px, 100%);">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="skill-search-{{ $card->id }}">Skill (optional)</label>
                            <input id="skill-search-{{ $card->id }}" name="skill_lookup" type="text" placeholder="Search skills" autocomplete="off" list="skill-options-{{ $card->id }}" value="{{ $card->deck?->name ?? '' }}" style="width:100%;">
                            <input type="hidden" id="deck_{{ $card->id }}" name="deck_id" value="{{ $card->deck_id ?? '' }}">
                            <datalist id="skill-options-{{ $card->id }}">
                                <option value="No skill (use General Skill)" data-deck-id=""></option>
                                @foreach ($decks as $deck)
                                    <option value="{{ $deck->name }}" data-deck-id="{{ $deck->id }}"></option>
                                @endforeach
                            </datalist>
                            <p class="muted" style="margin-top:0.45rem;">Type to find a skill quickly.</p>
                        </div>
                        <div>
                            <label for="front_{{ $card->id }}">Question / Prompt</label>
                            <textarea id="front_{{ $card->id }}" name="front_text" rows="3" required>{{ $card->front_text }}</textarea>
                        </div>
                        <div>
                            <label for="back_{{ $card->id }}">Answer / Notes (optional)</label>
                            <textarea id="back_{{ $card->id }}" name="back_text" rows="5">{{ $card->back_text }}</textarea>
                        </div>
                        <div><button type="submit" class="btn btn-primary">Save Changes</button></div>
                    </form>
                </details>

                <form method="POST" action="{{ route('cards.destroy', $card->id) }}" onsubmit="return confirm('Delete this card?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Card</button>
                </form>
            </div>
        </article>
    @empty
        <p class="muted">No cards yet. Create your first card above.</p>
    @endforelse
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const attachSkillLookup = function (inputId, hiddenInputId) {
            const searchInput = document.getElementById(inputId);
            const hiddenInput = document.getElementById(hiddenInputId);

            if (!searchInput || !hiddenInput) {
                return;
            }

            const syncSelection = function () {
                const inputValue = searchInput.value.trim();
                const option = document.querySelector('#' + searchInput.getAttribute('list') + ' option[value="' + CSS.escape(inputValue) + '"]');

                if (!option) {
                    hiddenInput.value = '';
                    return;
                }

                hiddenInput.value = option.getAttribute('data-deck-id') || '';
            };

            searchInput.addEventListener('input', syncSelection);
            searchInput.addEventListener('change', syncSelection);
            syncSelection();
        };

        attachSkillLookup('skill-search-create', 'deck_id');

        document.querySelectorAll('input[id^="skill-search-"]').forEach(function (input) {
            const hiddenInputId = input.id.replace('skill-search-', 'deck_');
            attachSkillLookup(input.id, hiddenInputId);
        });
    });
</script>
@endsection
