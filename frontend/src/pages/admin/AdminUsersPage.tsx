import { useEffect, useState } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { ShieldAlert } from "lucide-react";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { Button, EmptyState, Select, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

type Permission = "catalog" | "commerce" | "orders" | "reports" | "support" | "marketing";
type User = { id: number; name: string; email: string; role: string; status: string; permissions?: Permission[] };
const permissions: Permission[] = ["catalog", "commerce", "orders", "reports", "support", "marketing"];

export default function AdminUsersPage() {
  usePageTitle("Staff & Roles"); const user = useAuthStore((s) => s.user); const location = useLocation(); const toast = useUiStore((s) => s.pushToast); const [users, setUsers] = useState<User[]>([]); const [loading, setLoading] = useState(true);
  const load = async () => { setLoading(true); try { const r = await api<{ data: { data: User[] } }>("/admin/users?per_page=100"); setUsers(r.data.data); } finally { setLoading(false); } };
  useEffect(() => { if (user?.role === "admin") void load(); }, [user?.role]);
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />; if (user.role !== "admin") return <EmptyState icon={ShieldAlert} title="Admin access required" body="Roles are only available to administrators." actionLabel="Back to account" actionTo="/account" />;
  const update = async (member: User, changes: { role?: string; permissions?: Permission[] }) => { try { await api("/admin/users/" + member.id, { method: "PATCH", body: JSON.stringify(changes) }); await load(); } catch (e) { toast(e instanceof Error ? e.message : "User permissions could not be updated"); } };
  const toggle = (member: User, permission: Permission) => { const current = new Set(member.permissions || []); if (current.has(permission)) current.delete(permission); else current.add(permission); void update(member, { permissions: Array.from(current) }); };
  return <div className="mx-auto max-w-[1150px] px-4 py-10 sm:px-6 lg:py-16"><p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin · Security</p><h1 className="mt-2 font-display text-4xl">Staff & roles</h1>{loading ? <div className="flex justify-center p-16"><Spinner /></div> : <div className="mt-8 overflow-x-auto border border-line dark:border-line-dark"><table className="w-full min-w-[900px] text-left text-sm"><thead className="bg-cream text-xs uppercase dark:bg-nox2"><tr><th className="p-4">User</th><th className="p-4">Role</th><th className="p-4">Status</th><th className="p-4">Permissions</th></tr></thead><tbody>{users.map((member) => <tr key={member.id} className="border-t border-line align-top dark:border-line-dark"><td className="p-4"><b>{member.name}</b><span className="block text-xs text-smoke">{member.email}</span></td><td className="p-4"><Select aria-label={"Role for " + member.email} value={member.role} disabled={member.id === user.id} onChange={(e) => void update(member, { role: e.target.value })}><option value="customer">Customer</option><option value="staff">Staff</option><option value="manager">Manager</option><option value="admin">Admin</option></Select></td><td className="p-4">{member.status}</td><td className="p-4"><div className="grid grid-cols-2 gap-2">{permissions.map((permission) => <label key={permission} className="flex items-center gap-2 text-xs capitalize"><input type="checkbox" checked={member.role === "admin" || (member.permissions || []).includes(permission)} disabled={member.role === "admin" || member.id === user.id} onChange={() => toggle(member, permission)} />{permission}</label>)}</div></td></tr>)}</tbody></table></div>}<Button className="mt-6" variant="outline" onClick={() => void load()}>Refresh</Button></div>;
}
