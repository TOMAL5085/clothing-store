import type { ReactNode } from "react";

/** Shared editorial shell for the info/legal pages. */
export function InfoPage({
  kicker,
  title,
  intro,
  children,
}: {
  kicker: string;
  title: string;
  intro?: string;
  children: ReactNode;
}) {
  return (
    <article className="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:py-20">
      <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">{kicker}</p>
      <h1 className="mt-3 font-display text-4xl leading-tight text-ink sm:text-5xl dark:text-linen">
        {title}
      </h1>
      {intro && (
        <p className="mt-5 text-base leading-relaxed text-smoke dark:text-linen-dim">{intro}</p>
      )}
      <div className="mt-10 space-y-10">{children}</div>
    </article>
  );
}

export function InfoSection({
  heading,
  children,
}: {
  heading: string;
  children: ReactNode;
}) {
  return (
    <section>
      <h2 className="font-display text-2xl text-ink dark:text-linen">{heading}</h2>
      <div className="mt-3 space-y-3 text-sm leading-relaxed text-smoke dark:text-linen-dim">
        {children}
      </div>
    </section>
  );
}
