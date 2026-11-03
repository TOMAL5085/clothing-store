import { useEffect, useState } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { Megaphone, ShieldAlert } from "lucide-react";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Button, EmptyState, Select, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

type Banner = { id: number; title?: string; key?: string; placement?: string; status: string; image_url?: string };
export default function AdminAdsPage() {
  usePageTitle("Ad Banners"); const user = useAuthStore((s) => s.user); const location = useLocation(); const toast = useUiStore((s) => s.pushToast); const [banners, setBanners] = useState<Banner[]>([]); const [loading, setLoading] = useState(true);
  const load = async () => { setLoading(true); try { const r = await api<{ data: { data: Banner[] } }>("/admin/banners?status=all&per_page=100"); setBanners(r.data.data); } finally { setLoading(false); } };
  useEffect(() => { if (user?.role === "admin") void load(); }, [user?.role]);
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />; if (user.role !== "admin") return <EmptyState icon={ShieldAlert} title="Admin access required" body="Ad banners are only available to administrators." actionLabel="Back to account" actionTo="/account" />;
  const update = async (banner: Banner, placement: string) => { try { await api("/admin/banners/" + banner.id, { method: "PATCH", body: JSON.stringify({ placement }) }); await load(); } catch (e) { toast(e instanceof Error ? e.message : "Ad placement could not be updated"); } };
  return <div className="mx-auto max-w-[1100px] px-4 py-10 sm:px-6 lg:py-16"><p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin · Marketing</p><h1 className="mt-2 font-display text-4xl">Ad banner placement</h1><p className="mt-3 text-sm text-smoke">Create banners in Content, then assign them to an ad or homepage placement here.</p>{loading ? <div className="flex justify-center p-16"><Spinner /></div> : <div className="mt-8 space-y-3">{banners.map((banner) => <div key={banner.id} className="flex flex-wrap items-center justify-between gap-4 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2"><div className="flex items-center gap-3"><Megaphone className="h-5 w-5 text-bronze" /><div><b>{banner.title || banner.key || "Untitled banner"}</b><p className="text-xs text-smoke">{banner.status}</p></div></div><Select aria-label="Banner placement" value={banner.placement || "hero"} onChange={(e) => void update(banner, e.target.value)}><option value="hero">Hero slider</option><option value="ad">Advertisement</option><option value="homepage">Homepage</option><option value="sidebar">Sidebar</option></Select></div>)}</div>}<Button className="mt-6" variant="outline" onClick={() => void load()}>Refresh</Button></div>;
}
