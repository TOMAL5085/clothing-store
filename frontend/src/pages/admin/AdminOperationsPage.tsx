import { useEffect, useState } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { BarChart3, Mail, RefreshCcw, Search, ShieldAlert, Truck, Users } from "lucide-react";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Button, EmptyState, Input, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

type Order = { id: number; number: string; status: string; payment_status: string; total: string; created_at: string; invoice_emailed_at?: string | null; assignments?: { staff?: { name: string } }[] };
type Report = { data: Order[]; meta?: { total: number } };
type Summary = { data: { daily: number; monthly: number } };

export default function AdminOperationsPage() {
  usePageTitle("Operations");
  const user = useAuthStore((state) => state.user);
  const location = useLocation();
  const pushToast = useUiStore((state) => state.pushToast);
  const [orders, setOrders] = useState<Order[]>([]);
  const [summary, setSummary] = useState({ daily: 0, monthly: 0 });
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(true);

  const reload = async () => {
    setLoading(true);
    try {
      const query = search ? `?search=${encodeURIComponent(search)}&per_page=50` : "?per_page=50";
      const [report, visitors] = await Promise.all([api<Report>(`/admin/reports/orders${query}`), api<Summary>("/admin/visitor-summary")]);
      setOrders(report.data); setSummary(visitors.data);
    } catch { setOrders([]); } finally { setLoading(false); }
  };
  useEffect(() => { if (user?.role === "admin") void reload(); }, [user?.role]);

  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  if (user.role !== "admin") return <div className="mx-auto max-w-3xl px-4 py-12"><EmptyState icon={ShieldAlert} title="Admin access required" body="Operations are only available to store administrators." actionLabel="Back to account" actionTo="/account" /></div>;

  const invoice = async (order: Order) => { try { await api(`/admin/orders/${order.number}/invoice/email`, { method: "POST" }); pushToast("Invoice queued"); await reload(); } catch (e) { pushToast(e instanceof Error ? e.message : "Could not queue invoice"); } };
  const fraud = async (order: Order) => { try { await api(`/admin/orders/${order.number}/fraud-check`, { method: "POST" }); pushToast("Fraud check completed"); } catch { pushToast("Fraud check failed"); } };

  return <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
    <div className="flex flex-wrap items-end justify-between gap-4"><div><p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin · Phases C–G</p><h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Operations center</h1><p className="mt-3 max-w-2xl text-sm text-smoke dark:text-linen-dim">Finance, wholesale, fulfillment, courier, marketing and order recovery tools in one place.</p></div><Button variant="outline" onClick={() => void reload()}><RefreshCcw className="h-4 w-4" /> Refresh</Button></div>
    <div className="mt-8 grid gap-4 sm:grid-cols-3"><Metric icon={Users} label="Unique visitors today" value={summary.daily} /><Metric icon={BarChart3} label="Unique visitors this month" value={summary.monthly} /><Metric icon={Truck} label="Orders in report" value={orders.length} /></div>
    <div className="mt-10 flex gap-3"><div className="relative flex-1"><Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-smoke" /><Input value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => { if (e.key === "Enter") void reload(); }} placeholder="Search order number" className="pl-10" /></div><Button onClick={() => void reload()}>Search</Button></div>
    <div className="mt-5 overflow-x-auto border border-line dark:border-line-dark">{loading ? <div className="flex justify-center p-12"><Spinner /></div> : <table className="w-full min-w-[760px] text-left text-sm"><thead className="bg-cream text-[11px] font-bold tracking-[0.16em] uppercase dark:bg-nox2"><tr><th className="p-4">Order</th><th className="p-4">Status</th><th className="p-4">Payment</th><th className="p-4">Total</th><th className="p-4">Actions</th></tr></thead><tbody>{orders.map((order) => <tr key={order.id} className="border-t border-line dark:border-line-dark"><td className="p-4 font-bold">{order.number}<span className="block text-xs font-normal text-smoke">{new Date(order.created_at).toLocaleDateString()}</span></td><td className="p-4 capitalize">{order.status}</td><td className="p-4 capitalize">{order.payment_status}</td><td className="p-4">{order.total}</td><td className="p-4"><div className="flex flex-wrap gap-2"><Button size="sm" variant="outline" onClick={() => void invoice(order)}><Mail className="h-3.5 w-3.5" /> Invoice</Button><Button size="sm" variant="outline" onClick={() => void fraud(order)}><ShieldAlert className="h-3.5 w-3.5" /> Fraud check</Button></div></td></tr>)}</tbody></table>}</div>
  </div>;
}

function Metric({ icon: Icon, label, value }: { icon: typeof Users; label: string; value: number }) { return <div className="border border-line bg-cream p-5 dark:border-line-dark dark:bg-nox2"><Icon className="h-5 w-5 text-bronze" /><p className="mt-4 text-xs font-bold tracking-[0.14em] uppercase text-smoke">{label}</p><p className="mt-1 font-display text-3xl">{value}</p></div>; }
