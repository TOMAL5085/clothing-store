export type Category = "women" | "men" | "accessories";

export interface Swatch {
  name: string;
  hex: string;
}

export interface ProductVariant {
  id: number;
  sku: string;
  size?: string | null;
  color?: Swatch | null;
  price?: number | null;
  stockQuantity: number;
  lowStockThreshold: number;
  inventoryStatus: "in_stock" | "low_stock" | "out_of_stock";
  isActive: boolean;
}

export interface Product {
  id: string;
  slug: string;
  name: string;
  category: Category;
  price: number; // base USD
  compareAt?: number;
  rating: number;
  reviews: number;
  colors: Swatch[];
  sizes: string[];
  images: string[];
  alt: string;
  badge?: "New" | "Bestseller" | "Sale";
  isNew?: boolean;
  bestseller?: boolean;
  description: string;
  details: string[];
  inStock: boolean;
  stockQuantity?: number;
  availableStock?: number;
  lowStockThreshold?: number;
  inventoryStatus?: "in_stock" | "low_stock" | "out_of_stock";
  sku?: string;
  brand?: string;
  variants?: ProductVariant[];
}

/** 
 * Local Public Imagery Resolver
 * Directs image paths to the /public/imagery/ folder
 */
const px = (filename: string) => `/imagery/${filename}`;

export const SWATCHES: Record<string, Swatch> = {
  bone: { name: "Bone", hex: "#e6dfd0" },
  oat: { name: "Oat", hex: "#c8ae87" },
  khaki: { name: "Khaki", hex: "#97906b" },
  mocha: { name: "Mocha", hex: "#6d543c" },
  charcoal: { name: "Charcoal", hex: "#3b3833" },
  ink: { name: "Ink", hex: "#1c1a17" },
  chalk: { name: "Chalk", hex: "#f4f2ec" },
};

const APPAREL_SIZES = ["XS", "S", "M", "L", "XL"];
const ONE_SIZE = ["One Size"];

