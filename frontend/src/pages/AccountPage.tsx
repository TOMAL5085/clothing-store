import { useEffect, useState, type FormEvent } from "react";
import { Link, Navigate, useLocation, useSearchParams } from "react-router-dom";
import { BarChart3, Calendar, Heart, Home, KeyRound, LogOut, MailCheck, Moon, Package, RotateCcw, Save, ScrollText, ShieldCheck, Shirt, SlidersHorizontal, Star, Truck, XCircle } from "lucide-react";
import { ApiError, api } from "@/lib/api";
import { useAuthStore, type Address, type AddressPayload } from "@/store/authStore";
import { useOrderStore, type Order } from "@/store/orderStore";
import { useUiStore } from "@/store/uiStore";
import { useThemeStore } from "@/store/themeStore";
import { useCurrencyStore } from "@/store/currencyStore";
import { useCatalogStore } from "@/store/catalogStore";
import { Button, EmptyState, Field, Input, Price } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";
import { cn } from "@/utils/cn";

const TABS = [
  { id: "overview", label: "Overview" },
  { id: "orders", label: "Orders" },
  { id: "profile", label: "Profile" },
  { id: "security", label: "Security" },
  { id: "addresses", label: "Addresses" },
] as const;

const emptyAddress: AddressPayload = {
  label: "",
  firstName: "",
  lastName: "",
  email: "",
  phone: "",
  address: "",
  city: "",
  postalCode: "",
  country: "",
  isDefault: false,
};

interface OrderCollection {
  data: Order[];
}

function apiMessage(error: unknown, fallback: string) {
  if (error instanceof ApiError) {
    const first = Object.values(error.errors ?? {})[0]?.[0];
    return first ?? error.message;
  }
  return fallback;
}

