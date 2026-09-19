import { useEffect, useState } from "react";
import { useSearchParams } from "react-router-dom";
import { SearchX, SlidersHorizontal, X } from "lucide-react";
import type { Category, Product } from "@/data/products";
import { useCatalogStore, type PaginationMeta } from "@/store/catalogStore";
import { ProductCard } from "@/components/product/ProductCard";
import { ProductCardSkeleton, EmptyState } from "@/components/ui/primitives";
import { FiltersPanel, EMPTY_FILTERS, activeFilterCount, type ShopFilters } from "@/components/shop/FiltersPanel";
import { usePageTitle } from "@/utils/usePageTitle";
import { cn } from "@/utils/cn";

const SORTS = [
  { id: "featured", label: "Featured" },
  { id: "newest", label: "Newest" },
  { id: "oldest", label: "Oldest" },
  { id: "price-asc", label: "Price: Low to High" },
  { id: "price-desc", label: "Price: High to Low" },
  { id: "name-asc", label: "Name: A-Z" },
  { id: "name-desc", label: "Name: Z-A" },
  { id: "rating", label: "Top Rated" },
] as const;

const CATEGORY_TABS: Array<{ id: Category | "all"; label: string }> = [
  { id: "all", label: "All" },
  { id: "women", label: "Women" },
  { id: "men", label: "Men" },
  { id: "accessories", label: "Accessories" },
];

