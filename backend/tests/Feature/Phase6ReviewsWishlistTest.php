<?php

namespace Tests\Feature;

use App\Models\Color;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6ReviewsWishlistTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'rating' => 0,
            'reviews_count' => 0,
            'is_active' => true,
            'in_stock' => true,
            'stock_quantity' => 20,
        ], $overrides));
    }

    private function deliveredOrderFor(User $user, Product $product, int $quantity = 1, array $overrides = []): Order
    {
        $size = Size::firstOrCreate(['name' => 'M'], ['sort_order' => 3]);
        $color = Color::firstOrCreate(['name' => 'Ink'], ['hex' => '#1c1a17']);
        $variant = $product->variants()->create([
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'REV-'.$product->id.'-M-INK',
            'price' => $product->price,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $order = Order::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'payment_provider' => 'demo',
        ], $overrides));

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_external_id' => $product->external_id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'size' => 'M',
            'sku' => $variant->sku,
            'quantity' => $quantity,
            'unit_price' => $product->price,
            'line_total' => (float) $product->price * $quantity,
        ]);

        return $order->fresh(['user', 'items']);
    }

    /** @return array<string, mixed> */
    private function reviewPayload(array $overrides = []): array
    {
        return array_merge([
            'rating' => 5,
            'title' => 'Excellent quality',
            'body' => 'The fit and fabric exceeded my expectations. Highly recommended.',
        ], $overrides);
    }

    /* ------------------------------------------------------------------ *
     * Review creation and purchase verification
     * ------------------------------------------------------------------ */

    public function test_eligible_customer_can_create_review(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $this->deliveredOrderFor($customer, $product);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload())
            ->assertCreated();

        $response->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.verifiedPurchase', true)
            ->assertJsonPath('data.customerName', $customer->name)
            ->assertJsonPath('data.productId', $product->external_id);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'rating' => 5,
            'status' => 'pending',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_review(): void
    {
        $product = $this->product();

        $this->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload())
            ->assertUnauthorized();
    }

    public function test_customer_without_purchase_cannot_review(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload())
            ->assertForbidden();

        $this->assertSame(0, Review::query()->count());
    }

    public function test_customer_with_undelivered_order_cannot_review(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $this->deliveredOrderFor($customer, $product, 1, ['status' => 'processing']);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review');

        $this->assertSame(0, Review::query()->count());
    }

    public function test_customer_with_unpaid_order_cannot_review(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $this->deliveredOrderFor($customer, $product, 1, ['payment_status' => 'pending']);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload())
            ->assertUnprocessable();

        $this->assertSame(0, Review::query()->count());
    }

    public function test_duplicate_review_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $this->deliveredOrderFor($customer, $product);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload())
            ->assertCreated();

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload(['rating' => 4]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review');

        $this->assertSame(1, Review::query()->count());
    }

    public function test_rating_below_minimum_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $this->deliveredOrderFor($customer, $product);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload(['rating' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rating');
    }

    public function test_rating_above_maximum_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $this->deliveredOrderFor($customer, $product);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload(['rating' => 6]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rating');
    }

    public function test_non_integer_rating_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $this->deliveredOrderFor($customer, $product);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload(['rating' => 3.5]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rating');
    }

    public function test_review_body_is_required_and_title_has_max_length(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $this->deliveredOrderFor($customer, $product);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload(['title' => str_repeat('a', 121)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    /* ------------------------------------------------------------------ *
     * Review ownership
     * ------------------------------------------------------------------ */

    public function test_customer_can_edit_own_review(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $this->deliveredOrderFor($customer, $product);

        $created = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload())
            ->assertCreated();

        $reviewId = $created->json('data.id');

        $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/reviews/{$reviewId}", ['rating' => 4, 'body' => 'Updated opinion after a second wear.'])
            ->assertOk()
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.body', 'Updated opinion after a second wear.');

        $this->assertDatabaseHas('reviews', ['id' => $reviewId, 'rating' => 4]);
    }

    public function test_editing_approved_review_returns_it_to_moderation(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $review = Review::factory()->approved()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/reviews/{$review->id}", ['body' => 'Changed my mind slightly.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        $this->assertSame('pending', $review->fresh()->status);
    }

    public function test_customer_cannot_edit_another_customers_review(): void
    {
        $author = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $review = Review::factory()->create([
            'user_id' => $author->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($other, 'sanctum')
            ->putJson("/api/v1/reviews/{$review->id}", ['rating' => 1])
            ->assertForbidden();
    }

    public function test_customer_can_delete_own_review(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $this->deliveredOrderFor($customer, $product);

        $created = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload())
            ->assertCreated();

        $this->actingAs($customer, 'sanctum')
            ->deleteJson('/api/v1/reviews/'.$created->json('data.id'))
            ->assertOk();

        $this->assertSame(0, Review::query()->count());
    }

    public function test_customer_cannot_delete_another_customers_review(): void
    {
        $author = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $review = Review::factory()->create([
            'user_id' => $author->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/v1/reviews/{$review->id}")
            ->assertForbidden();

        $this->assertSame(1, Review::query()->count());
    }

    public function test_customer_cannot_set_moderation_status_or_impersonate(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $impostor = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $this->deliveredOrderFor($customer, $product);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload([
                'status' => 'approved',
                'user_id' => $impostor->id,
                'verified_purchase' => false,
            ]))
            ->assertCreated();

        $response->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.verifiedPurchase', true);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'status' => 'pending',
            'verified_purchase' => true,
        ]);
    }

    /* ------------------------------------------------------------------ *
     * Public visibility and aggregation
     * ------------------------------------------------------------------ */

    public function test_pending_review_is_not_publicly_visible(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        Review::factory()->create(['user_id' => $customer->id, 'product_id' => $product->id]);

        $this->getJson("/api/v1/products/{$product->slug}/reviews")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson("/api/v1/products/{$product->slug}/reviews/summary")
            ->assertOk()
            ->assertJsonPath('data.count', 0)
            ->assertJsonPath('data.average', 0);
    }

    public function test_rejected_review_is_not_publicly_visible(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        Review::factory()->rejected()->create(['user_id' => $customer->id, 'product_id' => $product->id]);

        $this->getJson("/api/v1/products/{$product->slug}/reviews")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_approved_review_is_publicly_visible(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Ada Reviewer']);
        $product = $this->product();
        Review::factory()->approved()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'rating' => 5,
        ]);

        $this->getJson("/api/v1/products/{$product->slug}/reviews")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 5)
            ->assertJsonPath('data.0.customerName', 'Ada Reviewer')
            ->assertJsonPath('data.0.verifiedPurchase', true)
            ->assertJsonMissing(['email' => $customer->email]);
    }

    public function test_only_approved_reviews_affect_summary(): void
    {
        $product = $this->product();
        $users = User::factory()->count(4)->create(['role' => 'customer']);

        Review::factory()->approved()->create(['user_id' => $users[0]->id, 'product_id' => $product->id, 'rating' => 5]);
        Review::factory()->approved()->create(['user_id' => $users[1]->id, 'product_id' => $product->id, 'rating' => 3]);
        Review::factory()->create(['user_id' => $users[2]->id, 'product_id' => $product->id, 'rating' => 1]);
        Review::factory()->rejected()->create(['user_id' => $users[3]->id, 'product_id' => $product->id, 'rating' => 1]);

        $this->getJson("/api/v1/products/{$product->slug}/reviews/summary")
            ->assertOk()
            ->assertJsonPath('data.count', 2)
            ->assertJsonPath('data.average', 4)
            ->assertJsonPath('data.distribution.5', 1)
            ->assertJsonPath('data.distribution.3', 1)
            ->assertJsonPath('data.distribution.1', 0);
    }

    public function test_review_listing_and_summary_require_active_product(): void
    {
        $missing = $this->getJson('/api/v1/products/no-such-product/reviews');
        $missing->assertNotFound();

        $inactive = $this->product(['is_active' => false]);
        $this->getJson("/api/v1/products/{$inactive->slug}/reviews")->assertNotFound();
        $this->getJson("/api/v1/products/{$inactive->slug}/reviews/summary")->assertNotFound();

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$inactive->slug}/reviews", $this->reviewPayload())
            ->assertNotFound();
    }

    /* ------------------------------------------------------------------ *
     * Moderation
     * ------------------------------------------------------------------ */

    public function test_admin_can_approve_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $review = Review::factory()->create(['user_id' => $customer->id, 'product_id' => $product->id]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/reviews/{$review->id}", ['decision' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertSame('approved', $review->fresh()->status);

        $this->getJson("/api/v1/products/{$product->slug}/reviews")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_reject_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $review = Review::factory()->create(['user_id' => $customer->id, 'product_id' => $product->id]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/reviews/{$review->id}", ['decision' => 'rejected'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->getJson("/api/v1/products/{$product->slug}/reviews")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_can_list_and_filter_reviews(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product();
        $users = User::factory()->count(2)->create(['role' => 'customer']);
        Review::factory()->create(['user_id' => $users[0]->id, 'product_id' => $product->id]);
        Review::factory()->approved()->create(['user_id' => $users[1]->id, 'product_id' => $product->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/reviews?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/reviews')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_non_admin_cannot_moderate_reviews(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $review = Review::factory()->create(['user_id' => $customer->id, 'product_id' => $product->id]);

        // Unauthenticated requests are rejected before any authenticated call,
        // because actingAs persists for the remainder of the test.
        $this->getJson('/api/v1/admin/reviews')->assertUnauthorized();

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/v1/admin/reviews/{$review->id}", ['decision' => 'approved'])
            ->assertForbidden();
    }

    /* ------------------------------------------------------------------ *
     * Own review and eligibility endpoints
     * ------------------------------------------------------------------ */

    public function test_mine_returns_own_review_only(): void
    {
        $author = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        Review::factory()->create(['user_id' => $author->id, 'product_id' => $product->id]);

        // Unauthenticated requests are rejected before any authenticated call,
        // because actingAs persists for the remainder of the test.
        $this->getJson("/api/v1/products/{$product->slug}/reviews/mine")
            ->assertUnauthorized();

        $this->actingAs($author, 'sanctum')
            ->getJson("/api/v1/products/{$product->slug}/reviews/mine")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        // Another customer has no review for this product.
        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/products/{$product->slug}/reviews/mine")
            ->assertNotFound();
    }

    public function test_eligibility_endpoint_reports_state(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $product = $this->product();
        $this->deliveredOrderFor($customer, $product);

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/products/{$product->slug}/reviews/eligibility")
            ->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.hasReviewed', false)
            ->assertJsonPath('data.verifiedPurchase', true);

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/products/{$product->slug}/reviews/eligibility")
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.hasReviewed', false);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/products/{$product->slug}/reviews", $this->reviewPayload())
            ->assertCreated();

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/products/{$product->slug}/reviews/eligibility")
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.hasReviewed', true);
    }

    /* ------------------------------------------------------------------ *
     * Wishlist
     * ------------------------------------------------------------------ */

    public function test_guest_wishlist_flow_is_preserved(): void
    {
        $product = $this->product();

        $added = $this->postJson('/api/v1/wishlist/items', ['product_id' => $product->external_id])
            ->assertCreated()
            ->assertJsonPath('data.ids.0', $product->external_id);

        $token = $added->json('data.token');
        $this->assertNotEmpty($token);

        $this->getJson('/api/v1/wishlist?'.http_build_query(['wishlist_token' => $token]))
            ->assertOk()
            ->assertJsonPath('data.ids.0', $product->external_id);
    }

    public function test_authenticated_customer_can_add_and_list_wishlist(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/wishlist/items', ['product_id' => $product->external_id])
            ->assertCreated()
            ->assertJsonPath('data.ids.0', $product->external_id);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/wishlist')
            ->assertOk()
            ->assertJsonPath('data.ids.0', $product->external_id)
            ->assertJsonPath('data.products.0.name', $product->name);
    }

    public function test_duplicate_wishlist_add_does_not_create_duplicate_row(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/wishlist/items', ['product_id' => $product->external_id])
            ->assertCreated();

        // The duplicate add is accepted but creates nothing new.
        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/wishlist/items', ['product_id' => $product->external_id])
            ->assertOk();

        $this->assertSame(1, $customer->wishlist()->firstOrFail()->items()->count());
    }

    public function test_authenticated_customer_can_remove_wishlist_item(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/wishlist/items', ['product_id' => $product->external_id])
            ->assertCreated();

        $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/wishlist/items/{$product->external_id}")
            ->assertOk()
            ->assertJsonCount(0, 'data.ids');

        $this->assertSame(0, $customer->wishlist()->firstOrFail()->items()->count());
    }

    public function test_customer_only_sees_own_wishlist(): void
    {
        $customerA = User::factory()->create(['role' => 'customer']);
        $customerB = User::factory()->create(['role' => 'customer']);
        $product = $this->product();

        $this->actingAs($customerA, 'sanctum')
            ->postJson('/api/v1/wishlist/items', ['product_id' => $product->external_id])
            ->assertCreated();

        // A first-time wishlist lookup materializes the row, so any 2xx is fine.
        $this->actingAs($customerB, 'sanctum')
            ->getJson('/api/v1/wishlist')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data.ids');
    }

    public function test_inactive_product_cannot_be_added_to_wishlist(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->product(['is_active' => false]);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/wishlist/items', ['product_id' => $product->external_id])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('wishlist_items', ['product_id' => $product->id]);
    }

    public function test_unknown_product_is_rejected_for_wishlist(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/wishlist/items', ['product_id' => 'no-such-product'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');
    }

    public function test_removing_unknown_wishlist_product_returns_not_found(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->deleteJson('/api/v1/wishlist/items/no-such-product')
            ->assertNotFound();
    }
}
