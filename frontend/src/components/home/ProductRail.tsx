import { useRef } from "react";
import type { Product } from "@/data/products";
import { ProductCard } from "@/components/ui/ProductCard";
import { SectionHeader } from "@/components/ui/SectionHeader";
import { Reveal } from "@/components/ui/Reveal";

/**
 * Horizontally scrolling product rail — shared by NEW DROP and BEST SELLERS.
 * Header arrows scroll the rail; geometry per SOURCE 01.
 */

interface ProductRailProps {
  title: string;
  viewAllLabel: string;
  viewAllHref: string;
  products: Product[];
}

export function ProductRail({ title, viewAllLabel, viewAllHref, products }: ProductRailProps) {
  const railRef = useRef<HTMLDivElement>(null);

  const scroll = (direction: 1 | -1) => {
    const rail = railRef.current;
    if (!rail) return;
    rail.scrollBy({ left: direction * rail.clientWidth * 0.72, behavior: "smooth" });
  };

  return (
    <section aria-label={title} className="mx-auto w-full max-w-[1440px] px-5 py-12 md:px-10 md:py-16">
      <Reveal>
        <SectionHeader
          title={title}
          viewAllLabel={viewAllLabel}
          viewAllHref={viewAllHref}
          onPrev={() => scroll(-1)}
          onNext={() => scroll(1)}
        />
      </Reveal>
      <Reveal delay={0.08}>
        <div
          ref={railRef}
          className="no-scrollbar -mx-5 mt-7 flex snap-x snap-mandatory gap-4 overflow-x-auto px-5 pb-1 md:-mx-10 md:mt-9 md:px-10"
        >
          {products.map((product) => (
            <div key={product.id} className="w-[206px] shrink-0 snap-start sm:w-[232px] lg:w-[248px]">
              <ProductCard product={product} />
            </div>
          ))}
        </div>
      </Reveal>
    </section>
  );
}
