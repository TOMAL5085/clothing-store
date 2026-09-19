// import { useState, useEffect, useCallback } from "react";
// import { Link } from "react-router-dom";
// import { motion, AnimatePresence } from "framer-motion";
// import { ChevronLeft, ChevronRight } from "lucide-react";
// import jaajLogo from "@/assets/imagery/jaaj logo-02.png";

// const HERO_IMAGES = [
//   "https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1920&q=80",
//   "https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=1920&q=80",
//   "https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=1920&q=80",
// ];

// export function Hero() {
//   const [currentIndex, setCurrentIndex] = useState(0);
//   const [isAnimating, setIsAnimating] = useState(false);
//   const [isPaused, setIsPaused] = useState(false);

//   const triggerSlide = useCallback((nextIndex: number) => {
//     setIsAnimating(true);

//     setTimeout(() => {
//       setCurrentIndex(nextIndex);
//     }, 350);

//     setTimeout(() => {
//       setIsAnimating(false);
//     }, 1000);
//   }, []);

//   const handleNext = useCallback(() => {
//     if (isAnimating) return;
//     setCurrentIndex((prevIndex) => {
//       const next = (prevIndex + 1) % HERO_IMAGES.length;
//       triggerSlide(next);
//       return prevIndex;
//     });
//   }, [isAnimating, triggerSlide]);

//   const handlePrev = useCallback(() => {
//     if (isAnimating) return;
//     setCurrentIndex((prevIndex) => {
//       const prev = (prevIndex - 1 + HERO_IMAGES.length) % HERO_IMAGES.length;
//       triggerSlide(prev);
//       return prevIndex;
//     });
//   }, [isAnimating, triggerSlide]);

//   // Auto-Slide Interval Timer
//   useEffect(() => {
//     if (isPaused || isAnimating) return;

//     const timer = setInterval(() => {
//       setCurrentIndex((prevIndex) => {
//         const next = (prevIndex + 1) % HERO_IMAGES.length;
//         triggerSlide(next);
//         return prevIndex;
//       });
//     }, 5000);

//     return () => clearInterval(timer);
//   }, [isPaused, isAnimating, triggerSlide]);

//   return (
//     <section
//       className="relative w-full h-[85vh] min-h-[600px] overflow-hidden bg-neutral-950 text-white flex flex-col justify-between p-8 md:p-16 select-none"
//       onMouseEnter={() => setIsPaused(true)}
//       onMouseLeave={() => setIsPaused(false)}
//     >
//       {/* 1. Background Image Cross-Fade Slider */}
//       <div className="absolute inset-0 z-0">
//         <AnimatePresence mode="sync">
//           <motion.div
//             key={currentIndex}
//             className="absolute inset-0 bg-cover bg-center"
//             style={{ backgroundImage: `url(${HERO_IMAGES[currentIndex]})` }}
//             initial={{ opacity: 0, scale: 1.05 }}
//             animate={{ opacity: 1, scale: 1 }}
//             exit={{ opacity: 0 }}
//             transition={{ duration: 1.2, ease: "easeInOut" }}
//           />
//         </AnimatePresence>

//         {/* Dark Overlay for Text Readability */}
//         <div className="absolute inset-0 bg-black/50" />
//       </div>

//       {/* 2. White Light Sweep Beam Animation Overlay */}
//       <AnimatePresence>
//         {isAnimating && (
//           <motion.div
//             key="light-sweep"
//             initial={{ x: "-100%", opacity: 0 }}
//             animate={{ x: "200%", opacity: [0, 0.75, 0] }}
//             transition={{ duration: 0.9, ease: [0.4, 0, 0.2, 1] }}
//             className="absolute inset-0 z-10 pointer-events-none w-[60%] h-[200%] -top-[50%] -rotate-12 bg-gradient-to-r from-transparent via-white/80 to-transparent blur-md"
//           />
//         )}
//       </AnimatePresence>

//       {/* 3. Hero Content (Updated Text & Red Tag) */}
//       <div className="relative z-20 max-w-xl mt-auto mb-8">
//         <span className="uppercase tracking-[0.25em] text-xs md:text-sm text-red-600 font-bold">
//           NEW COLLECTION
//         </span>
//         <h1 className="text-4xl md:text-6xl font-black tracking-tight my-3 leading-[1.1] uppercase">
//           DEFINE YOUR <br /> OWN STYLE
//         </h1>
//         <p className="text-sm md:text-base text-neutral-300 font-light leading-relaxed">
//           Premium fabrics. Timeless designs. <br />
//           Made for the modern generation.
//         </p>