export const PRODUCTS: Product[] = [
  {
    id: "adele-trench",
    slug: "adele-trench-coat",
    name: "Adele Trench Coat",
    category: "women",
    price: 420,
    rating: 4.9,
    reviews: 214,
    colors: [SWATCHES.oat, SWATCHES.ink],
    sizes: APPAREL_SIZES,
    images: [px("hero-primary.jpg"), px("collection-women.jpg")],
    alt: "Model wearing the Adele trench coat in oat cotton",
    badge: "New",
    isNew: true,
    description:
      "A fluid, double-faced trench cut from bonded organic cotton. Storm flap, horn buttons and a belt that ties — never buckles. The coat you reach for every single morning.",
    details: [
      "100% organic cotton, bonded double-face",
      "Horn buttons, hidden closure",
      "Falls below the knee",
      "Made in Portugal in small batches",
    ],
    inStock: true,
  },
  {
    id: "alba-blazer",
    slug: "alba-oversized-blazer",
    name: "Alba Oversized Blazer",
    category: "women",
    price: 290,
    rating: 4.8,
    reviews: 486,
    colors: [SWATCHES.bone, SWATCHES.charcoal],
    sizes: APPAREL_SIZES,
    images: [px("collection-women.jpg"), px("hero-primary.jpg")],
    alt: "Model in the Alba oversized blazer, beige",
    badge: "Bestseller",
    bestseller: true,
    description:
      "Our signature soft shoulder. The Alba is tailored with a relaxed drop and an easy, unlined body that moves with you — sharp from a distance, soft up close.",
    details: [
      "Wool-blend twill, half canvas",
      "Single-button closure",
      "Relaxed, dropped shoulder",
      "Dry clean only",
    ],
    inStock: true,
  },
  {
    id: "poplin-shirt",
    slug: "poplin-column-shirt",
    name: "Poplin Column Shirt",
    category: "women",
    price: 145,
    rating: 4.7,
    reviews: 168,
    colors: [SWATCHES.chalk, SWATCHES.bone],
    sizes: APPAREL_SIZES,
    images: [px("collection-women.jpg")],
    alt: "Model rolling the sleeves of the white poplin column shirt",
    description:
      "Crisp organic-cotton poplin with a clean, collar-forward stance. Slightly elongated through the body — wear it tucked, half-tucked or open over knitwear.",
    details: [
      "Organic cotton poplin",
      "Mother-of-pearl buttons",
      "Curved hem, side vents",
      "Machine washable",
    ],
    inStock: true,
  },
  {
    id: "rena-hoodie",
    slug: "rena-relaxed-hoodie",
    name: "Rena Relaxed Hoodie",
    category: "women",
    price: 185,
    rating: 4.6,
    reviews: 92,
    colors: [SWATCHES.mocha, SWATCHES.ink],
    sizes: APPAREL_SIZES,
    images: [px("hero-primary.jpg")],
    alt: "Model in the Rena relaxed hoodie in mocha loopback cotton",
    description:
      "Heavyweight loopback cotton with a dropped shoulder and a double-layer hood. Brushed inside, clean outside — the hoodie, elevated.",
    details: [
      "460gsm loopback organic cotton",
      "Double-layer hood",
      "Ribbed cuffs and hem",
      "Garment-dyed, pre-shrunk",
    ],
    inStock: true,
  },
  {
    id: "seda-top",
    slug: "seda-bias-top",
    name: "Seda Bias-Cut Top",
    category: "women",
    price: 155,
    rating: 4.8,
    reviews: 61,
    colors: [SWATCHES.bone, SWATCHES.chalk],
    sizes: APPAREL_SIZES,
    images: [px("collection-women.jpg")],
    alt: "Close portrait of the Seda bias-cut top in bone",
    badge: "New",
    isNew: true,
    description:
      "Cut on the bias so it drapes instead of clings. Sand-washed silk-touch cupro with a clean crew neck — quiet from across the room.",
    details: [
      "Sand-washed cupro, silk hand-feel",
      "Bias-cut body",
      "French seams throughout",
      "Hand wash cold",
    ],
    inStock: true,
  },
  {
    id: "column-trouser",
    slug: "tailored-column-trouser",
    name: "Tailored Column Trouser",
    category: "women",
    price: 210,
    rating: 4.9,
    reviews: 357,
    colors: [SWATCHES.charcoal, SWATCHES.bone],
    sizes: APPAREL_SIZES,
    images: [px("collection-women.jpg")],
    alt: "Model seated wearing the tailored column trouser",
    badge: "Bestseller",
    bestseller: true,
    description:
      "A single, clean crease from hip to hem. High-rise, full-length, endlessly pairable — the trouser our stylists reach for first.",
    details: [
      "Wool-blend suiting with stretch",
      "Hook-and-bar closure",
      "Full-length wide leg",
      "Unfinished hem for tailoring",
    ],
    inStock: true,
  },
  {
    id: "studio-shirt",
    slug: "studio-white-shirt",
    name: "The Studio Shirt",
    category: "women",
    price: 135,
    rating: 4.5,
    reviews: 143,
    colors: [SWATCHES.chalk],
    sizes: APPAREL_SIZES,
    images: [px("collection-women.jpg")],
    alt: "Model in soft light wearing the Studio shirt",
    description:
      "The white shirt, perfected. Mid-weight organic cotton with a soft structure that holds a cuff, a knot, or a clean open collar.",
    details: [
      "Mid-weight organic cotton",
      "Single chest pocket",
      "Curved, elongated hem",
      "Machine washable",
    ],
    inStock: true,
  },
  {
    id: "resort-set",
    slug: "resort-poplin-set",
    name: "Resort Poplin Set",
    category: "women",
    price: 240,
    rating: 4.7,
    reviews: 88,
    colors: [SWATCHES.chalk, SWATCHES.khaki],
    sizes: APPAREL_SIZES,
    images: [px("collection-women.jpg")],
    alt: "Model outdoors in the Resort poplin set",
    badge: "New",
    isNew: true,
    description:
      "A boxy camp shirt and drawstring trouser in airy garment-washed poplin. Sold as a set, worn a hundred ways.",
    details: [
      "Garment-washed organic poplin",
      "Camp collar, boxy body",
      "Drawstring, cropped trouser",
      "Sold as a two-piece set",
    ],
    inStock: true,
  },
  {
    id: "sculpt-dress",
    slug: "sculpt-studio-dress",
    name: "Sculpt Studio Dress",
    category: "women",
    price: 265,
    rating: 4.8,
    reviews: 129,
    colors: [SWATCHES.chalk, SWATCHES.ink],
    sizes: APPAREL_SIZES,
    images: [px("collection-women.jpg")],
    alt: "Model in a minimalist white studio wearing the Sculpt dress",
    description:
      "Architectural ease. A column silhouette with sculpted darts and side splits — dresses up with a heel, down with a slide.",
    details: [
      "Dense crepe jersey",
      "Sculpted dart shaping",
      "Side hem splits",
      "Midi length",
    ],
    inStock: true,
  },
  {
    id: "organic-tee-trio",
    slug: "organic-tee-three-pack",
    name: "Organic Tee — 3 Pack",
    category: "women",
    price: 95,
    rating: 4.9,
    reviews: 1204,
    colors: [SWATCHES.chalk, SWATCHES.bone, SWATCHES.ink],
    sizes: APPAREL_SIZES,
    images: [px("collection-women.jpg")],
    alt: "Folded stack of the organic tee three-pack",
    badge: "Bestseller",
    bestseller: true,
    description:
      "Our perfect tee, three ways. Chalk, bone and ink — in soft, long-staple organic cotton that keeps its shape wash after wash.",
    details: [
      "Long-staple organic cotton",
      "Three colours included",
      "Pre-shrunk, true fit",
      "Machine washable",
    ],
    inStock: true,
  },
  {
    id: "archer-jacket",
    slug: "archer-zip-jacket",
    name: "Archer Zip Jacket",
    category: "men",
    price: 255,
    rating: 4.7,
    reviews: 74,
    colors: [SWATCHES.ink],
    sizes: APPAREL_SIZES,
    images: [px("collection-men.jpg")],
    alt: "Profile of a model wearing the black Archer zip jacket",
    badge: "New",
    isNew: true,
    description:
      "A cropped, clean-front zip jacket in brushed twill. No logos, no noise — just a perfect collar and a two-way zip.",
    details: [
      "Brushed cotton twill",
      "Two-way matte zip",
      "Cropped, boxy fit",
      "Interior security pocket",
    ],
    inStock: true,
  },
  {
    id: "studio-hoodie-men",
    slug: "mens-studio-hoodie",
    name: "Men's Studio Hoodie",
    category: "men",
    price: 125,
    rating: 4.8,
    reviews: 311,
    colors: [SWATCHES.chalk, SWATCHES.charcoal],
    sizes: APPAREL_SIZES,
    images: [px("collection-men.jpg")],
    alt: "Model with glasses wearing the chalk Studio hoodie",
    badge: "Bestseller",
    bestseller: true,
    description:
      "The hoodie we wagered could replace half your wardrobe. Dense, dry-hand fleece with a structured hood that stands on its own.",
    details: [
      "480gsm organic fleece",
      "Structured double hood",
      "Flat drawcords, no aglets",
      "Machine washable",
    ],
    inStock: true,
  },
  {
    id: "merino-crew",
    slug: "traceable-merino-crew",
    name: "Traceable Merino Crew",
    category: "men",
    price: 175,
    rating: 4.8,
    reviews: 156,
    colors: [SWATCHES.charcoal, SWATCHES.oat],
    sizes: APPAREL_SIZES,
    images: [px("collection-men.jpg")],
    alt: "Model holding a hanger with the grey merino crew knit",
    description:
      "Fully traceable extra-fine merino, knitted whole-garment with zero seams. Regulates temperature from October to April.",
    details: [
      "100% traceable extra-fine merino",
      "Seamless whole-garment knit",
      "Naturally breathable",
      "Hand wash or wool cycle",
    ],
    inStock: true,
  },
  {
    id: "sutton-blazer",
    slug: "sutton-single-breasted-blazer",
    name: "Sutton Blazer",
    category: "men",
    price: 340,
    rating: 4.9,
    reviews: 98,
    colors: [SWATCHES.ink, SWATCHES.charcoal],
    sizes: APPAREL_SIZES,
    images: [px("collection-men.jpg")],
    alt: "Relaxed portrait of a model in the black Sutton blazer",
    description:
      "Unstructured tailoring for the office that no longer exists. Soft shoulder, patch pockets, and cloth with enough body to hold a crease without one.",
    details: [
      "Italian wool-silk suiting",
      "Unstructured, unlined body",
      "Patch pockets",
      "Dry clean only",
    ],
    inStock: true,
  },
  {
    id: "onyx-shirt",
    slug: "onyx-slim-shirt",
    name: "Onyx Slim Shirt",
    category: "men",
    price: 150,
    rating: 4.6,
    reviews: 67,
    colors: [SWATCHES.ink],
    sizes: APPAREL_SIZES,
    images: [px("collection-men.jpg")],
    alt: "Model in the slim black Onyx shirt against a neutral backdrop",
    description:
      "A slim, mercerised-cotton shirt with refined button-down collar. The black shirt, done properly — no sheen, no stretch-out.",
    details: [
      "Mercerised organic cotton",
      "Slim tailored fit",
      "Reinforced collar stand",
      "Machine washable",
    ],
    inStock: true,
  },
  {
    id: "jersey-pant",
    slug: "tapered-jersey-pant",
    name: "Tapered Jersey Pant",
    category: "men",
    price: 140,
    rating: 4.7,
    reviews: 203,
    colors: [SWATCHES.charcoal, SWATCHES.bone],
    sizes: APPAREL_SIZES,
    images: [px("collection-men.jpg")],
    alt: "Model seated in the tapered jersey pant",
    description:
      "Dress-pant polish, sweatpant feel. A dense knit jersey with a clean taper and a hidden drawstring waist.",
    details: [
      "Dense double-knit jersey",
      "Hidden drawstring waist",
      "Tapered, cropped leg",
      "Machine washable",
    ],
    inStock: true,
  },
  {
    id: "uniform-set",
    slug: "all-black-uniform-set",
    name: "The Uniform Set",
    category: "men",
    price: 300,
    rating: 4.8,
    reviews: 41,
    colors: [SWATCHES.ink],
    sizes: APPAREL_SIZES,
    images: [px("collection-men.jpg")],
    alt: "Model in the all-black Uniform set on a white backdrop",
    badge: "New",
    isNew: true,
    description:
      "Our Onyx shirt and Jersey pant, boxed as one. The five-second outfit that reads as a decision.",
    details: [
      "Onyx shirt + Jersey pant",
      "Tonal throughout",
      "Boxed set, one price",
      "Machine washable",
    ],
    inStock: true,
  },
  {
    id: "cinder-tote",
    slug: "cinder-leather-tote",
    name: "Cinder Leather Tote",
    category: "accessories",
    price: 390,
    rating: 4.9,
    reviews: 176,
    colors: [SWATCHES.mocha],
    sizes: ONE_SIZE,
    images: [px("club-giftbox.jpg")],
    alt: "The Cinder tote in vegetable-tanned mocha leather on a bench",
    badge: "Bestseller",
    bestseller: true,
    description:
      "Vegetable-tanned leather that records your life in patina. Fits a 16-inch laptop, a novel, and everything the day demands.",
    details: [
      "Full-grain vegetable-tanned leather",
      "Unlined, saddle-stitched",
      "Fits 16″ laptop",
      "Ages to a deep patina",
    ],
    inStock: true,
  },
  {
    id: "oslo-sunglasses",
    slug: "oslo-frame-sunglasses",
    name: "Oslo Frame Sunglasses",
    category: "accessories",
    price: 160,
    rating: 4.7,
    reviews: 89,
    colors: [SWATCHES.ink],
    sizes: ONE_SIZE,
    images: [px("experience-architecture.jpg")],
    alt: "The Oslo sunglasses with leather case on a table",
    badge: "New",
    isNew: true,
    description:
      "Bio-acetate frames with a soft-square lens. Handmade in a family studio, with leather case and cloth included.",
    details: [
      "Mazzucchelli bio-acetate",
      "CR-39 lenses, 100% UV",
      "Five-barrel hinges",
      "Leather case included",
    ],
    inStock: true,
  },
  {
    id: "archive-weekender",
    slug: "archive-weekender-set",
    name: "Archive Weekender Set",
    category: "accessories",
    price: 460,
    compareAt: 520,
    rating: 4.8,
    reviews: 54,
    colors: [SWATCHES.bone, SWATCHES.chalk],
    sizes: ONE_SIZE,
    images: [px("club-giftbox.jpg")],
    alt: "The Archive weekender set with sandals and sunglasses",
    badge: "Sale",
    description:
      "Canvas-and-leather weekender, sandal and frame — the full weekend kit, priced as a set. Cabin-sized, lifetime-backed.",
    details: [
      "22oz canvas, leather trim",
      "Cabin-approved dimensions",
      "Brass hardware",
      "Lifetime repairs",
    ],
    inStock: true,
  },
  {
    id: "carry-duo",
    slug: "everyday-carry-duo",
    name: "Everyday Carry Duo",
    category: "accessories",
    price: 220,
    rating: 4.6,
    reviews: 63,
    colors: [SWATCHES.mocha],
    sizes: ONE_SIZE,
    images: [px("club-giftbox.jpg")],
    alt: "Leather bag and sunglasses on a wooden table",
    description:
      "The Oslo frame and a slim leather sling — the two accessories our editors never leave without, together.",
    details: [
      "Oslo frame + leather sling",
      "Vegetable-tanned leather",
      "Adjustable strap",
      "Gift box included",
    ],
    inStock: true,
  },
  {
    id: "rider-sling",
    slug: "rider-sling-bag",
    name: "Rider Sling Bag",
    category: "accessories",
    price: 230,
    rating: 4.7,
    reviews: 112,
    colors: [SWATCHES.charcoal, SWATCHES.mocha],
    sizes: ONE_SIZE,
    images: [px("club-giftbox.jpg")],
    alt: "Model outdoors carrying the Rider sling bag",
    description:
      "A slim crossbody in waxed canvas and leather, sized to carry the essentials flat against the body. Rides the city with you.",
    details: [
      "Waxed canvas, leather trim",
      "Water-repellent finish",
      "Internal phone sleeve",
      "Lifetime repairs",
    ],
    inStock: true,
  },
];

