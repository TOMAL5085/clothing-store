import { useCallback, useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { ArrowLeft, ArrowRight } from "lucide-react";
import { HERO_SLIDES } from "@/data/products";
import { buttonClasses } from "@/components/ui/primitives";
import { cn } from "@/utils/cn";
import jaajLogo from "@/assets/jaaj logo.png";

const AUTOPLAY_MS = 6500;
const HERO_COPY = {
  kicker: "NEW COLLECTION",
  title: "DEFINE YOUR OWN STYLE",
  body: "Premium fabrics. Timeless designs. Made for the modern generation.",
};

function useReducedMotion() {
  const [reduced, setReduced] = useState(
    () => typeof window !== "undefined" && window.matchMedia("(prefers-reduced-motion: reduce)").matches
  );
  useEffect(() => {
    const media = window.matchMedia("(prefers-reduced-motion: reduce)");
    const onChange = () => setReduced(media.matches);
    media.addEventListener("change", onChange);
    return () => media.removeEventListener("change", onChange);
  }, []);
  return reduced;
}

export function HeroSection() {
  const [index, setIndex] = useState(0);
  const [paused, setPaused] = useState(false);
  const reducedMotion = useReducedMotion();
  const timer = useRef<number | null>(null);
  const count = HERO_SLIDES.length;

  const goTo = useCallback(
    (next: number) => setIndex(((next % count) + count) % count),
    [count]
  );

  useEffect(() => {
    if (reducedMotion || paused) return;
    timer.current = window.setTimeout(() => goTo(index + 1), AUTOPLAY_MS);
    return () => {
      if (timer.current) window.clearTimeout(timer.current);
    };
  }, [index, paused, reducedMotion, goTo]);

  return (
    <section
      aria-label="Featured campaigns"
      aria-roledescription="carousel"
      className="relative h-[calc(100svh-104px)] max-h-[860px] min-h-[540px] w-full overflow-hidden bg-ink"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      onFocus={() => setPaused(true)}
      onBlur={() => setPaused(false)}
    >
      {HERO_SLIDES.map((slide, i) => {
        const active = i === index;
        return (
          <div
            key={slide.id}
            role="group"
            aria-roledescription="slide"
            aria-label={`${i + 1} of ${count}: ${HERO_COPY.title}`}
            aria-hidden={!active}
            className={cn(
              "absolute inset-0 transition-opacity duration-1000 ease-out",
              active ? "z-10 opacity-100" : "z-0 opacity-0"
            )}
          >
            <picture>
              <source media="(max-width: 767px)" srcSet={slide.imageMobile} />
              <img
                src={slide.image}
                alt={slide.alt}
                width={1920}
                height={1080}
                loading={i === 0 ? "eager" : "lazy"}
                fetchPriority={i === 0 ? "high" : "auto"}
                decoding="async"
                className={cn(
                  "h-full w-full object-cover object-top transition-transform ease-out",
                  reducedMotion ? "duration-0" : "duration-[7000ms]",
                  active && !reducedMotion ? "scale-105" : "scale-100"
                )}
              />
            </picture>
            <div className="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/25 to-ink/10" aria-hidden />
            <div className="hero-light-sweep" aria-hidden />
            <div className="absolute inset-x-0 bottom-0">
              <div className="mx-auto flex max-w-[1440px] items-end justify-between gap-8 px-4 pb-24 sm:px-6 sm:pb-28 lg:px-10">
                <div
                  className={cn(
                    "max-w-2xl space-y-5",
                    active && !reducedMotion && "animate-fade-up"
                  )}
                >
                  <p className="text-[11px] font-bold tracking-[0.3em] uppercase text-paper/80">
                    {HERO_COPY.kicker}
                  </p>
                  <h1 className="font-display text-5xl leading-[1.02] text-cream sm:text-6xl lg:text-7xl">
                    DEFINE YOUR <br /> OWN STYLE
                  </h1>
                  <p className="max-w-lg text-sm leading-relaxed text-paper/85 sm:text-base">
                    {HERO_COPY.body}
                  </p>
                  <div className="flex flex-wrap gap-3 pt-2">
                    <Link to="/shop" className={buttonClasses("light", "lg")}>
                      Shop All
                    </Link>
                  </div>
                </div>
                <img
                  src={jaajLogo}
                  alt="jaaj logo"
                  className="mb-1 w-24 shrink-0 object-contain sm:w-36 lg:w-48"
                />
              </div>
            </div>
          </div>
        );
      })}

      {/* Controls */}
      <div className="absolute inset-x-0 bottom-0 z-20">
        <div className="mx-auto flex max-w-[1440px] items-end justify-between px-4 pb-6 sm:px-6 lg:px-10">
          <div className="flex items-center gap-3" role="tablist" aria-label="Choose slide">
            {HERO_SLIDES.map((slide, i) => (
              <button
                key={slide.id}
                role="tab"
                aria-selected={index === i}
                aria-label={`Go to slide ${i + 1}: ${HERO_COPY.title}`}
                onClick={() => goTo(i)}
                className="group flex h-10 w-10 items-center justify-center"
              >
                <span
                  className={cn(
                    "h-0.5 w-8 transition-all duration-300",
                    index === i ? "bg-cream" : "bg-cream/35 group-hover:bg-cream/70"
                  )}
                  aria-hidden
                />
              </button>
            ))}
          </div>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => goTo(index - 1)}
              aria-label="Previous slide"
              className="flex h-11 w-11 items-center justify-center rounded-full border border-cream/40 text-cream transition-colors hover:bg-cream hover:text-ink"
            >
              <ArrowLeft className="h-4 w-4" aria-hidden />
            </button>
            <button
              type="button"
              onClick={() => goTo(index + 1)}
              aria-label="Next slide"
              className="flex h-11 w-11 items-center justify-center rounded-full border border-cream/40 text-cream transition-colors hover:bg-cream hover:text-ink"
            >
              <ArrowRight className="h-4 w-4" aria-hidden />
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}
