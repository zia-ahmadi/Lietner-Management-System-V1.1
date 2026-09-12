@extends('layouts.app')

@section('content')
<div class="page-header">
    <div>
        <h1>Launch Settings</h1>
        <p class="muted" style="margin-bottom:0;">Configure the local project and scripts used to start or stop Leitner.</p>
    </div>
</div>

<div class="grid two" style="align-items:start;">
    <section class="panel">
        <h2 style="margin-bottom:0.75rem;">Local launcher</h2>
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
@endsection