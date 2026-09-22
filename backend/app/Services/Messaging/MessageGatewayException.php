<?php

namespace App\Services\Messaging;

class MessageGatewayException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode = 'gateway_error',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
