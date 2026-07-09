@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>Manage Data</h1>
            <p class="muted" style="margin-top:0.35rem;">Remove cards for a skill or reset the app completely.</p>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-soft">Back To Dashboard</a>
        </div>
    </div>

    <div class="grid two" style="margin-top:1rem; gap:1rem;">
        <div class="panel">
            <h2 style="margin-bottom:0.6rem;">Skills</h2>
            <p class="muted">Remove all cards and reviews for a specific skill without deleting the skill itself.</p>

            <div style="margin-top:1rem; display: grid; gap:0.6rem;">
                @forelse ($decks as $deck)
                    <div class="list-item">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.5rem;">
                            <div>
                                <strong>{{ $deck->name }}</strong>
                                <div class="meta">Cards: {{ $deck->cards_count }} | Due: {{ $deck->due_cards_count }}</div>
                            </div>
                            <div class="actions">
                                <form method="POST" action="{{ route('manage.clear_skill', $deck->id) }}" onsubmit="return confirm('Remove ALL cards and reviews for this skill?');">
                                    @csrf
                                    <button type="submit" class="btn btn-danger">Clear Cards</button>
                                </form>
                                <form method="POST" action="{{ route('skills.destroy', $deck->id) }}" onsubmit="return confirm('Delete this skill and all its cards?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-soft">Delete Skill</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="muted">No skills found.</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <h2 style="margin-bottom:0.6rem;">Reset Application</h2>
            <p class="muted">This will permanently remove your skills, cards, reviews, and materials. This action cannot be undone.</p>

            <form method="POST" action="{{ route('manage.reset') }}" style="margin-top:1rem;" onsubmit="return confirm('Are you sure you want to reset the application? This cannot be undone.');">
                @csrf
                <label for="confirm">Type <strong>RESET</strong> to confirm</label>
                <input id="confirm" name="confirm" placeholder="Type RESET to confirm" required>
                <div style="margin-top:0.6rem; display:flex; gap:0.5rem;">
                    <button type="submit" class="btn btn-danger">Reset Application</button>
                    <a href="{{ route('backups.index') }}" class="btn btn-soft">Create Backup First</a>
                </div>
            </form>
        </div>
    </div>
@endsection
