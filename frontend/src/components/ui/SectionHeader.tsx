import { Link } from "react-router-dom";
import { ArrowIconButton } from "./ArrowIconButton";
import { cn } from "@/utils/cn";

/**
 * Editorial section header, per SOURCE 01:
 * uppercase display title left — VIEW ALL + square arrow buttons right.
 */

interface SectionHeaderProps {
  title: string;
  viewAllLabel?: string;
  viewAllHref?: string;
  onPrev?: () => void;
  onNext?: () => void;
  className?: string;
}

export function SectionHeader({
  title,
  viewAllLabel,
  viewAllHref,
  onPrev,
  onNext,
  className,
}: SectionHeaderProps) {
  const showArrows = Boolean(onPrev || onNext);
  return (
    <div className={cn("flex items-end justify-between gap-6", className)}>
      <h2 className="font-display text-[17px] font-extrabold uppercase leading-none tracking-[0.16em] md:text-[19px]">
        {title}
      </h2>
      <div className="flex items-center gap-5">
        {viewAllLabel && viewAllHref && (
          <Link
            to={viewAllHref}
            className="text-[11px] font-semibold uppercase tracking-nav text-muted underline-offset-4 transition-colors duration-300 hover:text-foreground hover:underline"
          >
            {viewAllLabel}
          </Link>
        )}
        {showArrows && (
          <div className="flex items-center gap-2">
            <ArrowIconButton direction="prev" onClick={onPrev} label={`Scroll ${title} back`} />
            <ArrowIconButton direction="next" onClick={onNext} label={`Scroll ${title} forward`} />
          </div>
        )}
      </div>
    </div>
  );
}
