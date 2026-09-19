import { ChevronLeft, ChevronRight } from "lucide-react";
import { cn } from "@/utils/cn";

/** Square bordered prev/next controls seen beside section headers in SOURCE 01. */

interface ArrowIconButtonProps {
  direction: "prev" | "next";
  onClick?: () => void;
  label: string;
  className?: string;
}

export function ArrowIconButton({ direction, onClick, label, className }: ArrowIconButtonProps) {
  const Icon = direction === "prev" ? ChevronLeft : ChevronRight;
  return (
    <button
      type="button"
      onClick={onClick}
      aria-label={label}
      className={cn(
        "flex h-9 w-9 items-center justify-center border border-line text-foreground",
        "transition-colors duration-300 hover:border-foreground hover:bg-foreground hover:text-background",
        className
      )}
    >
      <Icon size={15} strokeWidth={1.8} absoluteStrokeWidth />
    </button>
  );
}
