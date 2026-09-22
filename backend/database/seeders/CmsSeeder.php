<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\CmsContent;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    /**
     * Seed storefront content matching the approved design copy. All rows
     * are idempotent (keyed upserts) and published, so a fresh database
     * renders exactly the approved storefront.
     */
    public function run(): void
    {
        CmsContent::query()->updateOrCreate(
            ['key' => 'site-announcement'],
            [
                'type' => 'announcement',
                // Title/subtitle map to the bar's two-tone segments so the
                // approved design (plain lead-in + red highlight) is kept.
                'title' => 'FREE SHIPPING ON ALL ORDERS ABOVE',
                'subtitle' => '$99',
                'body' => null,
                'status' => 'published',
                'sort_order' => 0,
                'starts_at' => null,
                'ends_at' => null,
            ]
        );

        $slides = [
            [
                'key' => 'hero-slide-1',
                'image_path' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1920&q=80',
                'sort_order' => 0,
            ],
            [
                'key' => 'hero-slide-2',
                'image_path' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=1920&q=80',
                'sort_order' => 1,
            ],
            [
                'key' => 'hero-slide-3',
                'image_path' => 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=1920&q=80',
                'sort_order' => 2,
            ],
        ];

        foreach ($slides as $slide) {
            Banner::query()->updateOrCreate(
                ['key' => $slide['key']],
                [
                    'title' => 'Hero slide',
                    'image_path' => $slide['image_path'],
                    'status' => 'published',
                    'sort_order' => $slide['sort_order'],
                    'starts_at' => null,
                    'ends_at' => null,
                ]
            );
        }
    }
}
