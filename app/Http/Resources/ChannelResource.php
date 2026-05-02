<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $channel_id
 * @property string $name
 * @property string $custom_url
 * @property string $thumbnail_url
 */
class ChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'channel_id' => $this->channel_id,
            'name' => $this->name,
            'custom_url' => $this->custom_url,
            'thumbnail_url' => $this->thumbnail_url,
        ];
    }
}
