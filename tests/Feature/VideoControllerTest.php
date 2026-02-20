<?php

use App\Actions\FetchChannelInfoAction;
use App\Actions\FetchYouTubeMetadataAction;
use App\Dtos\YouTubeChannelData;
use App\Dtos\YouTubeFullMetadataData;
use App\Dtos\YouTubeVideoData;
use App\Enums\RecipeGenerationStatus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\{postJson, assertDatabaseHas, actingAs};

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

        $user = \App\Models\User::factory()->create();

        $response = actingAs($user)
            ->postJson('/api/videos/preview', [
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
        $user = \App\Models\User::factory()->create();

        $response = actingAs($user)
            ->postJson('/api/videos/preview', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['video_url']);
    });

    test('returns error when youtube api fails', function () {
        Http::fake([
            'www.googleapis.com/*' => Http::response([], 500),
        ]);

        $user = \App\Models\User::factory()->create();
        $response = actingAs($user)
            ->postJson('/api/videos/preview', [
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'fetch_failed',
            ]);
    });

    test('すでに登録動画がある場合その動画が返される', function () {
        $channel = \App\Models\Channel::factory()->create([
            'channel_id' => 'UC12345',
            'name' => 'Chef Ryuji',
        ]);

        $video = \App\Models\Video::factory()->create([
            'video_id' => 'dQw4w9WgXcQ',
            'title' => 'Delicious Curry',
            'channel_id' => $channel->id,
        ]);

        $user = \App\Models\User::factory()->create();

        $response = actingAs($user)
            ->postJson('/api/videos/preview', [
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'video_id' => $video->video_id,
                    'title' => 'Delicious Curry',
                ]
            ]);
    });

    test('異常：渡されたのがurlではない場合', function () {
        $user = \App\Models\User::factory()->create();

        $response = actingAs($user)
            ->postJson('/api/videos/preview', [
                'video_url' => 'invalid_video_id'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                "message" => 'video urlは、有効なURL形式で指定してください。 (その他、1エラーあり)',
                "errors" => [
                    "video_url" => [
                        "video urlは、有効なURL形式で指定してください。",
                        "YouTubeのURLを入力してください。"
                    ]
                ]
            ]);
    });

    test('異常：ログインしていなければエラー', function () {
        $response = postJson('/api/videos/preview', [
            'video_url'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
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

        $user = \App\Models\User::factory()->create();

        $response = actingAs($user)
            ->postJson('/api/videos', [
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

    test('すでに登録動画がある場合その動画が返される', function () {
        Queue::fake();

        $channel = \App\Models\Channel::factory()->create([
            'channel_id' => 'UC12345',
            'name' => 'Chef Ryuji',
        ]);

        $video = \App\Models\Video::factory()->create([
            'video_id' => 'dQw4w9WgXcQ',
            'title' => 'Delicious Curry',
            'channel_id' => $channel->id,
            'recipe_generation_status' => RecipeGenerationStatus::COMPLETED,
        ]);

        $user = \App\Models\User::factory()->create();

        $response = actingAs($user)
            ->postJson('/api/videos', [
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'video_id' => 'dQw4w9WgXcQ',
                    'title' => 'Delicious Curry',
                    'recipe_generation_status' => RecipeGenerationStatus::COMPLETED->value,
                ]
            ]);
    });

    test('異常：generation_retry_countが規定回数を超えていたらVideoExceptionが投げられる', function () {
        $channel = \App\Models\Channel::factory()->create([
            'channel_id' => 'UC12345',
            'name' => 'Chef Ryuji',
        ]);

        $video = \App\Models\Video::factory()->create([
            'video_id' => 'dQw4w9WgXcQ',
            'title' => 'Delicious Curry',
            'channel_id' => $channel->id,
            'recipe_generation_status' => RecipeGenerationStatus::FAILED,
            'generation_retry_count' => config('services.gemini.retry_count', 2),
        ]);

        $user = \App\Models\User::factory()->create();

        $response = actingAs($user)
            ->postJson('/api/videos', [
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'max_retry_exceeded',
                'message' => 'この動画は生成できません、他の動画を試してください。'
            ]);
    });

    test('異常：ログインしていなければエラー', function () {
        $response = postJson('/api/videos/preview', [
            'video_url'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    });

    test('異常：1分間に同じユーザーから一定回数以上のアクセスで429エラーを返す', function () {
        Queue::fake();

        $videoData = new YouTubeVideoData(
            videoId: 'dQw4w9WgXcQ',
            title: 'Delicious Curry',
            channelName: 'Chef Ryuji',
            channelId: 'UC12345',
            categoryId: '10',
            description: 'This is a description.',
            thumbnailUrl: 'https://example.com/thumb.jpg',
            publishedAt: '2023-01-01T12:00:00Z',
            durationSeconds: 930,
            viewCount: 1000,
            likeCount: 100,
            commentCount: 10,
            topicCategories: [],
        );

        $channelData = new YouTubeChannelData();

        /** @var TestCase $this */
        $this->mock(FetchYouTubeMetadataAction::class)
            ->shouldReceive('execute')
            ->andReturn($videoData);

        $this->mock(FetchChannelInfoAction::class)
            ->shouldReceive('execute')
            ->andReturn($channelData);

        $this->mock(FetchYouTubeMetadataAction::class)
            ->shouldReceive('execute')
            ->andReturn(new YouTubeFullMetadataData(
                $videoData,
                $channelData
            ));

        $user = \App\Models\User::factory()->create();
        RateLimiter::clear('throttle:youtube-api');

        // 2. 高速ループ
        for ($i = 0; $i < 3; $i++) {
            actingAs($user)->postJson('/api/videos', [
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
            ]);
        }

        // 4回目
        actingAs($user)->postJson('/api/videos', [
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
        ])->assertStatus(429);
    });
});
