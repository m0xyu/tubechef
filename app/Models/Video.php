<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $channel_id
 * @property string $video_id
 * @property string $title
 * @property string|null $description
 * @property string|null $thumbnail_url
 * @property \Illuminate\Support\Carbon $published_at
 * @property int|null $view_count
 * @property int|null $like_count
 * @property int|null $comment_count
 * @property array<array-key, mixed>|null $topic_categories
 * @property \Illuminate\Support\Carbon $fetched_at
 * @property int|null $duration 動画の長さ（秒）
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Channel $channel
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereCommentCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereFetchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereLikeCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereThumbnailUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereTopicCategories($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereVideoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Video whereViewCount($value)
 * @mixin \Eloquent
 */
class Video extends Model
{
    protected $fillable = [
        'video_id',
        'title',
        'description',
        'thumbnail_url',
        'view_count',
        'like_count',
        'comment_count',
        'topic_categories',
        'channel_id',
        'duration',
        'published_at',
        'fetched_at',
    ];

    protected $casts = [
        'topic_categories' => 'array',
        'published_at' => 'datetime',
        'fetched_at' => 'datetime',
    ];

    /**
     * 動画に関連するチャンネルを取得する
     * @return BelongsTo<Channel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
