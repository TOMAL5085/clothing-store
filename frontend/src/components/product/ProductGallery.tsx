import { useState } from "react";
import type { Product } from "@/data/products";
import { Badge } from "@/components/ui/primitives";
import { cn } from "@/utils/cn";

export function ProductGallery({ product }: { product: Product }) {
  const [active, setActive] = useState(0);
  const images = product.images;

  return (
    <div className="flex flex-col-reverse gap-4 sm:flex-row">
      {/* Thumbnails */}
      <div
        className="flex gap-3 sm:flex-col"
        role="tablist"
        aria-label={`${product.name} images`}
      >
        {images.map((src, index) => (
          <button
            key={src + index}
            role="tab"
            aria-selected={active === index}
            aria-label={`View image ${index + 1}`}
            onClick={() => setActive(index)}
            className={cn(
              "relative h-20 w-16 shrink-0 overflow-hidden bg-fog transition-all sm:h-24 sm:w-20 dark:bg-nox2",
              active === index
                ? "ring-2 ring-ink ring-offset-2 ring-offset-paper dark:ring-linen dark:ring-offset-nox"
                : "opacity-60 hover:opacity-100"
            )}
          >
            <img
              src={src}
              alt=""
              width={160}
              height={200}
              loading="lazy"
              decoding="async"
              className="h-full w-full object-cover"
            />
          </button>
        ))}
      </div>

      {/* Main image */}
      <div className="relative flex-1 overflow-hidden bg-fog dark:bg-nox2">
        <div className="aspect-[3/4] w-full">
          <img
            key={images[active]}
            src={images[active]}
            alt={product.alt}
            width={900}
            height={1200}
            loading="eager"
            decoding="async"
            className="h-full w-full animate-fade-up object-cover [animation-duration:0.5s]"
          />
        </div>
        <div className="absolute top-4 left-4 flex flex-col gap-1.5">
          {product.badge === "Sale" && <Badge tone="sale">Sale</Badge>}
          {product.badge === "New" && <Badge tone="ink">New</Badge>}
          {product.badge === "Bestseller" && <Badge tone="bronze">Bestseller</Badge>}
        </div>
      </div>
    </div>
  );
}
