<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Color;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = collect([
            ['slug' => 'women', 'label' => 'Women', 'tagline' => 'Soft tailoring & fluid essentials', 'image' => '/imagery/collection-women.jpg', 'alt' => 'Woman against a draped beige backdrop', 'sort_order' => 1],
            ['slug' => 'men', 'label' => 'Men', 'tagline' => 'The uniform, refined', 'image' => '/imagery/collection-men.jpg', 'alt' => 'Man in minimal black attire on white', 'sort_order' => 2],
            ['slug' => 'accessories', 'label' => 'Accessories', 'tagline' => 'Objects in leather & acetate', 'image' => '/imagery/club-giftbox.jpg', 'alt' => 'Handbag and accessories on a retail display', 'sort_order' => 3],
        ])->mapWithKeys(fn ($data) => [$data['slug'] => Category::updateOrCreate(['slug' => $data['slug']], $data)]);

        $colors = collect([
            'Bone' => '#e6dfd0',
            'Oat' => '#c8ae87',
            'Khaki' => '#97906b',
            'Mocha' => '#6d543c',
            'Charcoal' => '#3b3833',
            'Ink' => '#1c1a17',
            'Chalk' => '#f4f2ec',
        ])->mapWithKeys(fn ($hex, $name) => [$name => Color::updateOrCreate(['name' => $name], ['hex' => $hex])]);

        $sizes = collect(['XS', 'S', 'M', 'L', 'XL', 'One Size'])
            ->mapWithKeys(fn ($name, $index) => [$name => Size::updateOrCreate(['name' => $name], ['sort_order' => $index + 1])]);

        Coupon::updateOrCreate(['code' => 'JAAJ10'], [
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
            'starts_at' => null,
            'expires_at' => null,
            'minimum_order_amount' => 0,
            'maximum_discount_amount' => null,
            'usage_limit' => null,
            'per_customer_limit' => null,
        ]);

        $products = [
            ['adele-trench', 'adele-trench-coat', 'Adele Trench Coat', 'women', 420, null, 4.9, 214, ['Oat', 'Ink'], ['XS', 'S', 'M', 'L', 'XL'], ['/imagery/hero-primary.jpg', '/imagery/collection-women.jpg'], 'Model wearing the Adele trench coat in oat cotton', 'New', true, false, 'A fluid, double-faced trench cut from bonded organic cotton. Storm flap, horn buttons and a belt that ties, never buckles.', ['100% organic cotton, bonded double-face', 'Horn buttons, hidden closure', 'Falls below the knee', 'Made in Portugal in small batches']],
            ['alba-blazer', 'alba-oversized-blazer', 'Alba Oversized Blazer', 'women', 290, null, 4.8, 486, ['Bone', 'Charcoal'], ['XS', 'S', 'M', 'L', 'XL'], ['/imagery/collection-women.jpg', '/imagery/hero-primary.jpg'], 'Model in the Alba oversized blazer, beige', 'Bestseller', false, true, 'Our signature soft shoulder, tailored with a relaxed drop and an easy, unlined body that moves with you.', ['Wool-blend twill, half canvas', 'Single-button closure', 'Relaxed, dropped shoulder', 'Dry clean only']],
            ['poplin-shirt', 'poplin-column-shirt', 'Poplin Column Shirt', 'women', 145, null, 4.7, 168, ['Chalk', 'Bone'], ['XS', 'S', 'M', 'L', 'XL'], ['/imagery/collection-women.jpg'], 'Model rolling the sleeves of the white poplin column shirt', null, false, false, 'Crisp organic-cotton poplin with a clean, collar-forward stance.', ['Organic cotton poplin', 'Mother-of-pearl buttons', 'Curved hem, side vents', 'Machine washable']],
            ['rena-hoodie', 'rena-relaxed-hoodie', 'Rena Relaxed Hoodie', 'women', 185, null, 4.6, 92, ['Mocha', 'Ink'], ['XS', 'S', 'M', 'L', 'XL'], ['/imagery/hero-primary.jpg'], 'Model in the Rena relaxed hoodie in mocha loopback cotton', null, false, false, 'Heavyweight loopback cotton with a dropped shoulder and a double-layer hood.', ['460gsm loopback organic cotton', 'Double-layer hood', 'Ribbed cuffs and hem', 'Garment-dyed, pre-shrunk']],
            ['column-trouser', 'tailored-column-trouser', 'Tailored Column Trouser', 'women', 210, null, 4.9, 357, ['Charcoal', 'Bone'], ['XS', 'S', 'M', 'L', 'XL'], ['/imagery/collection-women.jpg'], 'Model seated wearing the tailored column trouser', 'Bestseller', false, true, 'A single, clean crease from hip to hem. High-rise, full-length, endlessly pairable.', ['Wool-blend suiting with stretch', 'Hook-and-bar closure', 'Full-length wide leg', 'Unfinished hem for tailoring']],
            ['archer-jacket', 'archer-zip-jacket', 'Archer Zip Jacket', 'men', 255, null, 4.7, 74, ['Ink'], ['XS', 'S', 'M', 'L', 'XL'], ['/imagery/collection-men.jpg'], 'Profile of a model wearing the black Archer zip jacket', 'New', true, false, 'A cropped, clean-front zip jacket in brushed twill with a perfect collar and two-way zip.', ['Brushed cotton twill', 'Two-way matte zip', 'Cropped, boxy fit', 'Interior security pocket']],
            ['studio-hoodie-men', 'mens-studio-hoodie', "Men's Studio Hoodie", 'men', 125, null, 4.8, 311, ['Chalk', 'Charcoal'], ['XS', 'S', 'M', 'L', 'XL'], ['/imagery/collection-men.jpg'], 'Model with glasses wearing the chalk Studio hoodie', 'Bestseller', false, true, 'Dense, dry-hand fleece with a structured hood that stands on its own.', ['480gsm organic fleece', 'Structured double hood', 'Flat drawcords', 'Machine washable']],
            ['merino-crew', 'traceable-merino-crew', 'Traceable Merino Crew', 'men', 175, null, 4.8, 156, ['Charcoal', 'Oat'], ['XS', 'S', 'M', 'L', 'XL'], ['/imagery/collection-men.jpg'], 'Model holding a hanger with the grey merino crew knit', null, false, false, 'Fully traceable extra-fine merino, knitted whole-garment with zero seams.', ['100% traceable extra-fine merino', 'Seamless whole-garment knit', 'Naturally breathable', 'Hand wash or wool cycle']],
            ['sutton-blazer', 'sutton-single-breasted-blazer', 'Sutton Blazer', 'men', 340, null, 4.9, 98, ['Ink', 'Charcoal'], ['XS', 'S', 'M', 'L', 'XL'], ['/imagery/collection-men.jpg'], 'Relaxed portrait of a model in the black Sutton blazer', null, false, false, 'Unstructured tailoring with soft shoulder, patch pockets, and cloth with body.', ['Italian wool-silk suiting', 'Unstructured, unlined body', 'Patch pockets', 'Dry clean only']],
            ['cinder-tote', 'cinder-leather-tote', 'Cinder Leather Tote', 'accessories', 390, null, 4.9, 176, ['Mocha'], ['One Size'], ['/imagery/club-giftbox.jpg'], 'The Cinder tote in vegetable-tanned mocha leather on a bench', 'Bestseller', false, true, 'Vegetable-tanned leather that records your life in patina. Fits a 16-inch laptop.', ['Full-grain vegetable-tanned leather', 'Unlined, saddle-stitched', 'Fits 16-inch laptop', 'Ages to a deep patina']],
            ['oslo-sunglasses', 'oslo-frame-sunglasses', 'Oslo Frame Sunglasses', 'accessories', 160, null, 4.7, 89, ['Ink'], ['One Size'], ['/imagery/experience-architecture.jpg'], 'The Oslo sunglasses with leather case on a table', 'New', true, false, 'Bio-acetate frames with a soft-square lens, handmade in a family studio.', ['Mazzucchelli bio-acetate', 'CR-39 lenses, 100% UV', 'Five-barrel hinges', 'Leather case included']],
            ['archive-weekender', 'archive-weekender-set', 'Archive Weekender Set', 'accessories', 460, 520, 4.8, 54, ['Bone', 'Chalk'], ['One Size'], ['/imagery/club-giftbox.jpg'], 'The Archive weekender set with sandals and sunglasses', 'Sale', false, false, 'Canvas-and-leather weekender, sandal and frame, the full weekend kit.', ['22oz canvas, leather trim', 'Cabin-approved dimensions', 'Brass hardware', 'Lifetime repairs']],
        ];

        foreach ($products as [$externalId, $slug, $name, $category, $price, $compareAt, $rating, $reviews, $productColors, $productSizes, $images, $alt, $badge, $isNew, $bestseller, $description, $details]) {
            $stock = count($productSizes) * max(1, count($productColors)) * 18;
            $product = Product::updateOrCreate(['external_id' => $externalId], [
                'category_id' => $categories[$category]->id,
                'slug' => $slug,
                'name' => $name,
                'description' => $description,
                'short_description' => Str::limit($description, 120),
                'price' => $price,
                'compare_at_price' => $compareAt,
                'sku' => 'JAAJ-'.Str::upper(Str::slug($externalId, '-')),
                'brand' => 'JAAJ',
                'rating' => $rating,
                'reviews_count' => $reviews,
                'badge' => $badge,
                'is_new' => $isNew,
                'is_bestseller' => $bestseller,
                'is_featured' => $isNew || $bestseller,
                'is_active' => true,
                'in_stock' => true,
                'stock_quantity' => $stock,
                'details' => $details,
            ]);

            $product->images()->delete();
            foreach ($images as $order => $url) {
                $product->images()->create(['url' => $url, 'alt' => $alt, 'sort_order' => $order + 1]);
            }

            $product->variants()->delete();
            foreach ($productSizes as $size) {
                foreach ($productColors as $color) {
                    $product->variants()->create([
                        'size_id' => $sizes[$size]->id,
                        'color_id' => $colors[$color]->id,
                        'sku' => 'JAAJ-'.Str::upper(Str::slug($externalId.'-'.$size.'-'.$color, '-')),
                        'price' => $price,
                        'stock_quantity' => 18,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
