import { useState } from "react";
import type { FormEvent } from "react";
import { ArrowRight, Check } from "lucide-react";
import { CLUB } from "@/data/siteCopy";
import { Reveal } from "@/components/ui/Reveal";
import giftbox from "@/assets/imagery/club-giftbox.jpg";

/**
 * JOIN THE JAAJ CLUB — split block: capture form on ink panel,
 * gift-box photography on the right, geometry per SOURCE 01.
 */

export function ClubNewsletter() {
  const [email, setEmail] = useState("");
  const [done, setDone] = useState(false);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (!email.trim()) return;
    setDone(true); // Part 3 wires the real service call.
  };

  return (
    <section aria-label={CLUB.title} className="mx-auto w-full max-w-[1440px] px-5 pb-14 pt-2 md:px-10 md:pb-20">
      <div className="grid grid-cols-1 md:grid-cols-2">
        <Reveal className="flex">
          <div className="flex min-h-[340px] w-full flex-col items-start justify-center bg-ink p-9 text-ink-foreground md:min-h-[400px] lg:p-16">
            <h2 className="font-display text-[24px] font-extrabold uppercase leading-tight tracking-[0.05em] lg:text-[30px]">
              {CLUB.title}
            </h2>
            <p className="mt-4 max-w-[360px] text-[13.5px] leading-relaxed text-ink-muted">
              {CLUB.description}
            </p>

            {done ? (
              <p className="mt-8 flex items-center gap-3 border border-ink-line px-5 py-4 text-[12px] uppercase tracking-caption text-ink-foreground">
                <Check size={16} strokeWidth={1.8} absoluteStrokeWidth className="text-accent-strong" />
                Welcome to the club.
              </p>
            ) : (
              <form onSubmit={submit} className="mt-8 flex w-full max-w-[400px]" aria-label={CLUB.submitLabel}>
                <label htmlFor="club-email" className="sr-only">
                  {CLUB.inputPlaceholder}
                </label>
                <input
                  id="club-email"
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder={CLUB.inputPlaceholder}
                  className="h-12 min-w-0 flex-1 border border-ink-line bg-transparent px-4 text-[13px] text-ink-foreground placeholder:text-ink-muted/80 focus:border-ink-foreground/60 focus:outline-none"
                />
                <button
                  type="submit"
                  aria-label={CLUB.submitLabel}
                  className="flex h-12 w-14 shrink-0 items-center justify-center bg-white text-black transition-colors duration-300 hover:bg-accent-strong hover:text-white"
                >
                  <ArrowRight size={17} strokeWidth={1.7} absoluteStrokeWidth />
                </button>
              </form>
            )}
          </div>
        </Reveal>

        <Reveal delay={0.1} className="relative">
          <div className="relative aspect-[16/12] h-full min-h-[280px] overflow-hidden md:aspect-auto">
            <img
              src={giftbox}
              alt="JAAJ gift box wrapped with a ribbon"
              loading="lazy"
              draggable={false}
              className="img-bw absolute inset-0 h-full w-full object-cover"
            />
          </div>
        </Reveal>
      </div>
    </section>
  );
}
