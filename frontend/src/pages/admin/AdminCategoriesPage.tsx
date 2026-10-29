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

interface Category {
  id: number;
  slug: string;
  label: string;
  tagline: string | null;
  image: string | null;
  alt: string | null;
  sort_order: number;
  is_active: boolean;
  parent_id: number | null;
  parent?: { slug: string; label: string } | null;
}

interface CategoryCollection {
  data: Category[];
  meta?: PaginationMeta;
}

interface CategoryOption {
  id: number;
  label: string;
}

const STATUSES = ["draft", "published", "archived"];

const EMPTY_FORM = {
  slug: "",
  label: "",
  tagline: "",
  image: "",
  alt: "",
  parent_id: "",
  sort_order: "0",
  is_active: "true",
};

type FormState = typeof EMPTY_FORM;

export default function AdminCategoriesPage() {
  usePageTitle("Categories");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const [categories, setCategories] = useState<Category[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [level, setLevel] = useState("all"); // "all" | "top" | "sub"
  const [formOpen, setFormOpen] = useState(false);
  const [editingSlug, setEditingSlug] = useState<string | null>(null);
  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [parentOptions, setParentOptions] = useState<CategoryOption[]>([]);

  const searchParams = useMemo(() => {
    const params = new URLSearchParams({ status, per_page: "50" });
    if (query.trim()) params.set("q", query.trim());
    if (level === "top") params.set("parent_id", "null");
    else if (level === "sub") params.set("parent_id", "not_null");
    return params.toString();
  }, [query, status, level]);

  useEffect(() => {
    if (user?.role !== "admin") return;

    let cancelled = false;
    setLoading(true);
    api<CategoryCollection>(`/admin/categories?${searchParams}`)
      .then((response) => {
        if (cancelled) return;
        setCategories(response.data);
        setMeta(response.meta);
      })
      .catch(() => {
        if (!cancelled) setCategories([]);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [searchParams, user?.role]);

  useEffect(() => {
    if (user?.role !== "admin") return;
    api<CategoryCollection>("/admin/categories?status=all&per_page=100")
      .then((response) => {
        const options: CategoryOption[] = response.data.map((c) => ({ id: c.id, label: c.label }));
        setParentOptions(options);
      })
      .catch(() => setParentOptions([]));
  }, [user?.role]);

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  if (user.role !== "admin") {
    return (
      <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <EmptyState icon={ShieldAlert} title="Admin access required" body="Category management is only available to store administrators." actionLabel="Back to account" actionTo="/account" />
      </div>
    );
  }

  const reload = async () => {
    const response = await api<CategoryCollection>(`/admin/categories?${searchParams}`);
    setCategories(response.data);
    setMeta(response.meta);
  };

  const openCreate = () => {
    setEditingSlug(null);
    setForm(EMPTY_FORM);
    setErrors({});
    setFormOpen(true);
  };

  const openEdit = (record: Category) => {
    setEditingSlug(record.slug);
    setForm({
      slug: record.slug,
      label: record.label,
      tagline: record.tagline ?? "",
      image: record.image ?? "",
      alt: record.alt ?? "",
      parent_id: record.parent_id ?? "",
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
      label: form.label,
      tagline: form.tagline || null,
      image: form.image || null,
      alt: form.alt || null,
      sort_order: Number(form.sort_order) || 0,
      is_active: form.is_active === "true",
    };
    if (form.slug.trim() !== "") payload.slug = form.slug;
    if (form.parent_id.trim() !== "") payload.parent_id = Number(form.parent_id);
    else payload.parent_id = null;
    try {
      if (editingSlug === null) {
        await api("/admin/categories", { method: "POST", body: JSON.stringify(payload) });
        pushToast("Category created");
      } else {
        await api(`/admin/categories/${editingSlug}`, { method: "PUT", body: JSON.stringify(payload) });
        pushToast("Category saved");
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
    if (!window.confirm("Delete this category?")) return;
    try {
      await api(`/admin/categories/${slug}`, { method: "DELETE" });
      pushToast("Category deleted");
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
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Categories</h1>
        </div>
        <div className="flex gap-4">
          <Link to="/admin/products" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Inventory</Link>
          <Link to="/admin/orders" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Orders</Link>
          <Link to="/admin/content" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Content</Link>
          <Link to="/admin/brands" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Brands</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_180px_auto_auto]">
        <Field label="Search categories" htmlFor="admin-category-search">
          <div className="relative">
            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-smoke" aria-hidden />
            <Input id="admin-category-search" value={query} onChange={(event) => setQuery(event.target.value)} className="pl-10" placeholder="Label, slug" />
          </div>
        </Field>
        <Field label="Level" htmlFor="admin-category-level">
          <Select id="admin-category-level" value={level} onChange={(event) => setLevel(event.target.value)}>
            <option value="all">All levels</option>
            <option value="top">Top-level only</option>
            <option value="sub">Sub-categories only</option>
          </Select>
        </Field>
        <Field label="Status" htmlFor="admin-category-status">
          <Select id="admin-category-status" value={status} onChange={(event) => setStatus(event.target.value)}>
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
        ) : categories.length === 0 ? (
          <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
            <EmptyState icon={Trash2} title="No categories yet" body="Create your first category to get started." />
          </div>
        ) : (
          <div className="space-y-4">
            <p className="text-xs text-smoke dark:text-linen-dim">
              Showing {meta?.total ?? categories.length} categories.
            </p>
            {categories.map((category) => (
              <section key={category.slug} className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
                <div className="flex flex-wrap items-start justify-between gap-4 border-b border-line p-5 dark:border-line-dark">
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <h2 className="font-display text-xl text-ink dark:text-linen">{category.label}</h2>
                      <Badge tone="ink">{category.slug}</Badge>
                      {category.parent && (
                        <span className="flex items-center gap-1 text-xs font-semibold text-smoke uppercase dark:text-linen-dim">
                          › {category.parent.label}
                        </span>
                      )}
                    </div>
                    <p className="mt-1 text-xs text-smoke dark:text-linen-dim">{category.tagline ?? "No tagline"} · order {category.sort_order} · {category.parent_id ? "Sub-category" : "Top-level"}</p>
                  </div>
                  <span className={cn("px-2 py-0.5 text-[10px] font-bold tracking-[0.14em] uppercase", category.is_active ? "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300" : "bg-bronze/15 text-bronze")}>
                    {category.is_active ? "Active" : "Inactive"}
                  </span>
                  <div className="flex flex-wrap gap-2">
                    <Button type="button" size="sm" variant="outline" onClick={() => openEdit(category)}>Edit</Button>
                    <Button type="button" size="sm" variant="ghost" icon={Trash2} onClick={() => void remove(category.slug)}>Delete</Button>
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
          <div role="dialog" aria-modal="true" aria-label={editingSlug === null ? "Create category" : "Edit category"} className="relative w-full max-w-lg bg-cream p-6 shadow-2xl sm:p-8 dark:bg-nox2">
            <div className="mb-5 flex items-center justify-between">
              <h2 className="font-display text-2xl text-ink dark:text-linen">{editingSlug === null ? "New Category" : "Edit Category"}</h2>
              <button type="button" onClick={() => setFormOpen(false)} aria-label="Close dialog" className="flex h-10 w-10 items-center justify-center rounded-full hover:bg-fog dark:hover:bg-nox3">
                <X className="h-5 w-5 text-ink dark:text-linen" aria-hidden />
              </button>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Slug" htmlFor="category-slug" required error={errors.slug?.[0]}>
                <Input id="category-slug" value={form.slug} disabled={editingSlug !== null} onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))} placeholder="men" />
              </Field>
              <Field label="Label" htmlFor="category-label" required error={errors.label?.[0]}>
                <Input id="category-label" value={form.label} onChange={(e) => setForm((f) => ({ ...f, label: e.target.value }))} />
              </Field>
              <Field label="Tagline" htmlFor="category-tagline" className="sm:col-span-2" error={errors.tagline?.[0]}>
                <Input id="category-tagline" value={form.tagline} onChange={(e) => setForm((f) => ({ ...f, tagline: e.target.value }))} />
              </Field>
              <Field label="Image URL" htmlFor="category-image" error={errors.image?.[0]}>
                <Input id="category-image" value={form.image} onChange={(e) => setForm((f) => ({ ...f, image: e.target.value }))} placeholder="/imagery/collection-women.jpg" />
              </Field>
              <Field label="Alt text" htmlFor="category-alt" error={errors.alt?.[0]}>
                <Input id="category-alt" value={form.alt} onChange={(e) => setForm((f) => ({ ...f, alt: e.target.value }))} />
              </Field>
              <Field label="Parent category" htmlFor="category-parent" error={errors.parent_id?.[0]}>
                <Select id="category-parent" value={form.parent_id} onChange={(e) => setForm((f) => ({ ...f, parent_id: e.target.value }))}>
                  <option value="">None (top-level)</option>
                  {parentOptions.map((option) => (
                    <option key={option.id} value={String(option.id)}>
                      {option.label}
                    </option>
                  ))}
                </Select>
              </Field>
              <Field label="Sort order" htmlFor="category-sort" error={errors.sort_order?.[0]}>
                <Input id="category-sort" type="number" min={0} value={form.sort_order} onChange={(e) => setForm((f) => ({ ...f, sort_order: e.target.value }))} />
              </Field>
              <Field label="Status" htmlFor="category-status" error={errors.is_active?.[0]}>
                <Select id="category-status" value={form.is_active} onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.value }))}>
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
