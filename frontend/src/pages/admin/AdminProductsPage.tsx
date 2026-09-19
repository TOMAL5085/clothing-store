import { useEffect, useMemo, useState } from "react";
import { Link, Navigate, useLocation } from "react-router-dom";
import { Package, RefreshCcw, Save, Search, ShieldAlert } from "lucide-react";
import { api } from "@/lib/api";
import type { Product, ProductVariant } from "@/data/products";
import type { PaginationMeta } from "@/store/catalogStore";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Badge, Button, EmptyState, Field, Input, Select, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";
import { cn } from "@/utils/cn";

type InventoryMode = "set" | "increase" | "decrease";

interface ProductCollection {
  data: Product[];
  meta?: PaginationMeta;
}

interface InventoryDraft {
  mode: InventoryMode;
  quantity: string;
  lowStockThreshold: string;
}

interface InventoryResponse {
  data: Product;
}

function draftKey(product: Product, variant?: ProductVariant) {
  return `${product.slug}:${variant?.id ?? "product"}`;
}

function initialDraft(variant?: ProductVariant, product?: Product): InventoryDraft {
  return {
    mode: "set",
    quantity: String(variant?.stockQuantity ?? product?.stockQuantity ?? 0),
    lowStockThreshold: String(variant?.lowStockThreshold ?? product?.lowStockThreshold ?? 5),
  };
}

function statusTone(status?: Product["inventoryStatus"]) {
  if (status === "out_of_stock") return "bg-red-800 text-cream";
  if (status === "low_stock") return "bg-bronze/15 text-bronze";
  return "bg-ink text-paper dark:bg-linen dark:text-nox";
}

