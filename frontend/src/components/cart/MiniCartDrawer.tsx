import { useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import { ShoppingBag, Truck, X } from "lucide-react";
import { useCartStore } from "@/store/cartStore";
import { useUiStore } from "@/store/uiStore";
import { useCurrencyStore } from "@/store/currencyStore";
import { useCatalogStore } from "@/store/catalogStore";
import { LinkButton, Price, QuantityStepper } from "@/components/ui/primitives";
import { cn } from "@/utils/cn";

const FREE_SHIPPING_THRESHOLD = 200;

export function MiniCartDrawer() {
  const navigate = useNavigate();
  const { miniCartOpen, setMiniCartOpen } = useUiStore();
  const { items, setQty, removeItem, summary } = useCartStore();
  const format = useCurrencyStore((s) => s.format);
  const products = useCatalogStore((s) => s.products);

  const lines = items
    .map((line) => ({ line, product: products.find((p) => p.id === line.productId) }))
    .filter((entry) => Boolean(entry.product) || Boolean(entry.line.productName)) as Array<{
    line: (typeof items)[number];
    product?: (typeof products)[number];
  }>;

  const subtotal = summary.subtotal || lines.reduce((sum, { line, product }) => sum + (line.lineTotal ?? (line.unitPrice ?? product?.price ?? 0) * line.qty), 0);
  const progress = Math.min(1, subtotal / FREE_SHIPPING_THRESHOLD);

  useEffect(() => {
    const onKey = (event: KeyboardEvent) => {
      if (event.key === "Escape") setMiniCartOpen(false);
    };
    if (miniCartOpen) {
      window.addEventListener("keydown", onKey);
      document.body.style.overflow = "hidden";
    }
    return () => {
      window.removeEventListener("keydown", onKey);
      document.body.style.overflow = "";
    };
  }, [miniCartOpen, setMiniCartOpen]);

  const close = () => setMiniCartOpen(false);

  return (
    <div
      className={cn("fixed inset-0 z-50", !miniCartOpen && "pointer-events-none")}
      aria-hidden={!miniCartOpen}
    >
      <div
        className={cn(
          "absolute inset-0 bg-ink/45 transition-opacity duration-300",
          miniCartOpen ? "opacity-100" : "opacity-0"
        )}
        onClick={close}
      />
      <aside
        role="dialog"
        aria-modal="true"
        aria-label="Shopping bag"
        className={cn(
          "absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-cream shadow-2xl transition-transform duration-300 ease-out dark:bg-nox2",
          miniCartOpen ? "translate-x-0" : "translate-x-full"
        )}
      >
        <div className="flex items-center justify-between border-b border-line px-6 py-5 dark:border-line-dark">
          <h2 className="flex items-center gap-2.5 text-sm font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">
            <ShoppingBag className="h-4 w-4" aria-hidden />
            Your Bag ({lines.reduce((n, { line }) => n + line.qty, 0)})
          </h2>
          <button
            type="button"
            onClick={close}
            aria-label="Close bag"
            className="flex h-10 w-10 items-center justify-center rounded-full text-ink transition-colors hover:bg-fog dark:text-linen dark:hover:bg-nox3"
          >
            <X className="h-5 w-5" aria-hidden />
          </button>
        </div>

        {lines.length === 0 ? (
          <div className="flex flex-1 flex-col items-center justify-center gap-4 px-8 text-center">
            <span className="flex h-16 w-16 items-center justify-center rounded-full bg-fog dark:bg-nox3">
              <ShoppingBag className="h-7 w-7 text-smoke dark:text-linen-dim" aria-hidden />
            </span>
            <p className="font-display text-2xl text-ink dark:text-linen">Your bag is empty</p>
            <p className="max-w-xs text-sm leading-relaxed text-smoke dark:text-linen-dim">
              Fill it with pieces you'll keep for years — start with the new season arrivals.
            </p>
            <LinkButton to="/shop" size="md" className="mt-2" onClickCapture={close}>
              Shop New Arrivals
            </LinkButton>
          </div>
        ) : (
          <>
            <div className="border-b border-line px-6 py-4 dark:border-line-dark">
              {subtotal >= FREE_SHIPPING_THRESHOLD ? (
                <p className="flex items-center gap-2 text-xs font-semibold text-bronze">
                  <Truck className="h-4 w-4" aria-hidden /> You've unlocked complimentary shipping.
                </p>
              ) : (
                <p className="text-xs text-smoke dark:text-linen-dim">
                  You're <strong className="text-ink dark:text-linen">{format(FREE_SHIPPING_THRESHOLD - subtotal)}</strong> away from complimentary shipping.
                </p>
              )}
              <div className="mt-2.5 h-1 overflow-hidden rounded-full bg-fog dark:bg-nox3" aria-hidden>
                <div
                  className="h-full rounded-full bg-bronze transition-all duration-500"
                  style={{ width: `${progress * 100}%` }}
                />
              </div>
            </div>

            <ul className="flex-1 divide-y divide-line overflow-y-auto px-6 dark:divide-line-dark">
              {lines.map(({ line, product }) => {
                const name = product?.name ?? line.productName ?? "Cart item";
                const slug = product?.slug ?? line.productSlug;
                const lineTotal = line.lineTotal ?? (line.unitPrice ?? product?.price ?? 0) * line.qty;
                return (
                <li key={line.id} className="flex gap-4 py-5">
                  <Link to={slug ? `/product/${slug}` : "/shop"} onClick={close} className="shrink-0">
                    <img
                      src={product?.images[0] ?? line.image ?? "/imagery/hero-primary.jpg"}
                      alt={product?.alt ?? name}
                      width={180}
                      height={240}
                      loading="lazy"
                      decoding="async"
                      className="h-28 w-[5.25rem] bg-fog object-cover dark:bg-nox3"
                    />
                  </Link>
                  <div className="flex flex-1 flex-col justify-between gap-2">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <Link
                          to={slug ? `/product/${slug}` : "/shop"}
                          onClick={close}
                          className="text-sm font-semibold text-ink transition-colors hover:text-bronze dark:text-linen"
                        >
                          {name}
                        </Link>
                        <p className="mt-0.5 text-xs text-smoke dark:text-linen-dim">Size {line.size}{line.color ? ` / ${line.color}` : ""}</p>
                      </div>
                      <Price amount={lineTotal} />
                    </div>
                    <div className="flex items-center justify-between">
                      <QuantityStepper
                        small
                        value={line.qty}
                        max={line.availableStock ?? 12}
                        onChange={(qty) => void setQty(line.id, qty)}
                        ariaLabel={`Quantity for ${name}`}
                      />
                      <button
                        type="button"
                        onClick={() => void removeItem(line.id)}
                        aria-label={`Remove ${name} from bag`}
                        className="text-[11px] font-semibold tracking-[0.12em] uppercase text-smoke underline-offset-4 transition-colors hover:text-red-700 hover:underline dark:text-linen-dim"
                      >
                        Remove
                      </button>
                    </div>
                  </div>
                </li>
                );
              })}
            </ul>

            <div className="space-y-4 border-t border-line px-6 py-5 dark:border-line-dark">
              <div className="flex items-center justify-between">
                <span className="text-xs font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">
                  Subtotal
                </span>
                <Price amount={subtotal} large />
              </div>
              <p className="text-xs text-smoke dark:text-linen-dim">
                Shipping and taxes calculated at checkout.
              </p>
              <div className="grid grid-cols-2 gap-3">
                <LinkButton to="/cart" variant="outline" onClickCapture={close} className="w-full">
                  View Bag
                </LinkButton>
                <LinkButton to="/checkout" onClickCapture={close} className="w-full">
                  Checkout
                </LinkButton>
              </div>
              <button
                type="button"
                onClick={() => {
                  close();
                  navigate("/shop");
                }}
                className="w-full text-center text-[11px] font-bold tracking-[0.16em] uppercase text-smoke underline-offset-4 transition-colors hover:text-ink hover:underline dark:text-linen-dim dark:hover:text-linen"
              >
                Continue shopping
              </button>
            </div>
          </>
        )}
      </aside>
    </div>
  );
}