export const CATEGORIES: Array<{
  id: Category;
  label: string;
  tagline: string;
  image: string;
  alt: string;
}> = [
  {
    id: "women",
    label: "Women",
    tagline: "Soft tailoring & fluid essentials",
    image: px("collection-women.jpg"),
    alt: "Woman against a draped beige backdrop",
  },
  {
    id: "men",
    label: "Men",
    tagline: "The uniform, refined",
    image: px("collection-men.jpg"),
    alt: "Man in minimal black attire on white",
  },
  {
    id: "accessories",
    label: "Accessories",
    tagline: "Objects in leather & acetate",
    image: px("club-giftbox.jpg"),
    alt: "Handbag and accessories on a retail display",
  },
];

export function getProductBySlug(slug: string | undefined) {
  return PRODUCTS.find((product) => product.slug === slug);
}

export function relatedProducts(product: Product, count = 4) {
  return PRODUCTS.filter(
    (candidate) => candidate.category === product.category && candidate.id !== product.id
  ).slice(0, count);
}

export function searchProducts(query: string) {
  const q = query.trim().toLowerCase();
  if (!q) return [];
  return PRODUCTS.filter(
    (product) =>
      product.name.toLowerCase().includes(q) ||
      product.category.includes(q) ||
      product.description.toLowerCase().includes(q)
  );
}

