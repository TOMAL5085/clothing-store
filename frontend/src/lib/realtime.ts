import Echo from "laravel-echo";
import Pusher from "pusher-js";
import { authToken, hasApiToken } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";
import { useAdminNotificationStore, useNotificationStore } from "@/store/notificationStore";

const USER_CHANNEL_PREFIX = "App.Models.User.";
const ADMIN_CHANNEL = "admin.notifications";
const EVENT_NAME = ".notification.created";

let echo: Echo<"reverb"> | null = null;
let subscribedUserId: number | null = null;
let subscribedAdmin = false;

function apiOrigin() {
  const apiUrl = (import.meta.env.VITE_API_URL ?? "http://localhost:8000/api/v1").replace(/\/$/, "");
  return apiUrl.replace(/\/api\/v1\/?$/, "");
}

function broadcastConfig() {
  return {
    key: (import.meta.env.VITE_REVERB_APP_KEY as string | undefined) ?? "local-reverb-key",
    wsHost: (import.meta.env.VITE_REVERB_HOST as string | undefined) ?? "127.0.0.1",
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 6001),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 6001),
    forceTLS: ((import.meta.env.VITE_REVERB_SCHEME as string | undefined) ?? "http") === "https",
    authEndpoint:
      (import.meta.env.VITE_BROADCAST_AUTH_ENDPOINT as string | undefined) ?? `${apiOrigin()}/broadcasting/auth`,
  };
}

export function isRealtimeEnabled(): boolean {
  return ((import.meta.env.VITE_REALTIME_ENABLED as string | undefined) ?? "true") !== "false";
}

/**
 * Subscribe the current user to their private notification channel (plus
 * the admin channel for admins). Safe to call repeatedly: it no-ops when
 * the subscription already matches, and it never throws — if the socket
 * server is offline the app keeps working on plain HTTP.
 */
export function connectRealtime(): void {
  if (!isRealtimeEnabled() || typeof window === "undefined") return;

  const user = useAuthStore.getState().user;
  const userId = typeof user?.id === "number" ? user.id : null;
  const wantsAdmin = user?.role === "admin";

  if (!userId || !hasApiToken()) {
    disconnectRealtime();
    return;
  }

  if (echo && subscribedUserId === userId && subscribedAdmin === wantsAdmin) return;
  disconnectRealtime();

  try {
    const config = broadcastConfig();
    const token = authToken();

    echo = new Echo({
      broadcaster: "reverb",
      Pusher,
      key: config.key,
      wsHost: config.wsHost,
      wsPort: config.wsPort,
      wssPort: config.wssPort,
      forceTLS: config.forceTLS,
      disableStats: true,
      enabledTransports: ["ws", "wss"],
      authEndpoint: config.authEndpoint,
      auth: {
        headers: {
          Authorization: `Bearer ${token ?? ""}`,
          Accept: "application/json",
        },
      },
    });
    subscribedUserId = userId;
    subscribedAdmin = wantsAdmin;

    echo.private(`${USER_CHANNEL_PREFIX}${userId}`).listen(EVENT_NAME, () => {
      const store = useNotificationStore.getState();
      void store.fetchUnreadCount();
      if (window.location.pathname === "/notifications") {
        void store.fetchNotifications(1).catch(() => undefined);
      }
    });

    if (wantsAdmin) {
      echo.private(ADMIN_CHANNEL).listen(EVENT_NAME, () => {
        void useAdminNotificationStore.getState().fetchUnreadCount();
      });
    }
  } catch {
    disconnectRealtime();
  }
}

export function disconnectRealtime(): void {
  try {
    echo?.disconnect();
  } catch {
    // Socket teardown must never break logout or account switching.
  }
  echo = null;
  subscribedUserId = null;
  subscribedAdmin = false;
}
