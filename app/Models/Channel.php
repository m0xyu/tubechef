<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $fillable = [
        'channel_id',
        'name',
        'thumbnail_url',
        'custom_url',
        'subscriber_count',
        'view_count',
        'video_count',
    ];

    public function videos()
    {
        return $this->hasMany(Video::class);
    }
}
