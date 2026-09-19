import { forwardRef, type ButtonHTMLAttributes, type InputHTMLAttributes, type ReactNode, type SelectHTMLAttributes, type TextareaHTMLAttributes } from "react";
import { Link } from "react-router-dom";
import { Loader2, Minus, Plus, type LucideIcon } from "lucide-react";
import { cn } from "@/utils/cn";
import { useCurrencyStore } from "@/store/currencyStore";

/* ---------------------------------- Buttons --------------------------------- */

type ButtonVariant = "primary" | "outline" | "ghost" | "light" | "dark";
type ButtonSize = "sm" | "md" | "lg";

export function buttonClasses(variant: ButtonVariant = "primary", size: ButtonSize = "md") {
  const base =
    "inline-flex items-center justify-center gap-2 font-semibold leading-none tracking-[0.08em] uppercase transition-all duration-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-bronze disabled:cursor-not-allowed disabled:opacity-50";
  const sizes: Record<ButtonSize, string> = {
    sm: "h-9 px-4 text-[11px]",
    md: "h-11 px-6 text-xs",
    lg: "h-13 px-8 py-4 text-xs",
  };
  const variants: Record<ButtonVariant, string> = {
    primary:
      "bg-ink text-paper hover:bg-bronze-deep dark:bg-linen dark:text-nox dark:hover:bg-bronze",
    outline:
      "border border-ink/25 bg-transparent text-ink hover:border-ink hover:bg-ink hover:text-paper dark:border-linen/30 dark:text-linen dark:hover:border-linen dark:hover:bg-linen dark:hover:text-nox",
    ghost: "text-ink underline-offset-4 hover:text-bronze hover:underline dark:text-linen",
    light: "bg-cream text-ink hover:bg-bronze hover:text-cream",
    dark: "bg-ink text-paper hover:bg-bronze-deep",
  };
  return cn(base, sizes[size], variants[variant]);
}

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  loading?: boolean;
  icon?: LucideIcon;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
  { variant, size, loading, icon: Icon, className, children, disabled, ...props },
  ref
) {
  return (
    <button
      ref={ref}
      className={cn(buttonClasses(variant, size), className)}
      disabled={disabled || loading}
      {...props}
    >
      {loading ? (
        <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
      ) : (
        Icon && <Icon className="h-4 w-4" aria-hidden />
      )}
      {children}
    </button>
  );
});

export function LinkButton({
  to,
  variant,
  size,
  className,
  children,
  icon: Icon,
  ...rest
}: {
  to: string;
  variant?: ButtonVariant;
  size?: ButtonSize;
  className?: string;
  children: ReactNode;
  icon?: LucideIcon;
} & Omit<React.ComponentProps<typeof Link>, "to" | "className">) {
  return (
    <Link to={to} className={cn(buttonClasses(variant, size), className)} {...rest}>
      {Icon && <Icon className="h-4 w-4" aria-hidden />}
      {children}
    </Link>
  );
}

/* ----------------------------------- Forms ---------------------------------- */

export function Field({
  label,
  htmlFor,
  error,
  hint,
  required,
  children,
  className,
}: {
  label: string;
  htmlFor: string;
  error?: string;
  hint?: string;
  required?: boolean;
  children: ReactNode;
  className?: string;
}) {
  return (
    <div className={cn("space-y-1.5", className)}>
      <label
        htmlFor={htmlFor}
        className="block text-[11px] font-semibold tracking-[0.14em] uppercase text-smoke dark:text-linen-dim"
      >
        {label}
        {required && <span className="ml-1 text-bronze" aria-hidden>*</span>}
      </label>
      {children}
      {hint && !error && (
        <p className="text-xs text-smoke dark:text-linen-dim">{hint}</p>
      )}
      {error && (
        <p className="text-xs font-semibold text-red-700 dark:text-red-400" role="alert">
          {error}
        </p>
      )}
    </div>
  );
}

const controlBase =
  "w-full border border-line bg-cream px-4 py-3 text-sm text-ink placeholder:text-smoke/70 transition-colors focus:border-bronze focus:outline-none dark:border-line-dark dark:bg-nox2 dark:text-linen dark:placeholder:text-linen-dim/60";

export const Input = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(
  function Input({ className, ...props }, ref) {
    return <input ref={ref} className={cn(controlBase, className)} {...props} />;
  }
);

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaHTMLAttributes<HTMLTextAreaElement>>(
  function Textarea({ className, ...props }, ref) {
    return <textarea ref={ref} rows={5} className={cn(controlBase, "resize-none", className)} {...props} />;
  }
);

export const Select = forwardRef<HTMLSelectElement, SelectHTMLAttributes<HTMLSelectElement>>(
  function Select({ className, ...props }, ref) {
    return <select ref={ref} className={cn(controlBase, "appearance-none pr-10", className)} {...props} />;
  }
);

/* ------------------------------- Quantity control ------------------------------ */

