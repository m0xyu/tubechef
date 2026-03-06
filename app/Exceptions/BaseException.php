<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * アプリケーション例外の基底クラス
 */
abstract class BaseException extends RuntimeException
{
    protected int $statusCode = 500;
    protected string $userMessage;
    /** @var array<string, mixed> */
    protected array $details = [];
    protected bool $shouldReport = true;

    /**
     * @param string $message ログ出力用の技術的なエラーメッセージ（必須）
     * @param string|null $userMessage ユーザー表示用メッセージ
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message,
        ?string $userMessage = null,
        ?Throwable $previous = null,
    ) {
        // 親クラスには技術的なメッセージを渡す（ログ用）
        parent::__construct($message, 0, $previous);

        // ユーザー用メッセージの設定
        $this->userMessage = $userMessage ?? $this->getDefaultUserMessage();
    }

    /**
     * この例外固有のエラーコードを返す (例: 'inventory_shortage')
     * @return string
     */
    abstract public function getErrorCode(): string;

    /**
     * デフォルトのユーザー向けメッセージ
     *  @return string
     */
    abstract protected function getDefaultUserMessage(): string;

    public function withStatus(int $statusCode): static
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @param array<string, mixed> $details
     */
    public function withDetails(array $details): static
    {
        $this->details = $details;
        return $this;
    }

    public function doNotReport(): self
    {
        $this->shouldReport = false;
        return $this;
    }

    /** @return int */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** @return string */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    public function shouldReport(): bool
    {
        return $this->shouldReport;
    }
}
