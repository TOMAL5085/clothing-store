import { RotateCcw } from "lucide-react";
import { SWATCHES } from "@/data/products";
import { cn } from "@/utils/cn";
import { Input } from "@/components/ui/primitives";

export interface ShopFilters {
  sizes: string[];
  colors: string[];
  minPrice: string;
  maxPrice: string;
  inStockOnly: boolean;
}

export const EMPTY_FILTERS: ShopFilters = {
  sizes: [],
  colors: [],
  minPrice: "",
  maxPrice: "",
  inStockOnly: false,
};

const ALL_SIZES = ["XS", "S", "M", "L", "XL", "One Size"];

function FilterSection({
  title,
  children,
  defaultOpen = true,
}: {
  title: string;
  children: React.ReactNode;
  defaultOpen?: boolean;
}) {
  return (
    <details open={defaultOpen} className="group border-b border-line py-4 dark:border-line-dark">
      <summary className="flex cursor-pointer list-none items-center justify-between text-[11px] font-bold tracking-[0.18em] uppercase text-ink dark:text-linen [&::-webkit-details-marker]:hidden">
        {title}
        <span aria-hidden className="text-sm transition-transform duration-300 group-open:rotate-45">+</span>
      </summary>
      <div className="pt-4">{children}</div>
    </details>
  );
}

export function activeFilterCount(filters: ShopFilters) {
  return (
    filters.sizes.length +
    filters.colors.length +
    (filters.minPrice ? 1 : 0) +
    (filters.maxPrice ? 1 : 0) +
    (filters.inStockOnly ? 1 : 0)
  );
}

export function FiltersPanel({
  filters,
  onChange,
  onReset,
}: {
  filters: ShopFilters;
  onChange: (next: ShopFilters) => void;
  onReset: () => void;
}) {
  const toggleIn = (list: string[], value: string) =>
    list.includes(value) ? list.filter((v) => v !== value) : [...list, value];

  const count = activeFilterCount(filters);

  return (
    <div>
      <div className="flex items-center justify-between pb-1">
        <h2 className="text-xs font-bold tracking-[0.2em] uppercase text-ink dark:text-linen">
          Filters {count > 0 && <span className="text-bronze">({count})</span>}
        </h2>
        <button
          type="button"
          onClick={onReset}
          disabled={count === 0}
          className="inline-flex items-center gap-1.5 text-[11px] font-bold tracking-[0.14em] uppercase text-smoke transition-colors hover:text-bronze disabled:opacity-40 dark:text-linen-dim"
        >
          <RotateCcw className="h-3 w-3" aria-hidden />
          Reset
        </button>
      </div>

      <FilterSection title="Size">
        <div className="flex flex-wrap gap-2">
          {ALL_SIZES.map((size) => {
            const active = filters.sizes.includes(size);
            return (
              <button
                key={size}
                type="button"
                aria-pressed={active}
                onClick={() => onChange({ ...filters, sizes: toggleIn(filters.sizes, size) })}
                className={cn(
                  "min-w-11 border px-3 py-2 text-xs font-semibold transition-all",
                  active
                    ? "border-ink bg-ink text-paper dark:border-linen dark:bg-linen dark:text-nox"
                    : "border-line text-ink hover:border-ink dark:border-line-dark dark:text-linen dark:hover:border-linen"
                )}
              >
                {size}
              </button>
            );
          })}
        </div>
      </FilterSection>

      <FilterSection title="Colour">
        <div className="flex flex-wrap gap-2.5">
          {Object.values(SWATCHES).map((swatch) => {
            const active = filters.colors.includes(swatch.name);
            return (
              <button
                key={swatch.name}
                type="button"
                aria-pressed={active}
                aria-label={`Filter by colour ${swatch.name}`}
                title={swatch.name}
                onClick={() => onChange({ ...filters, colors: toggleIn(filters.colors, swatch.name) })}
                className={cn(
                  "h-8 w-8 rounded-full border transition-all",
                  active
                    ? "scale-110 border-ink ring-2 ring-ink ring-offset-2 ring-offset-paper dark:border-linen dark:ring-linen dark:ring-offset-nox"
                    : "border-line hover:scale-105 dark:border-line-dark"
                )}
                style={{ backgroundColor: swatch.hex }}
              />
            );
          })}
        </div>
      </FilterSection>

      <FilterSection title="Price (USD)">
        <div className="flex items-center gap-2">
          <label className="sr-only" htmlFor="min-price">Minimum price</label>
          <Input
            id="min-price"
            inputMode="numeric"
            placeholder="Min"
            value={filters.minPrice}
            onChange={(e) => onChange({ ...filters, minPrice: e.target.value.replace(/[^0-9]/g, "") })}
          />
          <span className="text-smoke" aria-hidden>—</span>
          <label className="sr-only" htmlFor="max-price">Maximum price</label>
          <Input
            id="max-price"
            inputMode="numeric"
            placeholder="Max"
            value={filters.maxPrice}
            onChange={(e) => onChange({ ...filters, maxPrice: e.target.value.replace(/[^0-9]/g, "") })}
          />
        </div>
      </FilterSection>

      <FilterSection title="Availability">
        <label className="flex cursor-pointer items-center gap-3 text-sm text-ink dark:text-linen">
          <input
            type="checkbox"
            checked={filters.inStockOnly}
            onChange={(e) => onChange({ ...filters, inStockOnly: e.target.checked })}
            className="h-4 w-4 accent-bronze"
          />
          In stock only
        </label>
      </FilterSection>
    </div>
  );
}
