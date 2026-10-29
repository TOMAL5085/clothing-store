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

interface Supplier {
  id: number;
  slug: string;
  name: string;
  contact_person: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  city_id: number | null;
  city: { id: number; name: string } | null;
  zone_id: number | null;
  zone: { id: number; name: string } | null;
  status: string;
  sort_order: number;
  purchase_count?: number;
}

interface SupplierCollection {
  data: Supplier[];
  meta?: PaginationMeta;
}

const STATUSES = ["active", "inactive", "suspended"];

const EMPTY_FORM = {
  name: "",
  slug: "",
  contact_person: "",
  email: "",
  phone: "",
  tax_id: "",
  address: "",
  city_id: "",
  zone_id: "",
  status: "active",
  sort_order: "0",
};

type FormState = typeof EMPTY_FORM;

export default function AdminSuppliersPage() {
  usePageTitle("Suppliers");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
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
    api<SupplierCollection>(`/admin/suppliers?${searchParams}`)
      .then((response) => {
        if (cancelled) return;
        setSuppliers(response.data);
        setMeta(response.meta);
      })
      .catch(() => {
        if (!cancelled) setSuppliers([]);
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
          body="Supplier management is only available to store administrators."
          actionLabel="Back to account"
          actionTo="/account"
        />
      </div>
    );
  }

  const reload = async () => {
    const response = await api<SupplierCollection>(`/admin/suppliers?${searchParams}`);
    setSuppliers(response.data);
    setMeta(response.meta);
  };

  const openCreate = () => {
    setEditingSlug(null);
    setForm(EMPTY_FORM);
    setErrors({});
    setFormOpen(true);
  };

  const openEdit = (record: Supplier) => {
    setEditingSlug(record.slug);
    setForm({
      name: record.name,
      slug: record.slug,
      contact_person: record.contact_person ?? "",
      email: record.email ?? "",
      phone: record.phone ?? "",
      tax_id: "",
      address: record.address ?? "",
      city_id: String(record.city_id ?? ""),
      zone_id: String(record.zone_id ?? ""),
      status: record.status,
      sort_order: String(record.sort_order),
    });
    setErrors({});
    setFormOpen(true);
  };

  const submit = async () => {
    setSaving(true);
    setErrors({});
    const payload: Record<string, unknown> = {
      name: form.name,
      slug: form.slug,
      contact_person: form.contact_person || null,
      email: form.email || null,
      phone: form.phone || null,
      tax_id: form.tax_id || null,
      address: form.address || null,
      city_id: form.city_id ? Number(form.city_id) : null,
      zone_id: form.zone_id ? Number(form.zone_id) : null,
      status: form.status,
      sort_order: Number(form.sort_order) || 0,
    };
    try {
      if (editingSlug === null) {
        await api("/admin/suppliers", { method: "POST", body: JSON.stringify(payload) });
        pushToast("Supplier created");
      } else {
        await api(`/admin/suppliers/${editingSlug}`, { method: "PUT", body: JSON.stringify(payload) });
        pushToast("Supplier saved");
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
    if (!window.confirm("Delete this supplier?")) return;
    try {
      await api(`/admin/suppliers/${slug}`, { method: "DELETE" });
      pushToast("Supplier deleted");
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
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Suppliers</h1>
        </div>
        <div className="flex gap-4">
          <Link to="/admin/zones" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Zones</Link>
          <Link to="/admin/cities" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Cities</Link>
          <Link to="/admin/purchases" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Purchases</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_180px_auto]">
        <Field label="Search suppliers" htmlFor="admin-supplier-search">
          <div className="relative">
            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-smoke" aria-hidden />
            <Input id="admin-supplier-search" value={query} onChange={(event) => setQuery(event.target.value)} className="pl-10" placeholder="Name, contact, slug" />
          </div>
        </Field>
        <Field label="Status" htmlFor="admin-supplier-status">
          <Select id="admin-supplier-status" value={status} onChange={(event) => setStatus(event.target.value)}>
            <option value="all">All</option>
            {STATUSES.map((s) => (
              <option key={s} value={s} className="capitalize">
                {s}
              </option>
            ))}
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
        ) : suppliers.length === 0 ? (
          <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
            <EmptyState icon={Trash2} title="No suppliers yet" body="Create your first supplier to get started." />
          </div>
        ) : (
          <div className="space-y-4">
            <p className="text-xs text-smoke dark:text-linen-dim">
              Showing {meta?.total ?? suppliers.length} suppliers.
            </p>
            {suppliers.map((supplier) => (
              <section key={supplier.slug} className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
                <div className="flex flex-wrap items-start justify-between gap-4 border-b border-line p-5 dark:border-line-dark">
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <h2 className="font-display text-xl text-ink dark:text-linen">{supplier.name}</h2>
                      <Badge tone="ink">{supplier.slug}</Badge>
                      {supplier.purchase_count !== undefined && <span className="ml-2 text-xs text-smoke dark:text-linen-dim">({supplier.purchase_count} POs)</span>}
                    </div>
                    <p className="mt-1 text-xs text-smoke dark:text-linen-dim">
                      {supplier.contact_person ?? "No contact"} ·
                      {supplier.email ?? "No email"} ·
                      {(supplier.city?.name ?? supplier.zone?.name) ?? ""}
                    </p>
                  </div>
                  <span className={cn("px-2 py-0.5 text-[10px] font-bold tracking-[0.14em] uppercase", supplier.status === "active" ? "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300" : "bg-bronze/15 text-bronze")}>
                    {supplier.status}
                  </span>
                  <div className="flex flex-wrap gap-2">
                    <Button type="button" size="sm" variant="outline" onClick={() => openEdit(supplier)}>Edit</Button>
                    <Button type="button" size="sm" variant="ghost" icon={Trash2} onClick={() => void remove(supplier.slug)}>Delete</Button>
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
          <div role="dialog" aria-modal="true" aria-label={editingSlug === null ? "Create supplier" : "Edit supplier"} className="relative w-full max-w-lg bg-cream p-6 shadow-2xl sm:p-8 dark:bg-nox2">
            <div className="mb-5 flex items-center justify-between">
              <h2 className="font-display text-2xl text-ink dark:text-linen">{editingSlug === null ? "New Supplier" : "Edit Supplier"}</h2>
              <button type="button" onClick={() => setFormOpen(false)} aria-label="Close dialog" className="flex h-10 w-10 items-center justify-center rounded-full hover:bg-fog dark:hover:bg-nox3">
                <X className="h-5 w-5 text-ink dark:text-linen" aria-hidden />
              </button>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Name" htmlFor="supplier-name" required error={errors.name?.[0]}>
                <Input id="supplier-name" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
              </Field>
              <Field label="Slug" htmlFor="supplier-slug" required error={errors.slug?.[0]}>
                <Input id="supplier-slug" value={form.slug} disabled={editingSlug !== null} onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))} placeholder="acme-corp" />
              </Field>
              <Field label="Contact Person" htmlFor="supplier-contact" error={errors.contact_person?.[0]}>
                <Input id="supplier-contact" value={form.contact_person} onChange={(e) => setForm((f) => ({ ...f, contact_person: e.target.value }))} />
              </Field>
              <Field label="Email" htmlFor="supplier-email" type="email" error={errors.email?.[0]}>
                <Input id="supplier-email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} />
              </Field>
              <Field label="Phone" htmlFor="supplier-phone" error={errors.phone?.[0]}>
                <Input id="supplier-phone" value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))} />
              </Field>
              <Field label="Tax ID" htmlFor="supplier-tax" error={errors.tax_id?.[0]}>
                <Input id="supplier-tax" value={form.tax_id} onChange={(e) => setForm((f) => ({ ...f, tax_id: e.target.value }))} />
              </Field>
              <Field label="Address" htmlFor="supplier-address" className="sm:col-span-2" error={errors.address?.[0]}>
                <Input id="supplier-address" value={form.address} onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))} />
              </Field>
              <Field label="City ID" htmlFor="supplier-city" error={errors.city_id?.[0]}>
                <Input id="supplier-city" type="number" min={0} value={form.city_id} onChange={(e) => setForm((f) => ({ ...f, city_id: e.target.value }))} />
              </Field>
              <Field label="Zone ID" htmlFor="supplier-zone" error={errors.zone_id?.[0]}>
                <Input id="supplier-zone" type="number" min={0} value={form.zone_id} onChange={(e) => setForm((f) => ({ ...f, zone_id: e.target.value }))} />
              </Field>
              <Field label="Status" htmlFor="supplier-status" error={errors.status?.[0]}>
                <Select id="supplier-status" value={form.status} onChange={(e) => setForm((f) => ({ ...f, status: e.target.value }))}>
                  {STATUSES.map((s) => (
                    <option key={s} value={s} className="capitalize">
                      {s}
                    </option>
                  ))}
                </Select>
              </Field>
              <Field label="Sort order" htmlFor="supplier-sort" error={errors.sort_order?.[0]}>
                <Input id="supplier-sort" type="number" min={0} value={form.sort_order} onChange={(e) => setForm((f) => ({ ...f, sort_order: e.target.value }))} />
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
