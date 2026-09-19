import { Link } from "react-router-dom";
import { CATEGORY_STRIP } from "@/data/siteCopy";
import { CATEGORIES } from "@/data/gallery";
import { CategoryIcon } from "@/components/ui/CategoryIcon";
import { Reveal } from "@/components/ui/Reveal";

/**
 * SHOP BY CATEGORY — eight hairline-bordered cells with line icons,
 * exactly the geometry language of SOURCE 01. Hover inverts the cell.
 */

export function CategoryStrip() {
  return (
    <section aria-label={CATEGORY_STRIP.title} className="mx-auto w-full max-w-[1440px] px-5 py-12 md:px-10 md:py-16">
      <Reveal>
        <h2 className="font-display text-[17px] font-extrabold uppercase leading-none tracking-[0.16em] md:text-[19px]">
          {CATEGORY_STRIP.title}
        </h2>
      </Reveal>
      <Reveal delay={0.08}>
        <div className="mt-7 grid grid-cols-2 overflow-hidden border border-line sm:grid-cols-4 md:mt-9 lg:grid-cols-8">
          {CATEGORIES.map((category) => (
            <Link
              key={category.label}
              to={category.href}
              className="group flex h-[120px] -ml-px -mt-px flex-col items-center justify-center gap-3.5 border border-line text-foreground/80 transition-colors duration-300 hover:bg-foreground hover:text-background lg:h-[136px]"
            >
              <CategoryIcon
                icon={category.icon}
                size={28}
                className="transition-transform duration-500 ease-out group-hover:-translate-y-1"
              />
              <span className="font-display text-[10px] font-bold uppercase tracking-caption">
                {category.label}
              </span>
            </Link>
          ))}
        </div>
      </Reveal>
    </section>
  );
}
