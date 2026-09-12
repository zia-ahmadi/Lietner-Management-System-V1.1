@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h1>Settings</h1>
        <p class="muted" style="margin-bottom:0;">Choose a settings area to manage.</p>
    </div>
</div>

<div class="grid two" style="align-items:stretch;">
    <a href="{{ route('settings.launcher') }}" class="panel" style="text-decoration:none;color:inherit;">
        <span class="label-chip">Launcher</span>
        <h2 style="margin-top:0.75rem;">Local Launcher Settings</h2>
        <p>Configure the project directory, start and stop scripts, application port, and browser behavior.</p>
        <span class="btn btn-primary">Open launcher settings</span>
    </a>

    <a href="{{ route('settings.backups') }}" class="panel" style="text-decoration:none;color:inherit;">
        <span class="label-chip">Data</span>
        <h2 style="margin-top:0.75rem;">Backup Management</h2>
        <p>Create, restore, and review your Leitner backups from one place.</p>
        <span class="btn btn-primary">Open backup management</span>
    </a>
</div>
@endsection
