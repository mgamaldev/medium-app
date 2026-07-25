<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_generate_presigned_url_for_avatar(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/avatars/presigned-url', [
            'file_name' => 'avatar.png',
            'content_type' => 'image/png',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'upload_url',
            'form_fields',
            'file_key',
        ]);

        $this->assertStringStartsWith('avatars/', $response->json('file_key'));
    }

    public function test_confirm_avatar_endpoint_updates_user_and_deletes_old_avatar(): void
    {
        Storage::fake('s3');

        $oldAvatarKey = 'avatars/old-photo.png';
        Storage::disk('s3')->put($oldAvatarKey, 'fake content');

        /** @var User $user */
        $user = User::factory()->create(['avatar' => $oldAvatarKey]);
        $this->actingAs($user);

        $newAvatarKey = 'avatars/new-photo.png';

        $response = $this->postJson('/api/avatars/confirm', [
            'file_key' => $newAvatarKey,
        ]);

        $response->assertStatus(200);

        $this->assertEquals($newAvatarKey, $user->fresh()->avatar);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('s3');
        $disk->assertMissing($oldAvatarKey);
    }

    public function test_confirm_avatar_endpoint_rejects_invalid_file_prefix(): void
    {
        Storage::fake('s3');

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $invalidCoverKey = 'covers/some-article-image.jpg';

        $response = $this->postJson('/api/avatars/confirm', [
            'file_key' => $invalidCoverKey,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file_key']);
    }
}
