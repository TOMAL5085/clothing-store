import { useEffect, useMemo, useState } from "react";
import { Link, Navigate, useLocation } from "react-router-dom";
import { RefreshCcw, Search, ShieldAlert, Trash2, X } from "lucide-react";
import { api } from "@/lib/api";
import type { PaginationMeta } from "@/store/catalogStore";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Badge, Button, EmptyState, Field, Input, Select, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";
import { cn } from "@/utils/cn";

interface LedgerEntry {
  id: number;
  supplier_id: number;
  reference_type: string;
  reference_id: number;
  debit: number;
  credit: number;
  balance: number;
  description: string | null;
  created_at: string;
}

interface LedgerCollection {
  data: LedgerEntry[];
  meta?: PaginationMeta;
}

const EMPTY_FORM = {
  supplier_id: "",
  reference_type: "purchase",
  reference_id: "",
  debit: "0",
  credit: "0",
  description: "",
};

type FormState = typeof EMPTY_FORM;

export default function AdminSupplierLedgerPage() {
  usePageTitle("Supplier Ledger");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const [entries, setEntries] = useState<LedgerEntry[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [supplierFilter, setSupplierFilter] = useState("");
  const [typeFilter, setTypeFilter] = useState("all");
  const [formOpen, setFormOpen] = useState(false);
  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [errors, setErrors] = useState<Record<string, string[]>>({});

  const searchParams = useMemo(() => {
    const params = new URLSearchParams({ per_page: "50" });
    if (supplierFilter !== "") params.set("supplier_id", supplierFilter);
    if (typeFilter !== "all") params.set("reference_type", typeFilter);
    return params.toString();
  }, [supplierFilter, typeFilter]);

  useEffect(() => {
    if (user?.role !== "admin") return;

    let cancelled = false;
    setLoading(true);
    api<LedgerCollection>(`/admin/supplier-ledger?${searchParams}`)
      .then((response) => {
        if (cancelled) return;
        setEntries(response.data);
        setMeta(response.meta);
      })
      .catch(() => {
        if (!cancelled) setEntries([]);
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
          body="Supplier ledger is only available to store administrators."
          actionLabel="Back to account"
          actionTo="/account"
        />
      </div>
    );
  }

  const reload = async () => {
    const response = await api<LedgerCollection>(`/admin/supplier-ledger?${searchParams}`);
    setEntries(response.data);
    setMeta(response.meta);
  };

  const openCreate = () => {
    setForm(EMPTY_FORM);
    setErrors({});
    setFormOpen(true);
  };

  const submit = async () => {
    setSaving(true);
    setErrors({});
    const payload: Record<string, unknown> = {
      supplier_id: Number(form.supplier_id),
      reference_type: form.reference_type,
      reference_id: Number(form.reference_id),
      debit: Number(form.debit) || 0,
      credit: Number(form.credit) || 0,
      description: form.description || null,
    };
    try {
      await api("/admin/supplier-ledger", { method: "POST", body: JSON.stringify(payload) });
      pushToast("Ledger entry created");
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
    if (!window.confirm("Delete this ledger entry?")) return;
    try {
      await api(`/admin/supplier-ledger/${id}`, { method: "DELETE" });
      pushToast("Entry deleted");
      await reload();
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Delete failed");
    }
  };

  const totalDebit = entries.reduce((sum, e) => sum + e.debit, 0);
  const totalCredit = entries.reduce((sum, e) => sum + e.credit, 0);

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Supplier Ledger</h1>
        </div>
        <div className="flex gap-4">
          <Link to="/admin/suppliers" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Suppliers</Link>
          <Link to="/admin/purchases" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Purchases</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_180px_auto_auto]">
        <Field label="Filter by Supplier ID" htmlFor="ledger-supplier">
          <Input id="ledger-supplier" type="number" min={0} value={supplierFilter} onChange={(e) => setSupplierFilter(e.target.value)} />
        </Field>
        <Field label="Reference Type" htmlFor="ledger-type">
          <Select id="ledger-type" value={typeFilter} onChange={(event) => setTypeFilter(event.target.value)}>
            <option value="all">All</option>
            <option value="purchase">Purchase</option>
            <option value="payment">Payment</option>
            <option value="adjustment">Adjustment</option>
          </Select>
        </Field>
        <Button type="button" icon={RefreshCcw} className="self-end" onClick={() => setSupplierFilter("")}>
          Refresh
        </Button>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream dark:border-line-dark dark:bg-nox2 p-4">
        <div className="flex justify-between text-sm font-semibold text-smoke dark:text-linen-dim">
          <span>Total Debit</span>
          <span>${totalDebit.toFixed(2)}</span>
        </div>
        <div className="flex justify-between text-sm font-semibold text-smoke dark:text-linen-dim">
          <span>Total Credit</span>
          <span>${totalCredit.toFixed(2)}</span>
        </div>
        <div className="flex justify-between text-sm font-bold text-ink dark:text-linen border-t border-line pt-2">
          <span>Net Balance</span>
          <span>${(totalDebit - totalCredit).toFixed(2)}</span>
        </div>
      </div>

      <div className="mt-8">
        {loading ? (
          <div className="flex min-h-60 items-center justify-center">
            <Spinner />
          </div>
        ) : entries.length === 0 ? (
          <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
            <EmptyState icon={LucideSpinner} title="No ledger entries yet" body="Create your first ledger entry to get started." />
          </div>
        ) : (
          <div className="space-y-4">
            <p className="text-xs text-smoke dark:text-linen-dim">
              Showing {meta?.total ?? entries.length} entries.
            </p>
            {entries.map((entry) => (
              <section key={entry.id} className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
                <div className="flex flex-wrap items-start justify-between gap-4 border-b border-line p-5 dark:border-line-dark">
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <h2 className="font-display text-xl text-ink dark:text-linen">#{entry.id}</h2>
                      <Badge tone="ink">{entry.reference_type}</Badge>
                    </div>
                    <p className="mt-1 text-xs text-smoke dark:text-linen-dim">
                      Supplier {entry.supplier_id} · Ref {entry.reference_id} ·
                      {new Date(entry.created_at).toLocaleDateString()}
                    </p>
                    {entry.description && <p className="mt-1 text-xs font-medium text-ink dark:text-linen">{entry.description}</p>}
                  </div>
                  <div className="flex flex-wrap items-end gap-4">
                    <span className="text-right">
                      <span className="text-xs font-semibold text-emerald-800 dark:text-emerald-300">+${entry.debit.toFixed(2)}</span>
                    </span>
                    <span className="text-right">
                      <span className="text-xs font-semibold text-red-800 dark:text-red-300">-${entry.credit.toFixed(2)}</span>
                    </span>
                    <span className="text-right font-mono text-lg font-bold text-ink dark:text-linen">
                      ${entry.balance.toFixed(2)}
                    </span>
                    <Button type="button" size="sm" variant="ghost" icon={Trash2} onClick={() => void remove(entry.id)}>Delete</Button>
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
          <div role="dialog" aria-modal="true" aria-label="Create ledger entry" className="relative w-full max-w-lg bg-cream p-6 shadow-2xl sm:p-8 dark:bg-nox2">
            <div className="mb-5 flex items-center justify-between">
              <h2 className="font-display text-2xl text-ink dark:text-linen">New Ledger Entry</h2>
              <button type="button" onClick={() => setFormOpen(false)} aria-label="Close dialog" className="flex h-10 w-10 items-center justify-center rounded-full hover:bg-fog dark:hover:bg-nox3">
                <X className="h-5 w-5 text-ink dark:text-linen" aria-hidden />
              </button>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Supplier ID" htmlFor="ledger-supplier-id" required type="number" error={errors.supplier_id?.[0]}>
                <Input id="ledger-supplier-id" type="number" min={1} value={form.supplier_id} onChange={(e) => setForm((f) => ({ ...f, supplier_id: e.target.value }))} />
              </Field>
              <Field label="Reference Type" htmlFor="ledger-ref-type" error={errors.reference_type?.[0]}>
                <Select id="ledger-ref-type" value={form.reference_type} onChange={(e) => setForm((f) => ({ ...f, reference_type: e.target.value }))}>
                  <option value="purchase">Purchase</option>
                  <option value="payment">Payment</option>
                  <option value="adjustment">Adjustment</option>
                </Select>
              </Field>
              <Field label="Reference ID" htmlFor="ledger-ref-id" required type="number" error={errors.reference_id?.[0]}>
                <Input id="ledger-ref-id" type="number" min={1} value={form.reference_id} onChange={(e) => setForm((f) => ({ ...f, reference_id: e.target.value }))} />
              </Field>
              <Field label="Debit" htmlFor="ledger-debit" type="number" step="0.01" min={0} error={errors.debit?.[0]}>
                <Input id="ledger-debit" type="number" step="0.01" min={0} value={form.debit} onChange={(e) => setForm((f) => ({ ...f, debit: e.target.value }))} />
              </Field>
              <Field label="Credit" htmlFor="ledger-credit" type="number" step="0.01" min={0} error={errors.credit?.[0]}>
                <Input id="ledger-credit" type="number" step="0.01" min={0} value={form.credit} onChange={(e) => setForm((f) => ({ ...f, credit: e.target.value }))} />
              </Field>
              <Field label="Description" htmlFor="ledger-desc" className="sm:col-span-2" error={errors.description?.[0]}>
                <Input id="ledger-desc" value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} />
              </Field>
            </div>
            <div className="mt-6 flex gap-2">
              <Button type="button" icon={Save} loading={saving} onClick={() => void submit()}>
                Create Entry
              </Button>
              <Button type="button" variant="ghost" onClick={() => setFormOpen(false)}>Cancel</Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
