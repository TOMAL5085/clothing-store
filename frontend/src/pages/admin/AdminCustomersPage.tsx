import { useEffect, useMemo, useState } from "react";
import { Link, Navigate, useLocation } from "react-router-dom";
import { RefreshCcw, Search, ShieldAlert, Users } from "lucide-react";
import { api } from "@/lib/api";
import { useAuthStore, type AuthUser } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import type { PaginationMeta } from "@/store/catalogStore";
import { Button, EmptyState, Field, Input, Select, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

interface CustomerCollection {
  data: AuthUser[];
  meta?: PaginationMeta;
}

interface CustomerResponse {
  data: AuthUser;
}

export default function AdminCustomersPage() {
  usePageTitle("Customers");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const [customers, setCustomers] = useState<AuthUser[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [loading, setLoading] = useState(true);
  const [savingId, setSavingId] = useState<number | undefined>();
  const [error, setError] = useState("");

  const searchParams = useMemo(() => {
    const params = new URLSearchParams({ status, per_page: "100" });
    if (query.trim()) params.set("q", query.trim());
    return params.toString();
  }, [query, status]);

  useEffect(() => {
    if (user?.role !== "admin") return;
    let cancelled = false;
    setLoading(true);
    setError("");
    api<CustomerCollection>(`/admin/customers?${searchParams}`)
      .then((response) => {
        if (cancelled) return;
        setCustomers(response.data);
        setMeta(response.meta);
      })
      .catch((requestError) => {
        if (!cancelled) setError(requestError instanceof Error ? requestError.message : "Customers could not be loaded.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [searchParams, user?.role]);

  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />;

  if (user.role !== "admin") {
    return (
      <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <EmptyState icon={ShieldAlert} title="Admin access required" body="Customer management is only available to store administrators." actionLabel="Back to account" actionTo="/account" />
      </div>
    );
  }

  const updateStatus = async (customer: AuthUser, nextStatus: "active" | "inactive") => {
    if (!customer.id) return;
    setSavingId(customer.id);
    try {
      const response = await api<CustomerResponse>(`/admin/customers/${customer.id}/status`, {
        method: "PATCH",
        body: JSON.stringify({ status: nextStatus }),
      });
      setCustomers((current) => current.map((item) => (item.id === customer.id ? response.data : item)));
      pushToast("Customer updated");
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Customer update failed");
    } finally {
      setSavingId(undefined);
    }
  };

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Customers</h1>
        </div>
        <div className="flex gap-4">
          <Link to="/admin/products" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Inventory</Link>
          <Link to="/admin/orders" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Orders</Link>
          <Link to="/admin/reviews" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Reviews</Link>`n          <Link to="/admin/analytics" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Analytics</Link>`n          <Link to="/admin/audit-log" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Audit Log</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_180px_auto]">
        <Field label="Search customers" htmlFor="admin-customer-search">
          <div className="relative">
            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-smoke" aria-hidden />
            <Input id="admin-customer-search" value={query} onChange={(event) => setQuery(event.target.value)} className="pl-10" placeholder="Name, email, phone" />
          </div>
        </Field>
        <Field label="Status" htmlFor="admin-customer-status">
          <Select id="admin-customer-status" value={status} onChange={(event) => setStatus(event.target.value)}>
            <option value="all">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </Select>
        </Field>
        <Button type="button" variant="outline" icon={RefreshCcw} className="self-end" onClick={() => setQuery((current) => current.trim())}>Refresh</Button>
      </div>

      <div className="mt-8">
        {loading && <div className="flex min-h-60 items-center justify-center"><Spinner /></div>}
        {!loading && error && <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2"><EmptyState icon={Users} title="Customers unavailable" body={error} /></div>}
        {!loading && !error && customers.length === 0 && <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2"><EmptyState icon={Users} title="No customers found" body="Try a different search or status filter." /></div>}
        {!loading && !error && customers.length > 0 && (
          <div className="space-y-4">
            <p className="text-xs text-smoke dark:text-linen-dim">Showing {meta?.total ?? customers.length} customers.</p>
            {customers.map((customer) => (
              <section key={customer.id ?? customer.email} className="grid gap-4 border border-line bg-cream p-5 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_auto] md:items-center">
                <div>
                  <h2 className="font-display text-2xl text-ink dark:text-linen">{customer.name}</h2>
                  <p className="mt-1 text-sm text-smoke dark:text-linen-dim">{customer.email}</p>
                  <p className="mt-2 text-xs font-semibold tracking-[0.14em] text-smoke uppercase dark:text-linen-dim">
                    {customer.emailVerified ? "Verified" : "Pending"} / {customer.ordersCount ?? 0} orders / {customer.addressesCount ?? 0} addresses
                  </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                  <span className="px-3 py-1 text-[10px] font-bold tracking-[0.14em] uppercase bg-ink text-paper dark:bg-linen dark:text-nox">{customer.status ?? "active"}</span>
                  <Button type="button" size="sm" variant="outline" loading={savingId === customer.id} onClick={() => void updateStatus(customer, customer.status === "inactive" ? "active" : "inactive")}>
                    {customer.status === "inactive" ? "Activate" : "Deactivate"}
                  </Button>
                </div>
              </section>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
