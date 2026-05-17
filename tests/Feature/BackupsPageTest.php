<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Deck;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_backup_and_see_it_in_list(): void
    {
        $user = User::factory()->create();
        Deck::create(['user_id' => $user->id, 'name' => 'Skill A']);

        $path = storage_path('framework/testing/backups_user_'.$user->id);
        File::ensureDirectoryExists($path);

        $this->actingAs($user)->post('/backups', [
            'backup_path' => $path,
        ])->assertRedirect();

        $files = collect(File::files($path))->filter(fn ($f) => str_starts_with($f->getFilename(), 'leitner_backup_'));
        $this->assertTrue($files->isNotEmpty());

        $response = $this->actingAs($user)->get('/backups?path='.urlencode($path));
        $response->assertOk();
        $response->assertSee('Recent Backups');
        $response->assertSee('leitner_backup_');
    }

    public function test_user_can_restore_data_from_uploaded_backup_file(): void
    {
        $user = User::factory()->create();

        $oldSkill = Deck::create(['user_id' => $user->id, 'name' => 'Old Skill']);
        Card::create([
            'deck_id' => $oldSkill->id,
            'front_text' => 'Old card',
            'back_text' => 'Old answer',
            'next_review_at' => now(),
        ]);
        Material::create([
            'user_id' => $user->id,
            'title' => 'Old material',
        ]);

        $payload = [
            'meta' => ['user_id' => $user->id],
            'skills' => [
                ['id' => 101, 'name' => 'Restored Skill', 'description' => 'Imported'],
            ],
            'cards' => [
                ['id' => 501, 'deck_id' => 101, 'front_text' => 'Restored card', 'back_text' => 'Restored answer', 'box' => 2, 'review_streak' => 1, 'next_review_at' => now()->toDateTimeString()],
            ],
            'reviews' => [],
            'materials' => [
                ['title' => 'Restored material', 'category' => 'General', 'content' => 'Imported notes', 'resource_url' => ''],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('restore_backup.json', json_encode($payload));

        $this->actingAs($user)->post('/backups/restore', [
            'restore_file' => $file,
            'backup_path' => storage_path('framework/testing/backups_user_'.$user->id),
        ])->assertRedirect();

        $this->assertDatabaseMissing('decks', ['name' => 'Old Skill']);
        $this->assertDatabaseMissing('cards', ['front_text' => 'Old card']);
        $this->assertDatabaseMissing('materials', ['title' => 'Old material']);

        $this->assertDatabaseHas('decks', ['user_id' => $user->id, 'name' => 'Restored Skill']);
        $this->assertDatabaseHas('cards', ['front_text' => 'Restored card']);
        $this->assertDatabaseHas('materials', ['user_id' => $user->id, 'title' => 'Restored material']);
    }
}
