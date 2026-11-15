import { useEffect, useMemo, useState, type FormEvent } from "react";
import { Link, Navigate, useLocation } from "react-router-dom";
import { Package, Plus, RefreshCcw, Save, Search, ShieldAlert, X } from "lucide-react";
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

interface PricingDraft {
  price: string;
  currency: string;
}

interface InventoryResponse {
  data: Product;
}

interface NewProductForm {
  name: string;
  slug: string;
  category: string;
  subcategory: string;
  sku: string;
  price: string;
  currency: string;
  stock_quantity: string;
  brand: string;
  weight: string;
  weight_unit: string;
  badge: string;
  description: string;
  image_files: File[];
  sizes: string[];
  custom_size: string;
  colors: Array<{ name: string; hex: string }>;
  custom_color_name: string;
  custom_color_hex: string;
}

const emptyProductForm: NewProductForm = {
  name: "",
  slug: "",
  category: "women",
  subcategory: "",
  sku: "",
  price: "",
  currency: "USD",
  stock_quantity: "0",
  brand: "JAAJ",
  weight: "",
  weight_unit: "kg",
  badge: "",
  description: "",
  image_files: [],
  sizes: [],
  custom_size: "",
  colors: [],
  custom_color_name: "",
  custom_color_hex: "#1c1a17",
};

const DEFAULT_SIZES = ["XS", "S", "M", "L", "XL", "XXL"];
const DEFAULT_COLORS = [
  { name: "Bone", hex: "#e6dfd0" }, { name: "Oat", hex: "#c8ae87" },
  { name: "Khaki", hex: "#97906b" }, { name: "Mocha", hex: "#6d543c" },
  { name: "Charcoal", hex: "#3b3833" }, { name: "Ink", hex: "#1c1a17" },
];

