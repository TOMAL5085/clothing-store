import { useRef } from "react";
import { INSTAGRAM } from "@/data/siteCopy";
import { INSTAGRAM_GALLERY } from "@/data/gallery";
import { SocialIcon } from "@/components/ui/SocialIcon";
import { ArrowIconButton } from "@/components/ui/ArrowIconButton";
import { Reveal } from "@/components/ui/Reveal";

/**
 * INSTAGRAM GALLERY — monochrome editorial strip with the official handle;
 * hover reveals full color + the Instagram glyph.
 */

export function InstagramGallery() {
  const railRef = useRef<HTMLDivElement>(null);

  const scroll = (direction: 1 | -1) => {
    const rail = railRef.current;
    if (!rail) return;
    rail.scrollBy({ left: direction * rail.clientWidth * 0.6, behavior: "smooth" });
  };

  return (
    <section aria-label={INSTAGRAM.title} className="mx-auto w-full max-w-[1440px] px-5 py-12 md:px-10 md:py-16">
      <Reveal>
        <div className="flex items-end justify-between gap-6">
          <h2 className="font-display text-[17px] font-extrabold uppercase leading-none tracking-[0.16em] md:text-[19px]">
            {INSTAGRAM.title}
          </h2>
          <div className="flex items-center gap-5">
            <a
              href={INSTAGRAM.href}
              target="_blank"
              rel="noreferrer"
              className="font-display text-[11px] font-bold uppercase tracking-nav text-muted transition-colors duration-300 hover:text-foreground"
            >
              {INSTAGRAM.handle}
            </a>
            <div className="hidden items-center gap-2 sm:flex">
              <ArrowIconButton direction="prev" onClick={() => scroll(-1)} label="Scroll gallery back" />
              <ArrowIconButton direction="next" onClick={() => scroll(1)} label="Scroll gallery forward" />
            </div>
          </div>
        </div>
      </Reveal>

      <Reveal delay={0.08}>
        <div
          ref={railRef}
          className="no-scrollbar -mx-5 mt-7 flex snap-x snap-mandatory gap-3 overflow-x-auto px-5 md:-mx-10 md:mt-9 md:px-10"
        >
          {INSTAGRAM_GALLERY.map((image) => (
            <a
              key={image.src}
              href={INSTAGRAM.href}
              target="_blank"
              rel="noreferrer"
              className="group relative aspect-square w-[168px] shrink-0 snap-start overflow-hidden border border-line sm:w-[196px] lg:w-[210px]"
              aria-label={image.alt}
            >
              <img
                src={image.src}
                alt=""
                loading="lazy"
                draggable={false}
                className="img-bw-hover h-full w-full object-cover transition-transform duration-700 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-[1.06]"
              />
              <span className="absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition-all duration-500 group-hover:bg-black/40 group-hover:opacity-100">
                <SocialIcon icon="instagram" size={20} />
              </span>
            </a>
          ))}
        </div>
      </Reveal>
    </section>
  );
}
