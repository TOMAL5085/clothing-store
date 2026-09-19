import { Link } from "react-router-dom";
import { Heart } from "lucide-react";
import type { Product } from "@/data/products";
import { Price, Badge } from "@/components/ui/primitives";
import { useWishlistStore } from "@/store/wishlistStore";
import { cn } from "@/utils/cn";

interface ProductCardProps { product: Product; className?: string; }
export function ProductCard({ product, className }: ProductCardProps) {
  const wished = useWishlistStore((s) => s.ids.includes(product.id));
  const toggleWish = useWishlistStore((s) => s.toggle);
  return <article className={cn("group", className)}>
    <Link to={`/product/${product.slug}`} className="block" aria-label={`${product.name} — ${product.price}`}>
      <div className="relative aspect-[4/5] overflow-hidden border border-line bg-surface">
        <img src={product.images[0]} alt={product.alt} loading="lazy" draggable={false} className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.045]" />
        {product.badge && <div className="absolute left-3 top-3"><Badge tone={product.badge === "Sale" ? "sale" : product.badge === "New" ? "ink" : "bronze"}>{product.badge}</Badge></div>}
      </div>
    </Link>
    <div className="flex items-start justify-between gap-3 pt-3">
      <div className="min-w-0"><h3 className="truncate font-display text-[11px] font-bold uppercase tracking-[0.14em]">{product.name}</h3><Price amount={product.price} compareAt={product.compareAt} /></div>
      <button type="button" onClick={() => toggleWish(product.id)} aria-label={wished ? `Remove ${product.name} from wishlist` : `Add ${product.name} to wishlist`} aria-pressed={wished} className="mt-0.5 text-foreground/70"><Heart size={16} className={cn(wished && "fill-accent stroke-accent")} /></button>
    </div>
  </article>;
}
