import type { NavItem, ServicePoint, FooterLinkGroup } from "@/types";
import { SERVICE_BAR, FOOTER_COPY } from "./siteCopy";

/** Primary navigation — order and labels per SOURCE 01. */
export const PRIMARY_NAV: NavItem[] = [
  { label: "MEN", href: "/shop?category=men" },
  { label: "WOMEN", href: "/shop?category=women" },
  { label: "KIDS", href: "/shop" },
  { label: "ACCESSORIES", href: "/shop?category=accessories" },
  { label: "BEAUTY", href: "/shop" },
  { label: "SALE", href: "/shop?sort=price-desc", emphasis: true },
  { label: "NEW IN", href: "/shop?sort=newest" },
];

/** Service bar, typed from verbatim copy. */
export const SERVICE_POINTS: ServicePoint[] = SERVICE_BAR.map((p) => ({
  ...p,
}));

/** Footer link columns — labels per SOURCE 01. */
export const FOOTER_GROUPS: FooterLinkGroup[] = [
  {
    heading: "SHOP",
    links: [
      { label: "Men", href: "/shop?category=men" },
      { label: "Women", href: "/shop?category=women" },
      { label: "Kids", href: "/shop" },
      { label: "Accessories", href: "/shop?category=accessories" },
      { label: "Beauty", href: "/shop" },
      { label: "Sale", href: "/shop?sort=price-desc" },
      { label: "New In", href: "/shop?sort=newest" },
    ],
  },
  {
    heading: "CUSTOMER CARE",
    links: [
      { label: "Contact Us", href: "/contact" },
      { label: "Track Order", href: "/track-order" },
      { label: "Shipping & Delivery", href: "/shipping" },
      { label: "Returns & Exchanges", href: "/returns" },
      { label: "FAQ", href: "/faq" },
      { label: "Size Guide", href: "/size-guide" },
    ],
  },
  {
    heading: "COMPANY",
    links: [
      { label: "About JAAJ", href: "/our-story" },
      { label: "Careers", href: "/careers" },
      { label: "Store Locator", href: "/stores" },
      { label: "Press", href: "/press" },
      { label: "Sustainability", href: "/sustainability" },
    ],
  },
  {
    heading: "LEGAL",
    links: [
      { label: "Terms & Conditions", href: "/terms" },
      { label: "Privacy Policy", href: "/privacy" },
      { label: "Refund Policy", href: "/refund-policy" },
      { label: "Cookie Policy", href: "/cookies" },
    ],
  },
];

export const PAYMENT_MARKS = ["VISA", "MC", "AMEX", "PAYPAL"] as const;

export { FOOTER_COPY };
