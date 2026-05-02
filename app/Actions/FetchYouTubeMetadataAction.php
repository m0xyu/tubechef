<?php

namespace App\Actions;

use App\Dtos\YouTubeVideoData;
use App\Enums\Errors\VideoError;
use App\Exceptions\VideoException;
use App\ValueObjects\YouTubeVideoId;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class FetchYouTubeMetadataAction
{
    public const PARTS_PREVIEW = ['snippet', 'contentDetails', 'topicDetails'];
    public const PARTS_FULL = ['snippet', 'contentDetails', 'statistics', 'topicDetails'];

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
        $item = $this->fetchVideoMetadata($videoId, $parts);
        return $this->toDto($videoId, $item);
    }

    /**
     * APIリクエストとレスポンスバリデーション
     * @param YouTubeVideoId $videoId
     * @param array<string> $parts
     * @return array<string, mixed>
     * @throws VideoException
     */
    protected function fetchVideoMetadata(YouTubeVideoId $videoId, array $parts): array
    {
        $baseUrlRaw = config('services.youtube.base_url');
        $baseUrl = is_string($baseUrlRaw) ? $baseUrlRaw : '';
        $apiKeyRaw = config('services.google.api_key');
        $apiKey = is_string($apiKeyRaw) ? $apiKeyRaw : '';

        $response = Http::get("{$baseUrl}/videos", [
            'part' => implode(',', $parts),
            'id' => (string)$videoId,
            'key' => $apiKey,
        ]);

        if ($response->failed() || empty($response->json('items'))) {
            throw new VideoException(VideoError::FETCH_FAILED);
        }

        $item = $response->json('items.0');
        if (!is_array($item)) {
            throw new VideoException(VideoError::FETCH_FAILED);
        }
        if (($item['kind'] ?? null) !== 'youtube#video') {
            throw new VideoException(VideoError::NOT_A_VIDEO);
        }
        // array<string, mixed> になるようkeyをstringに限定
        $result = [];
        foreach ($item as $k => $v) {
            if (is_string($k)) {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    /**
     * 配列からDTOへ変換
     * @param YouTubeVideoId $videoId
     * @param array<string, mixed> $item
     * @return YouTubeVideoData
     */
    protected function toDto(YouTubeVideoId $videoId, array $item): YouTubeVideoData
    {
        $snippet = is_array($item['snippet'] ?? null) ? $item['snippet'] : [];
        $details = is_array($item['contentDetails'] ?? null) ? $item['contentDetails'] : [];
        $statistics = is_array($item['statistics'] ?? null) ? $item['statistics'] : [];
        $topicDetails = is_array($item['topicDetails'] ?? null) ? $item['topicDetails'] : [];

        // Arr::get を活用して深い階層のデータへ安全にアクセス（ネストの解消）
        $title = Arr::get($snippet, 'title');
        $channelTitle = Arr::get($snippet, 'channelTitle');
        $channelId = Arr::get($snippet, 'channelId');
        $categoryId = Arr::get($snippet, 'categoryId');
        $description = Arr::get($snippet, 'description');
        $publishedAt = Arr::get($snippet, 'publishedAt');

        $thumbHigh = Arr::get($snippet, 'thumbnails.high.url');
        $thumbDefault = Arr::get($snippet, 'thumbnails.default.url');
        $thumbnailUrl = is_string($thumbHigh) ? $thumbHigh : (is_string($thumbDefault) ? $thumbDefault : null);

        $durationRaw = Arr::get($details, 'duration');
        $durationSeconds = $this->convertDurationToSeconds(is_string($durationRaw) ? $durationRaw : null);

        $viewCount = Arr::get($statistics, 'viewCount');
        $likeCount = Arr::get($statistics, 'likeCount');
        $commentCount = Arr::get($statistics, 'commentCount');

        $topicCategoriesRaw = Arr::get($topicDetails, 'topicCategories', []);
        $topicCategories = is_array($topicCategoriesRaw) ? array_values(array_filter($topicCategoriesRaw, 'is_string')) : [];
        $cleanTags = $this->extractTopicNames($topicCategories);

        // 厳格な型チェックとキャストを実行
        $resultArray = [
            'video_id' => (string)$videoId,
            'title' => is_string($title) ? $title : '',
            'channel_name' => is_string($channelTitle) ? $channelTitle : null,
            'channel_id' => is_string($channelId) ? $channelId : '',
            'category_id' => is_numeric($categoryId) ? (int)$categoryId : null,
            'description' => is_string($description) ? $description : null,
            'thumbnail_url' => $thumbnailUrl,
            'published_at' => is_string($publishedAt) ? $publishedAt : null,
            'duration' => is_int($durationSeconds) ? $durationSeconds : 0,
            'view_count' => is_numeric($viewCount) ? (int)$viewCount : null,
            'like_count' => is_numeric($likeCount) ? (int)$likeCount : null,
            'comment_count' => is_numeric($commentCount) ? (int)$commentCount : null,
            'topic_categories' => $cleanTags,
        ];

        return YouTubeVideoData::fromArray($resultArray);
    }

    /**
     * WikipediaのURLリストから、トピック名だけを抽出する
     * 例: https://en.wikipedia.org/wiki/Food -> "Food"
     * 
     * @param array<string> $urls WikipediaのURLリスト
     * @return array<string> 抽出されたトピック名のリスト
     */
    private function extractTopicNames(array $urls): array
    {
        return array_map(function ($url) {
            $basename = basename($url);
            $decoded = urldecode($basename);

            return str_replace('_', ' ', $decoded);
        }, $urls);
    }

    /**
     * ISO 8601 形式 (PT1H2M10S) を秒数 (3730) に変換する
     * @param string|null $isoDuration ISO 8601 形式の期間文字列
     * @return int|null 秒数、または null (無効な場合)
     */
    private function convertDurationToSeconds(?string $isoDuration): ?int
    {
        if (!$isoDuration) {
            return null;
        }

        try {
            $interval = new \DateInterval($isoDuration);
            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (Exception $e) {
            return null;
        }
    }
}
