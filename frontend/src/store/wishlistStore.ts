import { create } from "zustand";

/** Wishlist store — scaffold. Persistence + drawer UI land in Part 3. */

interface WishlistState {
  ids: string[];
  toggle: (productId: string) => void;
  has: (productId: string) => boolean;
}

export const useWishlistStore = create<WishlistState>((set, get) => ({
  ids: [],
  toggle: (productId) =>
    set((state) => ({
      ids: state.ids.includes(productId)
        ? state.ids.filter((id) => id !== productId)
        : [...state.ids, productId],
    })),
  has: (productId) => get().ids.includes(productId),
}));
