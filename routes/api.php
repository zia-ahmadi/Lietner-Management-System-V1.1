<?php

use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\DeckController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('decks')->group(function (): void {
    Route::get('/', [DeckController::class, 'index']);
    Route::post('/', [DeckController::class, 'store']);
    Route::get('/{deck}', [DeckController::class, 'show']);
    Route::match(['put', 'patch'], '/{deck}', [DeckController::class, 'update']);
    Route::delete('/{deck}', [DeckController::class, 'destroy']);

    Route::get('/{deck}/stats', [DeckController::class, 'stats']);
    Route::get('/{deck}/cards', [CardController::class, 'index']);
    Route::post('/{deck}/cards', [CardController::class, 'store']);
    Route::get('/{deck}/due', [CardController::class, 'due']);
});

Route::match(['put', 'patch'], '/cards/{card}', [CardController::class, 'update']);
Route::delete('/cards/{card}', [CardController::class, 'destroy']);
Route::post('/cards/{card}/review', [ReviewController::class, 'store']);
