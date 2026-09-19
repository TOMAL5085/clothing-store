import { create } from "zustand";
import { persist } from "zustand/middleware";
import { api, ApiError } from "@/lib/api";

export interface CartLine {
  id: string;
  legacyId?: string;
  databaseId?: number;
  productId: string;
  productName?: string;
  productSlug?: string;
  image?: string | null;
  size: string;
  color?: string | null;
  sku?: string | null;
  qty: number;
  quantity?: number;
  unitPrice?: number;
  lineTotal?: number;
  availableStock?: number;
  inStock?: boolean;
  addedAt: number;
}

export interface CartSummary {
  subtotal: number;
  discount: number;
  shipping: number;
  tax: number;
  total: number;
}

interface CartResponse {
  data: {
    token?: string;
    items: CartLine[];
    promo: string | null;
    coupon?: { code: string; type: string; value: number } | null;
    summary: CartSummary;
  };
}

interface CartState {
  items: CartLine[];
  promo: string | null;
  summary: CartSummary;
  loading: boolean;
  error: string | null;
  loadCart: () => Promise<void>;
  setPromo: (code: string | null) => Promise<void>;
  addItem: (productId: string, size: string, qty?: number, color?: string | null) => Promise<void>;
  removeItem: (lineId: string) => Promise<void>;
  setQty: (lineId: string, qty: number) => Promise<void>;
  clearCart: () => Promise<void>;
}

const cartTokenKey = "jaaj-cart-token";
const emptySummary: CartSummary = { subtotal: 0, discount: 0, shipping: 0, tax: 0, total: 0 };

function rememberToken(response: CartResponse) {
  const token = response.data.token;
  if (token) localStorage.setItem(cartTokenKey, token);
}

function withCartToken(payload: Record<string, unknown> = {}) {
  return { ...payload, cart_token: localStorage.getItem(cartTokenKey) };
}

function message(error: unknown) {
  if (error instanceof ApiError) {
    const first = Object.values(error.errors ?? {})[0]?.[0];
    return first ?? error.message;
  }
  return "Cart could not be updated.";
}

function normalize(response: CartResponse) {
  rememberToken(response);
  return {
    items: response.data.items.map((item) => ({ ...item, addedAt: item.addedAt ?? Date.now() })),
    promo: response.data.promo,
    summary: response.data.summary,
    error: null,
    loading: false,
  };
}

async function cartRequest(path: string, body?: Record<string, unknown>, method = "POST") {
  return api<CartResponse>(path, {
    method,
    body: body ? JSON.stringify(withCartToken(body)) : undefined,
  });
}

export function lineIdFor(productId: string, size: string) {
  return `${productId}__${size}`;
}

export const useCartStore = create<CartState>()(
  persist(
    (set) => ({
      items: [],
      promo: null,
      summary: emptySummary,
      loading: false,
      error: null,
      loadCart: async () => {
        set({ loading: true });
        try {
          const token = localStorage.getItem(cartTokenKey);
          const response = await api<CartResponse>(`/cart${token ? `?cart_token=${encodeURIComponent(token)}` : ""}`);
          set(normalize(response));
        } catch (error) {
          set({ loading: false, error: message(error) });
        }
      },
      setPromo: async (code) => {
        try {
          const response = await cartRequest("/cart/promo", { promo_code: code });
          set(normalize(response));
        } catch (error) {
          set({ error: message(error) });
          throw error;
        }
      },
      addItem: async (productId, size, qty = 1, color = null) => {
        try {
          const response = await cartRequest("/cart/items", { product_id: productId, size, color, quantity: qty });
          set(normalize(response));
        } catch (error) {
          set({ error: message(error) });
          throw error;
        }
      },
      removeItem: async (lineId) => {
        try {
          const response = await cartRequest(`/cart/items/${encodeURIComponent(lineId)}`, undefined, "DELETE");
          set(normalize(response));
        } catch (error) {
          set({ error: message(error) });
          throw error;
        }
      },
      setQty: async (lineId, qty) => {
        try {
          const response = await cartRequest(`/cart/items/${encodeURIComponent(lineId)}`, { quantity: qty }, "PATCH");
          set(normalize(response));
        } catch (error) {
          set({ error: message(error) });
          throw error;
        }
      },
      clearCart: async () => {
        try {
          await api("/cart", {
            method: "DELETE",
            body: JSON.stringify(withCartToken()),
          });
          set({ items: [], promo: null, summary: emptySummary, error: null });
        } catch (error) {
          set({ error: message(error) });
          throw error;
        }
      },
    }),
    {
      name: "jaaj-cart",
      partialize: (state) => ({ items: state.items, promo: state.promo, summary: state.summary }),
    },
  ),
);

export const selectCartCount = (state: CartState) =>
  state.items.reduce((total, item) => total + item.qty, 0);
