<?php

use App\Actions\FetchChannelInfoAction;
use App\Dtos\YouTubeChannelData;
use Illuminate\Support\Facades\Http;

describe('FetchChannelInfoAction', function () {
    test('fetches channel info successfully', function () {
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

        $action = new FetchChannelInfoAction();
        $channelInfo = $action->execute('UC12345');

        expect($channelInfo)->toEqual(new YouTubeChannelData(
            channelDescription: 'This is a channel description.',
            channelCustomUrl: 'mychannel',
            channelThumbnailUrl: 'https://example.com/channel_thumb.jpg',
            subscriberCount: 1500,
            channelViewCount: 50000,
            channelVideoCount: 100
        ));
    });

    test('returns empty DTO when api fails', function () {
        Http::fake([
            '*/youtube/v3/channels*' => Http::response([], 404),
        ]);

        $action = new FetchChannelInfoAction();
        $channelInfo = $action->execute('invalid_id');

        expect($channelInfo)->toEqual(YouTubeChannelData::empty());
        expect($channelInfo->channelDescription)->toBeNull();
    });
});
