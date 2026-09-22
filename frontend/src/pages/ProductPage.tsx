import { useEffect, useMemo, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { ChevronRight, Heart, Package, RefreshCcw, ShoppingBag, TriangleAlert, X } from "lucide-react";
import { useCatalogStore } from "@/store/catalogStore";
import { trackAddToCart, trackProductView } from "@/lib/marketing";
import { ProductGallery } from "@/components/product/ProductGallery";
import { ProductCard } from "@/components/product/ProductCard";
import { Rating } from "@/components/product/Rating";
import { Reviews } from "@/components/product/Reviews";
import { Button, EmptyState, Price, QuantityStepper, SectionHeading } from "@/components/ui/primitives";
import { useCartStore } from "@/store/cartStore";
import { useUiStore } from "@/store/uiStore";
import { usePageTitle } from "@/utils/usePageTitle";
import { cn } from "@/utils/cn";

const SIZE_GUIDE = [
  ["XS", "80–84", "62–66", "86–90"],
  ["S", "84–90", "66–72", "90–96"],
  ["M", "90–96", "72–78", "96–102"],
  ["L", "96–104", "78–86", "102–110"],
  ["XL", "104–112", "86–94", "110–118"],
];

export default function ProductPage() {
  const { slug } = useParams<{ slug: string }>();
  const product = useCatalogStore((s) => s.getProductBySlug(slug));
  const products = useCatalogStore((s) => s.products);
  const [size, setSize] = useState<string | null>(null);
  const [color, setColor] = useState<string | null>(product?.colors[0]?.name ?? null);
  const [qty, setQty] = useState(1);
  const [sizeError, setSizeError] = useState(false);
  const [guideOpen, setGuideOpen] = useState(false);

  const addItem = useCartStore((s) => s.addItem);
  const { wishlist, toggleWishlist, pushToast, setMiniCartOpen } = useUiStore();

  const related = useMemo(
    () => (product ? products.filter((candidate) => candidate.category === product.category && candidate.id !== product.id).slice(0, 4) : []),
    [product, products],
  );

  usePageTitle(product?.name ?? "Product not found", product?.description);

  useEffect(() => {
    if (!product) return;
    trackProductView({ productId: product.id, slug: product.slug, category: product.category });
  }, [product?.id]);

  if (!product) {
    return (
      <div className="mx-auto max-w-3xl">
        <EmptyState
          icon={TriangleAlert}
          title="Product not found"
          body="The piece you're looking for has sold through or moved. Explore the current collection instead."
          actionLabel="Shop the collection"
          actionTo="/shop"
        />
      </div>
    );
  }

  const wished = wishlist.includes(product.id);
  const lowStockMessage = `Low stock${product.availableStock ? ` - ${product.availableStock} left` : ""} - ships within 24 hours`;

  const addToBag = async () => {
    if (!size) {
      setSizeError(true);
      pushToast("Select a size first");
      return;
    }
    setSizeError(false);
    try {
      await addItem(product.id, size, qty, color);
      trackAddToCart({ productId: product.id, slug: product.slug, quantity: qty });
    pushToast(`${product.name} — added to bag`);
    setMiniCartOpen(true);
    } catch (error) {
      pushToast(error instanceof Error ? error.message : "Could not add to bag");
    }
  };

  return (
    <div className="bg-paper dark:bg-nox">
      {/* Breadcrumb */}
      <nav aria-label="Breadcrumb" className="mx-auto max-w-[1440px] px-4 pt-6 sm:px-6 lg:px-10">
        <ol className="flex flex-wrap items-center gap-1.5 text-[11px] font-semibold tracking-[0.1em] uppercase text-smoke dark:text-linen-dim">
          <li><Link to="/" className="hover:text-bronze">Home</Link></li>
          <li aria-hidden><ChevronRight className="h-3 w-3" /></li>
          <li><Link to="/shop" className="hover:text-bronze">Shop</Link></li>
          <li aria-hidden><ChevronRight className="h-3 w-3" /></li>
          <li>
            <Link to={`/shop?category=${product.category}`} className="capitalize hover:text-bronze">
              {product.category}
            </Link>
          </li>
          <li aria-hidden><ChevronRight className="h-3 w-3" /></li>
          <li aria-current="page" className="text-ink dark:text-linen">{product.name}</li>
        </ol>
      </nav>

      <div className="mx-auto grid max-w-[1440px] gap-10 px-4 py-8 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-10 lg:py-12">
        <ProductGallery product={product} />

        {/* Info */}
        <div className="flex flex-col gap-6 lg:py-2">
          <div className="space-y-3">
            <p className="text-[11px] font-bold tracking-[0.22em] uppercase text-bronze">{product.category}</p>
            <h1 className="font-display text-3xl leading-tight text-ink sm:text-4xl dark:text-linen">
              {product.name}
            </h1>
            <Rating value={product.rating} reviews={product.reviews} />
            <Price amount={product.price} compareAt={product.compareAt} large />
          </div>

          <p className="border-y border-line py-5 text-sm leading-relaxed text-smoke dark:border-line-dark dark:text-linen-dim">
            {product.description}
          </p>

          {/* Colour */}
          <fieldset>
            <legend className="mb-2.5 text-[11px] font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">
              Colour — <span className="text-smoke dark:text-linen-dim">{color}</span>
            </legend>
            <div className="flex gap-2.5">
              {product.colors.map((swatch) => (
                <button
                  key={swatch.name}
                  type="button"
                  aria-pressed={color === swatch.name}
                  aria-label={`Colour ${swatch.name}`}
                  onClick={() => setColor(swatch.name)}
                  className={cn(
                    "h-9 w-9 rounded-full border transition-all",
                    color === swatch.name
                      ? "scale-105 border-ink ring-2 ring-ink ring-offset-2 ring-offset-paper dark:border-linen dark:ring-linen dark:ring-offset-nox"
                      : "border-line hover:scale-105 dark:border-line-dark"
                  )}
                  style={{ backgroundColor: swatch.hex }}
                />
              ))}
            </div>
          </fieldset>

          {/* Size */}
          <fieldset>
            <legend className="mb-2.5 flex w-full items-center justify-between text-[11px] font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">
              <span>
                Size {sizeError && <span className="ml-2 text-red-700 normal-case dark:text-red-400" role="alert">— please select</span>}
              </span>
              {product.sizes.length > 1 && (
                <button
                  type="button"
                  onClick={() => setGuideOpen(true)}
                  className="font-semibold tracking-[0.12em] text-smoke underline-offset-4 hover:text-bronze hover:underline dark:text-linen-dim"
                >
                  Size guide
                </button>
              )}
            </legend>
            <div className={cn("flex flex-wrap gap-2", sizeError && "rounded-sm ring-2 ring-red-700/60 ring-offset-2 ring-offset-paper dark:ring-offset-nox")}>
              {product.sizes.map((option) => (
                <button
                  key={option}
                  type="button"
                  aria-pressed={size === option}
                  onClick={() => {
                    setSize(option);
                    setSizeError(false);
                  }}
                  className={cn(
                    "min-w-12 border px-4 py-3 text-xs font-bold transition-all",
                    size === option
                      ? "border-ink bg-ink text-paper dark:border-linen dark:bg-linen dark:text-nox"
                      : "border-line text-ink hover:border-ink dark:border-line-dark dark:text-linen dark:hover:border-linen"
                  )}
                >
                  {option}
                </button>
              ))}
            </div>
          </fieldset>

          {/* Quantity + CTA */}
          <div className="flex flex-wrap items-stretch gap-3">
            <QuantityStepper value={qty} onChange={setQty} ariaLabel={`Quantity for ${product.name}`} />
            <Button onClick={addToBag} icon={ShoppingBag} size="lg" className="flex-1" disabled={!product.inStock}>
              {product.inStock ? "Add to Bag" : "Sold Out"}
            </Button>
            <button
              type="button"
              aria-pressed={wished}
              aria-label={wished ? `Remove ${product.name} from wishlist` : `Add ${product.name} to wishlist`}
              onClick={() => {
                toggleWishlist(product.id);
                pushToast(wished ? "Removed from wishlist" : "Saved to wishlist");
              }}
              className={cn(
                "flex w-13 items-center justify-center border transition-all hover:scale-105",
                wished
                  ? "border-bronze bg-bronze text-cream"
                  : "border-line text-ink hover:border-ink dark:border-line-dark dark:text-linen dark:hover:border-linen"
              )}
            >
              <Heart className={cn("h-5 w-5", wished && "fill-cream")} aria-hidden />
            </button>
          </div>

          {product.inStock && product.inventoryStatus !== "low_stock" && (
          <p className="flex items-center gap-2 text-xs font-semibold text-bronze">
            <Package className="h-4 w-4" aria-hidden />
            In stock — ships within 24 hours, free over $200
          </p>
          )}
          {product.inStock && product.inventoryStatus === "low_stock" && (
            <p className="flex items-center gap-2 text-xs font-semibold text-bronze">
              <Package className="h-4 w-4" aria-hidden />
              {lowStockMessage}
            </p>
          )}
          {!product.inStock && (
            <p className="flex items-center gap-2 text-xs font-semibold text-red-700 dark:text-red-400">
              <Package className="h-4 w-4" aria-hidden />
              Out of stock
            </p>
          )}

          {/* Accordions */}
          <div>
            {[
              { title: "Details & Care", body: product.details },
              {
                title: "Shipping & Returns",
                body: [
                  "Complimentary carbon-neutral shipping on orders over $200 (€185 / £160).",
                  "Standard delivery 2–5 business days; express 1–2 business days.",
                  "30-day returns and free size exchanges — no questions asked.",
                ],
              },
            ].map((section) => (
              <details key={section.title} className="group border-b border-line dark:border-line-dark">
                <summary className="flex cursor-pointer list-none items-center justify-between py-4 text-xs font-bold tracking-[0.16em] uppercase text-ink dark:text-linen [&::-webkit-details-marker]:hidden">
                  {section.title}
                  <span aria-hidden className="transition-transform duration-300 group-open:rotate-45">+</span>
                </summary>
                <ul className="space-y-2 pb-5 text-sm leading-relaxed text-smoke dark:text-linen-dim">
                  {section.body.map((line) => (
                    <li key={line} className="flex gap-2.5">
                      <span aria-hidden className="mt-[7px] h-1 w-1 shrink-0 rounded-full bg-bronze" />
                      {line}
                    </li>
                  ))}
                </ul>
              </details>
            ))}
          </div>
        </div>
      </div>

      {/* Reviews */}
      <Reviews slug={product.slug} productName={product.name} />

      {/* Related */}
      <section className="mx-auto max-w-[1440px] px-4 pb-16 sm:px-6 lg:px-10 lg:pb-24" aria-labelledby="related-heading">
        <SectionHeading kicker="Complete the Look" title="You May Also Like" />
        <h2 id="related-heading" className="sr-only">Related products</h2>
        <div className="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 lg:grid-cols-4">
          {related.map((item) => (
            <ProductCard key={item.id} product={item} />
          ))}
        </div>
      </section>

      {/* Size guide modal */}
      {guideOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-ink/50" onClick={() => setGuideOpen(false)} aria-hidden />
          <div
            role="dialog"
            aria-modal="true"
            aria-label="Size guide"
            className="relative w-full max-w-lg bg-cream p-6 shadow-2xl sm:p-8 dark:bg-nox2"
          >
            <div className="mb-5 flex items-center justify-between">
              <h2 className="font-display text-2xl text-ink dark:text-linen">Size Guide</h2>
              <button
                type="button"
                onClick={() => setGuideOpen(false)}
                aria-label="Close size guide"
                className="flex h-10 w-10 items-center justify-center rounded-full hover:bg-fog dark:hover:bg-nox3"
              >
                <X className="h-5 w-5 text-ink dark:text-linen" aria-hidden />
              </button>
            </div>
            <table className="w-full text-center text-sm">
              <caption className="sr-only">Body measurements in centimetres by size</caption>
              <thead>
                <tr className="border-b border-line text-[10px] font-bold tracking-[0.14em] uppercase text-smoke dark:border-line-dark dark:text-linen-dim">
                  <th scope="col" className="py-2">Size</th>
                  <th scope="col" className="py-2">Chest (cm)</th>
                  <th scope="col" className="py-2">Waist (cm)</th>
                  <th scope="col" className="py-2">Hip (cm)</th>
                </tr>
              </thead>
              <tbody className="text-ink dark:text-linen">
                {SIZE_GUIDE.map((row) => (
                  <tr key={row[0]} className="border-b border-line/60 dark:border-line-dark/60">
                    {row.map((cell, i) => (
                      <td key={i} className={cn("py-2.5", i === 0 && "font-bold")}>{cell}</td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
            <p className="mt-4 flex items-center gap-2 text-xs text-smoke dark:text-linen-dim">
              <RefreshCcw className="h-3.5 w-3.5" aria-hidden /> Between sizes? Exchange free within 30 days.
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
