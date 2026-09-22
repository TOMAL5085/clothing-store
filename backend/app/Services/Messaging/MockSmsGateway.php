<?php

namespace App\Services\Messaging;

class MockSmsGateway implements MessageGateway
{
    /** @var list<array{recipient:string, message:string, idempotency_key:?string}> */
    public static array $outbox = [];

    public static int $counter = 0;

    public function name(): string
    {
        return 'mock-sms';
    }

    public function send(string $recipient, string $message, array $options = []): array
    {
        if (str_ends_with($recipient, '0000')) {
            throw new MessageGatewayException('Mock SMS provider rejected the recipient.', 'mock_rejected');
        }

        self::$counter++;
        self::$outbox[] = [
            'recipient' => $recipient,
            'message' => $message,
            'idempotency_key' => $options['idempotency_key'] ?? null,
        ];

        return [
            'provider_message_id' => 'mock-sms-'.self::$counter,
            'status' => 'sent',
        ];
    }

    /** @return list<array{recipient:string, message:string, idempotency_key:?string}> */
    public static function sent(): array
    {
        return self::$outbox;
    }

    public static function reset(): void
    {
        self::$outbox = [];
        self::$counter = 0;
    }
}
