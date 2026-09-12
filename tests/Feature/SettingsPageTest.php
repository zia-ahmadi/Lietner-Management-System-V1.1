<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_and_save_launch_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertSee('Launch Settings');

        $this->actingAs($user)->put('/settings', [
            'project_path' => base_path(),
            'start_script_path' => base_path('start-leitner.sh'),
            'stop_script_path' => base_path('stop-leitner.sh'),
            'launch_port' => 8140,
            'open_browser' => '0',
        ])->assertRedirect('/settings');

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

        $this->actingAs($user)->put('/settings', [
            'project_path' => '/missing/project',
            'start_script_path' => '/missing/start.sh',
            'stop_script_path' => '/missing/stop.sh',
            'launch_port' => 8137,
        ])->assertSessionHasErrors('project_path');
    }
}