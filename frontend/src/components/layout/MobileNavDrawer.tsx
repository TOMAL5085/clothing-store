import { useEffect } from "react";
import { Link } from "react-router-dom";
import { AnimatePresence, motion } from "framer-motion";
import { X } from "lucide-react";
import { PRIMARY_NAV } from "@/data/navigation";
import { useUIStore } from "@/store/uiStore";
import { useThemeStore } from "@/store/themeStore";
import { Moon, Sun } from "lucide-react";
import { Logo } from "./Logo";

/**
 * Mobile navigation — Part 1 shell.
 * Slide-over drawer with the primary nav + theme toggle; richer
 * category content and micro-interactions arrive in Part 2.
 */

export function MobileNavDrawer() {
  const open = useUIStore((s) => s.mobileNavOpen);
  const setOpen = useUIStore((s) => s.setMobileNavOpen);
  const theme = useThemeStore((s) => s.theme);
  const toggleTheme = useThemeStore((s) => s.toggleTheme);

  useEffect(() => {
    if (!open) return;

    const previousOverflow = document.body.style.overflow;
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") setOpen(false);
    };

    document.body.style.overflow = "hidden";
    document.addEventListener("keydown", handleKeyDown);
    return () => {
      document.body.style.overflow = previousOverflow;
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [open, setOpen]);

  return (
    <AnimatePresence>
      {open && (
        <>
          <motion.div
            key="scrim"
            className="fixed inset-0 z-[60] bg-black/50"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.3 }}
            onClick={() => setOpen(false)}
            aria-hidden="true"
          />
          <motion.aside
            key="panel"
            id="mobile-navigation"
            role="dialog"
            aria-modal="true"
            aria-label="Menu"
            className="fixed inset-y-0 left-0 z-[70] flex w-[86vw] max-w-[360px] flex-col border-r border-line bg-background"
            initial={{ x: "-100%" }}
            animate={{ x: 0 }}
            exit={{ x: "-100%" }}
            transition={{ duration: 0.45, ease: [0.22, 1, 0.36, 1] }}
          >
            <div className="flex h-16 items-center justify-between border-b border-line px-5">
              <Logo variant="wordmark" width={78} />
              <button
                type="button"
                aria-label="Close menu"
                onClick={() => setOpen(false)}
                className="flex h-10 w-10 items-center justify-center text-foreground/80 hover:text-foreground"
              >
                <X size={19} strokeWidth={1.6} absoluteStrokeWidth />
              </button>
            </div>

            <nav
              aria-label="Mobile"
              className="flex-1 overflow-y-auto px-5 py-6"
            >
              <ul className="space-y-1">
                {PRIMARY_NAV.map((item, i) => (
                  <motion.li
                    key={item.label}
                    initial={{ opacity: 0, x: -14 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{
                      delay: 0.08 + i * 0.045,
                      duration: 0.4,
                      ease: "easeOut",
                    }}
                  >
                    <Link
                      to={item.href}
                      onClick={() => setOpen(false)}
                      className={`block py-3 font-display text-[15px] font-bold uppercase tracking-[0.18em] ${
                        item.emphasis ? "text-accent" : "text-foreground"
                      }`}
                    >
                      {item.label}
                    </Link>
                  </motion.li>
                ))}
              </ul>
            </nav>

            <div className="border-t border-line px-5 py-4">
              <button
                type="button"
                onClick={toggleTheme}
                className="flex w-full items-center justify-between py-2 text-[11px] font-semibold uppercase tracking-nav text-foreground/80"
              >
                {theme === "light" ? "Dark mode" : "Light mode"}
                {theme === "light" ? (
                  <Moon size={17} strokeWidth={1.6} absoluteStrokeWidth />
                ) : (
                  <Sun size={18} strokeWidth={1.6} absoluteStrokeWidth />
                )}
              </button>
            </div>
          </motion.aside>
        </>
      )}
    </AnimatePresence>
  );
}
