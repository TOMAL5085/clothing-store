import { create } from "zustand";
import { PRODUCTS, searchProducts as searchFallback, type Product } from "@/data/products";
import { api, type ApiCollection } from "@/lib/api";

export interface ProductQuery {
  category?: string | null;
  q?: string;
  sort?: string;
  sizes?: string[];
  colors?: string[];
  minPrice?: string;
  maxPrice?: string;
  inStockOnly?: boolean;
  page?: number;
  perPage?: number;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface ProductCollection {
  data: Product[];
  meta?: PaginationMeta;
}

interface CatalogState {
  products: Product[];
  loaded: boolean;
  loading: boolean;
  loadProducts: () => Promise<void>;
  listProducts: (query?: ProductQuery) => Promise<ProductCollection>;
  getProductBySlug: (slug: string | undefined) => Product | undefined;
  searchProducts: (query: string) => Product[];
}

function productQueryString(query: ProductQuery = {}) {
  const params = new URLSearchParams();
  if (query.category) params.set("category", query.category);
  if (query.q) params.set("q", query.q);
  if (query.sort && query.sort !== "featured") params.set("sort", query.sort);
  if (query.minPrice) params.set("min_price", query.minPrice);
  if (query.maxPrice) params.set("max_price", query.maxPrice);
  if (query.inStockOnly) params.set("in_stock", "1");
  if (query.page) params.set("page", String(query.page));
  params.set("per_page", String(query.perPage ?? 12));
  query.sizes?.forEach((size) => params.append("sizes[]", size));
  query.colors?.forEach((color) => params.append("colors[]", color));
  return params.toString();
}

export const useCatalogStore = create<CatalogState>((set, get) => ({
  products: PRODUCTS,
  loaded: false,
  loading: false,
  loadProducts: async () => {
    if (get().loading || get().loaded) return;
    set({ loading: true });
    try {
      const response = await api<ApiCollection<Product>>("/products?per_page=100");
      set({ products: response.data, loaded: true, loading: false });
    } catch (error) {
      console.warn("Using local product fallback.", error);
      set({ loading: false, loaded: true });
    }
  },
  listProducts: async (query = {}) => {
    const response = await api<ProductCollection>(`/products?${productQueryString(query)}`);
    return response;
  },
  getProductBySlug: (slug) => get().products.find((product) => product.slug === slug),
  searchProducts: (query) => {
    const q = query.trim().toLowerCase();
    if (!q) return [];
    const products = get().products;
    if (products === PRODUCTS) return searchFallback(query);
    return products.filter(
      (product) =>
        product.name.toLowerCase().includes(q) ||
        product.category.includes(q) ||
        product.description.toLowerCase().includes(q),
    );
  },
}));

export const useProducts = () => useCatalogStore((state) => state.products);
