import { useLocation } from "react-router-dom";
import { InfoPage, InfoSection } from "@/components/layout/InfoPage";

const content: Record<
  string,
  {
    kicker: string;
    title: string;
    intro: string;
    sections: Array<{ heading: string; body: string[] }>;
  }
> = {
  "/our-story": {
    kicker: "Our story",
    title: "Quiet luxury, rooted in craft.",
    intro:
      "JAAJ was built around the idea that wardrobes should feel considered, personal and lasting.",
    sections: [
      {
        heading: "The point of view",
        body: [
          "We design with restraint: fewer pieces, better fabrics, and silhouettes that move naturally from morning meetings to late dinners.",
          "Every collection is built to last through seasons, not trends.",
        ],
      },
      {
        heading: "How we make",
        body: [
          "We work with small partner studios across Europe and produce in measured batches to keep quality high and waste low.",
          "That means each finish, button and seam is chosen for function as much as aesthetics.",
        ],
      },
    ],
  },
  "/careers": {
    kicker: "Careers",
    title: "Join the studio.",
    intro:
      "We’re a small team building thoughtful essentials for modern wardrobes.",
    sections: [
      {
        heading: "Current opportunities",
        body: [
          "We’re always looking for people who care deeply about design, service and craftsmanship.",
          "Email hello@jaaj.studio with a short note about your background and the role you’re interested in.",
        ],
      },
    ],
  },
  "/stores": {
    kicker: "Stores",
    title: "Visit the JAAJ studio.",
    intro:
      "Find the nearest JAAJ space and experience the collection in person.",
    sections: [
      {
        heading: "Flagship retail",
        body: [
          "Jægersborggade 22, 2200 Copenhagen N, Denmark",
          "Open Tue–Sat, 11:00–18:00 CET",
        ],
      },
    ],
  },
  "/press": {
    kicker: "Press",
    title: "Press & editorial.",
    intro:
      "For press, media and partnership enquiries, we welcome the conversation.",
    sections: [
      {
        heading: "Request assets",
        body: [
          "Our team can share lookbooks, product imagery and brand information for editorial coverage.",
          "Please contact press@jaaj.studio with your request and publication details.",
        ],
      },
    ],
  },
  "/sustainability": {
    kicker: "Sustainability",
    title: "Responsibility in every step.",
    intro: "We design for longevity, low waste and transparent sourcing.",
    sections: [
      {
        heading: "What we focus on",
        body: [
          "Organic and traceable fibres, long-lived silhouettes and small-batch production.",
          "We also prioritise repairability and packaging designed to be reused.",
        ],
      },
    ],
  },
  "/refund-policy": {
    kicker: "Refund policy",
    title: "Returns & refunds.",
    intro:
      "Returns are simple and transparent, with exchanges offered within 30 days.",
    sections: [
      {
        heading: "Policy",
        body: [
          "Items must be unworn and returned in their original condition within 30 days of delivery.",
          "Refunds are processed to the original payment method within 3–5 business days after the return is accepted.",
        ],
      },
    ],
  },
  "/cookies": {
    kicker: "Cookie policy",
    title: "Cookies & privacy preferences.",
    intro:
      "We use cookies to remember preferences, analyse site performance and improve your experience.",
    sections: [
      {
        heading: "How we use them",
        body: [
          "Essential cookies keep the site secure and functional.",
          "Performance and preference cookies help us improve the shop experience without selling your data.",
        ],
      },
    ],
  },
  "/track-order": {
    kicker: "Track order",
    title: "Follow your order.",
    intro:
      "Your delivery updates will be sent by email as soon as the parcel is dispatched.",
    sections: [
      {
        heading: "Status updates",
        body: [
          "Track the movement of your parcel through every shipping milestone.",
          "If you need help, contact care@jaaj.studio and we’ll assist directly.",
        ],
      },
    ],
  },
  "/size-guide": {
    kicker: "Size guide",
    title: "Find your fit.",
    intro:
      "Our pieces are designed with an easy, relaxed silhouette and a precise, measured fit.",
    sections: [
      {
        heading: "General guidance",
        body: [
          "If you prefer a more tailored shape, consider sizing down one step.",
          "For the most accurate fit, refer to the garment-specific measurements on each product page.",
        ],
      },
    ],
  },
};

export default function InfoRoutePage() {
  const { pathname } = useLocation();
  const page = content[pathname] ?? content["/our-story"];

  return (
    <InfoPage kicker={page.kicker} title={page.title} intro={page.intro}>
      {page.sections.map((section) => (
        <InfoSection key={section.heading} heading={section.heading}>
          {section.body.map((line) => (
            <p key={line}>{line}</p>
          ))}
        </InfoSection>
      ))}
    </InfoPage>
  );
}