export default function ShopPage() {
  const [params, setParams] = useSearchParams();
  const category = (params.get("category") as Category | null) ?? null;
  const query = params.get("q") ?? "";
  const sort = params.get("sort") ?? "featured";

  const [filters, setFilters] = useState<ShopFilters>(EMPTY_FILTERS);
  const [loading, setLoading] = useState(true);
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [results, setResults] = useState<Product[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [loadError, setLoadError] = useState("");
  const listProducts = useCatalogStore((s) => s.listProducts);
  const page = Number(params.get("page") ?? "1");

  const heading = query
    ? `Search: “${query}”`
    : category
      ? CATEGORY_TABS.find((t) => t.id === category)?.label ?? "Shop"
      : "Shop All";

  usePageTitle(heading === "Shop All" ? "Shop All" : heading, "Browse the full JAAJ collection — women, men and accessories in organic fabrics and quiet design.");

  useEffect(() => {
    let alive = true;
    setLoading(true);
    setLoadError("");
    listProducts({
      category,
      q: query,
      sort,
      sizes: filters.sizes,
      colors: filters.colors,
      minPrice: filters.minPrice,
      maxPrice: filters.maxPrice,
      inStockOnly: filters.inStockOnly,
      page,
      perPage: 12,
    })
      .then((response) => {
        if (!alive) return;
        setResults(response.data);
        setMeta(response.meta ?? null);
      })
      .catch(() => {
        if (!alive) return;
        setResults([]);
        setMeta(null);
        setLoadError("Products could not be loaded. Please try again.");
      })
      .finally(() => {
        if (alive) setLoading(false);
      });
    return () => {
      alive = false;
    };
  }, [category, query, sort, filters, page, listProducts]);

  useEffect(() => {
    document.body.style.overflow = filtersOpen ? "hidden" : "";
    return () => {
      document.body.style.overflow = "";
    };
  }, [filtersOpen]);

  const setCategory = (id: Category | "all") => {
    const next = new URLSearchParams(params);
    if (id === "all") next.delete("category");
    else next.set("category", id);
    next.delete("page");
    setParams(next, { preventScrollReset: true });
  };

  const setSort = (value: string) => {
    const next = new URLSearchParams(params);
    if (value === "featured") next.delete("sort");
    else next.set("sort", value);
    next.delete("page");
    setParams(next, { preventScrollReset: true });
  };

  const clearQuery = () => {
    const next = new URLSearchParams(params);
    next.delete("q");
    next.delete("page");
    setParams(next, { preventScrollReset: true });
  };

  const setPage = (nextPage: number) => {
    const next = new URLSearchParams(params);
    if (nextPage <= 1) next.delete("page");
    else next.set("page", String(nextPage));
    setParams(next);
  };

  const filterCount = activeFilterCount(filters);

  const panel = (
    <FiltersPanel
      filters={filters}
      onChange={(next) => {
        setFilters(next);
        const updated = new URLSearchParams(params);
        updated.delete("page");
        setParams(updated, { preventScrollReset: true });
      }}
      onReset={() => {
        setFilters(EMPTY_FILTERS);
        const updated = new URLSearchParams(params);
        updated.delete("page");
        setParams(updated, { preventScrollReset: true });
      }}
    />
  );

  return (
    <div className="bg-paper dark:bg-nox">
      {/* Page head */}
      <div className="border-b border-line dark:border-line-dark">
        <div className="mx-auto max-w-[1440px] px-4 py-12 sm:px-6 lg:px-10 lg:py-16">
          <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">JAAJ Collection</p>
          <div className="mt-3 flex flex-wrap items-end justify-between gap-4">
            <h1 className="font-display text-4xl text-ink sm:text-5xl dark:text-linen">{heading}</h1>
            <p className="text-sm text-smoke dark:text-linen-dim" aria-live="polite">
              {loading ? "Loading..." : `${meta?.total ?? results.length} ${(meta?.total ?? results.length) === 1 ? "product" : "products"}`}
            </p>
          </div>
          {query && (
            <button
              type="button"
              onClick={clearQuery}
              className="mt-4 inline-flex items-center gap-2 border border-line px-4 py-2 text-xs font-bold tracking-[0.14em] uppercase text-ink transition-colors hover:border-ink dark:border-line-dark dark:text-linen dark:hover:border-linen"
            >
              <X className="h-3.5 w-3.5" aria-hidden /> Clear search
            </button>
          )}
        </div>
      </div>

      <div className="mx-auto flex max-w-[1440px] gap-10 px-4 py-10 sm:px-6 lg:px-10 lg:py-14">
        {/* Desktop filters */}
        <aside className="hidden w-60 shrink-0 lg:block" aria-label="Product filters">
          <div className="sticky top-32">{panel}</div>
        </aside>

        {/* Results */}
        <div className="min-w-0 flex-1">
          <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
            {/* Category tabs */}
            <div className="flex flex-wrap gap-1.5" role="group" aria-label="Filter by collection">
              {CATEGORY_TABS.map((tab) => (
                <button
                  key={tab.id}
                  type="button"
                  aria-pressed={(category ?? "all") === tab.id}
                  onClick={() => setCategory(tab.id)}
                  className={cn(
                    "px-4 py-2 text-[11px] font-bold tracking-[0.14em] uppercase transition-all",
                    (category ?? "all") === tab.id
                      ? "bg-ink text-paper dark:bg-linen dark:text-nox"
                      : "border border-line text-smoke hover:border-ink hover:text-ink dark:border-line-dark dark:text-linen-dim dark:hover:border-linen dark:hover:text-linen"
                  )}
                >
                  {tab.label}
                </button>
              ))}
            </div>

            <div className="flex items-center gap-2.5">
              <button
                type="button"
                onClick={() => setFiltersOpen(true)}
                className="inline-flex items-center gap-2 border border-line px-4 py-2 text-[11px] font-bold tracking-[0.14em] uppercase text-ink lg:hidden dark:border-line-dark dark:text-linen"
              >
                <SlidersHorizontal className="h-3.5 w-3.5" aria-hidden />
                Filters{filterCount > 0 ? ` (${filterCount})` : ""}
              </button>
              <label htmlFor="sort-select" className="sr-only">
                Sort products
              </label>
              <select
                id="sort-select"
                value={sort}
                onChange={(event) => setSort(event.target.value)}
                className="h-9 cursor-pointer border border-line bg-transparent px-3 text-[11px] font-bold tracking-[0.1em] uppercase text-ink focus-visible:outline-2 focus-visible:outline-bronze dark:border-line-dark dark:text-linen"
              >
                {SORTS.map((option) => (
                  <option key={option.id} value={option.id}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {loadError ? (
            <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
              <EmptyState
                icon={SearchX}
                title="Products unavailable"
                body={loadError}
                actionLabel="Try again"
                actionTo="/shop"
              />
            </div>
          ) : loading ? (
            <div className="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3">
              {Array.from({ length: 8 }).map((_, i) => (
                <ProductCardSkeleton key={i} />
              ))}
            </div>
          ) : results.length === 0 ? (
            <div className="border border-line bg-cream dark:border-line-dark dark:bg-nox2">
              <EmptyState
                icon={SearchX}
                title={query ? `No results for “${query}”` : "No products match your filters"}
                body="Try removing a filter or widening your price range — the collection is deep."
                actionLabel="Reset all filters"
                actionTo="/shop"
              />
            </div>
          ) : (
            <>
              <div className="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-3">
                {results.map((product, i) => (
                  <ProductCard key={product.id} product={product} priority={i < 6} />
                ))}
              </div>
              {meta && meta.last_page > 1 && (
                <nav className="mt-10 flex items-center justify-between border-t border-line pt-6 dark:border-line-dark" aria-label="Product pages">
                  <button
                    type="button"
                    disabled={meta.current_page <= 1}
                    onClick={() => setPage(meta.current_page - 1)}
                    className="border border-line px-4 py-2 text-[11px] font-bold tracking-[0.14em] uppercase text-ink transition-colors hover:border-ink disabled:opacity-40 dark:border-line-dark dark:text-linen"
                  >
                    Previous
                  </button>
                  <span className="text-xs font-semibold text-smoke dark:text-linen-dim">
                    Page {meta.current_page} of {meta.last_page}
                  </span>
                  <button
                    type="button"
                    disabled={meta.current_page >= meta.last_page}
                    onClick={() => setPage(meta.current_page + 1)}
                    className="border border-line px-4 py-2 text-[11px] font-bold tracking-[0.14em] uppercase text-ink transition-colors hover:border-ink disabled:opacity-40 dark:border-line-dark dark:text-linen"
                  >
                    Next
                  </button>
                </nav>
              )}
            </>
          )}
        </div>
      </div>

      {/* Mobile filter drawer */}
      <div className={cn("fixed inset-0 z-50 lg:hidden", !filtersOpen && "pointer-events-none")} aria-hidden={!filtersOpen}>
        <div
          className={cn(
            "absolute inset-0 bg-ink/45 transition-opacity duration-300",
            filtersOpen ? "opacity-100" : "opacity-0"
          )}
          onClick={() => setFiltersOpen(false)}
        />
        <div
          role="dialog"
          aria-modal="true"
          aria-label="Filters"
          className={cn(
            "absolute inset-x-0 bottom-0 max-h-[82vh] overflow-y-auto rounded-t-none bg-cream px-5 pt-4 pb-8 shadow-2xl transition-transform duration-300 dark:bg-nox2",
            filtersOpen ? "translate-y-0" : "translate-y-full"
          )}
        >
          <div className="mb-2 flex items-center justify-between">
            <span className="text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">Refine</span>
            <button
              type="button"
              onClick={() => setFiltersOpen(false)}
              aria-label="Close filters"
              className="flex h-10 w-10 items-center justify-center rounded-full hover:bg-fog dark:hover:bg-nox3"
            >
              <X className="h-5 w-5 text-ink dark:text-linen" aria-hidden />
            </button>
          </div>
          {panel}
          <button
            type="button"
            onClick={() => setFiltersOpen(false)}
            className="mt-6 w-full bg-ink py-4 text-xs font-bold tracking-[0.18em] uppercase text-paper dark:bg-linen dark:text-nox"
          >
            Show {results.length} results
          </button>
        </div>
      </div>
    </div>
  );
}
