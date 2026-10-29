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

interface Purchase {
  id: number;
  reference_number: string;
  supplier_id: number;
  supplier: { id: number; name: string; slug: string } | null;
  status: string;
  subtotal: number;
  tax: number;
  total: number;
  payment_status: string;
  expected_date: string | null;
  received_at: string | null;
  notes: string | null;
  item_count?: number;
}

interface PurchaseCollection {
  data: Purchase[];
  meta?: PaginationMeta;
}

const PURCHASE_STATUSES = ["draft", "ordered", "received", "cancelled"];
const PAYMENT_STATUSES = ["pending", "partial", "paid", "overpaid"];

const EMPTY_FORM = {
  supplier_id: "",
  reference_number: "",
  status: "draft",
  subtotal: "0",
  tax: "0",
  total: "0",
  payment_status: "pending",
  expected_date: "",
  notes: "",
};

type FormState = typeof EMPTY_FORM;

export default function AdminPurchasesPage() {
  usePageTitle("Purchases");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const [purchases, setPurchases] = useState<Purchase[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [paymentStatus, setPaymentStatus] = useState("all");
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [errors, setErrors] = useState<Record<string, string[]>>({});

  const searchParams = useMemo(() => {
    const params = new URLSearchParams({ status, payment_status: paymentStatus, per_page: "50" });
    if (query.trim()) params.set("q", query.trim());
    return params.toString();
  }, [query, status, paymentStatus]);

  useEffect(() => {
    if (user?.role !== "admin") return;

    let cancelled = false;
    setLoading(true);
    api<PurchaseCollection>(`/admin/purchases?${searchParams}`)
      .then((response) => {
        if (cancelled) return;
        setPurchases(response.data);
        setMeta(response.meta);
      })
      .catch(() => {
        if (!cancelled) setPurchases([]);
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
          body="Purchase management is only available to store administrators."
          actionLabel="Back to account"
          actionTo="/account"
        />
      </div>
    );
  }

  const reload = async () => {
    const response = await api<PurchaseCollection>(`/admin/purchases?${searchParams}`);
    setPurchases(response.data);
    setMeta(response.meta);
  };

  const openCreate = () => {
    setEditingId(null);
    setForm(EMPTY_FORM);
    setErrors({});
    setFormOpen(true);
  };

  const openEdit = (record: Purchase) => {
    setEditingId(record.id);
    setForm({
      supplier_id: String(record.supplier_id),
      reference_number: record.reference_number,
      status: record.status,
      subtotal: String(record.subtotal),
      tax: String(record.tax),
      total: String(record.total),
      payment_status: record.payment_status,
      expected_date: record.expected_date ?? "",
      notes: record.notes ?? "",
    });
    setErrors({});
    setFormOpen(true);
  };

  const submit = async () => {
    setSaving(true);
    setErrors({});
    const payload: Record<string, unknown> = {
      supplier_id: Number(form.supplier_id),
      reference_number: form.reference_number,
      status: form.status,
      subtotal: Number(form.subtotal) || 0,
      tax: Number(form.tax) || 0,
      total: Number(form.total) || 0,
      payment_status: form.payment_status,
      expected_date: form.expected_date || null,
      notes: form.notes || null,
    };
    try {
      if (editingId === null) {
        await api("/admin/purchases", { method: "POST", body: JSON.stringify(payload) });
        pushToast("Purchase created");
      } else {
        await api(`/admin/purchases/${editingId}`, { method: "PUT", body: JSON.stringify(payload) });
        pushToast("Purchase saved");
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
    if (!window.confirm("Delete this purchase order?")) return;
    try {
      await api(`/admin/purchases/${id}`, { method: "DELETE" });
      pushToast("Purchase deleted");
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
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Purchase Orders</h1>
        </div>
        <div className="flex gap-4">
          <Link to="/admin/suppliers" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Suppliers</Link>
          <Link to="/admin/zones" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Zones</Link>
          <Link to="/admin/cities" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Cities</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_180px_180px_auto]">
        <Field label="Search POs" htmlFor="admin-purchase-search">
          <div className="relative">
            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-smoke" aria-hidden />
            <Input id="admin-purchase-search" value={query} onChange={(event) => setQuery(event.target.value)} className="pl-10" placeholder="Reference number" />
          </div>
        </Field>
        <Field label="Status" htmlFor="admin-purchase-status">
          <Select id="admin-purchase-status" value={status} onChange={(event) => setStatus(event.target.value)}>
            <option value="all">All</option>
            {PURCHASE_STATUSES.map((s) => (
              <option key={s} value={s} className="capitalize">
                {s}
              </option>
            ))}
          </Select>
        </Field>
        <Field label="Payment" htmlFor="admin-purchase-payment">
          <Select id="admin-purchase-payment" value={paymentStatus} onChange={(event) => setPaymentStatus(event.target.value)}>
            <option value="all">All</option>
            {PAYMENT_STATUSES.map((s) => (
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
        ) : purchases.length === 0 ? (
          <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
            <EmptyState icon={Trash2} title="No purchase orders yet" body="Create your first purchase order to get started." />
          </div>
        ) : (
          <div className="space-y-4">
            <p className="text-xs text-smoke dark:text-linen-dim">
              Showing {meta?.total ?? purchases.length} purchase orders.
            </p>
            {purchases.map((purchase) => (
              <section key={purchase.id} className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
                <div className="flex flex-wrap items-start justify-between gap-4 border-b border-line p-5 dark:border-line-dark">
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <h2 className="font-display text-xl text-ink dark:text-linen">{purchase.reference_number}</h2>
                      <Badge tone="ink">{purchase.supplier?.name ?? "Unknown supplier"}</Badge>
                    </div>
                    <p className="mt-1 text-xs text-smoke dark:text-linen-dim">
                      ${purchase.total.toFixed(2)} ·
                      {purchase.expected_date ? `Expected ${new Date(purchase.expected_date).toLocaleDateString()}` : ""}
                    </p>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    <span className={cn("px-2 py-0.5 text-[10px] font-bold tracking-[0.14em] uppercase", purchase.status === "received" ? "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300" : purchase.status === "cancelled" ? "bg-red-800/10 text-red-800 dark:text-red-300" : "bg-bronze/15 text-bronze")}>
                      {purchase.status}
                    </span>
                    <span className={cn("px-2 py-0.5 text-[10px] font-bold tracking-[0.14em] uppercase", purchase.payment_status === "paid" ? "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300" : "bg-bronze/15 text-bronze")}>
                      {purchase.payment_status}
                    </span>
                    <div className="flex flex-wrap gap-2">
                      <Button type="button" size="sm" variant="outline" onClick={() => openEdit(purchase)}>Edit</Button>
                      <Button type="button" size="sm" variant="ghost" icon={Trash2} onClick={() => void remove(purchase.id)}>Delete</Button>
                    </div>
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
          <div role="dialog" aria-modal="true" aria-label={editingId === null ? "Create purchase order" : "Edit purchase order"} className="relative w-full max-w-lg bg-cream p-6 shadow-2xl sm:p-8 dark:bg-nox2">
            <div className="mb-5 flex items-center justify-between">
              <h2 className="font-display text-2xl text-ink dark:text-linen">{editingId === null ? "New Purchase Order" : "Edit Purchase Order"}</h2>
              <button type="button" onClick={() => setFormOpen(false)} aria-label="Close dialog" className="flex h-10 w-10 items-center justify-center rounded-full hover:bg-fog dark:hover:bg-nox3">
                <X className="h-5 w-5 text-ink dark:text-linen" aria-hidden />
              </button>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Reference Number" htmlFor="purchase-ref" required error={errors.reference_number?.[0]}>
                <Input id="purchase-ref" value={form.reference_number} disabled={editingId !== null} onChange={(e) => setForm((f) => ({ ...f, reference_number: e.target.value }))} placeholder="PO-00001" />
              </Field>
              <Field label="Supplier ID" htmlFor="purchase-supplier" required type="number" error={errors.supplier_id?.[0]}>
                <Input id="purchase-supplier" type="number" min={1} value={form.supplier_id} onChange={(e) => setForm((f) => ({ ...f, supplier_id: e.target.value }))} />
              </Field>
              <Field label="Status" htmlFor="purchase-status" error={errors.status?.[0]}>
                <Select id="purchase-status" value={form.status} onChange={(e) => setForm((f) => ({ ...f, status: e.target.value }))}>
                  {PURCHASE_STATUSES.map((s) => (
                    <option key={s} value={s} className="capitalize">
                      {s}
                    </option>
                  ))}
                </Select>
              </Field>
              <Field label="Payment Status" htmlFor="purchase-payment" error={errors.payment_status?.[0]}>
                <Select id="purchase-payment" value={form.payment_status} onChange={(e) => setForm((f) => ({ ...f, payment_status: e.target.value }))}>
                  {PAYMENT_STATUSES.map((s) => (
                    <option key={s} value={s} className="capitalize">
                      {s}
                    </option>
                  ))}
                </Select>
              </Field>
              <Field label="Subtotal" htmlFor="purchase-subtotal" type="number" step="0.01" error={errors.subtotal?.[0]}>
                <Input id="purchase-subtotal" type="number" step="0.01" min={0} value={form.subtotal} onChange={(e) => setForm((f) => ({ ...f, subtotal: e.target.value }))} />
              </Field>
              <Field label="Tax" htmlFor="purchase-tax" type="number" step="0.01" error={errors.tax?.[0]}>
                <Input id="purchase-tax" type="number" step="0.01" min={0} value={form.tax} onChange={(e) => setForm((f) => ({ ...f, tax: e.target.value }))} />
              </Field>
              <Field label="Total" htmlFor="purchase-total" type="number" step="0.01" error={errors.total?.[0]}>
                <Input id="purchase-total" type="number" step="0.01" min={0} value={form.total} onChange={(e) => setForm((f) => ({ ...f, total: e.target.value }))} />
              </Field>
              <Field label="Expected Date" htmlFor="purchase-expected" type="date" error={errors.expected_date?.[0]}>
                <Input id="purchase-expected" type="date" value={form.expected_date} onChange={(e) => setForm((f) => ({ ...f, expected_date: e.target.value }))} />
              </Field>
              <Field label="Notes" htmlFor="purchase-notes" className="sm:col-span-2" error={errors.notes?.[0]}>
                <Input id="purchase-notes" value={form.notes} onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))} />
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
