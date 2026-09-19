// import { Link, NavLink } from "react-router-dom";
// import { Heart, Menu, Moon, Search, ShoppingBag, Sun, User } from "lucide-react";
// import { PRIMARY_NAV } from "@/data/navigation";
// import { useCartStore, selectCartCount } from "@/store/cartStore";
// import { useWishlistStore } from "@/store/wishlistStore";
// import { useThemeStore } from "@/store/themeStore";
// import { useUIStore } from "@/store/uiStore";
// import { Logo } from "./Logo";
// import { cn } from "@/utils/cn";

// /**
//  * Header — SOURCE 01 composition:
//  * logo left · centered uppercase navigation · icon cluster right.
//  * Solid background, hairline bottom border, sticky on scroll.
//  */

// function IconButton({
//   label,
//   onClick,
//   children,
//   className,
// }: {
//   label: string;
//   onClick?: () => void;
//   children: React.ReactNode;
//   className?: string;
// }) {
//   return (
//     <button
//       type="button"
//       aria-label={label}
//       onClick={onClick}
//       className={cn(
//         "relative flex h-10 w-10 items-center justify-center text-foreground/85 transition-colors duration-300 hover:text-foreground",
//         className
//       )}
//     >
//       {children}
//     </button>
//   );
// }

// export function Header() {
//   const cartCount = useCartStore(selectCartCount);
//   const wishlistCount = useWishlistStore((s) => s.ids.length);
//   const theme = useThemeStore((s) => s.theme);
//   const toggleTheme = useThemeStore((s) => s.toggleTheme);
//   const setMobileNavOpen = useUIStore((s) => s.setMobileNavOpen);

//   return (
//     <header className="sticky top-0 z-50 border-b border-line bg-background">
//       <div className="mx-auto flex h-16 max-w-[1440px] items-center justify-between gap-4 px-5 md:px-10 lg:h-[72px]">
//         {/* left: burger (mobile) + official wordmark */}
//         <div className="flex items-center gap-1 lg:gap-0">
//           <IconButton label="Open menu" onClick={() => setMobileNavOpen(true)} className="-ml-2 lg:hidden">
//             <Menu size={19} strokeWidth={1.6} absoluteStrokeWidth />
//           </IconButton>
//           <Link to="/" aria-label="JAAJ — home" className="flex items-center">
//   <span className="inline-flex origin-left translate-y-[2px] scale-145 items-center lg:translate-y-[8px] lg:scale-[1.8]">
//     <Logo variant="wordmark" width={86} className="lg:w-[96px]" />
//   </span>
// </Link>
//         </div>

//         {/* center: primary navigation */}
//         <nav aria-label="Primary" className="hidden items-center gap-7 lg:flex xl:gap-9">
//           {PRIMARY_NAV.map((item) => (
//             <NavLink
//               key={item.label}
//               to={item.href}
//               className={({ isActive }) =>
//                 cn(
//                   "group relative py-2 font-display text-[11px] font-semibold uppercase tracking-nav transition-colors duration-300",
//                   item.emphasis ? "text-accent" : "text-foreground/80 hover:text-foreground",
//                   isActive && "text-foreground"
//                 )
//               }
//             >
//               {item.label}
//               <span className="absolute inset-x-0 -bottom-px h-px origin-left scale-x-0 bg-current transition-transform duration-300 ease-out group-hover:scale-x-100" />
//             </NavLink>
//           ))}
//         </nav>

