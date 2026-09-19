import { CreditCard, Headphones, RotateCcw, Truck } from "lucide-react";
import { SERVICE_POINTS } from "@/data/navigation";
import { Reveal } from "@/components/ui/Reveal";

/** Service bar — FREE SHIPPING / EASY RETURNS / SECURE PAYMENT / 24/7 SUPPORT. */

const ICONS = {
  truck: Truck,
  returns: RotateCcw,
  payment: CreditCard,
  support: Headphones,
} as const;

export function ServiceBar() {
  return (
    <section aria-label="Services" className="border-y border-line">
      <div className="mx-auto grid w-full max-w-[1440px] grid-cols-1 gap-7 px-5 py-10 sm:grid-cols-2 md:px-10 lg:grid-cols-4">
        {SERVICE_POINTS.map((point, i) => {
          const Icon = ICONS[point.icon];
          return (
            <Reveal key={point.title} delay={i * 0.07}>
              <div className="flex items-center gap-4">
                <Icon size={24} strokeWidth={1.3} absoluteStrokeWidth className="shrink-0 text-foreground" />
                <div>
                  <h3 className="font-display text-[11px] font-bold uppercase tracking-caption">
                    {point.title}
                  </h3>
                  <p className="mt-1.5 text-[12px] text-muted">{point.description}</p>
                </div>
              </div>
            </Reveal>
          );
        })}
      </div>
    </section>
  );
}
