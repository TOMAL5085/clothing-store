<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Shipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => 'pending',
            'carrier' => null,
            'tracking_number' => null,
            'tracking_reference' => null,
            'shipping_fee' => null,
            'estimated_delivery_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
        ];
    }
}