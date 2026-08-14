<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Deck;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard_and_materials(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/materials')->assertRedirect('/login');
    }

    public function test_user_sees_only_own_dashboard_and_materials_data(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $ownerDeck = Deck::create([
            'user_id' => $owner->id,
            'name' => 'Owner Deck',
        ]);

        $otherDeck = Deck::create([
            'user_id' => $other->id,
            'name' => 'Other Deck',
        ]);

        Card::create([
            'deck_id' => $ownerDeck->id,
            'front_text' => 'Owner card',
            'back_text' => 'Owner answer',
            'next_review_at' => now()->subMinute(),
        ]);

        Card::create([
            'deck_id' => $otherDeck->id,
            'front_text' => 'Other card',
            'back_text' => 'Other answer',
            'next_review_at' => now()->subMinute(),
        ]);

        Material::create([
            'user_id' => $owner->id,
            'title' => 'Owner material',
        ]);

        Material::create([
            'user_id' => $other->id,
            'title' => 'Other material',
        ]);

        $dashboard = $this->actingAs($owner)->get('/dashboard');
        $dashboard->assertOk();
        $dashboard->assertSee('Total Cards');
        $dashboard->assertSee('Total Skills');

        $cards = $this->actingAs($owner)->get('/cards');
        $cards->assertOk();
        $cards->assertSee('Owner card');
        $cards->assertDontSee('Other card');

        $materials = $this->actingAs($owner)->get('/materials');
        $materials->assertOk();
        $materials->assertSee('Owner material');
        $materials->assertDontSee('Other material');
    }

    public function test_dashboard_upcoming_due_forecast_counts_only_future_cards_for_the_authenticated_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ownerDeck = Deck::create(['user_id' => $owner->id, 'name' => 'Owner Deck']);
        $otherDeck = Deck::create(['user_id' => $other->id, 'name' => 'Other Deck']);

        Card::create(['deck_id' => $ownerDeck->id, 'front_text' => 'Soon', 'back_text' => '', 'next_review_at' => now()->addHours(2)]);
        Card::create(['deck_id' => $ownerDeck->id, 'front_text' => 'Later', 'back_text' => '', 'next_review_at' => now()->addDays(4)]);
        Card::create(['deck_id' => $ownerDeck->id, 'front_text' => 'Already due', 'back_text' => '', 'next_review_at' => now()->subMinute()]);
        Card::create(['deck_id' => $otherDeck->id, 'front_text' => 'Other user', 'back_text' => '', 'next_review_at' => now()->addHour()]);

        $response = $this->actingAs($owner)->getJson('/dashboard/upcoming-due?value=3&unit=days');

        $response->assertOk()->assertJsonPath('count', 1)->assertJsonStructure(['count', 'end_at']);
    }
}
