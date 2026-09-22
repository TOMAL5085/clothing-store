import { useEffect, useState } from "react";
import { ChartNoAxesColumn } from "lucide-react";
import { api } from "@/lib/api";
import { getConsent, setConsent, type ConsentState } from "@/lib/marketing";
import { useUiStore } from "@/store/uiStore";
import { Spinner } from "@/components/ui/primitives";
import { cn } from "@/utils/cn";

interface ConsentResponse {
  data: ConsentState;
}

function ConsentToggle({
  checked,
  onChange,
  label,
  hint,
  disabled,
}: {
  checked: boolean;
  onChange: (value: boolean) => void;
  label: string;
  hint: string;
  disabled?: boolean;
}) {
  return (
    <label
      className={cn(
        "flex cursor-pointer items-center justify-between gap-4 text-sm",
        disabled && "cursor-not-allowed opacity-60",
      )}
    >
      <span>
        <span className="block font-semibold text-ink dark:text-linen">{label}</span>
        <span className="block text-xs text-smoke dark:text-linen-dim">{hint}</span>
      </span>
      <input
        type="checkbox"
        checked={checked}
        disabled={disabled}
        onChange={(e) => onChange(e.target.checked)}
        aria-label={label}
        className="h-4 w-4 shrink-0 accent-bronze disabled:opacity-40"
      />
    </label>
  );
}

/**
 * Server-persisted marketing consent, mirrored to local storage so the
 * event tracker and the backend always agree.
 */
export function MarketingConsentToggles() {
  const pushToast = useUiStore((s) => s.pushToast);
  const [consent, setLocalConsent] = useState<ConsentState | null>(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    let cancelled = false;
    api<ConsentResponse>("/profile/marketing-consent")
      .then((response) => {
        if (cancelled) return;
        setLocalConsent(response.data);
        setConsent(response.data);
      })
      .catch(() => {
        if (!cancelled) setLocalConsent(getConsent());
      });
    return () => {
      cancelled = true;
    };
  }, []);

  const flip = (field: keyof ConsentState, value: boolean) => {
    if (!consent || saving) return;
    const previous = consent;
    const next = { ...consent, [field]: value };
    setLocalConsent(next);
    setSaving(true);
    api<ConsentResponse>("/profile/marketing-consent", {
      method: "PUT",
      body: JSON.stringify(next),
    })
      .then((response) => {
        setLocalConsent(response.data);
        setConsent(response.data);
      })
      .catch((requestError) => {
        setLocalConsent(previous);
        pushToast(requestError instanceof Error ? requestError.message : "Could not save consent");
      })
      .finally(() => setSaving(false));
  };

  if (!consent) {
    return (
      <div className="mt-4 flex justify-start py-2" role="status" aria-label="Loading consent settings">
        <Spinner className="h-4 w-4" />
      </div>
    );
  }

  return (
    <div className="mt-4 space-y-4 border-t border-line pt-4 dark:border-line-dark">
      <p className="flex items-center gap-2 text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">
        <ChartNoAxesColumn className="h-4 w-4 text-bronze" aria-hidden /> Measurement
      </p>
      <ConsentToggle
        checked={consent.analytics}
        onChange={(value) => flip("analytics", value)}
        label="Shopping analytics"
        hint="Anonymous browsing measurement that helps improve the store."
        disabled={saving}
      />
      <ConsentToggle
        checked={consent.marketing}
        onChange={(value) => flip("marketing", value)}
        label="Marketing updates"
        hint="Lets order updates feed future campaign measurement."
        disabled={saving}
      />
    </div>
  );
}
