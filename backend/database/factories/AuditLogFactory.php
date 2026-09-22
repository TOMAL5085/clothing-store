<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory(),
            'actor_role' => 'admin',
            'action' => 'order.status_updated',
            'auditable_type' => null,
            'auditable_id' => null,
            'ip' => '127.0.0.1',
            'user_agent' => 'Phase8SecurityTest/1.0',
            'metadata' => ['order_number' => 'JAAJ-100000'],
        ];
    }
}
