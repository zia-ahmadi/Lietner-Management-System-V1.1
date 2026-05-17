<?php

namespace Tests\Feature;

use App\Models\Deck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_update_and_delete_skill(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/skills', [
            'name' => 'Networking',
            'description' => 'Protocols and models',
        ])->assertRedirect('/skills');

        $skill = Deck::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->patch('/skills/'.$skill->id, [
            'name' => 'Computer Networking',
            'description' => 'Updated',
        ])->assertRedirect('/skills');

        $this->assertDatabaseHas('decks', [
            'id' => $skill->id,
            'name' => 'Computer Networking',
        ]);

        $this->actingAs($user)->delete('/skills/'.$skill->id)->assertRedirect('/skills');
        $this->assertDatabaseMissing('decks', ['id' => $skill->id]);
    }

    public function test_user_cannot_edit_or_delete_other_user_skill(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $skill = Deck::create([
            'user_id' => $owner->id,
            'name' => 'Private Skill',
        ]);

        $this->actingAs($intruder)->patch('/skills/'.$skill->id, [
            'name' => 'Changed',
        ])->assertNotFound();

        $this->actingAs($intruder)->delete('/skills/'.$skill->id)->assertNotFound();
    }
}
