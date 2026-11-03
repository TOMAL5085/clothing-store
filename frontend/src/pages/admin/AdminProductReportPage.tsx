import { useState } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { Download, ShieldAlert } from "lucide-react";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";
import { Button, EmptyState, Field, Input, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

type Row = { product_name: string; orders: number; quantity: number; revenue: number };
export default function AdminProductReportPage() {
  usePageTitle("Product Sales Report"); const user = useAuthStore((s) => s.user); const location = useLocation(); const [rows, setRows] = useState<Row[]>([]); const [from, setFrom] = useState(""); const [to, setTo] = useState(""); const [loading, setLoading] = useState(false);
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />; if (user.role !== "admin") return <EmptyState icon={ShieldAlert} title="Admin access required" body="Reports are only available to administrators." actionLabel="Back to account" actionTo="/account" />;
  const load = async () => { setLoading(true); try { const q = new URLSearchParams(); if (from) q.set("from", from); if (to) q.set("to", to); const r = await api<{ data: Row[] }>("/admin/reports/order-products?" + q); setRows(r.data); } finally { setLoading(false); } };
  return <div className="mx-auto max-w-[1100px] px-4 py-10 sm:px-6 lg:py-16"><p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin · Reports</p><h1 className="mt-2 flex items-center gap-3 font-display text-4xl"><Download className="h-7 w-7 text-bronze" />Product sales report</h1><div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 sm:grid-cols-3"><Field label="From" htmlFor="product-report-from"><Input id="product-report-from" type="date" value={from} onChange={(e) => setFrom(e.target.value)} /></Field><Field label="To" htmlFor="product-report-to"><Input id="product-report-to" type="date" value={to} onChange={(e) => setTo(e.target.value)} /></Field><Button className="mt-6" onClick={() => void load()}>Apply filter</Button></div>{loading ? <div className="flex justify-center p-16"><Spinner /></div> : <div className="mt-8 overflow-x-auto border border-line dark:border-line-dark"><table className="w-full text-left text-sm"><thead className="bg-cream text-xs uppercase dark:bg-nox2"><tr><th className="p-4">Product</th><th className="p-4">Orders</th><th className="p-4">Quantity</th><th className="p-4">Revenue</th></tr></thead><tbody>{rows.map((row) => <tr key={row.product_name} className="border-t border-line dark:border-line-dark"><td className="p-4 font-semibold">{row.product_name}</td><td className="p-4">{row.orders}</td><td className="p-4">{row.quantity}</td><td className="p-4">{row.revenue.toFixed(2)}</td></tr>)}</tbody></table></div>}</div>;
}
