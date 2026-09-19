import { Star } from "lucide-react";
import { cn } from "@/utils/cn";

export function Rating({
  value,
  reviews,
  className,
  showCount = true,
}: {
  value: number;
  reviews?: number;
  className?: string;
  showCount?: boolean;
}) {
  return (
    <span className={cn("inline-flex items-center gap-1.5", className)}>
      <span className="relative inline-flex" aria-hidden>
        <span className="flex gap-0.5 text-line dark:text-line-dark">
          {Array.from({ length: 5 }).map((_, i) => (
            <Star key={i} className="h-3.5 w-3.5" />
          ))}
        </span>
        <span
          className="absolute inset-0 flex gap-0.5 overflow-hidden text-bronze"
          style={{ width: `${(value / 5) * 100}%` }}
        >
          {Array.from({ length: 5 }).map((_, i) => (
            <Star key={i} className="h-3.5 w-3.5 shrink-0 fill-bronze" />
          ))}
        </span>
      </span>
      <span className="sr-only">Rated {value} out of 5</span>
      {showCount && (
        <span className="text-xs text-smoke dark:text-linen-dim">
          {value.toFixed(1)}
          {typeof reviews === "number" && ` (${reviews})`}
        </span>
      )}
    </span>
  );
}