//         {/* Action Buttons */}
//         <div className="mt-6 flex items-center gap-4">
//           <Link
//             to="/shop?category=men"
//             className="rounded-sm bg-white px-8 py-3 text-xs font-bold uppercase tracking-wider text-neutral-950 transition-colors duration-200 hover:bg-neutral-200"
//           >
//             SHOP MEN
//           </Link>
//           <Link
//             to="/shop?category=women"
//             className="rounded-sm border border-white px-8 py-3 text-xs font-bold uppercase tracking-wider text-white transition-colors duration-200 hover:bg-white/10"
//           >
//             SHOP WOMEN
//           </Link>
//         </div>
//       </div>

//       {/* 4. Bottom Right Logo Placement (Asset Image Used) */}
//       <div className="absolute right-8 md:right-16 bottom-16 z-20 flex items-center justify-end">
//         <img
//           src={jaajLogo}
//           alt="JAAJ Logo"
//           className="w-48 md:w-64 h-auto object-contain"
//         />
//       </div>

//       {/* 5. Manual Controls */}
//       <button
//         onClick={handlePrev}
//         aria-label="Previous slide"
//         className="absolute left-4 top-1/2 -translate-y-1/2 z-30 p-2.5 rounded-full bg-black/40 text-white hover:bg-black/70 backdrop-blur-sm border border-white/20 transition-all duration-200"
//       >
//         <ChevronLeft className="w-6 h-6" />
//       </button>

//       <button
//         onClick={handleNext}
//         aria-label="Next slide"
//         className="absolute right-4 top-1/2 -translate-y-1/2 z-30 p-2.5 rounded-full bg-black/40 text-white hover:bg-black/70 backdrop-blur-sm border border-white/20 transition-all duration-200"
//       >
//         <ChevronRight className="w-6 h-6" />
//       </button>

//       {/* 6. Number/Dot Indicators */}
//       <div className="absolute bottom-6 left-8 md:left-16 z-30 flex gap-3">
//         {HERO_IMAGES.map((_, idx) => (
//           <button
//             key={idx}
//             onClick={() => {
//               if (!isAnimating && idx !== currentIndex) {
//                 triggerSlide(idx);
//               }
//             }}
//             aria-label={`Go to slide ${idx + 1}`}
//             className={`h-1 transition-all duration-300 ${
//               currentIndex === idx
//                 ? "w-8 bg-red-600"
//                 : "w-4 bg-white/40 hover:bg-white/70"
//             }`}
//           />
//         ))}
//       </div>
//     </section>
//   );
// }

import { useState, useEffect, useCallback } from "react";
import { Link } from "react-router-dom";
import { motion, AnimatePresence } from "framer-motion";
import { ChevronLeft, ChevronRight } from "lucide-react";
import jaajLogo from "@/assets/imagery/jaaj logo-02.png";

const HERO_IMAGES = [
  "https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1920&q=80",
  "https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=1920&q=80",
  "https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=1920&q=80",
];

