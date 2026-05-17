@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Materials</h1>
    <span class="muted">Reference notes and resources</span>
</div>

<div class="grid two" style="align-items:start;">
    <section class="panel">
        <h2 style="margin-bottom:0.75rem;">Add Material</h2>
        <form method="POST" action="{{ route('materials.store') }}" class="stack">
            @csrf
            <div>
                <label for="title">Title</label>
                <input id="title" name="title" placeholder="Example: Binary Search Cheat Sheet" required>
            </div>
            <div>
                <label for="category">Category</label>
                <input id="category" name="category" placeholder="Algorithms / Language / Math">
            </div>
            <div>
                <label for="resource_url">Resource URL</label>
                <input id="resource_url" name="resource_url" type="url" placeholder="https://...">
            </div>
            <div>
                <label for="content">Notes</label>
                <textarea id="content" name="content" rows="5" placeholder="Quick notes, key points, references"></textarea>
            </div>
            <div><button class="btn btn-primary" type="submit">Save Material</button></div>
        </form>
    </section>

    <section class="panel">
        <h2 style="margin-bottom:0.75rem;">Saved Materials</h2>
        @forelse ($materials as $material)
            <article class="list-item" style="margin-bottom:0.6rem;">
                <strong>{{ $material->title }}</strong>
                <p style="margin:0.35rem 0;">{{ $material->content ?: 'No notes added.' }}</p>
                <div class="meta">Category: {{ $material->category ?: 'General' }}</div>
                @if ($material->resource_url)
                    <a href="{{ $material->resource_url }}" target="_blank" rel="noopener" style="font-size:0.9rem;">Open Resource</a>
                @endif
            </article>
        @empty
            <p class="muted">No materials yet. Add one from the form.</p>
        @endforelse
    </section>
</div>
@endsection
