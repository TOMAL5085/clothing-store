import { create } from "zustand";
import { persist } from "zustand/middleware";
import { api, hasApiToken, setApiToken } from "@/lib/api";

export interface AuthUser {
  id?: number;
  name: string;
  email: string;
  phone?: string | null;
  role?: "admin" | "customer" | string;
  status?: "active" | "inactive" | string;
  emailVerified?: boolean;
  emailVerifiedAt?: string | null;
  joinedAt: string;
  ordersCount?: number;
  addressesCount?: number;
}

export interface Address {
  id: number;
  label?: string | null;
  firstName: string;
  lastName: string;
  email: string;
  phone?: string | null;
  address: string;
  city: string;
  postalCode: string;
  country: string;
  isDefault: boolean;
}

export interface AddressPayload {
  label?: string;
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
  address: string;
  city: string;
  postalCode: string;
  country: string;
  isDefault?: boolean;
}

interface AuthState {
  user: AuthUser | null;
  addresses: Address[];
  status: "idle" | "loading";
  refreshUser: () => Promise<void>;
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string, phone?: string) => Promise<string | null>;
  logout: () => void;
  updateProfile: (payload: { name: string; email: string; phone?: string }) => Promise<string | null>;
  updatePassword: (payload: { currentPassword: string; password: string; passwordConfirmation: string }) => Promise<void>;
  requestOtp: () => Promise<string | null>;
  verifyOtp: (code: string) => Promise<void>;
  loadAddresses: () => Promise<void>;
  saveAddress: (payload: AddressPayload, id?: number) => Promise<void>;
  deleteAddress: (id: number) => Promise<void>;
}

interface AuthResponse {
  data: {
    user: AuthUser;
    token: string;
    debugOtp?: string | null;
  };
}

interface UserResponse {
  data: AuthUser;
  debugOtp?: string | null;
}

interface AddressCollection {
  data: Address[];
}

interface AddressResponse {
  data: Address;
}

interface OtpResponse {
  message: string;
  debugOtp?: string | null;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      addresses: [],
      status: "idle",
      refreshUser: async () => {
        if (!hasApiToken()) return;
        try {
          const response = await api<UserResponse>("/auth/me");
          set({ user: response.data });
        } catch (error) {
          setApiToken(null);
          set({ user: null, addresses: [] });
          throw error;
        }
      },
      login: async (email, password) => {
        set({ status: "loading" });
        try {
          const response = await api<AuthResponse>("/auth/login", {
            method: "POST",
            body: JSON.stringify({ email, password }),
          });
          setApiToken(response.data.token);
          set({ user: response.data.user, status: "idle" });
        } catch (error) {
          set({ status: "idle" });
          throw error;
        }
      },
      register: async (name, email, password, phone) => {
        set({ status: "loading" });
        try {
          const response = await api<AuthResponse>("/auth/register", {
            method: "POST",
            body: JSON.stringify({ name, email, phone, password, password_confirmation: password }),
          });
          setApiToken(response.data.token);
          set({ user: response.data.user, status: "idle" });
          return response.data.debugOtp ?? null;
        } catch (error) {
          set({ status: "idle" });
          throw error;
        }
      },
      logout: () => {
        void api("/auth/logout", { method: "POST" }).catch(() => undefined);
        setApiToken(null);
        set({ user: null, addresses: [] });
      },
      updateProfile: async (payload) => {
        const response = await api<UserResponse>("/profile", {
          method: "PUT",
          body: JSON.stringify(payload),
        });
        set({ user: response.data });
        return response.debugOtp ?? null;
      },
      updatePassword: async (payload) => {
        await api("/profile/password", {
          method: "PUT",
          body: JSON.stringify({
            current_password: payload.currentPassword,
            password: payload.password,
            password_confirmation: payload.passwordConfirmation,
          }),
        });
      },
      requestOtp: async () => {
        const response = await api<OtpResponse>("/auth/otp", { method: "POST" });
        return response.debugOtp ?? null;
      },
      verifyOtp: async (code) => {
        const response = await api<UserResponse>("/auth/otp/verify", {
          method: "POST",
          body: JSON.stringify({ code }),
        });
        set({ user: response.data });
      },
      loadAddresses: async () => {
        const response = await api<AddressCollection>("/addresses");
        set({ addresses: response.data });
      },
      saveAddress: async (payload, id) => {
        const response = await api<AddressResponse>(id ? `/addresses/${id}` : "/addresses", {
          method: id ? "PUT" : "POST",
          body: JSON.stringify(payload),
        });
        const saved = response.data;
        set((state) => ({
          addresses: id
            ? state.addresses.map((address) => (address.id === id ? saved : address))
            : [saved, ...state.addresses],
        }));
        await get().loadAddresses();
      },
      deleteAddress: async (id) => {
        await api(`/addresses/${id}`, { method: "DELETE" });
        set((state) => ({ addresses: state.addresses.filter((address) => address.id !== id) }));
      },
    }),
    { name: "jaaj-auth", partialize: (state) => ({ user: state.user }) },
  ),
);
