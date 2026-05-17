@extends('layouts.app')

@section('content')
<div class="panel" style="max-width:560px; margin:0 auto;">
    <h1 style="margin-bottom:0.75rem;">Sign In</h1>
    <p style="margin-top:0;">Access your dashboard and continue your review cycle.</p>

    <form method="POST" action="{{ route('login') }}" class="stack" style="margin-top:1rem;">
        @csrf
        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" required autofocus>
        </div>
        <div>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
        </div>
        <label style="display:flex; align-items:center; gap:0.45rem; font-weight:600;">
            <input type="checkbox" name="remember" style="width:auto;"> Remember me
        </label>
        <div><button type="submit" class="btn btn-primary">Login</button></div>
    </form>
</div>
@endsection
