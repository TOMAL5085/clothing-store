import type { Category } from "@/types";
import { INSTAGRAM } from "./siteCopy";

/** SHOP BY CATEGORY cells — order per SOURCE 01. */
export const CATEGORIES: Category[] = [
  { label: "SHIRTS", href: "/shop?category=women", icon: "shirt" },
  { label: "T-SHIRTS", href: "/shop?category=women", icon: "tshirt" },
  { label: "BOTTOMS", href: "/shop?category=women", icon: "bottoms" },
  { label: "OUTERWEAR", href: "/shop?category=men", icon: "outerwear" },
  {
    label: "ACCESSORIES",
    href: "/shop?category=accessories",
    icon: "accessories",
  },
  { label: "FOOTWEAR", href: "/shop?category=men", icon: "footwear" },
  { label: "BEAUTY", href: "/shop", icon: "beauty" },
  { label: "BAGS", href: "/shop?category=accessories", icon: "bags" },
];

export interface GalleryImage {
  src: string;
  alt: string;
}

const pxSquare = (id: number) =>
  `https://images.pexels.com/photos/${id}/pexels-photo-${id}.jpeg?auto=compress&cs=tinysrgb&fit=crop&w=640&h=640`;

/** INSTAGRAM GALLERY strip — monochrome editorial set. */
export const INSTAGRAM_GALLERY: GalleryImage[] = [
  {
    src: pxSquare(34691207),
    alt: "Editorial look in an urban setting — " + INSTAGRAM.handle,
  },
  {
    src: pxSquare(19793651),
    alt: "Street style portrait — " + INSTAGRAM.handle,
  },
  {
    src: pxSquare(6138993),
    alt: "Checkered blazer look over sunglasses — " + INSTAGRAM.handle,
  },
  {
    src: pxSquare(38290951),
    alt: "Magazine portrait in the city — " + INSTAGRAM.handle,
  },
  {
    src: pxSquare(19128143),
    alt: "Coat styling, urban elegance — " + INSTAGRAM.handle,
  },
  {
    src: pxSquare(31385591),
    alt: "Duo editorial, modern tailoring — " + INSTAGRAM.handle,
  },
];
