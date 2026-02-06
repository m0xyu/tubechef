<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
