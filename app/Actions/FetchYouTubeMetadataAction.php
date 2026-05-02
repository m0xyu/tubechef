<?php

namespace App\Actions;

use App\Dtos\YouTubeVideoData;
use App\Enums\Errors\VideoError;
use App\Exceptions\VideoException;
use App\Infrastructure\YouTube\YouTubeApiClient;
use App\ValueObjects\YouTubeVideoId;

class FetchYouTubeMetadataAction
{
    public const PARTS_PREVIEW = ['snippet', 'contentDetails', 'topicDetails'];
    public const PARTS_FULL = ['snippet', 'contentDetails', 'statistics', 'topicDetails'];

    public function __construct(
        private readonly YouTubeApiClient $youtubeClient
    ) {}

    /**
     * 動画のURLからYouTubeのメタデータを取得します。
     *
     * @param YouTubeVideoId $videoId
     * @param array<string> $parts 取得するメタデータのパーツ（デフォルトはプレビュー用）PARTS_PREVIEWまたはPARTS_FULLを使用
     * @return YouTubeVideoData 動画のメタデータ（タイトル、概要、サムネイルなど）
     * @throws VideoException 取得失敗時
     */
    public function execute(YouTubeVideoId $videoId, array $parts = self::PARTS_PREVIEW): YouTubeVideoData
    {
        $item = $this->youtubeClient->getVideos((string)$videoId, $parts);

        if (($item['kind']) !== 'youtube#video') {
            throw new VideoException(VideoError::NOT_A_VIDEO);
        }

        return YouTubeVideoData::fromApiResponse($videoId, $item);
    }
}
