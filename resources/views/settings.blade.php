@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h1>Settings</h1>
        <p class="muted" style="margin-bottom:0;">Manage the launcher now, with room for more app settings later.</p>
    </div>
</div>

<section class="panel">
    <h2 style="margin-bottom:0.75rem;">Local Launcher Settings</h2>
    <p class="muted" style="margin-top:0;">Configure the local project and scripts used to start or stop Leitner.</p>
    <div class="grid two" style="align-items:start;">
    <section class="panel">
        <form method="POST" action="{{ route('settings.update') }}" class="stack">
            @csrf
            @method('PUT')
            <div>
                <label for="project_path">Project directory</label>
                <input id="project_path" name="project_path" value="{{ old('project_path', $settings['project_path']) }}" placeholder="/home/user/leitner" required>
            </div>
            <div>
                <label for="start_script_path">Start script</label>
                <input id="start_script_path" name="start_script_path" value="{{ old('start_script_path', $settings['start_script_path']) }}" placeholder="/home/user/leitner/start-leitner.sh" required>
            </div>
            <div>
                <label for="stop_script_path">Stop script</label>
                <input id="stop_script_path" name="stop_script_path" value="{{ old('stop_script_path', $settings['stop_script_path']) }}" placeholder="/home/user/leitner/stop-leitner.sh" required>
            </div>
            <div>
                <label for="launch_port">Application port</label>
                <input id="launch_port" name="launch_port" type="number" min="1" max="65535" value="{{ old('launch_port', $settings['launch_port']) }}" required>
            </div>
            <label style="display:flex;align-items:center;gap:0.5rem;margin:0;">
                <input type="hidden" name="open_browser" value="0">
                <input type="checkbox" name="open_browser" value="1" @checked(old('open_browser', $settings['open_browser'])) style="width:auto;">
                Open the browser when the app starts
            </label>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </section>

    <section class="panel" style="background:#f8fffd;">
        <h2 style="margin-bottom:0.75rem;">Commands</h2>
        <p class="muted" style="margin-top:0;">Run these commands in a terminal on the machine where the project is installed.</p>
        <label for="start-command">Start</label>
        <textarea id="start-command" rows="3" readonly>{{ 'cd '.escapeshellarg($settings['project_path']).' && LEITNER_PORT='.(int) $settings['launch_port'].' LEITNER_NO_BROWSER='.(($settings['open_browser'] ?? true) ? '0' : '1').' '.escapeshellarg($settings['start_script_path']) }}</textarea>
        <label for="stop-command" style="margin-top:0.8rem;">Stop</label>
        <textarea id="stop-command" rows="2" readonly>{{ 'cd '.escapeshellarg($settings['project_path']).' && '.escapeshellarg($settings['stop_script_path']) }}</textarea>
        <p class="meta" style="margin-bottom:0;">The saved paths are checked before they are stored. The web page does not execute shell commands.</p>
    </section>
    </div>
</section>

<section class="panel" style="margin-top:1rem;">
    <div class="page-header" style="margin-bottom:0.75rem;">
        <div>
            <h2>Backup Management</h2>
            <p class="muted" style="margin:0.35rem 0 0;">Create, restore, and review backups without leaving Settings.</p>
        </div>
    </div>

    <div class="grid two" style="align-items:start;">
        <section class="panel">
            <h3 style="margin-bottom:0.75rem;">Create Backup</h3>
            <form method="POST" action="{{ route('backups.store') }}" class="stack">
                @csrf
                <div>
                    <label for="backup_path">Backup directory</label>
                    <input id="backup_path" name="backup_path" value="{{ old('backup_path', $backupPath) }}" placeholder="/home/user/leitner-backups" required>
                </div>
                <div class="actions">
                    <button type="submit" class="btn btn-primary">Backup Now</button>
                    <a href="{{ route('settings.edit', ['path' => $backupPath]) }}" class="btn btn-soft">Refresh List</a>
                </div>
            </form>
        </section>

        <section class="panel" style="background:#f8fffd;">
            <h3 style="margin-bottom:0.75rem;">Restore Backup</h3>
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

    <div style="margin-top:1rem;">
        <h3 style="margin-bottom:0.75rem;">Recent Backups</h3>
        @forelse ($backups as $backup)
            <article class="list-item" style="margin-bottom:0.6rem;">
                <strong>{{ $backup['name'] }}</strong>
                <div class="meta" style="margin-top:0.25rem;">Modified: {{ $backup['modified_at'] }} | Size: {{ $backup['size_kb'] }} KB</div>
                <div class="meta" style="word-break:break-all;">{{ $backup['path'] }}</div>
            </article>
        @empty
            <p class="muted">No backups found in this folder yet.</p>
        @endforelse
    </div>
</section>
@endsection