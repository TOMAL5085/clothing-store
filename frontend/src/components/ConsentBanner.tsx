import { useState } from "react";
import { Cookie } from "lucide-react";
import { api, hasApiToken } from "@/lib/api";
import { hasConsentRecord, setConsent } from "@/lib/marketing";
import { Button } from "@/components/ui/primitives";

/**
 * First-visit measurement consent. Dismissible, non-blocking, and purely
 * advisory: until the visitor decides, the tracker treats consent as
 * denied and the backend declines client events.
 */
export function ConsentBanner() {
  const [visible, setVisible] = useState(() => !hasConsentRecord());
  const [saving, setSaving] = useState(false);

  if (!visible) return null;

  const choose = (analytics: boolean, marketing: boolean) => {
    setSaving(true);
    setConsent({ analytics, marketing });
    // Mirror authenticated choice server-side; local state stays
    // authoritative if the request fails.
    if (hasApiToken()) {
      void api("/profile/marketing-consent", {
        method: "PUT",
        body: JSON.stringify({ analytics, marketing }),
      })
        .catch(() => undefined)
        .finally(() => {
          setSaving(false);
          setVisible(false);
        });
    } else {
      setSaving(false);
      setVisible(false);
    }
  };

  return (
    <div
      role="dialog"
      aria-label="Measurement consent"
      className="fixed inset-x-0 bottom-0 z-[60] border-t border-line bg-cream/95 px-4 py-4 shadow-xl backdrop-blur dark:border-line-dark dark:bg-nox2/95 sm:px-6"
    >
      <div className="mx-auto flex max-w-[1200px] flex-wrap items-center gap-x-6 gap-y-3">
        <p className="flex min-w-0 flex-1 items-center gap-3 text-sm text-smoke dark:text-linen-dim">
          <Cookie className="h-5 w-5 shrink-0 text-bronze" aria-hidden />
          <span>
            We measure anonymous shopping behavior to improve the store, and campaign performance when you allow
            it. Details live under Account → Profile → Measurement.
          </span>
        </p>
        <div className="flex shrink-0 flex-wrap gap-2">
          <Button type="button" size="sm" variant="ghost" disabled={saving} onClick={() => choose(false, false)}>
            Reject
          </Button>
          <Button type="button" size="sm" variant="outline" disabled={saving} onClick={() => choose(true, false)}>
            Analytics only
          </Button>
          <Button type="button" size="sm" disabled={saving} onClick={() => choose(true, true)}>
            Accept all
          </Button>
        </div>
      </div>
    </div>
  );
}