export function Hero() {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isAnimating, setIsAnimating] = useState(false);
  const [isPaused, setIsPaused] = useState(false);

  const triggerSlide = useCallback((nextIndex: number) => {
    setIsAnimating(true);

    setTimeout(() => {
      setCurrentIndex(nextIndex);
    }, 350);

    setTimeout(() => {
      setIsAnimating(false);
    }, 1000);
  }, []);

  const handleNext = useCallback(() => {
    if (isAnimating) return;
    setCurrentIndex((prevIndex) => {
      const next = (prevIndex + 1) % HERO_IMAGES.length;
      triggerSlide(next);
      return prevIndex;
    });
  }, [isAnimating, triggerSlide]);

  const handlePrev = useCallback(() => {
    if (isAnimating) return;
    setCurrentIndex((prevIndex) => {
      const prev = (prevIndex - 1 + HERO_IMAGES.length) % HERO_IMAGES.length;
      triggerSlide(prev);
      return prevIndex;
    });
  }, [isAnimating, triggerSlide]);

  // Auto-Slide Interval Timer
  useEffect(() => {
    if (isPaused || isAnimating) return;

    const timer = setInterval(() => {
      setCurrentIndex((prevIndex) => {
        const next = (prevIndex + 1) % HERO_IMAGES.length;
        triggerSlide(next);
        return prevIndex;
      });
    }, 5000);

    return () => clearInterval(timer);
  }, [isPaused, isAnimating, triggerSlide]);

  return (
    <section
      className="relative flex h-[85vh] min-h-[600px] w-full select-none flex-col justify-between overflow-hidden bg-neutral-950 p-8 text-white md:p-16"
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
    >
      {/* 1. Background Image Cross-Fade Slider */}
      <div className="absolute inset-0 z-0">
        <AnimatePresence mode="sync">
          <motion.div
            key={currentIndex}
            className="absolute inset-0 bg-cover bg-center"
            style={{ backgroundImage: `url(${HERO_IMAGES[currentIndex]})` }}
            initial={{ opacity: 0, scale: 1.05 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 1.2, ease: "easeInOut" }}
          />
        </AnimatePresence>

        {/* Dark Overlay for Text Readability */}
        <div className="absolute inset-0 bg-black/50" />
      </div>

      {/* 2. White Light Sweep Beam Animation Overlay */}
      <AnimatePresence>
        {isAnimating && (
          <motion.div
            key="light-sweep"
            initial={{ x: "-100%", opacity: 0 }}
            animate={{ x: "200%", opacity: [0, 0.75, 0] }}
            transition={{ duration: 0.9, ease: [0.4, 0, 0.2, 1] }}
            className="pointer-events-none absolute -top-[50%] z-10 h-[200%] w-[60%] -rotate-12 bg-gradient-to-r from-transparent via-white/80 to-transparent blur-md"
          />
        )}
      </AnimatePresence>

      {/* 3. Hero Content (Updated Text & Red Tag) */}
      <div className="relative z-20 mb-8 mt-auto max-w-xl">
        <span className="text-xs font-bold uppercase tracking-[0.25em] text-red-600 md:text-sm">
          NEW COLLECTION
        </span>
        <h1 className="my-3 font-black uppercase leading-[1.1] tracking-tight text-4xl md:text-6xl">
          DEFINE YOUR <br /> OWN STYLE
        </h1>
        <p className="font-light leading-relaxed text-neutral-300 text-sm md:text-base">
          Premium fabrics. Timeless designs. <br />
          Made for the modern generation.
        </p>

        {/* Action Buttons (2 Column Stacked Layout) */}
        <div className="mt-6 grid max-w-sm grid-cols-2 gap-x-4 gap-y-2 sm:max-w-md">
          {/* Column 1: Men & Kids */}
          <div className="flex flex-col gap-2">
            <Link
              to="/shop?category=men"
              className="flex items-center justify-center rounded-sm bg-white py-3 font-bold text-xs uppercase tracking-wider text-neutral-950 transition-colors duration-200 hover:bg-neutral-200"
            >
              SHOP MEN
            </Link>
            <Link
              to="/shop?category=kids"
              className="flex items-center justify-center rounded-sm border border-white/80 py-[2px] font-semibold text-[10px] uppercase tracking-wider text-white transition-colors duration-200 hover:bg-white/10 sm:text-[11px]"
            >
              SHOP KIDS
            </Link>
          </div>

          {/* Column 2: Women & Accessories */}
          <div className="flex flex-col gap-2">
            <Link
              to="/shop?category=women"
              className="flex items-center justify-center rounded-sm border border-white py-3 font-bold text-xs uppercase tracking-wider text-white transition-colors duration-200 hover:bg-white/10"
            >
              SHOP WOMEN
            </Link>
            <Link
              to="/shop?category=accessories"
              className="flex items-center justify-center rounded-sm bg-white/90 py-[2px] font-semibold text-[10px] uppercase tracking-wider text-neutral-950 transition-colors duration-200 hover:bg-white sm:text-[11px]"
            >
              SHOP ACCESSORIES
            </Link>
          </div>
        </div>
      </div>

      {/* 4. Bottom Right Logo Placement */}
      <div className="absolute bottom-16 right-8 z-20 flex items-center justify-end md:right-16">
        <img
          src={jaajLogo}
          alt="JAAJ Logo"
          className="h-auto w-48 object-contain md:w-64"
        />
      </div>

      {/* 5. Manual Controls */}
      <button
        onClick={handlePrev}
        aria-label="Previous slide"
        className="absolute left-4 top-1/2 z-30 -translate-y-1/2 rounded-full border border-white/20 bg-black/40 p-2.5 text-white transition-all duration-200 hover:bg-black/70 backdrop-blur-sm"
      >
        <ChevronLeft className="h-6 w-6" />
      </button>

      <button
        onClick={handleNext}
        aria-label="Next slide"
        className="absolute right-4 top-1/2 z-30 -translate-y-1/2 rounded-full border border-white/20 bg-black/40 p-2.5 text-white transition-all duration-200 hover:bg-black/70 backdrop-blur-sm"
      >
        <ChevronRight className="h-6 w-6" />
      </button>

      {/* 6. Number/Dot Indicators */}
      <div className="absolute bottom-6 left-8 z-30 flex gap-3 md:left-16">
        {HERO_IMAGES.map((_, idx) => (
          <button
            key={idx}
            onClick={() => {
              if (!isAnimating && idx !== currentIndex) {
                triggerSlide(idx);
              }
            }}
            aria-label={`Go to slide ${idx + 1}`}
            className={`h-1 transition-all duration-300 ${
              currentIndex === idx
                ? "w-8 bg-red-600"
                : "w-4 bg-white/40 hover:bg-white/70"
            }`}
          />
        ))}
      </div>
    </section>
  );
}
