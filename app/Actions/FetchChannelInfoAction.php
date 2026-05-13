<?php

declare(strict_types=1);

namespace App\Actions;

use App\Dtos\YouTubeChannelData;
use App\Exceptions\YouTubeApiException;
use App\Infrastructure\YouTube\YouTubeApiClient;
use Illuminate\Support\Facades\Log;

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
        } catch (YouTubeApiException $e) {
            // チャンネルが存在しない、またはAPI制限などの場合は空のDTOを返す（呼び出し元を止めない）
            Log::warning('Failed to fetch channel info', ['channel_id' => $channelId, 'error' => $e->getMessage()]);
            return YouTubeChannelData::empty();
        }
    }
}
