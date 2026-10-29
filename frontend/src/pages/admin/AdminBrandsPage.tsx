import { useEffect, useMemo, useState } from "react";
import { Link, Navigate, useLocation } from "react-router-dom";
import { RefreshCcw, Save, Search, ShieldAlert, Trash2, X } from "lucide-react";
import { api } from "@/lib/api";
import type { PaginationMeta } from "@/store/catalogStore";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Badge, Button, EmptyState, Field, Input, Select, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";
import { cn } from "@/utils/cn";

interface Brand {
  id: number;
  slug: string;
  name: string;
  description: string | null;
  image: string | null;
  sort_order: number;
  is_active: boolean;
  product_count?: number;
}

interface BrandCollection {
  data: Brand[];
  meta?: PaginationMeta;
}

const STATUSES = ["draft", "published", "archived"];

const EMPTY_FORM = {
  slug: "",
  name: "",
  description: "",
  image: "",
  sort_order: "0",
  is_active: "true",
};

type FormState = typeof EMPTY_FORM;

export default function AdminBrandsPage() {
  usePageTitle("Brands");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const [brands, setBrands] = useState<Brand[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [formOpen, setFormOpen] = useState(false);
  const [editingSlug, setEditingSlug] = useState<string | null>(null);
  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [errors, setErrors] = useState<Record<string, string[]>>({});

  const searchParams = useMemo(() => {
    const params = new URLSearchParams({ status, per_page: "50" });
    if (query.trim()) params.set("q", query.trim());
    return params.toString();
  }, [query, status]);

  useEffect(() => {
    if (user?.role !== "admin") return;

    let cancelled = false;
    setLoading(true);
    api<BrandCollection>(`/admin/brands?${searchParams}`)
      .then((response) => {
        if (cancelled) return;
        setBrands(response.data);
        setMeta(response.meta);
      })
      .catch(() => {
        if (!cancelled) setBrands([]);
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
          body="Brand management is only available to store administrators."
          actionLabel="Back to account"
          actionTo="/account"
        />
      </div>
    );
  }

  const reload = async () => {
    const response = await api<BrandCollection>(`/admin/brands?${searchParams}`);
    setBrands(response.data);
    setMeta(response.meta);
  };

  const openCreate = () => {
    setEditingSlug(null);
    setForm(EMPTY_FORM);
    setErrors({});
    setFormOpen(true);
  };

  const openEdit = (record: Brand) => {
    setEditingSlug(record.slug);
    setForm({
      slug: record.slug,
      name: record.name,
      description: record.description ?? "",
      image: record.image ?? "",
      sort_order: String(record.sort_order),
      is_active: String(record.is_active),
    });
    setErrors({});
    setFormOpen(true);
  };

  const submit = async () => {
    setSaving(true);
    setErrors({});
    const payload: Record<string, unknown> = {
      name: form.name,
      description: form.description || null,
      image: form.image || null,
      sort_order: Number(form.sort_order) || 0,
      is_active: form.is_active === "true",
    };
    if (form.slug.trim() !== "") payload.slug = form.slug;
    try {
      if (editingSlug === null) {
        await api("/admin/brands", { method: "POST", body: JSON.stringify(payload) });
        pushToast("Brand created");
      } else {
        await api(`/admin/brands/${editingSlug}`, { method: "PUT", body: JSON.stringify(payload) });
        pushToast("Brand saved");
      }
      setFormOpen(false);
      await reload();
    } catch (requestError) {
      setErrors((requestError as any)?.errors ?? {});
      pushToast(requestError instanceof Error ? requestError.message : "Save failed");
    } finally {
      setSaving(false);
    }
  };

  const remove = async (slug: string) => {
    if (!window.confirm("Delete this brand?")) return;
    try {
      await api(`/admin/brands/${slug}`, { method: "DELETE" });
      pushToast("Brand deleted");
      await reload();
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Delete failed");
    }
  };

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Brands</h1>
        </div>
        <div className="flex gap-4">
          <Link to="/admin/products" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Inventory</Link>
          <Link to="/admin/orders" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Orders</Link>
          <Link to="/admin/content" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Content</Link>
          <Link to="/admin/analytics" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Analytics</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_180px_auto]">
        <Field label="Search brands" htmlFor="admin-brand-search">
          <div className="relative">
            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-smoke" aria-hidden />
            <Input id="admin-brand-search" value={query} onChange={(event) => setQuery(event.target.value)} className="pl-10" placeholder="Name, slug" />
          </div>
        </Field>
        <Field label="Status" htmlFor="admin-brand-status">
          <Select id="admin-brand-status" value={status} onChange={(event) => setStatus(event.target.value)}>
            <option value="all">All</option>
            <option value="true">Active</option>
            <option value="false">Inactive</option>
          </Select>
        </Field>
        <Button type="button" icon={RefreshCcw} className="self-end" onClick={() => setQuery((current) => current.trim())}>
          Refresh
        </Button>
      </div>

      <div className="mt-8">
        {loading ? (
          <div className="flex min-h-60 items-center justify-center">
            <Spinner />
          </div>
        ) : brands.length === 0 ? (
          <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
            <EmptyState icon={Trash2} title="No brands yet" body="Create your first brand to get started." />
          </div>
        ) : (
          <div className="space-y-4">
            <p className="text-xs text-smoke dark:text-linen-dim">
              Showing {meta?.total ?? brands.length} brands.
            </p>
            {brands.map((brand) => (
              <section key={brand.slug} className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
                <div className="flex flex-wrap items-start justify-between gap-4 border-b border-line p-5 dark:border-line-dark">
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <h2 className="font-display text-xl text-ink dark:text-linen">{brand.name}</h2>
                      <Badge tone="ink">{brand.slug}</Badge>
                      {brand.product_count !== undefined && <span className="ml-2 text-xs text-smoke dark:text-linen-dim">({brand.product_count} products)</span>}
                    </div>
                    <p className="mt-1 text-xs text-smoke dark:text-linen-dim">{brand.description ?? "No description"} · order {brand.sort_order}</p>
                  </div>
                  <span className={cn("px-2 py-0.5 text-[10px] font-bold tracking-[0.14em] uppercase", brand.is_active ? "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300" : "bg-bronze/15 text-bronze")}>
                    {brand.is_active ? "Active" : "Inactive"}
                  </span>
                  <div className="flex flex-wrap gap-2">
                    <Button type="button" size="sm" variant="outline" onClick={() => openEdit(brand)}>Edit</Button>
                    <Button type="button" size="sm" variant="ghost" icon={Trash2} onClick={() => void remove(brand.slug)}>Delete</Button>
                  </div>
                </div>
              </section>
            ))}
          </div>
        )}
      </div>

      {formOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-ink/50" onClick={() => setFormOpen(false)} aria-hidden />
          <div role="dialog" aria-modal="true" aria-label={editingSlug === null ? "Create brand" : "Edit brand"} className="relative w-full max-w-lg bg-cream p-6 shadow-2xl sm:p-8 dark:bg-nox2">
            <div className="mb-5 flex items-center justify-between">
              <h2 className="font-display text-2xl text-ink dark:text-linen">{editingSlug === null ? "New Brand" : "Edit Brand"}</h2>
              <button type="button" onClick={() => setFormOpen(false)} aria-label="Close dialog" className="flex h-10 w-10 items-center justify-center rounded-full hover:bg-fog dark:hover:bg-nox3">
                <X className="h-5 w-5 text-ink dark:text-linen" aria-hidden />
              </button>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Slug" htmlFor="brand-slug" required error={errors.slug?.[0]}>
                <Input id="brand-slug" value={form.slug} disabled={editingSlug !== null} onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))} placeholder="nike" />
              </Field>
              <Field label="Name" htmlFor="brand-name" required error={errors.name?.[0]}>
                <Input id="brand-name" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
              </Field>
              <Field label="Description" htmlFor="brand-description" className="sm:col-span-2" error={errors.description?.[0]}>
                <Input id="brand-description" value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} />
              </Field>
              <Field label="Image URL" htmlFor="brand-image" error={errors.image?.[0]}>
                <Input id="brand-image" value={form.image} onChange={(e) => setForm((f) => ({ ...f, image: e.target.value }))} placeholder="/imagery/brands/default.jpg" />
              </Field>
              <Field label="Sort order" htmlFor="brand-sort" error={errors.sort_order?.[0]}>
                <Input id="brand-sort" type="number" min={0} value={form.sort_order} onChange={(e) => setForm((f) => ({ ...f, sort_order: e.target.value }))} />
              </Field>
              <Field label="Status" htmlFor="brand-status" error={errors.is_active?.[0]}>
                <Select id="brand-status" value={form.is_active} onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.value }))}>
                  <option value="true">Active</option>
                  <option value="false">Inactive</option>
                </Select>
              </Field>
            </div>
            <div className="mt-6 flex gap-2">
              <Button type="button" icon={Save} loading={saving} onClick={() => void submit()}>
                {editingSlug === null ? "Create" : "Save Changes"}
              </Button>
              <Button type="button" variant="ghost" onClick={() => setFormOpen(false)}>Cancel</Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
