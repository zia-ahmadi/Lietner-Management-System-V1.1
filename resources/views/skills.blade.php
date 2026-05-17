@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Skills Workspace</h1>
    <a href="{{ route('cards.index') }}" class="btn btn-soft">Go To Cards</a>
</div>

<div class="grid two" style="margin-bottom:1rem; align-items:start;">
    <section class="panel">
        <h2 style="margin-bottom:0.7rem;">Create Skill</h2>
        <form method="POST" action="{{ route('skills.store') }}" class="stack">
            @csrf
            <div>
                <label for="skill_name">Skill Name</label>
                <input id="skill_name" name="name" value="{{ old('name') }}" placeholder="Example: Networking Fundamentals" required>
            </div>
            <div>
                <label for="skill_description">Description</label>
                <textarea id="skill_description" name="description" rows="4" placeholder="What this skill covers">{{ old('description') }}</textarea>
            </div>
            <div><button type="submit" class="btn btn-primary">Create Skill</button></div>
        </form>
    </section>

    <section class="panel" style="background:#f8fffd;">
        <h2 style="margin-bottom:0.7rem;">Skill Notes</h2>
        <p style="margin-top:0;">A skill groups related cards and review history.</p>
        <p>You can rename or delete any skill. Deleting a skill also deletes its cards and reviews.</p>
    </section>
</div>

<section class="panel">
    <h2 style="margin-bottom:0.75rem;">All Skills</h2>
    <div class="grid three">
    @forelse ($skills as $skill)
        <article class="list-item" style="background:linear-gradient(180deg,#ffffff 0%,#f8fffd 100%); border-color:#cfe3dd;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.65rem; margin-bottom:0.35rem;">
                <strong style="font-size:1.06rem; line-height:1.2;">{{ $skill->name }}</strong>
                <small class="meta" style="white-space:nowrap;">{{ $skill->created_at?->format('Y-m-d') }}</small>
            </div>
            <p style="margin:0.35rem 0 0.55rem; min-height:2.5em;">{{ $skill->description ?: 'No description.' }}</p>
            <div class="meta" style="margin-bottom:0.65rem;">Cards: {{ $skill->cards_count }} | Due now: {{ $skill->due_cards_count }}</div>

            <div class="actions">
                <a href="{{ route('skills.edit', $skill->id) }}" class="btn btn-soft">Edit Skill</a>
                <form method="POST" action="{{ route('skills.destroy', $skill->id) }}" onsubmit="return confirm('Delete this skill and all its cards?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Skill</button>
                </form>
            </div>
        </article>
    @empty
        <p class="muted">No skills yet. Create your first skill above.</p>
    @endforelse
    </div>
</section>
@endsection
