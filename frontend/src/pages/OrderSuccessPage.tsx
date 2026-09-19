import { useEffect, useState } from "react";
import { Link, useParams, useSearchParams } from "react-router-dom";
import { Check, Mail, Package } from "lucide-react";
import { api } from "@/lib/api";
import { useOrderStore, type Order } from "@/store/orderStore";
import { useCurrencyStore } from "@/store/currencyStore";
import { useCatalogStore } from "@/store/catalogStore";
import { EmptyState, LinkButton, Price, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

interface OrderLookup {
  data: Order;
}

export default function OrderSuccessPage() {
  usePageTitle("Order Confirmed");
  const { orderId } = useParams<{ orderId: string }>();
  const [params] = useSearchParams();
  const checkoutToken = params.get("checkout_token");
  const order = useOrderStore((s) => s.getOrder(orderId ?? null));
  const addOrder = useOrderStore((s) => s.addOrder);
  const format = useCurrencyStore((s) => s.format);
  const products = useCatalogStore((s) => s.products);
  const [loading, setLoading] = useState(Boolean(orderId && !order && checkoutToken));

  useEffect(() => {
    if (!orderId || order || !checkoutToken) return;
    let cancelled = false;
    setLoading(true);
    api<OrderLookup>(`/checkout/orders/${encodeURIComponent(orderId)}?checkout_token=${encodeURIComponent(checkoutToken)}`)
      .then((response) => {
        if (cancelled) return;
        addOrder(response.data);
      })
      .catch(() => {
        if (cancelled) return;
        setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [addOrder, checkoutToken, order, orderId]);

  if (loading) {
    return (
      <div className="mx-auto flex max-w-3xl items-center justify-center px-4 py-24 sm:px-6">
        <Spinner />
      </div>
    );
  }

  if (!order) {
    return (
      <div className="mx-auto max-w-3xl">
        <EmptyState
          icon={Package}
          title="No recent order found"
          body="If you just checked out, your confirmation may still be processing. Your orders always live in your account."
          actionLabel="View my account"
          actionTo="/account"
        />
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:py-20">
      <div className="flex flex-col items-center gap-4 text-center">
        <span className="flex h-16 w-16 items-center justify-center rounded-full bg-ink text-paper dark:bg-linen dark:text-nox" aria-hidden>
          <Check className="h-8 w-8" />
        </span>
        <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Order {order.id}</p>
        <h1 className="font-display text-4xl text-ink sm:text-5xl dark:text-linen">Thank you — it's on its way.</h1>
        <p className="flex max-w-md items-center justify-center gap-2 text-sm leading-relaxed text-smoke dark:text-linen-dim">
          <Mail className="h-4 w-4 shrink-0" aria-hidden />
          A confirmation is on its way to {order.address.email}. Expected delivery:{" "}
          {order.method === "express" ? "1–2" : "2–5"} business days.
        </p>
      </div>

      <div className="mt-12 border border-line bg-cream p-6 sm:p-8 dark:border-line-dark dark:bg-nox2">
        <h2 className="text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">Your order</h2>
        <ul className="mt-5 divide-y divide-line dark:divide-line-dark">
          {order.lines.map((line) => {
            const product = products.find((p) => p.id === line.productId);
            if (!product) return null;
            return (
              <li key={line.id} className="flex items-center gap-4 py-4">
                <img
                  src={product.images[0]}
                  alt={product.alt}
                  width={120}
                  height={160}
                  loading="lazy"
                  decoding="async"
                  className="h-16 w-12 bg-fog object-cover dark:bg-nox3"
                />
                <span className="min-w-0 flex-1">
                  <span className="block text-sm font-semibold text-ink dark:text-linen">{product.name}</span>
                  <span className="text-xs text-smoke dark:text-linen-dim">Size {line.size} · Qty {line.qty}</span>
                </span>
                <Price amount={product.price * line.qty} />
              </li>
            );
          })}
        </ul>
        <dl className="mt-4 space-y-2 border-t border-line pt-4 text-sm dark:border-line-dark">
          <div className="flex justify-between">
            <dt className="text-smoke dark:text-linen-dim">Subtotal</dt>
            <dd className="font-semibold text-ink dark:text-linen">{format(order.subtotal)}</dd>
          </div>
          {order.discount > 0 && (
            <div className="flex justify-between text-bronze">
              <dt>Member code (10%)</dt>
              <dd className="font-semibold">−{format(order.discount)}</dd>
            </div>
          )}
          <div className="flex justify-between">
            <dt className="text-smoke dark:text-linen-dim">Shipping ({order.method})</dt>
            <dd className="font-semibold text-ink dark:text-linen">{order.shipping === 0 ? "Complimentary" : format(order.shipping)}</dd>
          </div>
          <div className="flex justify-between border-t border-line pt-3 dark:border-line-dark">
            <dt className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Total</dt>
            <dd><Price amount={order.total} large /></dd>
          </div>
        </dl>
        <p className="mt-4 text-xs leading-relaxed text-smoke dark:text-linen-dim">
          Shipping to: {order.address.firstName} {order.address.lastName}, {order.address.address},{" "}
          {order.address.city} {order.address.postalCode}, {order.address.country}
        </p>
      </div>

      <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
        <LinkButton to="/shop" size="lg">Continue Shopping</LinkButton>
        <Link
          to="/account?tab=orders"
          className="inline-flex h-13 items-center border border-ink/25 px-8 text-xs font-bold tracking-[0.16em] uppercase text-ink transition-colors hover:border-ink dark:border-linen/30 dark:text-linen dark:hover:border-linen"
        >
          Track in My Account
        </Link>
      </div>
    </div>
  );
}
