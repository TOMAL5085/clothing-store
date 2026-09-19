import { Link } from "react-router-dom";
import { Heart, Plus } from "lucide-react";
import type { Product } from "@/data/products";
import { Badge, Price } from "@/components/ui/primitives";
import { Rating } from "@/components/product/Rating";
import { useCartStore } from "@/store/cartStore";
import { useUiStore } from "@/store/uiStore";
import { cn } from "@/utils/cn";

export function ProductCard({ product, priority }: { product: Product; priority?: boolean }) {
  const addItem = useCartStore((s) => s.addItem);
  const { wishlist, toggleWishlist, pushToast, setMiniCartOpen } = useUiStore();
  const wished = wishlist.includes(product.id);
  const defaultSize = product.sizes[Math.min(2, product.sizes.length - 1)];

  const quickAdd = async () => {
    if (!product.inStock || !defaultSize) {
      pushToast(`${product.name} is sold out`);
      return;
    }

    try {
      await addItem(product.id, defaultSize, 1, product.colors[0]?.name);
      pushToast(`${product.name} added to bag`);
      setMiniCartOpen(true);
    } catch (error) {
      pushToast(error instanceof Error ? error.message : "Could not add to bag");
    }
  };

  return (
    <article className="group relative">
      <Link to={`/product/${product.slug}`} className="block" aria-label={`${product.name}, view product`}>
        <div className="relative aspect-[3/4] overflow-hidden bg-fog dark:bg-nox2">
          <img
            src={product.images[0]}
            alt={product.alt}
            width={900}
            height={1200}
            loading={priority ? "eager" : "lazy"}
            decoding="async"
            className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.04]"
          />
          {product.images[1] && (
            <img
              src={product.images[1]}
              alt=""
              width={900}
              height={1200}
              loading="lazy"
              decoding="async"
              aria-hidden
              className="absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-500 group-hover:opacity-100"
            />
          )}
          <div className="absolute top-3 left-3 flex flex-col gap-1.5">
            {product.badge === "Sale" && <Badge tone="sale">Sale</Badge>}
            {product.badge === "New" && <Badge tone="ink">New</Badge>}
            {product.badge === "Bestseller" && <Badge tone="bronze">Bestseller</Badge>}
            {!product.inStock && <Badge tone="ink">Sold Out</Badge>}
          </div>
        </div>
      </Link>

      <button
        type="button"
        aria-label={wished ? `Remove ${product.name} from wishlist` : `Add ${product.name} to wishlist`}
        aria-pressed={wished}
        onClick={() => {
          toggleWishlist(product.id);
          pushToast(wished ? `${product.name} removed from wishlist` : `${product.name} saved to wishlist`);
        }}
        className={cn(
          "absolute top-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-cream/90 backdrop-blur transition-all hover:scale-110 dark:bg-nox/80",
          wished ? "text-bronze" : "text-ink dark:text-linen",
        )}
      >
        <Heart className={cn("h-4 w-4 transition-colors", wished && "fill-bronze")} aria-hidden />
      </button>

      <button
        type="button"
        onClick={() => void quickAdd()}
        disabled={!product.inStock}
        aria-label={`Quick add ${product.name} to bag`}
        className="absolute right-3 bottom-[104px] flex h-9 w-9 translate-y-2 items-center justify-center rounded-full bg-ink text-paper opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100 focus-visible:translate-y-0 focus-visible:opacity-100 disabled:cursor-not-allowed disabled:opacity-40 sm:bottom-[112px] dark:bg-linen dark:text-nox"
      >
        <Plus className="h-4 w-4" aria-hidden />
      </button>

      <div className="mt-4 space-y-1 px-0.5">
        <div className="flex items-start justify-between gap-3">
          <h3 className="text-sm font-semibold text-ink dark:text-linen">
            <Link to={`/product/${product.slug}`} className="transition-colors hover:text-bronze">
              {product.name}
            </Link>
          </h3>
          <Price amount={product.price} compareAt={product.compareAt} className="shrink-0" />
        </div>
        <p className="text-[10px] font-semibold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">
          {product.category}
        </p>
        <Rating value={product.rating} reviews={product.reviews} />
      </div>
    </article>
  );
}
