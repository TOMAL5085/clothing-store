<?php

namespace App\Services\Messaging;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class TwilioSmsGateway implements MessageGateway
{
    public function name(): string
    {
        return 'twilio-sms';
    }

    public function send(string $recipient, string $message, array $options = []): array
    {
        $config = config('services.twilio');
        $sid = (string) ($config['sid'] ?? '');
        $token = (string) ($config['token'] ?? '');
        $from = (string) ($config['from'] ?? '');
        if ($sid === '' || $token === '' || $from === '') {
            throw new MessageGatewayException('Twilio SMS credentials are not configured.', 'twilio_not_configured');
        }

        try {
            $response = Http::asForm()->withBasicAuth($sid, $token)->timeout(10)->post(
                "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
                ['To' => $recipient, 'From' => $from, 'Body' => $message]
            )->throw();
        } catch (RequestException $exception) {
            throw new MessageGatewayException('Twilio rejected the SMS request.', 'twilio_request_failed', $exception);
        }

        $providerId = (string) $response->json('sid', '');
        if ($providerId === '') {
            throw new MessageGatewayException('Twilio returned no message id.', 'twilio_invalid_response');
        }

        return ['provider_message_id' => $providerId, 'status' => 'sent'];
    }
}
