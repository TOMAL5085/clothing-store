import { useEffect, useState } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { Plus, ShieldAlert, Trash2 } from "lucide-react";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Button, EmptyState, Field, Input, Select, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

type Coupon = { id: number; code: string; type: string; value: string; is_active: boolean; used_count: number; usage_limit?: number };
const empty = { code: "", type: "percent", value: "10", is_active: true, starts_at: "", expires_at: "", minimum_order_amount: "0", maximum_discount_amount: "", usage_limit: "", per_customer_limit: "" };

export default function AdminCouponsPage() {
  usePageTitle("Coupons");
  const user = useAuthStore((s) => s.user); const location = useLocation(); const toast = useUiStore((s) => s.pushToast);
  const [coupons, setCoupons] = useState<Coupon[]>([]); const [form, setForm] = useState(empty); const [loading, setLoading] = useState(true); const [saving, setSaving] = useState(false);
  const load = async () => { setLoading(true); try { const r = await api<{ data: { data: Coupon[] } }>("/admin/coupons?per_page=100"); setCoupons(r.data.data); } finally { setLoading(false); } };
  useEffect(() => { if (user?.role === "admin") void load(); }, [user?.role]);
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  if (user.role !== "admin") return <EmptyState icon={ShieldAlert} title="Admin access required" body="Coupons are only available to administrators." actionLabel="Back to account" actionTo="/account" />;
  const submit = async () => { setSaving(true); try { await api("/admin/coupons", { method: "POST", body: JSON.stringify({ ...form, starts_at: form.starts_at || null, expires_at: form.expires_at || null, maximum_discount_amount: form.maximum_discount_amount || null, usage_limit: form.usage_limit || null, per_customer_limit: form.per_customer_limit || null }) }); setForm(empty); toast("Coupon created"); await load(); } catch (e) { toast(e instanceof Error ? e.message : "Coupon could not be saved"); } finally { setSaving(false); } };
  const remove = async (coupon: Coupon) => { if (!window.confirm("Delete " + coupon.code + "?")) return; try { await api("/admin/coupons/" + coupon.id, { method: "DELETE" }); await load(); } catch (e) { toast(e instanceof Error ? e.message : "Coupon could not be deleted"); } };
  return <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16"><p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin · Commerce</p><h1 className="mt-2 font-display text-4xl">Coupons</h1><div className="mt-8 grid gap-4 border border-line bg-cream p-5 dark:border-line-dark dark:bg-nox2 sm:grid-cols-4"><Field label="Code" htmlFor="coupon-code"><Input id="coupon-code" value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })} placeholder="SUMMER10" /></Field><Field label="Type" htmlFor="coupon-type"><Select id="coupon-type" value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}><option value="percent">Percent</option><option value="fixed">Fixed</option></Select></Field><Field label="Value" htmlFor="coupon-value"><Input id="coupon-value" type="number" min="0.01" step="0.01" value={form.value} onChange={(e) => setForm({ ...form, value: e.target.value })} /></Field><Button className="mt-6" loading={saving} onClick={() => void submit()} icon={Plus}>Create coupon</Button></div>{loading ? <div className="flex justify-center p-16"><Spinner /></div> : <div className="mt-8 overflow-x-auto border border-line dark:border-line-dark"><table className="w-full text-left text-sm"><thead className="bg-cream text-xs uppercase dark:bg-nox2"><tr><th className="p-4">Code</th><th className="p-4">Discount</th><th className="p-4">Usage</th><th className="p-4">Status</th><th className="p-4" /></tr></thead><tbody>{coupons.map((coupon) => <tr key={coupon.id} className="border-t border-line dark:border-line-dark"><td className="p-4 font-semibold">{coupon.code}</td><td className="p-4">{coupon.value}{coupon.type === "percent" ? "%" : ""}</td><td className="p-4">{coupon.used_count}{coupon.usage_limit ? " / " + coupon.usage_limit : ""}</td><td className="p-4">{coupon.is_active ? "Active" : "Inactive"}</td><td className="p-4 text-right"><Button size="sm" variant="ghost" icon={Trash2} onClick={() => void remove(coupon)}>Delete</Button></td></tr>)}</tbody></table></div>}</div>;
}