export default function AdminProductsPage() {
  usePageTitle("Product Inventory");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const [products, setProducts] = useState<Product[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [loading, setLoading] = useState(true);
  const [savingKey, setSavingKey] = useState<string | null>(null);
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [drafts, setDrafts] = useState<Record<string, InventoryDraft>>({});
  const [error, setError] = useState<string | null>(null);

  const searchParams = useMemo(() => {
    const params = new URLSearchParams({ status, per_page: "100" });
    if (query.trim()) params.set("q", query.trim());
    return params.toString();
  }, [query, status]);

  useEffect(() => {
    if (user?.role !== "admin") return;

    let cancelled = false;
    setLoading(true);
    setError(null);

    api<ProductCollection>(`/admin/products?${searchParams}`)
      .then((response) => {
        if (cancelled) return;
        setProducts(response.data);
        setMeta(response.meta);
        const nextDrafts: Record<string, InventoryDraft> = {};
        response.data.forEach((product) => {
          const variants = product.variants?.length ? product.variants : [undefined];
          variants.forEach((variant) => {
            nextDrafts[draftKey(product, variant)] = initialDraft(variant, product);
          });
        });
        setDrafts(nextDrafts);
      })
      .catch((requestError) => {
        if (cancelled) return;
        setError(requestError instanceof Error ? requestError.message : "Inventory could not be loaded.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [searchParams, user?.role]);

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  if (user.role !== "admin") {
    return (
      <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <EmptyState
          icon={ShieldAlert}
          title="Admin access required"
          body="Product inventory is only available to store administrators."
          actionLabel="Back to account"
          actionTo="/account"
        />
      </div>
    );
  }

  const updateDraft = (key: string, partial: Partial<InventoryDraft>) => {
    setDrafts((current) => ({ ...current, [key]: { ...current[key], ...partial } }));
  };

  const applyInventory = async (product: Product, variant?: ProductVariant) => {
    const key = draftKey(product, variant);
    const draft = drafts[key] ?? initialDraft(variant, product);
    setSavingKey(key);

    try {
      const response = await api<InventoryResponse>(`/admin/products/${product.slug}/inventory`, {
        method: "POST",
        body: JSON.stringify({
          variant_id: variant?.id,
          mode: draft.mode,
          quantity: Number(draft.quantity),
          low_stock_threshold: Number(draft.lowStockThreshold),
        }),
      });

      setProducts((current) => current.map((item) => (item.slug === response.data.slug ? response.data : item)));
      pushToast("Inventory updated");
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Inventory update failed");
    } finally {
      setSavingKey(null);
    }
  };

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">
            Product Inventory
          </h1>
        </div>
        <div className="flex gap-4">
          <Link
            to="/admin/orders"
            className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim"
          >
            Orders
          </Link>
          <Link
            to="/admin/customers"
            className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim"
          >
            Customers
          </Link>
          <Link
            to="/account"
            className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim"
          >
            Account
          </Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_180px_auto]">
        <Field label="Search products" htmlFor="admin-product-search">
          <div className="relative">
            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-smoke" aria-hidden />
            <Input
              id="admin-product-search"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              className="pl-10"
              placeholder="Name, SKU, brand"
            />
          </div>
        </Field>
        <Field label="Status" htmlFor="admin-product-status">
          <Select id="admin-product-status" value={status} onChange={(event) => setStatus(event.target.value)}>
            <option value="all">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </Select>
        </Field>
        <Button
          type="button"
          variant="outline"
          icon={RefreshCcw}
          className="self-end"
          onClick={() => setQuery((current) => current.trim())}
        >
          Refresh
        </Button>
      </div>

      <div className="mt-8">
        {loading && (
          <div className="flex min-h-60 items-center justify-center">
            <Spinner />
          </div>
        )}

        {!loading && error && (
          <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
            <EmptyState icon={Package} title="Inventory unavailable" body={error} />
          </div>
        )}

        {!loading && !error && products.length === 0 && (
          <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
            <EmptyState icon={Package} title="No products found" body="Try a different search or status filter." />
          </div>
        )}

        {!loading && !error && products.length > 0 && (
          <div className="space-y-4">
            <p className="text-xs text-smoke dark:text-linen-dim">
              Showing {meta?.total ?? products.length} products.
            </p>
            {products.map((product) => {
              const variants = product.variants?.length ? product.variants : [undefined];
              return (
                <section key={product.slug} className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
                  <div className="flex flex-wrap items-start justify-between gap-4 border-b border-line p-5 dark:border-line-dark">
                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <h2 className="font-display text-2xl text-ink dark:text-linen">{product.name}</h2>
                        {product.badge && <Badge tone={product.badge === "Sale" ? "sale" : "bronze"}>{product.badge}</Badge>}
                      </div>
                      <p className="mt-1 text-xs font-semibold tracking-[0.14em] text-smoke uppercase dark:text-linen-dim">
                        {product.sku ?? product.id} / {product.category}
                      </p>
                    </div>
                    <span className={cn("px-3 py-1 text-[10px] font-bold tracking-[0.14em] uppercase", statusTone(product.inventoryStatus))}>
                      {product.inventoryStatus?.replace(/_/g, " ") ?? "in stock"}
                    </span>
                  </div>

                  <div className="divide-y divide-line dark:divide-line-dark">
                    {variants.map((variant) => {
                      const key = draftKey(product, variant);
                      const draft = drafts[key] ?? initialDraft(variant, product);
                      return (
                        <div key={key} className="grid gap-3 p-5 lg:grid-cols-[minmax(180px,1fr)_150px_130px_150px_auto] lg:items-end">
                          <div>
                            <p className="text-sm font-semibold text-ink dark:text-linen">
                              {variant ? `${variant.size ?? "One Size"} / ${variant.color?.name ?? "Default"}` : "Product stock"}
                            </p>
                            <p className="mt-1 text-xs text-smoke dark:text-linen-dim">
                              {variant?.sku ?? product.sku ?? product.id}
                            </p>
                            <p className="mt-2 text-xs font-semibold text-bronze">
                              Current stock: {variant?.stockQuantity ?? product.stockQuantity ?? 0}
                            </p>
                          </div>
                          <Field label="Mode" htmlFor={`${key}-mode`}>
                            <Select
                              id={`${key}-mode`}
                              value={draft.mode}
                              onChange={(event) => updateDraft(key, { mode: event.target.value as InventoryMode })}
                            >
                              <option value="set">Set</option>
                              <option value="increase">Increase</option>
                              <option value="decrease">Decrease</option>
                            </Select>
                          </Field>
                          <Field label="Quantity" htmlFor={`${key}-quantity`}>
                            <Input
                              id={`${key}-quantity`}
                              type="number"
                              min="0"
                              value={draft.quantity}
                              onChange={(event) => updateDraft(key, { quantity: event.target.value })}
                            />
                          </Field>
                          <Field label="Low Stock" htmlFor={`${key}-threshold`}>
                            <Input
                              id={`${key}-threshold`}
                              type="number"
                              min="0"
                              value={draft.lowStockThreshold}
                              onChange={(event) => updateDraft(key, { lowStockThreshold: event.target.value })}
                            />
                          </Field>
                          <Button
                            type="button"
                            icon={Save}
                            loading={savingKey === key}
                            onClick={() => void applyInventory(product, variant)}
                          >
                            Apply
                          </Button>
                        </div>
                      );
                    })}
                  </div>
                </section>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
