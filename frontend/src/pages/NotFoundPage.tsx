import { Compass } from "lucide-react";
import { LinkButton } from "@/components/ui/primitives";
import { usePageTitle } from "@/utils/usePageTitle";

export default function NotFoundPage() {
  usePageTitle("Page not found");
  return (
    <div className="mx-auto flex max-w-2xl flex-col items-center gap-5 px-4 py-20 text-center sm:px-6 lg:py-28">
      <span className="flex h-16 w-16 items-center justify-center rounded-full bg-fog dark:bg-nox2" aria-hidden>
        <Compass className="h-7 w-7 text-bronze" />
      </span>
      <p className="font-display text-7xl text-ink/10 sm:text-8xl dark:text-linen/10" aria-hidden>404</p>
      <h1 className="font-display text-4xl text-ink sm:text-5xl dark:text-linen">
        This page has sold out
      </h1>
      <p className="max-w-md text-sm leading-relaxed text-smoke dark:text-linen-dim">
        The page you're looking for has moved or no longer exists. The collection, however, is very
        much in stock.
      </p>
      <div className="mt-2 flex flex-wrap items-center justify-center gap-3">
        <LinkButton to="/" size="lg">Back to Home</LinkButton>
        <LinkButton to="/shop" variant="outline" size="lg">Shop the Collection</LinkButton>
      </div>
    </div>
  );
}
