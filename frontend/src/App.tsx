// import { Suspense, lazy, useEffect } from "react";
// import { BrowserRouter, Route, Routes, useLocation } from "react-router-dom";
// import { Header } from "@/components/layout/Header";
// import { Footer } from "@/components/layout/Footer";
// import { MiniCartDrawer } from "@/components/cart/MiniCartDrawer";
// import { SearchOverlay } from "@/components/search/SearchOverlay";
// import { ErrorBoundary } from "@/components/ErrorBoundary";
// import { Logo } from "@/components/brand/Logo";
// import { Spinner } from "@/components/ui/primitives";
// import { applyTheme, useThemeStore } from "@/store/themeStore";
// import { useUiStore } from "@/store/uiStore";
// import { cn } from "@/utils/cn";

// /* Route-level code splitting with React.lazy + Suspense (Part 4 §93) */
// const HomePage = lazy(() => import("@/pages/HomePage"));
// const ShopPage = lazy(() => import("@/pages/ShopPage"));
// const ProductPage = lazy(() => import("@/pages/ProductPage"));
// const CartPage = lazy(() => import("@/pages/CartPage"));
// const WishlistPage = lazy(() => import("@/pages/WishlistPage"));
// const CheckoutPage = lazy(() => import("@/pages/CheckoutPage"));
// const OrderSuccessPage = lazy(() => import("@/pages/OrderSuccessPage"));
// const OrderFailurePage = lazy(() => import("@/pages/OrderFailurePage"));
// const AccountPage = lazy(() => import("@/pages/AccountPage"));
// const ContactPage = lazy(() => import("@/pages/ContactPage"));
// const FaqPage = lazy(() => import("@/pages/FaqPage"));
// const ShippingPage = lazy(() => import("@/pages/ShippingPage"));
// const ReturnsPage = lazy(() => import("@/pages/ReturnsPage"));
// const PrivacyPage = lazy(() => import("@/pages/PrivacyPage"));
// const TermsPage = lazy(() => import("@/pages/TermsPage"));
// const AuthLayout = lazy(() => import("@/pages/auth/AuthLayout"));
// const LoginPage = lazy(() => import("@/pages/auth/LoginPage"));
// const RegisterPage = lazy(() => import("@/pages/auth/RegisterPage"));
// const ForgotPasswordPage = lazy(() => import("@/pages/auth/ForgotPasswordPage"));
// const NotFoundPage = lazy(() => import("@/pages/NotFoundPage"));

// function ScrollToTop() {
//   const { pathname } = useLocation();
//   useEffect(() => {
//     window.scrollTo({ top: 0, behavior: "instant" as ScrollBehavior });
//   }, [pathname]);
//   return null;
// }

// function RouteFallback() {
//   return (
//     <div className="flex min-h-[60vh] flex-col items-center justify-center gap-6">
//       <Logo asLink={false} className="opacity-40" />
//       <Spinner />
//     </div>
//   );
// }

// function Toasts() {
//   const toasts = useUiStore((s) => s.toasts);
//   return (
//     <div
//       aria-live="polite"
//       role="status"
//       className="pointer-events-none fixed inset-x-0 bottom-5 z-[60] flex flex-col items-center gap-2 px-4"
//     >
//       {toasts.map((toast) => (
//         <p
//           key={toast.id}
//           className={cn(
//             "animate-fade-up bg-ink px-5 py-3 text-xs font-bold tracking-[0.12em] text-paper uppercase shadow-xl [animation-duration:0.35s]",
//             "dark:bg-linen dark:text-nox"
//           )}
//         >
//           {toast.message}
//         </p>
//       ))}
//     </div>
//   );
// }

// export default function App() {
//   useEffect(() => {
//     applyTheme(useThemeStore.getState().theme);
//   }, []);

