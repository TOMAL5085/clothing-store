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

interface Zone {
  id: number;
  name: string;
  code: string;
  description: string | null;
  shipping_cost: number;
  sort_order: number;
  is_active: boolean;
  city_count?: number;
}

interface ZoneCollection {
  data: Zone[];
  meta?: PaginationMeta;
}

const EMPTY_FORM = {
  name: "",
  code: "",
  description: "",
  shipping_cost: "0",
  sort_order: "0",
  is_active: "true",
};

type FormState = typeof EMPTY_FORM;

export default function AdminZonesPage() {
  usePageTitle("Zones");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const [zones, setZones] = useState<Zone[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [formOpen, setFormOpen] = useState(false);
  const [editingCode, setEditingCode] = useState<string | null>(null);
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
    api<ZoneCollection>(`/admin/zones?${searchParams}`)
      .then((response) => {
        if (cancelled) return;
        setZones(response.data);
        setMeta(response.meta);
      })
      .catch(() => {
        if (!cancelled) setZones([]);
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
          body="Zone management is only available to store administrators."
          actionLabel="Back to account"
          actionTo="/account"
        />
      </div>
    );
  }

  const reload = async () => {
    const response = await api<ZoneCollection>(`/admin/zones?${searchParams}`);
    setZones(response.data);
    setMeta(response.meta);
  };

  const openCreate = () => {
    setEditingCode(null);
    setForm(EMPTY_FORM);
    setErrors({});
    setFormOpen(true);
  };

  const openEdit = (record: Zone) => {
    setEditingCode(record.code);
    setForm({
      name: record.name,
      code: record.code,
      description: record.description ?? "",
      shipping_cost: String(record.shipping_cost),
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
      shipping_cost: Number(form.shipping_cost) || 0,
      sort_order: Number(form.sort_order) || 0,
      is_active: form.is_active === "true",
    };
    if (form.code.trim() !== "") payload.code = form.code;
    try {
      if (editingCode === null) {
        await api("/admin/zones", { method: "POST", body: JSON.stringify(payload) });
        pushToast("Zone created");
      } else {
        await api(`/admin/zones/${editingCode}`, { method: "PUT", body: JSON.stringify(payload) });
        pushToast("Zone saved");
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

  const remove = async (code: string) => {
    if (!window.confirm("Delete this zone?")) return;
    try {
      await api(`/admin/zones/${code}`, { method: "DELETE" });
      pushToast("Zone deleted");
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
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Shipping Zones</h1>
        </div>
        <div className="flex gap-4">
          <Link to="/admin/suppliers" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Suppliers</Link>
          <Link to="/admin/cities" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Cities</Link>
          <Link to="/admin/purchases" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Purchases</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_180px_auto]">
        <Field label="Search zones" htmlFor="admin-zone-search">
          <div className="relative">
            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-smoke" aria-hidden />
            <Input id="admin-zone-search" value={query} onChange={(event) => setQuery(event.target.value)} className="pl-10" placeholder="Name, code" />
          </div>
        </Field>
        <Field label="Status" htmlFor="admin-zone-status">
          <Select id="admin-zone-status" value={status} onChange={(event) => setStatus(event.target.value)}>
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
        ) : zones.length === 0 ? (
          <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
            <EmptyState icon={Trash2} title="No zones yet" body="Create your first shipping zone to get started." />
          </div>
        ) : (
          <div className="space-y-4">
            <p className="text-xs text-smoke dark:text-linen-dim">
              Showing {meta?.total ?? zones.length} zones.
            </p>
            {zones.map((zone) => (
              <section key={zone.code} className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
                <div className="flex flex-wrap items-start justify-between gap-4 border-b border-line p-5 dark:border-line-dark">
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <h2 className="font-display text-xl text-ink dark:text-linen">{zone.name}</h2>
                      <Badge tone="ink">{zone.code}</Badge>
                      {zone.city_count !== undefined && <span className="ml-2 text-xs text-smoke dark:text-linen-dim">({zone.city_count} cities)</span>}
                    </div>
                    <p className="mt-1 text-xs text-smoke dark:text-linen-dim">
                      {zone.description ?? "No description"} · ${zone.shipping_cost} shipping · order {zone.sort_order}
                    </p>
                  </div>
                  <span className={cn("px-2 py-0.5 text-[10px] font-bold tracking-[0.14em] uppercase", zone.is_active ? "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300" : "bg-bronze/15 text-bronze")}>
                    {zone.is_active ? "Active" : "Inactive"}
                  </span>
                  <div className="flex flex-wrap gap-2">
                    <Button type="button" size="sm" variant="outline" onClick={() => openEdit(zone)}>Edit</Button>
                    <Button type="button" size="sm" variant="ghost" icon={Trash2} onClick={() => void remove(zone.code)}>Delete</Button>
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
          <div role="dialog" aria-modal="true" aria-label={editingCode === null ? "Create zone" : "Edit zone"} className="relative w-full max-w-lg bg-cream p-6 shadow-2xl sm:p-8 dark:bg-nox2">
            <div className="mb-5 flex items-center justify-between">
              <h2 className="font-display text-2xl text-ink dark:text-linen">{editingCode === null ? "New Zone" : "Edit Zone"}</h2>
              <button type="button" onClick={() => setFormOpen(false)} aria-label="Close dialog" className="flex h-10 w-10 items-center justify-center rounded-full hover:bg-fog dark:hover:bg-nox3">
                <X className="h-5 w-5 text-ink dark:text-linen" aria-hidden />
              </button>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Name" htmlFor="zone-name" required error={errors.name?.[0]}>
                <Input id="zone-name" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
              </Field>
              <Field label="Code" htmlFor="zone-code" required error={errors.code?.[0]}>
                <Input id="zone-code" value={form.code} disabled={editingCode !== null} onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))} placeholder="ASIA" />
              </Field>
              <Field label="Description" htmlFor="zone-description" className="sm:col-span-2" error={errors.description?.[0]}>
                <Input id="zone-description" value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} />
              </Field>
              <Field label="Shipping Cost" htmlFor="zone-shipping" error={errors.shipping_cost?.[0]}>
                <Input id="zone-shipping" type="number" step="0.01" min={0} value={form.shipping_cost} onChange={(e) => setForm((f) => ({ ...f, shipping_cost: e.target.value }))} />
              </Field>
              <Field label="Sort order" htmlFor="zone-sort" error={errors.sort_order?.[0]}>
                <Input id="zone-sort" type="number" min={0} value={form.sort_order} onChange={(e) => setForm((f) => ({ ...f, sort_order: e.target.value }))} />
              </Field>
              <Field label="Status" htmlFor="zone-status" error={errors.is_active?.[0]}>
                <Select id="zone-status" value={form.is_active} onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.value }))}>
                  <option value="true">Active</option>
                  <option value="false">Inactive</option>
                </Select>
              </Field>
            </div>
            <div className="mt-6 flex gap-2">
              <Button type="button" icon={Save} loading={saving} onClick={() => void submit()}>
                {editingCode === null ? "Create" : "Save Changes"}
              </Button>
              <Button type="button" variant="ghost" onClick={() => setFormOpen(false)}>Cancel</Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
