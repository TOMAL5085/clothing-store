import { InfoPage, InfoSection } from "@/components/layout/InfoPage";
import { usePageTitle } from "@/utils/usePageTitle";

export default function PrivacyPage() {
  usePageTitle("Privacy Policy", "How JAAJ collects, uses and protects your personal data.");
  return (
    <InfoPage
      kicker="Legal"
      title="Privacy policy"
      intro="Effective January 2026. We collect the minimum data needed to run a considered shop — and we treat it the way we treat our fabric: carefully."
    >
      <InfoSection heading="What we collect">
        <p>
          Account details (name, email), order information (shipping address, order history) and
          browsing preferences stored locally on your device (cart, wishlist, theme and currency).
        </p>
      </InfoSection>
      <InfoSection heading="How we use it">
        <p>
          To fulfil orders, provide customer care and — only with your consent — send the weekly JAAJ
          List email. We never sell your data, and we never will.
        </p>
      </InfoSection>
      <InfoSection heading="Local storage">
        <p>
          Your cart, wishlist, theme and currency choices live in your browser's local storage so they
          survive refreshes. Clear your browser data at any time to remove them.
        </p>
      </InfoSection>
      <InfoSection heading="Payments">
        <p>
          Card details are processed by our PCI-DSS-compliant payment partners over 256-bit encryption.
          JAAJ never sees or stores full card numbers.
        </p>
      </InfoSection>
      <InfoSection heading="Your rights">
        <p>
          Under GDPR you may request access, correction or deletion of your data at any time by
          emailing privacy@jaaj.studio. We respond within 30 days.
        </p>
      </InfoSection>
    </InfoPage>
  );
}
