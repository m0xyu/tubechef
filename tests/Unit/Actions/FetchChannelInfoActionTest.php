<?php

use App\Actions\FetchChannelInfoAction;
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

        expect($channelInfo)->toEqual([
            'channel_description' => 'This is a channel description.',
            'channel_custom_url' => 'mychannel',
            'channel_thumbnail_url' => 'https://example.com/channel_thumb.jpg',
            'subscriber_count' => '1500',
            'channel_view_count' => '50000',
            'channel_video_count' => '100',
        ]);
    });
});
