import { create } from "zustand";
import { persist } from "zustand/middleware";
import { api } from "@/lib/api";

export interface Notification {
  id: string;
  type: string;
  title: string;
  message: string;
  category: string;
  orderId: string | null;
  orderNumber: string | null;
  customerName: string | null;
  customerEmail: string | null;
  actionUrl: string | null;
  readAt: string | null;
  createdAt: string | null;
  isRead: boolean;
}

interface NotificationCollection {
  data: Notification[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

interface NotificationState {
  notifications: Notification[];
  unreadCount: number;
  loading: boolean;
  fetchNotifications: (page?: number) => Promise<void>;
  fetchUnreadCount: () => Promise<void>;
  markAsRead: (id: string) => Promise<void>;
  markAllAsRead: () => Promise<void>;
}

export const useNotificationStore = create<NotificationState>()(
  persist(
    (set, get) => ({
      notifications: [],
      unreadCount: 0,
      loading: false,

      fetchNotifications: async (page = 1) => {
        set({ loading: true });
        try {
          const response = await api<NotificationCollection>(`/notifications?page=${page}&per_page=20`);
          set({ notifications: response.data, loading: false });
        } catch (error) {
          set({ loading: false });
          throw error;
        }
      },

      fetchUnreadCount: async () => {
        try {
          const response = await api<{ unread_count: number }>('/notifications/unread-count');
          set({ unreadCount: response.unread_count });
        } catch (error) {
          console.error('Failed to fetch unread count:', error);
        }
      },

      markAsRead: async (id: string) => {
        try {
          await api(`/notifications/${id}/read`, { method: 'POST' });
          set((state) => ({
            notifications: state.notifications.map((n) =>
              n.id === id ? { ...n, isRead: true, readAt: new Date().toISOString() } : n
            ),
            unreadCount: Math.max(0, state.unreadCount - 1),
          }));
        } catch (error) {
          console.error('Failed to mark as read:', error);
          throw error;
        }
      },

      markAllAsRead: async () => {
        try {
          await api('/notifications/mark-all-read', { method: 'POST' });
          set((state) => ({
            notifications: state.notifications.map((n) => ({ ...n, isRead: true, readAt: new Date().toISOString() })),
            unreadCount: 0,
          }));
        } catch (error) {
          console.error('Failed to mark all as read:', error);
          throw error;
        }
      },
    }),
    { name: "jaaj-notifications", partialize: (state) => ({ unreadCount: state.unreadCount }) },
  )
);

// Admin notification store
interface AdminNotificationState {
  notifications: Notification[];
  unreadCount: number;
  loading: boolean;
  fetchNotifications: (page?: number) => Promise<void>;
  fetchUnreadCount: () => Promise<void>;
  markAsRead: (id: string) => Promise<void>;
  markAllAsRead: () => Promise<void>;
}

export const useAdminNotificationStore = create<AdminNotificationState>()(
  (set, get) => ({
    notifications: [],
    unreadCount: 0,
    loading: false,

    fetchNotifications: async (page = 1) => {
      set({ loading: true });
      try {
        const response = await api<NotificationCollection>(`/admin/notifications?page=${page}&per_page=20`);
        set({ notifications: response.data, loading: false });
      } catch (error) {
        set({ loading: false });
        throw error;
      }
    },

    fetchUnreadCount: async () => {
      try {
        const response = await api<{ unread_count: number }>('/admin/notifications/unread-count');
        set({ unreadCount: response.unread_count });
      } catch (error) {
        console.error('Failed to fetch admin unread count:', error);
      }
    },

    markAsRead: async (id: string) => {
      try {
        await api(`/admin/notifications/${id}/read`, { method: 'POST' });
        set((state) => ({
          notifications: state.notifications.map((n) =>
            n.id === id ? { ...n, isRead: true, readAt: new Date().toISOString() } : n
          ),
          unreadCount: Math.max(0, state.unreadCount - 1),
        }));
      } catch (error) {
        console.error('Failed to mark admin notification as read:', error);
        throw error;
      }
    },

    markAllAsRead: async () => {
      try {
        await api('/admin/notifications/mark-all-read', { method: 'POST' });
        set((state) => ({
          notifications: state.notifications.map((n) => ({ ...n, isRead: true, readAt: new Date().toISOString() })),
          unreadCount: 0,
        }));
      } catch (error) {
        console.error('Failed to mark all admin notifications as read:', error);
        throw error;
      }
    },
  })
);