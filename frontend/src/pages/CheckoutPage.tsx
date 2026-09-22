import { useEffect, useMemo, useRef, useState, type FormEvent } from "react";
import { Link, useNavigate } from "react-router-dom";
import { CreditCard, Info, Lock, ShoppingBag } from "lucide-react";
import { useCartStore } from "@/store/cartStore";
import { useCurrencyStore } from "@/store/currencyStore";
import { useOrderStore, type Order, type OrderAddress } from "@/store/orderStore";
import { useAuthStore } from "@/store/authStore";
import { FREE_SHIPPING_THRESHOLD, STANDARD_SHIPPING, useCartTotals } from "@/pages/CartPage";
import { Button, EmptyState, Field, Input, Price, Select } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";
import { cn } from "@/utils/cn";
import { api, ApiError } from "@/lib/api";
import { getAnonymousId, trackBeginCheckout } from "@/lib/marketing";

interface FormState extends OrderAddress {
  cardName: string;
  cardNumber: string;
  expiry: string;
  cvc: string;
}

interface CheckoutQuote {
  countryCode: string;
  provider: string;
  intendedProvider: string;
  paymentMode: "demo" | "gateways";
  currency: string;
  subtotal: number;
  discount: number;
  shipping: number;
  tax: number;
  total: number;
}

interface CheckoutResponse {
  data: Order;
  payment?: {
    mode?: string;
    provider?: string;
    intendedProvider?: string;
    redirectUrl?: string | null;
    checkoutToken?: string | null;
  };
}

const INITIAL: FormState = {
  firstName: "",
  lastName: "",
  email: "",
  address: "",
  city: "",
  postalCode: "",
  country: "Denmark",
  cardName: "",
  cardNumber: "",
  expiry: "",
  cvc: "",
};

const COUNTRIES = [
  "Bangladesh",
  "Denmark",
  "Germany",
  "France",
  "United Kingdom",
  "United States",
  "Sweden",
  "Norway",
  "Netherlands",
  "Spain",
  "Italy",
];

function validate(form: FormState, requireCard: boolean) {
  const errors: Partial<Record<keyof FormState, string>> = {};
  if (!form.firstName.trim()) errors.firstName = "First name is required.";
  if (!form.lastName.trim()) errors.lastName = "Last name is required.";
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(form.email)) errors.email = "Enter a valid email address.";
  if (form.address.trim().length < 4) errors.address = "Enter your street address.";
  if (!form.city.trim()) errors.city = "City is required.";
  if (form.postalCode.trim().length < 3) errors.postalCode = "Enter a valid postal code.";
  if (requireCard) {
    if (!form.cardName.trim()) errors.cardName = "Name on card is required.";
    const digits = form.cardNumber.replace(/\D/g, "");
    if (digits.length !== 16) errors.cardNumber = "Card number must be 16 digits.";
    if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(form.expiry)) errors.expiry = "Use MM/YY format.";
    if (!/^\d{3,4}$/.test(form.cvc)) errors.cvc = "3-4 digits.";
  }
  return errors;
}

function apiMessage(error: unknown, fallback: string) {
  if (error instanceof ApiError) {
    const first = Object.values(error.errors ?? {})[0]?.[0];
    return first ?? error.message;
  }
  return fallback;
}

