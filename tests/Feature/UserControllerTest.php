<?php

use App\Models\Recipe;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\deleteJson;

uses(RefreshDatabase::class);

describe('User Library', function () {

    it('returns a collection of history videos for the authenticated user', function () {
        $user = User::factory()->create();
        $videos = Video::factory()
            ->count(3)
            ->has(Recipe::factory())
            ->create();

        $user->historyVideos()->attach($videos->pluck('id'));

        actingAs($user)
            ->getJson('/api/user/library')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'video_id',
                        'title',
                        'channel' => ['name', 'id'],
                        'recipe_slug'
                    ]
                ]
            ]);
    });

    it('can delete a video from the user history', function () {
        // 1. 準備
        $video = Video::factory()->create(['video_id' => 'v123']);
        $user = User::factory()->create();
        $user->historyVideos()->attach($video->id);

        // 2. 実行
        actingAs($user)
            ->deleteJson("/api/user/library/v123")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        assertDatabaseMissing('video_user', [
            'user_id' => $user->id,
            'video_id' => $video->id,
        ]);
    });

    it('prevents a user from deleting another users history', function () {
        $otherUser = User::factory()->create();
        $video = Video::factory()->create(['video_id' => 'secret-video']);
        $otherUser->historyVideos()->attach($video->id);
        $user = User::factory()->create();

        actingAs($user)
            ->deleteJson("/api/user/library/secret-video")
            ->assertStatus(403);
    });

    it('returns 404 when deleting a non-existent video', function () {
        $user = User::factory()->create();
        actingAs($user)
            ->deleteJson("/api/user/library/non-existent-id")
            ->assertStatus(404);
    });
});