export const HERO_SLIDES = [
  {
    id: "quiet-icons",
    image: px("hero-primary.jpg"),
    imageMobile: px("hero-primary.jpg"),
    alt: "Model in an elegant coat against a neutral studio backdrop",
    kicker: "Autumn / Winter 2025",
    title: "The Quiet Icons",
    body: "Wardrobe pieces with nothing to prove. Soft tailoring, honest cloth, and shapes that outlast seasons.",
    primary: { label: "Shop New Arrivals", to: "/shop?sort=newest" },
    secondary: { label: "Explore Women", to: "/shop?category=women" },
  },
  {
    id: "soft-tailoring",
    image: px("collection-women.jpg"),
    imageMobile: px("collection-women.jpg"),
    alt: "Two models in neutral tailored looks standing together",
    kicker: "The Tailoring Edit",
    title: "Sharp Lines, Softer Mornings",
    body: "Unstructured blazers and column trousers in traceable wool — tailored to move at your pace.",
    primary: { label: "Shop Tailoring", to: "/shop?category=women" },
    secondary: { label: "Shop Men", to: "/shop?category=men" },
  },
  {
    id: "considered-basics",
    image: px("collection-men.jpg"),
    imageMobile: px("collection-men.jpg"),
    alt: "Model in a soft beige knit against a neutral background",
    kicker: "The Essentials",
    title: "Considered Basics, Kept for Years",
    body: "Organic cotton and traceable merino, cut clean and made to be worn on repeat. No logos. No noise.",
    primary: { label: "Shop Essentials", to: "/shop" },
    secondary: { label: "Leather & Objects", to: "/shop?category=accessories" },
  },
];

// Sub-list Exports
export const NEW_DROP_PRODUCTS = PRODUCTS.filter((p) => p.isNew || p.badge === "New");
export const BEST_SELLER_PRODUCTS = PRODUCTS.filter((p) => p.bestseller || p.badge === "Bestseller");
