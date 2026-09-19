// import { cn } from "@/utils/cn";

// /**
//  * JAAJ logo renderer.
//  *
//  * The original project references marketing brand assets that are not included in
//  * the workspace, so this component renders a self-contained typographic fallback
//  * that keeps the storefront fully functional without those missing files.
//  */
// type LogoVariant = "wordmark" | "monogram" | "lockup" | "stacked";
// type LogoTone = "auto" | "onInk" | "navy";

// interface LogoProps {
//   variant?: LogoVariant;
//   tone?: LogoTone;
//   /** CSS width of the rendered mark; height follows the aspect ratio. */
//   width: number;
//   className?: string;
//   alt?: string;
// }

// function getToneClasses(tone: LogoTone) {
//   if (tone === "onInk") {
//     return "text-white";
//   }

//   if (tone === "navy") {
//     return "text-[#0c1444] dark:text-white";
//   }

//   return "text-[#0c1444] dark:text-white";
// }

// function renderLogoContent(variant: LogoVariant, tone: LogoTone, alt: string) {
//   const baseClasses =
//     "flex select-none items-center justify-center font-display font-black uppercase leading-none tracking-[0.1em]";
//   const toneClasses = getToneClasses(tone);

//   if (variant === "monogram") {
//     return (
//       <span
//         role="img"
//         aria-label={alt}
//         className={cn(baseClasses, toneClasses, "text-[1.4em] sm:text-[1.6em]")}
//       >
//         J
//       </span>
//     );
//   }

//   if (variant === "stacked") {
//     return (
//       <span
//         role="img"
//         aria-label={alt}
//         className={cn(baseClasses, toneClasses, "flex-col gap-[0.18em] text-[0.8em]")}
//       >
//         <span className="tracking-[0.22em]">J</span>
//         <span className="tracking-[0.2em]">JAAJ</span>
//       </span>
//     );
//   }

//   if (variant === "lockup") {
//     return (
//       <span
//         role="img"
//         aria-label={alt}
//         className={cn(baseClasses, toneClasses, "flex-col items-start gap-[0.2em] text-[0.78em]")}
//       >
//         <span className="tracking-[0.22em]">JAAJ</span>
//         <span className="text-[0.38em] tracking-[0.42em] text-current/70">MODERN MEN</span>
//       </span>
//     );
//   }

//   return (
//     <span role="img" aria-label={alt} className={cn(baseClasses, toneClasses, "text-[0.82em]")}>
//       JAAJ
//     </span>
//   );
// }

// export function Logo({
//   variant = "wordmark",
//   tone = "auto",
//   width,
//   className,
//   alt = "JAAJ",
// }: LogoProps) {
//   const ratioMap: Record<LogoVariant, string> = {
//     wordmark: "1637 / 598",
//     monogram: "1 / 1",
//     lockup: "1556 / 1268",
//     stacked: "1596 / 1162",
//   };

//   return (
//     <span
//       className={cn("block shrink-0", className)}
//       style={{ width, aspectRatio: ratioMap[variant] }}
//       aria-hidden={alt === ""}
//     >
//       {renderLogoContent(variant, tone, alt)}
//     </span>
//   );
// }



// import { cn } from "@/utils/cn";

// type LogoVariant = "wordmark" | "monogram" | "lockup" | "stacked";
// type LogoTone = "auto" | "onInk" | "navy";

// interface LogoProps {
//   variant?: LogoVariant;
//   tone?: LogoTone;
//   /** CSS width of the rendered mark; height follows the aspect ratio. */
//   width: number;
//   className?: string;
//   alt?: string;
// }

// function getToneClasses(tone: LogoTone) {
//   if (tone === "onInk") {
//     return "text-white";
//   }

//   if (tone === "navy") {
//     return "text-[#0c1444] dark:text-white";
//   }

//   return "text-[#0c1444] dark:text-white";
// }

// function renderLogoContent(variant: LogoVariant, tone: LogoTone, alt: string) {
//   // Letter spacing কমিয়ে tracking-[0.08em] করা হয়েছে এবং font-black দিয়ে সর্বোচ্চ বোল্ড করা হয়েছে
//   const baseClasses =
//     "flex select-none items-center justify-center font-serif font-black uppercase leading-none tracking-[0.08em] antialiased";
//   const toneClasses = getToneClasses(tone);

//   if (variant === "monogram") {
//     return (
//       <span
//         role="img"
//         aria-label={alt}
//         className={cn(baseClasses, toneClasses, "text-[1.4em] sm:text-[1.6em] tracking-normal")}
//       >
//         J
//       </span>
//     );
//   }

