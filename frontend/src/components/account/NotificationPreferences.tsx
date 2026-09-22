import { useEffect, useState } from "react";
import { BellRing } from "lucide-react";
import { api } from "@/lib/api";
import { useUiStore } from "@/store/uiStore";
import { EmptyState, Spinner } from "@/components/ui/primitives";
import { cn } from "@/utils/cn";

interface Preference {
  category: string;
  label: string;
  in_app_enabled: boolean;
  email_enabled: boolean;
  sms_enabled: boolean;
  whatsapp_enabled: boolean;
}

interface PreferenceCollection {
  data: Preference[];
}

function Toggle({
  checked,
  onChange,
  label,
  disabled,
}: {
  checked: boolean;
  onChange: (value: boolean) => void;
  label: string;
  disabled?: boolean;
}) {
  return (
    <label
      className={cn(
        "flex cursor-pointer items-center gap-2 text-xs font-semibold",
        disabled ? "cursor-not-allowed text-smoke/50 dark:text-linen-dim/50" : "text-smoke dark:text-linen-dim",
      )}
    >
      <input
        type="checkbox"
        checked={checked}
        disabled={disabled}
        onChange={(e) => onChange(e.target.checked)}
        aria-label={label}
        className="h-4 w-4 accent-bronze disabled:opacity-40"
      />
      {label}
    </label>
  );
}

export function NotificationPreferences({ isAdmin }: { isAdmin: boolean }) {
  const pushToast = useUiStore((s) => s.pushToast);
  const [preferences, setPreferences] = useState<Preference[] | null>(null);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    let cancelled = false;
    api<PreferenceCollection>("/notification-preferences")
      .then((response) => {
        if (!cancelled) setPreferences(response.data);
      })
      .catch(() => {
        if (!cancelled) setError("Notification preferences could not be loaded.");
      });
    return () => {
      cancelled = true;
    };
  }, []);

  const visible = (preferences ?? []).filter((p) => isAdmin || !p.category.startsWith("admin_"));
  const customerPrefs = visible.filter((p) => !p.category.startsWith("admin_"));
  const adminPrefs = visible.filter((p) => p.category.startsWith("admin_"));

  const persist = async (next: Preference[]) => {
    const previous = preferences;
    setPreferences(next);
    setSaving(true);
    try {
      const response = await api<PreferenceCollection>("/notification-preferences", {
        method: "PUT",
        body: JSON.stringify({
          preferences: next.map((p) => ({
            category: p.category,
            in_app_enabled: p.in_app_enabled,
            email_enabled: p.email_enabled,
            sms_enabled: p.sms_enabled,
            whatsapp_enabled: p.whatsapp_enabled,
          })),
        }),
      });
      setPreferences(response.data);
    } catch (requestError) {
      setPreferences(previous);
      pushToast(requestError instanceof Error ? requestError.message : "Could not save preferences");
    } finally {
      setSaving(false);
    }
  };

  const flip = (category: string, field: "in_app_enabled" | "email_enabled", value: boolean) => {
    if (!preferences || saving) return;
    void persist(preferences.map((p) => (p.category === category ? { ...p, [field]: value } : p)));
  };

  const renderRow = (preference: Preference) => (
    <li
      key={preference.category}
      className="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-b border-line/60 py-4 last:border-0 dark:border-line-dark/60"
    >
      <div className="min-w-0 flex-1">
        <p className="text-sm font-semibold text-ink dark:text-linen">{preference.label}</p>
        <p className="mt-0.5 font-mono text-[11px] text-smoke dark:text-linen-dim">{preference.category}</p>
      </div>
      <div className="flex flex-wrap items-center gap-x-5 gap-y-2">
        <Toggle
          checked={preference.in_app_enabled}
          onChange={(value) => flip(preference.category, "in_app_enabled", value)}
          label="In-app"
        />
        <Toggle
          checked={preference.email_enabled}
          onChange={(value) => flip(preference.category, "email_enabled", value)}
          label="Email"
        />
        <Toggle checked={preference.sms_enabled} onChange={() => undefined} label="SMS · soon" disabled />
        <Toggle checked={preference.whatsapp_enabled} onChange={() => undefined} label="WhatsApp · soon" disabled />
      </div>
    </li>
  );

  return (
    <div className="mt-10 space-y-4">
      <div className="border border-line bg-cream p-6 dark:border-line-dark dark:bg-nox2">
        <h2 className="flex items-center gap-2 text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">
          <BellRing className="h-4 w-4 text-bronze" aria-hidden /> Order updates
        </h2>
        <p className="mt-2 text-sm text-smoke dark:text-linen-dim">
          Choose how you hear about your orders. Turning everything off only mutes notifications — your orders,
          payments, and refunds are unaffected.
        </p>
        {error ? (
          <p className="mt-4 text-sm text-red-700 dark:text-red-400" role="alert">{error}</p>
        ) : preferences === null ? (
          <div className="flex justify-center py-8" role="status" aria-label="Loading preferences">
            <Spinner />
          </div>
        ) : customerPrefs.length === 0 ? (
          <div className="mt-4">
            <EmptyState icon={BellRing} title="No preferences" body="Nothing to configure yet." />
          </div>
        ) : (
          <ul className="mt-2">{customerPrefs.map(renderRow)}</ul>
        )}
      </div>

      {isAdmin && (
        <div className="border border-line bg-cream p-6 dark:border-line-dark dark:bg-nox2">
          <h2 className="text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">Store alerts</h2>
          <p className="mt-2 text-sm text-smoke dark:text-linen-dim">
            Admin-only notifications about new orders, payments, and issues needing attention.
          </p>
          {preferences !== null && <ul className="mt-2">{adminPrefs.map(renderRow)}</ul>}
        </div>
      )}

      {saving && (
        <div className="flex items-center gap-2 text-xs text-smoke dark:text-linen-dim" role="status">
          <Spinner className="h-3.5 w-3.5" /> Saving…
        </div>
      )}
    </div>
  );
}
