import { useEffect, useMemo, useState } from "react";
import { Link, Navigate, useLocation } from "react-router-dom";
import { Package, RefreshCcw, Search, ShieldAlert } from "lucide-react";
import { api } from "@/lib/api";
import type { PaginationMeta } from "@/store/catalogStore";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { useCurrencyStore } from "@/store/currencyStore";
import { Button, EmptyState, Field, Input, Select, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

interface AdminOrderLine {
  id: string;
  productId: string;
  productName: string;
  productSlug: string;
  size: string;
  qty: number;
  unitPrice: number;
  lineTotal: number;
}

interface AdminOrder {
  id: string;
  status: string;
  statusCode: string;
  paymentStatus: string;
  paymentProvider: string;
  countryCode: string;
  currency: string;
  method: string;
  subtotal: number;
  discount: number;
  shipping: number;
  tax: number;
  total: number;
  promoCode: string | null;
  createdAt: string;
  customer: { id: number; name: string; email: string } | null;
  address: {
    firstName: string | null;
    lastName: string | null;
    email: string | null;
    address: string | null;
    city: string | null;
    postalCode: string | null;
    country: string | null;
  };
  payment: { provider: string; status: string; reference: string; amount: number; currency: string; method: string } | null;
  lines: AdminOrderLine[];
}

interface OrderCollection {
  data: AdminOrder[];
  meta?: PaginationMeta;
}

interface OrderResponse {
  data: AdminOrder;
}

const STATUSES = ["pending", "confirmed", "processing", "shipped", "delivered", "cancelled"] as const;

const ORDER_STATUS_TONE: Record<string, string> = {
  pending: "bg-bronze/15 text-bronze",
  confirmed: "bg-sky-800/15 text-sky-800 dark:text-sky-300",
  processing: "bg-ink text-paper dark:bg-linen dark:text-nox",
  shipped: "bg-violet-800/15 text-violet-800 dark:text-violet-300",
  delivered: "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300",
  cancelled: "bg-red-800/10 text-red-800 dark:text-red-300",
};

function paymentTone(status: string) {
  if (status === "paid") return "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300";
  if (status === "failed" || status === "canceled") return "bg-red-800/10 text-red-800 dark:text-red-300";
  return "bg-bronze/15 text-bronze";
}

export default function AdminOrdersPage() {
  usePageTitle("Orders");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const format = useCurrencyStore((state) => state.format);
  const [orders, setOrders] = useState<AdminOrder[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [paymentStatus, setPaymentStatus] = useState("all");
  const [loading, setLoading] = useState(true);
  const [savingId, setSavingId] = useState<string | undefined>();
  const [nextStatus, setNextStatus] = useState<Record<string, string>>({});
  const [error, setError] = useState("");

  const searchParams = useMemo(() => {
    const params = new URLSearchParams({ status, payment_status: paymentStatus, per_page: "100" });
    if (query.trim()) params.set("q", query.trim());
    return params.toString();
  }, [query, status, paymentStatus]);

  useEffect(() => {
    if (user?.role !== "admin") return;
    let cancelled = false;
    setLoading(true);
    setError("");
    api<OrderCollection>(`/admin/orders?${searchParams}`)
      .then((response) => {
        if (cancelled) return;
        setOrders(response.data);
        setMeta(response.meta);
        setNextStatus(Object.fromEntries(response.data.map((order) => [order.id, order.statusCode])));
      })
      .catch((requestError) => {
        if (!cancelled) setError(requestError instanceof Error ? requestError.message : "Orders could not be loaded.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [searchParams, user?.role]);

  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />;

  if (user.role !== "admin") {
    return (
      <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <EmptyState icon={ShieldAlert} title="Admin access required" body="Order management is only available to store administrators." actionLabel="Back to account" actionTo="/account" />
      </div>
    );
  }

  const updateStatus = async (order: AdminOrder) => {
    const target = nextStatus[order.id];
    if (!target || target === order.statusCode) return;
    setSavingId(order.id);
    try {
      const response = await api<OrderResponse>(`/admin/orders/${encodeURIComponent(order.id)}/status`, {
        method: "PATCH",
        body: JSON.stringify({ status: target }),
      });
      setOrders((current) => current.map((item) => (item.id === order.id ? response.data : item)));
      pushToast("Order status updated");
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Order update failed");
    } finally {
      setSavingId(undefined);
    }
  };

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Orders</h1>
        </div>
        <div className="flex gap-4">
          <Link to="/admin/products" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Inventory</Link>
          <Link to="/admin/customers" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Customers</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_180px_180px_auto]">
        <Field label="Search orders" htmlFor="admin-order-search">
          <div className="relative">
            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-smoke" aria-hidden />
            <Input id="admin-order-search" value={query} onChange={(event) => setQuery(event.target.value)} className="pl-10" placeholder="Order #, promo, customer" />
          </div>
        </Field>
        <Field label="Status" htmlFor="admin-order-status">
          <Select id="admin-order-status" value={status} onChange={(event) => setStatus(event.target.value)}>
            <option value="all">All</option>
            {STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
          </Select>
        </Field>
        <Field label="Payment" htmlFor="admin-order-payment">
          <Select id="admin-order-payment" value={paymentStatus} onChange={(event) => setPaymentStatus(event.target.value)}>
            <option value="all">All</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="failed">Failed</option>
            <option value="canceled">Canceled</option>
          </Select>
        </Field>
        <Button type="button" variant="outline" icon={RefreshCcw} className="self-end" onClick={() => setQuery((current) => current.trim())}>Refresh</Button>
      </div>

      <div className="mt-8">
        {loading && <div className="flex min-h-60 items-center justify-center"><Spinner /></div>}
        {!loading && error && <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2"><EmptyState icon={Package} title="Orders unavailable" body={error} /></div>}
        {!loading && !error && orders.length === 0 && <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2"><EmptyState icon={Package} title="No orders found" body="Try a different search or filter." /></div>}
        {!loading && !error && orders.length > 0 && (
          <div className="space-y-4">
            <p className="text-xs text-smoke dark:text-linen-dim">Showing {meta?.total ?? orders.length} orders.</p>
            {orders.map((order) => (
              <section key={order.id} className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
                <details className="group">
                  <summary className="flex cursor-pointer list-none flex-wrap items-center justify-between gap-4 p-5 [&::-webkit-details-marker]:hidden">
                    <div className="flex flex-wrap items-center gap-x-8 gap-y-2">
                      <div>
                        <p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Order</p>
                        <p className="font-semibold text-ink dark:text-linen">{order.id}</p>
                      </div>
                      <div>
                        <p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Customer</p>
                        <p className="text-sm text-ink dark:text-linen">{order.customer?.email ?? "Guest"}</p>
                      </div>
                      <div>
                        <p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Placed</p>
                        <p className="text-sm text-ink dark:text-linen">{new Date(order.createdAt).toLocaleDateString("en-US", { day: "numeric", month: "short", year: "numeric" })}</p>
                      </div>
                      <div>
                        <p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Total</p>
                        <p className="font-semibold text-ink dark:text-linen">{format(order.total)}</p>
                      </div>
                      <div>
                        <p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Provider</p>
                        <p className="text-sm text-ink dark:text-linen">{order.paymentProvider || "—"}</p>
                      </div>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                      <span className={`px-3 py-1 text-[10px] font-bold tracking-[0.14em] uppercase ${ORDER_STATUS_TONE[order.statusCode] ?? "bg-ink text-paper"}`}>{order.status}</span>
                      <span className={`px-3 py-1 text-[10px] font-bold tracking-[0.14em] uppercase ${paymentTone(order.paymentStatus)}`}>{order.paymentStatus}</span>
                    </div>
                  </summary>
                  <div className="grid gap-6 border-t border-line px-5 py-5 dark:border-line-dark lg:grid-cols-[1fr_260px]">
                    <div>
                      <h2 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Items</h2>
                      <ul className="mt-3 space-y-3">
                        {order.lines.map((line) => (
                          <li key={line.id} className="flex items-center justify-between gap-3 text-sm">
                            <span className="min-w-0 flex-1 truncate font-semibold text-ink dark:text-linen">{line.productName}</span>
                            <span className="shrink-0 text-xs text-smoke dark:text-linen-dim">Size {line.size} x {line.qty}</span>
                            <span className="shrink-0 font-semibold text-ink dark:text-linen">{format(line.lineTotal)}</span>
                          </li>
                        ))}
                      </ul>
                      <dl className="mt-4 max-w-xs space-y-2 border-t border-line pt-4 text-sm dark:border-line-dark">
                        <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Subtotal</dt><dd className="font-semibold text-ink dark:text-linen">{format(order.subtotal)}</dd></div>
                        {order.discount > 0 && <div className="flex justify-between text-bronze"><dt>Discount</dt><dd className="font-semibold">−{format(order.discount)}</dd></div>}
                        <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Shipping ({order.method})</dt><dd className="font-semibold text-ink dark:text-linen">{format(order.shipping)}</dd></div>
                        <div className="flex justify-between border-t border-line pt-2 dark:border-line-dark"><dt className="font-semibold text-ink dark:text-linen">Total</dt><dd className="font-semibold text-ink dark:text-linen">{format(order.total)}</dd></div>
                      </dl>
                    </div>
                    <div className="space-y-4">
                      <div>
                        <h2 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Ship to</h2>
                        <p className="mt-2 text-sm leading-relaxed text-smoke dark:text-linen-dim">
                          {order.address.firstName} {order.address.lastName}<br />
                          {order.address.address}, {order.address.city} {order.address.postalCode}<br />
                          {order.address.country}
                        </p>
                      </div>
                      {order.payment && (
                        <div>
                          <h2 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Payment</h2>
                          <p className="mt-2 text-sm text-smoke dark:text-linen-dim">
                            {order.payment.provider} · {order.payment.method ?? "—"} · {order.payment.reference}
                          </p>
                        </div>
                      )}
                      <div className="flex flex-wrap items-end gap-2">
                        <Field label="Update status" htmlFor={`order-status-${order.id}`}>
                          <Select id={`order-status-${order.id}`} value={nextStatus[order.id] ?? order.statusCode} onChange={(event) => setNextStatus((current) => ({ ...current, [order.id]: event.target.value }))}>
                            {STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
                          </Select>
                        </Field>
                        <Button type="button" size="sm" variant="outline" loading={savingId === order.id} disabled={nextStatus[order.id] === order.statusCode} onClick={() => void updateStatus(order)}>Save</Button>
                      </div>
                    </div>
                  </div>
                </details>
              </section>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}