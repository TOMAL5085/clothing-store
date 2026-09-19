import { create } from "zustand";
import { persist } from "zustand/middleware";
import type { CartLine } from "./cartStore";

export interface OrderAddress {
  firstName: string;
  lastName: string;
  email: string;
  address: string;
  city: string;
  postalCode: string;
  country: string;
}

export interface Order {
  id: string;
  lines: CartLine[];
  subtotal: number;
  discount: number;
  shipping: number;
  total: number;
  address: OrderAddress;
  method: "standard" | "express";
  createdAt: string;
  status: "Processing" | "Shipped" | "Delivered";
  checkoutToken?: string | null;
  paymentStatus?: string;
}

interface OrderState {
  orders: Order[];
  lastOrderId: string | null;
  placeOrder: (order: Omit<Order, "id" | "createdAt" | "status">) => Order;
  addOrder: (order: Order) => void;
  getOrder: (id: string | null) => Order | undefined;
}

export const useOrderStore = create<OrderState>()(
  persist(
    (set, get) => ({
      orders: [],
      lastOrderId: null,
      placeOrder: (draft) => {
        const order: Order = {
          ...draft,
          id: `JAAJ-${String(Math.floor(100000 + Math.random() * 900000))}`,
          createdAt: new Date().toISOString(),
          status: "Processing",
        };
        set((state) => ({ orders: [order, ...state.orders], lastOrderId: order.id }));
        return order;
      },
      addOrder: (order) => set((state) => ({ orders: [order, ...state.orders.filter((entry) => entry.id !== order.id)], lastOrderId: order.id })),
      getOrder: (id) => get().orders.find((order) => order.id === id),
    }),
    { name: "jaaj-orders" }
  )
);
