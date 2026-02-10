<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $channel_id
 * @property string $name
 * @property string|null $description
 * @property string|null $thumbnail_url
 * @property string|null $custom_url
 * @property int|null $view_count
 * @property int|null $video_count
 * @property int|null $subscriber_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Video> $videos
 * @property-read int|null $videos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereCustomUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereSubscriberCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereThumbnailUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereVideoCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Channel whereViewCount($value)
 * @method static \Database\Factories\ChannelFactory factory($count = null, $state = [])
 * @mixin \Eloquent
 */
class Channel extends Model
{
    /** @use HasFactory<\Database\Factories\ChannelFactory> */
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'name',
        'thumbnail_url',
        'custom_url',
        'description',
        'subscriber_count',
        'view_count',
        'video_count',
    ];

    /**
     * @return HasMany<Video, $this>
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
