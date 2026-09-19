import type { Product as ProductRecord } from "@/data/products";

export type Product = ProductRecord;
export type Category = { label: string; href: string; icon: CategoryIconKey };
export type CategoryIconKey = "shirt" | "tshirt" | "bottoms" | "outerwear" | "accessories" | "footwear" | "beauty" | "bags";
export type NavItem = { label: string; href: string; emphasis?: boolean };
export type ServicePoint = { title: string; description: string; icon: "truck" | "returns" | "payment" | "support" };
export type FooterLink = { label: string; href: string };
export type FooterLinkGroup = { heading: string; links: FooterLink[] };
