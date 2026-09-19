import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Lock, ShoppingBag, Tag } from "lucide-react";
import { useCartStore } from "@/store/cartStore";
import { useCurrencyStore } from "@/store/currencyStore";
import { useCatalogStore } from "@/store/catalogStore";
import { Button, EmptyState, LinkButton, Price, QuantityStepper, Input } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

export const FREE_SHIPPING_THRESHOLD = 200;
export const STANDARD_SHIPPING = 9.95;

export function useCartTotals() {
  const items = useCartStore((s) => s.items);
  const summary = useCartStore((s) => s.summary);
  const products = useCatalogStore((s) => s.products);
  const lines = items
    .map((line) => ({ line, product: products.find((p) => p.id === line.productId) }))
    .filter((entry) => Boolean(entry.product) || Boolean(entry.line.productName));
  const subtotal = summary.subtotal || lines.reduce((sum, { line, product }) => sum + (line.lineTotal ?? (line.unitPrice ?? product?.price ?? 0) * line.qty), 0);
  const count = lines.reduce((sum, { line }) => sum + line.qty, 0);
  return { lines, subtotal, count, summary };
}

export default function CartPage() {
  usePageTitle("Your Bag");
  const { setQty, removeItem, promo, setPromo, loadCart, summary, error } = useCartStore();
  const format = useCurrencyStore((s) => s.format);
  const { lines, count } = useCartTotals();

  const [promoInput, setPromoInput] = useState("");
  const [promoError, setPromoError] = useState("");

  useEffect(() => {
    void loadCart();
  }, [loadCart]);

  const applyPromo = async () => {
    try {
      await setPromo(promoInput.trim().toUpperCase());
      setPromoError("");
    } catch {
      setPromoError(useCartStore.getState().error ?? "That code is not valid.");
    }
  };

  if (lines.length === 0) {
    return (
      <div className="mx-auto max-w-3xl">
        <EmptyState
          icon={ShoppingBag}
          title="Your bag is empty"
          body="Considered pieces take a moment to choose. Start with this season's new arrivals."
          actionLabel="Shop New Arrivals"
          actionTo="/shop?sort=newest"
        />
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-[1440px] px-4 py-10 sm:px-6 lg:px-10 lg:py-16">
      <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">JAAJ</p>
      <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">
        Your Bag <span className="text-xl text-smoke align-middle dark:text-linen-dim">({count})</span>
      </h1>

      <div className="mt-10 grid gap-12 lg:grid-cols-[1fr_400px]">
        <ul className="divide-y divide-line border-y border-line dark:divide-line-dark dark:border-line-dark">
          {lines.map(({ line, product }) => {
            const name = product?.name ?? line.productName ?? "Cart item";
            const slug = product?.slug ?? line.productSlug;
            const unitPrice = line.unitPrice ?? product?.price ?? 0;
            const lineTotal = line.lineTotal ?? unitPrice * line.qty;
            return (
              <li key={line.id} className="flex gap-5 py-6 sm:gap-7">
                <Link to={slug ? `/product/${slug}` : "/shop"} className="shrink-0">
                  <img
                    src={product?.images[0] ?? line.image ?? "/imagery/hero-primary.jpg"}
                    alt={product?.alt ?? name}
                    width={300}
                    height={400}
                    loading="lazy"
                    decoding="async"
                    className="h-36 w-28 bg-fog object-cover sm:h-44 sm:w-36 dark:bg-nox2"
                  />
                </Link>
                <div className="flex min-w-0 flex-1 flex-col justify-between gap-3">
                  <div className="flex items-start justify-between gap-4">
                    <div className="min-w-0">
                      <Link to={slug ? `/product/${slug}` : "/shop"} className="font-display text-xl leading-snug text-ink transition-colors hover:text-bronze dark:text-linen">
                        {name}
                      </Link>
                      <p className="mt-1 text-xs tracking-[0.12em] uppercase text-smoke dark:text-linen-dim">
                        {product?.category ?? "Item"} / Size {line.size}{line.color ? ` / ${line.color}` : ""}
                      </p>
                      <Price amount={unitPrice} className="mt-2" />
                      {line.availableStock !== undefined && line.availableStock <= line.qty && (
                        <p className="mt-2 text-xs font-semibold text-bronze">Only {line.availableStock} available</p>
                      )}
                    </div>
                    <Price amount={lineTotal} large className="shrink-0" />
                  </div>
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <QuantityStepper
                      value={line.qty}
                      max={line.availableStock ?? 12}
                      onChange={(qty) => void setQty(line.id, qty)}
                      ariaLabel={`Quantity for ${name}`}
                    />
                    <button
                      type="button"
                      onClick={() => void removeItem(line.id)}
                      aria-label={`Remove ${name} from bag`}
                      className="text-[11px] font-bold tracking-[0.14em] uppercase text-smoke underline-offset-4 transition-colors hover:text-red-700 hover:underline dark:text-linen-dim"
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </li>
            );
          })}
        </ul>

        <aside className="h-fit space-y-6 border border-line bg-cream p-6 sm:p-8 lg:sticky lg:top-32 dark:border-line-dark dark:bg-nox2" aria-label="Order summary">
          <h2 className="text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">Order Summary</h2>

          {promo ? (
            <p className="flex items-center justify-between border border-bronze/40 bg-bronze/10 px-4 py-3 text-xs font-bold tracking-[0.12em] uppercase text-bronze">
              <span className="flex items-center gap-2"><Tag className="h-3.5 w-3.5" aria-hidden /> {promo} applied</span>
              <button type="button" onClick={() => void setPromo(null)} className="underline underline-offset-2">Remove</button>
            </p>
          ) : (
            <div>
              <label htmlFor="promo" className="sr-only">Promo code</label>
              <div className="flex gap-2">
                <Input
                  id="promo"
                  placeholder="Promo code (try JAAJ10)"
                  value={promoInput}
                  onChange={(e) => setPromoInput(e.target.value)}
                  onKeyDown={(e) => e.key === "Enter" && void applyPromo()}
                />
                <Button type="button" variant="outline" onClick={() => void applyPromo()}>Apply</Button>
              </div>
              {(promoError || error) && (
                <p role="alert" className="mt-1.5 text-xs font-semibold text-red-700 dark:text-red-400">{promoError || error}</p>
              )}
            </div>
          )}

          <dl className="space-y-3 border-y border-line py-5 text-sm dark:border-line-dark">
            <div className="flex justify-between">
              <dt className="text-smoke dark:text-linen-dim">Subtotal</dt>
              <dd className="font-semibold text-ink dark:text-linen">{format(summary.subtotal)}</dd>
            </div>
            {summary.discount > 0 && (
              <div className="flex justify-between text-bronze">
                <dt>Member code</dt>
                <dd className="font-semibold">-{format(summary.discount)}</dd>
              </div>
            )}
            <div className="flex justify-between">
              <dt className="text-smoke dark:text-linen-dim">Shipping</dt>
              <dd className="font-semibold text-ink dark:text-linen">
                {summary.shipping === 0 ? "Complimentary" : format(summary.shipping)}
              </dd>
            </div>
          </dl>

          <div className="flex items-baseline justify-between">
            <span className="text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">Total</span>
            <Price amount={summary.total} large />
          </div>

          <LinkButton to="/checkout" size="lg" className="w-full" icon={Lock}>
            Secure Checkout
          </LinkButton>
          <Link to="/shop" className="block text-center text-[11px] font-bold tracking-[0.16em] uppercase text-smoke underline-offset-4 transition-colors hover:text-ink hover:underline dark:text-linen-dim dark:hover:text-linen">
            Continue shopping
          </Link>
          <p className="pt-1 text-center text-[10px] tracking-[0.1em] uppercase text-smoke/80 dark:text-linen-dim/80">
            30-day returns / Free size exchanges
          </p>
        </aside>
      </div>
    </div>
  );
}
