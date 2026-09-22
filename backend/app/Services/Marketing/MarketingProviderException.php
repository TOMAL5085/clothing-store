<?php

namespace App\Services\Marketing;

class MarketingProviderException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode = 'provider_error',
        private readonly bool $retryable = true,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }
}
