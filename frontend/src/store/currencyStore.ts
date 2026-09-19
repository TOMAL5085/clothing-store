import { create } from "zustand";
import { persist } from "zustand/middleware";

export type Currency = "USD" | "EUR" | "GBP";

export const CURRENCIES: Array<{
  code: Currency;
  symbol: string;
  label: string;
  locale: string;
  rate: number;
}> = [
  { code: "USD", symbol: "$", label: "US Dollar", locale: "en-US", rate: 1 },
  { code: "EUR", symbol: "€", label: "Euro", locale: "de-DE", rate: 0.92 },
  { code: "GBP", symbol: "£", label: "British Pound", locale: "en-GB", rate: 0.79 },
];

interface CurrencyState {
  currency: Currency;
  setCurrency: (currency: Currency) => void;
  /** Convert a base USD price into the active currency amount. */
  convert: (usd: number) => number;
  /** Format a base USD price in the active currency. */
  format: (usd: number, options?: Intl.NumberFormatOptions) => string;
}

export const useCurrencyStore = create<CurrencyState>()(
  persist(
    (set, get) => ({
      currency: "USD",
      setCurrency: (currency) => set({ currency }),
      convert: (usd) => {
        const active = CURRENCIES.find((c) => c.code === get().currency) ?? CURRENCIES[0];
        return usd * active.rate;
      },
      format: (usd, options) => {
        const active = CURRENCIES.find((c) => c.code === get().currency) ?? CURRENCIES[0];
        return new Intl.NumberFormat(active.locale, {
          style: "currency",
          currency: active.code,
          maximumFractionDigits: usd % 1 === 0 ? 0 : 2,
          ...options,
        }).format(usd * active.rate);
      },
    }),
    { name: "jaaj-currency" }
  )
);
