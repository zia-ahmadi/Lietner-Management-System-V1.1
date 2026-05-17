@extends('layouts.app')

@section('content')
<div class="panel" style="max-width:560px; margin:0 auto;">
    <h1 style="margin-bottom:0.75rem;">Create Account</h1>
    <p style="margin-top:0;">Start your own spaced repetition workflow.</p>

    <form method="POST" action="{{ route('register') }}" class="stack" style="margin-top:1rem;">
        @csrf
        <div>
            <label for="name">Name</label>
            <input id="name" type="text" name="name" required autofocus>
        </div>
        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
        </div>
        <div>
            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>
        </div>
        <div><button type="submit" class="btn btn-primary">Register</button></div>
    </form>
</div>
@endsection
