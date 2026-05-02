<?php

use App\Enums\Errors\GeminiError;
use App\Exceptions\GeminiException;
use App\Infrastructure\Gemini\GeminiApiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

describe('GeminiApiClient', function () {
    beforeEach(function () {
        $this->client = new GeminiApiClient(
            baseUrl: 'https://gemini.api.mock/',
            apiKey: 'dummy',
            model: 'gemini-2-flash'
        );
    });

    test('post returns expected array', function () {
        Http::fake([
            'https://gemini.api.mock/gemini-2-flash:generateContent*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => ['This is a response.']
                    ]
                ]],
                'usageMetadata' => [
                    'inputTokens' => 10,
                    'outputTokens' => 20,
                    'totalTokens' => 30,
                ],
                'modelVersion' => 'gemini-2-flash-v1',
            ], 200)
        ]);

        $payload = ['input' => 'Hello, Gemini!'];
        $result = $this->client->post($payload);
        expect($result['candidates'][0]['content']['parts'][0])->toBe('This is a response.');
        expect($result['usageMetadata']['inputTokens'])->toBe(10);
        expect($result['modelVersion'])->toBe('gemini-2-flash-v1');
    });

    test('post throws GeminiException with RESOURCE_EXHAUSTED on 429', function () {
        Log::spy();

        Http::fake([
            '*' => Http::response(['error' => ['message' => 'Quota exceeded']], 429)
        ]);

        $payload = ['input' => 'Hello, Gemini!'];

        try {
            $this->client->post($payload);

            $this->fail('GeminiException was not thrown.');
        } catch (GeminiException $e) {
            expect($e->getErrorEnum())->toBe(GeminiError::RESOURCE_EXHAUSTED);
        }
    });

    test('post throws GeminiException with UNAVAILABLE on ConnectionException', function () {
        Log::spy();

        Http::fake([
            '*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Connection timed out');
            }
        ]);

        $payload = ['input' => 'Hello, Gemini!'];

        try {
            $this->client->post($payload);
            $this->fail('GeminiException was not thrown.');
        } catch (GeminiException $e) {
            expect($e->getErrorEnum())->toBe(GeminiError::UNAVAILABLE);
        }
    });
});
