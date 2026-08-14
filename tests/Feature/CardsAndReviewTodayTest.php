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

    public function test_review_today_can_be_filtered_to_a_specific_skill(): void
    {
        $user = User::factory()->create();
        $firstDeck = Deck::create(['user_id' => $user->id, 'name' => 'First Skill']);
        $secondDeck = Deck::create(['user_id' => $user->id, 'name' => 'Second Skill']);

        Card::create([
            'deck_id' => $firstDeck->id,
            'front_text' => 'First skill question',
            'back_text' => 'Answer one',
            'box' => 1,
            'review_streak' => 0,
            'next_review_at' => now()->subMinute(),
        ]);

        Card::create([
            'deck_id' => $secondDeck->id,
            'front_text' => 'Second skill question',
            'back_text' => 'Answer two',
            'box' => 1,
            'review_streak' => 0,
            'next_review_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->get('/review-today?deck_id='.$firstDeck->id);

        $response->assertOk();
        $response->assertSee('First skill question');
        $response->assertDontSee('Second skill question');
    }

    public function test_cards_page_prioritizes_recently_used_skills_in_skill_picker(): void
    {
        $user = User::factory()->create();
        $oldDeck = Deck::create(['user_id' => $user->id, 'name' => 'Legacy Skill']);
        $recentDeck = Deck::create(['user_id' => $user->id, 'name' => 'Recently Used Skill']);

        Card::create([
            'deck_id' => $oldDeck->id,
            'front_text' => 'Old skill card',
            'back_text' => 'Answer',
            'box' => 1,
            'review_streak' => 0,
            'next_review_at' => now()->subWeek(),
            'created_at' => now()->subWeek(),
            'updated_at' => now()->subWeek(),
        ]);

        Card::create([
            'deck_id' => $recentDeck->id,
            'front_text' => 'Recent skill card',
            'back_text' => 'Answer',
            'box' => 1,
            'review_streak' => 0,
            'next_review_at' => now()->subMinute(),
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->get('/cards');

        $response->assertOk();
        $response->assertSeeInOrder(['Recently Used Skill', 'Legacy Skill']);
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

    public function test_card_content_uses_automatic_text_direction_in_list_review_and_edit_views(): void
    {
        $user = User::factory()->create();
        $deck = Deck::create(['user_id' => $user->id, 'name' => 'Language Skill']);

        Card::create([
            'deck_id' => $deck->id,
            'front_text' => 'این یک API test است',
            'back_text' => 'پاسخ با Laravel',
            'next_review_at' => now()->subMinute(),
        ]);

        $cardsResponse = $this->actingAs($user)->get('/cards');
        $cardsResponse->assertOk();
        $cardsResponse->assertSee('dir="auto"', false);
        $cardsResponse->assertSee('unicode-bidi: plaintext', false);

        $reviewResponse = $this->actingAs($user)->get('/review-today');
        $reviewResponse->assertOk();
        $reviewResponse->assertSee('dir="auto"', false);
    }
}
