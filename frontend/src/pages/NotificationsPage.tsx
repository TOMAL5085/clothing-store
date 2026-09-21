import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Bell, Check, ChevronRight, X, Mail, Truck, RotateCcw, DollarSign, ShieldAlert, Package, AlertCircle, Clock } from "lucide-react";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";
import { useNotificationStore, type Notification } from "@/store/notificationStore";
import { useUiStore } from "@/store/uiStore";
import { usePageTitle } from "@/utils/usePageTitle";
import { Button, EmptyState, Skeleton } from "@/components/ui/primitives";
import { cn, formatDate } from "@/utils/cn";

const categoryIcons: Record<string, typeof Bell> = {
  order_placed: Package,
  order_paid: DollarSign,
  order_payment_failed: AlertCircle,
  order_status_changed: Truck,
  shipment_status_changed: Truck,
  cancellation_requested: RotateCcw,
  cancellation_approved: Check,
  cancellation_rejected: X,
  cancellation_completed: ShieldAlert,
  return_requested: RotateCcw,
  return_approved: Check,
  return_rejected: X,
  return_received: Package,
  refund_created: DollarSign,
  refund_processing: Clock,
  refund_completed: Check,
  refund_failed: AlertCircle,
  admin_new_order: Package,
  admin_order_paid: DollarSign,
  admin_cancellation_requested: RotateCcw,
  admin_return_requested: RotateCcw,
  admin_refund_action_required: DollarSign,
  admin_shipment_problem: Truck,
};

const categoryLabels: Record<string, string> = {
  order_placed: "Order Placed",
  order_paid: "Payment Confirmed",
  order_payment_failed: "Payment Failed",
  order_status_changed: "Status Updated",
  shipment_status_changed: "Shipment Update",
  cancellation_requested: "Cancellation Requested",
  cancellation_approved: "Cancellation Approved",
  cancellation_rejected: "Cancellation Rejected",
  cancellation_completed: "Order Cancelled",
  return_requested: "Return Requested",
  return_approved: "Return Approved",
  return_rejected: "Return Rejected",
  return_received: "Return Received",
  refund_created: "Refund Initiated",
  refund_processing: "Refund Processing",
  refund_completed: "Refund Completed",
  refund_failed: "Refund Failed",
  admin_new_order: "New Order",
  admin_order_paid: "Payment Received",
  admin_cancellation_requested: "Cancellation Request",
  admin_return_requested: "Return Request",
  admin_refund_action_required: "Refund Action Required",
  admin_shipment_problem: "Shipment Issue",
};