//   if (variant === "stacked") {
//     return (
//       <span
//         role="img"
//         aria-label={alt}
//         className={cn(baseClasses, toneClasses, "flex-col gap-[0.18em] text-[0.8em]")}
//       >
//         <span className="tracking-[0.15em]">J</span>
//         <span className="tracking-[0.12em]">JAAJ</span>
//       </span>
//     );
//   }

//   if (variant === "lockup") {
//     return (
//       <span
//         role="img"
//         aria-label={alt}
//         className={cn(baseClasses, toneClasses, "flex-col items-start gap-[0.25em] text-[0.78em]")}
//       >
//         <span className="tracking-[0.12em]">JAAJ</span>
//         <span className="font-sans font-bold text-[0.35em] tracking-[0.3em] text-current/75">
//           MODERN MEN
//         </span>
//       </span>
//     );
//   }

//   return (
//     <span role="img" aria-label={alt} className={cn(baseClasses, toneClasses, "text-[0.9em]")}>
//       JAAJ
//     </span>
//   );
// }

// export function Logo({
//   variant = "wordmark",
//   tone = "auto",
//   width,
//   className,
//   alt = "JAAJ",
// }: LogoProps) {
//   const ratioMap: Record<LogoVariant, string> = {
//     wordmark: "1637 / 598",
//     monogram: "1 / 1",
//     lockup: "1556 / 1268",
//     stacked: "1596 / 1162",
//   };

//   return (
//     <span
//       className={cn("block shrink-0", className)}
//       style={{ width, aspectRatio: ratioMap[variant] }}
//       aria-hidden={alt === ""}
//     >
//       {renderLogoContent(variant, tone, alt)}
//     </span>
//   );
// }



import { cn } from "@/utils/cn";

type LogoVariant = "wordmark" | "monogram" | "lockup" | "stacked";
type LogoTone = "auto" | "onInk" | "navy";

interface LogoProps {
  variant?: LogoVariant;
  tone?: LogoTone;
  /** CSS width of the rendered mark; height follows the aspect ratio. */
  width: number;
  className?: string;
  alt?: string;
}

function getToneClasses(tone: LogoTone) {
  if (tone === "onInk") {
    return "text-white";
  }

  if (tone === "navy") {
    return "text-[#0c1444] dark:text-white";
  }

  return "text-[#0c1444] dark:text-white";
}

function renderLogoContent(variant: LogoVariant, tone: LogoTone, alt: string) {
  // font-serif এবং font-black দিয়ে সব Variant-এ একই Serif বোল্ড ফন্ট নিশ্চিত করা হয়েছে
  const baseClasses =
    "flex select-none items-center justify-center font-serif font-black uppercase leading-none tracking-[0.05em] antialiased";
  const toneClasses = getToneClasses(tone);

  if (variant === "monogram") {
    return (
      <span
        role="img"
        aria-label={alt}
        className={cn(baseClasses, toneClasses, "text-[1.8em] tracking-normal")}
      >
        J
      </span>
    );
  }

  if (variant === "stacked") {
    return (
      <span
        role="img"
        aria-label={alt}
        className={cn(baseClasses, toneClasses, "flex-col gap-[0.1em] text-[1em]")}
      >
        <span className="tracking-[0.08em]">J</span>
        <span className="tracking-[0.05em]">JAAJ</span>
      </span>
    );
  }

  if (variant === "lockup") {
    return (
      <span
        role="img"
        aria-label={alt}
        className={cn(baseClasses, toneClasses, "flex-col items-start gap-[0.25em] text-[0.95em]")}
      >
        <span className="tracking-[0.05em]">JAAJ</span>
        <span className="font-sans font-bold text-[0.38em] tracking-[0.3em] text-current/75">
          MODERN MEN
        </span>
      </span>
    );
  }

  return (
    <span role="img" aria-label={alt} className={cn(baseClasses, toneClasses, "text-[1.1em]")}>
      JAAJ
    </span>
  );
}

export function Logo({
  variant = "wordmark",
  tone = "auto",
  width,
  className,
  alt = "JAAJ",
}: LogoProps) {
  const ratioMap: Record<LogoVariant, string> = {
    wordmark: "1637 / 598",
    monogram: "1 / 1",
    lockup: "1556 / 1268",
    stacked: "1596 / 1162",
  };

  return (
    <span
      className={cn("block shrink-0", className)}
      style={{ width, aspectRatio: ratioMap[variant] }}
      aria-hidden={alt === ""}
    >
      {renderLogoContent(variant, tone, alt)}
    </span>
  );
}