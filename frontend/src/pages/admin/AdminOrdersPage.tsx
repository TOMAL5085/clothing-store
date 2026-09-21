import { useEffect, useMemo, useState } from "react";
import { Link, Navigate, useLocation } from "react-router-dom";
import { CheckCircle, Package, RefreshCcw, Search, ShieldAlert, Truck, XCircle, RotateCcw } from "lucide-react";
import { api } from "@/lib/api";
import type { PaginationMeta } from "@/store/catalogStore";
import { useAuthStore } from "@/store/authStore";
import { useUiStore } from "@/store/uiStore";
import { useCurrencyStore } from "@/store/currencyStore";
import { Button, EmptyState, Field, Input, Select, Spinner } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

interface AdminOrderLine {
  id: string;
  productId: string;
  productName: string;
  productSlug: string;
  size: string;
  qty: number;
  unitPrice: number;
  lineTotal: number;
}

interface AdminOrderShipmentEvent {
  id: number;
  status: string;
  statusCode: string;
  location: string | null;
  description: string | null;
  occurredAt: string | null;
}

interface AdminOrderShipment {
  id: number;
  status: string;
  statusCode: string;
  carrier: string | null;
  trackingNumber: string | null;
  trackingReference: string | null;
  shippingFee: number | null;
  estimatedDeliveryAt: string | null;
  shippedAt: string | null;
  deliveredAt: string | null;
  events?: AdminOrderShipmentEvent[];
}

interface AdminRefund {
  id: number;
  status: string;
  provider: string | null;
  amount: number;
  currency: string;
  reason: string;
  failureReason?: string | null;
  requestedAt?: string | null;
  processedAt?: string | null;
}

interface AdminCancellation {
  id: number;
  orderId: string;
  status: string;
  reason: string;
  adminReason?: string | null;
  requestedAt?: string | null;
  refund?: AdminRefund | null;
}

interface AdminReturnItem {
  id: number;
  orderItemId: number;
  productName?: string | null;
  productId?: string | null;
  size?: string | null;
  quantity: number;
  resolutionStatus: string;
}

interface AdminReturn {
  id: number;
  orderId: string;
  status: string;
  reason: string;
  adminReason?: string | null;
  requestedAt?: string | null;
  receivedAt?: string | null;
  items?: AdminReturnItem[];
  refund?: AdminRefund | null;
}

interface AdminOrder {
  id: string;
  status: string;
  statusCode: string;
  paymentStatus: string;
  paymentProvider: string;
  countryCode: string;
  currency: string;
  method: string;
  subtotal: number;
  discount: number;
  shipping: number;
  tax: number;
  total: number;
  promoCode: string | null;
  createdAt: string;
  customer: { id: number; name: string; email: string } | null;
  address: {
    firstName: string | null;
    lastName: string | null;
    email: string | null;
    address: string | null;
    city: string | null;
    postalCode: string | null;
    country: string | null;
  };
  payment: { provider: string; status: string; reference: string; amount: number; currency: string; method: string } | null;
  shipment: AdminOrderShipment | null;
  cancellation?: AdminCancellation | null;
  returns?: AdminReturn[];
  refunds?: AdminRefund[];
  lines: AdminOrderLine[];
}

interface OrderCollection {
  data: AdminOrder[];
  meta?: PaginationMeta;
}

interface OrderResponse {
  data: AdminOrder;
}

const STATUSES = ["pending", "confirmed", "processing", "shipped", "delivered", "cancelled"] as const;

const ORDER_STATUS_TONE: Record<string, string> = {
  pending: "bg-bronze/15 text-bronze",
  confirmed: "bg-sky-800/15 text-sky-800 dark:text-sky-300",
  processing: "bg-ink text-paper dark:bg-linen dark:text-nox",
  shipped: "bg-violet-800/15 text-violet-800 dark:text-violet-300",
  delivered: "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300",
  cancelled: "bg-red-800/10 text-red-800 dark:text-red-300",
};

