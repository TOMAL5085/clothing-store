<?php

namespace App\Services\Messaging;

interface MessageGateway
{
    /**
     * Provider driver name, e.g. "mock-sms". Used for delivery records and
     * logs — never a secret.
     */
    public function name(): string;

    /**
     * Send one message. Implementations must perform the single provider
     * call here and nowhere else, so retries stay centralized in the job.
     *
     * @param  array{idempotency_key?:string}  $options
     * @return array{provider_message_id:string, status:string}
     *
     * @throws MessageGatewayException on transport or provider rejection.
     */
    public function send(string $recipient, string $message, array $options = []): array;
}
