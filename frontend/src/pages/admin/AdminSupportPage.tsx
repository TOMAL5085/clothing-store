import { useEffect, useState } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { ShieldAlert } from "lucide-react";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Button, EmptyState, Select, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

type Ticket = { id: number; name: string; email: string; topic: string; message: string; status: string; created_at: string };
export default function AdminSupportPage() {
  usePageTitle("Support Tickets"); const user = useAuthStore((s) => s.user); const location = useLocation(); const toast = useUiStore((s) => s.pushToast); const [tickets, setTickets] = useState<Ticket[]>([]); const [loading, setLoading] = useState(true);
  const load = async () => { setLoading(true); try { const r = await api<{ data: { data: Ticket[] } }>("/admin/support/tickets?per_page=100"); setTickets(r.data.data); } finally { setLoading(false); } };
  useEffect(() => { if (user?.role === "admin") void load(); }, [user?.role]);
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />; if (user.role !== "admin") return <EmptyState icon={ShieldAlert} title="Admin access required" body="Support tickets are only available to administrators." actionLabel="Back to account" actionTo="/account" />;
  const status = async (ticket: Ticket, value: string) => { try { await api("/admin/support/tickets/" + ticket.id, { method: "PATCH", body: JSON.stringify({ status: value }) }); await load(); } catch (e) { toast(e instanceof Error ? e.message : "Ticket could not be updated"); } };
  return <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16"><p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin · Support</p><h1 className="mt-2 font-display text-4xl">Support tickets</h1>{loading ? <div className="flex justify-center p-16"><Spinner /></div> : <div className="mt-8 space-y-4">{tickets.map((ticket) => <article key={ticket.id} className="border border-line bg-cream p-5 dark:border-line-dark dark:bg-nox2"><div className="flex flex-wrap items-start justify-between gap-4"><div><h2 className="font-semibold">{ticket.topic} · {ticket.name}</h2><p className="text-xs text-smoke">{ticket.email} · {new Date(ticket.created_at).toLocaleString()}</p></div><Select aria-label="Ticket status" value={ticket.status} onChange={(e) => void status(ticket, e.target.value)}><option value="open">Open</option><option value="pending">Pending</option><option value="resolved">Resolved</option><option value="closed">Closed</option></Select></div><p className="mt-4 whitespace-pre-wrap text-sm">{ticket.message}</p></article>)}{tickets.length === 0 && <p className="text-sm text-smoke">No support tickets yet.</p>}</div>}<Button className="mt-6" variant="outline" onClick={() => void load()}>Refresh</Button></div>;
}
