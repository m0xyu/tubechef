<?php

use App\Enums\RecipeGenerationStatus;
use App\Jobs\GenerateRecipeJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;

describe('Video Controller: preview', function () {
    test('valid url returns video metadata successfully', function () {
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'items' => [[
                    'kind' => 'youtube#video',
                    'id' => 'dQw4w9WgXcQ',
                    'snippet' => [
                        'title' => 'Delicious Curry',
                        'channelTitle' => 'Chef Ryuji',
                        'channelId' => 'UC12345',
                        'categoryId' => '10',
                        'description' => 'This is a description.',
                        'thumbnails' => [
                            'high' => ['url' => 'https://example.com/thumb.jpg']
                        ],
                        'publishedAt' => '2023-01-01T12:00:00Z',
                    ],
                    'contentDetails' => [
                        'duration' => 'PT15M30S', // 15分30秒
                    ],
                ]]
            ], 200),
        ]);

        $response = postJson('/api/videos/preview', [
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Delicious Curry',
                    'duration' => 930,
                ]
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'video_id',
                    'title',
                    'description',
                    'thumbnail_url',
                    'published_at',
                    'duration',
                    'channel' => [
                        'id',
                        'name',
                    ]
                ]
            ]);
    });

    test('validation fails when video_url is missing', function () {
        // 空のデータを送る
        $response = postJson('/api/videos/preview', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['video_url']);
    });

    test('returns error when youtube api fails', function () {
        Http::fake([
            'www.googleapis.com/*' => Http::response([], 500),
        ]);

        $response = postJson('/api/videos/preview', [
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'fetch_failed',
            ]);
    });
});

describe('Video Controller: store', function () {
    test('valid url stores video metadata successfully', function () {
        Queue::fake();

        Http::fake([
            '*/youtube/v3/videos*' => Http::response([
                'items' => [[
                    'kind' => 'youtube#video',
                    'id' => 'dQw4w9WgXcQ',
                    'snippet' => [
                        'title' => 'Delicious Curry',
                        'channelTitle' => 'Chef Ryuji',
                        'channelId' => 'UC12345',
                        'categoryId' => '10',
                        'description' => 'This is a description.',
                        'thumbnails' => [
                            'high' => ['url' => 'https://example.com/thumb.jpg']
                        ],
                        'publishedAt' => '2023-01-01T12:00:00Z',
                    ],
                    'contentDetails' => [
                        'duration' => 'PT15M30S', // 15分30秒
                    ],
                    'statistics' => [
                        'viewCount' => '1000',
                        'likeCount' => '100',
                        'commentCount' => '10',
                    ],
                    'topicDetails' => [
                        'topicCategories' => ['https://en.wikipedia.org/wiki/Food']
                    ]
                ]]
            ], 200),

            '*/youtube/v3/channels*' => Http::response([
                'items' => [[
                    'id' => 'UC12345',
                    'kind' => 'youtube#channel',
                    'snippet' => [
                        'title' => 'Chef Ryuji',
                        'description' => 'Channel Description',
                        'customUrl' => '@ryuji',
                        'thumbnails' => ['high' => ['url' => 'http://img.com/c.jpg']],
                    ],
                    'statistics' => [
                        'subscriberCount' => '5000000',
                        'viewCount' => '100000000',
                        'videoCount' => '500',
                    ]
                ]]
            ], 200),
        ]);

        $response = postJson('/api/videos', [
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Delicious Curry',
                    'duration' => 930,
                ]
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'video_id',
                    'title',
                    'description',
                    'thumbnail_url',
                    'published_at',
                    'duration',
                    'view_count',
                    'like_count',
                    'comment_count',
                    'topic_categories',
                    'recipe_generation_status',
                    'recipe_generation_status_message',
                    'channel' =>  [
                        'channel_id',
                        'name',
                        'custom_url',
                        'thumbnail_url',
                    ]
                ]
            ]);

        assertDatabaseHas('channels', [
            'channel_id' => 'UC12345',
            'name' => 'Chef Ryuji',
            'subscriber_count' => 5000000,
        ]);

        assertDatabaseHas('videos', [
            'video_id' => 'dQw4w9WgXcQ',
            'title' => 'Delicious Curry',
            'view_count' => 1000,
            'duration' => 930,
            'recipe_generation_status' => RecipeGenerationStatus::PROCESSING->value,
        ]);
    });
});
