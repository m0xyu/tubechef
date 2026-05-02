<?php

use App\Infrastructure\YouTube\YouTubeApiClient;
use Illuminate\Support\Facades\Http;

describe('YouTubeApiClient', function () {
    beforeEach(function () {
        $this->client = new YouTubeApiClient(
            baseUrl: 'https://www.googleapis.com/youtube/v3',
            apiKey: 'dummy',
        );
    });

    test('getVideos returns expected array', function () {
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
                        'topicCategories' => [
                            'https://en.wikipedia.org/wiki/Food',
                            'https://en.wikipedia.org/wiki/Lifestyle_(sociology)'
                        ],
                    ]
                ]]
            ], 200)
        ]);

        $result = $this->client->getVideos('dQw4w9WgXcQ', ['snippet']);
        expect($result['kind'])->toBe('youtube#video');
        expect($result['id'])->toBe('dQw4w9WgXcQ');
        expect($result['snippet']['title'])->toBe('Delicious Curry');
    });

    test('getChannels returns expected array', function () {
        Http::fake([
            '*/youtube/v3/channels*' => Http::response([
                'items' => [[
                    'snippet' => [
                        'description' => 'This is a channel description.',
                        'customUrl' => 'mychannel',
                        'thumbnails' => [
                            'high' => ['url' => 'https://example.com/channel_thumb.jpg'],
                            'default' => ['url' => 'https://example.com/channel_thumb_default.jpg'],
                        ],
                    ],
                    'statistics' => [
                        'subscriberCount' => '1500',
                        'viewCount' => '50000',
                        'videoCount' => '100',
                    ],
                ]]
            ], 200),
        ]);

        $response = $this->client->getChannels(
            channelId: 'UC12345'
        );
        expect($response['snippet']['description'])->toBe('This is a channel description.');
        expect($response['snippet']['customUrl'])->toBe('mychannel');
        expect($response['statistics']['subscriberCount'])->toBe('1500');
        expect($response['statistics']['viewCount'])->toBe('50000');
        expect($response['statistics']['videoCount'])->toBe('100');
    });
});
