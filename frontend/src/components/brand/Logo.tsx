import { Link } from "react-router-dom";
import { cn } from "@/utils/cn";

/** JAAJ wordmark, typeset in the brand display face. */
export function Logo({
  className,
  asLink = true,
  invert,
}: {
  className?: string;
  asLink?: boolean;
  invert?: boolean;
}) {
  const wordmark = (
    <span className={cn("inline-flex flex-col items-center leading-none", className)}>
      <span
        className={cn(
          "font-logo text-[30px] font-medium tracking-[0.34em] [margin-right:-0.34em]",
          invert ? "text-cream" : "text-ink dark:text-linen"
        )}
      >
        JAAJ
      </span>
    </span>
  );

  if (!asLink) return wordmark;
  return (
    <Link to="/" aria-label="JAAJ — back to home" className="inline-flex p-1">
      {wordmark}
    </Link>
  );
}
