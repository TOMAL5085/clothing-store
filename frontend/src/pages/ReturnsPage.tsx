import { Link } from "react-router-dom";
import { InfoPage, InfoSection } from "@/components/layout/InfoPage";
import { usePageTitle } from "@/utils/usePageTitle";

export default function ReturnsPage() {
  usePageTitle("Returns & Exchanges", "30-day returns and free size exchanges at JAAJ.");
  return (
    <InfoPage
      kicker="Peace of mind"
      title="Returns & exchanges"
      intro="Buy the piece, live with the piece. You have 30 days to decide — and size exchanges always ship free, both ways."
    >
      <InfoSection heading="How to start a return">
        <p>
          Sign in to your <Link to="/account" className="font-semibold text-bronze underline underline-offset-4">account</Link>,
          open the order and select the pieces to return — or email care@jaaj.studio with your order
          number. We'll issue a prepaid label within one business day.
        </p>
      </InfoSection>
      <InfoSection heading="Condition">
        <p>
          Pieces must be unworn, unwashed and returned with tags attached. Try them on at home as you
          would in our studio — that's exactly what the 30 days are for.
        </p>
      </InfoSection>
      <InfoSection heading="Refunds">
        <p>
          Refunds are issued to the original payment method within 3–5 business days of your parcel
          arriving at our studio. Original shipping fees are refundable within the EU.
        </p>
      </InfoSection>
      <InfoSection heading="Exchanges">
        <p>
          Need a different size? Exchanges ship free both ways, always. The new size leaves our studio
          before your return even lands.
        </p>
      </InfoSection>
      <InfoSection heading="Repairs for life">
        <p>
          Leather goods and hardware carry lifetime repairs. Loose button after year three? Send it
          home — our jaaj team will make it right.
        </p>
      </InfoSection>
    </InfoPage>
  );
}
