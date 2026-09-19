import { InfoPage, InfoSection } from "@/components/layout/InfoPage";
import { usePageTitle } from "@/utils/usePageTitle";

export default function TermsPage() {
  usePageTitle("Terms of Service", "The terms that govern shopping at JAAJ.");
  return (
    <InfoPage
      kicker="Legal"
      title="Terms of service"
      intro="Effective January 2026. The plain-language version of the agreement between you and JAAJ AB."
    >
      <InfoSection heading="The shop">
        <p>
          JAAJ AB (registration DK-44 21 87 03) sells clothing, leather goods and accessories
          through this website. By placing an order you agree to these terms.
        </p>
      </InfoSection>
      <InfoSection heading="Pricing & availability">
        <p>
          Prices are shown in your selected currency and include applicable VAT for EU customers. We
          produce in small batches; if a piece sells out between your order and fulfilment, we refund
          it immediately and let you know by email.
        </p>
      </InfoSection>
      <InfoSection heading="Orders & payment">
        <p>
          An order is an offer to purchase; we confirm acceptance by email when your parcel ships.
          Payment is captured at checkout via our encrypted payment partners.
        </p>
      </InfoSection>
      <InfoSection heading="Returns">
        <p>
          Our 30-day return policy and free size exchanges are described on the Returns page and form
          part of these terms.
        </p>
      </InfoSection>
      <InfoSection heading="Intellectual property">
        <p>
          All designs, photography and text on this site belong to JAAJ AB. Please don't
          reproduce them without written permission.
        </p>
      </InfoSection>
      <InfoSection heading="Questions">
        <p>
          Write to legal@jaaj.studio and a human — usually the person who drafted these terms — will
          reply.
        </p>
      </InfoSection>
    </InfoPage>
  );
}
