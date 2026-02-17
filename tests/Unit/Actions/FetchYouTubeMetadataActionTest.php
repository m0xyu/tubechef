<?php

use App\Actions\FetchYouTubeMetadataAction;
use App\Dtos\YouTubeVideoData;
use App\Enums\Errors\VideoError;
use App\Exceptions\VideoException;
use Illuminate\Support\Facades\Http;

describe('FetchYouTubeMetadataActionTest', function () {
    test('fetches video info successfully', function () {
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

        $action = new FetchYouTubeMetadataAction();
        $channelInfo = $action->execute('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        expect($channelInfo)->toEqual(new YouTubeVideoData(
            videoId: 'dQw4w9WgXcQ',
            title: 'Delicious Curry',
            channelName: 'Chef Ryuji',
            channelId: 'UC12345',
            categoryId: 10,
            description: 'This is a description.',
            thumbnailUrl: 'https://example.com/thumb.jpg',
            publishedAt: '2023-01-01T12:00:00Z',
            durationSeconds: 930,
            viewCount: 1000,
            likeCount: 100,
            commentCount: 10,
            topicCategories: ['Food', 'Lifestyle (sociology)']
        ));
    });

    test('異常：kindがvideo以外の場合', function () {
        Http::fake([
            '*/youtube/v3/videos*' => Http::response([
                'items' => [[
                    'kind' => 'youtube#channel', // videoじゃない！
                    'id' => 'UC12345',
                ]]
            ], 200)
        ]);

        $action = new FetchYouTubeMetadataAction();

        expect(fn() => $action->execute('https://www.youtube.com/watch?v=dQw4w9WgXcQ'))
            ->toThrow(VideoException::class, VideoError::NOT_A_VIDEO->message());
    });

    test('異常：responseにitemsがない場合', function () {
        Http::fake([
            '*/youtube/v3/videos*' => Http::response([], 200)
        ]);

        $action = new FetchYouTubeMetadataAction();

        expect(fn() => $action->execute('https://www.youtube.com/watch?v=dQw4w9WgXcQ'))
            ->toThrow(VideoException::class, VideoError::FETCH_FAILED->message());
    });

    test('異常：渡されたのがurlではない場合', function () {
        $action = new FetchYouTubeMetadataAction();

        expect(fn() => $action->execute('invalid_video_id'))
            ->toThrow(VideoException::class, VideoError::INVALID_ID->message());
    });
});
