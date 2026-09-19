import { useEffect } from "react";

const DEFAULT_TITLE = "JAAJ — Contemporary Essentials, Quietly Iconic";
const DEFAULT_DESCRIPTION =
  "JAAJ designs contemporary essentials in organic cotton, traceable wool and vegetable-tanned leather. Considered wardrobe icons, made in small batches.";

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
  }, [title, description]);
}
