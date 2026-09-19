import type { CategoryIconKey } from "@/types";

/**
 * JAAJ category icon set — hand-drawn minimal line marks (1.5px stroke,
 * square terminals) crafted for the SHOP BY CATEGORY strip in SOURCE 01.
 * Single consistent vocabulary; Lucide is used for UI chrome elsewhere.
 */

const PATHS: Record<CategoryIconKey, string[]> = {
  shirt: [
    "M8.2 5.4 12 7.2l3.8-1.8L19.6 8l-2.1 3-1.5-.9V19H8v-8.9l-1.5.9-2.1-3z",
    "M8.2 5.4 12 4.2l3.8 1.2",
  ],
  tshirt: [
    "M7.8 6.2 10 4h4l2.2 2.2 3 2.4-2 2.8-1.2-.9V20H8v-9.5l-1.2.9-2-2.8z",
    "M10 4c.4 1 1.1 1.6 2 1.6S13.6 5 14 4",
  ],
  bottoms: ["M8 3.5h8l1 17h-4.4L12 10l-.6 10.5H7z", "M8 6.4h8.1"],
  outerwear: [
    "M9.6 4 12 3l2.4 1 2.8 1.8-1 4.2-1.9-1V20H7.7V9l-1.9 1-1-4.2z",
    "M12 3v17",
    "M9.6 4 12 8l2.4-4",
  ],
  accessories: [
    "M4.5 11h2.2l1 1.5v1.6a2.6 2.6 0 1 1-5.2 0V12.5a1.5 1.5 0 0 1 2-1.5Z",
    "M17.3 11h2.2a1.5 1.5 0 0 1 2 1.5v1.6a2.6 2.6 0 1 1-5.2 0V12.5z",
    "M9.7 11.2h4.6",
    "M4.5 11c0-2.4 3.4-3.6 7.5-3.6s7.5 1.2 7.5 3.6",
  ],
  footwear: [
    "M4 16.2c0-2.8 1.3-4.6 3-6C8 9.4 8.8 9.4 9.5 10c1.3 1.3 2.6 2.2 4.4 2.7 1.8.5 3.9 1 6.1 1.9l.5 2.6H4z",
    "M4 16.2h16.7",
    "M8.6 11l1.2-1M10.4 12.2l1.2-1",
  ],
  beauty: ["M10 6l2.6-1.8V9H10z", "M9.1 9h4.4v11H9.1z", "M8.2 20h6.2v1.5H8.2z"],
  bags: ["M6 9h12l1.1 11.5H4.9z", "M9 9V7.4a3 3 0 0 1 6 0V9"],
};

interface CategoryIconProps {
  icon: CategoryIconKey;
  size?: number;
  className?: string;
}

export function CategoryIcon({ icon, size = 26, className }: CategoryIconProps) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={1.5}
      strokeLinecap="square"
      strokeLinejoin="miter"
      aria-hidden="true"
      className={className}
    >
      {PATHS[icon].map((d) => (
        <path key={d} d={d} />
      ))}
    </svg>
  );
}
