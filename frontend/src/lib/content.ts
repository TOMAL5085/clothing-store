import { api } from "@/lib/api";
import { ANNOUNCEMENT, HERO_COPY } from "@/data/siteCopy";

export interface CmsContent {
  id: number;
  key: string;
  type: string;
  title: string;
  subtitle: string | null;
  body: string | null;
  image_url: string | null;
  mobile_image_url: string | null;
  cta_label: string | null;
  cta_url: string | null;
  status: string;
  sort_order: number;
}

export interface BannerSlide {
  id: number;
  key: string | null;
  title: string | null;
  image_url: string | null;
  mobile_image_url: string | null;
  cta_label: string | null;
  cta_url: string | null;
  sort_order: number;
}

interface CmsCollection {
  data: CmsContent[];
}

interface BannerCollection {
  data: BannerSlide[];
}

/** Approved static fallbacks — the storefront renders identically with or without the CMS API. */
export const FALLBACK_ANNOUNCEMENT = {
  textBefore: ANNOUNCEMENT.textBefore,
  highlight: ANNOUNCEMENT.highlight,
};

export const FALLBACK_HERO_COPY = HERO_COPY;

let bannersPromise: Promise<BannerSlide[]> | null = null;
let announcementPromise: Promise<{ textBefore: string; highlight: string } | null> | null = null;

/**
 * Published hero slides, ordered. Resolves to an empty array when the API
 * is unavailable so callers fall back to bundled imagery.
 */
export function fetchBannerSlides(): Promise<BannerSlide[]> {
  if (!bannersPromise) {
    bannersPromise = api<BannerCollection>("/banners?per_page=10")
      .then((response) => response.data.filter((slide) => slide.image_url))
      .catch(() => []);
  }
  return bannersPromise;
}

/**
 * Published site announcement, or null when unavailable so callers fall
 * back to the approved static copy. Title/subtitle map to the bar's
 * two-tone segments (lead-in + highlight).
 */
export function fetchAnnouncement(): Promise<{ textBefore: string; highlight: string } | null> {
  if (!announcementPromise) {
    announcementPromise = api<CmsCollection>("/cms/content?key=site-announcement")
      .then((response) => {
        const record = response.data[0];
        if (!record) return null;
        return {
          textBefore: record.title || FALLBACK_ANNOUNCEMENT.textBefore,
          highlight: record.subtitle || FALLBACK_ANNOUNCEMENT.highlight,
        };
      })
      .catch(() => null);
  }
  return announcementPromise;
}
