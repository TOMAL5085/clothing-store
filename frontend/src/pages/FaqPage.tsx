import { Link } from "react-router-dom";
import { usePageTitle } from "@/utils/usePageTitle";

const FAQS = [
  {
    q: "How does JAAJ sizing run?",
    a: "Our pieces are cut true to size with a relaxed, editorial drape. If you prefer a closer fit, consider sizing down. Every product page includes a full size guide with body measurements in centimetres, and exchanges are always free.",
  },
  {
    q: "Where are JAAJ pieces made?",
    a: "Every piece is cut and sewn in one of 42 partner studios across Portugal, Italy and Denmark. We produce in small batches and publish the studio for each garment on its care label.",
  },
  {
    q: "What fabrics do you use?",
    a: "97% of our collection is made from organic or traceable fibres: GOTS-certified cotton, fully traceable extra-fine merino, and silk-touch cupro. We never use virgin polyester.",
  },
  {
    q: "How long does shipping take?",
    a: "Standard carbon-neutral delivery takes 2–5 business days and is complimentary over $200 (€185 / £160). Express courier (1–2 business days) is available at checkout for a flat $18.",
  },
  {
    q: "What is your return policy?",
    a: "You have 30 days to return unworn pieces for a full refund, no questions asked. Size exchanges ship free both ways. Start a return from your account or the contact page.",
  },
  {
    q: "Do you offer repairs?",
    a: "Yes — lifetime repairs on leather goods and hardware. Email care@jaaj.studio with your order number and we'll arrange collection.",
  },
  {
    q: "Which payment methods do you accept?",
    a: "Visa, Mastercard, American Express, PayPal and Klarna. All payments are processed over 256-bit encryption; we never store card details.",
  },
  {
    q: "Can I change or cancel my order?",
    a: "Orders can be amended within 60 minutes of placement — contact us immediately and we'll catch it before it leaves the studio.",
  },
];

export default function FaqPage() {
  usePageTitle("FAQ", "Answers on JAAJ sizing, fabrics, shipping, returns and repairs.");
  return (
    <div className="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:py-20">
      <p className="text-[11px] font-bold tracking-[0.24em] uppercase text-bronze">Help</p>
      <h1 className="mt-3 font-display text-4xl leading-tight text-ink sm:text-5xl dark:text-linen">
        Frequently asked questions
      </h1>
      <p className="mt-4 text-sm leading-relaxed text-smoke sm:text-base dark:text-linen-dim">
        Everything about fabrics, sizing and shipping. Still stuck?{" "}
        <Link to="/contact" className="font-semibold text-bronze underline underline-offset-4">
          Contact jaaj
        </Link>
        .
      </p>

      <div className="mt-10 divide-y divide-line border-y border-line dark:divide-line-dark dark:border-line-dark">
        {FAQS.map((faq, i) => (
          <details key={faq.q} className="group" open={i === 0}>
            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 py-5 text-left [&::-webkit-details-marker]:hidden">
              <span className="text-sm font-bold text-ink transition-colors group-hover:text-bronze sm:text-base dark:text-linen">
                {faq.q}
              </span>
              <span
                aria-hidden
                className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-line text-lg leading-none text-smoke transition-transform duration-300 group-open:rotate-45 dark:border-line-dark"
              >
                +
              </span>
            </summary>
            <p className="pb-6 pr-12 text-sm leading-relaxed text-smoke dark:text-linen-dim">{faq.a}</p>
          </details>
        ))}
      </div>
    </div>
  );
}