export function QuantityStepper({
  value,
  onChange,
  min = 1,
  max = 12,
  small,
  ariaLabel = "Quantity",
}: {
  value: number;
  onChange: (next: number) => void;
  min?: number;
  max?: number;
  small?: boolean;
  ariaLabel?: string;
}) {
  const btn =
    "inline-flex items-center justify-center text-ink transition-colors hover:bg-fog disabled:opacity-40 dark:text-linen dark:hover:bg-nox3";
  const sizeCls = small ? "h-8 w-8" : "h-10 w-10";
  return (
    <div
      className={cn(
        "inline-flex items-stretch border border-line dark:border-line-dark",
        small ? "h-8" : "h-10"
      )}
      role="group"
      aria-label={ariaLabel}
    >
      <button
        type="button"
        aria-label="Decrease quantity"
        className={cn(btn, sizeCls)}
        disabled={value <= min}
        onClick={() => onChange(Math.max(min, value - 1))}
      >
        <Minus className="h-3.5 w-3.5" aria-hidden />
      </button>
      <span
        aria-live="polite"
        className={cn(
          "flex items-center justify-center border-x border-line text-sm font-semibold tabular-nums dark:border-line-dark",
          small ? "w-9" : "w-11"
        )}
      >
        {value}
      </span>
      <button
        type="button"
        aria-label="Increase quantity"
        className={cn(btn, sizeCls)}
        disabled={value >= max}
        onClick={() => onChange(Math.min(max, value + 1))}
      >
        <Plus className="h-3.5 w-3.5" aria-hidden />
      </button>
    </div>
  );
}

/* ---------------------------------- Display ---------------------------------- */

export function Price({
  amount,
  compareAt,
  className,
  large,
}: {
  amount: number;
  compareAt?: number;
  className?: string;
  large?: boolean;
}) {
  const format = useCurrencyStore((s) => s.format);
  return (
    <span className={cn("inline-flex items-baseline gap-2", className)}>
      <span className={cn("font-semibold", large ? "text-xl" : "text-sm")}>{format(amount)}</span>
      {compareAt && compareAt > amount && (
        <span className="text-sm text-smoke line-through dark:text-linen-dim">
          {format(compareAt)}
        </span>
      )}
    </span>
  );
}

export function Badge({ children, tone = "ink" }: { children: ReactNode; tone?: "ink" | "bronze" | "sale" }) {
  return (
    <span
      className={cn(
        "inline-flex items-center px-2.5 py-1 text-[10px] font-bold tracking-[0.16em] uppercase",
        tone === "ink" && "bg-ink text-paper dark:bg-linen dark:text-nox",
        tone === "bronze" && "bg-bronze text-cream",
        tone === "sale" && "bg-red-800 text-cream"
      )}
    >
      {children}
    </span>
  );
}

export function Skeleton({ className }: { className?: string }) {
  return <div className={cn("skeleton rounded-none", className)} aria-hidden />;
}

export function ProductCardSkeleton() {
  return (
    <div aria-hidden>
      <Skeleton className="aspect-[3/4] w-full" />
      <div className="mt-4 space-y-2">
        <Skeleton className="h-4 w-2/3" />
        <Skeleton className="h-4 w-1/3" />
      </div>
    </div>
  );
}

export function Spinner({ className }: { className?: string }) {
  return (
    <span
      className={cn(
        "inline-block h-6 w-6 animate-spin rounded-full border-2 border-bronze border-t-transparent",
        className
      )}
      role="status"
      aria-label="Loading"
    />
  );
}

export function EmptyState({
  icon: Icon,
  title,
  body,
  actionLabel,
  actionTo,
}: {
  icon: LucideIcon;
  title: string;
  body: string;
  actionLabel?: string;
  actionTo?: string;
}) {
  return (
    <div className="flex flex-col items-center gap-4 px-6 py-16 text-center">
      <span className="flex h-14 w-14 items-center justify-center rounded-full border border-line bg-fog dark:border-line-dark dark:bg-nox2">
        <Icon className="h-6 w-6 text-smoke dark:text-linen-dim" aria-hidden />
      </span>
      <h2 className="font-display text-2xl text-ink dark:text-linen">{title}</h2>
      <p className="max-w-sm text-sm leading-relaxed text-smoke dark:text-linen-dim">{body}</p>
      {actionLabel && actionTo && (
        <LinkButton to={actionTo} size="md" className="mt-2">
          {actionLabel}
        </LinkButton>
      )}
    </div>
  );
}

export function SectionHeading({
  kicker,
  title,
  body,
  actionLabel,
  actionTo,
  center,
}: {
  kicker?: string;
  title: string;
  body?: string;
  actionLabel?: string;
  actionTo?: string;
  center?: boolean;
}) {
  return (
    <div className={cn("flex flex-wrap items-end justify-between gap-6", center && "flex-col items-center text-center")}>
      <div className={cn("max-w-xl space-y-3", center && "flex flex-col items-center")}>
        {kicker && (
          <p className="text-[11px] font-bold tracking-[0.22em] uppercase text-bronze">{kicker}</p>
        )}
        <h2 className="font-display text-3xl leading-tight text-ink sm:text-4xl dark:text-linen">
          {title}
        </h2>
        {body && <p className="text-sm leading-relaxed text-smoke dark:text-linen-dim">{body}</p>}
      </div>
      {actionLabel && actionTo && (
        <Link
          to={actionTo}
          className="group inline-flex items-center gap-2 text-xs font-bold tracking-[0.16em] uppercase text-ink transition-colors hover:text-bronze dark:text-linen"
        >
          {actionLabel}
          <span aria-hidden className="transition-transform duration-300 group-hover:translate-x-1">→</span>
        </Link>
      )}
    </div>
  );
}