//   return (
//     <BrowserRouter>
//       <ErrorBoundary>
//         <ScrollToTop />
//         <a
//           href="#main"
//           className="sr-only z-[70] focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:bg-ink focus:px-4 focus:py-2 focus:text-xs focus:font-bold focus:text-paper dark:focus:bg-linen dark:focus:text-nox"
//         >
//           Skip to content
//         </a>
//         <div className="flex min-h-screen flex-col bg-paper text-ink dark:bg-nox dark:text-linen">
//           <Header />
//           <main id="main" className="flex-1">
//             <Suspense fallback={<RouteFallback />}>
//               <Routes>
//                 <Route path="/" element={<HomePage />} />
//                 <Route path="/shop" element={<ShopPage />} />
//                 <Route path="/product/:slug" element={<ProductPage />} />
//                 <Route path="/cart" element={<CartPage />} />
//                 <Route path="/wishlist" element={<WishlistPage />} />
//                 <Route path="/checkout" element={<CheckoutPage />} />
//                 <Route path="/order/success/:orderId" element={<OrderSuccessPage />} />
//                 <Route path="/order/failed" element={<OrderFailurePage />} />
//                 <Route path="/account" element={<AccountPage />} />
//                 <Route path="/contact" element={<ContactPage />} />
//                 <Route path="/faq" element={<FaqPage />} />
//                 <Route path="/shipping" element={<ShippingPage />} />
//                 <Route path="/returns" element={<ReturnsPage />} />
//                 <Route path="/privacy" element={<PrivacyPage />} />
//                 <Route path="/terms" element={<TermsPage />} />
//                 <Route element={<AuthLayout />}>
//                   <Route path="/login" element={<LoginPage />} />
//                   <Route path="/register" element={<RegisterPage />} />
//                   <Route path="/forgot-password" element={<ForgotPasswordPage />} />
//                 </Route>
//                 <Route path="*" element={<NotFoundPage />} />
//               </Routes>
//             </Suspense>
//           </main>
//           <Footer />
//         </div>
//         <MiniCartDrawer />
//         <SearchOverlay />
//         <Toasts />
//       </ErrorBoundary>
//     </BrowserRouter>
//   );
// }

import { Suspense, lazy, useEffect } from "react";
import { BrowserRouter, Route, Routes, useLocation } from "react-router-dom";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { MiniCartDrawer } from "@/components/cart/MiniCartDrawer";
import { SearchOverlay } from "@/components/search/SearchOverlay";
import { MobileNavDrawer } from "@/components/layout/MobileNavDrawer";
import { ConsentBanner } from "@/components/ConsentBanner";
import { ErrorBoundary } from "@/components/ErrorBoundary";
import { Logo } from "@/components/brand/Logo";
import { Spinner } from "@/components/ui/primitives";
import { applyTheme, useThemeStore } from "@/store/themeStore";
import { useUiStore } from "@/store/uiStore";
import { useCatalogStore } from "@/store/catalogStore";
import { useAuthStore } from "@/store/authStore";
import { useCartStore } from "@/store/cartStore";
import { trackPageView } from "@/lib/marketing";
import { cn } from "@/utils/cn";

/* Route-level code splitting with React.lazy + Suspense (Part 4 §93) */
const HomePage = lazy(() => import("@/pages/HomePage"));
const ShopPage = lazy(() => import("@/pages/ShopPage"));
const ProductPage = lazy(() => import("@/pages/ProductPage"));
const CartPage = lazy(() => import("@/pages/CartPage"));
const WishlistPage = lazy(() => import("@/pages/WishlistPage"));
const CheckoutPage = lazy(() => import("@/pages/CheckoutPage"));
const OrderSuccessPage = lazy(() => import("@/pages/OrderSuccessPage"));
const OrderFailurePage = lazy(() => import("@/pages/OrderFailurePage"));
const AccountPage = lazy(() => import("@/pages/AccountPage"));
const ContactPage = lazy(() => import("@/pages/ContactPage"));
const FaqPage = lazy(() => import("@/pages/FaqPage"));
const ShippingPage = lazy(() => import("@/pages/ShippingPage"));
const ReturnsPage = lazy(() => import("@/pages/ReturnsPage"));
const PrivacyPage = lazy(() => import("@/pages/PrivacyPage"));
const TermsPage = lazy(() => import("@/pages/TermsPage"));
const AuthLayout = lazy(() => import("@/pages/auth/AuthLayout"));
const LoginPage = lazy(() => import("@/pages/auth/LoginPage"));
const RegisterPage = lazy(() => import("@/pages/auth/RegisterPage"));
const ForgotPasswordPage = lazy(
  () => import("@/pages/auth/ForgotPasswordPage"),
);
const AdminProductsPage = lazy(() => import("@/pages/admin/AdminProductsPage"));
const AdminCustomersPage = lazy(() => import("@/pages/admin/AdminCustomersPage"));
const AdminOrdersPage = lazy(() => import("@/pages/admin/AdminOrdersPage"));
const AdminReviewsPage = lazy(() => import("@/pages/admin/AdminReviewsPage"));
const AdminAnalyticsPage = lazy(() => import("@/pages/admin/AdminAnalyticsPage"));
const AdminAuditPage = lazy(() => import("@/pages/admin/AdminAuditPage"));
const NotificationsPage = lazy(() => import("@/pages/NotificationsPage"));
const InfoRoutePage = lazy(() => import("@/pages/InfoRoutePage"));
const NotFoundPage = lazy(() => import("@/pages/NotFoundPage"));

function ScrollToTop() {
  const { pathname } = useLocation();
  useEffect(() => {
    window.scrollTo({ top: 0, behavior: "instant" as ScrollBehavior });
  }, [pathname]);
  return null;
}

function RouteTracker() {
  const { pathname, search } = useLocation();
  useEffect(() => {
    trackPageView(`${pathname}${search}`);
  }, [pathname, search]);
  return null;
}