export default function AccountPage() {
  usePageTitle("My Account");
  const location = useLocation();
  const [params, setParams] = useSearchParams();
  const tab = params.get("tab") ?? "overview";

  const {
    user,
    addresses,
    logout,
    updateProfile,
    updatePassword,
    requestOtp,
    verifyOtp,
    loadAddresses,
    saveAddress,
    deleteAddress,
  } = useAuthStore();
  const localOrders = useOrderStore((s) => s.orders);
  const wishlist = useUiStore((s) => s.wishlist);
  const pushToast = useUiStore((s) => s.pushToast);
  const products = useCatalogStore((s) => s.products);
  const { theme, toggleTheme } = useThemeStore();
  const { currency } = useCurrencyStore();
  const [orders, setOrders] = useState<Order[]>(localOrders);
  const [profile, setProfile] = useState({ name: "", email: "", phone: "" });
  const [password, setPassword] = useState({ currentPassword: "", password: "", passwordConfirmation: "" });
  const [otp, setOtp] = useState("");
  const [addressForm, setAddressForm] = useState<AddressPayload>(emptyAddress);
  const [editingAddressId, setEditingAddressId] = useState<number | undefined>();
  const [busy, setBusy] = useState<string | null>(null);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!user) return;
    setProfile({ name: user.name, email: user.email, phone: user.phone ?? "" });
    setAddressForm((current) => ({ ...current, firstName: user.name.split(" ")[0] ?? "", email: user.email, phone: user.phone ?? "" }));
  }, [user]);

  useEffect(() => {
    if (!user) return;
    void api<OrderCollection>("/orders")
      .then((response) => setOrders(response.data))
      .catch(() => setOrders(localOrders));
    void loadAddresses().catch(() => undefined);
  }, [loadAddresses, localOrders, user]);

  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname + location.search }} />;

  const firstName = user.name.split(" ")[0];
  const hour = new Date().getHours();
  const greeting = hour < 12 ? "Good morning" : hour < 18 ? "Good afternoon" : "Good evening";

  const setTab = (id: string) => setParams(id === "overview" ? {} : { tab: id }, { preventScrollReset: true });

  const submitProfile = async (event: FormEvent) => {
    event.preventDefault();
    setBusy("profile");
    setError("");
    try {
      const debugOtp = await updateProfile(profile);
      pushToast(debugOtp ? `Verification code: ${debugOtp}` : "Profile updated");
    } catch (requestError) {
      setError(apiMessage(requestError, "Profile update failed."));
    } finally {
      setBusy(null);
    }
  };

  const submitPassword = async (event: FormEvent) => {
    event.preventDefault();
    setBusy("password");
    setError("");
    try {
      await updatePassword(password);
      setPassword({ currentPassword: "", password: "", passwordConfirmation: "" });
      pushToast("Password updated");
    } catch (requestError) {
      setError(apiMessage(requestError, "Password update failed."));
    } finally {
      setBusy(null);
    }
  };

  const submitOtp = async (event: FormEvent) => {
    event.preventDefault();
    setBusy("otp");
    setError("");
    try {
      await verifyOtp(otp);
      setOtp("");
      pushToast("Email verified");
    } catch (requestError) {
      setError(apiMessage(requestError, "Verification failed."));
    } finally {
      setBusy(null);
    }
  };

  const sendOtp = async () => {
    setBusy("send-otp");
    setError("");
    try {
      const debugOtp = await requestOtp();
      pushToast(debugOtp ? `Verification code: ${debugOtp}` : "Verification code sent");
    } catch (requestError) {
      setError(apiMessage(requestError, "Could not send verification code."));
    } finally {
      setBusy(null);
    }
  };

  const submitAddress = async (event: FormEvent) => {
    event.preventDefault();
    setBusy("address");
    setError("");
    try {
      await saveAddress(addressForm, editingAddressId);
      setAddressForm({ ...emptyAddress, firstName, email: user.email, phone: user.phone ?? "" });
      setEditingAddressId(undefined);
      pushToast(editingAddressId ? "Address updated" : "Address saved");
    } catch (requestError) {
      setError(apiMessage(requestError, "Address could not be saved."));
    } finally {
      setBusy(null);
    }
  };

  const editAddress = (address: Address) => {
    setEditingAddressId(address.id);
    setAddressForm({
      label: address.label ?? "",
      firstName: address.firstName,
      lastName: address.lastName,
      email: address.email,
      phone: address.phone ?? "",
      address: address.address,
      city: address.city,
      postalCode: address.postalCode,
      country: address.country,
      isDefault: address.isDefault,
    });
    setTab("addresses");
  };

  const removeAddress = async (id: number) => {
    setBusy(`delete-${id}`);
    try {
      await deleteAddress(id);
      pushToast("Address deleted");
    } catch (requestError) {
      setError(apiMessage(requestError, "Address could not be deleted."));
    } finally {
      setBusy(null);
    }
  };

  const reloadOrders = async () => {
    const response = await api<OrderCollection>("/orders");
    setOrders(response.data);
  };

  const requestCancellation = async (order: Order) => {
    const reason = window.prompt("Why would you like to cancel this order?");
    if (!reason?.trim()) return;
    setBusy(`cancel-${order.id}`);
    setError("");
    try {
      await api(`/orders/${encodeURIComponent(order.id)}/cancellation`, {
        method: "POST",
        body: JSON.stringify({ reason }),
      });
      await reloadOrders();
      pushToast("Cancellation request submitted");
    } catch (requestError) {
      setError(apiMessage(requestError, "Cancellation request could not be submitted."));
    } finally {
      setBusy(null);
    }
  };

  const requestReturn = async (order: Order, orderItemId?: number) => {
    if (!orderItemId) {
      setError("Return request could not identify the order item.");
      return;
    }
    const reason = window.prompt("Why would you like to return this item?");
    if (!reason?.trim()) return;
    setBusy(`return-${order.id}-${orderItemId}`);
    setError("");
    try {
      await api(`/orders/${encodeURIComponent(order.id)}/returns`, {
        method: "POST",
        body: JSON.stringify({
          reason,
          items: [{ order_item_id: orderItemId, quantity: 1 }],
        }),
      });
      await reloadOrders();
      pushToast("Return request submitted");
    } catch (requestError) {
      setError(apiMessage(requestError, "Return request could not be submitted."));
    } finally {
      setBusy(null);
    }
  };

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">JAAJ Members</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">
            {greeting}, {firstName}.
          </h1>
        </div>
        <Button variant="outline" icon={LogOut} onClick={() => logout()}>
          Sign Out
        </Button>
      </div>

      <div className="mt-10 flex gap-1 overflow-x-auto border-b border-line dark:border-line-dark" role="tablist" aria-label="Account sections">
        {TABS.map((t) => (
          <button
            key={t.id}
            type="button"
            role="tab"
            aria-selected={tab === t.id}
            onClick={() => setTab(t.id)}
            className={cn(
              "shrink-0 border-b-2 px-5 py-3 text-xs font-bold tracking-[0.16em] uppercase transition-all",
              tab === t.id
                ? "border-ink text-ink dark:border-linen dark:text-linen"
                : "border-transparent text-smoke hover:text-ink dark:text-linen-dim dark:hover:text-linen",
            )}
          >
            {t.label}
          </button>
        ))}
      </div>

      {error && (
        <p className="mt-5 border border-red-800/30 bg-red-800/10 px-4 py-3 text-sm font-semibold text-red-800 dark:text-red-300" role="alert">
          {error}
        </p>
      )}

      {tab === "overview" && (
        <div className="mt-10 grid gap-4 sm:grid-cols-3">
          {[
            { icon: Package, label: "Orders", value: String(orders.length), to: "?tab=orders", hint: orders.length ? "Track recent orders" : "No orders yet" },
            { icon: Heart, label: "Wishlist", value: String(wishlist.length), to: "/wishlist", hint: wishlist.length ? "Pieces you're keeping an eye on" : "Nothing saved yet" },
            { icon: MailCheck, label: "Verification", value: user.emailVerified ? "Verified" : "Pending", to: "?tab=security", hint: user.emailVerified ? "Email confirmed" : "Verify your email" },
            { icon: Home, label: "Addresses", value: String(addresses.length), to: "?tab=addresses", hint: addresses.length ? "Saved for checkout" : "Add a delivery address" },
            { icon: Calendar, label: "Member since", value: new Date(user.joinedAt).toLocaleDateString("en-US", { month: "short", year: "numeric" }), to: "?tab=profile", hint: user.status === "inactive" ? "Account inactive" : "Account active" },
            ...(user.role === "admin"
              ? [
                  { icon: Shirt, label: "Admin", value: "Inventory", to: "/admin/products", hint: "Manage products and stock" },
                  { icon: Package, label: "Orders", value: "Manage", to: "/admin/orders", hint: "Fulfillment and order status" },
                  { icon: ShieldCheck, label: "Customers", value: "Manage", to: "/admin/customers", hint: "Customer account controls" },
                  { icon: Star, label: "Reviews", value: "Moderate", to: "/admin/reviews", hint: "Approve or reject product reviews" },
                  { icon: BarChart3, label: "Analytics", value: "Insights", to: "/admin/analytics", hint: "Revenue, orders and catalog reports" },
                  { icon: ScrollText, label: "Audit", value: "Log", to: "/admin/audit-log", hint: "Administrative action history" },
                ]
              : []),
          ].map(({ icon: Icon, label, value, to, hint }) => (
            <Link key={label} to={to} className="group space-y-4 border border-line bg-cream p-6 transition-colors hover:border-ink dark:border-line-dark dark:bg-nox2 dark:hover:border-linen">
              <Icon className="h-5 w-5 text-bronze" aria-hidden />
              <div>
                <p className="text-[10px] font-bold tracking-[0.2em] uppercase text-smoke dark:text-linen-dim">{label}</p>
                <p className="mt-1 font-display text-3xl text-ink group-hover:text-bronze dark:text-linen">{value}</p>
                <p className="mt-1 text-xs text-smoke dark:text-linen-dim">{hint}</p>
              </div>
            </Link>
          ))}
        </div>
      )}

      {tab === "orders" && (
        <div className="mt-10">
          {orders.length === 0 ? (
            <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
              <EmptyState icon={Package} title="No orders yet" body="When you place an order it appears here with tracking and return options." actionLabel="Start with new arrivals" actionTo="/shop?sort=newest" />
            </div>
          ) : (
            <ul className="space-y-4">
              {orders.map((order) => (
                <li key={order.id} className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
                  <details className="group">
                    <summary className="flex cursor-pointer list-none flex-wrap items-center justify-between gap-4 p-5 [&::-webkit-details-marker]:hidden">
                      <div className="flex flex-wrap items-center gap-x-8 gap-y-2">
                        <div><p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Order</p><p className="font-semibold text-ink dark:text-linen">{order.id}</p></div>
                        <div><p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Placed</p><p className="text-sm text-ink dark:text-linen">{new Date(order.createdAt).toLocaleDateString("en-US", { day: "numeric", month: "short", year: "numeric" })}</p></div>
                        <div><p className="text-[10px] font-bold tracking-[0.18em] uppercase text-smoke dark:text-linen-dim">Total</p><Price amount={order.total} /></div>
                      </div>
                      <span className="bg-bronze/15 px-3 py-1 text-[10px] font-bold tracking-[0.14em] uppercase text-bronze">{order.status}</span>
                    </summary>
                    <div className="border-t border-line px-5 py-5 dark:border-line-dark">
                      <ul className="space-y-3">
                        {order.lines.map((line) => {
                          const product = products.find((p) => p.id === line.productId);
                          return (
                            <li key={line.id} className="flex items-center gap-3">
                              {product && <img src={product.images[0]} alt={product.alt} width={100} height={132} loading="lazy" decoding="async" className="h-14 w-10 bg-fog object-cover dark:bg-nox3" />}
                              <Link to={product ? `/product/${product.slug}` : "/shop"} className="min-w-0 flex-1 truncate text-sm font-semibold text-ink hover:text-bronze dark:text-linen">
                                {product?.name ?? line.productName ?? line.productId}
                              </Link>
                              <span className="text-xs text-smoke dark:text-linen-dim">Size {line.size} x {line.qty}</span>
                              <Price amount={(line.unitPrice ?? product?.price ?? 0) * line.qty} />
                              {order.status === "Delivered" && (line.productSlug || product) && (
                                <Link
                                  to={`/product/${line.productSlug ?? product?.slug}#reviews`}
                                  className="inline-flex items-center gap-1.5 border border-line px-3 py-2 text-[10px] font-bold tracking-[0.14em] uppercase text-smoke transition-colors hover:border-ink hover:text-ink dark:border-line-dark dark:text-linen-dim dark:hover:border-linen dark:hover:text-linen"
                                >
                                  <Star className="h-3.5 w-3.5" aria-hidden />
                                  Review
                                </Link>
                              )}
                              {order.status === "Delivered" && (
                                <Button
                                  type="button"
                                  size="sm"
                                  variant="outline"
                                  icon={RotateCcw}
                                  loading={busy === `return-${order.id}-${line.orderItemId}`}
                                  onClick={() => void requestReturn(order, line.orderItemId)}
                                >
                                  Return
                                </Button>
                              )}
                            </li>
                          );
                        })}
                      </ul>
                      <div className="mt-4 flex flex-wrap gap-2">
                        {["Pending", "Confirmed", "Processing"].includes(order.status) && !order.cancellation && (
                          <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            icon={XCircle}
                            loading={busy === `cancel-${order.id}`}
                            onClick={() => void requestCancellation(order)}
                          >
                            Request Cancellation
                          </Button>
                        )}
                      </div>
                      {order.shipment && (
                        <div className="mt-4 border-t border-line pt-4 dark:border-line-dark">
                          <h3 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen flex items-center gap-2"><Truck className="h-4 w-4" /> Shipping</h3>
                          <dl className="mt-2 space-y-1 text-sm">
                            <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Status</dt><dd className="font-semibold capitalize">{order.shipment.status}</dd></div>
                            {order.shipment.carrier && <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Carrier</dt><dd className="font-semibold text-ink dark:text-linen">{order.shipment.carrier}</dd></div>}
                            {order.shipment.trackingNumber && <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Tracking</dt><dd className="font-semibold text-ink dark:text-linen">{order.shipment.trackingNumber}</dd></div>}
                            {order.shipment.estimatedDeliveryAt && <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Est. Delivery</dt><dd className="font-semibold text-ink dark:text-linen">{new Date(order.shipment.estimatedDeliveryAt).toLocaleDateString()}</dd></div>}
                            {order.shipment.shippedAt && <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Shipped</dt><dd className="font-semibold text-ink dark:text-linen">{new Date(order.shipment.shippedAt).toLocaleDateString()}</dd></div>}
                            {order.shipment.deliveredAt && <div className="flex justify-between"><dt className="text-smoke dark:text-linen-dim">Delivered</dt><dd className="font-semibold text-ink dark:text-linen">{new Date(order.shipment.deliveredAt).toLocaleDateString()}</dd></div>}
                          </dl>
                          {order.shipment.events && order.shipment.events.length > 0 && (
                            <div className="mt-4 border-t border-line pt-4 dark:border-line-dark">
                              <h4 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Timeline</h4>
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
                      {(order.cancellation || (order.returns && order.returns.length > 0) || (order.refunds && order.refunds.length > 0)) && (
                        <div className="mt-4 border-t border-line pt-4 dark:border-line-dark">
                          <h3 className="text-xs font-bold tracking-[0.18em] uppercase text-ink dark:text-linen">Resolution</h3>
                          {order.cancellation && (
                            <p className="mt-2 text-sm text-smoke dark:text-linen-dim">
                              Cancellation: <span className="font-semibold capitalize text-ink dark:text-linen">{order.cancellation.status}</span>
                              {order.cancellation.refund && <> · Refund {order.cancellation.refund.status}</>}
                            </p>
                          )}
                          {order.returns?.map((entry) => (
                            <p key={entry.id} className="mt-2 text-sm text-smoke dark:text-linen-dim">
                              Return #{entry.id}: <span className="font-semibold capitalize text-ink dark:text-linen">{entry.status}</span>
                              {entry.refund && <> · Refund {entry.refund.status}</>}
                            </p>
                          ))}
                          {order.refunds?.map((refund) => (
                            <p key={refund.id} className="mt-2 text-sm text-smoke dark:text-linen-dim">
                              Refund #{refund.id}: <span className="font-semibold capitalize text-ink dark:text-linen">{refund.status}</span> · <Price amount={refund.amount} />
                            </p>
                          ))}
                        </div>
                      )}
                    </div>
                  </details>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}

      {tab === "profile" && (
        <form onSubmit={submitProfile} className="mt-10 grid gap-4 lg:grid-cols-2">
          <div className="border border-line bg-cream p-6 dark:border-line-dark dark:bg-nox2">
            <h2 className="text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">Profile</h2>
            <div className="mt-5 space-y-4">
              <Field label="Name" htmlFor="profile-name" required><Input id="profile-name" value={profile.name} onChange={(e) => setProfile((p) => ({ ...p, name: e.target.value }))} /></Field>
              <Field label="Email" htmlFor="profile-email" required><Input id="profile-email" type="email" value={profile.email} onChange={(e) => setProfile((p) => ({ ...p, email: e.target.value }))} /></Field>
              <Field label="Phone" htmlFor="profile-phone"><Input id="profile-phone" type="tel" value={profile.phone} onChange={(e) => setProfile((p) => ({ ...p, phone: e.target.value }))} /></Field>
              <Button type="submit" icon={Save} loading={busy === "profile"}>Save Profile</Button>
            </div>
          </div>
          <div className="border border-line bg-cream p-6 dark:border-line-dark dark:bg-nox2">
            <h2 className="text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">Account</h2>
            <dl className="mt-5 space-y-4 text-sm">
              <div className="flex justify-between gap-4 border-b border-line pb-3 dark:border-line-dark"><dt className="text-smoke dark:text-linen-dim">Status</dt><dd className="font-semibold capitalize text-ink dark:text-linen">{user.status ?? "active"}</dd></div>
              <div className="flex justify-between gap-4 border-b border-line pb-3 dark:border-line-dark"><dt className="text-smoke dark:text-linen-dim">Email</dt><dd className="font-semibold text-ink dark:text-linen">{user.emailVerified ? "Verified" : "Pending"}</dd></div>
              <div className="flex justify-between gap-4"><dt className="text-smoke dark:text-linen-dim">Member since</dt><dd className="font-semibold text-ink dark:text-linen">{new Date(user.joinedAt).toLocaleDateString("en-US", { day: "numeric", month: "long", year: "numeric" })}</dd></div>
            </dl>
          </div>
        </form>
      )}

      {tab === "security" && (
        <div className="mt-10 grid gap-4 lg:grid-cols-2">
          <div className="border border-line bg-cream p-6 dark:border-line-dark dark:bg-nox2">
            <h2 className="flex items-center gap-2 text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen"><ShieldCheck className="h-4 w-4 text-bronze" /> Email Verification</h2>
            <p className="mt-4 text-sm text-smoke dark:text-linen-dim">{user.emailVerified ? "Your email is verified." : "Enter the six-digit verification code sent to your email."}</p>
            {!user.emailVerified && (
              <form onSubmit={submitOtp} className="mt-5 space-y-4">
                <Field label="Verification code" htmlFor="otp-code" required><Input id="otp-code" inputMode="numeric" maxLength={6} value={otp} onChange={(e) => setOtp(e.target.value)} /></Field>
                <div className="flex flex-wrap gap-3">
                  <Button type="submit" icon={MailCheck} loading={busy === "otp"}>Verify</Button>
                  <Button type="button" variant="outline" onClick={() => void sendOtp()} loading={busy === "send-otp"}>Send Code</Button>
                </div>
              </form>
            )}
          </div>
          <form onSubmit={submitPassword} className="border border-line bg-cream p-6 dark:border-line-dark dark:bg-nox2">
            <h2 className="flex items-center gap-2 text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen"><KeyRound className="h-4 w-4 text-bronze" /> Password</h2>
            <div className="mt-5 space-y-4">
              <Field label="Current password" htmlFor="current-password" required><Input id="current-password" type="password" value={password.currentPassword} onChange={(e) => setPassword((p) => ({ ...p, currentPassword: e.target.value }))} /></Field>
              <Field label="New password" htmlFor="new-password" required><Input id="new-password" type="password" value={password.password} onChange={(e) => setPassword((p) => ({ ...p, password: e.target.value }))} /></Field>
              <Field label="Confirm password" htmlFor="confirm-password" required><Input id="confirm-password" type="password" value={password.passwordConfirmation} onChange={(e) => setPassword((p) => ({ ...p, passwordConfirmation: e.target.value }))} /></Field>
              <Button type="submit" icon={Save} loading={busy === "password"}>Update Password</Button>
            </div>
          </form>
        </div>
      )}

      {tab === "addresses" && (
        <div className="mt-10 grid gap-4 lg:grid-cols-[1fr_1.1fr]">
          <form onSubmit={submitAddress} className="border border-line bg-cream p-6 dark:border-line-dark dark:bg-nox2">
            <h2 className="text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">{editingAddressId ? "Edit Address" : "New Address"}</h2>
            <div className="mt-5 grid gap-4 sm:grid-cols-2">
              <Field label="Label" htmlFor="address-label"><Input id="address-label" value={addressForm.label ?? ""} onChange={(e) => setAddressForm((p) => ({ ...p, label: e.target.value }))} /></Field>
              <Field label="Phone" htmlFor="address-phone"><Input id="address-phone" type="tel" value={addressForm.phone ?? ""} onChange={(e) => setAddressForm((p) => ({ ...p, phone: e.target.value }))} /></Field>
              <Field label="First name" htmlFor="address-first" required><Input id="address-first" value={addressForm.firstName} onChange={(e) => setAddressForm((p) => ({ ...p, firstName: e.target.value }))} /></Field>
              <Field label="Last name" htmlFor="address-last" required><Input id="address-last" value={addressForm.lastName} onChange={(e) => setAddressForm((p) => ({ ...p, lastName: e.target.value }))} /></Field>
              <Field label="Email" htmlFor="address-email" required className="sm:col-span-2"><Input id="address-email" type="email" value={addressForm.email} onChange={(e) => setAddressForm((p) => ({ ...p, email: e.target.value }))} /></Field>
              <Field label="Address" htmlFor="address-line" required className="sm:col-span-2"><Input id="address-line" value={addressForm.address} onChange={(e) => setAddressForm((p) => ({ ...p, address: e.target.value }))} /></Field>
              <Field label="City" htmlFor="address-city" required><Input id="address-city" value={addressForm.city} onChange={(e) => setAddressForm((p) => ({ ...p, city: e.target.value }))} /></Field>
              <Field label="Postal code" htmlFor="address-postal" required><Input id="address-postal" value={addressForm.postalCode} onChange={(e) => setAddressForm((p) => ({ ...p, postalCode: e.target.value }))} /></Field>
              <Field label="Country" htmlFor="address-country" required className="sm:col-span-2"><Input id="address-country" value={addressForm.country} onChange={(e) => setAddressForm((p) => ({ ...p, country: e.target.value }))} /></Field>
            </div>
            <label className="mt-4 flex cursor-pointer items-center gap-2.5 text-sm text-smoke dark:text-linen-dim">
              <input type="checkbox" checked={Boolean(addressForm.isDefault)} onChange={(e) => setAddressForm((p) => ({ ...p, isDefault: e.target.checked }))} className="h-4 w-4 accent-bronze" />
              Default address
            </label>
            <div className="mt-5 flex flex-wrap gap-3">
              <Button type="submit" icon={Save} loading={busy === "address"}>{editingAddressId ? "Update Address" : "Save Address"}</Button>
              {editingAddressId && <Button type="button" variant="outline" onClick={() => { setEditingAddressId(undefined); setAddressForm({ ...emptyAddress, firstName, email: user.email, phone: user.phone ?? "" }); }}>Cancel</Button>}
            </div>
          </form>
          <div className="space-y-4">
            {addresses.length === 0 ? (
              <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2"><EmptyState icon={Home} title="No addresses yet" body="Save an address here for faster checkout later." /></div>
            ) : addresses.map((address) => (
              <div key={address.id} className="border border-line bg-cream p-5 dark:border-line-dark dark:bg-nox2">
                <div className="flex items-start justify-between gap-4">
                  <div>
                    <p className="text-sm font-semibold text-ink dark:text-linen">{address.label || "Address"} {address.isDefault && <span className="ml-2 text-[10px] font-bold tracking-[0.14em] text-bronze uppercase">Default</span>}</p>
                    <p className="mt-2 text-sm text-smoke dark:text-linen-dim">{address.firstName} {address.lastName}</p>
                    <p className="text-sm text-smoke dark:text-linen-dim">{address.address}, {address.city} {address.postalCode}</p>
                    <p className="text-sm text-smoke dark:text-linen-dim">{address.country}</p>
                  </div>
                  <div className="flex gap-2">
                    <Button type="button" size="sm" variant="outline" onClick={() => editAddress(address)}>Edit</Button>
                    <Button type="button" size="sm" variant="ghost" loading={busy === `delete-${address.id}`} onClick={() => void removeAddress(address.id)}>Delete</Button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {tab === "profile" && (
        <div className="mt-4 border border-line bg-cream p-6 dark:border-line-dark dark:bg-nox2">
          <h2 className="flex items-center gap-2 text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">
            <SlidersHorizontal className="h-4 w-4 text-bronze" aria-hidden /> Preferences
          </h2>
          <div className="mt-5 flex items-center justify-between gap-4 text-sm">
            <span className="flex items-center gap-2 text-smoke dark:text-linen-dim"><Moon className="h-4 w-4" aria-hidden /> Appearance</span>
            <button type="button" onClick={toggleTheme} aria-pressed={theme === "dark"} className="border border-line px-4 py-2 text-[10px] font-bold tracking-[0.14em] uppercase text-ink transition-colors hover:border-ink dark:border-line-dark dark:text-linen dark:hover:border-linen">{theme === "dark" ? "Dark" : "Light"}</button>
          </div>
          <div className="mt-4 flex items-center justify-between gap-4 text-sm">
            <span className="text-smoke dark:text-linen-dim">Currency</span>
            <span className="font-semibold text-ink dark:text-linen">{currency}</span>
          </div>
        </div>
      )}
    </div>
  );
}
