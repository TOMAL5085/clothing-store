import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { getConsent, type ConsentState } from "@/lib/marketing";

declare global {
  interface Window {
    dataLayer: unknown[];
    gtag?: (...args: unknown[]) => void;
    fbq?: (...args: unknown[]) => void;
    _fbq?: unknown[][];
  }
}

type Settings = { google_analytics_id?: string; meta_pixel_id?: string };

function validGa(value: unknown): value is string { return typeof value === "string" && /^(G|AW)-[A-Z0-9-]+$/i.test(value); }
function validPixel(value: unknown): value is string { return typeof value === "string" && /^\d{5,20}$/.test(value); }

function removeScript(id: string) { document.getElementById(id)?.remove(); }

function syncScripts(settings: Settings, consent: ConsentState) {
  const gaId = validGa(settings.google_analytics_id) ? settings.google_analytics_id : null;
  const pixelId = validPixel(settings.meta_pixel_id) ? settings.meta_pixel_id : null;
  if (consent.analytics && gaId) {
    if (!document.getElementById("jaaj-ga-script")) { const script = document.createElement("script"); script.id = "jaaj-ga-script"; script.async = true; script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(gaId)}`; document.head.appendChild(script); }
    window.dataLayer = window.dataLayer || []; window.gtag = window.gtag || ((...args: unknown[]) => window.dataLayer.push(args)); window.gtag("js", new Date()); window.gtag("config", gaId, { anonymize_ip: true });
  } else { removeScript("jaaj-ga-script"); }
  if (consent.marketing && pixelId) {
    if (!document.getElementById("jaaj-pixel-script")) { const script = document.createElement("script"); script.id = "jaaj-pixel-script"; script.async = true; script.src = `https://connect.facebook.net/en_US/fbevents.js`; document.head.appendChild(script); }
    window.fbq = window.fbq || ((...args: unknown[]) => { (window._fbq = window._fbq || []).push(args); }); window.fbq("init", pixelId); window.fbq("track", "PageView");
  } else { removeScript("jaaj-pixel-script"); }
}

export function MarketingScripts() {
  const [settings, setSettings] = useState<Settings>({});
  const [consent, setConsent] = useState(getConsent);
  useEffect(() => { void api<{ data: Settings }>("/store-settings").then((response) => setSettings(response.data)).catch(() => undefined); const onChange = () => setConsent(getConsent()); window.addEventListener("jaaj:consent-change", onChange); return () => window.removeEventListener("jaaj:consent-change", onChange); }, []);
  useEffect(() => { syncScripts(settings, consent); }, [settings, consent]);
  return null;
}
