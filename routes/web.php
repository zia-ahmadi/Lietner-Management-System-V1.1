<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Web\BackupController;
use App\Http\Controllers\Web\StudyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StudyController::class, 'home'])->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [StudyController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/upcoming-due', [StudyController::class, 'upcomingDueForecast'])->name('dashboard.upcoming-due');

    Route::get('/skills', [StudyController::class, 'skills'])->name('skills.index');
    Route::post('/skills', [StudyController::class, 'storeSkill'])->name('skills.store');
    Route::get('/skills/{skill}/edit', [StudyController::class, 'editSkill'])->name('skills.edit');
    Route::patch('/skills/{skill}', [StudyController::class, 'updateSkill'])->name('skills.update');
    Route::delete('/skills/{skill}', [StudyController::class, 'destroySkill'])->name('skills.destroy');
    Route::post('/decks', [StudyController::class, 'storeSkill'])->name('decks.store');

    Route::get('/cards', [StudyController::class, 'cards'])->name('cards.index');
    Route::post('/cards', [StudyController::class, 'storeCard'])->name('cards.store');
    Route::patch('/cards/{card}', [StudyController::class, 'updateCard'])->name('cards.update');
    Route::delete('/cards/{card}', [StudyController::class, 'destroyCard'])->name('cards.destroy');
    Route::post('/cards/{card}/restart', [StudyController::class, 'restartCardReview'])->name('cards.restart');

    Route::get('/review-today', [StudyController::class, 'reviewToday'])->name('review.today');
    Route::post('/review-today/{card}', [StudyController::class, 'submitTodayReview'])->name('review.submit');

    Route::get('/materials', [StudyController::class, 'materials'])->name('materials');
    Route::post('/materials', [StudyController::class, 'storeMaterial'])->name('materials.store');

    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::post('/backups/restore', [BackupController::class, 'restore'])->name('backups.restore');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Management tools: clear cards for a skill and reset user data
    Route::get('/manage', [StudyController::class, 'manage'])->name('manage.index');
    Route::post('/manage/clear-skill/{skill}', [StudyController::class, 'clearSkill'])->name('manage.clear_skill');
    Route::post('/manage/reset', [StudyController::class, 'resetApp'])->name('manage.reset');
});
