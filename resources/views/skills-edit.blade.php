@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h1>Edit Skill</h1>
        <p class="muted" style="margin:0.25rem 0 0;">Update name and description for this skill.</p>
    </div>
    <a href="{{ route('skills.index') }}" class="btn btn-soft">Back To Skills</a>
</div>

<div class="grid two" style="align-items:start;">
    <section class="panel">
        <h2 style="margin-bottom:0.75rem;">Skill Details</h2>
        <form method="POST" action="{{ route('skills.update', $skill->id) }}" class="stack">
            @csrf
            @method('PATCH')
            <div>
                <label for="name">Skill Name</label>
                <input id="name" name="name" value="{{ old('name', $skill->name) }}" required>
            </div>
            <div>
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" placeholder="What this skill covers">{{ old('description', $skill->description) }}</textarea>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('skills.index') }}" class="btn btn-soft">Cancel</a>
            </div>
        </form>
    </section>

    <section class="panel" style="background:#f8fffd;">
        <h2 style="margin-bottom:0.75rem;">Skill Summary</h2>
        <div class="meta">Created: {{ $skill->created_at?->format('Y-m-d H:i') }}</div>
        <div class="meta">Cards: {{ $skill->cards_count }}</div>
        <div class="meta">Due now: {{ $skill->due_cards_count }}</div>
    </section>
</div>
@endsection
