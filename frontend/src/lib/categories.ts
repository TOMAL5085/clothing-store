import { api, type ApiCollection } from "@/lib/api";

export interface StoreCategory {
  id: string;
  slug: string;
  label: string;
  tagline: string | null;
  image: string | null;
  alt: string | null;
  sort_order: number;
  is_active: boolean;
  children: StoreCategory[];
  has_children: number | boolean;
}

export async function fetchCategoryTree() {
  const response = await api<ApiCollection<StoreCategory>>("/categories");
  return response.data;
}

export function categoryShopPath(slug: string) {
  return `/shop?category=${encodeURIComponent(slug)}`;
}
