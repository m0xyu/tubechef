<?php

namespace App\ValueObjects;

use App\Enums\Errors\VideoError;
use App\Exceptions\VideoException;

final readonly class YouTubeVideoId implements \Stringable
{
    /**
     * @param string $value
     */
    private function __construct(public string $value) {}

    /**
     * URLからインスタンスを生成する
     * @param string $url
     * @return self
     * @throws VideoException
     */
    public static function fromUrl(string $url): YouTubeVideoId
    {
        $pattern = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:watch\?v=|v\/|embed\/|live\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';

        if (preg_match($pattern, $url, $matches)) {
            return new self($matches[1]);
        }

        throw new VideoException(VideoError::INVALID_ID);
    }

    /**
     * すでにIDが分かっている場合（DBからの取得時など）
     * @param string $id
     * @return self
     */
    public static function fromString(string $id): self
    {
        if (strlen($id) !== 11) {
            throw new VideoException(VideoError::INVALID_ID);
        }
        return new self($id);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