export default function NotificationsPage() {
  usePageTitle("Notifications");
  const { user } = useAuthStore();
  const { notifications, unreadCount, loading, fetchNotifications, markAsRead, markAllAsRead, fetchUnreadCount } = useNotificationStore();
  const pushToast = useUiStore((s) => s.pushToast);
  const navigate = useNavigate();
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);

  useEffect(() => {
    if (user) {
      fetchNotifications(1);
      fetchUnreadCount();
    }
  }, [user, fetchNotifications, fetchUnreadCount]);

  const handleMarkAsRead = async (id: string, actionUrl: string | null) => {
    await markAsRead(id);
    if (actionUrl) {
      navigate(actionUrl);
    }
  };

  const handleMarkAllAsRead = async () => {
    await markAllAsRead();
    pushToast("All notifications marked as read");
  };

  const loadMore = async () => {
    const nextPage = page + 1;
    try {
      const response = await api<{ data: Notification[]; meta: { current_page: number; last_page: number } }>(
        `/notifications?page=${nextPage}&per_page=20`
      );
      // Use the store's internal method to add notifications
      useNotificationStore.setState((state) => ({
        notifications: [...state.notifications, ...response.data],
      }));
      setPage(nextPage);
      setHasMore(nextPage < response.meta.last_page);
    } catch (error) {
      console.error("Failed to load more notifications:", error);
    }
  };

  if (!user) return null;

  return (
    <div className="mx-auto max-w-[1200px] px-4 py-10 sm:px-6 lg:py-16">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">JAAJ Members</p>
          <h1 className="mt-2 font-display text-4xl text-ink sm:text-5xl dark:text-linen">Notifications</h1>
        </div>
        {unreadCount > 0 && (
          <Button variant="outline" icon={Check} onClick={handleMarkAllAsRead}>
            Mark All as Read
          </Button>
        )}
      </div>

      {loading && notifications.length === 0 && (
        <div className="mt-8 space-y-4">
          {[1, 2, 3].map((i) => (
            <Skeleton key={i} className="h-20 w-full" />
          ))}
        </div>
      )}

      {!loading && notifications.length === 0 && (
        <div className="mt-8 border border-line bg-cream dark:border-line-dark dark:bg-nox2">
          <EmptyState icon={Bell} title="No notifications yet" body="When you have order updates, they'll appear here." />
        </div>
      )}

      {!loading && notifications.length > 0 && (
        <div className="mt-8 space-y-4">
          {notifications.map((notification) => {
            const Icon = categoryIcons[notification.category] || Bell;
            const label = categoryLabels[notification.category] || notification.category;
            const isUnread = !notification.isRead;

            return (
              <Link
                key={notification.id}
                to={notification.actionUrl ?? "/account?tab=orders"}
                onClick={(e) => {
                  if (!notification.isRead) {
                    e.preventDefault();
                    handleMarkAsRead(notification.id, notification.actionUrl);
                  }
                }}
                className={cn(
                  "group relative flex items-start gap-4 p-5 border transition-colors",
                  isUnread
                    ? "border-bronze bg-bronze/5 dark:border-bronze/30 dark:bg-bronze/5"
                    : "border-line bg-cream dark:border-line-dark dark:bg-nox2",
                  "hover:border-ink/50 dark:hover:border-linen/50"
                )}
              >
                <div
                  className={cn(
                    "flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-lg",
                    isUnread ? "bg-bronze/10 dark:bg-bronze/10" : "bg-fog dark:bg-nox3"
                  )}
                >
                  <Icon className="h-5 w-5 text-ink dark:text-linen" aria-hidden />
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-start justify-between gap-3">
                    <p className={cn("font-semibold", isUnread ? "text-ink dark:text-linen" : "text-ink/80 dark:text-linen/80")}>
                      {notification.title}
                    </p>
                    <span className="text-xs text-smoke dark:text-linen-dim whitespace-nowrap">
                      {notification.createdAt ? formatDate(notification.createdAt) : ""}
                    </span>
                  </div>
                  <p className="mt-1 text-sm text-smoke dark:text-linen-dim line-clamp-2">{notification.message}</p>
                  <div className="mt-2 flex items-center gap-2">
                    <span className={cn("text-[10px] font-bold tracking-[0.14em] uppercase px-2 py-0.5 rounded", isUnread ? "bg-bronze/10 text-bronze dark:bg-bronze/10 dark:text-bronze" : "bg-fog text-smoke dark:bg-nox3 dark:text-linen-dim")}>
                      {label}
                    </span>
                    {notification.orderNumber && (
                      <span className="text-xs text-smoke dark:text-linen-dim font-mono">
                        #{notification.orderNumber}
                      </span>
                    )}
                  </div>
                  {isUnread && (
                    <span className="absolute right-4 top-4 h-2.5 w-2.5 rounded-full bg-bronze" aria-label="Unread" />
                  )}
                </div>
              </Link>
            );
          })}

          {hasMore && (
            <div className="mt-4 text-center">
              <Button variant="outline" onClick={loadMore} disabled={loading}>
                Load More
              </Button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

// Helper to extend zustand set with proper typing
function set(fn: (state: any) => any) {
  // This is just a placeholder for the type system
  return fn;
}