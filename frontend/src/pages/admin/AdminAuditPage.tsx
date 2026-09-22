import { useEffect, useMemo, useState } from "react";
import { Link, Navigate, useLocation } from "react-router-dom";
import { ScrollText, ShieldAlert } from "lucide-react";
import { api } from "@/lib/api";
import type { PaginationMeta } from "@/store/catalogStore";
import { useAuthStore } from "@/store/authStore";
import { Button, EmptyState, Field, Input, Select, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

interface AuditLogEntry {
  id: number;
  action: string;
  actor_id: number | null;
  actor_role: string | null;
  auditable_type: string | null;
  auditable_id: number | null;
  ip: string | null;
  user_agent: string | null;
  metadata: Record<string, string | number | boolean | null>;
  created_at: string | null;
}

interface AuditLogCollection {
  data: AuditLogEntry[];
  meta?: PaginationMeta;
}

function formatTime(value: string | null) {
  if (!value) return "—";
  return new Date(value).toLocaleString("en-US", {
    day: "numeric",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function targetLabel(entry: AuditLogEntry) {
  const meta = entry.metadata ?? {};
  const ref =
    (typeof meta.order_number === "string" && meta.order_number) ||
    (typeof meta.product_id === "string" && meta.product_id) ||
    (typeof meta.customer_id === "number" && `#${meta.customer_id}`) ||
    null;
  const type = entry.auditable_type?.split("\\").pop() ?? null;
  if (ref && type) return `${type} ${ref}`;
  if (ref) return ref;
  if (type && entry.auditable_id) return `${type} #${entry.auditable_id}`;
  return "—";
}

function changeLabel(entry: AuditLogEntry) {
  const meta = entry.metadata ?? {};
  if (typeof meta.previous_status === "string" && typeof meta.new_status === "string") {
    return `${meta.previous_status} → ${meta.new_status}`;
  }
  if (typeof meta.decision === "string") return `decision: ${meta.decision}`;
  if (typeof meta.new_quantity === "number") return `qty → ${meta.new_quantity}`;
  return "—";
}

export default function AdminAuditPage() {
  usePageTitle("Audit Log");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const [entries, setEntries] = useState<AuditLogEntry[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [actions, setActions] = useState<string[]>([]);
  const [action, setAction] = useState("all");
  const [actorId, setActorId] = useState("");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const searchParams = useMemo(() => {
    const params = new URLSearchParams({ per_page: "50", page: String(page) });
    if (action !== "all") params.set("action", action);
    if (actorId.trim()) params.set("actor_id", actorId.trim());
    if (from) params.set("from", from);
    if (to) params.set("to", to);
    return params.toString();
  }, [action, actorId, from, to, page]);

  useEffect(() => {
    if (user?.role !== "admin") return;
    let cancelled = false;
    setLoading(true);
    setError("");
    Promise.all([
      api<AuditLogCollection>(`/admin/audit-logs?${searchParams}`),
      api<{ data: string[] }>(`/admin/audit-logs/actions`),
    ])
      .then(([logs, actionList]) => {
        if (cancelled) return;
        setEntries(logs.data);
        setMeta(logs.meta);
        setActions(actionList.data);
      })
      .catch((requestError) => {
        if (!cancelled) setError(requestError instanceof Error ? requestError.message : "Audit log could not be loaded.");
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
      <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
        <EmptyState
          icon={ShieldAlert}
          title="Admins only"
          body="The audit log is only available to store administrators."
          actionLabel="Back to account"
          actionTo="/account"
        />
      </div>
    );
  }

  const resetPage = () => setPage(1);

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Audit Log</h1>
        </div>
        <div className="flex flex-wrap gap-4">
          <Link to="/admin/products" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Inventory</Link>
          <Link to="/admin/orders" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Orders</Link>
          <Link to="/admin/customers" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Customers</Link>
          <Link to="/admin/reviews" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Reviews</Link>
          <Link to="/admin/analytics" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Analytics</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[220px_160px_160px_160px]">
        <Field label="Action" htmlFor="audit-action">
          <Select id="audit-action" value={action} onChange={(event) => { setAction(event.target.value); resetPage(); }}>
            <option value="all">All actions</option>
            {actions.map((name) => (
              <option key={name} value={name}>{name}</option>
            ))}
          </Select>
        </Field>
        <Field label="Actor ID" htmlFor="audit-actor">
          <Input id="audit-actor" inputMode="numeric" value={actorId} onChange={(event) => { setActorId(event.target.value); resetPage(); }} placeholder="e.g. 3" />
        </Field>
        <Field label="From" htmlFor="audit-from">
          <Input id="audit-from" type="date" value={from} onChange={(event) => { setFrom(event.target.value); resetPage(); }} />
        </Field>
        <Field label="To" htmlFor="audit-to">
          <Input id="audit-to" type="date" value={to} onChange={(event) => { setTo(event.target.value); resetPage(); }} />
        </Field>
      </div>

      {loading ? (
        <div className="flex justify-center py-16" role="status" aria-label="Loading audit log">
          <Spinner />
        </div>
      ) : error ? (
        <p className="mt-8 text-sm text-red-700 dark:text-red-400" role="alert">{error}</p>
      ) : entries.length === 0 ? (
        <div className="mt-8">
          <EmptyState
            icon={ScrollText}
            title="No audit events"
            body="Administrative mutations will appear here once they occur."
          />
        </div>
      ) : (
        <div className="mt-8">
          <div className="overflow-x-auto border border-line dark:border-line-dark">
            <table className="w-full min-w-[760px] text-left text-sm">
              <thead>
                <tr className="border-b border-line bg-cream text-[10px] font-bold tracking-[0.14em] uppercase text-smoke dark:border-line-dark dark:bg-nox2 dark:text-linen-dim">
                  <th scope="col" className="px-4 py-3">Time</th>
                  <th scope="col" className="px-4 py-3">Action</th>
                  <th scope="col" className="px-4 py-3">Actor</th>
                  <th scope="col" className="px-4 py-3">Target</th>
                  <th scope="col" className="px-4 py-3">Change</th>
                  <th scope="col" className="px-4 py-3">IP</th>
                </tr>
              </thead>
              <tbody>
                {entries.map((entry) => (
                  <tr key={entry.id} className="border-b border-line/60 last:border-0 dark:border-line-dark/60">
                    <td className="whitespace-nowrap px-4 py-3 text-smoke dark:text-linen-dim">{formatTime(entry.created_at)}</td>
                    <td className="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-ink dark:text-linen">{entry.action}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-smoke dark:text-linen-dim">
                      {entry.actor_id ? `#${entry.actor_id} (${entry.actor_role ?? "user"})` : "system"}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-smoke dark:text-linen-dim">{targetLabel(entry)}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-smoke dark:text-linen-dim">{changeLabel(entry)}</td>
                    <td className="whitespace-nowrap px-4 py-3 font-mono text-xs text-smoke dark:text-linen-dim">{entry.ip ?? "—"}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {meta && meta.last_page > 1 && (
            <div className="mt-4 flex items-center justify-between gap-3">
              <p className="text-xs text-smoke dark:text-linen-dim">
                Page {meta.current_page} of {meta.last_page} · {meta.total} events
              </p>
              <div className="flex gap-2">
                <Button type="button" size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage((current) => current - 1)}>
                  Previous
                </Button>
                <Button type="button" size="sm" variant="outline" disabled={page >= meta.last_page} onClick={() => setPage((current) => current + 1)}>
                  Next
                </Button>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
