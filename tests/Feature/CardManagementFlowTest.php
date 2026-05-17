<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_card_without_deck_and_without_answer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cards', [
            'front_text' => 'OSI model layers',
        ]);

        $response->assertRedirect('/cards');
        $this->assertDatabaseHas('decks', [
            'user_id' => $user->id,
            'name' => 'General Skill',
        ]);
        $this->assertDatabaseHas('cards', [
            'front_text' => 'OSI model layers',
            'back_text' => '',
        ]);
    }

    public function test_user_can_edit_restart_and_delete_card(): void
    {
        $user = User::factory()->create();
        $deck = Deck::create(['user_id' => $user->id, 'name' => 'Networks']);

        $card = Card::create([
            'deck_id' => $deck->id,
            'front_text' => 'Old prompt',
            'back_text' => 'Old answer',
            'box' => 3,
            'review_streak' => 2,
            'next_review_at' => now()->addDay(),
        ]);

        $this->actingAs($user)->patch('/cards/'.$card->id, [
            'deck_id' => $deck->id,
            'front_text' => 'New prompt',
            'back_text' => 'New answer',
        ])->assertRedirect('/cards');

        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
            'front_text' => 'New prompt',
            'back_text' => 'New answer',
        ]);

        $this->actingAs($user)->post('/cards/'.$card->id.'/restart')->assertRedirect('/cards');

        $card->refresh();
        $this->assertSame(1, $card->box);
        $this->assertSame(0, $card->review_streak);
        $this->assertTrue($card->next_review_at->isSameDay(now()));

        $this->actingAs($user)->delete('/cards/'.$card->id)->assertRedirect('/cards');
        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    public function test_user_cannot_manage_other_user_card(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $deck = Deck::create(['user_id' => $owner->id, 'name' => 'Owner Deck']);

        $card = Card::create([
            'deck_id' => $deck->id,
            'front_text' => 'Owner card',
            'back_text' => 'Owner answer',
            'next_review_at' => now(),
        ]);

        $this->actingAs($intruder)->patch('/cards/'.$card->id, [
            'front_text' => 'Hacked',
        ])->assertNotFound();

        $this->actingAs($intruder)->post('/cards/'.$card->id.'/restart')->assertNotFound();
        $this->actingAs($intruder)->delete('/cards/'.$card->id)->assertNotFound();
    }

    public function test_cards_page_supports_search_filter_and_sort(): void
    {
        $user = User::factory()->create();
        $deckA = Deck::create(['user_id' => $user->id, 'name' => 'Algorithms']);
        $deckB = Deck::create(['user_id' => $user->id, 'name' => 'Databases']);

        Card::create([
            'deck_id' => $deckA->id,
            'front_text' => 'Binary search',
            'back_text' => 'Divide and conquer',
            'box' => 2,
            'next_review_at' => now()->subMinute(),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        Card::create([
            'deck_id' => $deckB->id,
            'front_text' => 'Normalization',
            'back_text' => 'Database normal forms',
            'box' => 5,
            'next_review_at' => now()->addDay(),
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        $search = $this->actingAs($user)->get('/cards?q=Binary');
        $search->assertOk();
        $search->assertSee('Binary search');
        $search->assertDontSee('Normalization');

        $deckFiltered = $this->actingAs($user)->get('/cards?deck_id='.$deckB->id);
        $deckFiltered->assertOk();
        $deckFiltered->assertSee('Normalization');
        $deckFiltered->assertDontSee('Binary search');

        $dueOnly = $this->actingAs($user)->get('/cards?due=due');
        $dueOnly->assertOk();
        $dueOnly->assertSee('Binary search');
        $dueOnly->assertDontSee('Normalization');

        $sortHighBox = $this->actingAs($user)->get('/cards?sort=box_high');
        $sortHighBox->assertOk();
        $sortHighBox->assertSeeInOrder(['Normalization', 'Binary search']);
    }
}
