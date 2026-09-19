import { Link } from "react-router-dom";
import { COLLECTION_TILES } from "@/data/siteCopy";
import { Reveal } from "@/components/ui/Reveal";
import menImage from "@/assets/imagery/collection-men.jpg";
import womenImage from "@/assets/imagery/collection-women.jpg";

/**
 * MEN / WOMEN collection split — two tall monochrome tiles,
 * display label + COLLECTION + DISCOVER NOW — dashed rule, per SOURCE 01.
 */

const IMAGES = [menImage, womenImage] as const;

export function CollectionSplit() {
  return (
    <section aria-label="Collections" className="mx-auto w-full max-w-[1440px] px-5 md:px-10">
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        {COLLECTION_TILES.map((tile, i) => (
          <Reveal key={tile.title} delay={i * 0.08}>
            <Link
              to={tile.href}
              className="group relative block aspect-[4/5] overflow-hidden bg-ink sm:aspect-[5/5.4] lg:aspect-[4/4.6]"
              aria-label={`${tile.title} ${tile.subtitle} — ${tile.cta}`}
            >
              <img
                src={IMAGES[i]}
                alt={`${tile.title.toLowerCase()}'s collection editorial`}
                loading="lazy"
                draggable={false}
                className="img-bw h-full w-full object-cover transition-transform duration-[1.2s] ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-[1.05]"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/65 via-transparent to-black/10" />

              <div className="absolute bottom-0 left-0 p-7 lg:p-10">
                <p className="font-display text-[30px] font-extrabold uppercase leading-none tracking-[0.06em] text-white lg:text-[38px]">
                  {tile.title}
                </p>
                <p className="mt-1.5 font-display text-[13px] font-bold uppercase tracking-eyebrow text-white/75">
                  {tile.subtitle}
                </p>
                <p className="mt-5 flex items-center gap-3 text-[11px] font-semibold uppercase tracking-nav text-white/85 transition-colors duration-300 group-hover:text-white">
                  {tile.cta}
                  <span
                    aria-hidden="true"
                    className="h-px w-12 bg-white/70 transition-all duration-500 ease-out group-hover:w-20 group-hover:bg-white"
                  />
                </p>
              </div>
            </Link>
          </Reveal>
        ))}
      </div>
    </section>
  );
}
