import {useEffect} from "react";
import { Outlet, useLocation } from "react-router-dom";
import { AnnouncementBar } from "./AnnouncementBar";
import { Header } from "./Header";
import { Footer } from "./Footer";
import { MobileNavDrawer } from "./MobileNavDrawer";

/**
 * Site layout chrome:
 * AnnouncementBar (ink, static) → Header (sticky) → page → Footer (ink).
 * MobileNavDrawer renders at root level via portal-free fixed layers.
 */
export function SiteLayout() {
  const { pathname } = useLocation();

  useEffect(() => {
    window.scrollTo({ top: 0, left: 0, behavior: "instant" as ScrollBehavior });
  }, [pathname]);

  return (
    <div className="flex min-h-screen flex-col bg-background text-foreground">
      <AnnouncementBar />
      <Header />
      <main className="flex-1">
        <Outlet />
      </main>
      <Footer />
      <MobileNavDrawer />
    </div>
  );
}
