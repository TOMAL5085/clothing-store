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

interface City {
  id: number;
  zone_id: number;
  zone: { id: number; name: string; code: string } | null;
  name: string;
  code: string | null;
  sort_order: number;
  is_active: boolean;
}

interface CityCollection {
  data: City[];
  meta?: PaginationMeta;
}

interface ZoneOption {
  id: number;
  name: string;
}

const EMPTY_FORM = {
  zone_id: "",
  name: "",
  code: "",
  sort_order: "0",
  is_active: "true",
};

type FormState = typeof EMPTY_FORM;

export default function AdminCitiesPage() {
  usePageTitle("Cities");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const [cities, setCities] = useState<City[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [zoneFilter, setZoneFilter] = useState("");
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [zoneOptions, setZoneOptions] = useState<ZoneOption[]>([]);

  const searchParams = useMemo(() => {
    const params = new URLSearchParams({ status, per_page: "50" });
    if (query.trim()) params.set("q", query.trim());
    if (zoneFilter !== "") params.set("zone_id", zoneFilter);
    return params.toString();
  }, [query, status, zoneFilter]);

  useEffect(() => {
    if (user?.role !== "admin") return;

    let cancelled = false;
    setLoading(true);
    api<CityCollection>(`/admin/cities?${searchParams}`)
      .then((response) => {
        if (cancelled) return;
        setCities(response.data);
        setMeta(response.meta);
      })
      .catch(() => {
        if (!cancelled) setCities([]);
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
    api<CityCollection>("/admin/cities?status=all&per_page=100")
      .then((response) => {
        const zones = Array.from(new Set(response.data.map((c) => c.zone?.id).filter(Boolean))).map((id) => {
          const c = response.data.find((city) => city.zone?.id === id);
          return { id: id!, name: c?.zone?.name ?? "Unknown" };
        });
        setZoneOptions(zones);
      })
      .catch(() => setZoneOptions([]));
  }, [user?.role]);

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  if (user.role !== "admin") {
    return (
      <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <EmptyState icon={ShieldAlert} title="Admin access required" body="City management is only available to store administrators." actionLabel="Back to account" actionTo="/account" />
      </div>
    );
  }

  const reload = async () => {
    const response = await api<CityCollection>(`/admin/cities?${searchParams}`);
    setCities(response.data);
    setMeta(response.meta);
  };

  const openCreate = () => {
    setEditingId(null);
    setForm(EMPTY_FORM);
    setErrors({});
    setFormOpen(true);
  };

  const openEdit = (record: City) => {
    setEditingId(record.id);
    setForm({
      zone_id: String(record.zone_id),
      name: record.name,
      code: record.code ?? "",
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
      zone_id: Number(form.zone_id),
      name: form.name,
      code: form.code || null,
      sort_order: Number(form.sort_order) || 0,
      is_active: form.is_active === "true",
    };
    try {
      if (editingId === null) {
        await api("/admin/cities", { method: "POST", body: JSON.stringify(payload) });
        pushToast("City created");
      } else {
        await api(`/admin/cities/${editingId}`, { method: "PUT", body: JSON.stringify(payload) });
        pushToast("City saved");
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

  const remove = async (id: number) => {
    if (!window.confirm("Delete this city?")) return;
    try {
      await api(`/admin/cities/${id}`, { method: "DELETE" });
      pushToast("City deleted");
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
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Cities</h1>
        </div>
        <div className="flex gap-4">
          <Link to="/admin/zones" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Zones</Link>
          <Link to="/admin/suppliers" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Suppliers</Link>
          <Link to="/admin/purchases" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Purchases</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_180px_auto_auto]">
        <Field label="Search cities" htmlFor="admin-city-search">
          <div className="relative">
            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-smoke" aria-hidden />
            <Input id="admin-city-search" value={query} onChange={(event) => setQuery(event.target.value)} className="pl-10" placeholder="Name, code" />
          </div>
        </Field>
        <Field label="Zone" htmlFor="admin-city-zone">
          <Select id="admin-city-zone" value={zoneFilter} onChange={(event) => setZoneFilter(event.target.value)}>
            <option value="">All zones</option>
            {zoneOptions.map((zone) => (
              <option key={zone.id} value={String(zone.id)}>
                {zone.name}
              </option>
            ))}
          </Select>
        </Field>
        <Field label="Status" htmlFor="admin-city-status">
          <Select id="admin-city-status" value={status} onChange={(event) => setStatus(event.target.value)}>
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
        ) : cities.length === 0 ? (
          <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
            <EmptyState icon={Trash2} title="No cities yet" body="Create your first city to get started." />
          </div>
        ) : (
          <div className="space-y-4">
            <p className="text-xs text-smoke dark:text-linen-dim">
              Showing {meta?.total ?? cities.length} cities.
            </p>
            {cities.map((city) => (
              <section key={city.id} className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
                <div className="flex flex-wrap items-start justify-between gap-4 border-b border-line p-5 dark:border-line-dark">
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <h2 className="font-display text-xl text-ink dark:text-linen">{city.name}</h2>
                      <Badge tone="ink">{city.code ?? "—"}</Badge>
                      {city.zone && <span className="ml-2 text-xs text-smoke dark:text-linen-dim">· {city.zone.name}</span>}
                    </div>
                    <p className="mt-1 text-xs text-smoke dark:text-linen-dim">order {city.sort_order}</p>
                  </div>
                  <span className={cn("px-2 py-0.5 text-[10px] font-bold tracking-[0.14em] uppercase", city.is_active ? "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300" : "bg-bronze/15 text-bronze")}>
                    {city.is_active ? "Active" : "Inactive"}
                  </span>
                  <div className="flex flex-wrap gap-2">
                    <Button type="button" size="sm" variant="outline" onClick={() => openEdit(city)}>Edit</Button>
                    <Button type="button" size="sm" variant="ghost" icon={Trash2} onClick={() => void remove(city.id)}>Delete</Button>
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
          <div role="dialog" aria-modal="true" aria-label={editingId === null ? "Create city" : "Edit city"} className="relative w-full max-w-lg bg-cream p-6 shadow-2xl sm:p-8 dark:bg-nox2">
            <div className="mb-5 flex items-center justify-between">
              <h2 className="font-display text-2xl text-ink dark:text-linen">{editingId === null ? "New City" : "Edit City"}</h2>
              <button type="button" onClick={() => setFormOpen(false)} aria-label="Close dialog" className="flex h-10 w-10 items-center justify-center rounded-full hover:bg-fog dark:hover:bg-nox3">
                <X className="h-5 w-5 text-ink dark:text-linen" aria-hidden />
              </button>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Zone" htmlFor="city-zone" required error={errors.zone_id?.[0]}>
                <Select id="city-zone" value={form.zone_id} onChange={(e) => setForm((f) => ({ ...f, zone_id: e.target.value }))}>
                  <option value="">Select a zone</option>
                  {zoneOptions.map((zone) => (
                    <option key={zone.id} value={String(zone.id)}>
                      {zone.name}
                    </option>
                  ))}
                </Select>
              </Field>
              <Field label="Name" htmlFor="city-name" required error={errors.name?.[0]}>
                <Input id="city-name" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
              </Field>
              <Field label="Code" htmlFor="city-code" error={errors.code?.[0]}>
                <Input id="city-code" value={form.code} onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))} placeholder="DHA" />
              </Field>
              <Field label="Sort order" htmlFor="city-sort" error={errors.sort_order?.[0]}>
                <Input id="city-sort" type="number" min={0} value={form.sort_order} onChange={(e) => setForm((f) => ({ ...f, sort_order: e.target.value }))} />
              </Field>
              <Field label="Status" htmlFor="city-status" error={errors.is_active?.[0]}>
                <Select id="city-status" value={form.is_active} onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.value }))}>
                  <option value="true">Active</option>
                  <option value="false">Inactive</option>
                </Select>
              </Field>
            </div>
            <div className="mt-6 flex gap-2">
              <Button type="button" icon={Save} loading={saving} onClick={() => void submit()}>
                {editingId === null ? "Create" : "Save Changes"}
              </Button>
              <Button type="button" variant="ghost" onClick={() => setFormOpen(false)}>Cancel</Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
