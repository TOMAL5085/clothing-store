// import { Link } from "react-router-dom";
// import { ArrowUp } from "lucide-react";
// import { FOOTER_COPY, FOOTER_GROUPS, PAYMENT_MARKS } from "@/data/navigation";
// import { SocialIcon, type SocialIconKey } from "@/components/ui/SocialIcon";
// import { Logo } from "./Logo";

// /**
//  * Footer — SOURCE 01 structure:
//  * brand column (mark + tagline + socials) · SHOP / CUSTOMER CARE /
//  * COMPANY / LEGAL columns · bottom bar with ©, WE ACCEPT marks, back-to-top.
//  * Rendered in the ink treatment in both themes, exactly like SOURCE 01.
//  */

// const SOCIALS: { label: string; href: string; icon: SocialIconKey }[] = [
//   { label: "Instagram", href: "https://instagram.com", icon: "instagram" },
//   { label: "Facebook", href: "https://facebook.com", icon: "facebook" },
//   { label: "TikTok", href: "https://tiktok.com", icon: "tiktok" },
//   { label: "YouTube", href: "https://youtube.com", icon: "youtube" },
// ];

// export function Footer() {
//   const backToTop = () => window.scrollTo({ top: 0, behavior: "smooth" });

//   return (
//     <footer className="border-t border-ink-line bg-ink text-ink-foreground">
//       <div className="mx-auto max-w-[1440px] px-5 md:px-10">
//         {/* upper: brand + link groups */}
//         <div className="grid grid-cols-1 gap-10 py-14 sm:grid-cols-2 lg:grid-cols-[1.4fr_repeat(4,1fr)] lg:gap-8 lg:py-[72px]">
//           <div>
//             {/* Monogram সরানো হয়েছে এবং Wordmark-এর সাইজ বাড়িয়ে হেডার লোগোর মত বোল্ড করা হয়েছে */}
//             <div className="flex items-center">
//               <span className="inline-flex align-items-center translate-y-5 origin-left scale-205 items-center lg:scale-200">
//                 <Logo variant="wordmark" tone="onInk" width={110} alt="JAAJ" />
//               </span>
//             </div>
//             <p className="mt-6 max-w-[300px] text-[12.5px] leading-relaxed text-ink-muted">
//               {FOOTER_COPY.tagline}
//             </p>
//             <div className="mt-6 flex items-center gap-2">
//               {SOCIALS.map(({ label, href, icon }) => (
//                 <a
//                   key={label}
//                   href={href}
//                   target="_blank"
//                   rel="noreferrer"
//                   aria-label={`JAAJ on ${label}`}
//                   className="flex h-9 w-9 items-center justify-center border border-ink-line text-ink-foreground/75 transition-colors duration-300 hover:border-ink-foreground hover:text-ink-foreground"
//                 >
//                   <SocialIcon icon={icon} size={15} />
//                 </a>
//               ))}
//             </div>
//           </div>

//           {FOOTER_GROUPS.map((group) => (
//             <nav key={group.heading} aria-label={group.heading}>
//               <h3 className="font-display text-[11px] font-bold uppercase tracking-nav text-ink-foreground">
//                 {group.heading}
//               </h3>
//               <ul className="mt-5 space-y-2.5">
//                 {group.links.map((link) => (
//                   <li key={link.label}>
//                     <Link
//                       to={link.href}
//                       className="text-[12.5px] text-ink-muted transition-colors duration-300 hover:text-ink-foreground"
//                     >
//                       {link.label}
//                     </Link>
//                   </li>
//                 ))}
//               </ul>
//             </nav>
//           ))}
//         </div>

//         {/* bottom bar */}
//         <div className="flex flex-col items-start justify-between gap-5 border-t border-ink-line py-6 sm:flex-row sm:items-center">
//           <p className="text-[11px] text-ink-muted">{FOOTER_COPY.bottomNote}</p>

//           <div className="flex w-full items-center justify-between gap-6 sm:w-auto sm:justify-end">
//             <div className="flex items-center gap-2.5">
//               <span className="mr-1 text-[9.5px] uppercase tracking-caption text-ink-muted">
//                 {FOOTER_COPY.paymentsLabel}
//               </span>
//               {PAYMENT_MARKS.map((mark) => (
//                 <span
//                   key={mark}
//                   className="flex h-6 items-center border border-ink-line px-2 font-display text-[8.5px] font-bold tracking-caption text-ink-foreground/80"
//                 >
//                   {mark}
//                 </span>
//               ))}
//             </div>
//             <button
//               type="button"
//               onClick={backToTop}
//               aria-label="Back to top"
//               className="flex h-9 w-9 shrink-0 items-center justify-center border border-ink-line text-ink-foreground transition-colors duration-300 hover:border-ink-foreground"
//             >
//               <ArrowUp size={15} strokeWidth={1.6} absoluteStrokeWidth />
//             </button>
//           </div>
//         </div>
//       </div>
//     </footer>
//   );
// }


