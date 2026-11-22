import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { ChevronDown, ChevronRight, LoaderCircle, RefreshCcw } from "lucide-react";
import { categoryShopPath, fetchCategoryTree, type StoreCategory } from "@/lib/categories";
import { usePageTitle } from "@/utils/usePageTitle";

export default function CategoriesPage() {
  usePageTitle("Categories", "Browse every JAAJ category and subcategory.");
  const [categories, setCategories] = useState<StoreCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState(false);
  const [expanded, setExpanded] = useState<string[]>([]);

  const loadCategories = () => {
    setLoading(true);
    setLoadError(false);
    fetchCategoryTree()
      .then((data) => setCategories(data))
      .catch(() => setLoadError(true))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadCategories();
    const refreshWhenVisible = () => {
      if (document.visibilityState === "visible") loadCategories();
    };
    document.addEventListener("visibilitychange", refreshWhenVisible);
    return () => document.removeEventListener("visibilitychange", refreshWhenVisible);
  }, []);

  const toggleExpanded = (slug: string) => setExpanded((current) => current.includes(slug)
    ? current.filter((item) => item !== slug)
    : [...current, slug]);

  return (
    <div className="min-h-[65vh] bg-paper py-10 dark:bg-nox sm:py-14 lg:py-18">
      <div className="mx-auto max-w-[1120px] px-4 sm:px-6 lg:px-10">
        <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Browse the collection</p>
        <div className="mt-3 flex flex-wrap items-end justify-between gap-4 border-b border-line pb-7 dark:border-line-dark">
          <div>
            <h1 className="font-display text-4xl text-ink sm:text-5xl dark:text-linen">All Categories</h1>
            <p className="mt-3 max-w-xl text-sm text-smoke dark:text-linen-dim">Choose a category to shop, or expand it to explore its subcategories.</p>
          </div>
          <button type="button" onClick={loadCategories} disabled={loading} className="inline-flex items-center gap-2 border border-line px-4 py-2 text-[11px] font-bold tracking-[0.14em] uppercase text-ink transition-colors hover:border-ink disabled:opacity-60 dark:border-line-dark dark:text-linen dark:hover:border-linen">
            <RefreshCcw className={loading ? "h-3.5 w-3.5 animate-spin" : "h-3.5 w-3.5"} /> Refresh
          </button>
        </div>

        {loading ? (
          <div className="flex min-h-64 items-center justify-center"><LoaderCircle className="h-6 w-6 animate-spin text-bronze" /></div>
        ) : loadError ? (
          <div className="py-16 text-center"><p className="text-sm text-smoke dark:text-linen-dim">Categories could not be loaded.</p><button type="button" onClick={loadCategories} className="mt-4 text-xs font-bold uppercase tracking-[0.14em] text-bronze">Try again</button></div>
        ) : categories.length === 0 ? (
          <p className="py-16 text-center text-sm text-smoke dark:text-linen-dim">No categories are available yet.</p>
        ) : (
          <div className="mt-8 grid gap-4 md:grid-cols-2">
            {categories.map((category) => {
              const hasChildren = category.children.length > 0;
              const isExpanded = expanded.includes(category.slug);
              return (
                <article key={category.slug} className="border border-line bg-cream p-5 transition-shadow hover:shadow-md dark:border-line-dark dark:bg-nox2 sm:p-6">
                  <div className="flex gap-4">
                    {category.image && <img src={category.image} alt={category.alt || category.label} className="h-16 w-14 shrink-0 object-cover" />}
                    <div className="min-w-0 flex-1">
                      <Link to={categoryShopPath(category.slug)} className="font-display text-2xl text-ink transition-colors hover:text-bronze dark:text-linen">{category.label}</Link>
                      {category.tagline && <p className="mt-1 text-sm text-smoke dark:text-linen-dim">{category.tagline}</p>}
                    </div>
                    {hasChildren && <button type="button" onClick={() => toggleExpanded(category.slug)} aria-expanded={isExpanded} className="flex h-9 w-9 shrink-0 items-center justify-center border border-line text-ink hover:border-ink dark:border-line-dark dark:text-linen dark:hover:border-linen"><span className="sr-only">{isExpanded ? "Hide" : "Show"} subcategories</span>{isExpanded ? <ChevronDown size={17} /> : <ChevronRight size={17} />}</button>}
                  </div>
                  {hasChildren && isExpanded && (
                    <ul className="mt-5 grid gap-2 border-t border-line pt-4 sm:grid-cols-2 dark:border-line-dark">
                      {category.children.map((child) => <li key={child.slug}><Link to={categoryShopPath(child.slug)} className="block py-1 text-sm text-ink/75 transition-colors hover:text-bronze dark:text-linen-dim">{child.label} <span aria-hidden="true">→</span></Link></li>)}
                    </ul>
                  )}
                </article>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
