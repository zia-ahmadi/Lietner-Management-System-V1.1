@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h1>Backup Management</h1>
        <p class="muted" style="margin-bottom:0;">Create, restore, and review your Leitner backups.</p>
    </div>
    <a href="{{ route('settings.edit') }}" class="btn btn-soft">All Settings</a>
</div>

<div class="grid two" style="align-items:start;">
    <section class="panel">
        <h2 style="margin-bottom:0.75rem;">Create Backup</h2>
        <p class="muted" style="margin-top:0;">A JSON file of your skills, cards, reviews, and materials will be created in this directory.</p>
        <form method="POST" action="{{ route('backups.store') }}" class="stack">
            @csrf
            <div>
                <label for="backup_path">Backup directory</label>
                <input id="backup_path" name="backup_path" value="{{ old('backup_path', $backupPath) }}" placeholder="/home/user/leitner-backups" required>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Backup Now</button>
                <a href="{{ route('settings.backups', ['path' => $backupPath]) }}" class="btn btn-soft">Refresh List</a>
            </div>
        </form>
    </section>

    <section class="panel" style="background:#f8fffd;">
        <h2 style="margin-bottom:0.75rem;">Restore Backup</h2>
        <p class="muted" style="margin-top:0;">Restoring replaces the current skills, cards, reviews, and materials.</p>
        <form method="POST" action="{{ route('backups.restore') }}" enctype="multipart/form-data" class="stack">
            @csrf
            <input type="hidden" name="backup_path" value="{{ $backupPath }}">
            <div>
                <label for="restore_file">Backup file (.json)</label>
                <input id="restore_file" name="restore_file" type="file" accept=".json,.txt" required>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Restore Backup</button>
            </div>
        </form>
        <p class="meta" style="word-break:break-all;margin-bottom:0;margin-top:0.85rem;">Current folder: {{ $backupPath }}</p>
    </section>
</div>

<section class="panel" style="margin-top:1rem;">
    <h2 style="margin-bottom:0.75rem;">Recent Backups</h2>
    @forelse ($backups as $backup)
        <article class="list-item" style="margin-bottom:0.6rem;">
            <strong>{{ $backup['name'] }}</strong>
            <div class="meta" style="margin-top:0.25rem;">Modified: {{ $backup['modified_at'] }} | Size: {{ $backup['size_kb'] }} KB</div>
            <div class="meta" style="word-break:break-all;">{{ $backup['path'] }}</div>
        </article>
    @empty
        <p class="muted">No backups found in this folder yet.</p>
    @endforelse
</section>
@endsection
