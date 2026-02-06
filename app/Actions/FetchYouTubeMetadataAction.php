<?php

namespace App\Actions;

use Exception;
use Illuminate\Support\Facades\Http;

class FetchYouTubeMetadataAction
{
    public const PARTS_PREVIEW = ['snippet', 'contentDetails'];
    public const PARTS_FULL = ['snippet', 'contentDetails', 'statistics', 'topicDetails'];

    /**
     * 動画のURLまたはIDからYouTubeのメタデータを取得します。
     *
     * @param string $urlOrId YouTubeのURLまたは動画ID
     * @param array $parts 取得するメタデータのパーツ（デフォルトはプレビュー用）PARTS_PREVIEWまたはPARTS_FULLを使用
     * @return array 動画のメタデータ（タイトル、概要、サムネイルなど）
     * @throws Exception 取得失敗時
     */
    public function execute(string $urlOrId, array $parts = self::PARTS_PREVIEW): array
    {
        $videoId = $this->extractVideoId($urlOrId);

        $apiKey = config('services.google.api_key');
        $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => implode(',', $parts),
            'id' => $videoId,
            'key' => $apiKey,
        ]);

        if ($response->failed() || empty($response->json('items'))) {
            throw new Exception('Failed to fetch YouTube metadata.');
        }

        $item = $response->json('items.0');

        $kind = $item['kind'] ?? null;
        if ($kind !== 'youtube#video') {
            throw new Exception('The provided ID does not correspond to a YouTube video.');
        }

        $snippet = $item['snippet'] ?? [];
        $details = $item['contentDetails'] ?? [];
        $statistics = $item['statistics'] ?? [];
        $topicDetails = $item['topicDetails'] ?? [];

        $cleanTags = $this->extractTopicNames($topicDetails['topicCategories'] ?? []);
        $durationSeconds = $this->convertDurationToSeconds($details['duration'] ?? null);

        return [
            'video_id' => $videoId,
            'title' => $snippet['title'] ?? null,
            'channel_name' => $snippet['channelTitle'] ?? null,
            'channel_id' => $snippet['channelId'] ?? null,
            'category_id' => $snippet['categoryId'] ?? null,
            'description' => $snippet['description'] ?? null,
            'thumbnail_url' => $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['default']['url'] ?? null,
            'published_at' => $snippet['publishedAt'] ?? null,
            'duration' => $durationSeconds,
            'view_count' => $statistics['viewCount'] ?? null,
            'like_count' => $statistics['likeCount'] ?? null,
            'comment_count' => $statistics['commentCount'] ?? null,
            'topic_categories' => $cleanTags,
        ];
    }

    /**
     * URLから動画IDを抽出する
     * 対応形式: 
     * - https://www.youtube.com/watch?v=dQw4w9WgXcQ
     * - https://youtu.be/dQw4w9WgXcQ
     * - dQw4w9WgXcQ (ID直接)
     */
    private function extractVideoId(string $urlOrId): string
    {
        if (preg_match('/^[\w-]{11}$/', $urlOrId)) {
            return $urlOrId;
        }

        if (preg_match('/(?:v=|\/)([\w-]{11})(?:&|\?|\/|$)/', $urlOrId, $matches)) {
            return $matches[1];
        }

        throw new Exception('有効なYouTube URLまたは動画IDではありません。');
    }

    /**
     * WikipediaのURLリストから、トピック名だけを抽出する
     * 例: https://en.wikipedia.org/wiki/Lifestyle_(sociology) -> "Lifestyle (sociology)"
     * 
     * @param array $urls WikipediaのURLリスト
     * @return array 抽出されたトピック名のリスト
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
