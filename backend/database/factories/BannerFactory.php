<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(),
            'title' => fake()->sentence(3),
            'subtitle' => null,
            'body' => null,
            'image_path' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1920&q=80',
            'mobile_image_path' => null,
            'cta_label' => null,
            'cta_url' => null,
            'status' => 'draft',
            'sort_order' => 0,
            'starts_at' => null,
            'ends_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
