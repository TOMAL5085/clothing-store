import { useEffect, useState } from "react";

export type Language = "en" | "bn";
const STORAGE_KEY = "jaaj-language";

export function LanguageSwitcher() {
  const [language, setLanguage] = useState<Language>(() => (localStorage.getItem(STORAGE_KEY) === "bn" ? "bn" : "en"));
  useEffect(() => { document.documentElement.lang = language; localStorage.setItem(STORAGE_KEY, language); }, [language]);
  return <label className="sr-only">Language<select aria-label="Language" value={language} onChange={(event) => setLanguage(event.target.value as Language)} className="not-sr-only h-8 border border-line bg-transparent px-1 text-[10px] font-bold uppercase tracking-wide text-foreground dark:border-line-dark"><option value="en">EN</option><option value="bn">বাংলা</option></select></label>;
}