function RouteFallback() {
  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center gap-6">
      <Logo asLink={false} className="opacity-40" />
      <Spinner />
    </div>
  );
}

function Toasts() {
  const toasts = useUiStore((s) => s.toasts);
  return (
    <div
      aria-live="polite"
      role="status"
      className="pointer-events-none fixed inset-x-0 bottom-5 z-[60] flex flex-col items-center gap-2 px-4"
    >
      {toasts.map((toast) => (
        <p
          key={toast.id}
          className={cn(
            "animate-fade-up bg-ink px-5 py-3 text-xs font-bold tracking-[0.12em] text-paper uppercase shadow-xl [animation-duration:0.35s]",
            "dark:bg-linen dark:text-nox",
          )}
        >
          {toast.message}
        </p>
      ))}
    </div>
  );
}

export default function App() {
  const loadProducts = useCatalogStore((s) => s.loadProducts);
  const refreshUser = useAuthStore((s) => s.refreshUser);
  const loadCart = useCartStore((s) => s.loadCart);
  const loadWishlist = useUiStore((s) => s.loadWishlist);

  useEffect(() => {
    applyTheme(useThemeStore.getState().theme);
    void loadProducts();
    void refreshUser().catch(() => undefined);
    void loadCart().catch(() => undefined);
    void loadWishlist().catch(() => undefined);
  }, [loadProducts, refreshUser, loadCart, loadWishlist]);

  return (
    <BrowserRouter>
      <ErrorBoundary>
        <ScrollToTop />
        <RouteTracker />
        <a
          href="#main"
          className="sr-only z-[70] focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:bg-ink focus:px-4 focus:py-2 focus:text-xs focus:font-bold focus:text-paper dark:focus:bg-linen dark:focus:text-nox"
        >
          Skip to content
        </a>
        <div className="flex min-h-screen flex-col bg-paper text-ink dark:bg-nox dark:text-linen">
          <Header />
          <main id="main" className="flex-1">
            <Suspense fallback={<RouteFallback />}>
              <Routes>
                <Route path="/" element={<HomePage />} />
                <Route path="/shop" element={<ShopPage />} />
                <Route path="/product/:slug" element={<ProductPage />} />
                <Route path="/cart" element={<CartPage />} />
                <Route path="/wishlist" element={<WishlistPage />} />
                <Route path="/checkout" element={<CheckoutPage />} />
                <Route
                  path="/order/success/:orderId"
                  element={<OrderSuccessPage />}
                />
                <Route path="/order/failed" element={<OrderFailurePage />} />
                <Route path="/account" element={<AccountPage />} />
                <Route path="/contact" element={<ContactPage />} />
                <Route path="/faq" element={<FaqPage />} />
                <Route path="/shipping" element={<ShippingPage />} />
                <Route path="/returns" element={<ReturnsPage />} />
                <Route path="/privacy" element={<PrivacyPage />} />
                <Route path="/terms" element={<TermsPage />} />
                <Route path="/notifications" element={<NotificationsPage />} />
                <Route path="/admin/products" element={<AdminProductsPage />} />
                <Route path="/admin/customers" element={<AdminCustomersPage />} />
                <Route path="/admin/orders" element={<AdminOrdersPage />} />
                <Route path="/admin/reviews" element={<AdminReviewsPage />} />
                <Route path="/admin/analytics" element={<AdminAnalyticsPage />} />
                <Route path="/admin/audit-log" element={<AdminAuditPage />} />
                <Route path="/our-story" element={<InfoRoutePage />} />
                <Route path="/careers" element={<InfoRoutePage />} />
                <Route path="/stores" element={<InfoRoutePage />} />
                <Route path="/press" element={<InfoRoutePage />} />
                <Route path="/sustainability" element={<InfoRoutePage />} />
                <Route path="/refund-policy" element={<InfoRoutePage />} />
                <Route path="/cookies" element={<InfoRoutePage />} />
                <Route path="/track-order" element={<InfoRoutePage />} />
                <Route path="/size-guide" element={<InfoRoutePage />} />
                <Route element={<AuthLayout />}>
                  <Route path="/login" element={<LoginPage />} />
                  <Route path="/register" element={<RegisterPage />} />
                  <Route
                    path="/forgot-password"
                    element={<ForgotPasswordPage />}
                  />
                </Route>
                <Route path="*" element={<NotFoundPage />} />
              </Routes>
            </Suspense>
          </main>
          <Footer />
        </div>
        {/* 2. নিচে মোবাইল নেভিগেশন কম্পোনেন্ট বসিয়ে দিন */}
        <MobileNavDrawer />
        <MiniCartDrawer />
        <SearchOverlay />
        <Toasts />
        <ConsentBanner />
      </ErrorBoundary>
    </BrowserRouter>
  );
}
