<?php

use App\Models\Card;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('leitner:due {deckId?}', function (?int $deckId = null) {
    $query = Card::query()->due();

    if ($deckId !== null) {
        $query->where('deck_id', $deckId);
    }

    $totalDue = $query->count();

    $this->info("Due cards: {$totalDue}");

    if ($deckId !== null) {
        $this->line("Deck ID: {$deckId}");
    }
})->purpose('Show due Leitner cards, optionally by deck ID.');
