import { EXPERIENCE } from "@/data/siteCopy";
import { Button } from "@/components/ui/Button";
import { Logo } from "@/components/layout/Logo";
import { Reveal } from "@/components/ui/Reveal";
import architecture from "@/assets/imagery/experience-architecture.jpg";

/**
 * THE JAAJ EXPERIENCE — ink statement panel beside brand architecture
 * photography carrying the white official wordmark, per SOURCE 01.
 */

export function Experience() {
  return (
    <section aria-label="THE JAAJ EXPERIENCE" className="mx-auto w-full max-w-[1440px] px-5 py-12 md:px-10 md:py-16">
      <div className="grid grid-cols-1 md:grid-cols-2">
        {/* statement panel — ink in both themes, exactly like SOURCE 01 */}
        <Reveal className="flex">
          <div className="flex min-h-[380px] w-full flex-col items-start justify-center bg-ink p-9 text-ink-foreground md:min-h-[460px] lg:p-16">
            <h2 className="font-display text-[30px] font-extrabold uppercase leading-[1.02] tracking-[0.04em] lg:text-[40px]">
              {EXPERIENCE.titleLine1}
              <br />
              {EXPERIENCE.titleLine2}
            </h2>
            <p className="mt-6 font-display text-[13px] font-bold uppercase tracking-caption">
              {EXPERIENCE.statement}
            </p>
            <p className="mt-2.5 max-w-[340px] text-[13.5px] leading-relaxed text-ink-muted">
              {EXPERIENCE.description}
            </p>
            <Button variant="solidWhite" to={EXPERIENCE.cta.href} className="mt-9">
              {EXPERIENCE.cta.label}
            </Button>
          </div>
        </Reveal>

        {/* brand image with official white wordmark */}
        <Reveal delay={0.1} className="relative">
          <div className="group relative aspect-[16/12] h-full min-h-[300px] overflow-hidden md:aspect-auto">
            <img
              src={architecture}
              alt="JAAJ flagship — concrete facade"
              loading="lazy"
              draggable={false}
              className="img-bw absolute inset-0 h-full w-full object-cover transition-transform duration-[1.4s] ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-[1.04]"
            />
            <div className="absolute inset-0 bg-black/30" />
            <div className="absolute inset-0 flex items-center justify-center">
              <Logo
                variant="wordmark"
                tone="onInk"
                width={128}
                className="drop-shadow-md transition-transform duration-[1.2s] ease-out group-hover:scale-105 lg:w-[160px]"
              />
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  );
}
