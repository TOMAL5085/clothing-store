import { useEffect, useState } from "react";
import { Navigate, useLocation, useParams } from "react-router-dom";
import { ArrowLeft, Printer, ShieldAlert } from "lucide-react";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";
import { Button, EmptyState, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

type Invoice = { number: string; created_at: string; currency?: string | null; subtotal: string; discount: string; shipping: string; tax: string; total: string; user?: { name: string; email: string } | null; items?: { id: number; product_name: string; quantity: number; unit_price: string; line_total: string }[]; shipping_address?: { first_name?: string; last_name?: string; address?: string; city?: string; postal_code?: string; country?: string } | null };

export default function AdminInvoicePage() {
  const { orderNumber } = useParams<{ orderNumber: string }>();
  const user = useAuthStore((state) => state.user);
  const location = useLocation();
  const [invoice, setInvoice] = useState<Invoice | null>(null);
  const [loading, setLoading] = useState(true);
  usePageTitle(invoice ? `Invoice ${invoice.number}` : "Invoice");
  useEffect(() => { if (user?.role !== "admin" || !orderNumber) return; api<{ data: Invoice }>(`/admin/orders/${encodeURIComponent(orderNumber)}/invoice`).then((response) => setInvoice(response.data)).finally(() => setLoading(false)); }, [orderNumber, user?.role]);
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  if (user.role !== "admin") return <div className="mx-auto max-w-3xl px-4 py-12"><EmptyState icon={ShieldAlert} title="Admin access required" body="Invoices are only available to store administrators." actionLabel="Back to account" actionTo="/account" /></div>;
  if (loading) return <div className="flex min-h-[60vh] items-center justify-center"><Spinner /></div>;
  if (!invoice) return <div className="mx-auto max-w-3xl px-4 py-12"><EmptyState icon={ShieldAlert} title="Invoice not found" body="The requested order invoice could not be loaded." actionLabel="Back to orders" actionTo="/admin/orders" /></div>;
  return <div className="mx-auto max-w-3xl px-4 py-10 print:max-w-none print:px-0"><div className="mb-6 flex justify-between print:hidden"><Button variant="outline" onClick={() => window.history.back()} icon={ArrowLeft}>Back</Button><Button onClick={() => window.print()} icon={Printer}>Print invoice</Button></div><article className="border border-line bg-white p-8 text-ink shadow-sm print:border-0 print:shadow-none"><div className="flex justify-between gap-6 border-b border-line pb-8"><div><p className="text-xs font-bold tracking-[0.25em] uppercase text-bronze">JAAJ</p><h1 className="mt-3 font-display text-4xl">Invoice</h1></div><div className="text-right text-sm"><p className="font-bold">#{invoice.number}</p><p className="mt-1 text-smoke">{new Date(invoice.created_at).toLocaleDateString()}</p></div></div><div className="grid gap-6 border-b border-line py-6 sm:grid-cols-2"><div><p className="text-[10px] font-bold tracking-[0.16em] uppercase text-smoke">Bill to</p><p className="mt-2 font-semibold">{invoice.user?.name ?? "Guest customer"}</p><p className="text-smoke">{invoice.user?.email}</p></div><div><p className="text-[10px] font-bold tracking-[0.16em] uppercase text-smoke">Ship to</p><p className="mt-2 text-sm">{invoice.shipping_address ? `${invoice.shipping_address.first_name ?? ""} ${invoice.shipping_address.last_name ?? ""}` : "—"}<br />{invoice.shipping_address?.address}<br />{invoice.shipping_address?.city} {invoice.shipping_address?.postal_code}<br />{invoice.shipping_address?.country}</p></div></div><table className="mt-8 w-full text-left text-sm"><thead className="border-b border-line text-[10px] font-bold tracking-[0.14em] uppercase text-smoke"><tr><th className="pb-3">Item</th><th className="pb-3 text-center">Qty</th><th className="pb-3 text-right">Amount</th></tr></thead><tbody>{invoice.items?.map((item) => <tr key={item.id} className="border-b border-line"><td className="py-4">{item.product_name}</td><td className="py-4 text-center">{item.quantity}</td><td className="py-4 text-right">{item.line_total}</td></tr>)}</tbody></table><div className="ml-auto mt-8 max-w-xs space-y-2 text-sm"><Total label="Subtotal" value={invoice.subtotal} /><Total label="Discount" value={`-${invoice.discount}`} /><Total label="Shipping" value={invoice.shipping} /><Total label="Tax" value={invoice.tax} /><Total label="Total" value={invoice.total} strong /></div></article></div>;
}

function Total({ label, value, strong }: { label: string; value: string; strong?: boolean }) { return <div className={`flex justify-between ${strong ? "border-t border-line pt-3 text-base font-bold" : "text-smoke"}`}><span>{label}</span><span>{value}</span></div>; }
