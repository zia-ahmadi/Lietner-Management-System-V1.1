<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('settings', [
            'settings' => [
                'project_path' => $user->project_path ?: base_path(),
                'start_script_path' => $user->start_script_path ?: base_path('start-leitner.sh'),
                'stop_script_path' => $user->stop_script_path ?: base_path('stop-leitner.sh'),
                'launch_port' => $user->launch_port ?: 8137,
                'open_browser' => $user->open_browser ?? true,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_path' => ['required', 'string', 'max:2000'],
            'start_script_path' => ['required', 'string', 'max:2000'],
            'stop_script_path' => ['required', 'string', 'max:2000'],
            'launch_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'open_browser' => ['nullable', 'boolean'],
        ]);

        $paths = [
            'project_path' => trim($validated['project_path']),
            'start_script_path' => trim($validated['start_script_path']),
            'stop_script_path' => trim($validated['stop_script_path']),
        ];

        if (! File::isDirectory($paths['project_path'])) {
            return back()->withInput()->withErrors(['project_path' => 'Project directory does not exist.']);
        }

        foreach (['start_script_path', 'stop_script_path'] as $field) {
            if (! File::isFile($paths[$field])) {
                return back()->withInput()->withErrors([$field => 'Script file does not exist.']);
            }
        }

        $request->user()->update([
            ...$paths,
            'launch_port' => (int) $validated['launch_port'],
            'open_browser' => $request->boolean('open_browser'),
        ]);

        return redirect()->route('settings.edit')->with('status', 'Launch settings saved.');
    }
}