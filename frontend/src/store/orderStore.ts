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

export interface OrderShipmentEvent {
  id: number;
  status: string;
  statusCode: string;
  location: string | null;
  description: string | null;
  occurredAt: string | null;
}

export interface OrderShipment {
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
  events?: OrderShipmentEvent[];
}

export interface OrderRefund {
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

export interface OrderCancellation {
  id: number;
  orderId: string;
  status: string;
  reason: string;
  adminReason?: string | null;
  requestedAt?: string | null;
  reviewedAt?: string | null;
  executedAt?: string | null;
  refund?: OrderRefund | null;
}

export interface OrderReturnItem {
  id: number;
  orderItemId: number;
  productName?: string | null;
  productId?: string | null;
  size?: string | null;
  quantity: number;
  resolutionStatus: string;
}

export interface OrderReturn {
  id: number;
  orderId: string;
  status: string;
  reason: string;
  adminReason?: string | null;
  requestedAt?: string | null;
  reviewedAt?: string | null;
  receivedAt?: string | null;
  resolvedAt?: string | null;
  items?: OrderReturnItem[];
  refund?: OrderRefund | null;
}

export interface Order {
  id: string;
  lines: Array<CartLine & { orderItemId?: number }>;
  subtotal: number;
  discount: number;
  shipping: number;
  total: number;
  address: OrderAddress;
  method: "standard" | "express";
  createdAt: string;
  status: "Processing" | "Shipped" | "Delivered" | "Pending" | "Confirmed" | "Cancelled";
  checkoutToken?: string | null;
  paymentStatus?: string;
  shipment?: OrderShipment | null;
  cancellation?: OrderCancellation | null;
  returns?: OrderReturn[];
  refunds?: OrderRefund[];
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
