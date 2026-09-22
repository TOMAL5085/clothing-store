import { Heart, ShoppingBag, X } from "lucide-react";
import { ProductCard } from "@/components/product/ProductCard";
import { EmptyState } from "@/components/ui/primitives";
import { useUiStore } from "@/store/uiStore";
import { useCartStore } from "@/store/cartStore";
import { useCatalogStore } from "@/store/catalogStore";
import { usePageTitle } from "@/utils/usePageTitle";
import { trackAddToCart } from "@/lib/marketing";

export default function WishlistPage() {
  usePageTitle("Wishlist");
  const { wishlist, removeFromWishlist, pushToast, setMiniCartOpen } = useUiStore();
  const addItem = useCartStore((s) => s.addItem);
  const catalogProducts = useCatalogStore((s) => s.products);

  const products = catalogProducts.filter((p) => wishlist.includes(p.id));

  return (
    <div className="mx-auto max-w-[1440px] px-4 py-10 sm:px-6 lg:px-10 lg:py-16">
      <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Saved Pieces</p>
      <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">
        Wishlist <span className="align-middle text-xl text-smoke dark:text-linen-dim">({products.length})</span>
      </h1>

      {products.length === 0 ? (
        <div className="mt-10 border border-line bg-cream dark:border-line-dark dark:bg-nox2">
          <EmptyState
            icon={Heart}
            title="Nothing saved yet"
            body="Tap the heart on any piece to keep it here — your shortlist survives refreshes and visits."
            actionLabel="Discover the collection"
            actionTo="/shop"
          />
        </div>
      ) : (
        <div className="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3 lg:grid-cols-4">
          {products.map((product) => {
            const defaultSize = product.sizes[Math.min(2, product.sizes.length - 1)];
            return (
              <div key={product.id}>
                <ProductCard product={product} />
                <div className="mt-3 flex gap-2">
                  <button
                    type="button"
                    onClick={() => {
                      void addItem(product.id, defaultSize, 1, product.colors[0]?.name)
                        .then(() => {
                          trackAddToCart({ productId: product.id, slug: product.slug, quantity: 1 });
                          removeFromWishlist(product.id);
                          pushToast(`${product.name} moved to bag`);
                          setMiniCartOpen(true);
                        })
                        .catch((error) => pushToast(error instanceof Error ? error.message : "Could not move to bag"));
                    }}
                    className="inline-flex flex-1 items-center justify-center gap-2 border border-ink bg-ink px-3 py-2.5 text-[10px] font-bold tracking-[0.14em] uppercase text-paper transition-colors hover:bg-bronze-deep dark:border-linen dark:bg-linen dark:text-nox"
                  >
                    <ShoppingBag className="h-3.5 w-3.5" aria-hidden />
                    Move to Bag
                  </button>
                  <button
                    type="button"
                    aria-label={`Remove ${product.name} from wishlist`}
                    onClick={() => {
                      removeFromWishlist(product.id);
                      pushToast(`${product.name} removed from wishlist`);
                    }}
                    className="inline-flex items-center justify-center border border-line px-3 text-smoke transition-colors hover:border-ink hover:text-ink dark:border-line-dark dark:text-linen-dim dark:hover:border-linen dark:hover:text-linen"
                  >
                    <X className="h-4 w-4" aria-hidden />
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
