<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $subcategories = [
            'women' => ['Dresses', 'Tops', 'Skirts', 'Trousers', 'Jackets'],
            'men' => ['Shirts', 'T-Shirts', 'Trousers', 'Jackets', 'Shoes'],
            'kids' => ['Boys', 'Girls', 'Baby'],
            'accessories' => ['Bags', 'Jewellery', 'Sunglasses'],
            'decor' => ['Wall Art', 'Cushions', 'Lighting'],
        ];

        foreach ($subcategories as $parentSlug => $labels) {
            $parent = Category::query()->where('slug', $parentSlug)->first();

            if (! $parent) {
                continue;
            }

            foreach ($labels as $index => $label) {
                Category::query()->firstOrCreate(
                    ['slug' => $parentSlug.'-'.str($label)->slug()],
                    [
                        'label' => $label,
                        'parent_id' => $parent->id,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        // Keep administrator-managed category data intact on rollback.
    }
};
