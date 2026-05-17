@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Dashboard</h1>
    <span class="muted">Your card progress at a glance</span>
</div>

<div class="grid six">
    <div class="stat" style="grid-column:span 2;">
        <h3>Total Cards</h3>
        <p>{{ $stats['total_cards'] }}</p>
    </div>
    <div class="stat" style="grid-column:span 2; background:#eefff8;">
        <h3>Completed Cards</h3>
        <p style="color:#0a7f52;">{{ $stats['completed_cards'] }}</p>
    </div>
    <div class="stat" style="grid-column:span 2; background:#fff5ea;">
        <h3>Due Today</h3>
        <p style="color:#c0671c;">{{ $stats['due_today'] }}</p>
    </div>
    <div class="stat" style="grid-column:span 2;">
        <h3>Total Skills</h3>
        <p>{{ $stats['total_skills'] }}</p>
    </div>
    <div class="stat" style="grid-column:span 2;">
        <h3>Total Reviews</h3>
        <p>{{ $stats['total_reviews'] }}</p>
    </div>
    <div class="stat" style="grid-column:span 2;">
        <h3>Materials</h3>
        <p>{{ $stats['materials'] }}</p>
    </div>
</div>
@endsection
