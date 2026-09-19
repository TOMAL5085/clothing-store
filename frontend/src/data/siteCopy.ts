/**
 * JAAJ — Site copy (SOURCE 01 verbatim)
 * ------------------------------------------------------------------
 * ALL visible copy on the homepage is transcribed exactly from SOURCE 01
 * (Homepage_high_res.png). Do not paraphrase, "improve", or re-order.
 *
 * ★ THE HERO IMAGE MAY CHANGE. THE HERO TEXT MUST NOT CHANGE. ★
 * The hero copy below is a single fixed data object, fully decoupled from
 * whatever image the hero background layer displays (see HERO_SLIDES in
 * Hero.tsx). Slider work in Part 2 swaps imagery only — never this copy.
 */

export const ANNOUNCEMENT = {
  /** "$99" is rendered in the brand red accent, per SOURCE 01. */
  textBefore: "FREE SHIPPING ON ALL ORDERS ABOVE",
  highlight: "$99",
} as const;

export const HERO_COPY = {
  eyebrow: "NEW COLLECTION",
  titleLine1: "DEFINE YOUR",
  titleLine2: "OWN STYLE",
  subLine1: "Premium fabrics. Timeless designs.",
  subLine2: "Made for the modern generation.",
  primaryCta: { label: "SHOP MEN", href: "/shop?category=men" },
  secondaryCta: { label: "SHOP WOMEN", href: "/shop?category=women" },
  /** Static pagination indices displayed bottom-left ("01 — 02 — 03"). */
  slideIndices: ["01", "02", "03"],
} as const;

export const NEW_DROP = {
  title: "NEW DROP",
  viewAll: "VIEW ALL",
  viewAllHref: "/shop?sort=newest",
} as const;

export const COLLECTION_TILES = [
  {
    title: "MEN",
    subtitle: "COLLECTION",
    cta: "DISCOVER NOW",
    href: "/shop?category=men",
  },
  {
    title: "WOMEN",
    subtitle: "COLLECTION",
    cta: "DISCOVER NOW",
    href: "/shop?category=women",
  },
] as const;

export const CATEGORY_STRIP = {
  title: "SHOP BY CATEGORY",
} as const;

export const SERVICE_BAR = [
  { icon: "truck", title: "FREE SHIPPING", description: "On orders over $99" },
  { icon: "returns", title: "EASY RETURNS", description: "Within 30 days" },
  {
    icon: "payment",
    title: "SECURE PAYMENT",
    description: "100% secure checkout",
  },
  {
    icon: "support",
    title: "24/7 SUPPORT",
    description: "We are here to help",
  },
] as const;

export const BEST_SELLERS = {
  title: "BEST SELLERS",
  viewAll: "VIEW ALL",
  viewAllHref: "/shop",
} as const;

export const EXPERIENCE = {
  titleLine1: "THE JAAJ",
  titleLine2: "EXPERIENCE",
  statement: "Minimal. Modern. Authentic.",
  description: "Designed to elevate your everyday.",
  cta: { label: "OUR STORY", href: "/our-story" },
} as const;

export const INSTAGRAM = {
  title: "INSTAGRAM GALLERY",
  handle: "@JAAJ.OFFICIAL",
  href: "https://instagram.com",
} as const;

export const CLUB = {
  title: "JOIN THE JAAJ CLUB",
  description:
    "Be the first to know about new arrivals, exclusive offers and more.",
  inputPlaceholder: "Enter your email",
  submitLabel: "Subscribe",
} as const;

export const FOOTER_COPY = {
  tagline:
    "Contemporary fashion for the modern generation. Timeless style. Premium quality.",
  bottomNote: `© ${new Date().getFullYear()} JAAJ. All rights reserved.`,
  paymentsLabel: "WE ACCEPT",
};

export const PLACEHOLDER_COPY = {
  eyebrow: "JAAJ",
  title: "COMING SOON",
  body: "This section is being crafted. Explore the new season on the homepage.",
  cta: "BACK TO HOME",
} as const;