function slugify(value: string) {
  return value.toLowerCase().trim().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
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
  const [pricingDrafts, setPricingDrafts] = useState<Record<string, PricingDraft>>({});
  const [savingPrice, setSavingPrice] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [showCreate, setShowCreate] = useState(false);
  const [creating, setCreating] = useState(false);
  const [newProduct, setNewProduct] = useState<NewProductForm>(emptyProductForm);

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
        setPricingDrafts(Object.fromEntries(response.data.map((product) => [product.slug, { price: String(product.price), currency: product.currency ?? "USD" }])));
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

  const savePricing = async (product: Product) => {
    const draft = pricingDrafts[product.slug];
    if (!draft) return;
    setSavingPrice(product.slug);
    try {
      const response = await api<{ data: Product }>(`/admin/products/${product.slug}`, { method: "PUT", body: JSON.stringify({ price: Number(draft.price), currency: draft.currency }) });
      setProducts((current) => current.map((item) => item.slug === product.slug ? response.data : item));
      pushToast("Product price updated");
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Price update failed");
    } finally {
      setSavingPrice(null);
    }
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

  const createProduct = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setCreating(true);
    try {
      const images = await Promise.all(newProduct.image_files.map(async (file, index) => {
        const imageForm = new FormData();
        imageForm.append("image", file);
        const uploaded = await api<{ data: { url: string } }>("/admin/products/image", { method: "POST", body: imageForm });
        return { url: uploaded.data.url, alt: newProduct.name.trim(), sort_order: index + 1 };
      }));
      const sizes = [...newProduct.sizes, newProduct.custom_size.trim()].filter(Boolean);
      const variantSizes = sizes.length ? sizes : newProduct.colors.length ? ["One Size"] : [];
      const variantColors = newProduct.colors.length ? newProduct.colors : [null];
      const variants = variantSizes.flatMap((size) => variantColors.map((color, index) => ({
        size,
        color: color?.name,
        color_hex: color?.hex,
        sku: `${newProduct.sku.trim()}-${size}-${color?.name ?? index + 1}`.replace(/\s+/g, "-").toUpperCase(),
        price: Number(newProduct.price),
        stock_quantity: Number(newProduct.stock_quantity),
      })));
      const payload = {
        name: newProduct.name.trim(),
        slug: newProduct.slug.trim() || slugify(newProduct.name),
        category: newProduct.category,
        subcategory: newProduct.subcategory.trim() || undefined,
        sku: newProduct.sku.trim(),
        price: Number(newProduct.price),
        currency: newProduct.currency,
        stock_quantity: Number(newProduct.stock_quantity),
        brand: newProduct.brand.trim() || "JAAJ",
        weight: newProduct.weight ? Number(newProduct.weight) : undefined,
        weight_unit: newProduct.weight_unit,
        badge: newProduct.badge || undefined,
        description: newProduct.description.trim(),
        short_description: newProduct.description.trim().slice(0, 500),
        images,
        variants,
      };
      const response = await api<{ data: Product }>("/admin/products", { method: "POST", body: JSON.stringify(payload) });
      setProducts((current) => [response.data, ...current]);
      setMeta((current) => current ? { ...current, total: (current.total ?? products.length) + 1 } : current);
      setNewProduct(emptyProductForm);
      setShowCreate(false);
      pushToast("Product created");
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Product could not be created");
    } finally {
      setCreating(false);
    }
  };

  const toggleSize = (size: string) => setNewProduct((current) => ({
    ...current,
    sizes: current.sizes.includes(size) ? current.sizes.filter((item) => item !== size) : [...current.sizes, size],
  }));

  const toggleColor = (color: { name: string; hex: string }) => setNewProduct((current) => ({
    ...current,
    colors: current.colors.some((item) => item.name === color.name) ? current.colors.filter((item) => item.name !== color.name) : [...current.colors, color],
  }));

  const addCustomColor = () => setNewProduct((current) => {
    const name = current.custom_color_name.trim();
    if (!name || current.colors.some((color) => color.name.toLowerCase() === name.toLowerCase())) return current;
    return { ...current, colors: [...current.colors, { name, hex: current.custom_color_hex }], custom_color_name: "" };
  });

  const moveImage = (index: number, direction: -1 | 1) => setNewProduct((current) => {
    const next = [...current.image_files];
    const target = index + direction;
    if (target < 0 || target >= next.length) return current;
    [next[index], next[target]] = [next[target], next[index]];
    return { ...current, image_files: next };
  });

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">
            Product Inventory
          </h1>
        </div>
        <div className="flex flex-wrap items-center gap-4">
          <Button type="button" icon={Plus} onClick={() => setShowCreate((current) => !current)}>
            {showCreate ? "Close" : "Add Product"}
          </Button>
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
            to="/admin/reviews"
            className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim"
          >
            Reviews
          </Link>
          <Link
            to="/admin/analytics"
            className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim"
          >
            Analytics
          </Link>
          <Link
            to="/admin/audit-log"
            className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim"
          >
            Audit Log
          </Link>
          <Link
            to="/admin/content"
            className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim"
          >
            Content
          </Link>
          <Link
            to="/account"
            className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim"
          >
            Account
          </Link>
        </div>
      </div>

      {showCreate && (
        <form onSubmit={createProduct} className="mt-8 border border-line bg-cream p-5 dark:border-line-dark dark:bg-nox2">
          <div className="flex items-center justify-between gap-4">
            <div>
              <p className="text-[11px] font-bold tracking-[0.2em] uppercase text-bronze">New catalog item</p>
              <h2 className="mt-1 font-display text-2xl text-ink dark:text-linen">Add Product</h2>
            </div>
            <button type="button" aria-label="Close add product form" className="text-smoke hover:text-ink dark:hover:text-linen" onClick={() => setShowCreate(false)}><X size={18} /></button>
          </div>
          <div className="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Field label="Product name" htmlFor="new-product-name"><Input id="new-product-name" required value={newProduct.name} onChange={(event) => setNewProduct((current) => ({ ...current, name: event.target.value, slug: current.slug || slugify(event.target.value) }))} /></Field>
            <Field label="Slug" htmlFor="new-product-slug"><Input id="new-product-slug" required value={newProduct.slug} onChange={(event) => setNewProduct((current) => ({ ...current, slug: event.target.value }))} /></Field>
            <Field label="SKU" htmlFor="new-product-sku"><Input id="new-product-sku" required value={newProduct.sku} onChange={(event) => setNewProduct((current) => ({ ...current, sku: event.target.value }))} /></Field>
            <Field label="Category" htmlFor="new-product-category"><Select id="new-product-category" value={newProduct.category} onChange={(event) => setNewProduct((current) => ({ ...current, category: event.target.value }))}><option value="women">Women</option><option value="men">Men</option><option value="kids">Kids</option><option value="decor">Decor</option><option value="accessories">Accessories</option></Select></Field>
            <Field label="Subcategory" htmlFor="new-product-subcategory" hint="Optional. Type a new name to create it under this category."><Input id="new-product-subcategory" value={newProduct.subcategory} onChange={(event) => setNewProduct((current) => ({ ...current, subcategory: event.target.value }))} placeholder="Shoes, shirts, trousers…" /></Field>
            <Field label="Price" htmlFor="new-product-price"><Input id="new-product-price" required type="number" min="0" step="0.01" value={newProduct.price} onChange={(event) => setNewProduct((current) => ({ ...current, price: event.target.value }))} /></Field>
            <Field label="Price currency" htmlFor="new-product-currency"><Select id="new-product-currency" value={newProduct.currency} onChange={(event) => setNewProduct((current) => ({ ...current, currency: event.target.value }))}><option value="USD">USD — US Dollar</option><option value="BDT">BDT — Bangladeshi Taka</option><option value="GBP">GBP — British Pound</option></Select></Field>
            <Field label="Stock quantity" htmlFor="new-product-stock"><Input id="new-product-stock" required type="number" min="0" value={newProduct.stock_quantity} onChange={(event) => setNewProduct((current) => ({ ...current, stock_quantity: event.target.value }))} /></Field>
            <Field label="Brand" htmlFor="new-product-brand"><Input id="new-product-brand" value={newProduct.brand} onChange={(event) => setNewProduct((current) => ({ ...current, brand: event.target.value }))} /></Field>
            <Field label="Weight" htmlFor="new-product-weight"><Input id="new-product-weight" type="number" min="0" step="0.001" value={newProduct.weight} onChange={(event) => setNewProduct((current) => ({ ...current, weight: event.target.value }))} /></Field>
            <Field label="Weight unit" htmlFor="new-product-weight-unit"><Select id="new-product-weight-unit" value={newProduct.weight_unit} onChange={(event) => setNewProduct((current) => ({ ...current, weight_unit: event.target.value }))}><option value="g">g</option><option value="kg">kg</option><option value="lb">lb</option><option value="oz">oz</option></Select></Field>
            <Field label="Badge" htmlFor="new-product-badge"><Select id="new-product-badge" value={newProduct.badge} onChange={(event) => setNewProduct((current) => ({ ...current, badge: event.target.value }))}><option value="">None</option><option value="New">New</option><option value="Bestseller">Bestseller</option><option value="Sale">Sale</option></Select></Field>
            <Field label="Product images" htmlFor="new-product-image" hint="Upload one or more JPG, PNG, or WEBP files."><Input id="new-product-image" type="file" multiple accept="image/jpeg,image/png,image/webp" onChange={(event) => setNewProduct((current) => ({ ...current, image_files: Array.from(event.target.files ?? []) }))} /></Field>
          </div>
          {newProduct.image_files.length > 0 && <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">{newProduct.image_files.map((file, index) => <div key={`${file.name}-${index}`} className="relative border border-line p-2 dark:border-line-dark"><img src={URL.createObjectURL(file)} alt={`Preview ${index + 1}`} className="aspect-square w-full object-cover" /><div className="mt-2 flex justify-between gap-1 text-xs"><button type="button" disabled={index === 0} onClick={() => moveImage(index, -1)}>← Move</button><button type="button" onClick={() => setNewProduct((current) => ({ ...current, image_files: current.image_files.filter((_, itemIndex) => itemIndex !== index) }))}>Remove</button><button type="button" disabled={index === newProduct.image_files.length - 1} onClick={() => moveImage(index, 1)}>Move →</button></div></div>)}</div>}
          <div className="mt-5 grid gap-5 border-y border-line py-5 dark:border-line-dark lg:grid-cols-2">
            <fieldset><legend className="text-xs font-bold tracking-[0.14em] uppercase">Optional sizes</legend><div className="mt-3 flex flex-wrap gap-2">{DEFAULT_SIZES.map((size) => <button key={size} type="button" onClick={() => toggleSize(size)} className={cn("border px-3 py-2 text-xs font-semibold", newProduct.sizes.includes(size) ? "border-ink bg-ink text-paper dark:border-linen dark:bg-linen dark:text-nox" : "border-line dark:border-line-dark")}>{size}</button>)}</div><Input className="mt-3" value={newProduct.custom_size} onChange={(event) => setNewProduct((current) => ({ ...current, custom_size: event.target.value }))} placeholder="Custom size, e.g. 32W" /></fieldset>
            <fieldset><legend className="text-xs font-bold tracking-[0.14em] uppercase">Optional colours</legend><div className="mt-3 flex flex-wrap gap-2">{DEFAULT_COLORS.map((color) => <button key={color.name} type="button" onClick={() => toggleColor(color)} className={cn("flex items-center gap-2 border px-2 py-2 text-xs font-semibold", newProduct.colors.some((item) => item.name === color.name) ? "border-ink dark:border-linen" : "border-line dark:border-line-dark")}><span className="h-4 w-4 rounded-full border" style={{ backgroundColor: color.hex }} />{color.name}</button>)}</div><div className="mt-3 flex gap-2"><Input type="color" aria-label="Custom colour" value={newProduct.custom_color_hex} onChange={(event) => setNewProduct((current) => ({ ...current, custom_color_hex: event.target.value }))} className="h-10 w-12 p-1" /><Input value={newProduct.custom_color_name} onChange={(event) => setNewProduct((current) => ({ ...current, custom_color_name: event.target.value }))} placeholder="Custom colour name" /><Button type="button" size="sm" variant="outline" onClick={addCustomColor}>Add</Button></div></fieldset>
          </div>
          <Field label="Description" htmlFor="new-product-description" className="mt-4"><textarea id="new-product-description" required rows={4} value={newProduct.description} onChange={(event) => setNewProduct((current) => ({ ...current, description: event.target.value }))} className="w-full border border-line bg-paper px-3 py-2 text-sm text-ink outline-none focus:border-bronze dark:border-line-dark dark:bg-nox dark:text-linen" /></Field>
          <div className="mt-5 flex justify-end"><Button type="submit" icon={Plus} loading={creating}>Create Product</Button></div>
        </form>
      )}

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
              const pricing = pricingDrafts[product.slug] ?? { price: String(product.price), currency: product.currency ?? "USD" };
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
                  <div className="grid gap-3 border-b border-line p-5 sm:grid-cols-[1fr_180px_auto] sm:items-end dark:border-line-dark">
                    <Field label="Price" htmlFor={`${product.slug}-price`}><Input id={`${product.slug}-price`} type="number" min="0" step="0.01" value={pricing.price} onChange={(event) => setPricingDrafts((current) => ({ ...current, [product.slug]: { ...pricing, price: event.target.value } }))} /></Field>
                    <Field label="Currency" htmlFor={`${product.slug}-currency`}><Select id={`${product.slug}-currency`} value={pricing.currency} onChange={(event) => setPricingDrafts((current) => ({ ...current, [product.slug]: { ...pricing, currency: event.target.value } }))}><option value="USD">USD</option><option value="BDT">BDT</option><option value="GBP">GBP</option></Select></Field>
                    <Button type="button" variant="outline" size="sm" loading={savingPrice === product.slug} onClick={() => void savePricing(product)}>Save price</Button>
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
