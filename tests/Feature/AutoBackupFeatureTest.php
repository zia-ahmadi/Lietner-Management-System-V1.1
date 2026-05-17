<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AutoBackupFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_creates_and_updates_auto_backup_after_changes(): void
    {
        $user = User::factory()->create();
        $skill = Deck::create(['user_id' => $user->id, 'name' => 'Auto Backup Skill']);

        $path = storage_path('framework/testing/auto_backups');
        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        }

        config(['auto_backup.path' => $path]);

        $this->actingAs($user)->post('/cards', [
            'deck_id' => $skill->id,
            'front_text' => 'Auto backup card',
            'back_text' => 'Auto answer',
        ])->assertRedirect('/cards');

        $file = $path.DIRECTORY_SEPARATOR.'leitner_auto_backup_user'.$user->id.'.json';
        $this->assertTrue(File::exists($file));

        $initial = json_decode(File::get($file), true);
        $this->assertSame('automatic_backup', $initial['meta']['type']);
        $this->assertCount(1, $initial['cards']);

        $cardId = (int) Card::query()->where('front_text', 'Auto backup card')->value('id');

        $this->actingAs($user)->post('/review-today/'.$cardId, [
            'result' => 'correct',
        ])->assertRedirect('/review-today');

        $afterReview = json_decode(File::get($file), true);
        $this->assertCount(1, $afterReview['reviews']);
        $this->assertSame(2, (int) $afterReview['cards'][0]['box']);
    }
}