function paymentTone(status: string) {
  if (status === "paid") return "bg-emerald-800/15 text-emerald-800 dark:text-emerald-300";
  if (status === "failed" || status === "canceled") return "bg-red-800/10 text-red-800 dark:text-red-300";
  return "bg-bronze/15 text-bronze";
}

export default function AdminOrdersPage() {
  usePageTitle("Orders");
  const location = useLocation();
  const user = useAuthStore((state) => state.user);
  const pushToast = useUiStore((state) => state.pushToast);
  const format = useCurrencyStore((state) => state.format);
  const [orders, setOrders] = useState<AdminOrder[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | undefined>();
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("all");
  const [paymentStatus, setPaymentStatus] = useState("all");
  const [loading, setLoading] = useState(true);
  const [savingId, setSavingId] = useState<string | undefined>();
  const [nextStatus, setNextStatus] = useState<Record<string, string>>({});
  const [error, setError] = useState("");
  const [showCreateShipment, setShowCreateShipment] = useState<string | null>(null);

  const searchParams = useMemo(() => {
    const params = new URLSearchParams({ status, payment_status: paymentStatus, per_page: "100" });
    if (query.trim()) params.set("q", query.trim());
    return params.toString();
  }, [query, status, paymentStatus]);

  useEffect(() => {
    if (user?.role !== "admin") return;
    let cancelled = false;
    setLoading(true);
    setError("");
    api<OrderCollection>(`/admin/orders?${searchParams}`)
      .then((response) => {
        if (cancelled) return;
        setOrders(response.data);
        setMeta(response.meta);
        setNextStatus(Object.fromEntries(response.data.map((order) => [order.id, order.statusCode])));
      })
      .catch((requestError) => {
        if (!cancelled) setError(requestError instanceof Error ? requestError.message : "Orders could not be loaded.");
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
        <EmptyState icon={ShieldAlert} title="Admin access required" body="Order management is only available to store administrators." actionLabel="Back to account" actionTo="/account" />
      </div>
    );
  }

  const updateStatus = async (order: AdminOrder) => {
    const target = nextStatus[order.id];
    if (!target || target === order.statusCode) return;
    setSavingId(order.id);
    try {
      const response = await api<OrderResponse>(`/admin/orders/${encodeURIComponent(order.id)}/status`, {
        method: "PATCH",
        body: JSON.stringify({ status: target }),
      });
      setOrders((current) => current.map((item) => (item.id === order.id ? response.data : item)));
      pushToast("Order status updated");
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Order update failed");
    } finally {
      setSavingId(undefined);
    }
  };

  const refreshOrders = async () => {
    const response = await api<OrderCollection>(`/admin/orders?${searchParams}`);
    setOrders(response.data);
    setMeta(response.meta);
    setNextStatus(Object.fromEntries(response.data.map((order) => [order.id, order.statusCode])));
  };

  const reviewCancellation = async (id: number, decision: "approved" | "rejected") => {
    setSavingId(`cancel-${id}`);
    try {
      await api(`/admin/cancellations/${id}`, {
        method: "PATCH",
        body: JSON.stringify({ decision }),
      });
      await refreshOrders();
      pushToast(decision === "approved" ? "Cancellation approved" : "Cancellation rejected");
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Cancellation review failed");
    } finally {
      setSavingId(undefined);
    }
  };

  const reviewReturn = async (id: number, decision: "approved" | "rejected") => {
    setSavingId(`return-${id}`);
    try {
      await api(`/admin/returns/${id}`, {
        method: "PATCH",
        body: JSON.stringify({ decision }),
      });
      await refreshOrders();
      pushToast(decision === "approved" ? "Return approved" : "Return rejected");
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Return review failed");
    } finally {
      setSavingId(undefined);
    }
  };

  const markReturnReceived = async (id: number) => {
    setSavingId(`received-${id}`);
    try {
      await api(`/admin/returns/${id}/received`, { method: "POST", body: JSON.stringify({}) });
      await refreshOrders();
      pushToast("Return marked received");
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Return update failed");
    } finally {
      setSavingId(undefined);
    }
  };

  const syncCourierStatus = async (orderId: string) => {
    setSavingId(`sync-${orderId}`);
    try {
      await api(`/admin/orders/${encodeURIComponent(orderId)}/shipment/status/sync`, { method: "POST" });
      await refreshOrders();
      pushToast("Courier status synced");
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Courier sync failed");
    } finally {
      setSavingId(undefined);
    }
  };

  const updateRefund = async (id: number, status: "processing" | "succeeded" | "failed" | "canceled") => {
    setSavingId(`refund-${id}`);
    try {
      await api(`/admin/refunds/${id}`, {
        method: "PATCH",
        body: JSON.stringify({ status }),
      });
      await refreshOrders();
      pushToast("Refund updated");
    } catch (requestError) {
      pushToast(requestError instanceof Error ? requestError.message : "Refund update failed");
    } finally {
      setSavingId(undefined);
    }
  };

  interface CreateShipmentFormProps {
    orderId: string;
    onClose: () => void;
    onSuccess: () => void;
  }

  function CreateShipmentForm({ orderId, onClose, onSuccess }: CreateShipmentFormProps) {
    const [carrier, setCarrier] = useState("");
    const [trackingNumber, setTrackingNumber] = useState("");
    const [trackingReference, setTrackingReference] = useState("");
    const [shippingFee, setShippingFee] = useState("");
    const [estimatedDelivery, setEstimatedDelivery] = useState("");
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState("");

    const handleSubmit = async (e: React.FormEvent) => {
      e.preventDefault();
      setBusy(true);
      setError("");
      try {
        await api(`/admin/orders/${encodeURIComponent(orderId)}/shipment`, {
          method: "POST",
          body: JSON.stringify({
            carrier: carrier || undefined,
            tracking_number: trackingNumber || undefined,
            tracking_reference: trackingReference || undefined,
            shipping_fee: shippingFee ? parseFloat(shippingFee) : undefined,
            estimated_delivery_at: estimatedDelivery || undefined,
          }),
        });
        pushToast("Shipment created");
        onSuccess();
      } catch (requestError) {
        setError(requestError instanceof Error ? requestError.message : "Failed to create shipment");
      } finally {
        setBusy(false);
      }
    };

    return (
      <form onSubmit={handleSubmit} className="space-y-4">
        <Field label="Carrier" htmlFor="shipment-carrier">
          <Input id="shipment-carrier" value={carrier} onChange={(e) => setCarrier(e.target.value)} placeholder="e.g., DHL, FedEx, UPS" />
        </Field>
        <Field label="Tracking Number" htmlFor="shipment-tracking">
          <Input id="shipment-tracking" value={trackingNumber} onChange={(e) => setTrackingNumber(e.target.value)} placeholder="e.g., 1234567890" />
        </Field>
        <Field label="Tracking Reference" htmlFor="shipment-ref">
          <Input id="shipment-ref" value={trackingReference} onChange={(e) => setTrackingReference(e.target.value)} placeholder="Internal reference" />
        </Field>
        <Field label="Shipping Fee" htmlFor="shipment-fee">
          <Input id="shipment-fee" type="number" step="0.01" value={shippingFee} onChange={(e) => setShippingFee(e.target.value)} placeholder="0.00" />
        </Field>
        <Field label="Estimated Delivery" htmlFor="shipment-est-delivery">
          <Input id="shipment-est-delivery" type="date" value={estimatedDelivery} onChange={(e) => setEstimatedDelivery(e.target.value)} />
        </Field>
        {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
        <div className="flex flex-wrap gap-2">
          <Button type="submit" icon={Truck} loading={busy}>Create Shipment</Button>
          <Button type="button" variant="outline" onClick={onClose}>Cancel</Button>
        </div>
      </form>
    );
  }

  interface UpdateShipmentFormProps {
    order: AdminOrder;
    onClose: () => void;
  }

  function UpdateShipmentForm({ order, onClose }: UpdateShipmentFormProps) {
    const [status, setStatus] = useState(order.shipment?.statusCode ?? "pending");
    const [carrier, setCarrier] = useState(order.shipment?.carrier ?? "");
    const [trackingNumber, setTrackingNumber] = useState(order.shipment?.trackingNumber ?? "");
    const [trackingReference, setTrackingReference] = useState(order.shipment?.trackingReference ?? "");
    const [shippingFee, setShippingFee] = useState(order.shipment?.shippingFee?.toString() ?? "");
    const [estimatedDelivery, setEstimatedDelivery] = useState(
      order.shipment?.estimatedDeliveryAt ? order.shipment.estimatedDeliveryAt.split("T")[0] : ""
    );
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState("");

    const SHIPMENT_STATUSES = ["pending", "processing", "ready_to_ship", "shipped", "in_transit", "out_for_delivery", "delivered", "failed_delivery"] as const;

    const handleSubmit = async (e: React.FormEvent) => {
      e.preventDefault();
      setBusy(true);
      setError("");
      try {
        await api(`/admin/orders/${encodeURIComponent(order.id)}/shipment`, {
          method: "PUT",
          body: JSON.stringify({
            status,
            carrier: carrier || undefined,
            tracking_number: trackingNumber || undefined,
            tracking_reference: trackingReference || undefined,
            shipping_fee: shippingFee ? parseFloat(shippingFee) : undefined,
            estimated_delivery_at: estimatedDelivery || undefined,
          }),
        });
        pushToast("Shipment updated");
        onClose();
      } catch (requestError) {
        setError(requestError instanceof Error ? requestError.message : "Failed to update shipment");
      } finally {
        setBusy(false);
      }
    };

    return (
      <form onSubmit={handleSubmit} className="space-y-4">
        <Field label="Status" htmlFor="shipment-status-update">
          <Select id="shipment-status-update" value={status} onChange={(e) => setStatus(e.target.value)}>
            {SHIPMENT_STATUSES.map((s) => <option key={s} value={s}>{s.replace("_", " ")}</option>)}
          </Select>
        </Field>
        <Field label="Carrier" htmlFor="shipment-carrier-update">
          <Input id="shipment-carrier-update" value={carrier} onChange={(e) => setCarrier(e.target.value)} placeholder="e.g., DHL, FedEx, UPS" />
        </Field>
        <Field label="Tracking Number" htmlFor="shipment-tracking-update">
          <Input id="shipment-tracking-update" value={trackingNumber} onChange={(e) => setTrackingNumber(e.target.value)} placeholder="e.g., 1234567890" />
        </Field>
        <Field label="Tracking Reference" htmlFor="shipment-ref-update">
          <Input id="shipment-ref-update" value={trackingReference} onChange={(e) => setTrackingReference(e.target.value)} placeholder="Internal reference" />
        </Field>
        <Field label="Shipping Fee" htmlFor="shipment-fee-update">
          <Input id="shipment-fee-update" type="number" step="0.01" value={shippingFee} onChange={(e) => setShippingFee(e.target.value)} placeholder="0.00" />
        </Field>
        <Field label="Estimated Delivery" htmlFor="shipment-est-delivery-update">
          <Input id="shipment-est-delivery-update" type="date" value={estimatedDelivery} onChange={(e) => setEstimatedDelivery(e.target.value)} />
        </Field>
        {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
        <div className="flex flex-wrap gap-2">
          <Button type="submit" icon={Truck} loading={busy}>Update Shipment</Button>
        </div>
      </form>
    );
  }

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Admin</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Orders</h1>
        </div>
        <div className="flex gap-4">
          <Link to="/admin/products" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Inventory</Link>
          <Link to="/admin/customers" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Customers</Link>
          <Link to="/account" className="text-xs font-bold tracking-[0.16em] uppercase text-smoke transition-colors hover:text-bronze dark:text-linen-dim">Account</Link>
        </div>
      </div>

      <div className="mt-8 grid gap-3 border border-line bg-cream p-4 dark:border-line-dark dark:bg-nox2 md:grid-cols-[1fr_180px_180px_auto]">
        <Field label="Search orders" htmlFor="admin-order-search">
          <div className="relative">
            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-smoke" aria-hidden />
            <Input id="admin-order-search" value={query} onChange={(event) => setQuery(event.target.value)} className="pl-10" placeholder="Order #, promo, customer" />
          </div>
        </Field>
        <Field label="Status" htmlFor="admin-order-status">
          <Select id="admin-order-status" value={status} onChange={(event) => setStatus(event.target.value)}>
            <option value="all">All</option>
            {STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
          </Select>
        </Field>
        <Field label="Payment" htmlFor="admin-order-payment">
          <Select id="admin-order-payment" value={paymentStatus} onChange={(event) => setPaymentStatus(event.target.value)}>
            <option value="all">All</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="failed">Failed</option>
            <option value="canceled">Canceled</option>
          </Select>
        </Field>
        <Button type="button" variant="outline" icon={RefreshCcw} className="self-end" onClick={() => setQuery((current) => current.trim())}>Refresh</Button>
      </div>

      <div className="mt-8">
        {loading && <div className="flex min-h-60 items-center justify-center"><Spinner /></div>}
        {!loading && error && <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2"><EmptyState icon={Package} title="Orders unavailable" body={error} /></div>}
        {!loading && !error && orders.length === 0 && <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2"><EmptyState icon={Package} title="No orders found" body="Try a different search or filter." /></div>}
        {!loading && !error && orders.length > 0 && (
          <div className="space-y-4">
            <p className="text-xs text-smoke dark:text-linen-dim">Showing {meta?.total ?? orders.length} orders.</p>
            {orders.map((order) => (
              <section key={order.id} className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
                <details className="group">
                  <summary className="flex cursor-pointer list-none flex-wrap items-center justify-between gap-4 p-5 [&::-webkit-details-marker]:hidden">
                    <div className="flex flex-wrap items-center gap-x-8 gap-y-2">
                      <div>
                        <p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Order</p>
                        <p className="font-semibold text-ink dark:text-linen">{order.id}</p>
                      </div>
                      <div>
                        <p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Customer</p>
                        <p className="text-sm text-ink dark:text-linen">{order.customer?.email ?? "Guest"}</p>
                      </div>
                      <div>
                        <p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Placed</p>
                        <p className="text-sm text-ink dark:text-linen">{new Date(order.createdAt).toLocaleDateString("en-US", { day: "numeric", month: "short", year: "numeric" })}</p>
                      </div>
                      <div>
                        <p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Total</p>
                        <p className="font-semibold text-ink dark:text-linen">{format(order.total)}</p>
                      </div>
                      <div>
                        <p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Provider</p>
                        <p className="text-sm text-ink dark:text-linen">{order.paymentProvider || "—"}</p>
                      </div>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                      <span className={`px-3 py-1 text-[10px] font-bold tracking-[0.14em] uppercase ${ORDER_STATUS_TONE[order.statusCode] ?? "bg-ink text-paper"}`}>{order.status}</span>
                      <span className={`px-3 py-1 text-[10px] font-bold tracking-[0.14em] uppercase ${paymentTone(order.paymentStatus)}`}>{order.paymentStatus}</span>
                    </div>
                  </summary>
                  <div className="grid gap-6 border-t border-line px-5 py-5 dark:border-line-dark lg:grid-cols-[1fr_260px]">
                    <div>
                      <h2 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Items</h2>
                      <ul className="mt-3 space-y-3">
                        {order.lines.map((line) => (
                          <li key={line.id} className="flex items-center justify-between gap-3 text-sm">
                            <span className="min-w-0 flex-1 truncate font-semibold text-ink dark:text-linen">{line.productName}</span>
                            <span className="shrink-0 text-xs text-smoke dark:text-linen-dim">Size {line.size} x {line.qty}</span>
                            <span className="shrink-0 font-semibold text-ink dark:text-linen">{format(line.lineTotal)}</span>
                          </li>
                        ))}
                      </ul>
                      <dl className="mt-4 max-w-xs space-y-2 border-t border-line pt-4 text-sm dark:border-line-dark">
                        <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Subtotal</dt><dd className="font-semibold text-ink dark:text-linen">{format(order.subtotal)}</dd></div>
                        {order.discount > 0 && <div className="flex justify-between text-bronze"><dt>Discount</dt><dd className="font-semibold">−{format(order.discount)}</dd></div>}
                        <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Shipping ({order.method})</dt><dd className="font-semibold text-ink dark:text-linen">{format(order.shipping)}</dd></div>
                        <div className="flex justify-between border-t border-line pt-2 dark:border-line-dark"><dt className="font-semibold text-ink dark:text-linen">Total</dt><dd className="font-semibold text-ink dark:text-linen">{format(order.total)}</dd></div>
                      </dl>
                    </div>
                    <div className="space-y-4">
                      <div>
                        <h2 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Ship to</h2>
                        <p className="mt-2 text-sm leading-relaxed text-smoke dark:text-linen-dim">
                          {order.address.firstName} {order.address.lastName}<br />
                          {order.address.address}, {order.address.city} {order.address.postalCode}<br />
                          {order.address.country}
                        </p>
                      </div>
                      {order.payment && (
                        <div>
                          <h2 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Payment</h2>
                          <p className="mt-2 text-sm text-smoke dark:text-linen-dim">
                            {order.payment.provider} · {order.payment.method ?? "—"} · {order.payment.reference}
                          </p>
                        </div>
                      )}
                      {(order.cancellation || (order.returns && order.returns.length > 0) || (order.refunds && order.refunds.length > 0)) && (
                        <div className="border-t border-line pt-4 dark:border-line-dark">
                          <h2 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Resolution</h2>
                          {order.cancellation && (
                            <div className="mt-3 space-y-2 text-sm">
                              <div className="flex justify-between gap-3">
                                <span className="text-smoke dark:text-linen-dim">Cancellation</span>
                                <span className="font-semibold capitalize text-ink dark:text-linen">{order.cancellation.status}</span>
                              </div>
                              <p className="text-smoke dark:text-linen-dim">{order.cancellation.reason}</p>
                              {order.cancellation.status === "pending" && (
                                <div className="flex flex-wrap gap-2">
                                  <Button type="button" size="sm" variant="outline" icon={CheckCircle} loading={savingId === `cancel-${order.cancellation.id}`} onClick={() => void reviewCancellation(order.cancellation!.id, "approved")}>Approve</Button>
                                  <Button type="button" size="sm" variant="ghost" icon={XCircle} loading={savingId === `cancel-${order.cancellation.id}`} onClick={() => void reviewCancellation(order.cancellation!.id, "rejected")}>Reject</Button>
                                </div>
                              )}
                              {order.cancellation.refund && <p className="text-smoke dark:text-linen-dim">Refund: {order.cancellation.refund.status} · {format(order.cancellation.refund.amount)}</p>}
                            </div>
                          )}
                          {order.returns?.map((entry) => (
                            <div key={entry.id} className="mt-4 space-y-2 border-t border-line pt-3 text-sm dark:border-line-dark">
                              <div className="flex justify-between gap-3">
                                <span className="text-smoke dark:text-linen-dim">Return #{entry.id}</span>
                                <span className="font-semibold capitalize text-ink dark:text-linen">{entry.status}</span>
                              </div>
                              <p className="text-smoke dark:text-linen-dim">{entry.reason}</p>
                              {entry.items?.map((item) => (
                                <p key={item.id} className="text-xs text-smoke dark:text-linen-dim">{item.productName} · Qty {item.quantity} · {item.resolutionStatus}</p>
                              ))}
                              {entry.status === "pending" && (
                                <div className="flex flex-wrap gap-2">
                                  <Button type="button" size="sm" variant="outline" icon={CheckCircle} loading={savingId === `return-${entry.id}`} onClick={() => void reviewReturn(entry.id, "approved")}>Approve</Button>
                                  <Button type="button" size="sm" variant="ghost" icon={XCircle} loading={savingId === `return-${entry.id}`} onClick={() => void reviewReturn(entry.id, "rejected")}>Reject</Button>
                                </div>
                              )}
                              {entry.status === "approved" && (
                                <Button type="button" size="sm" variant="outline" icon={Package} loading={savingId === `received-${entry.id}`} onClick={() => void markReturnReceived(entry.id)}>Mark Received</Button>
                              )}
                              {entry.refund && <p className="text-smoke dark:text-linen-dim">Refund: {entry.refund.status} · {format(entry.refund.amount)}</p>}
                            </div>
                          ))}
                          {order.refunds?.map((refund) => (
                            <div key={refund.id} className="mt-4 space-y-2 border-t border-line pt-3 text-sm dark:border-line-dark">
                              <div className="flex justify-between gap-3">
                                <span className="text-smoke dark:text-linen-dim">Refund #{refund.id}</span>
                                <span className="font-semibold capitalize text-ink dark:text-linen">{refund.status}</span>
                              </div>
                              <p className="text-smoke dark:text-linen-dim">{refund.reason} · {format(refund.amount)}</p>
                              {!["succeeded", "canceled"].includes(refund.status) && (
                                <div className="flex flex-wrap gap-2">
                                  <Button type="button" size="sm" variant="outline" loading={savingId === `refund-${refund.id}`} onClick={() => void updateRefund(refund.id, "processing")}>Processing</Button>
                                  <Button type="button" size="sm" variant="outline" loading={savingId === `refund-${refund.id}`} onClick={() => void updateRefund(refund.id, "succeeded")}>Succeeded</Button>
                                  <Button type="button" size="sm" variant="ghost" loading={savingId === `refund-${refund.id}`} onClick={() => void updateRefund(refund.id, "failed")}>Failed</Button>
                                </div>
                              )}
                            </div>
                          ))}
                        </div>
                      )}
                      {order.shipment && (
                        <div>
                          <h2 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen flex items-center gap-2"><Truck className="h-4 w-4" /> Shipping</h2>
                          <dl className="mt-2 space-y-2 text-sm">
                            <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Status</dt><dd className="font-semibold capitalize">{order.shipment.status}</dd></div>
                            {order.shipment.carrier && <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Carrier</dt><dd className="font-semibold text-ink dark:text-linen">{order.shipment.carrier}</dd></div>}
                            {order.shipment.trackingNumber && <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Tracking</dt><dd className="font-semibold text-ink dark:text-linen">{order.shipment.trackingNumber}</dd></div>}
                            {order.shipment.trackingReference && <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Ref</dt><dd className="font-semibold text-ink dark:text-linen">{order.shipment.trackingReference}</dd></div>}
                            {order.shipment.shippingFee !== null && order.shipment.shippingFee > 0 && <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Fee</dt><dd className="font-semibold text-ink dark:text-linen">{format(order.shipment.shippingFee)}</dd></div>}
                            {order.shipment.estimatedDeliveryAt && <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Est. Delivery</dt><dd className="font-semibold text-ink dark:text-linen">{new Date(order.shipment.estimatedDeliveryAt).toLocaleDateString()}</dd></div>}
                            {order.shipment.shippedAt && <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Shipped</dt><dd className="font-semibold text-ink dark:text-linen">{new Date(order.shipment.shippedAt).toLocaleDateString()}</dd></div>}
                            {order.shipment.deliveredAt && <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Delivered</dt><dd className="font-semibold text-ink dark:text-linen">{new Date(order.shipment.deliveredAt).toLocaleDateString()}</dd></div>}
                          </dl>
                          {order.shipment.events && order.shipment.events.length > 0 && (
                            <div className="mt-4 border-t border-line pt-4 dark:border-line-dark">
                              <h3 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Tracking Events</h3>
                              <div className="mt-3 space-y-3">
                                {order.shipment.events
                                  .slice()
                                  .sort((a, b) => {
                                    const timeA = a.occurredAt ? new Date(a.occurredAt).getTime() : 0;
                                    const timeB = b.occurredAt ? new Date(b.occurredAt).getTime() : 0;
                                    return timeA - timeB;
                                  })
                                  .map((event, index) => {
                                    const occurredAt = event.occurredAt ? new Date(event.occurredAt) : null;
                                    return (
                                      <div key={event.id} className="flex items-start gap-3">
                                        <div className="flex flex-col items-center">
                                          <div
                                            className={`w-3 h-3 rounded-full border-2 ${
                                              index === 0 ? 'bg-bronze border-bronze' : 'bg-ink border-ink'
                                            }`}
                                          />
                                          {index < (order.shipment?.events?.length ?? 0) - 1 && (
                                            <div className="w-0.5 h-8 mt-1 bg-line dark:bg-line-dark" />
                                          )}
                                        </div>
                                        <div className="flex-1 min-w-0">
                                          <p className="text-sm font-semibold text-ink dark:text-linen capitalize">{event.status}</p>
                                          <p className="text-xs text-smoke dark:text-linen-dim">
                                            {event.description}
                                            {event.location && ` — ${event.location}`}
                                          </p>
                                          <p className="text-xs text-smoke dark:text-linen-dim mt-1">
                                            {occurredAt ? occurredAt.toLocaleString('en-US', {
                                              month: 'short',
                                              day: 'numeric',
                                              hour: '2-digit',
                                              minute: '2-digit',
                                            }) : 'Unknown date'}
                                          </p>
                                        </div>
                                      </div>
                                    );
                                  })}
                              </div>
                            </div>
                          )}
                        </div>
                      )}
                      {!order.shipment && (
                        <div className="border-t border-line pt-4 dark:border-line-dark">
                          <h2 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen flex items-center gap-2"><Truck className="h-4 w-4" /> Shipping</h2>
                          <p className="mt-2 text-sm text-smoke dark:text-linen-dim">No shipment created yet.</p>
                          <Button type="button" size="sm" variant="outline" className="mt-3" onClick={() => setShowCreateShipment(order.id)}>Create Shipment</Button>
                        </div>
                      )}
                      {showCreateShipment === order.id && (
                        <div className="border-t border-line pt-4 dark:border-line-dark">
                          <h2 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Create Shipment</h2>
                          <CreateShipmentForm orderId={order.id} onClose={() => setShowCreateShipment(null)} onSuccess={() => { setShowCreateShipment(null); }} />
                        </div>
                      )}
                      {order.shipment && (
                        <div className="border-t border-line pt-4 dark:border-line-dark">
                          <h2 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Update Shipment</h2>
                          <UpdateShipmentForm order={order} onClose={() => {}} />
                        </div>
                      )}
                      {order.shipment && (
                        <div className="border-t border-line pt-4 dark:border-line-dark">
                          <div className="flex flex-wrap gap-2">
                            <Button
                              type="button"
                              size="sm"
                              variant="outline"
                              icon={RotateCcw}
                              loading={savingId === `sync-${order.id}`}
                              onClick={() => void syncCourierStatus(order.id)}
                            >
                              Sync Courier Status
                            </Button>
                          </div>
                        </div>
                      )}
                      <div className="flex flex-wrap items-end gap-2">
                        <Field label="Update status" htmlFor={`order-status-${order.id}`}>
                          <Select id={`order-status-${order.id}`} value={nextStatus[order.id] ?? order.statusCode} onChange={(event) => setNextStatus((current) => ({ ...current, [order.id]: event.target.value }))}>
                            {STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
                          </Select>
                        </Field>
                        <Button type="button" size="sm" variant="outline" loading={savingId === order.id} disabled={nextStatus[order.id] === order.statusCode} onClick={() => void updateStatus(order)}>Save</Button>
                      </div>
                    </div>
                  </div>
                </details>
              </section>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
