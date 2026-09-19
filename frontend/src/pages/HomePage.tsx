import { Hero } from "@/components/home/Hero";
import { ProductRail } from "@/components/home/ProductRail";
import { CollectionSplit } from "@/components/home/CollectionSplit";
import { CategoryStrip } from "@/components/home/CategoryStrip";
import { ServiceBar } from "@/components/home/ServiceBar";
import { Experience } from "@/components/home/Experience";
import { InstagramGallery } from "@/components/home/InstagramGallery";
import { ClubNewsletter } from "@/components/home/ClubNewsletter";
import { NEW_DROP, BEST_SELLERS } from "@/data/siteCopy";
import { useCatalogStore } from "@/store/catalogStore";

/**
 * Homepage — section order locked to SOURCE 01:
 * Hero → NEW DROP → MEN/WOMEN split → SHOP BY CATEGORY → Service bar
 * → BEST SELLERS → THE JAAJ EXPERIENCE → INSTAGRAM GALLERY → JAAJ CLUB.
 */

export default function HomePage() {
  const products = useCatalogStore((s) => s.products);
  const newDropProducts = products.filter((p) => p.isNew || p.badge === "New");
  const bestSellerProducts = products.filter((p) => p.bestseller || p.badge === "Bestseller");

  return (
    <>
      <Hero />
      <ProductRail
        title={NEW_DROP.title}
        viewAllLabel={NEW_DROP.viewAll}
        viewAllHref={NEW_DROP.viewAllHref}
        products={newDropProducts}
      />
      <div className="pb-12 md:pb-16">
        <CollectionSplit />
      </div>
      <CategoryStrip />
      <ServiceBar />
      <ProductRail
        title={BEST_SELLERS.title}
        viewAllLabel={BEST_SELLERS.viewAll}
        viewAllHref={BEST_SELLERS.viewAllHref}
        products={bestSellerProducts}
      />
      <Experience />
      <InstagramGallery />
      <ClubNewsletter />
    </>
  );
}
