<?php

namespace Database\Factories;

use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NotificationDelivery>
 */
class NotificationDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_key' => (string) Str::uuid(),
            'notification_id' => null,
            'user_id' => User::factory(),
            'channel' => 'sms',
            'recipient' => '+15550001111',
            'template' => 'order_placed',
            'order_number' => 'JAAJ-100000',
            'status' => NotificationDelivery::STATUS_QUEUED,
            'provider' => 'mock',
            'provider_message_id' => null,
            'attempts' => 0,
            'last_attempted_at' => null,
            'delivered_at' => null,
            'failed_at' => null,
            'error_code' => null,
        ];
    }
}
