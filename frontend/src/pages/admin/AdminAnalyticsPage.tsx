import { useEffect, useMemo, useState } from "react";
import { Link, Navigate, useLocation } from "react-router-dom";
import { BarChart3, Download, ShieldAlert } from "lucide-react";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { useCurrencyStore } from "@/store/currencyStore";
import { Button, EmptyState, Field, Input, Select, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";
import { cn } from "@/utils/cn";

interface Compared {
  value: number;
  previous: number | null;
  change_percent: number | null;
}

interface OverviewData {
  range: { preset: string; from: string; to: string; timezone: string; group: string };
  revenue: Compared;
  gross_revenue: number;
  refunded_amount: number;
  orders: Compared;
  paid_orders: Compared;
  average_order_value: Compared;
  customers: {
    total_customers: number;
    purchasing_customers: number;
    new_customers: number;
    repeat_customers: number;
    guest_orders: number;
    authenticated_orders: number;
  };
  products: { active: number; low_stock: number; out_of_stock: number };
  pending_reviews: number;
  wishlist_items: number;
}

interface SalesPoint {
  period: string;
  orders: number;
  gross_revenue: number;
  refunded_amount: number;
  revenue: number;
}

interface SalesData {
  range: OverviewData["range"];
  series: SalesPoint[];
}

interface OrdersData {
  total: number;
  by_status: Array<{ status: string; count: number }>;
  by_payment_status: Array<{ status: string; count: number }>;
  refunded_orders: number;
}

interface TopProduct {
  product_id: string;
  slug: string;
  name: string;
  is_active: boolean;
  units_sold: number;
  revenue: number;
  orders_count: number;
}

interface TopCategory {
  slug: string;
  label: string;
  units_sold: number;
  revenue: number;
  orders_count: number;
}

interface InventoryData {
  active_products: number;
  out_of_stock_products: number;
  low_stock_products: number;
  total_units: number;
  low_stock_list: Array<{
    product_id: string;
    slug: string;
    name: string;
    stock_quantity: number;
    low_stock_threshold: number;
    inventory_status: string;
  }>;
}

interface ReviewsData {
  total: number;
  pending: number;
  approved: number;
  rejected: number;
  average_approved_rating: number;
  reviewed_products: number;
}

interface WishlistData {
  total_items: number;
  wishlists_with_account: number;
  guest_wishlists: number;
  top_products: Array<{ product_id: string; slug: string; name: string; saves: number }>;
}

type Preset = "today" | "7d" | "30d" | "month" | "prev_month" | "year" | "custom";

const PRESETS: Array<{ id: Preset; label: string }> = [
  { id: "today", label: "Today" },
  { id: "7d", label: "7 Days" },
  { id: "30d", label: "30 Days" },
  { id: "month", label: "This Month" },
  { id: "prev_month", label: "Previous Month" },
  { id: "year", label: "This Year" },
  { id: "custom", label: "Custom" },
];

function ChangeBadge({ change }: { change: number | null }) {
  if (change === null) {
    return <span className="text-xs text-smoke dark:text-linen-dim">no prior data</span>;
  }
  const positive = change >= 0;
  return (
    <span
      className={cn(
        "px-2 py-0.5 text-[11px] font-bold",
        positive
          ? "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300"
          : "bg-red-800/10 text-red-800 dark:text-red-300",
      )}
    >
      {positive ? "+" : ""}
      {change.toFixed(1)}% vs prior
    </span>
  );
}

function KpiCard({
  label,
  value,
  sub,
  change,
}: {
  label: string;
  value: string;
  sub?: string;
  change?: number | null;
}) {
  return (
    <div className="border border-line bg-cream p-5 dark:border-line-dark dark:bg-nox2">
      <p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">{label}</p>
      <p className="mt-1 font-display text-3xl text-ink dark:text-linen">{value}</p>
      <div className="mt-2 flex flex-wrap items-center gap-2">
        {change !== undefined && <ChangeBadge change={change} />}
        {sub && <span className="text-xs text-smoke dark:text-linen-dim">{sub}</span>}
      </div>
    </div>
  );
}

function SectionCard({
  title,
  hint,
  action,
  children,
}: {
  title: string;
  hint?: string;
  action?: React.ReactNode;
  children: React.ReactNode;
}) {
  return (
    <section className="border border-line bg-cream p-5 dark:border-line-dark dark:bg-nox2 sm:p-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="font-display text-2xl text-ink dark:text-linen">{title}</h2>
          {hint && <p className="mt-1 text-xs text-smoke dark:text-linen-dim">{hint}</p>}
        </div>
        {action}
      </div>
      <div className="mt-5">{children}</div>
    </section>
  );
}

function Bars({
  points,
  getValue,
  formatValue,
  ariaLabel,
}: {
  points: SalesPoint[];
  getValue: (point: SalesPoint) => number;
  formatValue: (value: number) => string;
  ariaLabel: string;
}) {
  const values = points.map(getValue);
  const max = Math.max(1, ...values);
  const labelEvery = Math.max(1, Math.ceil(points.length / 8));
  return (
    <div className="overflow-x-auto">
      <div className="flex min-w-[480px] items-end gap-1" role="img" aria-label={ariaLabel}>
        {points.map((point, index) => {
          const value = values[index];
          return (
            <div key={point.period} className="flex flex-1 flex-col items-center gap-1" title={`${point.period}: ${formatValue(value)}`}>
              <div className="flex h-32 w-full items-end bg-fog/60 dark:bg-nox3/60">
                <div className="w-full bg-bronze" style={{ height: `${Math.max(value > 0 ? 4 : 0, (value / max) * 100)}%` }} />
              </div>
              <span className="text-[10px] text-smoke dark:text-linen-dim">
                {index % labelEvery === 0 || index === points.length - 1 ? point.period.slice(5) : ""}
              </span>
            </div>
          );
        })}
      </div>
    </div>
  );
}

function shortDate(iso: string) {
  return new Date(iso).toLocaleDateString("en-US", { day: "numeric", month: "short" });
}

export default function AdminAnalyticsPage() {
  usePageTitle("Analytics");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const format = useCurrencyStore((state) => state.format);

  const [preset, setPreset] = useState<Preset>("30d");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [rangeError, setRangeError] = useState("");
  const [productSort, setProductSort] = useState<"revenue" | "quantity">("revenue");

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [overview, setOverview] = useState<OverviewData | null>(null);
  const [sales, setSales] = useState<SalesData | null>(null);
  const [orders, setOrders] = useState<OrdersData | null>(null);
  const [topProducts, setTopProducts] = useState<TopProduct[]>([]);
  const [topCategories, setTopCategories] = useState<TopCategory[]>([]);
  const [inventory, setInventory] = useState<InventoryData | null>(null);
  const [reviews, setReviews] = useState<ReviewsData | null>(null);
  const [wishlist, setWishlist] = useState<WishlistData | null>(null);

  const query = useMemo(() => {
    const params = new URLSearchParams({ preset });
    if (preset === "custom" && from && to) {
      params.set("from", from);
      params.set("to", to);
    }
    return params.toString();
  }, [preset, from, to]);

  useEffect(() => {
    if (user?.role !== "admin") return;
    if (preset === "custom" && (!from || !to)) return;
    if (preset === "custom" && from > to) {
      setRangeError("The start date must not be after the end date.");
      return;
    }
    setRangeError("");
    let cancelled = false;
    setLoading(true);
    setError("");
    const productQuery = `${query}&limit=10&sort=${productSort}`;
    Promise.allSettled([
      api<{ data: OverviewData }>(`/admin/analytics/overview?${query}`),
      api<{ data: SalesData }>(`/admin/analytics/sales?${query}`),
      api<{ data: OrdersData }>(`/admin/analytics/orders?${query}`),
      api<{ data: { items: TopProduct[] } }>(`/admin/analytics/products?${productQuery}`),
      api<{ data: { items: TopCategory[] } }>(`/admin/analytics/categories?${query}&limit=10`),
      api<{ data: InventoryData }>(`/admin/analytics/inventory?limit=20`),
      api<{ data: ReviewsData }>(`/admin/analytics/reviews`),
      api<{ data: WishlistData }>(`/admin/analytics/wishlist?limit=10`),
    ]).then((results) => {
      if (cancelled) return;
      const [overviewRes, salesRes, ordersRes, productsRes, categoriesRes, inventoryRes, reviewsRes, wishlistRes] = results;
      if (overviewRes.status === "rejected") {
        setError(overviewRes.reason instanceof Error ? overviewRes.reason.message : "Analytics could not be loaded.");
      } else {
        setOverview(overviewRes.value.data);
      }
      if (salesRes.status === "fulfilled") setSales(salesRes.value.data);
      if (ordersRes.status === "fulfilled") setOrders(ordersRes.value.data);
      if (productsRes.status === "fulfilled") setTopProducts(productsRes.value.data.items);
      if (categoriesRes.status === "fulfilled") setTopCategories(categoriesRes.value.data.items);
      if (inventoryRes.status === "fulfilled") setInventory(inventoryRes.value.data);
      if (reviewsRes.status === "fulfilled") setReviews(reviewsRes.value.data);
      if (wishlistRes.status === "fulfilled") setWishlist(wishlistRes.value.data);
      setLoading(false);
    });
    return () => {
      cancelled = true;
    };
  }, [query, productSort, user?.role, preset, from, to]);

  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />;

  if (user.role !== "admin") {
    return (
      <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
        <EmptyState
          icon={ShieldAlert}
          title="Admins only"
          body="Analytics are only available to store administrators."
          actionLabel="Back to account"
          actionTo="/account"
        />
      </div>
    );
  }

  const downloadCsv = () => {
    if (!sales) return;
    const rows = ["period,orders,gross_revenue,refunded_amount,revenue", ...sales.series.map((point) => (
      [point.period, point.orders, point.gross_revenue.toFixed(2), point.refunded_amount.toFixed(2), point.revenue.toFixed(2)].join(",")
    ))];
    const url = URL.createObjectURL(new Blob([rows.join("\n")], { type: "text/csv" }));
    const anchor = document.createElement("a");
    anchor.href = url;
    anchor.download = `sales-${sales.range.from.slice(0, 10)}-to-${sales.range.to.slice(0, 10)}.csv`;
    anchor.click();
    URL.revokeObjectURL(url);
    pushToast("Sales CSV downloaded");
  };

  const maxStatus = Math.max(1, ...(orders?.by_status.map((row) => row.count) ?? [1]));

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Analytics</h1>
        </div>
        <div className="flex flex-wrap gap-4">
          <Link to="/admin/products" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Inventory</Link>
          <Link to="/admin/orders" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Orders</Link>
          <Link to="/admin/customers" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Customers</Link>
          <Link to="/admin/reviews" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Reviews</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2">
        <div className="flex flex-wrap gap-2" role="group" aria-label="Date range presets">
          {PRESETS.map((option) => (
            <button
              key={option.id}
              type="button"
              aria-pressed={preset === option.id}
              onClick={() => setPreset(option.id)}
              className={cn(
                "border px-3 py-2 text-[11px] font-bold tracking-[0.12em] uppercase transition-colors",
                preset === option.id
                  ? "border-ink bg-ink text-paper dark:border-linen dark:bg-linen dark:text-nox"
                  : "border-line text-smoke hover:border-ink hover:text-ink dark:border-line-dark dark:text-linen-dim dark:hover:border-linen dark:hover:text-linen",
              )}
            >
              {option.label}
            </button>
          ))}
        </div>
        {preset === "custom" && (
          <div className="mt-3 grid gap-3 sm:grid-cols-2">
            <Field label="From" htmlFor="analytics-from">
              <Input id="analytics-from" type="date" value={from} max={to || undefined} onChange={(event) => setFrom(event.target.value)} />
            </Field>
            <Field label="To" htmlFor="analytics-to">
              <Input id="analytics-to" type="date" value={to} min={from || undefined} onChange={(event) => setTo(event.target.value)} />
            </Field>
          </div>
        )}
        {rangeError && <p className="mt-2 text-sm text-red-700 dark:text-red-400" role="alert">{rangeError}</p>}
        {overview && (
          <p className="mt-3 text-xs text-smoke dark:text-linen-dim">
            Showing {shortDate(overview.range.from)} – {shortDate(overview.range.to)} ({overview.range.timezone})
          </p>
        )}
      </div>

      {loading ? (
        <div className="flex justify-center py-16" role="status" aria-label="Loading analytics">
          <Spinner />
        </div>
      ) : error ? (
        <p className="mt-8 text-sm text-red-700 dark:text-red-400" role="alert">{error}</p>
      ) : (
        <div className="mt-8 space-y-6">
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <KpiCard label="Net revenue" value={format(overview?.revenue.value ?? 0)} change={overview?.revenue.change_percent ?? null} sub={`Gross ${format(overview?.gross_revenue ?? 0)} · refunded ${format(overview?.refunded_amount ?? 0)}`} />
            <KpiCard label="Orders" value={String(overview?.orders.value ?? 0)} change={overview?.orders.change_percent ?? null} sub={`${overview?.paid_orders.value ?? 0} paid`} />
            <KpiCard label="Average order value" value={format(overview?.average_order_value.value ?? 0)} change={overview?.average_order_value.change_percent ?? null} sub="Gross revenue ÷ paid orders" />
            <KpiCard label="Customers" value={String(overview?.customers.total_customers ?? 0)} sub={`${overview?.customers.purchasing_customers ?? 0} purchasing · ${overview?.customers.new_customers ?? 0} new`} />
            <KpiCard label="Active products" value={String(overview?.products.active ?? 0)} sub={`${overview?.products.out_of_stock ?? 0} out of stock`} />
            <KpiCard label="Low stock" value={String(overview?.products.low_stock ?? 0)} sub="At or below threshold" />
            <KpiCard label="Pending reviews" value={String(overview?.pending_reviews ?? 0)} sub="Awaiting moderation" />
            <KpiCard label="Wishlist saves" value={String(overview?.wishlist_items ?? 0)} sub="Across all wishlists" />
          </div>

          <div className="grid gap-6 lg:grid-cols-2">
            <SectionCard
              title="Revenue"
              hint={sales ? `${sales.series.length} ${sales.range.group} periods` : undefined}
              action={sales && sales.series.length > 0 ? (
                <Button type="button" size="sm" variant="outline" icon={Download} onClick={downloadCsv}>CSV</Button>
              ) : undefined}
            >
              {sales && sales.series.length > 0 ? (
                <Bars points={sales.series} getValue={(point) => point.revenue} formatValue={(value) => format(value)} ariaLabel="Revenue per period" />
              ) : (
                <EmptyState icon={BarChart3} title="No sales in range" body="No paid orders were recorded for the selected period." />
              )}
            </SectionCard>
            <SectionCard title="Orders" hint="Paid orders per period">
              {sales && sales.series.length > 0 ? (
                <Bars points={sales.series} getValue={(point) => point.orders} formatValue={(value) => String(value)} ariaLabel="Orders per period" />
              ) : (
                <EmptyState icon={BarChart3} title="No sales in range" body="No paid orders were recorded for the selected period." />
              )}
            </SectionCard>
          </div>

          <SectionCard title="Order status" hint={`Refunded orders in range: ${orders?.refunded_orders ?? 0}`}>
            {orders ? (
              <ul className="space-y-2">
                {orders.by_status.map((row) => (
                  <li key={row.status} className="flex items-center gap-3 text-sm">
                    <span className="w-28 shrink-0 font-semibold capitalize text-ink dark:text-linen">{row.status.replace(/_/g, " ")}</span>
                    <span className="h-2 flex-1 bg-fog dark:bg-nox3" aria-hidden>
                      <span className="block h-full bg-bronze" style={{ width: `${(row.count / maxStatus) * 100}%` }} />
                    </span>
                    <span className="w-10 shrink-0 text-right text-smoke dark:text-linen-dim">{row.count}</span>
                  </li>
                ))}
              </ul>
            ) : (
              <EmptyState icon={BarChart3} title="Unavailable" body="Order status data could not be loaded." />
            )}
          </SectionCard>

          <SectionCard
            title="Top products"
            hint="Units and revenue from paid orders only"
            action={(
              <Select aria-label="Sort top products" value={productSort} onChange={(event) => setProductSort(event.target.value as "revenue" | "quantity")}>
                <option value="revenue">By revenue</option>
                <option value="quantity">By units</option>
              </Select>
            )}
          >
            {topProducts.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full min-w-[560px] text-left text-sm">
                  <thead>
                    <tr className="border-b border-line text-[10px] font-bold tracking-[0.14em] uppercase text-smoke dark:border-line-dark dark:text-linen-dim">
                      <th scope="col" className="py-2 pr-4">Product</th>
                      <th scope="col" className="py-2 pr-4 text-right">Units</th>
                      <th scope="col" className="py-2 pr-4 text-right">Revenue</th>
                      <th scope="col" className="py-2 text-right">Orders</th>
                    </tr>
                  </thead>
                  <tbody>
                    {topProducts.map((item) => (
                      <tr key={item.product_id} className="border-b border-line/60 last:border-0 dark:border-line-dark/60">
                        <td className="py-2.5 pr-4">
                          <Link to={`/product/${item.slug}`} className="font-semibold text-ink hover:text-bronze dark:text-linen">
                            {item.name}
                          </Link>
                          {!item.is_active && <span className="ml-2 text-[10px] font-bold uppercase text-red-700 dark:text-red-400">inactive</span>}
                        </td>
                        <td className="py-2.5 pr-4 text-right text-smoke dark:text-linen-dim">{item.units_sold}</td>
                        <td className="py-2.5 pr-4 text-right font-semibold text-ink dark:text-linen">{format(item.revenue)}</td>
                        <td className="py-2.5 text-right text-smoke dark:text-linen-dim">{item.orders_count}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <EmptyState icon={BarChart3} title="No product sales" body="No paid order items exist for the selected period." />
            )}
          </SectionCard>

          <div className="grid gap-6 lg:grid-cols-2">
            <SectionCard title="Categories" hint="Revenue from paid orders">
              {topCategories.length > 0 ? (
                <ul className="space-y-3">
                  {topCategories.map((category) => (
                    <li key={category.slug} className="flex items-center justify-between gap-3 text-sm">
                      <span className="font-semibold text-ink dark:text-linen">{category.label}</span>
                      <span className="text-smoke dark:text-linen-dim">
                        {category.units_sold} units · <strong className="text-ink dark:text-linen">{format(category.revenue)}</strong>
                      </span>
                    </li>
                  ))}
                </ul>
              ) : (
                <EmptyState icon={BarChart3} title="No category sales" body="No paid order items exist for the selected period." />
              )}
            </SectionCard>
            <SectionCard title="Inventory attention" hint="Out of stock or at/below threshold">
              {inventory && inventory.low_stock_list.length > 0 ? (
                <ul className="space-y-3">
                  {inventory.low_stock_list.map((item) => (
                    <li key={item.product_id} className="flex items-center justify-between gap-3 text-sm">
                      <Link to={`/product/${item.slug}`} className="font-semibold text-ink hover:text-bronze dark:text-linen">
                        {item.name}
                      </Link>
                      <span className="shrink-0 text-smoke dark:text-linen-dim">
                        {item.stock_quantity} left · {item.inventory_status.replace(/_/g, " ")}
                      </span>
                    </li>
                  ))}
                </ul>
              ) : (
                <EmptyState icon={BarChart3} title="Stock looks healthy" body="No active products need restocking right now." />
              )}
            </SectionCard>
          </div>

          <div className="grid gap-6 lg:grid-cols-2">
            <SectionCard title="Reviews" hint="Moderation across all products">
              {reviews ? (
                <dl className="grid grid-cols-2 gap-3 text-sm">
                  <div className="border border-line p-3 dark:border-line-dark"><dt className="text-[10px] font-bold uppercase tracking-[0.14em] text-smoke dark:text-linen-dim">Pending</dt><dd className="mt-1 font-display text-2xl text-ink dark:text-linen">{reviews.pending}</dd></div>
                  <div className="border border-line p-3 dark:border-line-dark"><dt className="text-[10px] font-bold uppercase tracking-[0.14em] text-smoke dark:text-linen-dim">Approved</dt><dd className="mt-1 font-display text-2xl text-ink dark:text-linen">{reviews.approved}</dd></div>
                  <div className="border border-line p-3 dark:border-line-dark"><dt className="text-[10px] font-bold uppercase tracking-[0.14em] text-smoke dark:text-linen-dim">Avg. approved rating</dt><dd className="mt-1 font-display text-2xl text-ink dark:text-linen">{reviews.average_approved_rating.toFixed(1)}</dd></div>
                  <div className="border border-line p-3 dark:border-line-dark"><dt className="text-[10px] font-bold uppercase tracking-[0.14em] text-smoke dark:text-linen-dim">Reviewed products</dt><dd className="mt-1 font-display text-2xl text-ink dark:text-linen">{reviews.reviewed_products}</dd></div>
                </dl>
              ) : (
                <EmptyState icon={BarChart3} title="Unavailable" body="Review metrics could not be loaded." />
              )}
            </SectionCard>
            <SectionCard title="Wishlist" hint="Saves across customer and guest lists">
              {wishlist ? (
                <div>
                  <p className="text-sm text-smoke dark:text-linen-dim">
                    <strong className="font-display text-2xl text-ink dark:text-linen">{wishlist.total_items}</strong> saves · {wishlist.wishlists_with_account} account lists · {wishlist.guest_wishlists} guest lists
                  </p>
                  {wishlist.top_products.length > 0 && (
                    <ul className="mt-4 space-y-2">
                      {wishlist.top_products.slice(0, 5).map((item) => (
                        <li key={item.product_id} className="flex items-center justify-between gap-3 text-sm">
                          <Link to={`/product/${item.slug}`} className="font-semibold text-ink hover:text-bronze dark:text-linen">
                            {item.name}
                          </Link>
                          <span className="shrink-0 text-smoke dark:text-linen-dim">{item.saves} saves</span>
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              ) : (
                <EmptyState icon={BarChart3} title="Unavailable" body="Wishlist metrics could not be loaded." />
              )}
            </SectionCard>
          </div>
        </div>
      )}
    </div>
  );
}
