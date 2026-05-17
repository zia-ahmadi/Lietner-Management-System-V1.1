<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardsAndReviewTodayTest extends TestCase
{
    use RefreshDatabase;

    public function test_cards_page_lists_only_authenticated_user_cards_with_dates(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $userDeck = Deck::create(['user_id' => $user->id, 'name' => 'User Deck']);
        $otherDeck = Deck::create(['user_id' => $other->id, 'name' => 'Other Deck']);

        Card::create([
            'deck_id' => $userDeck->id,
            'front_text' => 'User Front',
            'back_text' => 'User Back',
            'next_review_at' => now()->subMinute(),
        ]);

        Card::create([
            'deck_id' => $otherDeck->id,
            'front_text' => 'Other Front',
            'back_text' => 'Other Back',
            'next_review_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->get('/cards');

        $response->assertOk();
        $response->assertSee('User Front');
        $response->assertDontSee('Other Front');
        $response->assertSee('Created:');
    }

    public function test_review_today_updates_card_and_redirects(): void
    {
        $user = User::factory()->create();
        $deck = Deck::create(['user_id' => $user->id, 'name' => 'Deck']);

        $card = Card::create([
            'deck_id' => $deck->id,
            'front_text' => 'Question',
            'back_text' => 'Answer',
            'box' => 1,
            'review_streak' => 0,
            'next_review_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->post('/review-today/'.$card->id, [
            'result' => 'correct',
        ]);

        $response->assertRedirect('/review-today');
        $this->assertDatabaseHas('reviews', [
            'card_id' => $card->id,
            'result' => 'correct',
            'box_before' => 1,
            'box_after' => 2,
        ]);
    }

    public function test_wrong_review_resets_card_and_schedules_tomorrow(): void
    {
        $user = User::factory()->create();
        $deck = Deck::create(['user_id' => $user->id, 'name' => 'Deck']);

        $card = Card::create([
            'deck_id' => $deck->id,
            'front_text' => 'Question',
            'back_text' => 'Answer',
            'box' => 4,
            'review_streak' => 3,
            'next_review_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)->post('/review-today/'.$card->id, [
            'result' => 'wrong',
        ])->assertRedirect('/review-today');

        $card->refresh();
        $this->assertSame(1, $card->box);
        $this->assertSame(0, $card->review_streak);
        $this->assertTrue($card->next_review_at->isSameDay(now()->addDay()));
    }

    public function test_newly_created_card_is_visible_in_review_today(): void
    {
        $user = User::factory()->create();
        $deck = Deck::create(['user_id' => $user->id, 'name' => 'Skill']);

        $this->actingAs($user)->post('/cards', [
            'deck_id' => $deck->id,
            'front_text' => 'New card for day 1',
            'back_text' => 'Answer',
        ])->assertRedirect('/cards');

        $response = $this->actingAs($user)->get('/review-today');
        $response->assertOk();
        $response->assertSee('New card for day 1');
    }
}
