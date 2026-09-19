import type { ReactElement } from "react";

/**
 * Minimal social brand glyphs (24×24, currentColor).
 * Newer Lucide releases dropped brand icons, so these are drawn in-house
 * with the same 1.6px-stroke vocabulary used across the JAAJ icon set.
 */

export type SocialIconKey = "instagram" | "facebook" | "tiktok" | "youtube";

const GLYPHS: Record<SocialIconKey, ReactElement> = {
  instagram: (
    <>
      <rect x="3.5" y="3.5" width="17" height="17" rx="4.5" fill="none" strokeWidth="1.6" />
      <circle cx="12" cy="12" r="3.8" fill="none" strokeWidth="1.6" />
      <circle cx="16.9" cy="7.1" r="1.15" stroke="none" fill="currentColor" />
    </>
  ),
  facebook: (
    <path
      d="M14.5 8.2V6.9c0-.9.5-1.2 1.3-1.2H17V3.1h-1.9c-2.4 0-3.6 1.5-3.6 3.6v1.5H9v2.6h2.5v9.4h3v-9.4h2.1l.4-2.6z"
      fill="currentColor"
      stroke="none"
    />
  ),
  tiktok: (
    <path
      d="M13.8 4v9.6a3.3 3.3 0 1 1-3.3-3.3M13.8 4c.5 2.6 2 4.1 4.6 4.4"
      fill="none"
      strokeWidth="1.7"
    />
  ),
  youtube: (
    <>
      <rect x="3" y="6.2" width="18" height="11.6" rx="3" fill="none" strokeWidth="1.6" />
      <path d="M10.2 9.6v4.8l4.4-2.4z" fill="currentColor" stroke="none" />
    </>
  ),
};

interface SocialIconProps {
  icon: SocialIconKey;
  size?: number;
  className?: string;
}

export function SocialIcon({ icon, size = 15, className }: SocialIconProps) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      className={className}
    >
      {GLYPHS[icon]}
    </svg>
  );
}
