import { create } from "zustand";
import { persist } from "zustand/middleware";
import { api } from "@/lib/api";

export interface Toast {
  id: number;
  message: string;
}

interface UiState {
  // Wishlist (persisted)
  wishlist: string[];
  loadWishlist: () => Promise<void>;
  toggleWishlist: (productId: string) => void;
  removeFromWishlist: (productId: string) => void;
  // Overlays & chrome (ephemeral)
  miniCartOpen: boolean;
  setMiniCartOpen: (open: boolean) => void;
  searchOpen: boolean;
  setSearchOpen: (open: boolean) => void;
  mobileNavOpen: boolean;
  setMobileNavOpen: (open: boolean) => void;
  toggleMobileNav: () => void;
  // Toasts (ephemeral)
  toasts: Toast[];
  pushToast: (message: string, durationMs?: number) => void;
  dismissToast: (id: number) => void;
}

let toastId = 0;
export const DEFAULT_TOAST_MS = 3200;
export const LONG_TOAST_MS = 5 * 60 * 1000;
const wishlistTokenKey = "jaaj-wishlist-token";

interface WishlistResponse {
  data: {
    token?: string;
    ids: string[];
  };
}

function rememberWishlistToken(response: WishlistResponse) {
  if (response.data.token) localStorage.setItem(wishlistTokenKey, response.data.token);
}

async function syncWishlist(productId: string, adding: boolean) {
  const response = await api<WishlistResponse>(adding ? "/wishlist/items" : `/wishlist/items/${encodeURIComponent(productId)}`, {
    method: adding ? "POST" : "DELETE",
    body: JSON.stringify(adding
      ? { product_id: productId, wishlist_token: localStorage.getItem(wishlistTokenKey) }
      : { wishlist_token: localStorage.getItem(wishlistTokenKey) }),
  }).catch(() => undefined);
  if (response) rememberWishlistToken(response);
}

export const useUiStore = create<UiState>()(
  persist(
    (set, get) => ({
      wishlist: [],
      loadWishlist: async () => {
        const token = localStorage.getItem(wishlistTokenKey);
        const response = await api<WishlistResponse>(`/wishlist${token ? `?wishlist_token=${encodeURIComponent(token)}` : ""}`);
        rememberWishlistToken(response);
        set({ wishlist: response.data.ids });
      },
      toggleWishlist: (productId) =>
        set((state) => {
          const adding = !state.wishlist.includes(productId);
          void syncWishlist(productId, adding);
          return {
            wishlist: adding
              ? [...state.wishlist, productId]
              : state.wishlist.filter((id) => id !== productId),
          };
        }),
      removeFromWishlist: (productId) => {
        void syncWishlist(productId, false);
        set((state) => ({
          wishlist: state.wishlist.filter((id) => id !== productId),
        }));
      },

      miniCartOpen: false,
      setMiniCartOpen: (open) => set({ miniCartOpen: open }),
      searchOpen: false,
      setSearchOpen: (open) => set({ searchOpen: open }),
      mobileNavOpen: false,
      setMobileNavOpen: (open) => set({ mobileNavOpen: open }),
      toggleMobileNav: () =>
        set((state) => ({ mobileNavOpen: !state.mobileNavOpen })),

      toasts: [],
      pushToast: (message, durationMs = DEFAULT_TOAST_MS) => {
        const id = ++toastId;
        set((state) => ({ toasts: [...state.toasts, { id, message }] }));
        window.setTimeout(() => get().dismissToast(id), durationMs);
      },
      dismissToast: (id) =>
        set((state) => ({
          toasts: state.toasts.filter((toast) => toast.id !== id),
        })),
    }),
    {
      name: "jaaj-ui",
      partialize: (state) => ({ wishlist: state.wishlist }),
    },
  ),
);

export const useUIStore = useUiStore;
