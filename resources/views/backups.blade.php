@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Backups</h1>
    <span class="muted">Create and review recent backups</span>
</div>

<div class="grid two" style="align-items:start;">
    <section class="panel">
        <h2 style="margin-bottom:0.75rem;">Create Backup</h2>
        <p class="muted" style="margin-top:0;">Enter a directory path, then click backup. A JSON file of your skills, cards, reviews, and materials will be created there.</p>

        <form method="POST" action="{{ route('backups.store') }}" class="stack">
            @csrf
            <div>
                <label for="backup_path">Backup Directory</label>
                <input id="backup_path" name="backup_path" value="{{ old('backup_path', $backupPath) }}" placeholder="D:\\Backups\\Leitner" required>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Backup Now</button>
                <a href="{{ route('backups.index', ['path' => $backupPath]) }}" class="btn btn-soft">Refresh List</a>
            </div>
        </form>
    </section>

    <section class="panel" style="background:#f8fffd;">
        <h2 style="margin-bottom:0.75rem;">Restore Backup</h2>
        <p class="muted" style="margin-top:0;">Choose a backup file from your PC. Restoring will replace your current skills, cards, reviews, and materials.</p>
        <form method="POST" action="{{ route('backups.restore') }}" enctype="multipart/form-data" class="stack">
            @csrf
            <input type="hidden" name="backup_path" value="{{ $backupPath }}">
            <div>
                <label for="restore_file">Backup File (.json)</label>
                <input id="restore_file" name="restore_file" type="file" accept=".json,.txt" required>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Restore Backup</button>
            </div>
        </form>
        <hr style="border:none;border-top:1px solid #d7e3ec;margin:1rem 0;">
        <h3 style="margin-bottom:0.45rem;">Current Folder</h3>
        <p class="meta" style="word-break: break-all;">{{ $backupPath }}</p>
        <p class="muted">Most recent 15 backups are shown below.</p>
    </section>
</div>

<section class="panel" style="margin-top:1rem;">
    <h2 style="margin-bottom:0.75rem;">Recent Backups</h2>

    @forelse ($backups as $backup)
        <article class="list-item" style="margin-bottom:0.6rem;">
            <strong>{{ $backup['name'] }}</strong>
            <div class="meta" style="margin-top:0.25rem;">Modified: {{ $backup['modified_at'] }} | Size: {{ $backup['size_kb'] }} KB</div>
            <div class="meta" style="word-break: break-all;">{{ $backup['path'] }}</div>
        </article>
    @empty
        <p class="muted">No backups found in this folder yet.</p>
    @endforelse
</section>
@endsection
