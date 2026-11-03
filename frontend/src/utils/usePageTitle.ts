import { useEffect } from "react";

const DEFAULT_TITLE = "JAAJ — Contemporary Essentials, Quietly Iconic";
const DEFAULT_DESCRIPTION =
  "JAAJ designs contemporary essentials in organic cotton, traceable wool and vegetable-tanned leather. Considered wardrobe icons, made in small batches.";
let seoPromise: Promise<Record<string, string>> | null = null;

function loadSeoSettings(): Promise<Record<string, string>> {
  if (!seoPromise) {
    const apiUrl = (import.meta.env.VITE_API_URL ?? "http://localhost:8000/api/v1").replace(/\/$/, "");
    seoPromise = fetch(apiUrl + "/store-settings").then((response) => response.json()).then((payload) => payload.data ?? {}).catch(() => ({}));
  }
  return seoPromise;
}

/**
 * Lightweight head management for the Vite SPA — sets document.title and
 * meta description per route without any external dependency.
 */
export function usePageTitle(title?: string, description?: string) {
  useEffect(() => {
    document.title = title ? `${title} — JAAJ` : DEFAULT_TITLE;

    let meta = document.querySelector<HTMLMetaElement>('meta[name="description"]');
    if (!meta) {
      meta = document.createElement("meta");
      meta.name = "description";
      document.head.appendChild(meta);
    }
    meta.content = description ?? DEFAULT_DESCRIPTION;
    void loadSeoSettings().then((settings) => {
      if (!title && settings.seo_title) document.title = settings.seo_title;
      if (!description && settings.seo_description) meta!.content = settings.seo_description;
    });
  }, [title, description]);
}
