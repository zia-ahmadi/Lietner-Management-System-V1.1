@extends('layouts.app')

@section('content')
<section class="hero panel" style="margin-bottom:1rem;">
    <div class="grid two" style="align-items:center;">
        <div>
            <span class="label-chip">Leitner Learning System</span>
            <h1 style="font-size:clamp(1.9rem, 4vw, 3rem); margin-top:0.7rem;">Study Smarter, Retain Longer.</h1>
            <p style="font-size:1.05rem; max-width:60ch;">Build knowledge with a professional spaced-repetition workflow designed for concepts, long notes, and exam preparation.</p>
            <div class="actions" style="margin-top:1rem;">
                @auth
                    <a class="btn btn-primary" href="{{ route('dashboard') }}">Open Dashboard</a>
                    <a class="btn btn-soft" href="{{ route('cards.index') }}">Open Cards</a>
                @else
                    <a class="btn btn-primary" href="{{ route('register') }}">Create Account</a>
                    <a class="btn btn-soft" href="{{ route('login') }}">Sign In</a>
                @endauth
            </div>
        </div>
        <div class="panel" style="background:#f8fffd;">
            <h2 style="margin-bottom:0.75rem;">System Flow</h2>
            <p style="margin:0.25rem 0;">Correct answers move forward: Day 1, 3, 7, 14, 30.</p>
            <p style="margin:0.25rem 0;">Wrong answers reset to start and reappear tomorrow.</p>
            <p style="margin:0.25rem 0;">After Day 30, card is treated as completed.</p>
        </div>
    </div>
</section>

<section class="panel">
    <h2 style="margin-bottom:0.8rem;">Review Calendar</h2>
    <div class="grid two">
        <div>
            <h3 style="margin-bottom:0.45rem;">Review Days</h3>
            <div class="actions">
                <span class="btn btn-soft" style="cursor:default;">Day 1</span>
                <span class="btn btn-soft" style="cursor:default;">Day 3</span>
                <span class="btn btn-soft" style="cursor:default;">Day 7</span>
                <span class="btn btn-soft" style="cursor:default;">Day 14</span>
                <span class="btn btn-soft" style="cursor:default;">Day 30</span>
            </div>
            <p style="margin-top:0.6rem;">These are the scheduled review checkpoints after correct answers.</p>
        </div>
        <div>
            <h3 style="margin-bottom:0.45rem;">Pass Days (No Review)</h3>
            <p style="margin:0.2rem 0;">Day 2</p>
            <p style="margin:0.2rem 0;">Day 4-6</p>
            <p style="margin:0.2rem 0;">Day 8-13</p>
            <p style="margin:0.2rem 0;">Day 15-29</p>
            <p style="margin-top:0.6rem;">These gaps are intentional for stronger long-term retention.</p>
        </div>
    </div>
</section>
@endsection