//         {/* right: utility icons */}
//         <div className="flex items-center gap-0.5">
//           <IconButton
//             label={theme === "light" ? "Switch to dark mode" : "Switch to light mode"}
//             onClick={toggleTheme}
//             className="hidden sm:flex"
//           >
//             {theme === "light" ? (
//               <Moon size={17} strokeWidth={1.6} absoluteStrokeWidth />
//             ) : (
//               <Sun size={18} strokeWidth={1.6} absoluteStrokeWidth />
//             )}
//           </IconButton>
//           <IconButton label="Search">
//             <Search size={18} strokeWidth={1.6} absoluteStrokeWidth />
//           </IconButton>
//           <Link to="/account" aria-label="Account" className="hidden sm:block">
//             <span className="flex h-10 w-10 items-center justify-center text-foreground/85 transition-colors duration-300 hover:text-foreground">
//               <User size={18} strokeWidth={1.6} absoluteStrokeWidth />
//             </span>
//           </Link>
//           <Link to="/wishlist" aria-label="Wishlist" className="hidden sm:block">
//             <span className="relative flex h-10 w-10 items-center justify-center text-foreground/85 transition-colors duration-300 hover:text-foreground">
//               <Heart size={18} strokeWidth={1.6} absoluteStrokeWidth />
//               {wishlistCount > 0 && (
//                 <span className="absolute right-1.5 top-1.5 h-1.5 w-1.5 bg-accent" aria-hidden="true" />
//               )}
//             </span>
//           </Link>
//           <Link to="/cart" aria-label={`Cart — ${cartCount} items`}>
//             <span className="relative flex h-10 w-10 items-center justify-center text-foreground/85 transition-colors duration-300 hover:text-foreground">
//               <ShoppingBag size={18} strokeWidth={1.6} absoluteStrokeWidth />
//               {cartCount > 0 && (
//                 <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center bg-accent px-1 font-display text-[9px] font-bold leading-none text-white">
//                   {cartCount}
//                 </span>
//               )}
//             </span>
//           </Link>
//         </div>
//       </div>
//     </header>
//   );
// // }

// import { Link, NavLink } from "react-router-dom";
// import {
//   Heart,
//   Menu,
//   Moon,
//   Search,
//   ShoppingBag,
//   Sun,
//   User,
// } from "lucide-react";
// import { PRIMARY_NAV } from "@/data/navigation";
// import { useCartStore, selectCartCount } from "@/store/cartStore";
// import { useWishlistStore } from "@/store/wishlistStore";
// import { useThemeStore } from "@/store/themeStore";
// import { useUiStore } from "@/store/uiStore";
// import { Logo } from "./Logo";
// import { cn } from "@/utils/cn";

// /**
//  * Header — SOURCE 01 composition:
//  * logo left · centered uppercase navigation · icon cluster right.
//  * Solid background, hairline bottom border, sticky on scroll.
//  */

// function IconButton({
//   label,
//   onClick,
//   expanded,
//   controls,
//   children,
//   className,
// }: {
//   label: string;
//   onClick?: () => void;
//   expanded?: boolean;
//   controls?: string;
//   children: React.ReactNode;
//   className?: string;
// }) {
//   return (
//     <button
//       type="button"
//       aria-label={label}
//       aria-expanded={expanded}
//       aria-controls={controls}
//       onClick={onClick}
//       className={cn(
//         "relative flex h-10 w-10 items-center justify-center text-foreground/85 transition-colors duration-300 hover:text-foreground",
//         className,
//       )}
//     >
//       {children}
//     </button>
//   );
// }

// export function Header() {
//   const cartCount = useCartStore(selectCartCount);
//   const wishlistCount = useWishlistStore((s) => s.ids.length);
//   const theme = useThemeStore((s) => s.theme);
//   const toggleTheme = useThemeStore((s) => s.toggleTheme);
//   const mobileNavOpen = useUiStore((s) => s.mobileNavOpen);
//   const toggleMobileNav = useUiStore((s) => s.toggleMobileNav);
//   const setSearchOpen = useUiStore((s) => s.setSearchOpen);

//   return (
//     <header className="sticky top-0 z-50 border-b border-line bg-background">
//       <div className="mx-auto flex h-16 max-w-[1440px] items-center justify-between gap-4 px-5 md:px-10 lg:h-[72px]">
//         {/* left: burger (mobile) + official wordmark */}
//         <div className="flex items-center gap-1 lg:gap-0">
//           <IconButton
//             label={mobileNavOpen ? "Close menu" : "Open menu"}
//             onClick={toggleMobileNav}
//             expanded={mobileNavOpen}
//             controls="mobile-navigation"
//             className="-ml-2 lg:hidden"
//           >
//             <Menu size={19} strokeWidth={1.6} absoluteStrokeWidth />
//           </IconButton>
//           <Link to="/" aria-label="JAAJ — home" className="flex items-center">
//             <span className="inline-flex origin-left translate-y-[10px]  scale-185 items-center lg:translate-y-[12px] lg:scale-[2.1]">
//               <Logo variant="wordmark" width={86} className="lg:w-[96px]" />
//             </span>
//           </Link>
//         </div>

