<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Deck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeitnerFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_deck_card_and_review_flow(): void
    {
        $deckResponse = $this->postJson('/api/decks', [
            'name' => 'Arabic Vocabulary',
            'description' => 'Daily memorization deck',
        ]);

        $deckResponse->assertCreated();
        $deckId = (int) $deckResponse->json('id');

        $cardResponse = $this->postJson("/api/decks/{$deckId}/cards", [
            'front_text' => 'kitab',
            'back_text' => 'book',
        ]);

        $cardResponse->assertCreated();
        $cardId = (int) $cardResponse->json('id');

        $reviewResponse = $this->postJson("/api/cards/{$cardId}/review", [
            'result' => 'correct',
            'quality' => 5,
        ]);

        $reviewResponse->assertCreated();
        $reviewResponse->assertJsonPath('card.box', 2);

        $this->assertDatabaseHas('reviews', [
            'card_id' => $cardId,
            'result' => 'correct',
            'box_before' => 1,
            'box_after' => 2,
        ]);
    }

    public function test_due_endpoint_returns_only_due_cards(): void
    {
        $deck = Deck::create([
            'name' => 'Computer Science',
        ]);

        Card::create([
            'deck_id' => $deck->id,
            'front_text' => 'TCP',
            'back_text' => 'Transport protocol',
            'box' => 1,
            'review_streak' => 0,
            'next_review_at' => now()->subHour(),
        ]);

        Card::create([
            'deck_id' => $deck->id,
            'front_text' => 'UDP',
            'back_text' => 'Connectionless protocol',
            'box' => 1,
            'review_streak' => 0,
            'next_review_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/decks/{$deck->id}/due");

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.front_text', 'TCP');
    }
}