export default function CheckoutPage() {
  usePageTitle("Secure Checkout");
  const navigate = useNavigate();
  const { lines, subtotal, summary } = useCartTotals();
  const { promo, clearCart } = useCartStore();
  const addOrder = useOrderStore((s) => s.addOrder);
  const format = useCurrencyStore((s) => s.format);
  const user = useAuthStore((s) => s.user);
  const addresses = useAuthStore((s) => s.addresses);
  const loadAddresses = useAuthStore((s) => s.loadAddresses);

  const [form, setForm] = useState<FormState>(INITIAL);
  const [errors, setErrors] = useState<Partial<Record<keyof FormState, string>>>({});
  const [formError, setFormError] = useState("");
  const [method, setMethod] = useState<"standard" | "express">("standard");
  const [processing, setProcessing] = useState(false);
  const [addressId, setAddressId] = useState<number | "new">("new");
  const [quote, setQuote] = useState<CheckoutQuote | null>(null);

  useEffect(() => {
    if (user) void loadAddresses().catch(() => undefined);
  }, [loadAddresses, user]);

  const checkoutCount = lines.reduce((sum, { line }) => sum + line.qty, 0);
  const checkoutTracked = useRef(false);
  useEffect(() => {
    if (!checkoutTracked.current && checkoutCount > 0) {
      checkoutTracked.current = true;
      trackBeginCheckout(checkoutCount);
    }
  }, [checkoutCount]);

  useEffect(() => {
    const country = addressId !== "new" ? addresses.find((entry) => entry.id === addressId)?.country : form.country;
    if (!country) return;
    const controller = new AbortController();
    const timer = window.setTimeout(() => {
      void api<{ data: CheckoutQuote }>("/checkout/quote", {
        method: "POST",
        body: JSON.stringify({
          cart_token: localStorage.getItem("jaaj-cart-token"),
          delivery_method: method,
          ...(addressId === "new"
            ? { shipping_address: { ...form, country } }
            : { shipping_address_id: addressId }),
        }),
        signal: controller.signal,
      })
        .then((response) => setQuote(response.data))
        .catch((error) => {
          if (error instanceof DOMException && error.name === "AbortError") return;
          setQuote(null);
        });
    }, 250);
    return () => {
      controller.abort();
      window.clearTimeout(timer);
    };
  }, [addressId, addresses, form, method]);

  const fallbackShipping = useMemo(() => {
    if (method === "express") return 18;
    return subtotal - (summary.discount) >= FREE_SHIPPING_THRESHOLD ? 0 : STANDARD_SHIPPING;
  }, [method, subtotal, summary.discount]);
  const discount = quote?.discount ?? summary.discount;
  const shippingCost = quote?.shipping ?? fallbackShipping;
  const displaySubtotal = quote?.subtotal ?? subtotal;
  const total = quote?.total ?? displaySubtotal - discount + shippingCost;
  const requireCard = (quote?.paymentMode ?? "demo") === "demo";
  const provider = quote?.provider ?? "demo";
  const intended = quote?.intendedProvider ?? provider;

  const applySavedAddress = (id: number | "new") => {
    setAddressId(id);
    if (id === "new") return;
    const saved = addresses.find((entry) => entry.id === id);
    if (!saved) return;
    setForm((prev) => ({
      ...prev,
      firstName: saved.firstName,
      lastName: saved.lastName,
      email: saved.email,
      address: saved.address,
      city: saved.city,
      postalCode: saved.postalCode,
      country: COUNTRIES.includes(saved.country) ? saved.country : saved.country,
    }));
  };

  const set = (key: keyof FormState) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    let value = e.target.value;
    if (key === "cardNumber") value = value.replace(/[^\d]/g, "").slice(0, 16).replace(/(\d{4})(?=\d)/g, "$1 ");
    if (key === "expiry") {
      value = value.replace(/[^\d]/g, "").slice(0, 4);
      if (value.length > 2) value = `${value.slice(0, 2)}/${value.slice(2)}`;
    }
    if (key === "cvc") value = value.replace(/[^\d]/g, "").slice(0, 4);
    setForm((prev) => ({ ...prev, [key]: value }));
    setErrors((prev) => ({ ...prev, [key]: undefined }));
    setAddressId("new");
  };

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    const nextErrors = validate(form, requireCard);
    setErrors(nextErrors);
    setFormError("");
    if (Object.values(nextErrors).some(Boolean)) {
      document.querySelector<HTMLElement>("[aria-invalid='true']")?.focus();
      return;
    }
    setProcessing(true);
    try {
      const response = await api<CheckoutResponse>("/checkout/orders", {
        method: "POST",
        body: JSON.stringify({
          cart_token: localStorage.getItem("jaaj-cart-token"),
          anonymous_id: getAnonymousId(),
          promo_code: promo,
          delivery_method: method,
          ...(addressId === "new" ? {} : { shipping_address_id: addressId }),
          shipping_address: {
            firstName: form.firstName,
            lastName: form.lastName,
            email: form.email,
            address: form.address,
            city: form.city,
            postalCode: form.postalCode,
            country: form.country,
          },
          ...(requireCard
            ? {
                payment: {
                  card_name: form.cardName,
                  card_number: form.cardNumber,
                  expiry: form.expiry,
                  cvc: form.cvc,
                },
              }
            : {}),
        }),
      });
      const order = {
        ...response.data,
        checkoutToken: response.payment?.checkoutToken ?? response.data.checkoutToken,
      };
      addOrder(order);
      if (response.payment?.redirectUrl) {
        window.location.href = response.payment.redirectUrl;
        return;
      }
      await clearCart();
      navigate(`/order/success/${order.id}`);
    } catch (error) {
      const reason = apiMessage(error, "Payment could not be completed.");
      if (error instanceof ApiError && error.status === 422) {
        setFormError(reason);
        return;
      }
      navigate("/order/failed", { state: { reason } });
    } finally {
      setProcessing(false);
    }
  };

  if (lines.length === 0) {
    return (
      <div className="mx-auto max-w-3xl">
        <EmptyState
          icon={ShoppingBag}
          title="Your bag is empty"
          body="Add a few considered pieces before heading to checkout."
          actionLabel="Shop the collection"
          actionTo="/shop"
        />
      </div>
    );
  }

  const steps = ["Shipping", "Payment", "Confirm"];
  const payLabel = !requireCard
    ? intended === "sslcommerz"
      ? "Continue to SSLCOMMERZ"
      : "Continue to Stripe"
    : processing
      ? "Processing Payment…"
      : `Pay ${format(total)}`;

  return (
    <div className="mx-auto max-w-[1440px] px-4 py-10 sm:px-6 lg:px-10 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">JAAJ Secure Checkout</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Checkout</h1>
        </div>
        <ol className="flex items-center gap-2 text-[10px] font-bold tracking-[0.16em] uppercase" aria-label="Checkout steps">
          {steps.map((step, i) => (
            <li key={step} className="flex items-center gap-2">
              <span className={cn("flex h-6 w-6 items-center justify-center rounded-full", i === 0 ? "bg-ink text-paper dark:bg-linen dark:text-nox" : "bg-fog text-smoke dark:bg-nox2 dark:text-linen-dim")}>
                {i + 1}
              </span>
              <span className={i === 0 ? "text-ink dark:text-linen" : "text-smoke dark:text-linen-dim"}>{step}</span>
              {i < steps.length - 1 && <span className="h-px w-6 bg-line dark:bg-line-dark" aria-hidden />}
            </li>
          ))}
        </ol>
      </div>

      <form onSubmit={submit} noValidate className="mt-10 grid gap-12 lg:grid-cols-[1fr_400px]">
        <div className="space-y-10">
          {formError && (
            <p className="border border-red-800/30 bg-red-800/10 px-4 py-3 text-sm font-semibold text-red-800 dark:text-red-300" role="alert">
              {formError}
            </p>
          )}

          <fieldset className="space-y-5">
            <legend className="font-display text-2xl text-ink dark:text-linen">Shipping Details</legend>
            {user && addresses.length > 0 && (
              <Field label="Saved address" htmlFor="savedAddress">
                <Select id="savedAddress" value={String(addressId)} onChange={(event) => applySavedAddress(event.target.value === "new" ? "new" : Number(event.target.value))}>
                  <option value="new">Enter a new address</option>
                  {addresses.map((address) => (
                    <option key={address.id} value={address.id}>
                      {address.label || `${address.address}, ${address.city}`} ({address.country})
                    </option>
                  ))}
                </Select>
              </Field>
            )}
            <div className="grid gap-5 sm:grid-cols-2">
              <Field label="First name" htmlFor="firstName" required error={errors.firstName}>
                <Input id="firstName" autoComplete="given-name" value={form.firstName} onChange={set("firstName")} aria-invalid={Boolean(errors.firstName)} aria-required />
              </Field>
              <Field label="Last name" htmlFor="lastName" required error={errors.lastName}>
                <Input id="lastName" autoComplete="family-name" value={form.lastName} onChange={set("lastName")} aria-invalid={Boolean(errors.lastName)} aria-required />
              </Field>
            </div>
            <Field label="Email" htmlFor="email" required error={errors.email} hint="Order updates land here.">
              <Input id="email" type="email" autoComplete="email" value={form.email} onChange={set("email")} aria-invalid={Boolean(errors.email)} aria-required />
            </Field>
            <Field label="Street address" htmlFor="address" required error={errors.address}>
              <Input id="address" autoComplete="street-address" value={form.address} onChange={set("address")} aria-invalid={Boolean(errors.address)} aria-required />
            </Field>
            <div className="grid gap-5 sm:grid-cols-3">
              <Field label="City" htmlFor="city" required error={errors.city}>
                <Input id="city" autoComplete="address-level2" value={form.city} onChange={set("city")} aria-invalid={Boolean(errors.city)} aria-required />
              </Field>
              <Field label="Postal code" htmlFor="postalCode" required error={errors.postalCode}>
                <Input id="postalCode" autoComplete="postal-code" value={form.postalCode} onChange={set("postalCode")} aria-invalid={Boolean(errors.postalCode)} aria-required />
              </Field>
              <Field label="Country" htmlFor="country" required>
                <Select id="country" autoComplete="country-name" value={form.country} onChange={set("country")}>
                  {COUNTRIES.map((country) => (
                    <option key={country}>{country}</option>
                  ))}
                </Select>
              </Field>
            </div>
          </fieldset>

          <fieldset className="space-y-4">
            <legend className="font-display text-2xl text-ink dark:text-linen">Delivery Method</legend>
            {(
              [
                ["standard", "Standard — carbon neutral", displaySubtotal >= FREE_SHIPPING_THRESHOLD ? "Complimentary" : format(STANDARD_SHIPPING), "2-5 business days"],
                ["express", "Express courier", format(18), "1-2 business days"],
              ] as const
            ).map(([id, label, price, eta]) => (
              <label
                key={id}
                className={cn(
                  "flex cursor-pointer items-center justify-between gap-4 border p-4 transition-all",
                  method === id
                    ? "border-ink bg-cream ring-1 ring-ink dark:border-linen dark:bg-nox2 dark:ring-linen"
                    : "border-line hover:border-ink/50 dark:border-line-dark"
                )}
              >
                <span className="flex items-center gap-3">
                  <input
                    type="radio"
                    name="delivery-method"
                    checked={method === id}
                    onChange={() => setMethod(id)}
                    className="h-4 w-4 accent-bronze"
                  />
                  <span>
                    <span className="block text-sm font-bold text-ink dark:text-linen">{label}</span>
                    <span className="text-xs text-smoke dark:text-linen-dim">{eta}</span>
                  </span>
                </span>
                <span className="text-sm font-semibold text-ink dark:text-linen">{price}</span>
              </label>
            ))}
          </fieldset>

          <fieldset className="space-y-5">
            <legend className="font-display text-2xl text-ink dark:text-linen">Payment</legend>
            <p className="flex items-start gap-2 border border-line bg-fog/60 p-3 text-xs leading-relaxed text-smoke dark:border-line-dark dark:bg-nox2 dark:text-linen-dim">
              <Info className="mt-0.5 h-4 w-4 shrink-0 text-bronze" aria-hidden />
              {requireCard
                ? "Demo gateway — use 4242 4242 4242 4242 to succeed. Any card ending in 0000 simulates a decline."
                : intended === "sslcommerz"
                  ? "Bangladesh orders are charged through SSLCOMMERZ. You will continue to the secure payment page."
                  : "International orders are charged through Stripe. You will continue to the secure payment page."}
            </p>
            {requireCard && (
              <>
                <Field label="Name on card" htmlFor="cardName" required error={errors.cardName}>
                  <Input id="cardName" autoComplete="cc-name" value={form.cardName} onChange={set("cardName")} aria-invalid={Boolean(errors.cardName)} aria-required />
                </Field>
                <Field label="Card number" htmlFor="cardNumber" required error={errors.cardNumber}>
                  <div className="relative">
                    <Input id="cardNumber" inputMode="numeric" autoComplete="cc-number" placeholder="4242 4242 4242 4242" value={form.cardNumber} onChange={set("cardNumber")} aria-invalid={Boolean(errors.cardNumber)} aria-required className="pr-11" />
                    <CreditCard className="absolute top-1/2 right-4 h-4 w-4 -translate-y-1/2 text-smoke" aria-hidden />
                  </div>
                </Field>
                <div className="grid gap-5 grid-cols-2">
                  <Field label="Expiry (MM/YY)" htmlFor="expiry" required error={errors.expiry}>
                    <Input id="expiry" inputMode="numeric" autoComplete="cc-exp" placeholder="MM/YY" value={form.expiry} onChange={set("expiry")} aria-invalid={Boolean(errors.expiry)} aria-required />
                  </Field>
                  <Field label="CVC" htmlFor="cvc" required error={errors.cvc}>
                    <Input id="cvc" inputMode="numeric" autoComplete="cc-csc" placeholder="123" value={form.cvc} onChange={set("cvc")} aria-invalid={Boolean(errors.cvc)} aria-required />
                  </Field>
                </div>
              </>
            )}
          </fieldset>
        </div>

        <aside className="h-fit space-y-5 border border-line bg-cream p-6 sm:p-8 lg:sticky lg:top-32 dark:border-line-dark dark:bg-nox2" aria-label="Order summary">
          <h2 className="text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">
            Order Summary ({lines.length})
          </h2>
          <ul className="max-h-64 space-y-4 overflow-y-auto pr-1">
            {lines.map(({ line, product }) => {
              if (!product) return null;
              return (
                <li key={line.id} className="flex items-center gap-3">
                  <img
                    src={product.images[0]}
                    alt={product.alt}
                    width={120}
                    height={160}
                    loading="lazy"
                    decoding="async"
                    className="h-16 w-12 shrink-0 bg-fog object-cover dark:bg-nox3"
                  />
                  <span className="min-w-0 flex-1">
                    <span className="block truncate text-sm font-semibold text-ink dark:text-linen">{product.name}</span>
                    <span className="text-xs text-smoke dark:text-linen-dim">Size {line.size} · Qty {line.qty}</span>
                  </span>
                  <Price amount={(line.lineTotal ?? product.price * line.qty)} className="shrink-0" />
                </li>
              );
            })}
          </ul>
          <dl className="space-y-3 border-y border-line py-4 text-sm dark:border-line-dark">
            <div className="flex justify-between">
              <dt className="text-smoke dark:text-linen-dim">Subtotal</dt>
              <dd className="font-semibold text-ink dark:text-linen">{format(displaySubtotal)}</dd>
            </div>
            {discount > 0 && (
              <div className="flex justify-between text-bronze">
                <dt>Member code</dt>
                <dd className="font-semibold">−{format(discount)}</dd>
              </div>
            )}
            <div className="flex justify-between">
              <dt className="text-smoke dark:text-linen-dim">Shipping</dt>
              <dd className="font-semibold text-ink dark:text-linen">{shippingCost === 0 ? "Complimentary" : format(shippingCost)}</dd>
            </div>
          </dl>
          <div className="flex items-baseline justify-between">
            <span className="text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">Total</span>
            <Price amount={total} large />
          </div>
          <Button type="submit" size="lg" className="w-full" icon={Lock} loading={processing}>
            {payLabel}
          </Button>
          <p className="text-center text-[10px] tracking-[0.1em] uppercase text-smoke/80 dark:text-linen-dim/80">
            256-bit encrypted · No card details stored
          </p>
          <Link
            to="/cart"
            className="block text-center text-[11px] font-bold tracking-[0.16em] uppercase text-smoke underline-offset-4 transition-colors hover:text-ink hover:underline dark:text-linen-dim dark:hover:text-linen"
          >
            ← Back to bag
          </Link>
        </aside>
      </form>
    </div>
  );
}
