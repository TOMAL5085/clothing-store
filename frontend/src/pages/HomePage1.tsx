import { Link } from "react-router-dom";
import { Leaf, Package, RefreshCcw, Scissors } from "lucide-react";
import { CATEGORIES, PRODUCTS } from "@/data/products";
import { HeroSection } from "@/components/home/HeroSection";
import { ProductCard } from "@/components/product/ProductCard";
import { SectionHeading } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

const px = (id: number, w: number, h: number) =>
  `https://images.pexels.com/photos/${id}/pexels-photo-${id}.jpeg?auto=compress&cs=tinysrgb&fit=crop&w=${w}&h=${h}`;

const VALUES = [
  { icon: Leaf, title: "97% Organic Fabrics", body: "Traceable cotton, wool & cupro" },
  { icon: Scissors, title: "Small Batches", body: "Cut in 42 partner studios" },
  { icon: Package, title: "Carbon-Neutral Delivery", body: "Free over $200, worldwide" },
  { icon: RefreshCcw, title: "30-Day Returns", body: "Free size exchanges, always" },
];

const MARQUEE = ["JAAJ", "Quiet Icons", "Considered Essentials", "Est. 2019", "Made in Small Batches"];

export default function HomePage() {
  usePageTitle();

  const newArrivals = [...PRODUCTS.filter((p) => p.isNew), ...PRODUCTS.filter((p) => p.bestseller)].slice(0, 8);
  const bestsellers = PRODUCTS.filter((p) => p.bestseller).slice(0, 4);

  return (
    <>
      <HeroSection />

      {/* Category trio */}
      <section className="mx-auto max-w-[1440px] px-4 py-16 sm:px-6 lg:px-10 lg:py-24" aria-labelledby="categories-heading">
        <SectionHeading
          kicker="Collections"
          title="Shop by Collection"
          body="Three worlds, one wardrobe. Start where you live most."
        />
        <h2 id="categories-heading" className="sr-only">Shop by collection</h2>
        <div className="mt-10 grid gap-3 sm:grid-cols-3">
          {CATEGORIES.map((category, i) => (
            <Link
              key={category.id}
              to={`/shop?category=${category.id}`}
              aria-label={`Shop ${category.label} — ${category.tagline}`}
              className="group relative block overflow-hidden bg-fog dark:bg-nox2"
            >
              <div className="aspect-[4/5] w-full">
                <img
                  src={category.image}
                  alt={category.alt}
                  width={800}
                  height={1066}
                  loading={i === 0 ? "eager" : "lazy"}
                  decoding="async"
                  className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.05]"
                />
              </div>
              <div className="absolute inset-0 bg-gradient-to-t from-ink/70 via-transparent to-transparent" aria-hidden />
              <div className="absolute inset-x-0 bottom-0 flex items-end justify-between p-6">
                <div>
                  <p className="font-display text-3xl text-cream">{category.label}</p>
                  <p className="mt-1 text-xs font-semibold tracking-[0.14em] uppercase text-paper/75">
                    {category.tagline}
                  </p>
                </div>
                <span
                  aria-hidden
                  className="flex h-11 w-11 items-center justify-center rounded-full border border-cream/50 text-cream transition-all duration-300 group-hover:bg-cream group-hover:text-ink"
                >
                  →
                </span>
              </div>
            </Link>
          ))}
        </div>
      </section>

      {/* New arrivals */}
      <section className="mx-auto max-w-[1440px] px-4 pb-16 sm:px-6 lg:px-10 lg:pb-24" aria-labelledby="new-heading">
        <SectionHeading
          kicker="Just Landed"
          title="New This Season"
          body="Fresh from the jaaj studio — the pieces our editors are wearing now."
          actionLabel="View all new"
          actionTo="/shop?sort=newest"
        />
        <h2 id="new-heading" className="sr-only">New arrivals</h2>
        <div className="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 lg:grid-cols-4">
          {newArrivals.map((product, i) => (
            <ProductCard key={product.id} product={product} priority={i < 4} />
          ))}
        </div>
      </section>

      {/* Editorial split */}
      <section className="border-y border-line bg-fog dark:border-line-dark dark:bg-nox2" aria-labelledby="studio-heading">
        <div className="mx-auto grid max-w-[1440px] items-stretch gap-0 lg:grid-cols-2">
          <div className="relative min-h-[420px] overflow-hidden lg:min-h-[620px]">
            <img
              src={px(7760996, 1100, 1300)}
              alt="Studio portrait from the JAAJ campaign"
              width={1100}
              height={1300}
              loading="lazy"
              decoding="async"
              className="absolute inset-0 h-full w-full object-cover transition-transform duration-[1200ms] ease-out hover:scale-[1.03]"
            />
          </div>
          <div className="flex flex-col justify-center gap-6 px-6 py-16 sm:px-10 lg:px-20 lg:py-24">
            <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">The JAAJ Standard</p>
            <h2 id="studio-heading" className="font-display text-4xl leading-tight text-ink sm:text-5xl dark:text-linen">
              Made slowly, in small batches meant to be kept.
            </h2>
            <p className="max-w-md text-sm leading-relaxed text-smoke sm:text-base dark:text-linen-dim">
              Every JAAJ piece begins as cloth we can trace and a pattern we've cut a dozen times.
              We produce less, choose better and stand behind every stitch — so your wardrobe gets
              quieter, and better, every year.
            </p>
            <dl className="grid grid-cols-3 gap-6 border-y border-line py-6 dark:border-line-dark">
              {[
                ["97%", "Organic fabrics"],
                ["42", "Partner studios"],
                ["2019", "Est. Copenhagen"],
              ].map(([value, label]) => (
                <div key={label}>
                  <dt className="sr-only">{label}</dt>
                  <dd className="font-display text-2xl text-ink sm:text-3xl dark:text-linen">{value}</dd>
                  <dd className="mt-1 text-[10px] font-bold tracking-[0.16em] uppercase text-smoke dark:text-linen-dim">
                    {label}
                  </dd>
                </div>
              ))}
            </dl>
            <div>
              <Link
                to="/shop"
                className="inline-flex items-center gap-2 border border-ink px-8 py-4 text-xs font-bold tracking-[0.16em] uppercase text-ink transition-colors hover:bg-ink hover:text-paper dark:border-linen dark:text-linen dark:hover:bg-linen dark:hover:text-nox"
              >
                Explore the Collection
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Bestsellers */}
      <section className="mx-auto max-w-[1440px] px-4 py-16 sm:px-6 lg:px-10 lg:py-24" aria-labelledby="best-heading">
        <SectionHeading
          kicker="Most Wanted"
          title="The Bestsellers"
          body="Kept, re-ordered and worn on repeat by the JAAJ community."
          actionLabel="Shop bestsellers"
          actionTo="/shop"
        />
        <h2 id="best-heading" className="sr-only">Bestsellers</h2>
        <div className="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 lg:grid-cols-4">
          {bestsellers.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>
      </section>

      {/* Values */}
      <section className="border-y border-line bg-cream dark:border-line-dark dark:bg-nox2" aria-label="Our commitments">
        <div className="mx-auto grid max-w-[1440px] grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
          {VALUES.map(({ icon: Icon, title, body }, i) => (
            <div
              key={title}
              className={`flex flex-col items-start gap-3 px-6 py-8 lg:px-10 lg:py-10 ${
                i > 0 ? "border-t border-line sm:border-t-0 sm:border-l dark:border-line-dark" : ""
              }`}
            >
              <Icon className="h-5 w-5 text-bronze" aria-hidden />
              <p className="text-sm font-bold text-ink dark:text-linen">{title}</p>
              <p className="text-xs leading-relaxed text-smoke dark:text-linen-dim">{body}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Marquee */}
      <div className="overflow-hidden border-b border-line py-8 dark:border-line-dark" aria-hidden>
        <div className="animate-marquee flex w-max items-center gap-10 whitespace-nowrap">
          {[...MARQUEE, ...MARQUEE, ...MARQUEE, ...MARQUEE].map((word, i) => (
            <span key={i} className="flex items-center gap-10">
              <span className="font-display text-4xl text-ink/15 sm:text-6xl dark:text-linen/10">{word}</span>
              <span className="h-2 w-2 rotate-45 bg-bronze/40" />
            </span>
          ))}
        </div>
      </div>
    </>
  );
}
