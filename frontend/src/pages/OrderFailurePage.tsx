import { Link, useLocation } from "react-router-dom";
import { CircleX, LifeBuoy } from "lucide-react";
import { LinkButton } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

export default function OrderFailurePage() {
  usePageTitle("Payment Unsuccessful");
  const location = useLocation();
  const reason =
    (location.state as { reason?: string } | null)?.reason ??
    "We couldn't complete your payment.";

  return (
    <div className="mx-auto flex max-w-2xl flex-col items-center gap-5 px-4 py-16 text-center sm:px-6 lg:py-24">
      <span className="flex h-16 w-16 items-center justify-center rounded-full bg-red-800/10" aria-hidden>
        <CircleX className="h-8 w-8 text-red-800 dark:text-red-400" />
      </span>
      <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-red-800 dark:text-red-400">
        Payment declined
      </p>
      <h1 className="font-display text-4xl text-ink sm:text-5xl dark:text-linen">
        Your order didn't go through
      </h1>
      <p className="max-w-md text-sm leading-relaxed text-smoke dark:text-linen-dim">
        {reason} Don't worry — nothing was charged, and your bag is saved exactly as you left it.
      </p>
      <div className="mt-2 flex flex-wrap items-center justify-center gap-3">
        <LinkButton to="/checkout" size="lg">Retry Payment</LinkButton>
        <LinkButton to="/cart" variant="outline" size="lg">Back to Bag</LinkButton>
      </div>
      <Link
        to="/contact"
        className="mt-4 inline-flex items-center gap-2 text-xs font-bold tracking-[0.16em] uppercase text-smoke underline-offset-4 transition-colors hover:text-bronze hover:underline dark:text-linen-dim"
      >
        <LifeBuoy className="h-4 w-4" aria-hidden /> Contact support
      </Link>
    </div>
  );
}
