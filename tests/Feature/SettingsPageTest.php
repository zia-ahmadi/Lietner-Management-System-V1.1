<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_settings_sections_and_save_launch_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertSee('Local Launcher Settings')
            ->assertSee(route('settings.launcher'))
            ->assertSee('Backup Management')
            ->assertSee(route('settings.backups'));

        $this->actingAs($user)
            ->get('/settings/local-launcher')
            ->assertOk()
            ->assertSee('Commands');

        $this->actingAs($user)
            ->get('/settings/backups')
            ->assertOk()
            ->assertSee('Recent Backups');

        $this->actingAs($user)->put('/settings/local-launcher', [
            'project_path' => base_path(),
            'start_script_path' => base_path('start-leitner.sh'),
            'stop_script_path' => base_path('stop-leitner.sh'),
            'launch_port' => 8140,
            'open_browser' => '0',
        ])->assertRedirect('/settings/local-launcher');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'project_path' => base_path(),
            'launch_port' => 8140,
            'open_browser' => 0,
        ]);
    }

    public function test_launch_settings_require_existing_paths(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/settings/local-launcher', [
            'project_path' => '/missing/project',
            'start_script_path' => '/missing/start.sh',
            'stop_script_path' => '/missing/stop.sh',
            'launch_port' => 8137,
        ])->assertSessionHasErrors('project_path');
    }
}