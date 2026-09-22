import { useEffect, useMemo, useState } from "react";
import { Link, Navigate, useLocation } from "react-router-dom";
import { Images, Megaphone, Plus, Save, Search, ShieldAlert, Trash2, X } from "lucide-react";
import { api, ApiError } from "@/lib/api";
import type { PaginationMeta } from "@/store/catalogStore";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Badge, Button, EmptyState, Field, Input, Select, Spinner, Textarea } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";
import { cn } from "@/utils/cn";

interface CmsRecord {
  id: number;
  key: string;
  type?: string;
  title: string | null;
  subtitle: string | null;
  body: string | null;
  image_url: string | null;
  mobile_image_url: string | null;
  cta_label: string | null;
  cta_url: string | null;
  status: string;
  sort_order: number;
  starts_at: string | null;
  ends_at: string | null;
}

interface CmsCollection {
  data: CmsRecord[];
  meta?: PaginationMeta;
}

type Tab = "content" | "banners";

const CONTENT_TYPES = ["announcement", "promo", "collection", "service", "homepage_section"];
const STATUSES = ["draft", "published", "archived"];

const EMPTY_FORM = {
  key: "",
  type: "promo",
  title: "",
  subtitle: "",
  body: "",
  image_path: "",
  mobile_image_path: "",
  cta_label: "",
  cta_url: "",
  status: "draft",
  sort_order: "0",
  starts_at: "",
  ends_at: "",
};

type FormState = typeof EMPTY_FORM;

