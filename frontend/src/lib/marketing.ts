import { api } from "@/lib/api";

export interface ConsentState {
  analytics: boolean;
  marketing: boolean;
}

export interface AttributionState {
  utm_source?: string;
  utm_medium?: string;
  utm_campaign?: string;
  utm_term?: string;
  utm_content?: string;
  gclid?: string;
  gbraid?: string;
  wbraid?: string;
  fbclid?: string;
  landing_url?: string;
  referrer?: string;
}

const ANON_KEY = "jaaj-anon-id";
const SESSION_KEY = "jaaj-session-id";
const CONSENT_KEY = "jaaj-consent";
const ATTRIBUTION_KEY = "jaaj-attribution";

const CLICK_IDS = ["gclid", "gbraid", "wbraid", "fbclid"] as const;
const UTM_KEYS = ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content"] as const;

function randomId(): string {
  if (typeof crypto !== "undefined" && "randomUUID" in crypto) {
    return crypto.randomUUID();
  }
  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 18)}`;
}

function readStorage(key: string, session = false): string | null {
  try {
    const storage = session ? window.sessionStorage : window.localStorage;
    return storage.getItem(key);
  } catch {
    return null;
  }
}

function writeStorage(key: string, value: string, session = false): void {
  try {
    const storage = session ? window.sessionStorage : window.localStorage;
    storage.setItem(key, value);
  } catch {
    // Private-mode storage failures must never break shopping.
  }
}

export function getAnonymousId(): string {
  let id = readStorage(ANON_KEY);
  if (!id) {
    id = randomId();
    writeStorage(ANON_KEY, id);
  }
  return id;
}

export function getSessionId(): string {
  let id = readStorage(SESSION_KEY, true);
  if (!id) {
    id = randomId();
    writeStorage(SESSION_KEY, id, true);
  }
  return id;
}

export function getConsent(): ConsentState {
  try {
    const raw = readStorage(CONSENT_KEY);
    if (!raw) return { analytics: false, marketing: false };
    const parsed = JSON.parse(raw) as Partial<ConsentState>;
    return { analytics: parsed.analytics === true, marketing: parsed.marketing === true };
  } catch {
    return { analytics: false, marketing: false };
  }
}

export function hasConsentRecord(): boolean {
  return readStorage(CONSENT_KEY) !== null;
}

export function setConsent(consent: ConsentState): void {
  writeStorage(CONSENT_KEY, JSON.stringify({ ...consent, decided_at: new Date().toISOString() }));
}

/**
 * Capture first-party attribution from the current URL once per session.
 * UTM parameters and click IDs are kept; everything else in the query
 * string is ignored and never persisted.
 */
export function captureAttribution(): AttributionState | undefined {
  if (typeof window === "undefined") return undefined;
  const params = new URLSearchParams(window.location.search);
  const attribution: AttributionState = {};

  for (const key of UTM_KEYS) {
    const value = params.get(key)?.trim();
    if (value) attribution[key] = value.slice(0, 200);
  }
  for (const key of CLICK_IDS) {
    const value = params.get(key)?.trim();
    if (value && /^[A-Za-z0-9_\-]+$/.test(value)) attribution[key] = value.slice(0, 200);
  }
  if (Object.keys(attribution).length > 0) {
    attribution.landing_url = window.location.href.split("#")[0].slice(0, 1000);
    if (document.referrer) attribution.referrer = document.referrer.slice(0, 1000);
    writeStorage(ATTRIBUTION_KEY, JSON.stringify(attribution), true);
    return attribution;
  }

  try {
    const raw = readStorage(ATTRIBUTION_KEY, true);
    if (raw) return JSON.parse(raw) as AttributionState;
  } catch {
    // Corrupt session data is simply ignored.
  }
  return undefined;
}

interface TrackFields {
  product_external_id?: string;
  slug?: string;
  currency?: string;
  value?: number;
  metadata?: Record<string, string | number>;
}

let lastPageView = "";

function postEvent(eventName: string, fields: TrackFields = {}): void {
  const payload: Record<string, unknown> = {
    event_id: randomId(),
    event_name: eventName,
    anonymous_id: getAnonymousId(),
    session_id: getSessionId(),
    consent: getConsent(),
  };

  const attribution = captureAttribution();
  if (attribution) payload.attribution = attribution;
  if (fields.product_external_id) payload.product_external_id = fields.product_external_id;
  if (fields.slug !== undefined || fields.currency !== undefined || fields.value !== undefined || fields.metadata) {
    payload.metadata = {
      ...(fields.slug !== undefined ? { slug: fields.slug } : {}),
      ...(fields.currency !== undefined ? { currency: fields.currency } : {}),
      ...(fields.metadata ?? {}),
    };
  }
  if (fields.value !== undefined) payload.value = fields.value;
  if (fields.currency !== undefined) payload.currency = fields.currency;

  // Fire-and-forget: tracking must never block UI or break shopping.
  void api("/marketing/events", {
    method: "POST",
    body: JSON.stringify(payload),
  }).catch(() => undefined);
}

export function trackPageView(path: string): void {
  if (path === lastPageView) return;
  lastPageView = path;
  postEvent("page_view", { metadata: { path: path.slice(0, 500) } });
}

export function trackProductView(input: { productId: string; slug?: string; category?: string; currency?: string }): void {
  postEvent("product_view", {
    product_external_id: input.productId,
    slug: input.slug,
    currency: input.currency,
    metadata: input.category ? { category: input.category } : undefined,
  });
}

export function trackSearch(query: string, resultCount: number): void {
  const trimmed = query.trim();
  if (!trimmed) return;
  postEvent("search", { metadata: { query: trimmed.slice(0, 200), result_count: resultCount } });
}

export function trackAddToCart(input: { productId: string; slug?: string; quantity?: number }): void {
  postEvent("add_to_cart", {
    product_external_id: input.productId,
    slug: input.slug,
    metadata: { quantity: input.quantity ?? 1 },
  });
}

export function trackRemoveFromCart(input: { productId: string; slug?: string; quantity?: number }): void {
  postEvent("remove_from_cart", {
    product_external_id: input.productId,
    slug: input.slug,
    metadata: { quantity: input.quantity ?? 1 },
  });
}

export function trackViewCart(itemCount: number): void {
  postEvent("view_cart", { metadata: { item_count: itemCount } });
}

export function trackBeginCheckout(itemCount: number): void {
  postEvent("begin_checkout", { metadata: { item_count: itemCount } });
}
