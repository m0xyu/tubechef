<?php

namespace App\Actions;

use App\Dtos\YouTubeChannelData;
use App\Exceptions\VideoException;
use App\Infrastructure\YouTube\YouTubeApiClient;

class FetchChannelInfoAction
{
    public function __construct(
        private readonly YouTubeApiClient $youtubeClient
    ) {}

    /**
     * YouTubeチャンネル情報を取得する
     *
     * @param string $channelId
     * @return YouTubeChannelData
     */
    public function execute(string $channelId): YouTubeChannelData
    {
        try {
            $item = $this->youtubeClient->getChannel($channelId);

            return YouTubeChannelData::fromApiResponse($item);
        } catch (VideoException $e) {
            // チャンネルが存在しない、またはAPI制限などの場合は空のDTOを返す（呼び出し元を止めない）
            return YouTubeChannelData::empty();
        }
    }
}
