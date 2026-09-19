import { useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { ArrowUpRight, Search, X } from "lucide-react";
import type { Product } from "@/data/products";
import { useCatalogStore } from "@/store/catalogStore";
import { useUiStore } from "@/store/uiStore";
import { Price } from "@/components/ui/primitives";
import { cn } from "@/utils/cn";

const POPULAR = ["Trench", "Blazer", "Hoodie", "Merino", "Leather", "Trouser"];

export function SearchOverlay() {
  const { searchOpen, setSearchOpen } = useUiStore();
  const listProducts = useCatalogStore((s) => s.listProducts);
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<Product[]>([]);
  const [loading, setLoading] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (searchOpen) {
      setQuery("");
      const t = window.setTimeout(() => inputRef.current?.focus(), 80);
      document.body.style.overflow = "hidden";
      const onKey = (event: KeyboardEvent) => {
        if (event.key === "Escape") setSearchOpen(false);
      };
      window.addEventListener("keydown", onKey);
      return () => {
        window.clearTimeout(t);
        window.removeEventListener("keydown", onKey);
        document.body.style.overflow = "";
      };
    }
  }, [searchOpen, setSearchOpen]);

  useEffect(() => {
    if (!searchOpen || !query.trim()) {
      setResults([]);
      setLoading(false);
      return;
    }

    let alive = true;
    setLoading(true);
    const timer = window.setTimeout(() => {
      listProducts({ q: query, perPage: 8 })
        .then((response) => {
          if (alive) setResults(response.data);
        })
        .catch(() => {
          if (alive) setResults([]);
        })
        .finally(() => {
          if (alive) setLoading(false);
        });
    }, 220);

    return () => {
      alive = false;
      window.clearTimeout(timer);
    };
  }, [query, searchOpen, listProducts]);

  const close = () => setSearchOpen(false);

  return (
    <div
      className={cn("fixed inset-0 z-50", !searchOpen && "pointer-events-none")}
      aria-hidden={!searchOpen}
    >
      <div
        className={cn(
          "absolute inset-0 bg-ink/45 backdrop-blur-sm transition-opacity duration-300",
          searchOpen ? "opacity-100" : "opacity-0"
        )}
        onClick={close}
      />
      <div
        role="dialog"
        aria-modal="true"
        aria-label="Search products"
        className={cn(
          "absolute inset-x-0 top-0 max-h-[85vh] overflow-y-auto bg-cream shadow-2xl transition-transform duration-300 ease-out dark:bg-nox2",
          searchOpen ? "translate-y-0" : "-translate-y-full"
        )}
      >
        <div className="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-10">
          <div className="flex items-center gap-4 border-b-2 border-ink pb-4 dark:border-linen">
            <Search className="h-6 w-6 shrink-0 text-ink dark:text-linen" aria-hidden />
            <label htmlFor="site-search" className="sr-only">
              Search products
            </label>
            <input
              ref={inputRef}
              id="site-search"
              type="search"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder="Search trench, blazer, leather…"
              autoComplete="off"
              className="w-full bg-transparent font-display text-2xl text-ink placeholder:text-smoke/50 focus:outline-none sm:text-3xl dark:text-linen dark:placeholder:text-linen-dim/50"
            />
            {query && (
              <button
                type="button"
                onClick={() => setQuery("")}
                className="text-[11px] font-bold tracking-[0.16em] uppercase text-smoke underline-offset-4 hover:underline dark:text-linen-dim"
              >
                Clear
              </button>
            )}
            <button
              type="button"
              onClick={close}
              aria-label="Close search"
              className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-ink transition-colors hover:bg-fog dark:text-linen dark:hover:bg-nox3"
            >
              <X className="h-5 w-5" aria-hidden />
            </button>
          </div>

          <div className="py-6" aria-live="polite">
            {!query.trim() && (
              <div className="space-y-3">
                <p className="text-[11px] font-bold tracking-[0.2em] uppercase text-smoke dark:text-linen-dim">
                  Popular right now
                </p>
                <div className="flex flex-wrap gap-2">
                  {POPULAR.map((term) => (
                    <button
                      key={term}
                      type="button"
                      onClick={() => setQuery(term)}
                      className="border border-line px-4 py-2 text-xs font-semibold text-ink transition-colors hover:border-ink hover:bg-ink hover:text-paper dark:border-line-dark dark:text-linen dark:hover:border-linen dark:hover:bg-linen dark:hover:text-nox"
                    >
                      {term}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {query.trim() && loading && (
              <p className="py-8 text-center text-sm font-semibold text-smoke dark:text-linen-dim">
                Searching...
              </p>
            )}

            {query.trim() && !loading && results.length === 0 && (
              <div className="py-8 text-center">
                <p className="font-display text-2xl text-ink dark:text-linen">
                  Nothing found for “{query}”
                </p>
                <p className="mt-2 text-sm text-smoke dark:text-linen-dim">
                  Try a different keyword, or browse the full collection.
                </p>
                <Link
                  to="/shop"
                  onClick={close}
                  className="mt-5 inline-flex items-center gap-2 text-xs font-bold tracking-[0.16em] uppercase text-bronze hover:underline"
                >
                  Shop all products <ArrowUpRight className="h-4 w-4" aria-hidden />
                </Link>
              </div>
            )}

            {!loading && results.length > 0 && (
              <>
                <p className="mb-4 text-[11px] font-bold tracking-[0.2em] uppercase text-smoke dark:text-linen-dim">
                  {results.length} {results.length === 1 ? "result" : "results"} for “{query}”
                </p>
                <ul className="grid gap-3 sm:grid-cols-2">
                  {results.slice(0, 8).map((product) => (
                    <li key={product.id}>
                      <Link
                        to={`/product/${product.slug}`}
                        onClick={close}
                        className="group flex items-center gap-4 border border-line bg-paper p-2.5 transition-colors hover:border-ink dark:border-line-dark dark:bg-nox dark:hover:border-linen"
                      >
                        <img
                          src={product.images[0]}
                          alt={product.alt}
                          width={120}
                          height={160}
                          loading="lazy"
                          decoding="async"
                          className="h-20 w-14 shrink-0 bg-fog object-cover dark:bg-nox3"
                        />
                        <span className="min-w-0 flex-1">
                          <span className="block truncate text-sm font-semibold text-ink group-hover:text-bronze dark:text-linen">
                            {product.name}
                          </span>
                          <span className="mt-0.5 block text-[10px] font-semibold tracking-[0.16em] uppercase text-smoke dark:text-linen-dim">
                            {product.category}
                          </span>
                          <Price amount={product.price} compareAt={product.compareAt} className="mt-1" />
                        </span>
                        <ArrowUpRight className="h-4 w-4 shrink-0 text-smoke transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" aria-hidden />
                      </Link>
                    </li>
                  ))}
                </ul>
                <div className="pt-5 text-center">
                  <Link
                    to={`/shop?q=${encodeURIComponent(query)}`}
                    onClick={close}
                    className="inline-flex items-center gap-2 border border-ink px-6 py-3 text-[11px] font-bold tracking-[0.18em] uppercase text-ink transition-colors hover:bg-ink hover:text-paper dark:border-linen dark:text-linen dark:hover:bg-linen dark:hover:text-nox"
                  >
                    View all {results.length} results
                  </Link>
                </div>
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
