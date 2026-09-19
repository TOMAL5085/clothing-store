import { create } from "zustand";
import { persist } from "zustand/middleware";

export type Theme = "light" | "dark";

interface ThemeState {
  theme: Theme;
  toggleTheme: () => void;
  setTheme: (theme: Theme) => void;
}

export function applyTheme(theme: Theme) {
  if (typeof document === "undefined") return;
  const root = document.documentElement;
  root.classList.toggle("dark", theme === "dark");
  root.style.colorScheme = theme;
  const meta = document.querySelector('meta[name="theme-color"]');
  if (meta) meta.setAttribute("content", theme === "dark" ? "#14120f" : "#f5f3ee");
}

export const useThemeStore = create<ThemeState>()(
  persist(
    (set) => ({
      /* Light is the product default — never inferred from the OS. */
      theme: "light",
      toggleTheme: () =>
        set((state) => ({ theme: state.theme === "light" ? "dark" : "light" })),
      setTheme: (theme) => set({ theme }),
    }),
    {
      name: "jaaj-theme",
      onRehydrateStorage: () => (state) => {
        applyTheme(state?.theme ?? "light");
      },
    }
  )
);

useThemeStore.subscribe((state) => applyTheme(state.theme));
