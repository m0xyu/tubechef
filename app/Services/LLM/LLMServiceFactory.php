<?php

namespace App\Services\LLM;

use App\Services\LLM\GeminiService;
use Illuminate\Contracts\Container\Container;
use Exception;

class LLMServiceFactory
{
    /**
     * @var Container LaravelのDIコンテナ
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function make(string $serviceName = 'default'): LLMServiceInterface
    {
        if ($serviceName === 'default' || $serviceName === 'gemini') {
            return $this->container->make(GeminiService::class);
        }

        // if ($serviceName === 'openai') {
        //     // 将来OpenAIもDIコンテナ経由で作る
        //     return $this->container->make(OpenAILLMService::class);
        // }

        throw new Exception("Unsupported LLM service: $serviceName");
    }
}
