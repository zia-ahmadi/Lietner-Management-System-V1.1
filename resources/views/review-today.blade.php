@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Review Today</h1>
    <span class="muted">Due cards: {{ $dueCount }}</span>
</div>

<form method="GET" action="{{ route('review.today') }}" class="actions" style="margin-bottom:1rem; flex-wrap:wrap;">
    <label for="deck-filter" style="margin-right:0.5rem;">Skill</label>
    <select id="deck-filter" name="deck_id" style="min-width:12rem;">
        <option value="">All skills</option>
        @foreach ($decks as $deck)
            <option value="{{ $deck->id }}" @selected($selectedDeckId === $deck->id)>{{ $deck->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-soft">Filter</button>
    @if ($selectedDeckId)
        <a href="{{ route('review.today') }}" class="btn btn-soft">Show All</a>
    @endif
</form>

@if ($currentCard)
    <section class="panel" style="margin-bottom:1rem;">
        <div class="meta">Skill: {{ $currentCard->deck?->name }} | Box {{ $currentCard->box }}</div>

        {{-- View mode --}}
        <div id="card-view-{{ $currentCard->id }}">
            <div class="text-panel scroll" style="margin-top:0.7rem;">
                <div class="text-rich text-front card-content" dir="auto">{{ $currentCard->front_text }}</div>
            </div>

            <div class="actions" style="margin-top:1rem;">
                <button id="show-answer-btn" type="button" class="btn btn-soft">Show Answer</button>
                <form method="POST" action="{{ route('review.submit', $currentCard->id) }}">@csrf<input type="hidden" name="deck_id" value="{{ $selectedDeckId ?? '' }}"><button type="submit" name="result" value="correct" class="btn btn-primary">I Got It Correct</button></form>
                <form method="POST" action="{{ route('review.submit', $currentCard->id) }}">@csrf<input type="hidden" name="deck_id" value="{{ $selectedDeckId ?? '' }}"><button type="submit" name="result" value="wrong" class="btn btn-soft">I Got It Wrong</button></form>
                <button type="button" class="btn btn-soft" onclick="toggleEdit({{ $currentCard->id }})">Edit Card</button>
            </div>

            <div id="answer-box" class="panel" style="display:none; margin-top:0.9rem; background:#f7fffc; border-color:#c8e9e3;">
                <strong>Answer</strong>
                <div class="text-panel scroll" style="margin-top:0.45rem;">
                    <div class="text-rich card-content" dir="auto">{{ $currentCard->back_text ?: 'No answer saved for this card.' }}</div>
                </div>
            </div>
        </div>

        {{-- Edit mode --}}
        <form id="card-edit-{{ $currentCard->id }}" method="POST" action="{{ route('cards.update', $currentCard->id) }}" style="display:none;" class="stack" onsubmit="return confirm('Save changes to this card?');">
            @csrf
            @method('PATCH')
            <div>
                <label for="deck_{{ $currentCard->id }}">Skill (optional)</label>
                <select id="deck_{{ $currentCard->id }}" name="deck_id">
                    <option value="">No skill (use General Skill)</option>
                    @foreach ($decks as $deck)
                        <option value="{{ $deck->id }}" @selected($currentCard->deck_id === $deck->id)>{{ $deck->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="front_{{ $currentCard->id }}">Question / Prompt</label>
                <textarea id="front_{{ $currentCard->id }}" name="front_text" rows="3" dir="auto" class="card-content" required>{{ $currentCard->front_text }}</textarea>
            </div>
            <div>
                <label for="back_{{ $currentCard->id }}">Answer / Notes (optional)</label>
                <textarea id="back_{{ $currentCard->id }}" name="back_text" rows="5" dir="auto" class="card-content">{{ $currentCard->back_text }}</textarea>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-soft" onclick="toggleEdit({{ $currentCard->id }})">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h3 style="margin-bottom:0.7rem;">Next Due Cards</h3>
        @forelse ($upcomingCards as $card)
            <div style="margin-bottom:0.5rem;" id="upcoming-view-{{ $card->id }}">
                <div class="list-item" style="margin-bottom:0;">
                    <div class="text-rich card-content" dir="auto">{{ \Illuminate\Support\Str::limit($card->front_text, 180) }}</div>
                    <div class="meta" style="margin-top:0.25rem;">Skill: {{ $card->deck?->name }} | Box {{ $card->box }} | Due: {{ $card->next_review_at?->format('Y-m-d H:i') }}</div>
                </div>
                <div style="margin-top:0.3rem;">
                    <button type="button" class="btn btn-soft" style="font-size:0.78rem; padding:0.15rem 0.5rem;" onclick="toggleUpcomingEdit({{ $card->id }})">Edit Card</button>
                </div>
            </div>

            <form id="upcoming-edit-{{ $card->id }}" method="POST" action="{{ route('cards.update', $card->id) }}" style="display:none;" class="stack" onsubmit="return confirm('Save changes to this card?');">
                @csrf
                @method('PATCH')
                <div style="margin-bottom:0.65rem;">
                    <label for="upcoming_front_{{ $card->id }}">Question / Prompt</label>
                    <textarea id="upcoming_front_{{ $card->id }}" name="front_text" rows="2" dir="auto" class="card-content" required style="width:100%;">{{ $card->front_text }}</textarea>
                </div>
                <div style="margin-bottom:0.65rem;">
                    <label for="upcoming_back_{{ $card->id }}">Answer / Notes (optional)</label>
                    <textarea id="upcoming_back_{{ $card->id }}" name="back_text" rows="3" dir="auto" class="card-content" style="width:100%;">{{ $card->back_text }}</textarea>
                </div>
                <div class="actions">
                    <button type="button" class="btn btn-soft" onclick="toggleUpcomingEdit({{ $card->id }})">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        @empty
            <p class="muted">No more cards queued after this one.</p>
        @endforelse
    </section>

    <script>
        (function () {
            const button = document.getElementById('show-answer-btn');
            const answerBox = document.getElementById('answer-box');
            if (!button || !answerBox) return;
            button.addEventListener('click', function () {
                answerBox.style.display = 'block';
                button.style.display = 'none';
            });
        })();

        function toggleEdit(cardId) {
            var viewEl = document.getElementById('card-view-' + cardId);
            var editEl = document.getElementById('card-edit-' + cardId);

            if (!viewEl || !editEl) return;

            if (editEl.style.display === 'none') {
                viewEl.style.display = 'none';
                editEl.style.display = 'block';
            } else {
                viewEl.style.display = 'block';
                editEl.style.display = 'none';
            }
        }

        function toggleUpcomingEdit(cardId) {
            var viewEl = document.getElementById('upcoming-view-' + cardId);
            var editEl = document.getElementById('upcoming-edit-' + cardId);

            if (!viewEl || !editEl) return;

            if (editEl.style.display === 'none') {
                viewEl.style.display = 'none';
                editEl.style.display = 'block';
            } else {
                viewEl.style.display = 'block';
                editEl.style.display = 'none';
            }
        }
    </script>
@else
    <section class="panel">
        <h2>No cards due right now.</h2>
        <p class="muted">You're done for now. Come back later for your next repetition cycle.</p>
        <a href="{{ route('cards.index') }}" class="btn btn-soft">Go To Cards</a>
    </section>
@endif
@endsection
