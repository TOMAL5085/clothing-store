<?php

namespace Database\Factories;

use App\Models\CmsContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CmsContent>
 */
class CmsContentFactory extends Factory
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
            'type' => fake()->randomElement(CmsContent::TYPES),
            'title' => fake()->sentence(3),
            'subtitle' => fake()->sentence(5),
            'body' => fake()->paragraph(),
            'image_path' => null,
            'mobile_image_path' => null,
            'cta_label' => 'Shop Now',
            'cta_url' => '/shop',
            'status' => 'draft',
            'sort_order' => 0,
            'starts_at' => null,
            'ends_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