//         {/* center: primary navigation */}
//         <nav
//           aria-label="Primary"
//           className="hidden items-center gap-7 lg:flex xl:gap-9"
//         >
//           {PRIMARY_NAV.map((item) => (
//             <NavLink
//               key={item.label}
//               to={item.href}
//               className={({ isActive }) =>
//                 cn(
//                   "group relative py-2 font-display text-[11px] font-semibold uppercase tracking-nav transition-colors duration-300",
//                   item.emphasis
//                     ? "text-accent"
//                     : "text-foreground/80 hover:text-foreground",
//                   isActive && "text-foreground",
//                 )
//               }
//             >
//               {item.label}
//               <span className="absolute inset-x-0 -bottom-px h-px origin-left scale-x-0 bg-current transition-transform duration-300 ease-out group-hover:scale-x-100" />
//             </NavLink>
//           ))}
//         </nav>

//         {/* right: utility icons */}
//         <div className="flex items-center gap-0.5">
//           <IconButton
//             label={
//               theme === "light" ? "Switch to dark mode" : "Switch to light mode"
//             }
//             onClick={toggleTheme}
//             className="hidden sm:flex"
//           >
//             {theme === "light" ? (
//               <Moon size={17} strokeWidth={1.6} absoluteStrokeWidth />
//             ) : (
//               <Sun size={18} strokeWidth={1.6} absoluteStrokeWidth />
//             )}
//           </IconButton>
//           <IconButton label="Search" onClick={() => setSearchOpen(true)}>
//             <Search size={18} strokeWidth={1.6} absoluteStrokeWidth />
//           </IconButton>
//           <Link to="/account" aria-label="Account" className="hidden sm:block">
//             <span className="flex h-10 w-10 items-center justify-center text-foreground/85 transition-colors duration-300 hover:text-foreground">
//               <User size={18} strokeWidth={1.6} absoluteStrokeWidth />
//             </span>
//           </Link>
//           <Link
//             to="/wishlist"
//             aria-label="Wishlist"
//             className="hidden sm:block"
//           >
//             <span className="relative flex h-10 w-10 items-center justify-center text-foreground/85 transition-colors duration-300 hover:text-foreground">
//               <Heart size={18} strokeWidth={1.6} absoluteStrokeWidth />
//               {wishlistCount > 0 && (
//                 <span
//                   className="absolute right-1.5 top-1.5 h-1.5 w-1.5 bg-accent"
//                   aria-hidden="true"
//                 />
//               )}
//             </span>
//           </Link>
//           <Link to="/cart" aria-label={`Cart — ${cartCount} items`}>
//             <span className="relative flex h-10 w-10 items-center justify-center text-foreground/85 transition-colors duration-300 hover:text-foreground">
//               <ShoppingBag size={18} strokeWidth={1.6} absoluteStrokeWidth />
//               {cartCount > 0 && (
//                 <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center bg-accent px-1 font-display text-[9px] font-bold leading-none text-white">
//                   {cartCount}
//                 </span>
//               )}
//             </span>
//           </Link>
//         </div>
//       </div>
//     </header>
//   );
// }

import { Link, NavLink } from "react-router-dom";
import {
  Heart,
  Menu,
  Moon,
  Search,
  ShoppingBag,
  Sun,
  User,
} from "lucide-react";
import { PRIMARY_NAV } from "@/data/navigation";
import { useCartStore, selectCartCount } from "@/store/cartStore";
import { useThemeStore } from "@/store/themeStore";
import { useUiStore } from "@/store/uiStore";
import logoTextImg from "@/assets/imagery/TEXT-01-01.png";
import { cn } from "@/utils/cn";

function IconButton({
  label,
  onClick,
  expanded,
  controls,
  children,
  className,
}: {
  label: string;
  onClick?: () => void;
  expanded?: boolean;
  controls?: string;
  children: React.ReactNode;
  className?: string;
}) {
  return (
    <button
      type="button"
      aria-label={label}
      aria-expanded={expanded}
      aria-controls={controls}
      onClick={onClick}
      className={cn(
        "relative flex h-10 w-10 items-center justify-center text-foreground/85 transition-colors duration-300 hover:text-foreground",
        className,
      )}
    >
      {children}
    </button>
  );
}