import { Link } from "react-router-dom";
import { ArrowUp } from "lucide-react";
import { FOOTER_COPY, FOOTER_GROUPS } from "@/data/navigation";
import { SocialIcon, type SocialIconKey } from "@/components/ui/SocialIcon";
import { Logo } from "./Logo";

/**
 * Footer — SOURCE 01 structure:
 * brand column (mark + tagline + socials) · SHOP / CUSTOMER CARE /
 * COMPANY / LEGAL columns · bottom bar with ©, WE ACCEPT marks, back-to-top.
 * Rendered in the ink treatment in both themes, exactly like SOURCE 01.
 */

const SOCIALS: { label: string; href: string; icon: SocialIconKey }[] = [
  { label: "Instagram", href: "https://instagram.com", icon: "instagram" },
  { label: "Facebook", href: "https://facebook.com", icon: "facebook" },
  { label: "TikTok", href: "https://tiktok.com", icon: "tiktok" },
  { label: "YouTube", href: "https://youtube.com", icon: "youtube" },
];

export function Footer() {
  const backToTop = () => window.scrollTo({ top: 0, behavior: "smooth" });

  return (
    <footer className="border-t border-ink-line bg-ink text-ink-foreground">
      <div className="mx-auto max-w-[1440px] px-5 md:px-10">
        {/* upper: brand + link groups */}
        <div className="grid grid-cols-1 gap-10 py-14 sm:grid-cols-2 lg:grid-cols-[1.4fr_repeat(4,1fr)] lg:gap-8 lg:py-[72px]">
          <div>
            {/* Monogram সরানো হয়েছে এবং Wordmark-এর সাইজ বাড়িয়ে হেডার লোগোর মত বোল্ড করা হয়েছে */}
            <div className="flex items-center">
              <span className="inline-flex align-items-center translate-y-5 origin-left scale-205 items-center lg:scale-200">
                <Logo variant="wordmark" tone="onInk" width={110} alt="JAAJ" />
              </span>
            </div>
            <p className="mt-6 max-w-[300px] text-[12.5px] leading-relaxed text-ink-muted">
              {FOOTER_COPY.tagline}
            </p>
            <div className="mt-6 flex items-center gap-2">
              {SOCIALS.map(({ label, href, icon }) => (
                <a
                  key={label}
                  href={href}
                  target="_blank"
                  rel="noreferrer"
                  aria-label={`JAAJ on ${label}`}
                  className="flex h-9 w-9 items-center justify-center border border-ink-line text-ink-foreground/75 transition-colors duration-300 hover:border-ink-foreground hover:text-ink-foreground"
                >
                  <SocialIcon icon={icon} size={15} />
                </a>
              ))}
            </div>
          </div>

          {FOOTER_GROUPS.map((group) => (
            <nav key={group.heading} aria-label={group.heading}>
              <h3 className="font-display text-[11px] font-bold uppercase tracking-nav text-ink-foreground">
                {group.heading}
              </h3>
              <ul className="mt-5 space-y-2.5">
                {group.links.map((link) => (
                  <li key={link.label}>
                    <Link
                      to={link.href}
                      className="text-[12.5px] text-ink-muted transition-colors duration-300 hover:text-ink-foreground"
                    >
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </nav>
          ))}
        </div>

        {/* bottom bar */}
        <div className="flex flex-col items-start justify-between gap-5 border-t border-ink-line py-6 sm:flex-row sm:items-center">
          <p className="text-[11px] text-ink-muted">{FOOTER_COPY.bottomNote}</p>

          <div className="flex w-full items-center justify-between gap-6 sm:w-auto sm:justify-end">
            <div className="flex items-center gap-2.5">
              <span className="mr-1 text-[9.5px] uppercase tracking-caption text-ink-muted">
                {FOOTER_COPY.paymentsLabel}
              </span>
              <img
                src="/imagery/payment method.svg"
                alt="Payment Methods"
                className="h-6 w-auto object-contain"
              />
            </div>
            <button
              type="button"
              onClick={backToTop}
              aria-label="Back to top"
              className="flex h-9 w-9 shrink-0 items-center justify-center border border-ink-line text-ink-foreground transition-colors duration-300 hover:border-ink-foreground"
            >
              <ArrowUp size={15} strokeWidth={1.6} absoluteStrokeWidth />
            </button>
          </div>
        </div>
      </div>
    </footer>
  );
}