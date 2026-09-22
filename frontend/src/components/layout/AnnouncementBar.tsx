import { useEffect, useState } from "react";
import { ANNOUNCEMENT } from "@/data/siteCopy";
import { fetchAnnouncement } from "@/lib/content";

/**
 * Announcement bar — always the ink treatment in both themes,
 * "$99" in crimson exactly per SOURCE 01.
 */
export function AnnouncementBar() {
  const [copy, setCopy] = useState({ textBefore: ANNOUNCEMENT.textBefore, highlight: ANNOUNCEMENT.highlight });

  useEffect(() => {
    let cancelled = false;
    fetchAnnouncement().then((record) => {
      if (!cancelled && record) setCopy(record);
    });
    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <div className="bg-ink text-ink-foreground">
      <p className="mx-auto flex h-9 max-w-[1440px] items-center justify-center px-4 text-center text-[10px] font-medium uppercase tracking-[0.26em]">
        {copy.textBefore}&nbsp;
        <span className="font-semibold text-accent-strong">{copy.highlight}</span>
      </p>
    </div>
  );
}
