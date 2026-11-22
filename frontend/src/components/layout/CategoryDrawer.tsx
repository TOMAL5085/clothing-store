import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { AnimatePresence, motion } from "framer-motion";
import { ChevronDown, ChevronRight, LoaderCircle, X } from "lucide-react";
import { categoryShopPath, fetchCategoryTree, type StoreCategory } from "@/lib/categories";
import { useUiStore } from "@/store/uiStore";

export function CategoryDrawer() {
  const open = useUiStore((state) => state.categoryDrawerOpen);
  const setOpen = useUiStore((state) => state.setCategoryDrawerOpen);
  const [categories, setCategories] = useState<StoreCategory[]>([]);
  const [loading, setLoading] = useState(false);
  const [loadError, setLoadError] = useState(false);
  const [expanded, setExpanded] = useState<string[]>([]);

  useEffect(() => {
    if (!open) return;
    let current = true;
    setLoading(true);
    setLoadError(false);
    fetchCategoryTree()
      .then((data) => {
        if (current) setCategories(data);
      })
      .catch(() => {
        if (current) setLoadError(true);
      })
      .finally(() => {
        if (current) setLoading(false);
      });
    return () => {
      current = false;
    };
  }, [open]);

  useEffect(() => {
    if (!open) return;
    const previousOverflow = document.body.style.overflow;
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") setOpen(false);
    };
    document.body.style.overflow = "hidden";
    document.addEventListener("keydown", handleKeyDown);
    return () => {
      document.body.style.overflow = previousOverflow;
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [open, setOpen]);

  const toggleExpanded = (slug: string) => {
    setExpanded((current) => current.includes(slug)
      ? current.filter((item) => item !== slug)
      : [...current, slug]);
  };

  return (
    <AnimatePresence>
      {open && (
        <>
          <motion.div
            className="fixed inset-0 z-[60] bg-black/50"
            initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}
            onClick={() => setOpen(false)} aria-hidden="true"
          />
          <motion.aside
            id="category-navigation" role="dialog" aria-modal="true" aria-label="Product categories"
            className="fixed inset-y-0 left-0 z-[70] flex w-[90vw] max-w-md flex-col border-r border-line bg-background shadow-2xl"
            initial={{ x: "-100%" }} animate={{ x: 0 }} exit={{ x: "-100%" }}
            transition={{ duration: 0.35, ease: [0.22, 1, 0.36, 1] }}
          >
            <div className="flex h-16 items-center justify-between border-b border-line px-5">
              <div>
                <p className="text-[10px] font-bold tracking-[0.2em] uppercase text-bronze">Shop</p>
                <h2 className="mt-0.5 font-display text-xl text-foreground">Categories</h2>
              </div>
              <button type="button" onClick={() => setOpen(false)} aria-label="Close categories" className="flex h-10 w-10 items-center justify-center text-foreground/80 hover:text-foreground">
                <X size={20} strokeWidth={1.6} />
              </button>
            </div>

            <div className="flex-1 overflow-y-auto px-5 py-5">
              {loading && <div className="flex justify-center py-10"><LoaderCircle className="h-5 w-5 animate-spin" /></div>}
              {loadError && <p className="py-6 text-sm text-smoke">Categories could not be loaded. Please try again.</p>}
              {!loading && !loadError && categories.length === 0 && <p className="py-6 text-sm text-smoke">No categories are available yet.</p>}
              {!loading && !loadError && categories.map((category) => {
                const hasChildren = category.children.length > 0;
                const isExpanded = expanded.includes(category.slug);
                return (
                  <section key={category.slug} className="border-b border-line py-1">
                    <div className="flex items-center gap-2">
                      <Link to={categoryShopPath(category.slug)} onClick={() => setOpen(false)} className="flex-1 py-4 font-sans text-sm font-bold uppercase tracking-[0.14em] text-foreground transition-colors hover:text-bronze">
                        {category.label}
                      </Link>
                      {hasChildren && (
                        <button type="button" onClick={() => toggleExpanded(category.slug)} aria-expanded={isExpanded} aria-label={`${isExpanded ? "Hide" : "Show"} ${category.label} subcategories`} className="flex h-9 w-9 items-center justify-center text-foreground/70 hover:text-foreground">
                          {isExpanded ? <ChevronDown size={17} /> : <ChevronRight size={17} />}
                        </button>
                      )}
                    </div>
                    {hasChildren && isExpanded && (
                      <ul className="mb-3 space-y-1 border-l border-line pl-4">
                        {category.children.map((child) => (
                          <li key={child.slug}>
                            <Link to={categoryShopPath(child.slug)} onClick={() => setOpen(false)} className="block py-2 text-sm text-foreground/70 transition-colors hover:text-bronze">{child.label}</Link>
                          </li>
                        ))}
                      </ul>
                    )}
                  </section>
                );
              })}
            </div>
            <Link to="/categories" onClick={() => setOpen(false)} className="flex items-center justify-between border-t border-line px-5 py-5 text-[11px] font-bold tracking-[0.18em] uppercase text-bronze hover:text-foreground">
              View all categories <span aria-hidden="true">→</span>
            </Link>
          </motion.aside>
        </>
      )}
    </AnimatePresence>
  );
}
