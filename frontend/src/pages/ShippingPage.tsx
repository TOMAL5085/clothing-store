import { InfoPage, InfoSection } from "@/components/layout/InfoPage";
import { usePageTitle } from "@/utils/usePageTitle";

export default function ShippingPage() {
  usePageTitle("Shipping", "JAAJ shipping options, delivery times and carbon-neutral logistics.");
  return (
    <InfoPage
      kicker="Delivery"
      title="Shipping information"
      intro="Every order leaves our Copenhagen studio within 24 hours, packed plastic-free and shipped carbon-neutral."
    >
      <InfoSection heading="Options & timing">
        <p>
          <strong className="text-ink dark:text-linen">Standard (2–5 business days)</strong> — complimentary
          on all orders over $200 (€185 / £160); otherwise a flat $9.95.
        </p>
        <p>
          <strong className="text-ink dark:text-linen">Express (1–2 business days)</strong> — flat $18
          worldwide via tracked courier.
        </p>
      </InfoSection>
      <InfoSection heading="Where we deliver">
        <p>
          We ship to over 60 countries across Europe, North America and Asia-Pacific. Duties for orders
          outside the EU are calculated at checkout, so your parcel never stops at the border.
        </p>
      </InfoSection>
      <InfoSection heading="Tracking">
        <p>
          A tracking link is emailed the moment your order ships. Orders placed before 14:00 CET leave
          the studio the same day.
        </p>
      </InfoSection>
      <InfoSection heading="Packaging">
        <p>
          FSC-certified recycled boxes, paper tape, no plastic. Your pieces arrive folded in acid-free
          tissue — never poly-bagged.
        </p>
      </InfoSection>
    </InfoPage>
  );
}
