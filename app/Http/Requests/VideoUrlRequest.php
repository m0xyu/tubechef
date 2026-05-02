<?php

namespace App\Http\Requests;

use App\Rules\YouTubeUrlRule;
use Illuminate\Foundation\Http\FormRequest;

class VideoUrlRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'video_url' => [
                'required',
                'url',
                new YouTubeUrlRule(),
            ],
        ];
    }

    /**
     * @return string
     */
    public function getVideoUrl(): string
    {
        $url = $this->input('video_url');
        if (!is_string($url)) {
            throw new \InvalidArgumentException('Invalid video URL');
        }
        return $url;
    }
}
