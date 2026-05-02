<?php

namespace App\Providers;

use App\Infrastructure\YouTube\YouTubeApiClient;
use App\Repositories\Contracts\RecipeRepositoryInterface;
use App\Repositories\RecipeRepository;
use App\Services\LLM\GeminiService;
use App\Services\LLM\LLMServiceInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    const YOUTUBE_API_RATE_LIMIT = 10; // 1ユーザー1分間に10回
    const GEMINI_API_RATE_LIMIT = 3; // 1ユーザー1分間
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(YouTubeApiClient::class, function ($app) {
            $baseUrlRaw = config('services.youtube.base_url');
            $baseUrl = is_string($baseUrlRaw) ? $baseUrlRaw : '';
            $apiKeyRaw = config('services.google.api_key');
            $apiKey = is_string($apiKeyRaw) ? $apiKeyRaw : '';
            return new YouTubeApiClient($baseUrl, $apiKey);
        });
        $this->app->bind(RecipeRepositoryInterface::class, RecipeRepository::class);
        $this->app->bind(LLMServiceInterface::class, GeminiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // レシピプレビュー（YouTube API）の制限: 1ユーザー1分間に10回
        RateLimiter::for('youtube-api', function (Request $request) {
            return Limit::perMinute(self::YOUTUBE_API_RATE_LIMIT)->by($request->user()?->id ?: $request->ip());
        });

        // レシピ生成（Gemini API）の制限: 1ユーザー1分間に3回
        RateLimiter::for('gemini-generator', function (Request $request) {
            return Limit::perMinute(self::GEMINI_API_RATE_LIMIT)->by($request->user()?->id ?: $request->ip());
        });
    }
}