function toLocalInput(value: string | null): string {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "";
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function statusTone(status: string) {
  if (status === "published") return "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300";
  if (status === "archived") return "bg-red-800/10 text-red-800 dark:text-red-300";
  return "bg-bronze/15 text-bronze";
}

function fieldErrors(error: unknown): Record<string, string[]> {
  return error instanceof ApiError ? (error.errors ?? {}) : {};
}

export default function AdminCmsPage() {
  usePageTitle("Content");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const [tab, setTab] = useState<Tab>("content");
  const [records, setRecords] = useState<CmsRecord[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [uploadingId, setUploadingId] = useState<number | null>(null);

  const base = tab === "content" ? "/admin/cms/content" : "/admin/banners";

  const searchParams = useMemo(() => {
    const params = new URLSearchParams({ status, per_page: "50" });
    if (query.trim()) params.set("q", query.trim());
    return params.toString();
  }, [query, status, tab]);

  useEffect(() => {
    if (user?.role !== "admin") return;
    let cancelled = false;
    setLoading(true);
    api<CmsCollection>(`${base}?${searchParams}`)
      .then((response) => {
        if (cancelled) return;
        setRecords(response.data);
        setMeta(response.meta);
      })
      .catch(() => {
        if (!cancelled) setRecords([]);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [base, searchParams, user?.role]);

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  if (user.role !== "admin") {
    return (
      <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <EmptyState
          icon={ShieldAlert}
          title="Admin access required"
          body="Content management is only available to store administrators."
          actionLabel="Back to account"
          actionTo="/account"
        />
      </div>
    );
  }

  const reload = async () => {
    const response = await api<CmsCollection>(`${base}?${searchParams}`);
    setRecords(response.data);
    setMeta(response.meta);
  };

  const openCreate = () => {
    setEditingId(null);
    setForm(EMPTY_FORM);
    setErrors({});
    setFormOpen(true);
  };

  const openEdit = (record: CmsRecord) => {
    setEditingId(record.id);
    setForm({
      key: record.key ?? "",
      type: (record.type as string) ?? "promo",
      title: record.title ?? "",
      subtitle: record.subtitle ?? "",
      body: record.body ?? "",
      image_path: "",
      mobile_image_path: "",
      cta_label: record.cta_label ?? "",
      cta_url: record.cta_url ?? "",
      status: record.status,
      sort_order: String(record.sort_order),
      starts_at: toLocalInput(record.starts_at),
      ends_at: toLocalInput(record.ends_at),
    });
    setErrors({});
    setFormOpen(true);
  };

  const submit = async () => {
    setSaving(true);
    setErrors({});
    const payload: Record<string, unknown> = {
      title: form.title || null,
      subtitle: form.subtitle || null,
      body: form.body || null,
      cta_label: form.cta_label || null,
      cta_url: form.cta_url || null,
      status: form.status,
      sort_order: Number(form.sort_order) || 0,
      starts_at: form.starts_at || null,
      ends_at: form.ends_at || null,
    };
    if (tab === "content") {
      payload.key = form.key;
      payload.type = form.type;
    } else {
      if (form.key.trim() !== "") payload.key = form.key;
    }
    try {
      if (editingId === null) {
        await api(`${base}`, { method: "POST", body: JSON.stringify(payload) });
        pushToast(tab === "content" ? "Content created" : "Banner created");
      } else {
        await api(`${base}/${editingId}`, { method: "PUT", body: JSON.stringify(payload) });
        pushToast("Changes saved");
      }
      setFormOpen(false);
      await reload();
    } catch (requestError) {
      setErrors(fieldErrors(requestError));
      pushToast(requestError instanceof Error ? requestError.message : "Save failed");
    } finally {
      setSaving(false);
    }
  };

  const remove = async (id: number) => {
    if (!window.confirm("Delete this record and its uploaded images?")) return;
    try {
      await api(`${base}/${id}`, { method: "DELETE" });
      pushToast("Deleted");
      await reload();
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Delete failed");
    }
  };

  const upload = async (id: number, files: FileList | null, field: "image" | "mobile_image") => {
    const file = files?.[0];
    if (!file) return;
    setUploadingId(id);
    try {
      const formData = new FormData();
      formData.append(field, file);
      await api(`${base}/${id}/image`, { method: "POST", body: formData });
      pushToast("Image uploaded");
      await reload();
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Upload failed");
    } finally {
      setUploadingId(null);
    }
  };

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">
            Content &amp; Banners
          </h1>
        </div>
        <div className="flex flex-wrap gap-4">
          <Link to="/admin/products" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Inventory</Link>
          <Link to="/admin/orders" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Orders</Link>
          <Link to="/admin/customers" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Customers</Link>
          <Link to="/admin/reviews" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Reviews</Link>
          <Link to="/admin/analytics" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Analytics</Link>
          <Link to="/admin/audit-log" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Audit Log</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 flex gap-1 border-b border-line dark:border-line-dark" role="tablist" aria-label="Content sections">
        {(
          [
            { id: "content", label: "Content", icon: Megaphone },
            { id: "banners", label: "Banners", icon: Images },
          ] as Array<{ id: Tab; label: string; icon: typeof Megaphone }>
        ).map((t) => (
          <button
            key={t.id}
            type="button"
            role="tab"
            aria-selected={tab === t.id}
            onClick={() => {
              setTab(t.id);
              setFormOpen(false);
            }}
            className={cn(
              "flex shrink-0 items-center gap-2 border-b-2 px-5 py-3 text-xs font-bold tracking-[0.16em] uppercase transition-all",
              tab === t.id
                ? "border-ink text-ink dark:border-linen dark:text-linen"
                : "border-transparent text-smoke hover:text-ink dark:text-linen-dim dark:hover:text-linen",
            )}
          >
            <t.icon className="h-4 w-4" aria-hidden />
            {t.label}
          </button>
        ))}
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_180px_auto_auto]">
        <Field label={tab === "content" ? "Search content" : "Search banners"} htmlFor="admin-cms-search">
          <div className="relative">
            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-smoke" aria-hidden />
            <Input
              id="admin-cms-search"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              className="pl-10"
              placeholder="Key, title"
            />
          </div>
        </Field>
        <Field label="Status" htmlFor="admin-cms-status">
          <Select id="admin-cms-status" value={status} onChange={(event) => setStatus(event.target.value)}>
            <option value="all">All</option>
            {STATUSES.map((s) => (
              <option key={s} value={s} className="capitalize">{s}</option>
            ))}
          </Select>
        </Field>
        <Button type="button" icon={Plus} className="self-end" onClick={openCreate}>
          New {tab === "content" ? "Content" : "Banner"}
        </Button>
      </div>

      <div className="mt-8">
        {loading ? (
          <div className="flex min-h-60 items-center justify-center">
            <Spinner />
          </div>
        ) : records.length === 0 ? (
          <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
            <EmptyState
              icon={tab === "content" ? Megaphone : Images}
              title={tab === "content" ? "No content yet" : "No banners yet"}
              body="Create the first record to get started."
            />
          </div>
        ) : (
          <div className="space-y-4">
            <p className="text-xs text-smoke dark:text-linen-dim">
              Showing {meta?.total ?? records.length} records, ordered by sort order.
            </p>
            {records.map((record) => (
              <section key={record.id} className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
                <div className="flex flex-wrap items-start justify-between gap-4 border-b border-line p-5 dark:border-line-dark">
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <h2 className="font-display text-xl text-ink dark:text-linen">{record.title || record.key || `#${record.id}`}</h2>
                      <span className={cn("px-2 py-0.5 text-[10px] font-bold tracking-[0.14em] uppercase", statusTone(record.status))}>
                        {record.status}
                      </span>
                      {record.type && <Badge tone="ink">{record.type}</Badge>}
                    </div>
                    <p className="mt-1 font-mono text-xs text-smoke dark:text-linen-dim">
                      {record.key ?? `#${record.id}`} · order {record.sort_order}
                      {record.starts_at ? ` · from ${new Date(record.starts_at).toLocaleDateString()}` : ""}
                      {record.ends_at ? ` · until ${new Date(record.ends_at).toLocaleDateString()}` : ""}
                    </p>
                    {record.image_url && (
                      <img src={record.image_url} alt="" className="mt-3 h-20 w-auto border border-line object-cover dark:border-line-dark" loading="lazy" />
                    )}
                  </div>
                  <div className="flex flex-wrap gap-2">
                    <Button type="button" size="sm" variant="outline" onClick={() => openEdit(record)}>Edit</Button>
                    <Button
                      type="button"
                      size="sm"
                      variant="ghost"
                      icon={Trash2}
                      onClick={() => void remove(record.id)}
                    >
                      Delete
                    </Button>
                  </div>
                </div>
                <div className="flex flex-wrap items-center gap-4 p-5">
                  <label className="text-xs font-semibold text-smoke dark:text-linen-dim">
                    Image
                    <input
                      type="file"
                      accept="image/jpeg,image/png,image/webp"
                      className="mt-1 block text-xs"
                      disabled={uploadingId === record.id}
                      onChange={(event) => void upload(record.id, event.target.files, "image")}
                    />
                  </label>
                  <label className="text-xs font-semibold text-smoke dark:text-linen-dim">
                    Mobile image
                    <input
                      type="file"
                      accept="image/jpeg,image/png,image/webp"
                      className="mt-1 block text-xs"
                      disabled={uploadingId === record.id}
                      onChange={(event) => void upload(record.id, event.target.files, "mobile_image")}
                    />
                  </label>
                  {uploadingId === record.id && <Spinner className="h-4 w-4" />}
                </div>
              </section>
            ))}
          </div>
        )}
      </div>

      {formOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-ink/50" onClick={() => setFormOpen(false)} aria-hidden />
          <div
            role="dialog"
            aria-modal="true"
            aria-label={editingId === null ? "Create record" : "Edit record"}
            className="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto bg-cream p-6 shadow-2xl sm:p-8 dark:bg-nox2"
          >
            <div className="mb-5 flex items-center justify-between">
              <h2 className="font-display text-2xl text-ink dark:text-linen">
                {editingId === null ? `New ${tab === "content" ? "Content" : "Banner"}` : "Edit Record"}
              </h2>
              <button
                type="button"
                onClick={() => setFormOpen(false)}
                aria-label="Close dialog"
                className="flex h-10 w-10 items-center justify-center rounded-full hover:bg-fog dark:hover:bg-nox3"
              >
                <X className="h-5 w-5 text-ink dark:text-linen" aria-hidden />
              </button>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              {tab === "content" ? (
                <Field label="Key" htmlFor="cms-key" required error={errors.key?.[0]}>
                  <Input id="cms-key" value={form.key} disabled={editingId !== null} onChange={(e) => setForm((f) => ({ ...f, key: e.target.value }))} placeholder="site-announcement" />
                </Field>
              ) : (
                <Field label="Key (optional)" htmlFor="cms-key" error={errors.key?.[0]}>
                  <Input id="cms-key" value={form.key} onChange={(e) => setForm((f) => ({ ...f, key: e.target.value }))} placeholder="hero-slide-1" />
                </Field>
              )}
              {tab === "content" ? (
                <Field label="Type" htmlFor="cms-type" required error={errors.type?.[0]}>
                  <Select id="cms-type" value={form.type} onChange={(e) => setForm((f) => ({ ...f, type: e.target.value }))}>
                    {CONTENT_TYPES.map((t) => (
                      <option key={t} value={t}>{t}</option>
                    ))}
                  </Select>
                </Field>
              ) : (
                <Field label="Sort order" htmlFor="cms-sort" error={errors.sort_order?.[0]}>
                  <Input id="cms-sort" type="number" min={0} value={form.sort_order} onChange={(e) => setForm((f) => ({ ...f, sort_order: e.target.value }))} />
                </Field>
              )}
              <Field label={tab === "content" ? "Title" : "Title (alt text)"} htmlFor="cms-title" required={tab === "content"} error={errors.title?.[0]}>
                <Input id="cms-title" value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} />
              </Field>
              <Field label="Subtitle" htmlFor="cms-subtitle" error={errors.subtitle?.[0]}>
                <Input id="cms-subtitle" value={form.subtitle} onChange={(e) => setForm((f) => ({ ...f, subtitle: e.target.value }))} />
              </Field>
              <Field label="Body" htmlFor="cms-body" className="sm:col-span-2" error={errors.body?.[0]}>
                <Textarea id="cms-body" rows={3} value={form.body} onChange={(e) => setForm((f) => ({ ...f, body: e.target.value }))} />
              </Field>
              <Field label="CTA label" htmlFor="cms-cta-label" error={errors.cta_label?.[0]}>
                <Input id="cms-cta-label" value={form.cta_label} onChange={(e) => setForm((f) => ({ ...f, cta_label: e.target.value }))} />
              </Field>
              <Field label="CTA URL" htmlFor="cms-cta-url" error={errors.cta_url?.[0]}>
                <Input id="cms-cta-url" value={form.cta_url} onChange={(e) => setForm((f) => ({ ...f, cta_url: e.target.value }))} placeholder="/shop" />
              </Field>
              {tab === "content" && (
                <Field label="Sort order" htmlFor="cms-sort" error={errors.sort_order?.[0]}>
                  <Input id="cms-sort" type="number" min={0} value={form.sort_order} onChange={(e) => setForm((f) => ({ ...f, sort_order: e.target.value }))} />
                </Field>
              )}
              <Field label="Status" htmlFor="cms-status" error={errors.status?.[0]}>
                <Select id="cms-status" value={form.status} onChange={(e) => setForm((f) => ({ ...f, status: e.target.value }))}>
                  {STATUSES.map((s) => (
                    <option key={s} value={s} className="capitalize">{s}</option>
                  ))}
                </Select>
              </Field>
              <Field label="Starts at" htmlFor="cms-starts" error={errors.starts_at?.[0]}>
                <Input id="cms-starts" type="datetime-local" value={form.starts_at} onChange={(e) => setForm((f) => ({ ...f, starts_at: e.target.value }))} />
              </Field>
              <Field label="Ends at" htmlFor="cms-ends" error={errors.ends_at?.[0]}>
                <Input id="cms-ends" type="datetime-local" value={form.ends_at} onChange={(e) => setForm((f) => ({ ...f, ends_at: e.target.value }))} />
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
