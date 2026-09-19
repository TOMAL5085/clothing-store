import { Link } from "react-router-dom";
import type { ReactNode } from "react";
import { cn } from "@/utils/cn";

/**
 * JAAJ Button — sharp geometry, editorial uppercase, single motion curve.
 * Geometry per SOURCE 01: no radius, precise padding, restrained borders.
 */

type Variant = "solid" | "solidWhite" | "outline" | "outlineWhite";
type Size = "md" | "sm" | "lg";

const base =
  "inline-flex select-none items-center justify-center gap-3 font-display font-semibold uppercase tracking-nav transition-colors duration-300 ease-out rounded-none";

const variants: Record<Variant, string> = {
  solid: "bg-foreground text-background hover:bg-hover",
  solidWhite: "bg-white text-black hover:bg-zinc-200",
  outline:
    "border border-foreground/35 text-foreground hover:border-foreground hover:bg-foreground hover:text-background",
  outlineWhite: "border border-white/55 text-white hover:border-white hover:bg-white hover:text-black",
};

const sizes: Record<Size, string> = {
  sm: "h-10 px-6 text-[11px]",
  md: "h-12 px-8 text-[12px]",
  lg: "h-14 px-10 text-[12px]",
};

interface CommonProps {
  variant?: Variant;
  size?: Size;
  className?: string;
  children: ReactNode;
}

interface ButtonAsButton extends CommonProps, Omit<React.ButtonHTMLAttributes<HTMLButtonElement>, "children"> {
  to?: undefined;
}

interface ButtonAsLink extends CommonProps {
  to: string;
  onClick?: () => void;
  ariaLabel?: string;
}

export function Button(props: ButtonAsButton | ButtonAsLink) {
  const { variant = "solid", size = "md", className, children } = props;
  const classes = cn(base, variants[variant], sizes[size], className);

  if (props.to !== undefined) {
    return (
      <Link to={props.to} onClick={props.onClick} aria-label={props.ariaLabel} className={classes}>
        {children}
      </Link>
    );
  }

  const { to: _to, variant: _v, size: _s, className: _c, children: _ch, ...rest } =
    props as ButtonAsButton & { to?: string };
  return (
    <button type="button" {...rest} className={classes}>
      {children}
    </button>
  );
}
