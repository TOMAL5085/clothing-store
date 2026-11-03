<?php

namespace App\Services\Messaging;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class TwilioWhatsappGateway implements MessageGateway
{
    public function name(): string
    {
        return 'twilio-whatsapp';
    }

    public function send(string $recipient, string $message, array $options = []): array
    {
        $config = config('services.twilio');
        $sid = (string) ($config['sid'] ?? '');
        $token = (string) ($config['token'] ?? '');
        $from = (string) ($config['whatsapp_from'] ?? '');
        if ($sid === '' || $token === '' || $from === '') {
            throw new MessageGatewayException('Twilio WhatsApp credentials are not configured.', 'twilio_whatsapp_not_configured');
        }

        try {
            $response = Http::asForm()->withBasicAuth($sid, $token)->timeout(10)->post(
                "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
                ['To' => str_starts_with($recipient, 'whatsapp:') ? $recipient : 'whatsapp:'.$recipient, 'From' => $from, 'Body' => $message]
            )->throw();
        } catch (RequestException $exception) {
            throw new MessageGatewayException('Twilio rejected the WhatsApp message.', 'twilio_whatsapp_request_failed', $exception);
        }

        $providerId = (string) $response->json('sid', '');
        if ($providerId === '') throw new MessageGatewayException('Twilio returned no WhatsApp message id.', 'twilio_whatsapp_invalid_response');
        return ['provider_message_id' => $providerId, 'status' => 'sent'];
    }
}
