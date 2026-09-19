import { ANNOUNCEMENT } from "@/data/siteCopy";

/**
 * Announcement bar — always the ink treatment in both themes,
 * "$99" in crimson exactly per SOURCE 01.
 */
export function AnnouncementBar() {
  return (
    <div className="bg-ink text-ink-foreground">
      <p className="mx-auto flex h-9 max-w-[1440px] items-center justify-center px-4 text-center text-[10px] font-medium uppercase tracking-[0.26em]">
        {ANNOUNCEMENT.textBefore}&nbsp;
        <span className="font-semibold text-accent-strong">{ANNOUNCEMENT.highlight}</span>
      </p>
    </div>
  );
}
