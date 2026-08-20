<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_a_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/photo', [
            'photo' => UploadedFile::fake()->image('me.jpg'),
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();
        $this->assertNotNull($user->avatar_url);
        $this->assertStringContainsString('avatars/', $user->avatar_url);
    }

    public function test_non_image_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/photo', [
            'photo' => UploadedFile::fake()->create('resume.pdf', 100),
        ]);

        $response->assertSessionHasErrors('photo');
        $this->assertNull($user->fresh()->avatar_url);
    }

    public function test_uploading_a_new_photo_deletes_the_previous_locally_stored_one(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/photo', [
            'photo' => UploadedFile::fake()->image('first.jpg'),
        ]);
        $firstPath = str_replace(Storage::disk('public')->url(''), '', $user->fresh()->avatar_url);
        Storage::disk('public')->assertExists($firstPath);

        $this->actingAs($user)->post('/profile/photo', [
            'photo' => UploadedFile::fake()->image('second.jpg'),
        ]);

        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_uploading_a_photo_when_the_current_one_is_a_google_url_does_not_error(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['avatar_url' => 'https://lh3.googleusercontent.com/photo.jpg']);

        $response = $this->actingAs($user)->post('/profile/photo', [
            'photo' => UploadedFile::fake()->image('me.jpg'),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertStringContainsString('avatars/', $user->fresh()->avatar_url);
    }

    public function test_user_can_remove_their_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/photo', [
            'photo' => UploadedFile::fake()->image('me.jpg'),
        ]);
        $path = str_replace(Storage::disk('public')->url(''), '', $user->fresh()->avatar_url);

        $this->actingAs($user)->delete('/profile/photo');

        $this->assertNull($user->fresh()->avatar_url);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_removing_a_google_imported_photo_just_clears_the_url(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['avatar_url' => 'https://lh3.googleusercontent.com/photo.jpg']);

        $this->actingAs($user)->delete('/profile/photo');

        $this->assertNull($user->fresh()->avatar_url);
    }

    public function test_remove_photo_button_only_shows_when_a_photo_is_set(): void
    {
        $withPhoto = User::factory()->create(['avatar_url' => 'https://lh3.googleusercontent.com/photo.jpg']);
        $withoutPhoto = User::factory()->create(['avatar_url' => null]);

        $this->actingAs($withPhoto)->get('/profile')->assertSee('Remove photo');
        $this->actingAs($withoutPhoto)->get('/profile')->assertDontSee('Remove photo');
    }
}
