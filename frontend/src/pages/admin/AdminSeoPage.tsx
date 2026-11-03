import { useEffect, useState } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { Search, ShieldAlert } from "lucide-react";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Button, EmptyState, Field, Input, Spinner, Textarea } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

export default function AdminSeoPage() {
  usePageTitle("SEO Settings"); const user = useAuthStore((s) => s.user); const location = useLocation(); const toast = useUiStore((s) => s.pushToast); const [form, setForm] = useState({ seo_title: "", seo_description: "", seo_keywords: "" }); const [loading, setLoading] = useState(true); const [saving, setSaving] = useState(false);
  useEffect(() => { if (user?.role !== "admin") return; void api<{ data: Record<string, string> }>("/admin/store-settings").then((r) => setForm({ seo_title: r.data.seo_title || "", seo_description: r.data.seo_description || "", seo_keywords: r.data.seo_keywords || "" })).finally(() => setLoading(false)); }, [user?.role]);
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />; if (user.role !== "admin") return <EmptyState icon={ShieldAlert} title="Admin access required" body="SEO settings are only available to administrators." actionLabel="Back to account" actionTo="/account" />;
  const save = async () => { setSaving(true); try { await Promise.all(Object.entries(form).map(([key, value]) => api("/admin/store-settings", { method: "PUT", body: JSON.stringify({ key, value, type: "string", is_public: true }) }))); toast("SEO settings saved"); } catch (e) { toast(e instanceof Error ? e.message : "SEO settings could not be saved"); } finally { setSaving(false); } };
  return <div className="mx-auto max-w-[900px] px-4 py-10 sm:px-6 lg:py-16"><p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin · Marketing</p><h1 className="mt-2 flex items-center gap-3 font-display text-4xl"><Search className="h-7 w-7 text-bronze" />SEO settings</h1>{loading ? <div className="flex justify-center p-16"><Spinner /></div> : <div className="mt-8 border border-line bg-cream p-6 dark:border-line-dark dark:bg-nox2"><div className="grid gap-4"><Field label="Default SEO title" htmlFor="seo-title"><Input id="seo-title" value={form.seo_title} onChange={(e) => setForm({ ...form, seo_title: e.target.value })} maxLength={70} /></Field><Field label="Default SEO description" htmlFor="seo-description"><Textarea id="seo-description" value={form.seo_description} onChange={(e) => setForm({ ...form, seo_description: e.target.value })} maxLength={160} /></Field><Field label="Keywords" htmlFor="seo-keywords"><Input id="seo-keywords" value={form.seo_keywords} onChange={(e) => setForm({ ...form, seo_keywords: e.target.value })} placeholder="clothing, essentials, fashion" /></Field><Button loading={saving} onClick={() => void save()}>Save SEO settings</Button></div></div>}</div>;
}