export function Header() {
  const cartCount = useCartStore(selectCartCount);
  const wishlistCount = useUiStore((s) => s.wishlist.length);
  const theme = useThemeStore((s) => s.theme);
  const toggleTheme = useThemeStore((s) => s.toggleTheme);
  const mobileNavOpen = useUiStore((s) => s.mobileNavOpen);
  const toggleMobileNav = useUiStore((s) => s.toggleMobileNav);
  const setSearchOpen = useUiStore((s) => s.setSearchOpen);

  return (
    <header className="sticky top-0 z-50 border-b border-line bg-background">
      <div className="mx-auto flex h-16 max-w-[1440px] items-center justify-between gap-4 px-5 md:px-10 lg:h-[72px]">
        {/* left: burger (mobile) + official wordmark */}
        <div className="flex items-center gap-1 lg:gap-0">
          <IconButton
            label={mobileNavOpen ? "Close menu" : "Open menu"}
            onClick={toggleMobileNav}
            expanded={mobileNavOpen}
            controls="mobile-navigation"
            className="-ml-2 lg:hidden"
          >
            <Menu size={19} strokeWidth={1.6} absoluteStrokeWidth />
          </IconButton>
          <Link to="/" aria-label="JAAJ — home" className="flex items-center">
            <img
              src={logoTextImg}
              alt="JAAJ Logo"
              className="h-9 w-auto object-contain dark:invert lg:h-13"
            />
          </Link>
        </div>

        {/* center: primary navigation */}
        <nav
          aria-label="Primary"
          className="hidden items-center gap-7 lg:flex xl:gap-9"
        >
          {PRIMARY_NAV.map((item) => (
            <NavLink
              key={item.label}
              to={item.href}
              className={({ isActive }) =>
                cn(
                  "group relative py-2 font-display text-[11px] font-semibold uppercase tracking-nav transition-colors duration-300",
                  item.emphasis
                    ? "text-accent"
                    : "text-foreground/80 hover:text-foreground",
                  isActive && "text-foreground",
                )
              }
            >
              {item.label}
              <span className="absolute inset-x-0 -bottom-px h-px origin-left scale-x-0 bg-current transition-transform duration-300 ease-out group-hover:scale-x-100" />
            </NavLink>
          ))}
        </nav>

        {/* right: utility icons */}
        <div className="flex items-center gap-0.5">
          <IconButton
            label={
              theme === "light" ? "Switch to dark mode" : "Switch to light mode"
            }
            onClick={toggleTheme}
            className="hidden sm:flex"
          >
            {theme === "light" ? (
              <Moon size={17} strokeWidth={1.6} absoluteStrokeWidth />
            ) : (
              <Sun size={18} strokeWidth={1.6} absoluteStrokeWidth />
            )}
          </IconButton>
          <IconButton label="Search" onClick={() => setSearchOpen(true)}>
            <Search size={18} strokeWidth={1.6} absoluteStrokeWidth />
          </IconButton>

          {/* Mobile and Desktop visual - hidden sm:block সরানো হয়েছে */}
          <Link to="/account" aria-label="Account">
            <span className="flex h-10 w-10 items-center justify-center text-foreground/85 transition-colors duration-300 hover:text-foreground">
              <User size={18} strokeWidth={1.6} absoluteStrokeWidth />
            </span>
          </Link>

          <Link
            to="/wishlist"
            aria-label="Wishlist"
            className="hidden sm:block"
          >
            <span className="relative flex h-10 w-10 items-center justify-center text-foreground/85 transition-colors duration-300 hover:text-foreground">
              <Heart size={18} strokeWidth={1.6} absoluteStrokeWidth />
              {wishlistCount > 0 && (
                <span
                  className="absolute right-1.5 top-1.5 h-1.5 w-1.5 bg-accent"
                  aria-hidden="true"
                />
              )}
            </span>
          </Link>
          <Link to="/cart" aria-label={`Cart — ${cartCount} items`}>
            <span className="relative flex h-10 w-10 items-center justify-center text-foreground/85 transition-colors duration-300 hover:text-foreground">
              <ShoppingBag size={18} strokeWidth={1.6} absoluteStrokeWidth />
              {cartCount > 0 && (
                <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center bg-accent px-1 font-display text-[9px] font-bold leading-none text-white">
                  {cartCount}
                </span>
              )}
            </span>
          </Link>
        </div>
      </div>
    </header>
  );
}
